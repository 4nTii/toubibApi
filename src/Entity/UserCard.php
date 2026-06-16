<?php

namespace App\Entity;

use App\Repository\UserCardRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserCardRepository::class)]
#[ORM\Table(name: 'user_cards')]
class UserCard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?Users $user = null;

    #[ORM\Column(name: 'card_holder', length: 100)]
    #[Groups(['user:read'])]
    private ?string $cardHolder = null;

    #[ORM\Column(name: 'card_number', length: 19)]
    #[Groups(['user:read'])]
    private ?string $cardNumber = null;

    #[ORM\Column(name: 'expire_date', length: 7)]
    #[Groups(['user:read'])]
    private ?string $expireDate = null;

    #[ORM\Column(name: 'card_cvv', length: 4)]
    private ?string $cardCvv = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCardHolder(): ?string
    {
        return $this->cardHolder;
    }

    public function setCardHolder(?string $cardHolder): static
    {
        $this->cardHolder = $cardHolder;
        return $this;
    }

    public function getCardNumber(): ?string
    {
        return $this->cardNumber;
    }

    public function setCardNumber(?string $cardNumber): static
    {
        $this->cardNumber = $cardNumber;
        return $this;
    }

    public function getExpireDate(): ?string
    {
        return $this->expireDate;
    }

    public function setExpireDate(?string $expireDate): static
    {
        $this->expireDate = $expireDate;
        return $this;
    }

    public function getCardCvv(): ?string
    {
        return $this->cardCvv;
    }

    public function setCardCvv(?string $cardCvv): static
    {
        $this->cardCvv = $cardCvv;
        return $this;
    }
}
