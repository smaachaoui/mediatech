<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class MediaController extends AbstractController
{
    #[Route('/books/{id}', name: 'app_book_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function bookShow(int $id): Response
    {
        return $this->render('book/show.html.twig', [
            'id' => $id,
        ]);
    }

    #[Route('/movies/{id}', name: 'app_movie_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function movieShow(int $id): Response
    {
        return $this->render('movie/show.html.twig', [
            'id' => $id,
        ]);
    }
}
