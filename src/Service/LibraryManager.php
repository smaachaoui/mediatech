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

    public const ADD_RESULT_ADDED = 'added';
    public const ADD_RESULT_ALREADY = 'already';
    public const ADD_RESULT_MOVED = 'moved';

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
     * @return self::ADD_RESULT_*
     */
    public function addToDefaultCollection(User $user, string $kind, string $externalId): string
    {
        return $this->addToSystemCollection($user, $kind, $externalId, self::DEFAULT_COLLECTION_NAME);
    }

    /**
     * @return self::ADD_RESULT_*
     */
    public function addToWishlistCollection(User $user, string $kind, string $externalId): string
    {
        return $this->addToSystemCollection($user, $kind, $externalId, self::WISHLIST_COLLECTION_NAME);
    }

    /**
     * Je centralise l'ajout d'un média dans une collection système ("Non répertorié" ou "Liste d'envie").
     *
     * Règles de robustesse :
     * - Je ne peux pas ajouter deux fois le même media à une même collection.
     * - Un media ne peut pas être à la fois dans "Non répertorié" et dans "Liste d'envie".
     *   Si j'ajoute un media à l'une, je le retire automatiquement de l'autre.
     *
     * @return self::ADD_RESULT_*
     */
    private function addToSystemCollection(User $user, string $kind, string $externalId, string $systemCollectionName): string
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

        $otherName = $systemCollectionName === self::DEFAULT_COLLECTION_NAME
            ? self::WISHLIST_COLLECTION_NAME
            : self::DEFAULT_COLLECTION_NAME;
        $otherCollection = $this->getOrCreateSystemCollection($user, $otherName);

        if ($kind === 'book') {
            $book = $this->getOrCreateBookFromApi($externalId);

            return $this->em->wrapInTransaction(function () use ($collection, $otherCollection, $book): string {
                $moved = $this->detachBook($otherCollection, $book);
                $already = $this->attachBook($collection, $book);

                if ($already) {
                    return self::ADD_RESULT_ALREADY;
                }

                return $moved ? self::ADD_RESULT_MOVED : self::ADD_RESULT_ADDED;
            });
        }

        if (!ctype_digit($externalId)) {
            throw new \InvalidArgumentException('TMDB id must be a positive integer.');
        }

        $movie = $this->getOrCreateMovieFromApi((int) $externalId);

        return $this->em->wrapInTransaction(function () use ($collection, $otherCollection, $movie): string {
            $moved = $this->detachMovie($otherCollection, $movie);
            $already = $this->attachMovie($collection, $movie);

            if ($already) {
                return self::ADD_RESULT_ALREADY;
            }

            return $moved ? self::ADD_RESULT_MOVED : self::ADD_RESULT_ADDED;
        });
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
        $googleBooksId = trim($googleBooksId);

        if ($googleBooksId === '') {
            throw new \InvalidArgumentException('Google Books id cannot be empty.');
        }

        $repo = $this->em->getRepository(Book::class);

        /** @var Book|null $book */
        $book = $repo->findOneBy(['googleBooksId' => $googleBooksId]);
        if ($book instanceof Book) {
            return $book;
        }

        $data = $this->googleBooks->getById($googleBooksId);

        if (empty($data) || !isset($data['id'], $data['title'])) {
            throw new \RuntimeException('Livre introuvable (Google Books).');
        }

        $book = new Book();
        $book->setGoogleBooksId((string) $data['id']);
        $book->setTitle((string) $data['title']);

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

        if (empty($data) || !isset($data['id'], $data['title'])) {
            throw new \RuntimeException('Film introuvable (TMDB).');
        }

        $apiId = (int) $data['id'];
        if ($apiId <= 0) {
            throw new \RuntimeException('Film introuvable (TMDB).');
        }

        $movie = new Movie();
        $movie->setTmdbId($apiId);
        $movie->setTitle((string) $data['title']);
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

    /**
     * @return bool true si déjà présent, false si nouvel ajout
     */
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

    /**
     * Je retire un livre d'une collection donnée si le lien existe.
     *
     * @return bool true si je l'ai effectivement retiré, false sinon.
     */
    private function detachBook(Collection $collection, Book $book): bool
    {
        $repo = $this->em->getRepository(CollectionBook::class);

        /** @var CollectionBook|null $link */
        $link = $repo->findOneBy([
            'collection' => $collection,
            'book' => $book,
        ]);

        if (!$link instanceof CollectionBook) {
            return false;
        }

        $this->em->remove($link);
        $this->em->flush();

        return true;
    }

    /**
     * @return bool true si déjà présent, false si nouvel ajout
     */
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

    /**
     * Je retire un film d'une collection donnée si le lien existe.
     *
     * @return bool true si je l'ai effectivement retiré, false sinon.
     */
    private function detachMovie(Collection $collection, Movie $movie): bool
    {
        $repo = $this->em->getRepository(CollectionMovie::class);

        /** @var CollectionMovie|null $link */
        $link = $repo->findOneBy([
            'collection' => $collection,
            'movie' => $movie,
        ]);

        if (!$link instanceof CollectionMovie) {
            return false;
        }

        $this->em->remove($link);
        $this->em->flush();

        return true;
    }
}
