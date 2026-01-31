<?php

namespace App\Entity;

use App\Repository\LoginAttemptRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoginAttemptRepository::class)]
#[ORM\Table(name: 'login_attempt')]
class LoginAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
     * Je stocke l'email utilisé lors de la tentative, même si le compte n'existe pas.
     */
    #[ORM\Column(length: 180)]
    private string $email;

    /*
     * Je stocke l'adresse IP pour détecter les tentatives abusives.
     */
    #[ORM\Column(length: 45)]
    private string $ipAddress;

    /*
     * Je stocke si la tentative a réussi ou non.
     */
    #[ORM\Column]
    private bool $success;

    /*
     * Je stocke la date de la tentative.
     */
    #[ORM\Column]
    private \DateTimeImmutable $attemptedAt;

    public function __construct()
    {
        $this->attemptedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function getAttemptedAt(): \DateTimeImmutable
    {
        return $this->attemptedAt;
    }

    /*
     * Je garde ce setter, mais attemptedAt est normalement fixé à la création.
     */
    public function setAttemptedAt(\DateTimeImmutable $attemptedAt): static
    {
        $this->attemptedAt = $attemptedAt;

        return $this;
    }
}
