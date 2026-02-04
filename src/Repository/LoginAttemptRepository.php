<?php

namespace App\Repository;

use App\Entity\LoginAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginAttempt>
 *
 * @method LoginAttempt|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoginAttempt|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoginAttempt[]    findAll()
 * @method LoginAttempt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoginAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginAttempt::class);
    }

    /**
     * Je compte les tentatives echouees pour un email sur une periode donnee.
     */
    public function countRecentFailedAttemptsByEmail(string $email, int $minutes = 15): int
    {
        $since = new \DateTimeImmutable(sprintf('-%d minutes', $minutes));

        return (int) $this->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->andWhere('la.email = :email')
            ->andWhere('la.success = :success')
            ->andWhere('la.attemptedAt >= :since')
            ->setParameter('email', mb_strtolower($email))
            ->setParameter('success', false)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Je compte les tentatives echouees pour une adresse IP sur une periode donnee.
     */
    public function countRecentFailedAttemptsByIp(string $ipAddress, int $minutes = 15): int
    {
        $since = new \DateTimeImmutable(sprintf('-%d minutes', $minutes));

        return (int) $this->createQueryBuilder('la')
            ->select('COUNT(la.id)')
            ->andWhere('la.ipAddress = :ip')
            ->andWhere('la.success = :success')
            ->andWhere('la.attemptedAt >= :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('success', false)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * J'enregistre une nouvelle tentative de connexion.
     */
    public function recordAttempt(string $email, string $ipAddress, bool $success): LoginAttempt
    {
        $attempt = new LoginAttempt();
        $attempt->setEmail(mb_strtolower($email));
        $attempt->setIpAddress($ipAddress);
        $attempt->setSuccess($success);

        $em = $this->getEntityManager();
        $em->persist($attempt);
        $em->flush();

        return $attempt;
    }

    /**
     * Je supprime les anciennes tentatives pour nettoyer la base.
     * J'appelle cette methode periodiquement via une commande ou un cron.
     */
    public function purgeOldAttempts(int $days = 7): int
    {
        $threshold = new \DateTimeImmutable(sprintf('-%d days', $days));

        return (int) $this->createQueryBuilder('la')
            ->delete()
            ->andWhere('la.attemptedAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }
}