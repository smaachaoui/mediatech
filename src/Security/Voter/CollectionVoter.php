<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Collection;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class CollectionVoter extends Voter
{
    public const VIEW = 'COLLECTION_VIEW';
    public const EDIT = 'COLLECTION_EDIT';
    public const DELETE = 'COLLECTION_DELETE';
    public const MANAGE_ITEMS = 'COLLECTION_MANAGE_ITEMS';
    public const PUBLISH = 'COLLECTION_PUBLISH';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Collection) {
            return false;
        }

        return in_array($attribute, [
            self::VIEW,
            self::EDIT,
            self::DELETE,
            self::MANAGE_ITEMS,
            self::PUBLISH,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Collection $collection */
        $collection = $subject;

        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        if ($collection->getUser()?->getId() !== $user->getId()) {
            return false;
        }

        if ($attribute === self::PUBLISH && $collection->isSystem()) {
            return false;
        }

        if (in_array($attribute, [self::EDIT, self::DELETE, self::MANAGE_ITEMS], true) && $collection->isSystem()) {
            return false;
        }

        return true;
    }
}
