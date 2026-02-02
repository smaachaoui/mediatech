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
    private const DEFAULT_COLLECTION_TYPE = 'system'; // <= 10 chars (champ length:10)

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly GoogleBooksService $googleBooks,
        private readonly TmdbService $tmdb,
    ) {}

    /**
     * Expose la collection système "Non répertorié" (créée si besoin).
     */
    public function getDefaultCollection(User $user): Collection
    {
        return $this->getOrCreateDefaultCollection($user);
    }

    /**
     * Ajoute un média (API) dans la collection “Non répertorié” de l’utilisateur.
     *
     * @return bool true si déjà présent, false si nouvel ajout
     */
    public function addToDefaultCollection(User $user, string $kind, string $externalId): bool
    {
        $kind = trim($kind);
        $externalId = trim($externalId);

        if ($kind !== 'book' && $kind !== 'movie') {
            throw new \InvalidArgumentException('Unknown kind. Expected "book" or "movie".');
        }
        if ($externalId === '') {
            throw new \InvalidArgumentException('External id cannot be empty.');
        }

        $collection = $this->getOrCreateDefaultCollection($user);

        if ($kind === 'book') {
            $book = $this->getOrCreateBookFromApi($externalId);
            return $this->attachBook($collection, $book);
        }

        // kind === movie
        $movie = $this->getOrCreateMovieFromApi((int) $externalId);
        return $this->attachMovie($collection, $movie);
    }

    /**
     * Crée ou récupère la collection par défaut "Non répertorié".
     *
     * IMPORTANT : Collection::type est obligatoire dans ton entité,
     * donc on le renseigne systématiquement.
     */
    private function getOrCreateDefaultCollection(User $user): Collection
    {
        $repo = $this->em->getRepository(Collection::class);

        /** @var Collection|null $collection */
        $collection = $repo->findOneBy([
            'user' => $user,
            'name' => self::DEFAULT_COLLECTION_NAME,
        ]);

        if ($collection instanceof Collection) {
            return $collection;
        }

        $collection = new Collection();
        $collection->setUser($user);
        $collection->setName(self::DEFAULT_COLLECTION_NAME);
        $collection->setType(self::DEFAULT_COLLECTION_TYPE);

        // Par cohérence, on garde la collection "inbox" privée et non publiée
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
        $book->setTitle($data['title'] ?? 'Sans titre');

        // Champs optionnels (selon ton entité Book)
        if (method_exists($book, 'setAuthors')) {
            $book->setAuthors($data['authors'] ?? []);
        }
        if (method_exists($book, 'setPublisher')) {
            $book->setPublisher($data['publisher'] ?? null);
        }
        if (method_exists($book, 'setPublishedDate')) {
            $book->setPublishedDate($data['publishedDate'] ?? null);
        }
        if (method_exists($book, 'setDescription')) {
            $book->setDescription($data['description'] ?? null);
        }
        if (method_exists($book, 'setThumbnail')) {
            $book->setThumbnail($data['thumbnail'] ?? null);
        }
        if (method_exists($book, 'setPageCount')) {
            $book->setPageCount($data['pageCount'] ?? null);
        }
        if (method_exists($book, 'setIsbn')) {
            $book->setIsbn($data['isbn'] ?? null);
        }

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
        $movie->setTitle($data['title'] ?? 'Sans titre');

        // Champs optionnels (selon ton entité Movie)
        if (method_exists($movie, 'setReleaseDate')) {
            $movie->setReleaseDate($data['releaseDate'] ?? null);
        }
        if (method_exists($movie, 'setOverview')) {
            $movie->setOverview($data['overview'] ?? null);
        }
        if (method_exists($movie, 'setPoster')) {
            $movie->setPoster($data['poster'] ?? null);
        }

        $this->em->persist($movie);
        $this->em->flush();

        return $movie;
    }

    /**
     * @return bool true si déjà présent, false sinon
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
            // Ne pas faire clear() : ça détache tout le contexte Doctrine
            $this->em->detach($link);
            return true;
        }
    }

    /**
     * @return bool true si déjà présent, false sinon
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
}
