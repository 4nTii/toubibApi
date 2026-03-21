<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'specialties')]
class Specialities
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name; // Nom de la spécialité, ex: "Généraliste", "Cardiologue"

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null; // Description facultative

    // Relation vers les médecins
    #[ORM\OneToMany(mappedBy: 'speciality', targetEntity: Doctors::class)]
    private Collection $doctors;

    public function __construct()
    {
        $this->doctors = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
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
            $doctor->setSpeciality($this);
        }

        return $this;
    }

    public function removeDoctor(Doctors $doctor): self
    {
        if ($this->doctors->removeElement($doctor)) {
            if ($doctor->getSpeciality() === $this) {
                $doctor->setSpeciality(null);
            }
        }

        return $this;
    }
}
