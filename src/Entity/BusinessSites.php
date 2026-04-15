<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups as Groups;

#[ORM\Entity]
#[ORM\Table(name: 'business_sites')]
class BusinessSites
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private string $address;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private string $ville;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private ?string $phone = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private ?string $email = null;

    #[ORM\ManyToOne(targetEntity: Regions::class, inversedBy: 'businessSites')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['businesssite:read'])]
    private ?Regions $region = null;

    #[ORM\OneToMany(mappedBy: 'businessSite', targetEntity: DoctorBusinessSite::class, cascade: ['persist', 'remove'])]
    private Collection $doctorBusinessSites;

    #[ORM\OneToMany(mappedBy: 'businessSite', targetEntity: Appointments::class)]
    private Collection $appointments;

    #[ORM\OneToMany(mappedBy: 'businessSite', targetEntity: UnavailabilitySlots::class)]
    private Collection $unavailabilitySlots;

    public function __construct()
    {
        $this->doctorBusinessSites = new ArrayCollection();
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

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): self
    {
        $this->ville = $ville;
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

    public function getDoctorBusinessSites(): Collection
    {
        return $this->doctorBusinessSites;
    }

    public function addDoctorBusinessSite(DoctorBusinessSite $doctorBusinessSite): self
    {
        if (!$this->doctorBusinessSites->contains($doctorBusinessSite)) {
            $this->doctorBusinessSites->add($doctorBusinessSite);
            $doctorBusinessSite->setBusinessSite($this);
        }
        return $this;
    }

    public function removeDoctorBusinessSite(DoctorBusinessSite $doctorBusinessSite): self
    {
        $this->doctorBusinessSites->removeElement($doctorBusinessSite);
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
}
