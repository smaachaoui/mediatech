<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Collection;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Je centralise ici les règles d'autorisation liées aux collections.
 * J'ai choisi d'utiliser un Voter afin d'isoler la logique de sécurité
 * et d'éviter de la disperser dans les contrôleurs.
 */
final class CollectionVoter extends Voter
{
    public const VIEW = 'COLLECTION_VIEW';
    public const EDIT = 'COLLECTION_EDIT';
    public const DELETE = 'COLLECTION_DELETE';
    public const MANAGE_ITEMS = 'COLLECTION_MANAGE_ITEMS';
    public const PUBLISH = 'COLLECTION_PUBLISH';

    /**
     * Je vérifie si le voter doit s'appliquer à la requête courante.
     * Je ne traite que les objets de type Collection et les attributs définis ici.
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof Collection) {
            return false;
        }

        return in_array(
            $attribute,
            [
                self::VIEW,
                self::EDIT,
                self::DELETE,
                self::MANAGE_ITEMS,
                self::PUBLISH,
            ],
            true
        );
    }

    /**
     * Je décide ici si l'utilisateur a le droit d'effectuer l'action demandée.
     * J'applique une logique simple :
     * - Un administrateur peut tout faire.
     * - Un utilisateur ne peut agir que sur ses propres collections.
     * - Les collections système ont des restrictions supplémentaires.
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Collection $collection */
        $collection = $subject;

        /*
         * Je donne tous les droits aux administrateurs.
         * Cela simplifie la gestion des cas spécifiques.
         */
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /*
         * Je vérifie que l'utilisateur est bien le propriétaire de la collection.
         * Un utilisateur ne peut pas modifier ou gérer une collection qui ne lui appartient pas.
         */
        if ($collection->getUser()?->getId() !== $user->getId()) {
            return false;
        }

        /*
         * Je bloque la publication des collections système.
         * Elles sont techniques et ne doivent pas être visibles publiquement.
         */
        if ($attribute === self::PUBLISH && $collection->isSystem()) {
            return false;
        }

        /*
         * Je bloque l'édition, la suppression et la gestion des items
         * pour les collections système afin de préserver leur intégrité.
         */
        if (
            in_array(
                $attribute,
                [
                    self::EDIT,
                    self::DELETE,
                    self::MANAGE_ITEMS,
                ],
                true
            )
            && $collection->isSystem()
        ) {
            return false;
        }

        /*
         * Si aucune règle bloquante n'a été rencontrée,
         * j'autorise l'action demandée.
         */
        return true;
    }
}
