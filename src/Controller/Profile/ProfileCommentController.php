<?php

namespace App\Controller\Profile;

use App\Entity\User;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Je gere les commentaires de l'utilisateur connecte.
 */
#[Route('/profile/comments')]
final class ProfileCommentController extends AbstractController
{
    /**
     * Je supprime un commentaire de l'utilisateur.
     */
    #[Route('/{id}/delete', name: 'app_profile_delete_comment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
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

        if ($comment->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Ce commentaire ne vous appartient pas.');
        }

        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire supprimé.');

        return $this->redirectToRoute('app_profile', ['section' => 'comments']);
    }
}