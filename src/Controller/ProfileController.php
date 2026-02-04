<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditType;
use App\Repository\CommentRepository;
use App\Service\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Je gere l'espace personnel de l'utilisateur connecte.
 */
final class ProfileController extends AbstractController
{
    private const ALLOWED_SECTIONS = ['info', 'collections', 'comments', 'support'];

    /**
     * J'affiche la page profil avec la section demandee.
     */
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

        /*
         * Je prepare le formulaire d'edition du profil pour la section info.
         */
        $editForm = null;
        if ($section === 'info') {
            $editForm = $this->createForm(ProfileEditType::class, $user);
        }

        return $this->render('profile/index.html.twig', array_merge($data, [
            'section' => $section,
            'user' => $user,
            'editForm' => $editForm?->createView(),
        ]));
    }

    /**
     * Je traite la modification du profil utilisateur.
     */
    #[Route('/profile/edit', name: 'app_profile_edit', methods: ['POST'])]
    public function edit(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*
             * Je gere l'upload de la photo de profil si presente.
             */
            $profilePictureFile = $form->get('profilePictureFile')->getData();
            if ($profilePictureFile) {
                $originalFilename = pathinfo(
                    $profilePictureFile->getClientOriginalName(),
                    PATHINFO_FILENAME
                );
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';

                    /*
                     * Je cree le dossier s'il n'existe pas.
                     */
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $profilePictureFile->move($uploadDir, $newFilename);

                    /*
                     * Je supprime l'ancienne photo si elle existe.
                     */
                    $oldPicture = $user->getProfilePicture();
                    if ($oldPicture && str_starts_with($oldPicture, 'uploads/profiles/')) {
                        $oldPath = $this->getParameter('kernel.project_dir') . '/public/' . $oldPicture;
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }

                    $user->setProfilePicture('uploads/profiles/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'upload de la photo.');

                    return $this->redirectToRoute('app_profile', ['section' => 'info']);
                }
            }

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
        } else {
            $this->addFlash('danger', 'Erreur lors de la mise à jour du profil.');
        }

        return $this->redirectToRoute('app_profile', ['section' => 'info']);
    }

    /**
     * Je supprime la photo de profil de l'utilisateur.
     */
    #[Route('/profile/remove-picture', name: 'app_profile_remove_picture', methods: ['POST'])]
    public function removePicture(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('remove_picture', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $currentPicture = $user->getProfilePicture();
        if ($currentPicture && str_starts_with($currentPicture, 'uploads/profiles/')) {
            $path = $this->getParameter('kernel.project_dir') . '/public/' . $currentPicture;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $user->setProfilePicture(null);
        $em->flush();

        $this->addFlash('success', 'Photo de profil supprimée.');

        return $this->redirectToRoute('app_profile', ['section' => 'info']);
    }

    /**
     * Je supprime un commentaire de l'utilisateur connecte.
     */
    #[Route(
        '/profile/comments/{id}/delete',
        name: 'app_profile_delete_comment',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function deleteComment(
        int $id,
        Request $request,
        CommentRepository $commentRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('delete_comment_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $comment = $commentRepository->find($id);
        if (!$comment) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        /*
         * Je verifie que le commentaire appartient bien a l'utilisateur.
         */
        if ($comment->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Ce commentaire ne vous appartient pas.');
        }

        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé.');

        return $this->redirectToRoute('app_profile', ['section' => 'comments']);
    }

    /*
     * ========================================================================
     * GESTION DES COLLECTIONS
     * ========================================================================
     */

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
                ? 'Collection publiée : elle apparaît dans "Nos collections".'
                : 'Collection dépubliée : elle n\'apparaît plus dans "Nos collections".'
        );

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }

    /*
     * ========================================================================
     * GESTION DE LA WISHLIST
     * ========================================================================
     */

    #[Route('/profile/wishlist/item/remove', name: 'app_profile_wishlist_remove_item', methods: ['POST'])]
    public function removeWishlistItem(Request $request, ProfileService $profileService): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('wishlist_remove_item', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');

        $profileService->removeItemFromWishlist($user, $type, $linkId);

        $this->addFlash('success', 'Retiré de votre liste d\'envie.');

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

        $type = (string) $request->request->get('type');
        $linkId = (int) $request->request->get('linkId');
        $collectionId = (int) $request->request->get('collectionId');

        $profileService->moveWishlistItemToCollection($user, $type, $linkId, $collectionId);

        $this->addFlash('success', 'Déplacé avec succès.');

        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }
}
