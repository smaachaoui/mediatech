<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProfileController extends AbstractController
{
    private const ALLOWED_SECTIONS = ['info', 'collections', 'comments', 'friends', 'support'];

    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function index(Request $request, ProfileService $profileService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $section = (string) $request->query->get('section', 'info');
        if (!in_array($section, self::ALLOWED_SECTIONS, true)) {
            $section = 'info';
        }

        $data = $profileService->getProfileData($user, $section);

        return $this->render('profile/index.html.twig', array_merge($data, [
            'section' => $section,
            'user' => $user,
        ]));
    }

    #[Route('/profile/collections/move', name: 'app_profile_move_item', methods: ['POST'])]
    public function moveItem(Request $request, ProfileService $profileService): RedirectResponse
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

        $profileService->moveUnlistedItemToCollection($user, $type, $linkId, $collectionId);

        $this->addFlash('success', 'Titre déplacé avec succès.');
        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    #[Route('/profile/collections/create', name: 'app_profile_create_collection', methods: ['POST'])]
    public function createCollection(Request $request, ProfileService $profileService): RedirectResponse
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

        $profileService->createUserCollection(
            $user,
            $name,
            is_string($genre) ? $genre : null,
            is_string($coverImage) ? $coverImage : null,
            is_string($description) ? $description : null,
            $mediaType
        );

        $this->addFlash('success', 'Collection créée.');
        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    #[Route('/profile/collections/{id}/edit', name: 'app_profile_edit_collection', methods: ['POST'])]
    public function editCollection(int $id, Request $request, ProfileService $profileService): RedirectResponse
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
        $coverImage = $request->request->get('coverImage');

        $profileService->updateUserCollection(
            $user,
            $id,
            $name,
            is_string($genre) ? $genre : null,
            is_string($coverImage) ? $coverImage : null,
            is_string($description) ? $description : null
        );

        $this->addFlash('success', 'Collection mise à jour.');
        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    #[Route('/profile/collections/{id}/delete', name: 'app_profile_delete_collection', methods: ['POST'])]
    public function deleteCollection(int $id, Request $request, ProfileService $profileService): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('delete_collection_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $profileService->deleteUserCollection($user, $id);

        $this->addFlash('success', 'Collection supprimée.');
        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    #[Route('/profile/collections/item/remove', name: 'app_profile_remove_item', methods: ['POST'])]
    public function removeItem(Request $request, ProfileService $profileService): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('remove_item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');

        $profileService->removeItemFromCollection($user, $type, $linkId);

        $this->addFlash('success', 'Élément retiré de la collection.');
        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    #[Route('/profile/collections/{id}/toggle-publish', name: 'app_profile_toggle_publish_collection', methods: ['POST'])]
    public function togglePublishCollection(int $id, Request $request, ProfileService $profileService): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('toggle_publish_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $isNowPublished = $profileService->togglePublishUserCollection($user, $id);

        $this->addFlash(
            'success',
            $isNowPublished
                ? 'Collection publiée : elle apparaît dans “Nos collections”.'
                : 'Collection dépubliée : elle n’apparaît plus dans “Nos collections”.'
        );

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    #[Route('/profile/wishlist/item/remove', name: 'app_profile_wishlist_remove_item', methods: ['POST'])]
    public function removeWishlistItem(Request $request, ProfileService $profileService): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('wishlist_remove_item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $type = (string) $request->request->get('type'); // book|movie
        $linkId = (int) $request->request->get('linkId');

        $profileService->removeItemFromWishlist($user, $type, $linkId);

        $this->addFlash('success', 'Retiré de votre liste d’envie.');
        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    #[Route('/profile/wishlist/move', name: 'app_profile_wishlist_move_item', methods: ['POST'])]
    public function moveWishlistItem(Request $request, ProfileService $profileService): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('wishlist_move_item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $type = (string) $request->request->get('type'); // book|movie
        $linkId = (int) $request->request->get('linkId');
        $collectionId = (int) $request->request->get('collectionId');

        $profileService->moveWishlistItemToCollection($user, $type, $linkId, $collectionId);

        $this->addFlash('success', 'Déplacé avec succès.');
        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

}
