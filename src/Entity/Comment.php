<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comment')]
#[Assert\Callback('validateAuthor')]
class Comment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le contenu du commentaire est requis.")]
    #[Assert\Length(
        min: 3,
        max: 2000,
        minMessage: "Le commentaire doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le commentaire ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $content = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(
        max: 60,
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $guestName = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: "L'adresse email n'est pas valide.")]
    #[Assert\Length(
        max: 180,
        maxMessage: "L'email ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $guestEmail = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Assert\Ip(message: "L'adresse IP n'est pas valide.")]
    #[Assert\Length(
        max: 45,
        maxMessage: "L'adresse IP ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le user-agent ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $userAgent = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "La collection associée est obligatoire.")]
    private ?Collection $collection = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getGuestName(): ?string
    {
        return $this->guestName;
    }

    public function setGuestName(?string $guestName): static
    {
        $this->guestName = $guestName;

        return $this;
    }

    public function getGuestEmail(): ?string
    {
        return $this->guestEmail;
    }

    public function setGuestEmail(?string $guestEmail): static
    {
        $this->guestEmail = $guestEmail;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCollection(): ?Collection
    {
        return $this->collection;
    }

    public function setCollection(Collection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Je considère qu'un commentaire invité est valide si user est null
     * et que guestName + guestEmail sont renseignés.
     */
    public function isGuestComment(): bool
    {
        return $this->user === null
            && $this->guestName !== null && trim($this->guestName) !== ''
            && $this->guestEmail !== null && trim($this->guestEmail) !== '';
    }

    public function validateAuthor(ExecutionContextInterface $context): void
    {
        $hasUser = $this->user !== null;
        $hasGuestName = $this->guestName !== null && trim($this->guestName) !== '';
        $hasGuestEmail = $this->guestEmail !== null && trim($this->guestEmail) !== '';

        if ($hasUser) {
            return;
        }

        if (!$hasGuestName) {
            $context->buildViolation("Le nom est obligatoire pour un commentaire invité.")
                ->atPath('guestName')
                ->addViolation();
        }

        if (!$hasGuestEmail) {
            $context->buildViolation("L'email est obligatoire pour un commentaire invité.")
                ->atPath('guestEmail')
                ->addViolation();
        }
    }
}
