<?php

namespace App\Entity;

use App\Repository\BlockedUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlockedUserRepository::class)]
#[ORM\Table(name: 'blocked_user')]
#[ORM\UniqueConstraint(name: 'uniq_blocked_user_pair', columns: ['blocker_id', 'blocked_id'])]
class BlockedUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'blockedUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $blocker = null;

    #[ORM\ManyToOne(inversedBy: 'blockedByUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $blocked = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /*
     * Je garde ce setter, mais createdAt est normalement fixé à la création.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getBlocker(): ?User
    {
        return $this->blocker;
    }

    public function setBlocker(User $blocker): static
    {
        $this->blocker = $blocker;

        return $this;
    }

    public function getBlocked(): ?User
    {
        return $this->blocked;
    }

    public function setBlocked(User $blocked): static
    {
        $this->blocked = $blocked;

        return $this;
    }
}
