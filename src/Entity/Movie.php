<?php

namespace App\Entity;

use App\Repository\MovieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $poster = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $director = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $genre = null;

    /*
     * Je garde la date de sortie optionnelle, parce que l'API peut ne pas la fournir.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $releaseDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $synopsis = null;

    #[ORM\Column(nullable: true)]
    private ?int $tmdbId = null;

    /*
     * Je stocke la date d'ajout en base pour trier les derniers films.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'movie', targetEntity: CollectionMovie::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private DoctrineCollection $collectionMovies;

    #[ORM\OneToMany(mappedBy: 'movie', targetEntity: Wishlist::class)]
    private DoctrineCollection $wishlists;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->collectionMovies = new ArrayCollection();
        $this->wishlists = new ArrayCollection();
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
        $this->title = $title;

        return $this;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    public function setPoster(?string $poster): static
    {
        $this->poster = $poster;

        return $this;
    }

    public function getDirector(): ?string
    {
        return $this->director;
    }

    public function setDirector(?string $director): static
    {
        $this->director = $director;

        return $this;
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre): static
    {
        $this->genre = $genre;

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
        $this->synopsis = $synopsis;

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

    /**
     * @return DoctrineCollection<int, Wishlist>
     */
    public function getWishlists(): DoctrineCollection
    {
        return $this->wishlists;
    }

    public function addWishlist(Wishlist $wishlist): static
    {
        if (!$this->wishlists->contains($wishlist)) {
            $this->wishlists->add($wishlist);
            $wishlist->setMovie($this);
        }

        return $this;
    }

    public function removeWishlist(Wishlist $wishlist): static
    {
        if ($this->wishlists->removeElement($wishlist)) {
            // set the owning side to null (unless already changed)
            if ($wishlist->getMovie() === $this) {
                $wishlist->setMovie(null);
            }
        }

        return $this;
    }
}
