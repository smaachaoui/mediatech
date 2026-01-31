<?php

namespace App\Entity;

use App\Repository\CollectionBookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\UniqueConstraint(name: 'uniq_collection_book', columns: ['collection_id', 'book_id'])]
#[ORM\Table(name: 'collection_book')]
#[ORM\Entity(repositoryClass: CollectionBookRepository::class)]
class CollectionBook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private \DateTimeImmutable $addedAt;

    #[ORM\ManyToOne(inversedBy: 'collectionBooks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Collection $collection = null;

    #[ORM\ManyToOne(inversedBy: 'collectionBooks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

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

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(Book $book): static
    {
        $this->book = $book;

        return $this;
    }
}
