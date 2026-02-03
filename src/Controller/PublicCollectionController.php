<?php

namespace App\Controller;

use App\Entity\Collection;
use App\Repository\CollectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PublicCollectionController extends AbstractController
{
    #[Route('/collections', name: 'app_collections_public', methods: ['GET'])]
    public function index(Request $request, CollectionRepository $collectionRepository): Response
    {
        $mediaType = (string) $request->query->get('type', Collection::MEDIA_ALL);
        if (!in_array($mediaType, Collection::MEDIA_TYPES, true)) {
            $mediaType = Collection::MEDIA_ALL;
        }

        $sort = (string) $request->query->get('sort', 'new');
        $allowedSorts = ['new', 'old', 'alpha'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'new';
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 9;

        $result = $collectionRepository->findPublicPublishedPaginated($mediaType, $sort, $page, $limit);

        $total = (int) $result['total'];
        $totalPages = max(1, (int) ceil($total / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $collectionRepository->findPublicPublishedPaginated($mediaType, $sort, $page, $limit);
        }

        return $this->render('collection/index.html.twig', [
            'collections' => $result['items'],
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => [
                'type' => $mediaType,
                'sort' => $sort,
            ],
        ]);
    }

    #[Route('/collections/{id}', name: 'app_collection_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, CollectionRepository $collectionRepository): Response
    {
        $collection = $collectionRepository->find($id);

        if (!$collection) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if (!$collection->isPublished() || $collection->getVisibility() !== 'public' || $collection->getScope() !== Collection::SCOPE_USER) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        return $this->render('collection/show.html.twig', [
            'collection' => $collection,
        ]);
    }
}
