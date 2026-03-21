<?php

namespace App\Entity;

use App\Repository\AppConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppConfigRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AppConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $appName = "Toubib";

    #[ORM\Column(length: 20)]
    private ?string $version = "0.1";

    #[ORM\Column(length: 255)]
    private ?string $logo = "/logo.png";

    #[ORM\Column]
    private ?bool $maintenance = false;

    #[ORM\Column]
    private ?\DateTime $updateAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppName(): ?string
    {
        return $this->appName;
    }

    public function setAppName(string $appName): static
    {
        $this->appName = $appName;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;
        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): static
    {
        $this->logo = $logo;
        return $this;
    }

    public function isMaintenance(): ?bool
    {
        return $this->maintenance;
    }

    public function setMaintenance(bool $maintenance): static
    {
        $this->maintenance = $maintenance;
        return $this;
    }

    public function getUpdateAt(): ?\DateTime
    {
        return $this->updateAt;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updateAt = new \DateTime();
    }
}
