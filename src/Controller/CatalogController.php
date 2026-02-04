<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere l'affichage du catalogue de livres et films.
 */
final class CatalogController extends AbstractController
{
    private const ITEMS_PER_PAGE = 12;

    #[Route('/catalog', name: 'app_catalog', methods: ['GET'])]
    public function index(
        Request $request,
        GoogleBooksService $googleBooks,
        TmdbService $tmdb
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', 'all');
        $page = max(1, (int) $request->query->get('page', 1));

        if (!in_array($type, ['all', 'book', 'movie'], true)) {
            $type = 'all';
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
                $books = $this->loadBooksNewest($googleBooks, self::ITEMS_PER_PAGE, 1);
                $movies = $this->loadMoviesNowPlaying($tmdb, self::ITEMS_PER_PAGE, 1);
            } elseif ($type === 'book') {
                $result = $this->loadBooksNewestWithTotal($googleBooks, self::ITEMS_PER_PAGE, $page);
                $books = $result['items'];
                $totalBooks = $result['total'];
                $totalPages = max(1, (int) ceil($totalBooks / self::ITEMS_PER_PAGE));
            } else {
                $result = $this->loadMoviesNowPlayingWithTotal($tmdb, self::ITEMS_PER_PAGE, $page);
                $movies = $result['items'];
                $totalMovies = $result['total'];
                $totalPages = min($result['totalPages'], 500);
            }

            return $this->render('catalog/index.html.twig', [
                'q' => '',
                'type' => $type,
                'books' => $books,
                'movies' => $movies,
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
            $books = $this->searchBooks($googleBooks, $q, self::ITEMS_PER_PAGE, 1);
            $movies = $this->searchMovies($tmdb, $q, self::ITEMS_PER_PAGE, 1);
        } elseif ($type === 'book') {
            $result = $this->searchBooksWithTotal($googleBooks, $q, self::ITEMS_PER_PAGE, $page);
            $books = $result['items'];
            $totalBooks = $result['total'];
            $totalPages = max(1, (int) ceil($totalBooks / self::ITEMS_PER_PAGE));
        } else {
            $result = $this->searchMoviesWithTotal($tmdb, $q, self::ITEMS_PER_PAGE, $page);
            $movies = $result['items'];
            $totalMovies = $result['total'];
            $totalPages = min($result['totalPages'], 500);
        }

        return $this->render('catalog/index.html.twig', [
            'q' => $q,
            'type' => $type,
            'books' => $books,
            'movies' => $movies,
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
    private function loadBooksNewest(GoogleBooksService $googleBooks, int $limit, int $page): array
    {
        try {
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
    private function loadBooksNewestWithTotal(GoogleBooksService $googleBooks, int $limit, int $page): array
    {
        try {
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
    private function loadMoviesNowPlaying(TmdbService $tmdb, int $limit, int $page): array
    {
        try {
            $result = $tmdb->nowPlaying($limit, $page);
            return $result['items'];
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des films pour le moment.');
            return [];
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    private function loadMoviesNowPlayingWithTotal(TmdbService $tmdb, int $limit, int $page): array
    {
        try {
            return $tmdb->nowPlaying($limit, $page);
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des films pour le moment.');
            return ['items' => [], 'total' => 0, 'totalPages' => 0];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchBooks(GoogleBooksService $googleBooks, string $query, int $limit, int $page): array
    {
        try {
            $result = $googleBooks->search($query, $limit, $page);
            return $result['items'];
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de contacter Google Books pour le moment.');
            return [];
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    private function searchBooksWithTotal(GoogleBooksService $googleBooks, string $query, int $limit, int $page): array
    {
        try {
            return $googleBooks->search($query, $limit, $page);
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de contacter Google Books pour le moment.');
            return ['items' => [], 'total' => 0];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function searchMovies(TmdbService $tmdb, string $query, int $limit, int $page): array
    {
        try {
            $result = $tmdb->search($query, $limit, $page);
            return $result['items'];
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de contacter TMDB pour le moment.');
            return [];
        }
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int, totalPages: int}
     */
    private function searchMoviesWithTotal(TmdbService $tmdb, string $query, int $limit, int $page): array
    {
        try {
            return $tmdb->search($query, $limit, $page);
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de contacter TMDB pour le moment.');
            return ['items' => [], 'total' => 0, 'totalPages' => 0];
        }
    }
}