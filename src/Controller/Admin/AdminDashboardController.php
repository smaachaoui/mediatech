<?php

namespace App\Controller\Admin;

use App\Repository\CollectionRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Security\Voter\AdminVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere le tableau de bord d'administration.
 */
#[Route('/admin')]
final class AdminDashboardController extends AbstractController
{
    /**
     * J'affiche la vue d'ensemble avec les statistiques.
     */
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        CollectionRepository $collectionRepository,
        CommentRepository $commentRepository
    ): Response {
        $this->denyAccessUnlessGranted(AdminVoter::ACCESS);

        /*
         * Je recupere les statistiques pour le tableau de bord.
         */
        $stats = [
            'users' => [
                'total' => $userRepository->countAll(),
                'active' => $userRepository->countActive(),
                'thisMonth' => $userRepository->countRegisteredThisMonth(),
            ],
            'collections' => [
                'total' => $collectionRepository->countUserCollections(),
                'published' => $collectionRepository->countPublished(),
            ],
            'comments' => [
                'total' => $commentRepository->countAll(),
                'thisWeek' => $commentRepository->countThisWeek(),
            ],
        ];

        return $this->render('admin/index.html.twig', [
            'section' => 'overview',
            'stats' => $stats,
        ]);
    }
}