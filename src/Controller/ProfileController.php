<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            'user' => $user, // utile pour _info.html.twig
        ]));
    }
}
