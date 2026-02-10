<?php

namespace App\Controller;

use App\Repository\GenreRepository;
use App\Service\GoogleBooksService;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere l'affichage du catalogue de livres et films avec filtrage par genre.
 */
final class CatalogController extends AbstractController
{
    private const ITEMS_PER_PAGE = 12;

    #[Route('/catalog', name: 'app_catalog', methods: ['GET'])]
    public function index(
        Request $request,
        GoogleBooksService $googleBooks,
        TmdbService $tmdb,
        GenreRepository $genreRepository
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', 'all');
        $genre = trim((string) $request->query->get('genre', ''));
        $page = max(1, (int) $request->query->get('page', 1));

        if (!in_array($type, ['all', 'book', 'movie'], true)) {
            $type = 'all';
        }

        /*
         * Je recupere les genres disponibles selon le type de media.
         * Si type=all, je recupere livres et films.
         * Sinon, uniquement le type selectionne.
         */
        $bookGenres = [];
        $movieGenres = [];

        if ($type === 'all' || $type === 'book') {
            $bookGenres = $genreRepository->findBookGenres();
        }

        if ($type === 'all' || $type === 'movie') {
            $movieGenres = $genreRepository->findMovieGenres();
        }

        $books = [];
        $movies = [];
        $totalBooks = 0;
        $totalMovies = 0;
        $totalPages = 1;

        /*
         * Mode "catalogue vivant" sans recherche.
         * Quand type=all, j'affiche 12 de chaque sans pagination.
         * Quand type=book ou type=movie, j'utilise la pagination.
         */
        if ($q === '') {
            if ($type === 'all') {
                $books = $this->loadBooksNewest($googleBooks, self::ITEMS_PER_PAGE, 1, $genre);
                $movies = $this->loadMoviesNowPlaying($tmdb, self::ITEMS_PER_PAGE, 1, $genre);
            } elseif ($type === 'book') {
                $result = $this->loadBooksNewestWithTotal($googleBooks, self::ITEMS_PER_PAGE, $page, $genre);
                $books = $result['items'];
                $totalBooks = $result['total'];
                $totalPages = max(1, (int) ceil($totalBooks / self::ITEMS_PER_PAGE));
            } else {
                $result = $this->loadMoviesNowPlayingWithTotal($tmdb, self::ITEMS_PER_PAGE, $page, $genre);
                $movies = $result['items'];
                $totalMovies = $result['total'];
                $totalPages = min($result['totalPages'], 500);
            }

            return $this->render('catalog/index.html.twig', [
                'q' => '',
                'type' => $type,
                'genre' => $genre,
                'books' => $books,
                'movies' => $movies,
                'bookGenres' => $bookGenres,
                'movieGenres' => $movieGenres,
                'hasResults' => !empty($books) || !empty($movies),
                'page' => $page,
                'totalPages' => $totalPages,
                'totalBooks' => $totalBooks,
                'totalMovies' => $totalMovies,
            ]);
        }

        /*
         * Mode "recherche".
         * Meme logique : type=all sans pagination, sinon avec pagination.
         */
        if ($type === 'all') {
            $books = $this->searchBooks($googleBooks, $q, self::ITEMS_PER_PAGE, 1, $genre);
            $movies = $this->searchMovies($tmdb, $q, self::ITEMS_PER_PAGE, 1, $genre);
        } elseif ($type === 'book') {
            $result = $this->searchBooksWithTotal($googleBooks, $q, self::ITEMS_PER_PAGE, $page, $genre);
            $books = $result['items'];
            $totalBooks = $result['total'];
            $totalPages = max(1, (int) ceil($totalBooks / self::ITEMS_PER_PAGE));
        } else {
            $result = $this->searchMoviesWithTotal($tmdb, $q, self::ITEMS_PER_PAGE, $page, $genre);
            $movies = $result['items'];
            $totalMovies = $result['total'];
            $totalPages = min($result['totalPages'], 500);
        }

        return $this->render('catalog/index.html.twig', [
            'q' => $q,
            'type' => $type,
            'genre' => $genre,
            'books' => $books,
            'movies' => $movies,
            'bookGenres' => $bookGenres,
            'movieGenres' => $movieGenres,
            'hasResults' => !empty($books) || !empty($movies),
            'page' => $page,
            'totalPages' => $totalPages,
            'totalBooks' => $totalBooks,
            'totalMovies' => $totalMovies,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadBooksNewest(GoogleBooksService $googleBooks, int $limit, int $page, string $genre = ''): array
    {
        try {
            /*
             * Si un genre est selectionne, je fais une recherche par sujet.
             * Sinon, j'utilise la methode newest classique.
             */
            if ($genre !== '') {
                $result = $googleBooks->search(sprintf('subject:%s', $genre), $limit, $page);
                return $result['items'];
            }

            $result = $googleBooks->newest('fiction', $limit, $page);
            if (empty($result['items'])) {
                $result = $googleBooks->search('subject:fiction', $limit, $page);
            }
            return $result['items'];
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des livres pour le moment.');
            return [];
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    private function loadBooksNewestWithTotal(GoogleBooksService $googleBooks, int $limit, int $page, string $genre = ''): array
    {
        try {
            if ($genre !== '') {
                return $googleBooks->search(sprintf('subject:%s', $genre), $limit, $page);
            }

            $result = $googleBooks->newest('fiction', $limit, $page);
            if (empty($result['items'])) {
                $result = $googleBooks->search('subject:fiction', $limit, $page);
            }
            return $result;
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des livres pour le moment.');
            return ['items' => [], 'total' => 0];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadMoviesNowPlaying(TmdbService $tmdb, int $limit, int $page, string $genre = ''): array
    {
        try {
            /*
             * Si un genre est specifie, j'utilise /discover/movie pour filtrer cote API.
             * Sinon, j'utilise nowPlaying classique.
             */
            if ($genre !== '') {
                $result = $tmdb->discoverByGenre($genre, $limit, $page);
            } else {
                $result = $tmdb->nowPlaying($limit, $page);
            }
            
            return $result['items'];
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des films pour le moment.');
            return [];
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    private function loadMoviesNowPlayingWithTotal(TmdbService $tmdb, int $limit, int $page, string $genre = ''): array
    {
        try {
            /*
             * Si un genre est specifie, j'utilise /discover/movie pour filtrer cote API.
             * Cela permet d'obtenir beaucoup plus de resultats qu'un filtrage cote client.
             */
            if ($genre !== '') {
                return $tmdb->discoverByGenre($genre, $limit, $page);
            }
            
            return $tmdb->nowPlaying($limit, $page);
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des films pour le moment.');
            return ['items' => [], 'total' => 0, 'totalPages' => 0];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchBooks(GoogleBooksService $googleBooks, string $query, int $limit, int $page, string $genre = ''): array
    {
        try {
            /*
             * Si un genre est selectionne, je l'ajoute a la requete de recherche.
             */
            $searchQuery = $genre !== '' ? sprintf('%s subject:%s', $query, $genre) : $query;
            
            $result = $googleBooks->search($searchQuery, $limit, $page);
            return $result['items'];
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de contacter Google Books pour le moment.');
            return [];
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    private function searchBooksWithTotal(GoogleBooksService $googleBooks, string $query, int $limit, int $page, string $genre = ''): array
    {
        try {
            $searchQuery = $genre !== '' ? sprintf('%s subject:%s', $query, $genre) : $query;
            return $googleBooks->search($searchQuery, $limit, $page);
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de contacter Google Books pour le moment.');
            return ['items' => [], 'total' => 0];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchMovies(TmdbService $tmdb, string $query, int $limit, int $page, string $genre = ''): array
    {
        try {
            $result = $tmdb->search($query, $limit, $page);
            
            if ($genre !== '') {
                $result['items'] = $this->filterMoviesByGenre($result['items'], $genre);
            }
            
            return $result['items'];
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de contacter TMDB pour le moment.');
            return [];
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    private function searchMoviesWithTotal(TmdbService $tmdb, string $query, int $limit, int $page, string $genre = ''): array
    {
        try {
            $result = $tmdb->search($query, $limit, $page);
            
            if ($genre !== '') {
                $result['items'] = $this->filterMoviesByGenre($result['items'], $genre);
                $result['total'] = count($result['items']);
                $result['totalPages'] = max(1, (int) ceil($result['total'] / $limit));
            }
            
            return $result;
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de contacter TMDB pour le moment.');
            return ['items' => [], 'total' => 0, 'totalPages' => 0];
        }
    }

    /**
     * Je filtre les films par genre cote client.
     * TMDB retourne un tableau de genres pour chaque film.
     *
     * @param array<int, array<string, mixed>> $movies
     * @return array<int, array<string, mixed>>
     */
    private function filterMoviesByGenre(array $movies, string $genre): array
    {
        if ($genre === '') {
            return $movies;
        }

        $filtered = [];
        
        foreach ($movies as $movie) {
            $movieGenres = $movie['genres'] ?? [];
            
            if (!is_array($movieGenres)) {
                continue;
            }
            
            /*
             * Je verifie si le genre recherche est present dans les genres du film.
             * La comparaison est insensible a la casse.
             */
            foreach ($movieGenres as $movieGenre) {
                if (is_string($movieGenre) && strcasecmp($movieGenre, $genre) === 0) {
                    $filtered[] = $movie;
                    break;
                }
            }
        }
        
        return $filtered;
    }
}