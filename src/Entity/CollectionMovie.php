<?php

namespace App\Entity;

use App\Repository\CollectionMovieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionMovieRepository::class)]
#[ORM\Table(name: 'collection_movie')]
#[ORM\UniqueConstraint(name: 'uniq_collection_movie', columns: ['collection_id', 'movie_id'])]
class CollectionMovie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
     * Je stocke la date d'ajout du film dans la collection.
     */
    #[ORM\Column]
    private \DateTimeImmutable $addedAt;

    #[ORM\ManyToOne(inversedBy: 'collectionMovies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Collection $collection = null;

    #[ORM\ManyToOne(inversedBy: 'collectionMovies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Movie $movie = null;

    public function __construct()
    {
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }

    public function setAddedAt(\DateTimeImmutable $addedAt): static
    {
        $this->addedAt = $addedAt;

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

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function setMovie(Movie $movie): static
    {
        $this->movie = $movie;

        return $this;
    }
}
