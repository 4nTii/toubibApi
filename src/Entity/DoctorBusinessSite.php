<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'doctor_business_site')]
#[ORM\UniqueConstraint(name: 'unique_doctor_site', columns: ['doctor_id', 'business_site_id'])]
class DoctorBusinessSite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Doctors::class, inversedBy: 'doctorBusinessSites')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['businesssite:read'])]
    private ?Doctors $doctor = null;

    #[ORM\ManyToOne(targetEntity: BusinessSites::class, inversedBy: 'doctorBusinessSites')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['doctor:read'])]
    private ?BusinessSites $businessSite = null;

    #[ORM\Column(name: 'is_owner', type: 'boolean')]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private bool $isOwner = false;

    #[ORM\Column(name: 'is_primary', type: 'boolean')]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private bool $isPrimary = false;

    #[ORM\Column(name: 'consultation_duration', type: 'integer', options: ['default' => 30])]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private int $consultationDuration = 30;

    #[ORM\Column(name: 'consultation_fee', type: 'integer', nullable: true)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private ?int $consultationFee = null;

    #[ORM\Column(name: 'working_schedule', type: 'json', nullable: true)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private ?array $workingSchedule = null;

    public function __construct()
    {
        $this->isOwner = false;
        $this->isPrimary = false;
        $this->consultationDuration = 30;
        $this->consultationFee = null;
        $this->workingSchedule = [
            "monday"    => ["start" => "08:00", "end" => "18:00", "enabled" => true],
            "tuesday"   => ["start" => "08:00", "end" => "18:00", "enabled" => true],
            "wednesday" => ["start" => "08:00", "end" => "12:00", "enabled" => true],
            "thursday"  => ["start" => "08:00", "end" => "18:00", "enabled" => true],
            "friday"    => ["start" => "08:00", "end" => "17:00", "enabled" => true],
            "saturday"  => ["start" => "09:00", "end" => "12:00", "enabled" => false],
            "sunday"    => ["start" => "00:00", "end" => "00:00", "enabled" => false],
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDoctor(): ?Doctors
    {
        return $this->doctor;
    }
    public function setDoctor(?Doctors $doctor): self
    {
        $this->doctor = $doctor;
        return $this;
    }

    public function getBusinessSite(): ?BusinessSites
    {
        return $this->businessSite;
    }
    public function setBusinessSite(?BusinessSites $businessSite): self
    {
        $this->businessSite = $businessSite;
        return $this;
    }

    public function isOwner(): bool
    {
        return $this->isOwner;
    }
    public function setIsOwner(bool $isOwner): self
    {
        $this->isOwner = $isOwner;
        return $this;
    }

    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }
    public function setIsPrimary(bool $isPrimary): self
    {
        $this->isPrimary = $isPrimary;
        return $this;
    }

    public function getConsultationDuration(): int
    {
        return $this->consultationDuration;
    }
    public function setConsultationDuration(int $consultationDuration): self
    {
        $this->consultationDuration = $consultationDuration;
        return $this;
    }

    public function getConsultationFee(): ?int
    {
        return $this->consultationFee;
    }
    public function setConsultationFee(?int $consultationFee): self
    {
        $this->consultationFee = $consultationFee;
        return $this;
    }

    public function getWorkingSchedule(): ?array
    {
        return $this->workingSchedule;
    }
    public function setWorkingSchedule(?array $workingSchedule): self
    {
        $this->workingSchedule = $workingSchedule;
        return $this;
    }
}
