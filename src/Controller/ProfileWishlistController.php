<?php

namespace App\Controller\Profile;

use App\Entity\User;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere la liste d'envies de l'utilisateur connecte.
 */
#[Route('/profile/wishlist')]
final class ProfileWishlistController extends AbstractController
{
    public function __construct(
        private readonly ProfileService $profileService
    ) {
    }

    /**
     * Je retire un element de la wishlist.
     */
    #[Route('/item/remove', name: 'app_profile_wishlist_remove_item', methods: ['POST'])]
    public function removeItem(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('wishlist_remove_item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');

        $this->profileService->removeItemFromWishlist($user, $type, $linkId);

        $this->addFlash('success', 'Retiré de votre liste d\'envie.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    /**
     * Je deplace un element de la wishlist vers une collection.
     */
    #[Route('/move', name: 'app_profile_wishlist_move_item', methods: ['POST'])]
    public function moveItem(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('wishlist_move_item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');
        $collectionId = (int) $request->request->get('collectionId');

        $this->profileService->moveWishlistItemToCollection($user, $type, $linkId, $collectionId);

        $this->addFlash('success', 'Déplacé avec succès.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }
}