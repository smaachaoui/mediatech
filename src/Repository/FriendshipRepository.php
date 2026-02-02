<?php

namespace App\Repository;

use App\Entity\Friendship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Friendship>
 *
 * @method Friendship|null find($id, $lockMode = null, $lockVersion = null)
 * @method Friendship|null findOneBy(array $criteria, array $orderBy = null)
 * @method Friendship[]    findAll()
 * @method Friendship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    public function findAcceptedFriends(\App\Entity\User $user): array
{
    return $this->createQueryBuilder('f')
        ->andWhere('(f.requester = :user OR f.addressee = :user)') // ⚠️ adapte si sender/receiver
        ->andWhere('f.status = :status')                           // ⚠️ adapte si enum/int
        ->setParameter('user', $user)
        ->setParameter('status', 'accepted')
        ->orderBy('f.id', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findPendingRequests(\App\Entity\User $user): array
{
    return $this->createQueryBuilder('f')
        ->andWhere('f.addressee = :user')
        ->andWhere('f.status = :status')
        ->setParameter('user', $user)
        ->setParameter('status', 'pending')
        ->orderBy('f.id', 'DESC')
        ->getQuery()
        ->getResult();
}


//    /**
//     * @return Friendship[] Returns an array of Friendship objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('f.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Friendship
//    {
//        return $this->createQueryBuilder('f')
//            ->andWhere('f.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
