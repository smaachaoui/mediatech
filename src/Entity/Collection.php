<?php

namespace App\Entity;

use App\Repository\CollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CollectionRepository::class)]
#[ORM\Table(name: 'collection', uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_collection_user_name', columns: ['user_id', 'name'])])]
#[UniqueEntity(fields: ['user', 'name'], message: 'Vous avez déjà une collection avec ce nom.')]
#[Assert\Callback('validatePublishing')]
class Collection
{
    public const SCOPE_SYSTEM = 'system';
    public const SCOPE_USER = 'user';

    public const MEDIA_ALL = 'all';
    public const MEDIA_BOOK = 'book';
    public const MEDIA_MOVIE = 'movie';

    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_PUBLIC = 'public';

    public const SCOPES = [self::SCOPE_SYSTEM, self::SCOPE_USER];
    public const MEDIA_TYPES = [self::MEDIA_ALL, self::MEDIA_BOOK, self::MEDIA_MOVIE];
    public const VISIBILITIES = [self::VISIBILITY_PRIVATE, self::VISIBILITY_PUBLIC];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Le nom de la collection est obligatoire.")]
    #[Assert\Length(
        max: 150,
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Url(message: "L'URL de couverture n'est pas valide.")]
    #[Assert\Length(
        max: 2048,
        maxMessage: "L'URL de couverture ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $coverImage = null;

    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(
        max: 60,
        maxMessage: "Le genre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $genre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    // ex: type -> scope
    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: self::SCOPES, message: "Le scope de la collection n'est pas valide.")]
    private string $scope = self::SCOPE_USER;

    // NEW: all|book|movie
    #[ORM\Column(name: 'media_type', length: 10)]
    #[Assert\Choice(choices: self::MEDIA_TYPES, message: "Le type de média n'est pas valide.")]
    private string $mediaType = self::MEDIA_ALL;

    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: self::VISIBILITIES, message: "La visibilité n'est pas valide.")]
    private string $visibility = self::VISIBILITY_PRIVATE;

    #[ORM\Column(options: ['default' => false])]
    #[Assert\NotNull]
    private bool $isPublished = false;

    #[ORM\Column]
    #[Assert\NotNull]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'utilisateur propriétaire est obligatoire.")]
    private ?User $user = null;

    #[ORM\OneToMany(mappedBy: 'collection', targetEntity: CollectionBook::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private DoctrineCollection $collectionBooks;

    #[ORM\OneToMany(mappedBy: 'collection', targetEntity: CollectionMovie::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private DoctrineCollection $collectionMovies;

    #[ORM\OneToMany(mappedBy: 'collection', targetEntity: Rating::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private DoctrineCollection $ratings;

    #[ORM\OneToMany(mappedBy: 'collection', targetEntity: Comment::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private DoctrineCollection $comments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->collectionBooks = new ArrayCollection();
        $this->collectionMovies = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage !== null ? trim($coverImage) : null;
        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $genre = $genre !== null ? trim($genre) : null;
        $this->genre = $genre !== '' ? $genre : null;
        return $this;
    }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): static
    {
        if (!in_array($scope, self::SCOPES, true)) {
            throw new \InvalidArgumentException('Invalid scope.');
        }
        $this->scope = $scope;
        return $this;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): static
    {
        if (!in_array($mediaType, self::MEDIA_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid media type.');
        }
        $this->mediaType = $mediaType;
        return $this;
    }

    public function isSystem(): bool
    {
        return $this->getScope() === self::SCOPE_SYSTEM;
    }


    /**
     * Compat temporaire (facultatif mais conseillé pendant refacto)
     * => évite les crashes si du code appelle encore getType/setType
     */
    public function getType(): string
    {
        return $this->getScope();
    }
    public function setType(string $type): static
    {
        return $this->setScope($type);
    }

    public function getVisibility(): string { return $this->visibility; }
    public function setVisibility(string $visibility): static { $this->visibility = $visibility; return $this; }

    public function isPublished(): bool { return $this->isPublished; }
    public function setIsPublished(bool $isPublished): static { $this->isPublished = $isPublished; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static { $this->publishedAt = $publishedAt; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getCollectionBooks(): DoctrineCollection { return $this->collectionBooks; }
    public function getCollectionMovies(): DoctrineCollection { return $this->collectionMovies; }
    public function getRatings(): DoctrineCollection { return $this->ratings; }
    public function getComments(): DoctrineCollection { return $this->comments; }

    public function validatePublishing(ExecutionContextInterface $context): void
    {
        if ($this->isPublished && $this->publishedAt === null) {
            $context->buildViolation("La date de publication est obligatoire lorsque la collection est publiée.")
                ->atPath('publishedAt')
                ->addViolation();
        }

        if (!$this->isPublished && $this->publishedAt !== null) {
            $context->buildViolation("La date de publication doit être vide lorsque la collection n'est pas publiée.")
                ->atPath('publishedAt')
                ->addViolation();
        }

        if ($this->visibility === self::VISIBILITY_PUBLIC && !$this->isPublished) {
            $context->buildViolation("Une collection publique doit être publiée.")
                ->atPath('visibility')
                ->addViolation();
        }
    }
}
