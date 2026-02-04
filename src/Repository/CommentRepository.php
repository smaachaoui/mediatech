<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Je retourne les derniers commentaires d'un utilisateur.
     *
     * @return Comment[]
     */
    public function findLatestByAuthor(User $user, int $limit = 30): array
    {
        return $this->createQueryBuilder('com')
            ->andWhere('com.user = :user')
            ->setParameter('user', $user)
            ->orderBy('com.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Je retourne les derniers commentaires d'une collection.
     *
     * @return Comment[]
     */
    public function findLatestByCollection(Collection $collection, int $limit = 20): array
    {
        return $this->createQueryBuilder('com')
            ->andWhere('com.collection = :collection')
            ->setParameter('collection', $collection)
            ->orderBy('com.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Je compte le nombre total de commentaires.
     */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('com')
            ->select('COUNT(com.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Je compte les commentaires publies cette semaine.
     */
    public function countThisWeek(): int
    {
        $startOfWeek = new \DateTimeImmutable('monday this week midnight');

        return (int) $this->createQueryBuilder('com')
            ->select('COUNT(com.id)')
            ->andWhere('com.createdAt >= :start')
            ->setParameter('start', $startOfWeek)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Je retourne les derniers commentaires pour la moderation admin.
     *
     * @return Comment[]
     */
    public function findLatestForModeration(int $limit = 50): array
    {
        return $this->createQueryBuilder('com')
            ->leftJoin('com.user', 'u')
            ->leftJoin('com.collection', 'c')
            ->addSelect('u', 'c')
            ->orderBy('com.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Je retourne une pagination des commentaires pour l'admin.
     *
     * @return array{items: Comment[], total: int}
     */
    public function findPaginatedForAdmin(int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('com')
            ->leftJoin('com.user', 'u')
            ->leftJoin('com.collection', 'c')
            ->addSelect('u', 'c')
            ->orderBy('com.createdAt', 'DESC')
            ->addOrderBy('com.id', 'DESC');

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select('COUNT(com.id)')
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
}