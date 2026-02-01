<?php

namespace App\Service;

use App\Entity\Book;
use App\Entity\Movie;
use App\Entity\Collection;
use App\Entity\CollectionBook;
use App\Entity\CollectionMovie;
use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class LibraryManager
{
    public function __construct(
        private EntityManagerInterface $em,
        private GoogleBooksService $googleBooks,
        private TmdbService $tmdb,
    ) {}

    /**
     * Ajoute un média (API) dans la collection “Non répertorié” de l’utilisateur.
     *
     * @return bool true si déjà présent, false si nouvel ajout
     */
    public function addToDefaultCollection(User $user, string $kind, string $externalId): bool
    {
        $collection = $this->getOrCreateDefaultCollection($user);

        if ($kind === 'book') {
            $book = $this->getOrCreateBookFromApi($externalId);
            return $this->attachBook($collection, $book);
        }

        if ($kind === 'movie') {
            $movie = $this->getOrCreateMovieFromApi((int) $externalId);
            return $this->attachMovie($collection, $movie);
        }

        throw new \InvalidArgumentException('Unknown kind.');
    }

    private function getOrCreateDefaultCollection(User $user): Collection
    {
        // ⚠️ adapte les champs selon ton entité Collection
        // Hypothèse la plus probable : Collection a (name, user/owner)
        $repo = $this->em->getRepository(Collection::class);

        $collection = $repo->findOneBy([
            // change 'user' -> 'owner' si besoin
            'user' => $user,
            'name' => 'Non répertorié',
        ]);

        if ($collection instanceof Collection) {
            return $collection;
        }

        $collection = new Collection();
        // change setUser -> setOwner si besoin
        $collection->setUser($user);
        $collection->setName('Non répertorié');

        // optionnel si tu as un bool "isSystem"/"isDefault"
        if (method_exists($collection, 'setIsDefault')) {
            $collection->setIsDefault(true);
        }

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
        $book->setGoogleBooksId($data['id']);
        $book->setTitle($data['title'] ?? 'Sans titre');

        // Adapte selon tes champs réels en entité Book
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

        // Adapte selon tes champs réels en entité Movie
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

    private function attachBook(Collection $collection, Book $book): bool
    {
        // Retourne true si déjà présent
        $link = new CollectionBook();
        $link->setCollection($collection);
        $link->setBook($book);

        try {
            $this->em->persist($link);
            $this->em->flush();
            return false;
        } catch (UniqueConstraintViolationException) {
            $this->em->clear(); // évite un état EM bancal
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
            $this->em->clear();
            return true;
        }
    }
}
