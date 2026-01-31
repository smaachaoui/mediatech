<?php

namespace App\Repository;

use App\Entity\CollectionMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollectionMovie>
 *
 * @method CollectionMovie|null find($id, $lockMode = null, $lockVersion = null)
 * @method CollectionMovie|null findOneBy(array $criteria, array $orderBy = null)
 * @method CollectionMovie[]    findAll()
 * @method CollectionMovie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CollectionMovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionMovie::class);
    }

//    /**
//     * @return CollectionMovie[] Returns an array of CollectionMovie objects
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

//    public function findOneBySomeField($value): ?CollectionMovie
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
