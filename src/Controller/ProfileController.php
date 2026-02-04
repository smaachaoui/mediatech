<?php

namespace App\Controller\Profile;

use App\Entity\User;
use App\Form\ProfileEditType;
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
 * Je gere l'affichage et l'edition du profil utilisateur.
 */
#[Route('/profile')]
final class ProfileController extends AbstractController
{
    private const ALLOWED_SECTIONS = ['info', 'collections', 'comments', 'support'];

    /**
     * J'affiche la page profil avec la section demandee.
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
    #[Route('/edit', name: 'app_profile_edit', methods: ['POST'])]
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
            $profilePictureFile = $form->get('profilePictureFile')->getData();

            if ($profilePictureFile) {
                $this->handleProfilePictureUpload($user, $profilePictureFile, $slugger);
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
     * Je gere l'upload de la photo de profil.
     */
    private function handleProfilePictureUpload(
        User $user,
        mixed $profilePictureFile,
        SluggerInterface $slugger
    ): void {
        $originalFilename = pathinfo(
            $profilePictureFile->getClientOriginalName(),
            PATHINFO_FILENAME
        );
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

        try {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $profilePictureFile->move($uploadDir, $newFilename);
            $this->deleteProfilePictureFile($user);
            $user->setProfilePicture('uploads/profiles/' . $newFilename);
        } catch (FileException $e) {
            $this->addFlash('danger', 'Erreur lors de l\'upload de la photo.');
        }
    }

    /**
     * Je supprime le fichier physique de la photo de profil.
     */
    private function deleteProfilePictureFile(User $user): void
    {
        $currentPicture = $user->getProfilePicture();

        if ($currentPicture && str_starts_with($currentPicture, 'uploads/profiles/')) {
            $path = $this->getParameter('kernel.project_dir') . '/public/' . $currentPicture;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}