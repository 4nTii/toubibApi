<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Specialties;

#[ORM\Entity]
#[ORM\Table(name: 'doctors')]
class Doctors
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    // Relation vers l'utilisateur
    #[ORM\OneToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)]
    private ?Users $user = null;

    // Relation vers la spécialité
    #[ORM\ManyToOne(targetEntity: Specialities::class, inversedBy: 'doctors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Specialities $speciality = null;

    // Relation vers le cabinet
    #[ORM\ManyToOne(targetEntity: BusinessSites::class, inversedBy: 'doctors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BusinessSites $businessSite = null;

    // Relation vers les rendez-vous
    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Appointments::class)]
    private Collection $appointments;

    // Relation vers les créneaux d'indisponibilité
    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: UnavailabilitySlots::class)]
    private Collection $unavailabilitySlots;

    // Relation vers les reviews
    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Reviews::class)]
    private Collection $reviews;

    // Relation vers les prescriptions émises
    #[ORM\OneToMany(mappedBy: 'doctor', targetEntity: Prescriptions::class)]
    private Collection $prescriptions;



    public function __construct()
    {
        $this->appointments = new ArrayCollection();
        $this->unavailabilitySlots = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->prescriptions = new ArrayCollection();
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

    public function setBusinessSite(BusinessSites $businessSite): self
    {
        $this->businessSite = $businessSite;
        return $this;
    }

    /**
     * @return Collection<int, Appointments>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    /**
     * @return Collection<int, UnavailabilitySlots>
     */
    public function getUnavailabilitySlots(): Collection
    {
        return $this->unavailabilitySlots;
    }

    /**
     * @return Collection<int, Reviews>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /**
     * @return Collection<int, Prescriptions>
     */
    public function getPrescriptions(): Collection
    {
        return $this->prescriptions;
    }
}
