<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Collection;
use App\Entity\Rating;
use App\Entity\User;
use App\Repository\CollectionRepository;
use App\Repository\CommentRepository;
use App\Repository\GenreRepository;
use App\Repository\RatingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PublicCollectionController extends AbstractController
{
    #[Route('/collections', name: 'app_collections_public', methods: ['GET'])]
    public function index(
        Request $request,
        CollectionRepository $collectionRepository,
        GenreRepository $genreRepository
    ): Response {
        $mediaType = (string) $request->query->get('type', Collection::MEDIA_ALL);
        if (!in_array($mediaType, Collection::MEDIA_TYPES, true)) {
            $mediaType = Collection::MEDIA_ALL;
        }

        $sort = (string) $request->query->get('sort', 'new');
        $allowedSorts = ['new', 'old', 'alpha'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'new';
        }

        $genre = trim((string) $request->query->get('genre', ''));

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 9;

        /*
         * Je recupere les genres disponibles selon le type de media.
         * Si type=all, je recupere livres et films.
         * Sinon, uniquement le type selectionne.
         */
        $bookGenres = [];
        $movieGenres = [];

        if ($mediaType === Collection::MEDIA_ALL || $mediaType === Collection::MEDIA_BOOK) {
            $bookGenres = $genreRepository->findBookGenres();
        }

        if ($mediaType === Collection::MEDIA_ALL || $mediaType === Collection::MEDIA_MOVIE) {
            $movieGenres = $genreRepository->findMovieGenres();
        }

        /*
         * Je recupere les collections filtrees par genre si necessaire.
         * Le repository gere la logique de filtrage en base de donnees.
         */
        $result = $collectionRepository->findPublicPublishedPaginated($mediaType, $sort, $page, $limit, $genre);

        $total = (int) $result['total'];
        $totalPages = max(1, (int) ceil($total / $limit));

        if ($page > $totalPages) {
            $page = $totalPages;
            $result = $collectionRepository->findPublicPublishedPaginated($mediaType, $sort, $page, $limit, $genre);
        }

        return $this->render('collection/index.html.twig', [
            'collections' => $result['items'],
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'filters' => [
                'type' => $mediaType,
                'sort' => $sort,
                'genre' => $genre,
            ],
            'bookGenres' => $bookGenres,
            'movieGenres' => $movieGenres,
        ]);
    }

    #[Route('/collections/{id}', name: 'app_collection_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int $id,
        CollectionRepository $collectionRepository,
        RatingRepository $ratingRepository,
        CommentRepository $commentRepository
    ): Response {
        $collection = $collectionRepository->find($id);

        if (!$collection instanceof Collection) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        if (
            !$collection->isPublished()
            || $collection->getVisibility() !== Collection::VISIBILITY_PUBLIC
            || $collection->getScope() !== Collection::SCOPE_USER
        ) {
            throw $this->createNotFoundException('Collection introuvable.');
        }

        $ratingStats = $ratingRepository->getStatsForCollection($collection);
        $comments = $commentRepository->findLatestByCollection($collection, 20);

        /*
         * Je recupere la note du visiteur si il est connecte.
         * Je m'en sers pour masquer le formulaire de note dans le template.
         */
        $userRating = null;

        $viewer = $this->getUser();
        if ($viewer instanceof User) {
            $userRating = $ratingRepository->findOneBy([
                'collection' => $collection,
                'user' => $viewer,
            ]);
        }

        return $this->render('collection/show.html.twig', [
            'collection' => $collection,
            'ratingAvg' => (float) ($ratingStats['avg'] ?? 0),
            'ratingCount' => (int) ($ratingStats['count'] ?? 0),
            'comments' => $comments,
            'userRating' => $userRating instanceof Rating ? $userRating : null,
            'userRatingValue' => $userRating instanceof Rating ? $userRating->getValue() : null,
        ]);
    }
}