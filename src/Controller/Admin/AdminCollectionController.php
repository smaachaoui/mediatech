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
use App\Form\AdminCollectionEditType;
use Symfony\Component\HttpFoundation\RedirectResponse;


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

    #[Route('/{id}/edit', name: 'admin_collections_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        int $id,
        Request $request,
        CollectionRepository $collectionRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted(AdminVoter::COLLECTIONS_MANAGE);

        $collection = $collectionRepository->find($id);
        if (!$collection || $collection->getScope() !== Collection::SCOPE_USER) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        $form = $this->createForm(AdminCollectionEditType::class, $collection);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Collection mise à jour.');
            return $this->redirectToRoute('admin_collections_index');
        }

        return $this->render('admin/collections/edit.html.twig', [
            'collection' => $collection,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Je mets à jour une collection via la modal (admin).
     */
    #[Route('/{id}/update', name: 'admin_collections_update', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function update(
        int $id,
        Request $request,
        CollectionRepository $collectionRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $collection = $collectionRepository->find($id);
        if (!$collection) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('admin_edit_collection_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $name = (string) $request->request->get('name', '');
        $genre = $request->request->get('genre');
        $description = $request->request->get('description');

        if (trim($name) === '') {
            $this->addFlash('danger', 'Le nom de la collection est obligatoire.');
            return $this->redirectToRoute('admin_collections_index');
        }

        $collection->setName($name);
        $collection->setGenre(is_string($genre) && trim($genre) !== '' ? $genre : null);
        $collection->setDescription(is_string($description) && trim($description) !== '' ? $description : null);

        $em->flush();

        $this->addFlash('success', 'Collection modifiée avec succès.');

        return $this->redirectToRoute('admin_collections_index');
    }

    #[Route('/{id}/delete', name: 'admin_collections_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        CollectionRepository $collectionRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(AdminVoter::COLLECTIONS_MANAGE);

        $collection = $collectionRepository->find($id);
        if (!$collection || $collection->getScope() !== Collection::SCOPE_USER) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if (
            !$this->isCsrfTokenValid(
                'admin_delete_collection_'.$collection->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_collections_index');
        }

        $em->remove($collection);
        $em->flush();

        $this->addFlash('success', 'Collection supprimée.');
        return $this->redirectToRoute('admin_collections_index');
    }


}
