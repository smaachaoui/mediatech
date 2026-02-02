<?php

namespace App\Repository;

use App\Entity\CollectionBook;
use App\Entity\Collection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<CollectionBook>
 *
 * @method CollectionBook|null find($id, $lockMode = null, $lockVersion = null)
 * @method CollectionBook|null findOneBy(array $criteria, array $orderBy = null)
 * @method CollectionBook[]    findAll()
 * @method CollectionBook[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionBookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionBook::class);
    }


    public function findLinksByCollection(Collection $collection): array
    {
        return $this->createQueryBuilder('cb')
            ->addSelect('b')
            ->innerJoin('cb.book', 'b')
            ->andWhere('cb.collection = :collection')
            ->setParameter('collection', $collection)
            ->orderBy('cb.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }


//    /**
//     * @return CollectionBook[] Returns an array of CollectionBook objects
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

//    public function findOneBySomeField($value): ?CollectionBook
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
