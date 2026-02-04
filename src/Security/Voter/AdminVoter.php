<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class AdminVoter extends Voter
{
    public const ACCESS = 'ADMIN_ACCESS';
    public const COMMENTS_MODERATE = 'ADMIN_COMMENTS_MODERATE';
    public const COLLECTIONS_MANAGE = 'ADMIN_COLLECTIONS_MANAGE';
    public const USERS_MANAGE = 'ADMIN_USERS_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::ACCESS,
            self::COMMENTS_MODERATE,
            self::COLLECTIONS_MANAGE,
            self::USERS_MANAGE,
        ], true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
