<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\Comment;
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

    public function findLatestByAuthor(\App\Entity\User $user, int $limit = 30): array
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
}
