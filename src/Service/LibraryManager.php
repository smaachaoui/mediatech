<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Collection;
use App\Entity\CollectionBook;
use App\Entity\CollectionMovie;
use App\Entity\Movie;
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class LibraryManager
{
    private const DEFAULT_COLLECTION_NAME = 'Non répertorié';
    private const WISHLIST_COLLECTION_NAME = 'Liste d\'envie';

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GoogleBooksService $googleBooks,
        private readonly TmdbService $tmdb,
    ) {}

    public function getDefaultCollection(User $user): Collection
    {
        return $this->getOrCreateSystemCollection($user, self::DEFAULT_COLLECTION_NAME);
    }

    public function getWishlistCollection(User $user): Collection
    {
        return $this->getOrCreateSystemCollection($user, self::WISHLIST_COLLECTION_NAME);
    }

    /**
     * @return bool true si déjà présent, false si nouvel ajout
     */
    public function addToDefaultCollection(User $user, string $kind, string $externalId): bool
    {
        return $this->addToSystemCollection($user, $kind, $externalId, self::DEFAULT_COLLECTION_NAME);
    }

    /**
     * @return bool true si déjà présent, false si nouvel ajout
     */
    public function addToWishlistCollection(User $user, string $kind, string $externalId): bool
    {
        return $this->addToSystemCollection($user, $kind, $externalId, self::WISHLIST_COLLECTION_NAME);
    }

    /**
     * Je centralise l'ajout d'un média dans une collection système ("Non répertorié" ou "Liste d'envie").
     *
     * @return bool true si déjà présent, false si nouvel ajout
     */
    private function addToSystemCollection(User $user, string $kind, string $externalId, string $systemCollectionName): bool
    {
        $kind = trim($kind);
        $externalId = trim($externalId);

        if (!in_array($kind, ['book', 'movie'], true)) {
            throw new \InvalidArgumentException('Unknown kind. Expected "book" or "movie".');
        }
        if ($externalId === '') {
            throw new \InvalidArgumentException('External id cannot be empty.');
        }

        $collection = $this->getOrCreateSystemCollection($user, $systemCollectionName);

        if ($kind === 'book') {
            $book = $this->getOrCreateBookFromApi($externalId);
            return $this->attachBook($collection, $book);
        }

        $movie = $this->getOrCreateMovieFromApi((int) $externalId);
        return $this->attachMovie($collection, $movie);
    }

    private function getOrCreateSystemCollection(User $user, string $name): Collection
    {
        $repo = $this->em->getRepository(Collection::class);

        /** @var Collection|null $collection */
        $collection = $repo->findOneBy([
            'user' => $user,
            'name' => $name,
        ]);

        if ($collection instanceof Collection) {
            return $collection;
        }

        $collection = new Collection();
        $collection->setUser($user);
        $collection->setName($name);
        $collection->setScope(Collection::SCOPE_SYSTEM);
        $collection->setMediaType(Collection::MEDIA_ALL);
        $collection->setVisibility('private');
        $collection->setIsPublished(false);

        $this->em->persist($collection);
        $this->em->flush();

        return $collection;
    }

    private function getOrCreateBookFromApi(string $googleBooksId): Book
    {
        $repo = $this->em->getRepository(Book::class);

        /** @var Book|null $book */
        $book = $repo->findOneBy(['googleBooksId' => $googleBooksId]);
        if ($book instanceof Book) {
            return $book;
        }

        $data = $this->googleBooks->getById($googleBooksId);

        $book = new Book();
        $book->setGoogleBooksId((string) ($data['id'] ?? $googleBooksId));
        $book->setTitle((string) ($data['title'] ?? 'Sans titre'));

        $authors = $data['authors'] ?? [];
        if (is_array($authors) && !empty($authors)) {
            $book->setAuthor(implode(', ', array_map('strval', $authors)));
        }

        $book->setPublisher(isset($data['publisher']) ? (string) $data['publisher'] : null);
        $book->setPageCount(isset($data['pageCount']) ? (int) $data['pageCount'] : null);
        $book->setIsbn(isset($data['isbn']) ? (string) $data['isbn'] : null);
        $book->setCoverImage(isset($data['thumbnail']) ? (string) $data['thumbnail'] : null);
        $book->setSynopsis(isset($data['description']) ? (string) $data['description'] : null);
        $book->setPublicationDate($this->parseFlexibleDate($data['publishedDate'] ?? null));

        $this->em->persist($book);
        $this->em->flush();

        return $book;
    }

    private function getOrCreateMovieFromApi(int $tmdbId): Movie
    {
        if ($tmdbId <= 0) {
            throw new \InvalidArgumentException('TMDB id must be a positive integer.');
        }

        $repo = $this->em->getRepository(Movie::class);

        /** @var Movie|null $movie */
        $movie = $repo->findOneBy(['tmdbId' => $tmdbId]);
        if ($movie instanceof Movie) {
            return $movie;
        }

        $data = $this->tmdb->getById($tmdbId);

        $movie = new Movie();
        $movie->setTmdbId((int) ($data['id'] ?? $tmdbId));
        $movie->setTitle((string) ($data['title'] ?? 'Sans titre'));
        $movie->setSynopsis(isset($data['overview']) ? (string) $data['overview'] : null);
        $movie->setPoster(isset($data['poster']) ? (string) $data['poster'] : null);
        $movie->setReleaseDate($this->parseIsoDate($data['releaseDate'] ?? null));

        $this->em->persist($movie);
        $this->em->flush();

        return $movie;
    }

    private function parseIsoDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $dt ?: null;
    }

    private function parseFlexibleDate(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $v = trim($value);

        if (preg_match('/^\d{4}$/', $v)) {
            return \DateTimeImmutable::createFromFormat('Y-m-d', $v . '-01-01') ?: null;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $v)) {
            return \DateTimeImmutable::createFromFormat('Y-m-d', $v . '-01') ?: null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return \DateTimeImmutable::createFromFormat('Y-m-d', $v) ?: null;
        }

        return null;
    }

    private function attachBook(Collection $collection, Book $book): bool
    {
        $link = new CollectionBook();
        $link->setCollection($collection);
        $link->setBook($book);

        try {
            $this->em->persist($link);
            $this->em->flush();
            return false;
        } catch (UniqueConstraintViolationException) {
            $this->em->detach($link);
            return true;
        }
    }

    private function attachMovie(Collection $collection, Movie $movie): bool
    {
        $link = new CollectionMovie();
        $link->setCollection($collection);
        $link->setMovie($movie);

        try {
            $this->em->persist($link);
            $this->em->flush();
            return false;
        } catch (UniqueConstraintViolationException) {
            $this->em->detach($link);
            return true;
        }
    }
}
