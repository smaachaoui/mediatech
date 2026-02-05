<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\LibraryManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class MediaCollectionController extends AbstractController
{
    #[Route('/media/{kind}/{id}/add', name: 'app_media_add_to_default', methods: ['POST'])]
    public function addToDefault(
        string $kind,
        string $id,
        Request $request,
        LibraryManager $library,
        LoggerInterface $logger
    ): Response {
        return $this->handleAdd(
            kind: $kind,
            id: $id,
            request: $request,
            logger: $logger,
            tokenPrefix: 'add_media',
            addCallback: static fn (User $user) => $library->addToDefaultCollection($user, $kind, $id),
            successMessage: 'Ajouté à "Non répertorié".|/profile?section=collections#tab-unlisted|Voir',
            movedMessage: 'Déplacé vers "Non répertorié".|/profile?section=collections#tab-unlisted|Voir',
            alreadyMessage: 'Déjà présent dans "Non répertorié".|/profile?section=collections#tab-unlisted|Voir',
            invalidAuthMessage: 'Connectez-vous pour ajouter à votre collection.',
            errorLogMessage: 'Erreur ajout Non répertorié',
            errorFlashMessage: 'Impossible d’ajouter le média pour le moment.'
        );
    }

    #[Route('/media/{kind}/{id}/wishlist', name: 'app_media_add_to_wishlist', methods: ['POST'])]
    public function addToWishlist(
        string $kind,
        string $id,
        Request $request,
        LibraryManager $library,
        LoggerInterface $logger
    ): Response {
        return $this->handleAdd(
            kind: $kind,
            id: $id,
            request: $request,
            logger: $logger,
            tokenPrefix: 'add_wishlist',
            addCallback: static fn (User $user) => $library->addToWishlistCollection($user, $kind, $id),
            successMessage: "Ajouté à votre liste d'envie.|/profile?section=collections#tab-wishlist|Voir",
            movedMessage: "Déplacé vers votre liste d'envie.|/profile?section=collections#tab-wishlist|Voir",
            alreadyMessage: "Déjà présent dans votre liste d'envie.|/profile?section=collections#tab-wishlist|Voir",
            invalidAuthMessage: 'Connectez-vous pour ajouter à votre liste d’envie.',
            errorLogMessage: 'Erreur ajout wishlist',
            errorFlashMessage: 'Impossible d’ajouter à la liste d’envie pour le moment.'
        );
    }

    /**
     * Je centralise la logique d'ajout en collection (CSRF, auth, flash, redirect).
     *
     * @param callable(User): string $addCallback Je retourne un resultat ADD_RESULT_*.
     */
    private function handleAdd(
        string $kind,
        string $id,
        Request $request,
        LoggerInterface $logger,
        string $tokenPrefix,
        callable $addCallback,
        string $successMessage,
        string $movedMessage,
        string $alreadyMessage,
        string $invalidAuthMessage,
        string $errorLogMessage,
        string $errorFlashMessage
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            $this->addFlash('warning', $invalidAuthMessage);

            return $this->redirectToRoute('app_login');
        }

        if (!in_array($kind, ['book', 'movie'], true)) {
            throw $this->createNotFoundException('Type de média invalide.');
        }

        $token = (string) $request->request->get('_token', '');
        $tokenId = sprintf('%s_%s_%s', $tokenPrefix, $kind, $id);

        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');

            return $this->redirect($this->resolveRedirectTarget($request, $kind, $id));
        }

        try {
            $result = (string) $addCallback($user);

            if ($result === LibraryManager::ADD_RESULT_ALREADY) {
                $this->addFlash('info', $alreadyMessage);
            } elseif ($result === LibraryManager::ADD_RESULT_MOVED) {
                $this->addFlash('success', $movedMessage);
            } else {
                $this->addFlash('success', $successMessage);
            }
        } catch (\Throwable $e) {
            $logger->error($errorLogMessage, [
                'kind' => $kind,
                'id' => $id,
                'exception' => $e,
            ]);

            $this->addFlash('danger', $errorFlashMessage);
        }

        return $this->redirect($this->resolveRedirectTarget($request, $kind, $id));
    }

    /**
     * Je determine une cible de redirection sure, sinon je reviens sur la page show du media.
     */
    private function resolveRedirectTarget(Request $request, string $kind, string $id): string
    {
        $redirectTo = $request->request->get('redirectTo');

        if (is_string($redirectTo) && $redirectTo !== '') {
            if (str_starts_with($redirectTo, '/')) {
                return $redirectTo;
            }

            if (str_starts_with($redirectTo, 'http')) {
                $host = (string) $request->getHost();
                $parsedHost = parse_url($redirectTo, PHP_URL_HOST);

                if (is_string($parsedHost) && $parsedHost === $host) {
                    return $redirectTo;
                }
            }
        }

        $referer = $request->headers->get('referer');
        if (is_string($referer) && $referer !== '') {
            $host = (string) $request->getHost();
            $parsedHost = parse_url($referer, PHP_URL_HOST);

            if (is_string($parsedHost) && $parsedHost === $host) {
                return $referer;
            }
        }

        return $this->generateUrl(
            $kind === 'book' ? 'app_book_show' : 'app_movie_show',
            ['id' => $id]
        );
    }
}
