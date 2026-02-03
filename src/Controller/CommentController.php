<?php

namespace App\Controller;

use App\Entity\Collection;
use App\Entity\Comment;
use App\Repository\CollectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

final class CommentController extends AbstractController
{
    #[Route('/collections/{id}/comment', name: 'app_collection_comment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function createForCollection(
        int $id,
        Request $request,
        CollectionRepository $collectionRepository,
        EntityManagerInterface $em,
        RateLimiterFactory $collectionCommentLimiter
    ): Response {
        $collection = $collectionRepository->find($id);
        if (!$collection) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if (
            !$collection->isPublished()
            || $collection->getVisibility() !== 'public'
            || $collection->getScope() !== Collection::SCOPE_USER
        ) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if (
            !$this->isCsrfTokenValid(
                'comment_collection_'.$collection->getId(),
                (string) $request->request->get('_token')
            )
        ) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        $honeypot = trim((string) $request->request->get('website', ''));
        if ($honeypot !== '') {
            $this->addFlash('danger', 'Action non autorisée.');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        $clientIp = (string) ($request->getClientIp() ?? 'unknown');
        $limiterKey = $clientIp.'|collection:'.$collection->getId();
        $limit = $collectionCommentLimiter->create($limiterKey)->consume(1);

        if (!$limit->isAccepted()) {
            $this->addFlash('danger', 'Trop de tentatives. Réessayez dans quelques instants.');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        $content = trim((string) $request->request->get('comment_content', ''));
        if ($content === '') {
            $this->addFlash('danger', 'Le commentaire ne peut pas être vide.');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        if (mb_strlen($content) > 1000) {
            $this->addFlash('danger', 'Le commentaire est trop long (1000 caractères maximum).');
            return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
        }

        $owner = $collection->getUser();
        $viewer = $this->getUser();

        $comment = new Comment();
        $comment->setCollection($collection);
        $comment->setContent($content);
        $comment->setIpAddress($clientIp);

        $ua = (string) $request->headers->get('User-Agent', '');
        $comment->setUserAgent(mb_substr($ua, 0, 255));

        if ($viewer instanceof \App\Entity\User) {
            if ($owner && $owner->getId() === $viewer->getId()) {
                $this->addFlash('danger', 'Vous ne pouvez pas commenter votre propre collection.');
                return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
            }

            $comment->setUser($viewer);
        } else {
            $guestName = trim((string) $request->request->get('guest_name', ''));
            $guestEmail = trim((string) $request->request->get('guest_email', ''));

            if ($guestName === '' || $guestEmail === '') {
                $this->addFlash('danger', 'Nom et email sont requis pour commenter en tant qu’invité.');
                return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
            }

            if (!filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('danger', 'Email invalide.');
                return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
            }

            if ($owner && $owner->getEmail() && mb_strtolower($owner->getEmail()) === mb_strtolower($guestEmail)) {
                $this->addFlash('danger', 'Vous ne pouvez pas commenter votre propre collection.');
                return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
            }

            $comment->setUser(null);
            $comment->setGuestName(mb_substr($guestName, 0, 60));
            $comment->setGuestEmail(mb_strtolower(mb_substr($guestEmail, 0, 180)));
        }

        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Votre commentaire a été publié.');
        return $this->redirectToRoute('app_collection_show', ['id' => $collection->getId()]);
    }
}
