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

        $type = (string) $request->request->get('type');          // book|movie
        $linkId = (int) $request->request->get('linkId');         // ID pivot
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

        $name = trim((string) $request->request->get('name'));
        $profileService->createUserCollection($user, $name);

        $this->addFlash('success', 'Collection créée.');
        return $this->redirectToRoute('app_profile', ['section' => 'collections']);
    }
}
