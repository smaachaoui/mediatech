<?php

namespace App\Service;

use App\Entity\Collection;
use App\Entity\CollectionBook;
use App\Entity\CollectionMovie;
use App\Entity\User;
use App\Repository\CollectionBookRepository;
use App\Repository\CollectionMovieRepository;
use App\Repository\CollectionRepository;
use App\Repository\CommentRepository;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ProfileService
{
    public function __construct(
        private readonly CollectionRepository $collectionRepository,
        private readonly CommentRepository $commentRepository,
        private readonly FriendshipRepository $friendshipRepository,

        private readonly LibraryManager $libraryManager,
        private readonly CollectionBookRepository $collectionBookRepository,
        private readonly CollectionMovieRepository $collectionMovieRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function getProfileData(User $user, string $section): array
    {
        return match ($section) {
            'collections' => $this->getCollectionsSectionData($user),

            'comments' => [
                'comments' => $this->commentRepository->findLatestByAuthor($user),
            ],

            'friends' => [
                'friends' => $this->friendshipRepository->findAcceptedFriends($user),
                'pending' => $this->friendshipRepository->findPendingRequests($user),
            ],

            default => [],
        };
    }

    private function getCollectionsSectionData(User $user): array
    {
        $unlistedCollection = $this->libraryManager->getDefaultCollection($user);

        $unlistedBookLinks = $this->collectionBookRepository->findLinksByCollection($unlistedCollection);
        $unlistedMovieLinks = $this->collectionMovieRepository->findLinksByCollection($unlistedCollection);

        $collections = $this->collectionRepository->findForUserExcluding($user, $unlistedCollection->getId());

        return [
            'collections' => $collections,
            'unlistedCollection' => $unlistedCollection,
            'unlistedBookLinks' => $unlistedBookLinks,
            'unlistedMovieLinks' => $unlistedMovieLinks,
        ];
    }

    /**
     * Crée une collection "utilisateur".
     */
    public function createUserCollection(User $user, string $name): Collection
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Le nom de la collection ne peut pas être vide.');
        }

        $collection = new Collection();
        $collection->setUser($user);
        $collection->setName($name);

        // Champ obligatoire dans ton entité (length 10)
        $collection->setType('user');

        // Par défaut private / non publiée (déjà default côté entité, mais on fixe clairement)
        $collection->setVisibility('private');
        $collection->setIsPublished(false);

        $this->em->persist($collection);
        $this->em->flush();

        return $collection;
    }

    /**
     * Déplace un item depuis "Non répertorié" vers une collection cible.
     * On déplace le PIVOT (CollectionBook/CollectionMovie) => ultra simple.
     */
    public function moveUnlistedItemToCollection(User $user, string $type, int $linkId, int $targetCollectionId): void
    {
        $type = trim($type);

        /** @var Collection|null $target */
        $target = $this->em->getRepository(Collection::class)->find($targetCollectionId);
        if (!$target) {
            throw new \RuntimeException('Collection cible introuvable.');
        }
        if ($target->getUser()?->getId() !== $user->getId()) {
            throw new \RuntimeException('Accès interdit à cette collection.');
        }

        if ($type === 'book') {
            /** @var CollectionBook|null $link */
            $link = $this->em->getRepository(CollectionBook::class)->find($linkId);
        } elseif ($type === 'movie') {
            /** @var CollectionMovie|null $link */
            $link = $this->em->getRepository(CollectionMovie::class)->find($linkId);
        } else {
            throw new \InvalidArgumentException('Type non supporté.');
        }

        if (!$link) {
            throw new \RuntimeException('Élément introuvable.');
        }

        // sécurité : le lien source appartient au user
        if ($link->getCollection()->getUser()?->getId() !== $user->getId()) {
            throw new \RuntimeException('Accès interdit à cet élément.');
        }

        $link->setCollection($target);
        $this->em->flush();
    }
}
