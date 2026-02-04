<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere l'affichage de la page d'accueil.
 */
final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(GoogleBooksService $googleBooks, TmdbService $tmdb): Response
    {
        $books = [];
        $movies = [];

        try {
            $result = $googleBooks->newest('fiction', 12);
            $books = $result['items'] ?? [];

            if (empty($books)) {
                $result = $googleBooks->search('subject:fiction', 12);
                $books = $result['items'] ?? [];
            }
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des livres pour le moment.');
            $books = [];
        }

        try {
            $result = $tmdb->nowPlaying(12);
            $movies = $result['items'] ?? [];
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des films pour le moment.');
            $movies = [];
        }

        return $this->render('home/index.html.twig', [
            'books' => $books,
            'movies' => $movies,
        ]);
    }
}