<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CatalogController extends AbstractController
{
    #[Route('/catalog/books', name: 'app_catalog_books', methods: ['GET'])]
    public function books(): Response
    {
        return $this->render('catalog/books.html.twig');
    }

    #[Route('/catalog/movies', name: 'app_catalog_movies', methods: ['GET'])]
    public function movies(): Response
    {
        return $this->render('catalog/movies.html.twig');
    }
}
