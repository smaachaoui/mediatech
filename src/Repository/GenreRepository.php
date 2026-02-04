<?php

namespace App\Repository;

use App\Entity\Genre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Genre>
 *
 * @method Genre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Genre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Genre[]    findAll()
 * @method Genre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GenreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Genre::class);
    }

    /**
     * Je retourne tous les genres tries par type puis par nom.
     *
     * @return Genre[]
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.type', 'ASC')
            ->addOrderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Je retourne les genres de livres.
     *
     * @return Genre[]
     */
    public function findBookGenres(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.type = :type')
            ->setParameter('type', 'book')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Je retourne les genres de films.
     *
     * @return Genre[]
     */
    public function findMovieGenres(): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.type = :type')
            ->setParameter('type', 'movie')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}