<?php

namespace App\Controller\Admin;

use App\Repository\ContactMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/messages')]
final class AdminContactController extends AbstractController
{
    /**
     * Je liste tous les messages de contact.
     */
    #[Route('', name: 'admin_messages_index', methods: ['GET'])]
    public function index(
        Request $request,
        ContactMessageRepository $contactMessageRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 20;

        $filter = (string) $request->query->get('read', 'all');
        $allowedFilters = ['all', 'read', 'unread'];
        if (!in_array($filter, $allowedFilters, true)) {
            $filter = 'all';
        }

        // Filtrer selon le statut
        $criteria = [];
        if ($filter === 'read') {
            $criteria['isRead'] = true;
        } elseif ($filter === 'unread') {
            $criteria['isRead'] = false;
        }

        // Récupérer les messages
        $qb = $contactMessageRepository->createQueryBuilder('m');
        
        if (!empty($criteria)) {
            foreach ($criteria as $field => $value) {
                $qb->andWhere("m.$field = :$field")
                   ->setParameter($field, $value);
            }
        }

        $qb->orderBy('m.createdAt', 'DESC');

        $totalMessages = count($qb->getQuery()->getResult());
        $totalPages = max(1, (int) ceil($totalMessages / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $messages = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Compter les non lus pour le badge
        $unreadCount = $contactMessageRepository->count(['isRead' => false]);

        return $this->render('admin/index.html.twig', [
            'section' => 'messages',
            'messages' => $messages,
            'filter' => $filter,
            'page' => $page,
            'totalPages' => $totalPages,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Je marque un message comme lu.
     */
    #[Route('/{id}/mark-read', name: 'admin_messages_mark_read', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function markAsRead(
        int $id,
        Request $request,
        ContactMessageRepository $contactMessageRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('mark_read_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $message = $contactMessageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException();
        }

        $message->setIsRead(true);
        $em->flush();

        $this->addFlash('success', 'Message marqué comme lu.');

        return $this->redirectToRoute('admin_messages_index');
    }

    /**
     * Je supprime un message de contact.
     */
    #[Route('/{id}/delete', name: 'admin_messages_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        int $id,
        Request $request,
        ContactMessageRepository $contactMessageRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_message_' . $id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $message = $contactMessageRepository->find($id);
        if (!$message) {
            throw $this->createNotFoundException();
        }

        $em->remove($message);
        $em->flush();

        $this->addFlash('success', 'Message supprimé.');

        return $this->redirectToRoute('admin_messages_index');
    }
}