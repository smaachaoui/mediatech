<?php

namespace App\Entity;

use App\Repository\CollectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CollectionRepository::class)]
class Collection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 10)]
    private string $type;

    #[ORM\Column(length: 10)]
    private string $visibility = 'private';

    #[ORM\Column(options: ['default' => false])]
    private bool $isPublished = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(nullable: false)]
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

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

    public function addCollectionBook(CollectionBook $collectionBook): static
    {
        if (!$this->collectionBooks->contains($collectionBook)) {
            $this->collectionBooks->add($collectionBook);
            $collectionBook->setCollection($this);
        }

        return $this;
    }

    public function removeCollectionBook(CollectionBook $collectionBook): static
    {
        $this->collectionBooks->removeElement($collectionBook);

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
            $collectionMovie->setCollection($this);
        }

        return $this;
    }

    public function removeCollectionMovie(CollectionMovie $collectionMovie): static
    {
        $this->collectionMovies->removeElement($collectionMovie);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, Rating>
     */
    public function getRatings(): DoctrineCollection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setCollection($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        $this->ratings->removeElement($rating);

        return $this;
    }

    /**
     * @return DoctrineCollection<int, Comment>
     */
    public function getComments(): DoctrineCollection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setCollection($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        $this->comments->removeElement($comment);

        return $this;
    }
}
