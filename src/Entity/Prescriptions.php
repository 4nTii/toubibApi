<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'prescriptions')]
class Prescriptions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Médecin qui a émis la prescription
    #[ORM\ManyToOne(targetEntity: Doctors::class, inversedBy: 'prescriptions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctors $doctor = null;

    // Patient destinataire
    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $patient = null;

    // Optionnel : lié à un rendez-vous spécifique
    #[ORM\ManyToOne(targetEntity: Appointments::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Appointments $appointment = null;

    #[ORM\Column(type: 'text')]
    private string $medications; // Détails des médicaments et posologie

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null; // Notes supplémentaires

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

    public function getDoctor(): ?Doctors
    {
        return $this->doctor;
    }

    public function setDoctor(Doctors $doctor): self
    {
        $this->doctor = $doctor;
        return $this;
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

    public function getAppointment(): ?Appointments
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointments $appointment): self
    {
        $this->appointment = $appointment;
        return $this;
    }

    public function getMedications(): string
    {
        return $this->medications;
    }

    public function setMedications(string $medications): self
    {
        $this->medications = $medications;
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
