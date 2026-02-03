<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    /**
     * Je rends la page d’accueil avec un flux de nouveautés provenant des APIs.
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(GoogleBooksService $googleBooks, TmdbService $tmdb): Response
    {
        $books = [];
        $movies = [];

        // Livres
        try {
            // Requête plus stable qu’un simple "bestseller"
            $books = $googleBooks->newest('fiction', 12);

            // Fallback si l'API renvoie 200 mais sans items exploitables
            if (empty($books)) {
                $books = $googleBooks->search('subject:fiction', 12);
            }
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de charger des livres pour le moment.');
            $books = [];
        }

        // Films
        try {
            $movies = $tmdb->nowPlaying(12);
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
