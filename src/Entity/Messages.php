<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'messages')]
class Messages
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // Expéditeur
    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'sentMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $sender = null;

    // Destinataire
    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'receivedMessages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Users $receiver = null;

    // Optionnel : si le message est lié à un rendez-vous
    #[ORM\ManyToOne(targetEntity: Appointments::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Appointments $appointment = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $sentAt;

    #[ORM\Column(type: 'boolean')]
    private bool $isRead = false;

    public function __construct()
    {
        $this->sentAt = new DateTime();
    }

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?Users
    {
        return $this->sender;
    }

    public function setSender(Users $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    public function getReceiver(): ?Users
    {
        return $this->receiver;
    }

    public function setReceiver(Users $receiver): self
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function getAppointment(): ?Appointments
    {
        return $this->appointment;
    }

    public function setAppointment(?Appointments $appointment): self
    {
        $this->appointment = $appointment;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getSentAt(): DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;
        return $this;
    }
}
