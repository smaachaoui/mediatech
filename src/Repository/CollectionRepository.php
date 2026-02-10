<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collection>
 *
 * @method Collection|null find($id, $lockMode = null, $lockVersion = null)
 * @method Collection|null findOneBy(array $criteria, array $orderBy = null)
 * @method Collection[]    findAll()
 * @method Collection[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collection::class);
    }

    /**
     * Je retourne une pagination simple de collections publiques publiées.
     * J'ai ajoute le filtrage par genre.
     *
     * @return array{items: Collection[], total: int}
     */
    public function findPublicPublishedPaginated(
        string $mediaType,
        string $sort,
        int $page,
        int $limit,
        string $genre = ''
    ): array {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.scope = :scope')
            ->andWhere('c.isPublished = :published')
            ->andWhere('c.visibility = :visibility')
            ->setParameter('scope', Collection::SCOPE_USER)
            ->setParameter('published', true)
            ->setParameter('visibility', 'public');

        if ($mediaType !== Collection::MEDIA_ALL) {
            $qb->andWhere('c.mediaType = :mediaType')
                ->setParameter('mediaType', $mediaType);
        }

        /*
         * Si un genre est specifie, je filtre les collections qui :
         * 1. Ont directement le genre dans leur champ "genre" (collection.genre)
         * 2. OU contiennent au moins un livre/film du genre demande (book.genre ou movie.genre)
         * 
         * Cette approche hybride permet de trouver les collections :
         * - Soit par leur genre declaré à la création
         * - Soit par le contenu effectif de médias qu'elles contiennent
         */
        if ($genre !== '') {
            $genreConditions = [];
            
            // Condition 1 : Le genre de la collection elle-même
            $genreConditions[] = 'LOWER(c.genre) = LOWER(:genre)';
            
            // Condition 2 : Les genres des médias contenus
            if ($mediaType === Collection::MEDIA_BOOK || $mediaType === Collection::MEDIA_ALL) {
                $qb->leftJoin('c.collectionBooks', 'cb')
                    ->leftJoin('cb.book', 'b');
                $genreConditions[] = 'LOWER(b.genre) = LOWER(:genre)';
            }

            if ($mediaType === Collection::MEDIA_MOVIE || $mediaType === Collection::MEDIA_ALL) {
                $qb->leftJoin('c.collectionMovies', 'cm')
                    ->leftJoin('cm.movie', 'm');
                $genreConditions[] = 'LOWER(m.genre) = LOWER(:genre)';
            }

            // Je combine toutes les conditions avec OR
            $qb->andWhere(implode(' OR ', $genreConditions))
                ->setParameter('genre', $genre);

            /*
             * J'ajoute DISTINCT pour eviter les doublons si une collection
             * contient plusieurs livres/films du meme genre.
             */
            $qb->distinct();
        }

        if ($sort === 'alpha') {
            $qb->orderBy('c.name', 'ASC');
        } elseif ($sort === 'old') {
            $qb->orderBy('c.publishedAt', 'ASC')
                ->addOrderBy('c.id', 'ASC');
        } else {
            $qb->orderBy('c.publishedAt', 'DESC')
                ->addOrderBy('c.id', 'DESC');
        }

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(DISTINCT c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
 * Je compte toutes les collections utilisateur (hors collections système).
 */
public function countUserCollections(): int
{
    return (int) $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->andWhere('c.scope = :scope')
        ->setParameter('scope', Collection::SCOPE_USER)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Je compte les collections utilisateur qui sont publiées.
 */
public function countPublished(): int
{
    return (int) $this->createQueryBuilder('c')
        ->select('COUNT(c.id)')
        ->andWhere('c.scope = :scope')
        ->andWhere('c.isPublished = :published')
        ->setParameter('scope', Collection::SCOPE_USER)
        ->setParameter('published', true)
        ->getQuery()
        ->getSingleScalarResult();
}

/**
 * Je retourne une pagination des collections utilisateur (admin).
 *
 * @return array{items: Collection[], total: int}
 */
public function findUserCollectionsPaginated(?bool $published, int $page, int $limit): array
{
    $qb = $this->createQueryBuilder('c')
        ->leftJoin('c.user', 'u')
        ->addSelect('u')
        ->andWhere('c.scope = :scope')
        ->setParameter('scope', Collection::SCOPE_USER);

    if ($published !== null) {
        $qb->andWhere('c.isPublished = :published')
            ->setParameter('published', $published);
    }

    $qb->orderBy('c.createdAt', 'DESC')
        ->addOrderBy('c.id', 'DESC');

    $countQb = clone $qb;
    $total = (int) $countQb
        ->select('COUNT(c.id)')
        ->getQuery()
        ->getSingleScalarResult();

    $items = $qb
        ->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();

    return [
        'items' => $items,
        'total' => $total,
    ];
}


    public function findForUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findForUserExcluding(User $user, int $excludedCollectionId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.id != :excluded')
            ->andWhere('c.scope = :scope')
            ->setParameter('user', $user)
            ->setParameter('excluded', $excludedCollectionId)
            ->setParameter('scope', Collection::SCOPE_USER)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}