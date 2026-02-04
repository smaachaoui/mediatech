<?php

namespace App\Service;

use App\Repository\LoginAttemptRepository;

/**
 * Je gere la logique de protection contre les attaques par force brute.
 */
final class LoginAttemptService
{
    /*
     * Je definis le nombre maximum de tentatives echouees autorisees.
     */
    private const MAX_ATTEMPTS_BY_EMAIL = 5;
    private const MAX_ATTEMPTS_BY_IP = 15;

    /*
     * Je definis la fenetre de temps en minutes pour compter les tentatives.
     */
    private const TIME_WINDOW_MINUTES = 15;

    public function __construct(
        private readonly LoginAttemptRepository $loginAttemptRepository
    ) {}

    /**
     * Je verifie si l'email est bloque suite a trop de tentatives echouees.
     */
    public function isEmailBlocked(string $email): bool
    {
        $failedAttempts = $this->loginAttemptRepository->countRecentFailedAttemptsByEmail(
            $email,
            self::TIME_WINDOW_MINUTES
        );

        return $failedAttempts >= self::MAX_ATTEMPTS_BY_EMAIL;
    }

    /**
     * Je verifie si l'adresse IP est bloquee suite a trop de tentatives echouees.
     */
    public function isIpBlocked(string $ipAddress): bool
    {
        $failedAttempts = $this->loginAttemptRepository->countRecentFailedAttemptsByIp(
            $ipAddress,
            self::TIME_WINDOW_MINUTES
        );

        return $failedAttempts >= self::MAX_ATTEMPTS_BY_IP;
    }

    /**
     * Je verifie si la tentative de connexion est autorisee.
     * Je retourne true si autorisee, false si bloquee.
     */
    public function isLoginAllowed(string $email, string $ipAddress): bool
    {
        if ($this->isIpBlocked($ipAddress)) {
            return false;
        }

        if ($this->isEmailBlocked($email)) {
            return false;
        }

        return true;
    }

    /**
     * J'enregistre une tentative de connexion echouee.
     */
    public function recordFailedAttempt(string $email, string $ipAddress): void
    {
        $this->loginAttemptRepository->recordAttempt($email, $ipAddress, false);
    }

    /**
     * J'enregistre une tentative de connexion reussie.
     */
    public function recordSuccessfulAttempt(string $email, string $ipAddress): void
    {
        $this->loginAttemptRepository->recordAttempt($email, $ipAddress, true);
    }

    /**
     * Je retourne le nombre de tentatives restantes pour un email.
     */
    public function getRemainingAttemptsForEmail(string $email): int
    {
        $failedAttempts = $this->loginAttemptRepository->countRecentFailedAttemptsByEmail(
            $email,
            self::TIME_WINDOW_MINUTES
        );

        return max(0, self::MAX_ATTEMPTS_BY_EMAIL - $failedAttempts);
    }

    /**
     * Je retourne la fenetre de temps en minutes.
     */
    public function getTimeWindowMinutes(): int
    {
        return self::TIME_WINDOW_MINUTES;
    }
}