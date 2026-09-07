<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use DateTime;
use DateTimeInterface;

#[ORM\Entity]
#[ORM\Table(name: 'patients_history')]
class PatientsHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['patient_history:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['patient_history:read'])]
    private ?Users $user = null;

    #[ORM\ManyToOne(targetEntity: Doctors::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['patient_history:read'])]
    private ?Doctors $doctor = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['patient_history:read'])]
    private DateTimeInterface $date;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['patient_history:read'])]
    private ?string $notes = null;

    public function __construct()
    {
        $this->date = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(Users $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getDoctor(): ?Doctors
    {
        return $this->doctor;
    }

    public function setDoctor(Doctors $doctor): self
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getDate(): DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }
}
