<?php

namespace App\Controller\Profile;

use App\Entity\User;
use App\Form\ProfileEditType;
use App\Service\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Je gère l'affichage et l'édition du profil utilisateur.
 */
#[Route('/profile')]
final class ProfileController extends AbstractController
{
    private const ALLOWED_SECTIONS = ['info', 'collections', 'comments', 'support'];

    /**
     * J'affiche la page profil avec la section demandée.
     */
    #[Route('', name: 'app_profile', methods: ['GET'])]
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

        // Form uniquement sur la section "info"
        $editFormView = null;
        if ($section === 'info') {
            $editFormView = $this->createForm(ProfileEditType::class, $user)->createView();
        }

        return $this->render('profile/index.html.twig', array_merge($data, [
            'section' => $section,
            'user' => $user,
            'editForm' => $editFormView,
        ]));
    }

    /**
     * Je traite la modification du profil utilisateur.
     *
     * IMPORTANT : en cas d'erreur de validation, je ré-affiche la page "info"
     * avec les erreurs du formulaire (pas de redirect, sinon on perd les erreurs).
     */
    #[Route('/edit', name: 'app_profile_edit', methods: ['POST'])]
    public function edit(
        Request $request,
        ProfileService $profileService,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileEditType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $profilePictureFile */
            $profilePictureFile = $form->get('profilePictureFile')->getData();

            if ($profilePictureFile instanceof UploadedFile) {
                $this->handleProfilePictureUpload($user, $profilePictureFile, $slugger);
            }

            $em->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profile', ['section' => 'info']);
        }

        // Form invalide : on ré-affiche la page profil section info, avec les erreurs
        $data = $profileService->getProfileData($user, 'info');

        $this->addFlash('danger', 'Merci de corriger les erreurs du formulaire.');

        return $this->render('profile/index.html.twig', array_merge($data, [
            'section' => 'info',
            'user' => $user,
            'editForm' => $form->createView(),
        ]));
    }

    /**
     * Je modifie l'email de l'utilisateur connecté.
     */
    #[Route('/email', name: 'app_profile_update_email', methods: ['POST'])]
    public function updateEmail(
        Request $request,
        ProfileService $profileService
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('update_email', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $email = trim((string) $request->request->get('email', ''));

        try {
            $profileService->updateUserEmail($user, $email);
            $this->addFlash('success', 'Email mis à jour.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_profile', ['section' => 'info']);
    }

    /**
     * Je supprime la photo de profil de l'utilisateur.
     */
    #[Route('/remove-picture', name: 'app_profile_remove_picture', methods: ['POST'])]
    public function removePicture(Request $request, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('remove_picture', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $this->deleteProfilePictureFile($user);
        $user->setProfilePicture(null);

        $em->flush();

        $this->addFlash('success', 'Photo de profil supprimée.');

        return $this->redirectToRoute('app_profile', ['section' => 'info']);
    }

    /**
     * Je gère l'upload de la photo de profil.
     */
    private function handleProfilePictureUpload(
        User $user,
        UploadedFile $profilePictureFile,
        SluggerInterface $slugger
    ): void {
        $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = (string) $slugger->slug($originalFilename);

        // Extension : on prend guessExtension, sinon fallback propre
        $extension = $profilePictureFile->guessExtension() ?: 'bin';

        $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $extension;

        try {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $profilePictureFile->move($uploadDir, $newFilename);

            // Supprime l'ancienne image si existante
            $this->deleteProfilePictureFile($user);

            // On stocke un chemin relatif depuis /public
            $user->setProfilePicture('uploads/profiles/' . $newFilename);
        } catch (FileException) {
            $this->addFlash('danger', 'Erreur lors de l\'upload de la photo.');
        }
    }

    /**
     * Je supprime le fichier physique de la photo de profil.
     */
    private function deleteProfilePictureFile(User $user): void
    {
        $currentPicture = $user->getProfilePicture();

        if (!$currentPicture) {
            return;
        }

        // On sécurise : on ne supprime que ce qui est dans uploads/profiles/
        if (!str_starts_with($currentPicture, 'uploads/profiles/')) {
            return;
        }

        $path = $this->getParameter('kernel.project_dir') . '/public/' . $currentPicture;

        if (is_file($path)) {
            @unlink($path);
        }
    }
}
