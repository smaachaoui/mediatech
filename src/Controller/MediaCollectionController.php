<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\LibraryManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        LoggerInterface $logger,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            $this->addFlash('warning', 'Connectez-vous pour ajouter à votre collection.');
            return $this->redirectToRoute('app_login');
        }

        if (!in_array($kind, ['book', 'movie'], true)) {
            throw $this->createNotFoundException('Type de média invalide.');
        }

        $token = (string) $request->request->get('_token', '');
        $tokenId = sprintf('add_media_%s_%s', $kind, $id);

        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirect($this->resolveRedirectTarget($request, $kind, $id));
        }

        try {
            $already = $library->addToDefaultCollection($user, $kind, $id);

            $this->addFlash(
                $already ? 'info' : 'success',
                $already ? 'Déjà présent dans “Non répertorié”.' : 'Ajouté à “Non répertorié”.'
            );
        } catch (\Throwable $e) {
            $logger->error('Erreur ajout Non répertorié', [
                'kind' => $kind,
                'id' => $id,
                'exception' => $e,
            ]);

            $this->addFlash('danger', 'Impossible d’ajouter le média pour le moment.');
        }

        return $this->redirect($this->resolveRedirectTarget($request, $kind, $id));
    }

    #[Route('/media/{kind}/{id}/wishlist', name: 'app_media_add_to_wishlist', methods: ['POST'])]
    public function addToWishlist(
        string $kind,
        string $id,
        Request $request,
        LibraryManager $library,
        LoggerInterface $logger,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            $this->addFlash('warning', 'Connectez-vous pour ajouter à votre liste d’envie.');
            return $this->redirectToRoute('app_login');
        }

        if (!in_array($kind, ['book', 'movie'], true)) {
            throw $this->createNotFoundException('Type de média invalide.');
        }

        $token = (string) $request->request->get('_token', '');
        $tokenId = sprintf('add_wishlist_%s_%s', $kind, $id);

        if (!$this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirect($this->resolveRedirectTarget($request, $kind, $id));
        }

        try {
            $already = $library->addToWishlistCollection($user, $kind, $id);

            $this->addFlash(
                $already ? 'info' : 'success',
                $already ? 'Déjà présent dans votre liste d’envie.' : 'Ajouté à votre liste d’envie.'
            );
        } catch (\Throwable $e) {
            $logger->error('Erreur ajout wishlist', [
                'kind' => $kind,
                'id' => $id,
                'exception' => $e,
            ]);

            $this->addFlash('danger', 'Impossible d’ajouter à la liste d’envie pour le moment.');
        }

        return $this->redirect($this->resolveRedirectTarget($request, $kind, $id));
    }

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

        return $this->generateUrl($kind === 'book' ? 'app_book_show' : 'app_movie_show', ['id' => $id]);
    }
}
