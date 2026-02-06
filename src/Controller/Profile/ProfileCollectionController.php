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

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');
        $collectionId = (int) $request->request->get('collectionId');

        if ($linkId <= 0 || $collectionId <= 0) {
            $this->addFlash('danger', 'Requête invalide.');
            return $this->redirectToRoute('app_profile', ['section' => 'collections']);
        }

        if (!$this->isCsrfTokenValid('move_item_' . $linkId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $this->profileService->moveUnlistedItemToCollection($user, $type, $linkId, $collectionId);

        $this->addFlash('success', 'Titre déplacé avec succès.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    /**
     * Je deplace plusieurs elements non repertories vers une collection.
     */
    #[Route('/move-multiple', name: 'app_profile_move_multiple_items', methods: ['POST'])]
    public function moveMultipleItems(Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $collectionId = (int) $request->request->get('collectionId');
        $items = $request->request->all('items'); // Récupère un tableau d'items

        if ($collectionId <= 0) {
            $this->addFlash('danger', 'Veuillez sélectionner une collection de destination.');
            return $this->redirectToRoute('app_profile', ['section' => 'collections']);
        }

        if (empty($items) || !is_array($items)) {
            $this->addFlash('warning', 'Aucun élément sélectionné.');
            return $this->redirectToRoute('app_profile', ['section' => 'collections']);
        }

        if (!$this->isCsrfTokenValid('move_multiple_items', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $movedCount = 0;
        $errors = 0;

        // Parcourir chaque item sélectionné et le déplacer
        foreach ($items as $item) {
            // Format attendu: "type:linkId" (ex: "movie:123" ou "book:456")
            $parts = explode(':', $item);
            if (count($parts) !== 2) {
                continue;
            }

            $type = $parts[0];
            $linkId = (int) $parts[1];

            if ($linkId <= 0 || !in_array($type, ['book', 'movie'], true)) {
                continue;
            }

            try {
                $this->profileService->moveUnlistedItemToCollection($user, $type, $linkId, $collectionId);
                $movedCount++;
            } catch (\Throwable) {
                $errors++;
            }
        }

        // Messages de confirmation
        if ($movedCount > 0) {
            $this->addFlash(
                'success',
                sprintf('%d élément%s déplacé%s avec succès.', $movedCount, $movedCount > 1 ? 's' : '', $movedCount > 1 ? 's' : '')
            );
        }

        if ($errors > 0) {
            $this->addFlash(
                'warning',
                sprintf('%d élément%s n\'a pas pu être déplacé.', $errors, $errors > 1 ? 's n\'ont' : '')
            );
        }

        if ($movedCount === 0 && $errors === 0) {
            $this->addFlash('warning', 'Aucun élément valide sélectionné.');
        }

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

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');

        if ($linkId <= 0) {
            $this->addFlash('danger', 'Requête invalide.');
            return $this->redirectToRoute('app_profile', ['section' => 'collections']);
        }

        if (!$this->isCsrfTokenValid('remove_item_' . $linkId, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $origin = (string) $request->request->get('origin', '');

        try {
            $this->profileService->removeItemFromCollection($user, $type, $linkId);

            if ($origin === 'unlisted') {
                $this->addFlash('success', 'Élément retiré de votre onglet “Non répertorié”.');
            } elseif ($origin === 'wishlist') {
                $this->addFlash('success', 'Élément retiré de votre onglet “Liste d\'envie”.');
            } else {
                $this->addFlash('success', 'Élément retiré de la collection.');
            }
        } catch (\Throwable) {
            $this->addFlash('danger', 'Impossible de retirer cet élément.');
        }


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
