<?php

namespace App\Entity;

use App\Repository\CollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Je représente une collection de médias (livres, films ou mixte) appartenant à un utilisateur.
 * J'ai intégré ici les règles métier principales autour de la publication et de la visibilité.
 */
#[ORM\Entity(repositoryClass: CollectionRepository::class)]
#[ORM\Table(
    name: 'collection',
    uniqueConstraints: [
        new ORM\UniqueConstraint(
            name: 'uniq_collection_user_name',
            columns: ['user_id', 'name']
        ),
    ]
)]
#[UniqueEntity(fields: ['user', 'name'], message: 'Vous avez déjà une collection avec ce nom.')]
#[Assert\Callback('validatePublishing')]
class Collection
{
    /**
     * Je l'utilise pour distinguer une collection système (créée automatiquement) d'une collection utilisateur.
     */
    public const SCOPE_SYSTEM = 'system';
    public const SCOPE_USER = 'user';

    /**
     * Je l'utilise pour limiter le type de médias autorisés dans la collection.
     */
    public const MEDIA_ALL = 'all';
    public const MEDIA_BOOK = 'book';
    public const MEDIA_MOVIE = 'movie';

    /**
     * Je définis ici les niveaux de visibilité exposés sur le front.
     */
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_PUBLIC = 'public';

    /**
     * Je centralise ces listes pour les validations (Assert\Choice) et pour les formulaires.
     */
    public const SCOPES = [self::SCOPE_SYSTEM, self::SCOPE_USER];
    public const MEDIA_TYPES = [self::MEDIA_ALL, self::MEDIA_BOOK, self::MEDIA_MOVIE];
    public const VISIBILITIES = [self::VISIBILITY_PRIVATE, self::VISIBILITY_PUBLIC];

