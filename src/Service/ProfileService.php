<?php

namespace App\Service;

use App\Entity\Collection;
use App\Entity\CollectionBook;
use App\Entity\CollectionMovie;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\CollectionBookRepository;
use App\Repository\CollectionMovieRepository;
use App\Repository\CollectionRepository;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class ProfileService
{
    public function __construct(
        private readonly CollectionRepository $collectionRepository,
        private readonly CommentRepository $commentRepository,
        private readonly LibraryManager $libraryManager,
        private readonly CollectionBookRepository $collectionBookRepository,
        private readonly CollectionMovieRepository $collectionMovieRepository,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,

    ) {}

    public function getProfileData(User $user, string $section): array
    {
        return match ($section) {
            'collections' => $this->getCollectionsSectionData($user),

            'comments' => [
                'comments' => $this->commentRepository->findLatestByAuthor($user),
            ],

            default => [],
        };
    }

    private function getCollectionsSectionData(User $user): array
    {
        $unlistedCollection = $this->libraryManager->getDefaultCollection($user);
        $wishlistCollection = $this->libraryManager->getWishlistCollection($user);

        $unlistedBookLinks = $this->collectionBookRepository->findLinksByCollection($unlistedCollection);
        $unlistedMovieLinks = $this->collectionMovieRepository->findLinksByCollection($unlistedCollection);

        $wishlistBookLinks = $this->collectionBookRepository->findLinksByCollection($wishlistCollection);
        $wishlistMovieLinks = $this->collectionMovieRepository->findLinksByCollection($wishlistCollection);

        $collections = $this->collectionRepository->findForUserExcluding($user, $unlistedCollection->getId());

        return [
            'collections' => $collections,

            'unlistedCollection' => $unlistedCollection,
            'unlistedBookLinks' => $unlistedBookLinks,
            'unlistedMovieLinks' => $unlistedMovieLinks,

            'wishlistCollection' => $wishlistCollection,
            'wishlistBookLinks' => $wishlistBookLinks,
            'wishlistMovieLinks' => $wishlistMovieLinks,
        ];
    }

    public function removeItemFromWishlist(User $user, string $type, int $linkId): void
    {
        $type = trim($type);

        if ($type === 'book') {
            $link = $this->em->getRepository(CollectionBook::class)->find($linkId);
        } elseif ($type === 'movie') {
            $link = $this->em->getRepository(CollectionMovie::class)->find($linkId);
        } else {
            throw new \InvalidArgumentException('Type non supporté.');
        }

        if (!$link) {
            throw new \RuntimeException('Élément introuvable.');
        }

        $collection = $link->getCollection();
        if (!$collection) {
            throw new \RuntimeException('Collection introuvable.');
        }

        if ($collection->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Accès interdit.');
        }

        if ($collection->getScope() !== Collection::SCOPE_SYSTEM || $collection->getName() !== 'Liste d\'envie') {
            throw new \RuntimeException('Cette action est réservée à la liste d’envie.');
        }

        $this->em->remove($link);
        $this->em->flush();
    }

    public function moveWishlistItemToCollection(User $user, string $type, int $linkId, int $targetCollectionId): void
    {
        $type = trim($type);

        /** @var Collection|null $target */
        $target = $this->em->getRepository(Collection::class)->find($targetCollectionId);
        if (!$target) {
            throw new \RuntimeException('Collection cible introuvable.');
        }

        if ($target->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Accès interdit à cette collection.');
        }

        if ($type === 'book') {
            $link = $this->em->getRepository(CollectionBook::class)->find($linkId);
        } elseif ($type === 'movie') {
            $link = $this->em->getRepository(CollectionMovie::class)->find($linkId);
        } else {
            throw new \InvalidArgumentException('Type non supporté.');
        }

        if (!$link) {
            throw new \RuntimeException('Élément introuvable.');
        }

        $source = $link->getCollection();
        if (!$source) {
            throw new \RuntimeException('Collection source introuvable.');
        }

        if ($source->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Accès interdit à cet élément.');
        }

        if ($source->getScope() !== Collection::SCOPE_SYSTEM || $source->getName() !== 'Liste d\'envie') {
            throw new \RuntimeException('Cette action est réservée à la liste d’envie.');
        }

        if ($target->getScope() === Collection::SCOPE_SYSTEM && $target->getName() !== 'Non répertorié') {
            throw new \RuntimeException('Impossible de déplacer vers une collection système autre que “Non répertorié”.');
        }

        $targetMedia = $target->getMediaType();
        if ($targetMedia === '') {
            $targetMedia = Collection::MEDIA_ALL;
        }

        if ($targetMedia !== Collection::MEDIA_ALL) {
            if ($type === 'book' && $targetMedia !== Collection::MEDIA_BOOK) {
                throw new \RuntimeException('Cette collection est réservée aux films.');
            }
            if ($type === 'movie' && $targetMedia !== Collection::MEDIA_MOVIE) {
                throw new \RuntimeException('Cette collection est réservée aux livres.');
            }
        }

        $link->setCollection($target);
        $this->em->flush();
    }

    /**
     * Je crée une collection "utilisateur".
     * mediaType attendu: book|movie (jamais all pour une collection user).
     *
     * Je n'impose pas la cover au moment de la création, car l'utilisateur choisit souvent
     * une couverture une fois des médias ajoutés. Si coverImage est fourni, je le valide
     * uniquement si la collection contient déjà des médias (rare au moment de la création).
     */
    public function createUserCollection(
        User $user,
        string $name,
        ?string $genre,
        ?string $coverImage,
        ?string $description,
        string $mediaType
    ): Collection {
        $name = trim($name);
        $description = $description !== null ? trim($description) : null;
        $genre = $genre !== null ? trim($genre) : null;
        $coverImage = $coverImage !== null ? trim($coverImage) : null;
        $mediaType = trim($mediaType);

        if ($name === '') {
            throw new \InvalidArgumentException('Le nom de la collection ne peut pas être vide.');
        }

        if (!in_array($mediaType, [Collection::MEDIA_BOOK, Collection::MEDIA_MOVIE], true)) {
            throw new \InvalidArgumentException('Type de collection invalide.');
        }

        $collection = new Collection();
        $collection->setUser($user);
        $collection->setName($name);
        $collection->setGenre($genre ?: null);
        $collection->setDescription($description ?: null);

        $collection->setScope(Collection::SCOPE_USER);
        $collection->setMediaType($mediaType);

        $collection->setVisibility('private');
        $collection->setIsPublished(false);

        $this->em->persist($collection);
        $this->em->flush();

        if ($coverImage !== null && $coverImage !== '') {
            $this->assertCoverBelongsToCollection($collection, $coverImage);
            $collection->setCoverImage($coverImage);
            $this->em->flush();
        }

        return $collection;
    }

    /**
     * Je mets à jour une collection utilisateur.
     * Je valide la cover: elle doit correspondre à un média présent dans la collection,
     * ou être null (je ne change rien) / "__none__" (je supprime).
     */
    public function updateUserCollection(
        User $user,
        int $collectionId,
        string $name,
        ?string $genre,
        ?string $coverImage,
        ?string $description
    ): void {
        /** @var Collection|null $collection */
        $collection = $this->em->getRepository(Collection::class)->find($collectionId);
        if (!$collection) {
            throw new \RuntimeException('Collection introuvable.');
        }

        if ($collection->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Accès interdit.');
        }

        if ($collection->getScope() === Collection::SCOPE_SYSTEM) {
            throw new \RuntimeException('Cette collection ne peut pas être modifiée.');
        }

        $name = trim($name);
        $description = $description !== null ? trim($description) : null;
        $genre = $genre !== null ? trim($genre) : null;

        if ($name === '') {
            throw new \InvalidArgumentException('Le nom ne peut pas être vide.');
        }

        $collection->setName($name);
        $collection->setGenre($genre ?: null);
        $collection->setDescription($description ?: null);

        $coverImage = $coverImage !== null ? trim($coverImage) : null;

        if ($coverImage === '__none__') {
            $collection->setCoverImage(null);
        } elseif (is_string($coverImage) && $coverImage !== '') {
            $this->assertCoverBelongsToCollection($collection, $coverImage);
            $collection->setCoverImage($coverImage);
        }

        $this->em->flush();
    }

    /**
     * Je publie / dépublie une collection utilisateur.
     * Je bloque la publication si la collection est vide.
     */
    public function togglePublishUserCollection(User $user, int $collectionId): bool
    {
        /** @var Collection|null $collection */
        $collection = $this->em->getRepository(Collection::class)->find($collectionId);
        if (!$collection) {
            throw new \RuntimeException('Collection introuvable.');
        }

        if ($collection->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Accès interdit.');
        }

        if ($collection->getScope() === Collection::SCOPE_SYSTEM) {
            throw new \RuntimeException('Cette collection ne peut pas être publiée.');
        }

        $itemsCount = $collection->getCollectionBooks()->count() + $collection->getCollectionMovies()->count();

        if (!$collection->isPublished()) {
            if ($itemsCount === 0) {
                throw new \RuntimeException('Impossible de publier une collection vide.');
            }

            $collection->setIsPublished(true);
            $collection->setVisibility('public');
            $collection->setPublishedAt(new \DateTimeImmutable());

            $this->em->flush();
            return true;
        }

        $collection->setIsPublished(false);
        $collection->setVisibility('private');
        $collection->setPublishedAt(null);

        $this->em->flush();
        return false;
    }

    public function removeItemFromCollection(User $user, string $type, int $linkId): void
    {
        if (!in_array($type, ['book', 'movie'], true)) {
            throw new \InvalidArgumentException('Type de média invalide.');
        }

        if ($linkId <= 0) {
            throw new \InvalidArgumentException('Identifiant invalide.');
        }

        if ($type === 'book') {
            /** @var CollectionBook|null $link */
            $link = $this->em->getRepository(CollectionBook::class)->find($linkId);

            if (!$link instanceof CollectionBook) {
                throw new \RuntimeException('Élément introuvable.');
            }

            $collection = $link->getCollection();
            if (!$collection || $collection->getUser()?->getId() !== $user->getId()) {
                throw new \RuntimeException('Accès refusé.');
            }

            $this->em->remove($link);
            $this->em->flush();

            return;
        }

        /** @var CollectionMovie|null $link */
        $link = $this->em->getRepository(CollectionMovie::class)->find($linkId);

        if (!$link instanceof CollectionMovie) {
            throw new \RuntimeException('Élément introuvable.');
        }

        $collection = $link->getCollection();
        if (!$collection || $collection->getUser()?->getId() !== $user->getId()) {
            throw new \RuntimeException('Accès refusé.');
        }

        $this->em->remove($link);
        $this->em->flush();
    }
    

    public function deleteUserCollection(User $user, int $collectionId): void
    {
        /** @var Collection|null $collection */
        $collection = $this->em->getRepository(Collection::class)->find($collectionId);
        if (!$collection) {
            throw new \RuntimeException('Collection introuvable.');
        }

        if ($collection->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Accès interdit.');
        }

        if ($collection->getScope() === Collection::SCOPE_SYSTEM) {
            throw new \RuntimeException('Cette collection ne peut pas être supprimée.');
        }

        $this->em->remove($collection);
        $this->em->flush();
    }

    /**
     * Je déplace un item depuis "Non répertorié" vers une collection cible.
     * Je déplace le PIVOT (CollectionBook/CollectionMovie).
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
            throw new AccessDeniedException('Accès interdit à cette collection.');
        }

        if ($target->getScope() === Collection::SCOPE_SYSTEM) {
            throw new \RuntimeException('Impossible de déplacer vers “Non répertorié”.');
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

        if ($link->getCollection()->getUser()?->getId() !== $user->getId()) {
            throw new AccessDeniedException('Accès interdit à cet élément.');
        }

        $targetMedia = $target->getMediaType();
        if ($targetMedia === '') {
            $targetMedia = Collection::MEDIA_ALL;
        }

        if ($targetMedia !== Collection::MEDIA_ALL) {
            if ($type === 'book' && $targetMedia !== Collection::MEDIA_BOOK) {
                throw new \RuntimeException('Cette collection est réservée aux films.');
            }
            if ($type === 'movie' && $targetMedia !== Collection::MEDIA_MOVIE) {
                throw new \RuntimeException('Cette collection est réservée aux livres.');
            }
        }

        $link->setCollection($target);
        $this->em->flush();

        $this->clearCoverIfNoLongerValid($link->getCollection());
    }

    /**
     * Je vérifie que la cover choisie correspond à un média présent dans la collection.
     * Si coverImage est null, je considère "Auto" et je ne bloque pas.
     */
    private function assertCoverBelongsToCollection(Collection $collection, ?string $coverImage): void
    {
        if ($coverImage === null) {
            return;
        }

        $coverImage = trim($coverImage);
        if ($coverImage === '') {
            return;
        }

        foreach ($collection->getCollectionBooks() as $link) {
            $book = $link->getBook();
            if ($book && $book->getCoverImage() === $coverImage) {
                return;
            }
        }

        foreach ($collection->getCollectionMovies() as $link) {
            $movie = $link->getMovie();
            if ($movie && $movie->getPoster() === $coverImage) {
                return;
            }
        }

        throw new \InvalidArgumentException('La couverture choisie ne correspond à aucun média de la collection.');
    }

    /**
     * Je supprime la cover enregistrée si elle ne correspond plus à un média de la collection.
     * Je fais ça après un retrait/déplacement pour éviter une cover cassée.
     */
    private function clearCoverIfNoLongerValid(Collection $collection): void
    {
        $current = $collection->getCoverImage();
        if ($current === null || trim($current) === '') {
            return;
        }

        try {
            $this->assertCoverBelongsToCollection($collection, $current);
        } catch (\InvalidArgumentException) {
            $collection->setCoverImage(null);
            $this->em->flush();
        }
    }

    public function updateUserEmail(User $user, string $email): void
    {
        $email = trim($email);

        if ($email === '') {
            throw new \InvalidArgumentException('Veuillez saisir un email.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Email invalide.');
        }

        if (mb_strtolower($email) === mb_strtolower((string) $user->getEmail())) {
            return;
        }

        $existing = $this->userRepository->findOneBy(['email' => $email]);
        if ($existing && $existing->getId() !== $user->getId()) {
            throw new \InvalidArgumentException('Cet email est déjà utilisé.');
        }

        $user->setEmail($email);
        $this->em->flush();
    }

}
