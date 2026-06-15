<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reviews')]
class Reviews
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Relation vers le patient
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $patient = null;

    // Relation vers le médecin
    #[ORM\ManyToOne(targetEntity: Doctors::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctors $doctor = null;

    // Optionnel : lié à un rendez-vous précis
    #[ORM\ManyToOne(targetEntity: Appointments::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Appointments $appointment = null;

    #[ORM\Column(type: 'smallint')]
    private int $rating; // Note de 1 à 5 par exemple

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatient(): ?Users
    {
        return $this->patient;
    }

    public function setPatient(Users $patient): self
    {
        $this->patient = $patient;
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

    public function getAppointment(): ?Appointments
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointments $appointment): self
    {
        $this->appointment = $appointment;
        return $this;
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    public function setRating(int $rating): self
    {
        $this->rating = $rating;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
