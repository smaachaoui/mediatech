<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection as DoctrineCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
     * Je garde l'email unique car c'est l'identifiant principal de connexion.
     */
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /*
     * Je stocke les rôles en base, et je garantis ROLE_USER dans getRoles().
     */
    #[ORM\Column]
    private array $roles = [];

    /*
     * Je stocke uniquement le mot de passe hashé.
     */
    #[ORM\Column]
    private ?string $password = null;

    /*
     * J'ajoute un pseudo pour l'affichage public.
     */
    #[ORM\Column(length: 50)]
    private ?string $pseudo = null;

    /*
     * Je stocke le chemin ou l'URL de la photo de profil.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePicture = null;

    /*
     * J'ajoute une biographie facultative.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biography = null;

    /*
     * Je garde un état actif/inactif pour pouvoir désactiver un compte sans le supprimer.
     */
    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    /*
     * Je conserve la date de création du compte.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Collection::class)]
    private DoctrineCollection $collections;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Rating::class)]
    private DoctrineCollection $ratings;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Comment::class)]
    private DoctrineCollection $comments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Wishlist::class)]
    private DoctrineCollection $wishlists;

    #[ORM\OneToMany(mappedBy: 'requester', targetEntity: Friendship::class)]
    private DoctrineCollection $friendshipsRequested;

    #[ORM\OneToMany(mappedBy: 'addressee', targetEntity: Friendship::class)]
    private DoctrineCollection $friendshipsReceived;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->collections = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->wishlists = new ArrayCollection();
        $this->friendshipsRequested = new ArrayCollection();
        $this->friendshipsReceived = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /*
     * Je retourne l'identifiant de sécurité utilisé par Symfony.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /*
     * Je ne stocke pas de données temporaires sensibles pour l'instant.
     */
    public function eraseCredentials(): void
    {
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): static
    {
        $this->profilePicture = $profilePicture;

        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }

    public function setBiography(?string $biography): static
    {
        $this->biography = $biography;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $active): static
    {
        $this->isActive = $active;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /*
     * Je laisse ce setter disponible, mais en pratique je ne le change pas après création.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DoctrineCollection<int, Collection>
     */
    public function getCollections(): DoctrineCollection
    {
        return $this->collections;
    }

    public function addCollection(Collection $collection): static
    {
        if (!$this->collections->contains($collection)) {
            $this->collections->add($collection);
            $collection->setUser($this);
        }

        return $this;
    }

    public function removeCollection(Collection $collection): static
    {
        $this->collections->removeElement($collection);

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
            $rating->setUser($this);
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
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        $this->comments->removeElement($comment);

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
            $wishlist->setUser($this);
        }

        return $this;
    }

    public function removeWishlist(Wishlist $wishlist): static
    {
        if ($this->wishlists->removeElement($wishlist)) {
            // set the owning side to null (unless already changed)
            if ($wishlist->getUser() === $this) {
                $wishlist->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return DoctrineCollection<int, Friendship>
     */
    public function getFriendshipsRequested(): DoctrineCollection
    {
        return $this->friendshipsRequested;
    }

    public function addFriendshipsRequested(Friendship $friendshipsRequested): static
    {
        if (!$this->friendshipsRequested->contains($friendshipsRequested)) {
            $this->friendshipsRequested->add($friendshipsRequested);
            $friendshipsRequested->setRequester($this);
        }

        return $this;
    }

    public function removeFriendshipsRequested(Friendship $friendshipsRequested): static
    {
        if ($this->friendshipsRequested->removeElement($friendshipsRequested)) {
            // set the owning side to null (unless already changed)
            if ($friendshipsRequested->getRequester() === $this) {
                $friendshipsRequested->setRequester(null);
            }
        }

        return $this;
    }

    /**
     * @return DoctrineCollection<int, Friendship>
     */
    public function getFriendshipsReceived(): DoctrineCollection
    {
        return $this->friendshipsReceived;
    }

    public function addFriendshipsReceived(Friendship $friendshipsReceived): static
    {
        if (!$this->friendshipsReceived->contains($friendshipsReceived)) {
            $this->friendshipsReceived->add($friendshipsReceived);
            $friendshipsReceived->setAddressee($this);
        }

        return $this;
    }

    public function removeFriendshipsReceived(Friendship $friendshipsReceived): static
    {
        if ($this->friendshipsReceived->removeElement($friendshipsReceived)) {
            // set the owning side to null (unless already changed)
            if ($friendshipsReceived->getAddressee() === $this) {
                $friendshipsReceived->setAddressee(null);
            }
        }

        return $this;
    }
}
