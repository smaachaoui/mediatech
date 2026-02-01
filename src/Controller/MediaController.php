<?php

namespace App\Controller;

use App\Service\GoogleBooksService;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class MediaController extends AbstractController
{
    #[Route('/books/{id}', name: 'app_book_show', methods: ['GET'])]
    public function bookShow(
        string $id,
        GoogleBooksService $googleBooks,
    ): Response {
        try {
            $book = $googleBooks->getById($id);
        } catch (\Throwable) {
            throw $this->createNotFoundException('Livre introuvable (Google Books).');
        }

        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/movies/{id}', name: 'app_movie_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function movieShow(
        int $id,
        TmdbService $tmdb,
    ): Response {
        try {
            $movie = $tmdb->getById($id);
        } catch (\Throwable) {
            throw $this->createNotFoundException('Film introuvable (TMDB).');
        }

        return $this->render('movie/show.html.twig', [
            'movie' => $movie,
        ]);
    }
}
