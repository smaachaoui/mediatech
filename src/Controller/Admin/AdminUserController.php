<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Security\Voter\AdminVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere les actions d'administration sur les utilisateurs.
 */
#[Route('/admin/users')]
final class AdminUserController extends AbstractController
{
    /**
     * J'affiche la liste des utilisateurs avec pagination et filtres.
     */
    #[Route('', name: 'admin_users_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted(AdminVoter::ACCESS);

        $filter = (string) $request->query->get('status', 'all');
        $search = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $isActive = null;
        if ($filter === 'active') {
            $isActive = true;
        } elseif ($filter === 'inactive') {
            $isActive = false;
        }

        $result = $userRepository->findPaginatedForAdmin($isActive, $search, $page, $limit);

        $total = (int) $result['total'];
        $totalPages = max(1, (int) ceil($total / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $userRepository->findPaginatedForAdmin($isActive, $search, $page, $limit);
        }

        return $this->render('admin/index.html.twig', [
            'section' => 'users',
            'users' => $result['items'],
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filter' => $filter,
            'search' => $search,
        ]);
    }

    /**
     * Je bascule le statut actif/inactif d'un utilisateur.
     */
    #[Route('/{id}/toggle-status', name: 'admin_users_toggle_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleStatus(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(AdminVoter::ACCESS);

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        /*
         * Je verifie le token CSRF pour securiser l'action.
         */
        if (!$this->isCsrfTokenValid('admin_toggle_user_' . $user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_users_index');
        }

        /*
         * J'empeche un admin de se desactiver lui-meme.
         */
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier votre propre statut.');
            return $this->redirectToRoute('admin_users_index');
        }

        $user->setActive(!$user->isActive());
        $em->flush();

        $this->addFlash(
            'success',
            $user->isActive()
                ? sprintf('Le compte de %s a été réactivé.', $user->getPseudo())
                : sprintf('Le compte de %s a été désactivé.', $user->getPseudo())
        );

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * Je bascule le role admin d'un utilisateur.
     */
    #[Route('/{id}/toggle-admin', name: 'admin_users_toggle_admin', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleAdmin(
        int $id,
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(AdminVoter::ACCESS);

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if (!$this->isCsrfTokenValid('admin_toggle_admin_' . $user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_users_index');
        }

        /*
         * J'empeche un admin de retirer ses propres droits.
         */
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier vos propres droits administrateur.');
            return $this->redirectToRoute('admin_users_index');
        }

        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles, true);

        if ($isAdmin) {
            /*
             * Je retire le role admin.
             */
            $newRoles = array_filter($roles, fn($r) => $r !== 'ROLE_ADMIN' && $r !== 'ROLE_USER');
            $user->setRoles(array_values($newRoles));
            $this->addFlash('success', sprintf('%s n\'est plus administrateur.', $user->getPseudo()));
        } else {
            /*
             * J'ajoute le role admin.
             */
            $user->setRoles(['ROLE_ADMIN']);
            $this->addFlash('success', sprintf('%s est maintenant administrateur.', $user->getPseudo()));
        }

        $em->flush();

        return $this->redirectToRoute('admin_users_index');
    }
}