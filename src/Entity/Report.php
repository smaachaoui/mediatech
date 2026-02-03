<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'report')]
#[ORM\UniqueConstraint(name: 'uniq_report_reporter_reported', columns: ['reporter_id', 'reported_user_id'])]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private string $reason;

    #[ORM\Column(length: 10)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $treatedAt = null;

 

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reportsReceived')]
    #[ORM\JoinColumn(name: 'reported_user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $reportedUser = null;


    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reportsMade')]
    #[ORM\JoinColumn(name: 'reporter_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $reporter = null;



    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'pending';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getTreatedAt(): ?\DateTimeImmutable
    {
        return $this->treatedAt;
    }

    public function setTreatedAt(?\DateTimeImmutable $treatedAt): static
    {
        $this->treatedAt = $treatedAt;

        return $this;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(User $reporter): static
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getReportedUser(): ?User
    {
        return $this->reportedUser;
    }

    public function setReportedUser(User $reportedUser): static
    {
        $this->reportedUser = $reportedUser;

        return $this;
    }
}
