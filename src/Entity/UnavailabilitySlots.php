<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'unavailability_slots')]
class UnavailabilitySlots
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Relation vers le médecin
    #[ORM\ManyToOne(targetEntity: Doctors::class, inversedBy: 'unavailabilitySlots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Doctors $doctor = null;

    // Relation vers le cabinet
    #[ORM\ManyToOne(targetEntity: BusinessSites::class, inversedBy: 'unavailabilitySlots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BusinessSites $businessSite = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $endTime;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $reason = null; // optionnel, ex: "Congés", "Formation"

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

    public function getBusinessSite(): ?BusinessSites
    {
        return $this->businessSite;
    }

    public function setBusinessSite(BusinessSites $businessSite): self
    {
        $this->businessSite = $businessSite;
        return $this;
    }

    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;
        return $this;
    }
}
