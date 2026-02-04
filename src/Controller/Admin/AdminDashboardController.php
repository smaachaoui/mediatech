<?php

namespace App\Controller\Admin;

use App\Security\Voter\AdminVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
final class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    #[Route('/{section}', name: 'admin_dashboard_section', requirements: ['section' => 'overview|comments|collections|users'], methods: ['GET'])]
    public function index(string $section = 'overview'): Response
    {
        $this->denyAccessUnlessGranted(AdminVoter::ACCESS);

        return $this->render('admin/index.html.twig', [
            'section' => $section,
        ]);
    }
}
