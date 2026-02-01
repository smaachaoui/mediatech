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
    #[Route('/catalog', name: 'app_catalog')]
    public function index(
        Request $request,
        GoogleBooksService $googleBooks,
        TmdbService $tmdb,
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $type = (string) $request->query->get('type', 'all'); // all|books|movies

        if (!in_array($type, ['all', 'books', 'movies'], true)) {
            $type = 'all';
        }

        $results = [
            'books' => [],
            'movies' => [],
        ];

        if ($q !== '') {
            if ($type === 'all' || $type === 'books') {
                try {
                    $results['books'] = $googleBooks->search($q);
                } catch (\Throwable) {
                    $this->addFlash('danger', 'Impossible de contacter Google Books pour le moment.');
                }
            }

            if ($type === 'all' || $type === 'movies') {
                try {
                    $results['movies'] = $tmdb->search($q);
                } catch (\Throwable) {
                    $this->addFlash('danger', 'Impossible de contacter TMDB pour le moment.');
                }
            }
        }

        return $this->render('catalog/index.html.twig', [
            'q' => $q,
            'type' => $type,
            'results' => $results,
        ]);
    }
}
