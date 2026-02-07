<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Je centralise ici les décisions d’accès liées aux actions administrateur.
 * J’utilise un Voter Symfony afin de séparer clairement la logique de sécurité
 * du code métier et des contrôleurs.
 */
final class AdminVoter extends Voter
{
    /**
     * Je définis les permissions administrateur utilisées dans l’application.
     * Ces constantes me permettent d’éviter les chaînes en dur dans les contrôleurs.
     */
    public const ACCESS = 'ADMIN_ACCESS';
    public const COMMENTS_MODERATE = 'ADMIN_COMMENTS_MODERATE';
    public const COLLECTIONS_MANAGE = 'ADMIN_COLLECTIONS_MANAGE';

    /**
     * Je précise ici les attributs que ce Voter est capable de gérer.
     * Si l’attribut ne fait pas partie de cette liste, Symfony ignore ce Voter.
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            self::ACCESS,
            self::COMMENTS_MODERATE,
            self::COLLECTIONS_MANAGE,
        ], true);
    }

    /**
     * Je décide si l’utilisateur courant est autorisé ou non.
     * Je vérifie que l’utilisateur est bien authentifié et qu’il possède
     * le rôle administrateur.
     */
    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
