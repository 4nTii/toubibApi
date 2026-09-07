<?php

namespace App\Entity;

use App\Repository\LoggingAttemptRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LoggingAttemptRepository::class)]
class LoggingAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'email', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'ip_address', length: 45)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private ?DateTimeImmutable $attemptedAt = null;

    public function __construct(?string $email, ?string $ipAddress, ?string $userAgent)
    {
        $this->attemptedAt = new DateTimeImmutable();
        $this->email = $email;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
    }

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    public function getAttemptedAt(): ?DateTimeImmutable
    {
        return $this->attemptedAt;
    }

    public function setAttemptedAt(DateTimeImmutable $attemptedAt): self
    {
        $this->attemptedAt = $attemptedAt;
        return $this;
    }
}
