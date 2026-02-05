<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
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

    /*
     * Je stocke l'URL ou le chemin de l'image de couverture si j'en ai une.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Url(message: "L'URL de couverture n'est pas valide.")]
    #[Assert\Length(
        max: 2048,
        maxMessage: "L'URL de couverture ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $coverImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom de l'auteur ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $author = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "L'éditeur ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $publisher = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le traducteur ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $translator = null;

    /*
     * Je garde la date de parution optionnelle, car l'API peut ne pas la fournir.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publicationDate = null;

    /*
     * Je garde le genre en texte pour rester simple et rapide au départ.
     */
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(
        max: 100,
        maxMessage: "Le genre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $genre = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "Le nombre de pages doit être positif.")]
    private ?int $pageCount = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(
        max: 20,
        maxMessage: "L'ISBN ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[0-9Xx- ]+$/",
        message: "L'ISBN contient des caractères non valides."
    )]
    private ?string $isbn = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 5000,
        maxMessage: "Le synopsis ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $synopsis = null;

    #[ORM\Column(length: 50, nullable: true, unique: true)]
    #[Assert\Length(
        max: 50,
        maxMessage: "L'identifiant Google Books ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $googleBooksId = null;

    /*
     * Je stocke la date d'ajout en base pour trier les derniers livres.
     */
    #[ORM\Column]
    #[Assert\NotNull]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'book', targetEntity: CollectionBook::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private DoctrineCollection $collectionBooks;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->collectionBooks = new ArrayCollection();
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

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $coverImage = $coverImage !== null ? trim($coverImage) : null;
        $this->coverImage = $coverImage !== '' ? $coverImage : null;

        return $this;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(?string $author): static
    {
        $author = $author !== null ? trim($author) : null;
        $this->author = $author !== '' ? $author : null;

        return $this;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setPublisher(?string $publisher): static
    {
        $publisher = $publisher !== null ? trim($publisher) : null;
        $this->publisher = $publisher !== '' ? $publisher : null;

        return $this;
    }

    public function getTranslator(): ?string
    {
        return $this->translator;
    }

    public function setTranslator(?string $translator): static
    {
        $translator = $translator !== null ? trim($translator) : null;
        $this->translator = $translator !== '' ? $translator : null;

        return $this;
    }

    public function getPublicationDate(): ?\DateTimeImmutable
    {
        return $this->publicationDate;
    }

    public function setPublicationDate(?\DateTimeImmutable $publicationDate): static
    {
        $this->publicationDate = $publicationDate;

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

    public function getPageCount(): ?int
    {
        return $this->pageCount;
    }

    public function setPageCount(?int $pageCount): static
    {
        $this->pageCount = $pageCount;

        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $isbn = $isbn !== null ? trim($isbn) : null;
        $this->isbn = $isbn !== '' ? $isbn : null;

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

    public function getGoogleBooksId(): ?string
    {
        return $this->googleBooksId;
    }

    public function setGoogleBooksId(?string $googleBooksId): static
    {
        $googleBooksId = $googleBooksId !== null ? trim($googleBooksId) : null;
        $this->googleBooksId = $googleBooksId !== '' ? $googleBooksId : null;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /*
     * Je garde le setter au cas où, mais je ne le change pas en pratique.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
            $collectionBook->setBook($this);
        }

        return $this;
    }

    public function removeCollectionBook(CollectionBook $collectionBook): static
    {
        if ($this->collectionBooks->removeElement($collectionBook)) {
            // Je ne mets pas book à null, je laisse Doctrine supprimer la ligne pivot.
        }

        return $this;
    }
}
