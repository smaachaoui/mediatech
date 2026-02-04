<?php

namespace App\Controller\Profile;

use App\Entity\User;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere les collections de l'utilisateur connecte.
 */
#[Route('/profile/collections')]
final class ProfileCollectionController extends AbstractController
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {
    }

    /**
     * Je deplace un element non repertorie vers une collection.
     */
    #[Route('/move', name: 'app_profile_move_item', methods: ['POST'])]
    public function moveItem(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('move_item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');
        $collectionId = (int) $request->request->get('collectionId');

        $this->profileService->moveUnlistedItemToCollection($user, $type, $linkId, $collectionId);

        $this->addFlash('success', 'Titre déplacé avec succès.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    /**
     * Je cree une nouvelle collection.
     */
    #[Route('/create', name: 'app_profile_create_collection', methods: ['POST'])]
    public function create(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('create_collection', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $name = (string) $request->request->get('name', '');
        $description = $request->request->get('description');
        $mediaType = (string) $request->request->get('mediaType', '');
        $genre = $request->request->get('genre');
        $coverImage = $request->request->get('coverImage');

        $coverImage = is_string($coverImage) ? trim($coverImage) : null;
        if ($coverImage === '') {
            $coverImage = null;
        }

        $this->profileService->createUserCollection(
            $user,
            $name,
            is_string($genre) ? $genre : null,
            $coverImage,
            is_string($description) ? $description : null,
            $mediaType
        );

        $this->addFlash('success', 'Collection créée.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    /**
     * Je modifie une collection existante.
     */
    #[Route('/{id}/edit', name: 'app_profile_edit_collection', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function edit(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('edit_collection_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $name = (string) $request->request->get('name', '');
        $description = $request->request->get('description');
        $genre = $request->request->get('genre');

        /*
         * Je récupère la valeur postée pour la couverture.
         * "__keep__" => je ne change rien
         * "__none__" => je supprime la couverture
         * URL => je tente d'appliquer uniquement si elle appartient à un média de la collection (contrôle côté service).
         */
        $coverImage = $request->request->get('coverImage');
        $coverImage = is_string($coverImage) ? trim($coverImage) : null;

        if ($coverImage === '__keep__' || $coverImage === '') {
            $coverImage = null;
        }

        $this->profileService->updateUserCollection(
            $user,
            $id,
            $name,
            is_string($genre) ? $genre : null,
            $coverImage,
            is_string($description) ? $description : null
        );

        $this->addFlash('success', 'Collection mise à jour.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    /**
     * Je supprime une collection.
     */
    #[Route('/{id}/delete', name: 'app_profile_delete_collection', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('delete_collection_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $this->profileService->deleteUserCollection($user, $id);

        $this->addFlash('success', 'Collection supprimée.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    /**
     * Je retire un element d'une collection.
     */
    #[Route('/item/remove', name: 'app_profile_remove_item', methods: ['POST'])]
    public function removeItem(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('remove_item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');

        $this->profileService->removeItemFromCollection($user, $type, $linkId);

        $this->addFlash('success', 'Élément retiré de la collection.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    /**
     * Je publie ou depublie une collection.
     */
    #[Route('/{id}/toggle-publish', name: 'app_profile_toggle_publish_collection', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function togglePublish(int $id, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('toggle_publish_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $isNowPublished = $this->profileService->togglePublishUserCollection($user, $id);

        $this->addFlash(
            'success',
            $isNowPublished
                ? 'Collection publiée : elle apparaît dans "Nos collections".'
                : 'Collection dépubliée : elle n\'apparaît plus dans "Nos collections".'
        );

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }
}
