<?php

namespace App\Entity;

use App\Repository\FriendshipRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FriendshipRepository::class)]
#[ORM\Table(name: 'friendship')]
#[ORM\UniqueConstraint(name: 'uniq_friendship_request', columns: ['requester_id', 'addressee_id'])]
class Friendship
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
     * Je stocke l'état de la demande : pending, accepted ou refused.
     */
    #[ORM\Column(length: 10)]
    private string $status = 'pending';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'friendshipsRequested')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requester = null;

    #[ORM\ManyToOne(inversedBy: 'friendshipsReceived')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $addressee = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'pending';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getRequester(): ?User
    {
        return $this->requester;
    }

    public function setRequester(User $requester): static
    {
        $this->requester = $requester;

        return $this;
    }

    public function getAddressee(): ?User
    {
        return $this->addressee;
    }

    public function setAddressee(User $addressee): static
    {
        $this->addressee = $addressee;

        return $this;
    }
}
