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

    public function findForUser(\App\Entity\User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')          // ⚠️ adapte si ton champ = owner/createdBy
            ->setParameter('user', $user)
            ->orderBy('c.id', 'DESC')             // safe si pas de createdAt
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

    /**
     * Je retourne une pagination simple de collections publiques publiées.
     *
     * @return array{items: Collection[], total: int}
     */
    public function findPublicPublishedPaginated(string $mediaType, string $sort, int $page, int $limit): array
    {
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




//    /**
//     * @return Collection[] Returns an array of Collection objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Collection
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
