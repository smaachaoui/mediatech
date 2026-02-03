<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CatalogController extends AbstractController
{
    #[Route('/catalog', name: 'app_catalog', methods: ['GET'])]
    public function index(
        Request $request,
        GoogleBooksService $googleBooks,
        TmdbService $tmdb
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', 'all'); // all|book|movie

        if (!in_array($type, ['all', 'book', 'movie'], true)) {
            $type = 'all';
        }

        $books = [];
        $movies = [];

        // ✅ Mode "catalogue vivant" sans recherche
        if ($q === '') {
            if ($type === 'all' || $type === 'book') {
                try {
                    $books = $googleBooks->newest('fiction', 12);
                    if (empty($books)) {
                        $books = $googleBooks->search('subject:fiction', 12);
                    }
                } catch (\Throwable) {
                    $this->addFlash('danger', 'Impossible de charger des livres pour le moment.');
                    $books = [];
                }
            }

            if ($type === 'all' || $type === 'movie') {
                try {
                    $movies = $tmdb->nowPlaying(12);
                } catch (\Throwable) {
                    $this->addFlash('danger', 'Impossible de charger des films pour le moment.');
                    $movies = [];
                }
            }

            return $this->render('catalog/index.html.twig', [
                'q' => '',
                'type' => $type,
                'books' => $books,
                'movies' => $movies,
                'hasResults' => !empty($books) || !empty($movies),
            ]);
        }

        // ✅ Mode "recherche"
        if ($type === 'all' || $type === 'book') {
            try {
                $books = $googleBooks->search($q, 12);
            } catch (\Throwable) {
                $this->addFlash('danger', 'Impossible de contacter Google Books pour le moment.');
                $books = [];
            }
        }

        if ($type === 'all' || $type === 'movie') {
            try {
                $movies = $tmdb->search($q, 12);
            } catch (\Throwable) {
                $this->addFlash('danger', 'Impossible de contacter TMDB pour le moment.');
                $movies = [];
            }
        }

        return $this->render('catalog/index.html.twig', [
            'q' => $q,
            'type' => $type,
            'books' => $books,
            'movies' => $movies,
            'hasResults' => !empty($books) || !empty($movies),
        ]);
    }
}
