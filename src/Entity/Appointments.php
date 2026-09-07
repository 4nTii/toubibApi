<?php

namespace App\Entity;

use DateTimeInterface;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'appointments')]
class Appointments
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
    #[ORM\ManyToOne(targetEntity: Doctors::class, inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctors $doctor = null;

    // Relation vers le cabinet
    #[ORM\ManyToOne(targetEntity: BusinessSites::class, inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BusinessSites $businessSite = null;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $startTime;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $endTime;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $status = 'scheduled'; // ex: scheduled, cancelled, completed

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

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

    public function getBusinessSite(): ?BusinessSites
    {
        return $this->businessSite;
    }

    public function setBusinessSite(BusinessSites $businessSite): self
    {
        $this->businessSite = $businessSite;
        return $this;
    }

    public function getStartTime(): DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
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
