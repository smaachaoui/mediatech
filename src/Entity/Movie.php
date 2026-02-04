<?php

namespace App\Entity;

use App\Repository\MovieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $title = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: "L'URL du poster ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Url(message: "L'URL du poster n'est pas valide.")]
    private ?string $poster = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le réalisateur ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $director = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: "Le genre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $genre = null;

    /*
     * Je garde la date de sortie optionnelle, parce que l'API peut ne pas la fournir.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $releaseDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: "Le synopsis ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $synopsis = null;

    #[ORM\Column(nullable: true, unique: true)]
    #[Assert\Positive(message: "L'identifiant TMDB doit être positif.")]
    private ?int $tmdbId = null;

    /*
     * Je stocke la date d'ajout en base pour trier les derniers films.
     */
    #[ORM\Column]
    #[Assert\NotNull]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'movie', targetEntity: CollectionMovie::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private DoctrineCollection $collectionMovies;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->collectionMovies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);

        return $this;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    public function setPoster(?string $poster): static
    {
        $poster = $poster !== null ? trim($poster) : null;
        $this->poster = $poster !== '' ? $poster : null;

        return $this;
    }

    public function getDirector(): ?string
    {
        return $this->director;
    }

    public function setDirector(?string $director): static
    {
        $director = $director !== null ? trim($director) : null;
        $this->director = $director !== '' ? $director : null;

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

    public function getReleaseDate(): ?\DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeImmutable $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getSynopsis(): ?string
    {
        return $this->synopsis;
    }

    public function setSynopsis(?string $synopsis): static
    {
        $synopsis = $synopsis !== null ? trim($synopsis) : null;
        $this->synopsis = $synopsis !== '' ? $synopsis : null;

        return $this;
    }

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(?int $tmdbId): static
    {
        $this->tmdbId = $tmdbId;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /*
     * Je laisse ce setter disponible, mais je ne le change pas en pratique.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DoctrineCollection<int, CollectionMovie>
     */
    public function getCollectionMovies(): DoctrineCollection
    {
        return $this->collectionMovies;
    }

    public function addCollectionMovie(CollectionMovie $collectionMovie): static
    {
        if (!$this->collectionMovies->contains($collectionMovie)) {
            $this->collectionMovies->add($collectionMovie);
            $collectionMovie->setMovie($this);
        }

        return $this;
    }

    public function removeCollectionMovie(CollectionMovie $collectionMovie): static
    {
        if ($this->collectionMovies->removeElement($collectionMovie)) {
            // Je ne mets pas movie à null, je laisse Doctrine supprimer la ligne pivot.
        }

        return $this;
    }
}
