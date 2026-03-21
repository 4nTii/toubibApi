<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'business_sites')]
class BusinessSites
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name; // Nom du cabinet ou centre médical

    #[ORM\Column(type: 'string', length: 255)]
    private string $address; // Adresse complète

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    // Relation vers la région
    #[ORM\ManyToOne(targetEntity: Regions::class, inversedBy: 'businessSites')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Regions $region = null;

    // Relation vers les médecins
    #[ORM\OneToMany(mappedBy: 'businessSite', targetEntity: Doctors::class)]
    private Collection $doctors;

    // Relation vers les rendez-vous
    #[ORM\OneToMany(mappedBy: 'businessSite', targetEntity: Appointments::class)]
    private Collection $appointments;

    // Relation vers les créneaux d'indisponibilité
    #[ORM\OneToMany(mappedBy: 'businessSite', targetEntity: UnavailabilitySlots::class)]
    private Collection $unavailabilitySlots;

    public function __construct()
    {
        $this->doctors = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->unavailabilitySlots = new ArrayCollection();
    }

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getRegion(): ?Regions
    {
        return $this->region;
    }

    public function setRegion(?Regions $region): self
    {
        $this->region = $region;
        return $this;
    }

    /**
     * @return Collection<int, Doctors>
     */
    public function getDoctors(): Collection
    {
        return $this->doctors;
    }

    public function addDoctor(Doctors $doctor): self
    {
        if (!$this->doctors->contains($doctor)) {
            $this->doctors->add($doctor);
            $doctor->setBusinessSite($this);
        }

        return $this;
    }

    public function removeDoctor(Doctors $doctor): self
    {
        if ($this->doctors->removeElement($doctor)) {
            if ($doctor->getBusinessSite() === $this) {
                $doctor->setBusinessSite(null);
            }
        }

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
}
