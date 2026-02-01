<?php

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class CatalogController extends AbstractController
{
    #[Route('/catalog', name: 'app_catalog', methods: ['GET'])]
    public function index(
        Request $request,
        BookRepository $bookRepository,
        MovieRepository $movieRepository
    ): Response {
        $type = $request->query->get('type', 'all'); // all|book|movie
        $q = trim((string) $request->query->get('q', ''));
        $sort = $request->query->get('sort', 'recent'); // recent|title
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = 12;

        // MVP: on fait 2 listes distinctes (simple et robuste)
        // Ensuite on pourra unifier et paginer globalement si tu veux.
        $latestBooks = [];
        $latestMovies = [];

        // TODO V2: filtrage par $q/$sort côté QueryBuilder (prochaine étape)
        if ($type === 'all' || $type === 'book') {
            $latestBooks = $bookRepository->findBy(
                [],
                $sort === 'title' ? ['title' => 'ASC'] : ['createdAt' => 'DESC'],
                $perPage,
                ($page - 1) * $perPage
            );
        }

        if ($type === 'all' || $type === 'movie') {
            $latestMovies = $movieRepository->findBy(
                [],
                $sort === 'title' ? ['title' => 'ASC'] : ['createdAt' => 'DESC'],
                $perPage,
                ($page - 1) * $perPage
            );
        }

        return $this->render('catalog/index.html.twig', [
            'type' => $type,
            'q' => $q,
            'sort' => $sort,
            'page' => $page,
            'perPage' => $perPage,
            'books' => $latestBooks,
            'movies' => $latestMovies,
        ]);
    }
}
