<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'regions')]
class Regions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private string $name; // Nom de la région

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['doctor:read', 'businesssite:read'])]
    private string $country; // Nom du pays

    // Relation vers business_sites
    #[ORM\OneToMany(mappedBy: 'region', targetEntity: BusinessSites::class)]
    private Collection $businessSites;

    public function __construct()
    {
        $this->businessSites = new ArrayCollection();
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

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    /**
     * @return Collection<int, BusinessSites>
     */
    public function getBusinessSites(): Collection
    {
        return $this->businessSites;
    }

    public function addBusinessSite(BusinessSites $site): self
    {
        if (!$this->businessSites->contains($site)) {
            $this->businessSites->add($site);
            $site->setRegion($this);
        }

        return $this;
    }

    public function removeBusinessSite(BusinessSites $site): self
    {
        if ($this->businessSites->removeElement($site)) {
            if ($site->getRegion() === $this) {
                $site->setRegion(null);
            }
        }

        return $this;
    }
}
