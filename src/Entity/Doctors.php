<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use DateTimeInterface;

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
    private ?DateTimeInterface $activityStarted = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['doctor:read'])]
    private ?string $biography = null;

    #[ORM\Column(name: 'profile_picture', type: 'string', length: 255, nullable: true)]
    #[Groups(['doctor:read'])]
    private ?string $profilePicture = null;

    #[ORM\Column(name: 'accept_new_patients', type: 'boolean')]
    #[Groups(['doctor:read'])]
    private bool $acceptNewPatients = true;

    #[ORM\Column(name: 'teleconsultation_enabled', type: 'boolean')]
    #[Groups(['doctor:read'])]
    private bool $teleconsultationEnabled = false;

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

    // Relation vers les cabinets via la table de jointure
    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: DoctorBusinessSite::class, cascade: ['persist', 'remove'])]
    #[Groups(['doctor:read'])]
    private Collection $doctorBusinessSites;

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
        $this->doctorBusinessSites = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->unavailabilitySlots = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->prescriptions = new ArrayCollection();
        $this->verified = true;
        $this->isActive = true;
        $this->acceptNewPatients = true;
        $this->teleconsultationEnabled = false;
    }

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

    public function getActivityStarted(): ?DateTimeInterface
    {
        return $this->activityStarted;
    }
    public function setActivityStarted(?DateTimeInterface $activityStarted): self
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

    public function getDoctorBusinessSites(): Collection
    {
        return $this->doctorBusinessSites;
    }

    public function addDoctorBusinessSite(DoctorBusinessSite $dbs): self
    {
        if (!$this->doctorBusinessSites->contains($dbs)) {
            $this->doctorBusinessSites->add($dbs);
            $dbs->setDoctor($this);
        }
        return $this;
    }

    public function removeDoctorBusinessSite(DoctorBusinessSite $dbs): self
    {
        $this->doctorBusinessSites->removeElement($dbs);
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
