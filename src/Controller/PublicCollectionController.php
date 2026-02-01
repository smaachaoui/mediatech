<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicCollectionController extends AbstractController
{
    #[Route('/collections', name: 'app_collections_public', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('collection/index.html.twig');
    }

    #[Route('/collections/{id}', name: 'app_collection_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id): Response
    {
        return $this->render('collection/show.html.twig', [
            'id' => $id,
        ]);
    }
}