    /**
     * Je garde un identifiant technique auto-incrémenté.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Je stocke le nom de la collection.
     * J'ai limité la longueur pour garantir une saisie cohérente et éviter des libellés trop longs en UI.
     */
    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: 'Le nom de la collection est obligatoire.')]
    #[Assert\Length(
        max: 150,
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $name = null;

    /**
     * Je stocke une URL de couverture (ex: image choisie par l'utilisateur).
     * J'ai conservé un champ texte pour accepter des URLs relativement longues.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Url(message: "L'URL de couverture n'est pas valide.")]
    #[Assert\Length(
        max: 2048,
        maxMessage: "L'URL de couverture ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $coverImage = null;

    /**
     * Je stocke le genre choisi lors de la création de la collection.
     * J'ai préféré un champ texte pour rester simple et flexible, tout en contrôlant la saisie via le formulaire.
     */
    #[ORM\Column(length: 60, nullable: true)]
    #[Assert\Length(
        max: 60,
        maxMessage: 'Le genre ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $genre = null;

    /**
     * Je stocke une description optionnelle pour contextualiser la collection.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    /**
     * Je définis le scope de la collection.
     * J'ai renommé l'ancien "type" en "scope" pour éviter la confusion avec le type de médias.
     */
    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: self::SCOPES, message: "Le scope de la collection n'est pas valide.")]
    private string $scope = self::SCOPE_USER;

    /**
     * Je définis quel type de médias la collection accepte.
     * J'ai choisi une valeur courte (all|book|movie) pour faciliter les contrôles et les filtres.
     */
    #[ORM\Column(name: 'media_type', length: 10)]
    #[Assert\Choice(choices: self::MEDIA_TYPES, message: "Le type de média n'est pas valide.")]
    private string $mediaType = self::MEDIA_ALL;

    /**
     * Je définis si la collection est privée ou visible publiquement.
     * La règle métier de cohérence avec "isPublished" est validée dans validatePublishing().
     */
    #[ORM\Column(length: 10)]
    #[Assert\Choice(choices: self::VISIBILITIES, message: "La visibilité n'est pas valide.")]
    private string $visibility = self::VISIBILITY_PRIVATE;

    /**
     * Je marque si la collection est publiée.
     * Je m'en sers pour contrôler l'accès public et l'affichage dans les pages communautaires.
     */
    #[ORM\Column(options: ['default' => false])]
    #[Assert\NotNull]
    private bool $isPublished = false;

    /**
     * Je stocke la date de création pour trier et historiser la collection.
     */
    #[ORM\Column]
    #[Assert\NotNull]
    private \DateTimeImmutable $createdAt;

    /**
     * Je stocke la date de publication quand la collection est rendue publique.
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    /**
     * Je référence le propriétaire de la collection.
     * J'ai rendu ce lien obligatoire pour garantir l'intégrité des données.
     */
    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'utilisateur propriétaire est obligatoire.")]
    private ?User $user = null;

    /**
     * Je stocke les livres présents dans la collection via une table de liaison.
     * J'ai choisi orphanRemoval pour supprimer proprement les liaisons quand la collection est supprimée.
     *
     * @var DoctrineCollection<int, CollectionBook>
     */
    #[ORM\OneToMany(
        mappedBy: 'collection',
        targetEntity: CollectionBook::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    private DoctrineCollection $collectionBooks;

    /**
     * Je stocke les films présents dans la collection via une table de liaison.
     *
     * @var DoctrineCollection<int, CollectionMovie>
     */
    #[ORM\OneToMany(
        mappedBy: 'collection',
        targetEntity: CollectionMovie::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    private DoctrineCollection $collectionMovies;

    /**
     * Je stocke les notes attribuées à la collection.
     *
     * @var DoctrineCollection<int, Rating>
     */
    #[ORM\OneToMany(
        mappedBy: 'collection',
        targetEntity: Rating::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    private DoctrineCollection $ratings;

    /**
     * Je stocke les commentaires associés à la collection.
     *
     * @var DoctrineCollection<int, Comment>
     */
    #[ORM\OneToMany(
        mappedBy: 'collection',
        targetEntity: Comment::class,
        orphanRemoval: true,
        cascade: ['persist', 'remove']
    )]
    private DoctrineCollection $comments;

    /**
     * Je prépare les collections Doctrine et j'initialise la date de création.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->collectionBooks = new ArrayCollection();
        $this->collectionMovies = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Je valide le scope côté domaine pour éviter d'introduire une valeur incohérente.
     *
     * @throws \InvalidArgumentException Si le scope fourni n'est pas supporté.
     */
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

    /**
     * Je valide le type de médias côté domaine pour garantir la cohérence des données.
     *
     * @throws \InvalidArgumentException Si le type fourni n'est pas supporté.
     */
    public function setMediaType(string $mediaType): static
    {
        if (!in_array($mediaType, self::MEDIA_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid media type.');
        }

        $this->mediaType = $mediaType;

        return $this;
    }

    /**
     * Je fournis un raccourci pour savoir si la collection est une collection système.
     */
    public function isSystem(): bool
    {
        return $this->getScope() === self::SCOPE_SYSTEM;
    }

    /**
     * Je garde une compatibilité temporaire pendant la refactorisation.
     * J'ai conservé getType()/setType() pour éviter des erreurs si une ancienne partie du code les appelle encore.
     */
    public function getType(): string
    {
        return $this->getScope();
    }

    public function setType(string $type): static
    {
        return $this->setScope($type);
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    /**
     * Je laisse la validation à Assert\Choice.
     * J'évite ici de dupliquer la règle puisque Symfony validera déjà la valeur via le formulaire.
     */
    public function setVisibility(string $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return DoctrineCollection<int, CollectionBook>
     */
    public function getCollectionBooks(): DoctrineCollection
    {
        return $this->collectionBooks;
    }

    /**
     * @return DoctrineCollection<int, CollectionMovie>
     */
    public function getCollectionMovies(): DoctrineCollection
    {
        return $this->collectionMovies;
    }

    /**
     * @return DoctrineCollection<int, Rating>
     */
    public function getRatings(): DoctrineCollection
    {
        return $this->ratings;
    }

    /**
     * @return DoctrineCollection<int, Comment>
     */
    public function getComments(): DoctrineCollection
    {
        return $this->comments;
    }

    /**
     * Je valide la cohérence entre visibilité et publication.
     * J'ai choisi une validation métier centralisée pour éviter des états incohérents en base.
     */
    public function validatePublishing(ExecutionContextInterface $context): void
    {
        if ($this->isPublished && $this->publishedAt === null) {
            $context->buildViolation('La date de publication est obligatoire lorsque la collection est publiée.')
                ->atPath('publishedAt')
                ->addViolation();
        }

        if (!$this->isPublished && $this->publishedAt !== null) {
            $context->buildViolation("La date de publication doit être vide lorsque la collection n'est pas publiée.")
                ->atPath('publishedAt')
                ->addViolation();
        }

        if ($this->visibility === self::VISIBILITY_PUBLIC && !$this->isPublished) {
            $context->buildViolation('Une collection publique doit être publiée.')
                ->atPath('visibility')
                ->addViolation();
        }
    }
}
