<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\LibraryManager;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * J'ai cree cette extension pour verifier si un media est deja dans les collections de l'utilisateur.
 * Cela me permet d'afficher un etat different sur les boutons d'ajout.
 */
final class LibraryExtension extends AbstractExtension
{
    public function __construct(
        private readonly LibraryManager $libraryManager,
        private readonly Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_in_collection', [$this, 'isInCollection']),
            new TwigFunction('is_in_wishlist', [$this, 'isInWishlist']),
        ];
    }

    /**
     * Je verifie si un media est deja dans une collection de l'utilisateur connecte.
     */
    public function isInCollection(string $kind, string|int $externalId): bool
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($kind === 'book') {
            return $this->libraryManager->isBookInUserCollections($user, (string) $externalId);
        }

        if ($kind === 'movie') {
            return $this->libraryManager->isMovieInUserCollections($user, (int) $externalId);
        }

        return false;
    }

    /**
     * Je verifie si un media est deja dans la liste d'envie de l'utilisateur connecte.
     */
    public function isInWishlist(string $kind, string|int $externalId): bool
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($kind === 'book') {
            return $this->libraryManager->isBookInWishlist($user, (string) $externalId);
        }

        if ($kind === 'movie') {
            return $this->libraryManager->isMovieInWishlist($user, (int) $externalId);
        }

        return false;
    }
}