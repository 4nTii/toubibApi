<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Specialties;
use Symfony\Component\Serializer\Attribute\Groups as Groups;

#[ORM\Entity]
#[ORM\Table(name: 'doctors')]
class Doctors
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['doctor:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['doctor:read'])]
    private bool $isActive = true;

    #[ORM\Column(name: 'license_number', type: 'string', length: 100, unique: true)]
    #[Groups(['doctor:read'])]
    private string $licenseNumber;

    #[ORM\Column(name: 'activity_started', type: 'date')]
    #[Groups(['doctor:read'])]
    private ?\DateTimeInterface $activityStarted = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['doctor:read'])]
    private ?string $biography = null;

    #[ORM\Column(name: 'profile_picture', type: 'string', length: 255, nullable: true)]
    #[Groups(['doctor:read'])]
    private ?string $profilePicture = null;

    #[ORM\Column(name: 'consultation_duration', type: 'integer', options: ['default' => 30])]
    #[Groups(['doctor:read'])]
    private ?int $consultationDuration = null;

    #[ORM\Column(name: 'consultation_fee', type: 'integer', nullable: true)]
    #[Groups(['doctor:read'])]
    private ?int $consultationFee = null;

    #[ORM\Column(name: 'accept_new_patients', type: 'boolean')]
    #[Groups(['doctor:read'])]
    private bool $acceptNewPatients = true;

    #[ORM\Column(name: 'teleconsultation_enabled', type: 'boolean')]
    #[Groups(['doctor:read'])]
    private bool $teleconsultationEnabled = false;

    #[ORM\Column(name: 'working_schedule', type: 'json', nullable: true)]
    #[Groups(['doctor:read'])]
    private ?array $workingSchedule = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['doctor:read'])]
    private bool $verified = false;

    // Relation vers l'utilisateur
    #[ORM\OneToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    #[Groups(['doctor:read'])]
    private ?Users $user = null;

    // Relation vers la spécialité
    #[ORM\ManyToOne(targetEntity: Specialities::class, inversedBy: 'doctors')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['doctor:read'])]
    private ?Specialities $speciality = null;

    // Relation vers le cabinet
    #[ORM\ManyToOne(targetEntity: BusinessSites::class, inversedBy: 'doctors')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['doctor:read'])]
    private ?BusinessSites $businessSite = null;

    // Pas de #[Groups] sur les collections → le serializer s'arrête
    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Appointments::class)]
    private Collection $appointments;

    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: UnavailabilitySlots::class)]
    private Collection $unavailabilitySlots;

    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Reviews::class)]
    private Collection $reviews;

    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Prescriptions::class)]
    private Collection $prescriptions;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
        $this->unavailabilitySlots = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->prescriptions = new ArrayCollection();
        $this->verified = true;
        $this->isActive = true;
        $this->acceptNewPatients = true;
        $this->consultationDuration = 30;
        $this->consultationFee = 20;
        $this->teleconsultationEnabled = false;
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

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getLicenseNumber(): string
    {
        return $this->licenseNumber;
    }
    public function setLicenseNumber(string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;
        return $this;
    }

    public function getActivityStarted(): ?\DateTimeInterface
    {
        return $this->activityStarted;
    }
    public function setActivityStarted(?\DateTimeInterface $activityStarted): self
    {
        $this->activityStarted = $activityStarted;
        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }
    public function setBiography(?string $biography): self
    {
        $this->biography = $biography;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }
    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getConsultationDuration(): ?int
    {
        return $this->consultationDuration;
    }
    public function setConsultationDuration(?int $consultationDuration): self
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

    public function isAcceptNewPatients(): bool
    {
        return $this->acceptNewPatients;
    }
    public function setAcceptNewPatients(bool $acceptNewPatients): self
    {
        $this->acceptNewPatients = $acceptNewPatients;
        return $this;
    }

    public function isTeleconsultationEnabled(): bool
    {
        return $this->teleconsultationEnabled;
    }
    public function setTeleconsultationEnabled(bool $teleconsultationEnabled): self
    {
        $this->teleconsultationEnabled = $teleconsultationEnabled;
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

    public function isVerified(): bool
    {
        return $this->verified;
    }
    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;
        return $this;
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

    public function getSpeciality(): ?Specialities
    {
        return $this->speciality;
    }
    public function setSpeciality(?Specialities $speciality): self
    {
        $this->speciality = $speciality;
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

    public function getAppointments(): Collection
    {
        return $this->appointments;
    }
    public function getUnavailabilitySlots(): Collection
    {
        return $this->unavailabilitySlots;
    }
    public function getReviews(): Collection
    {
        return $this->reviews;
    }
    public function getPrescriptions(): Collection
    {
        return $this->prescriptions;
    }
}
