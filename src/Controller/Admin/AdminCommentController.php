<?php

namespace App\Controller\Admin;

use App\Repository\CommentRepository;
use App\Security\Voter\AdminVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere la moderation des commentaires.
 */
#[Route('/admin/comments')]
final class AdminCommentController extends AbstractController
{
    /**
     * J'affiche la liste des commentaires avec pagination.
     */
    #[Route('', name: 'admin_comments_index', methods: ['GET'])]
    public function index(Request $request, CommentRepository $commentRepository): Response
    {
        $this->denyAccessUnlessGranted(AdminVoter::COMMENTS_MODERATE);

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $result = $commentRepository->findPaginatedForAdmin($page, $limit);

        $total = (int) $result['total'];
        $totalPages = max(1, (int) ceil($total / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $commentRepository->findPaginatedForAdmin($page, $limit);
        }

        return $this->render('admin/index.html.twig', [
            'section' => 'comments',
            'comments' => $result['items'],
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    /**
     * Je supprime un commentaire.
     */
    #[Route('/{id}/delete', name: 'admin_comments_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        CommentRepository $commentRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $this->denyAccessUnlessGranted(AdminVoter::COMMENTS_MODERATE);

        $comment = $commentRepository->find($id);
        if (!$comment) {
            throw $this->createNotFoundException('Commentaire introuvable.');
        }

        /*
         * Je verifie le token CSRF pour securiser l'action.
         */
        if (!$this->isCsrfTokenValid('admin_delete_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('admin_comments_index');
        }

        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé.');

        return $this->redirectToRoute('admin_comments_index');
    }
}