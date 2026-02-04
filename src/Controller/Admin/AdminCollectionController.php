<?php

namespace App\Controller\Admin;

use App\Entity\Collection;
use App\Repository\CollectionRepository;
use App\Security\Voter\AdminVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/collections')]
final class AdminCollectionController extends AbstractController
{
    #[Route('', name: 'admin_collections_index', methods: ['GET'])]
    public function index(Request $request, CollectionRepository $collectionRepository): Response
    {
        $this->denyAccessUnlessGranted(AdminVoter::COLLECTIONS_MANAGE);

        $filter = (string) $request->query->get('published', 'all');
        $published = null;

        if ($filter === 'published') {
            $published = true;
        } elseif ($filter === 'unpublished') {
            $published = false;
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $result = $collectionRepository->findUserCollectionsPaginated($published, $page, $limit);

        $total = (int) $result['total'];
        $totalPages = max(1, (int) ceil($total / $limit));
        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $collectionRepository->findUserCollectionsPaginated($published, $page, $limit);
        }

        return $this->render('admin/index.html.twig', [
            'section' => 'collections',
            'collections' => $result['items'],
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filter' => $filter,
        ]);
    }

    #[Route('/{id}/toggle-publish', name: 'admin_collections_toggle_publish', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function togglePublish(
        int $id,
        Request $request,
        CollectionRepository $collectionRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted(AdminVoter::COLLECTIONS_MANAGE);

        $collection = $collectionRepository->find($id);
        if (!$collection) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if ($collection->getScope() !== Collection::SCOPE_USER) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if (
            !$this->isCsrfTokenValid(
                'admin_toggle_publish_collection_'.$collection->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_collections_index');
        }

        if ($collection->isPublished()) {
            $collection->setIsPublished(false);
            $collection->setVisibility('private');
            $collection->setPublishedAt(null);
            $this->addFlash('success', 'Collection dépubliée.');
        } else {
            $itemsCount = $collection->getCollectionBooks()->count() + $collection->getCollectionMovies()->count();
            if ($itemsCount === 0) {
                $this->addFlash('danger', 'Impossible de publier une collection vide.');
                return $this->redirectToRoute('admin_collections_index');
            }

            $collection->setIsPublished(true);
            $collection->setVisibility('public');
            $collection->setPublishedAt(new \DateTimeImmutable());
            $this->addFlash('success', 'Collection publiée.');
        }

        $em->flush();

        return $this->redirectToRoute('admin_collections_index');
    }
}
