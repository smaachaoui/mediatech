<?php

namespace App\Entity;

use App\Repository\ContactMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\Table(name: 'contact_message')]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /*
     * Je stocke le nom de la personne qui envoie le message.
     */
    #[ORM\Column(length: 100)]
    private string $name;

    /*
     * Je stocke l'email de contact.
     */
    #[ORM\Column(length: 180)]
    private string $email;

    /*
     * Je stocke le sujet du message.
     */
    #[ORM\Column(length: 255)]
    private string $subject;

    /*
     * Je stocke le contenu du message.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $message;

    /*
     * Je garde un état lu / non lu pour l'admin.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $isRead = false;

    /*
     * Je stocke la date d'envoi du message.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /*
     * Je lie le message à un utilisateur si la personne est connectée.
     * Sinon, la valeur reste null.
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isRead = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /*
     * Je garde ce setter, mais createdAt est normalement fixé à la création.
     */
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
