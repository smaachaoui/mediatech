<?php

namespace App\Controller;

use App\Service\LibraryManager;
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
    ): Response {
        if (!$this->getUser()) {
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
            return $this->redirectToRoute($kind === 'book' ? 'app_book_show' : 'app_movie_show', ['id' => $id]);
        }

        try {
            $already = $library->addToDefaultCollection($this->getUser(), $kind, $id);

            if ($already) {
                $this->addFlash('info', 'Déjà présent dans “Non répertorié”.');
            } else {
                $this->addFlash('success', 'Ajouté à “Non répertorié”.');
            }
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Impossible d’ajouter le média pour le moment.');
        }

        return $this->redirectToRoute($kind === 'book' ? 'app_book_show' : 'app_movie_show', ['id' => $id]);
    }
}
