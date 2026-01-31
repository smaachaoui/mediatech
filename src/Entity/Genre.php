<?php

namespace App\Entity;

use App\Repository\GenreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GenreRepository::class)]
class Genre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 10)]
    private ?string $type = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoriteGenres')]
    private Collection $usersWhoFavorited;

    public function __construct()
    {
        $this->usersWhoFavorited = new ArrayCollection();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsersWhoFavorited(): Collection
    {
        return $this->usersWhoFavorited;
    }

    public function addUsersWhoFavorited(User $usersWhoFavorited): static
    {
        if (!$this->usersWhoFavorited->contains($usersWhoFavorited)) {
            $this->usersWhoFavorited->add($usersWhoFavorited);
            $usersWhoFavorited->addFavoriteGenre($this);
        }

        return $this;
    }

    public function removeUsersWhoFavorited(User $usersWhoFavorited): static
    {
        if ($this->usersWhoFavorited->removeElement($usersWhoFavorited)) {
            $usersWhoFavorited->removeFavoriteGenre($this);
        }

        return $this;
    }
}
