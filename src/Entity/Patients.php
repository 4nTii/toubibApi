<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'patients')]
class Patients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Relation vers l'utilisateur
    #[ORM\OneToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $user = null;

    // Dossier médical ou informations complémentaires
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $medicalHistory = null;

    // Relation vers les rendez-vous
    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: Appointments::class)]
    private Collection $appointments;

    // Relation vers les reviews
    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: Reviews::class)]
    private Collection $reviews;

    // Relation vers les prescriptions
    #[ORM\OneToMany(mappedBy: 'patient', targetEntity: Prescriptions::class)]
    private Collection $prescriptions;

    public function __construct()
    {
        $this->appointments = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->prescriptions = new ArrayCollection();
    }

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMedicalHistory(): ?string
    {
        return $this->medicalHistory;
    }

    public function setMedicalHistory(?string $medicalHistory): self
    {
        $this->medicalHistory = $medicalHistory;
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
