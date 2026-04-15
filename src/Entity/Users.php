<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\LoggingAttempt;
use DateTime;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Ignore as Ignore;
use Symfony\Component\Serializer\Attribute\Groups as Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Ce Email est déjà utilisé')]
#[UniqueEntity(fields: ['phone'], message: 'Ce numéro est déjà utilisé')]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read', 'doctor:read'])]
    private ?int $id = null;

    #[ORM\Column(name: "first_name", type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Le prénom est requis")]
    #[Assert\Length(min: 2, max: 50, minMessage: "Le prénom doit faire au moins {{ limit }} caractères")]
    #[Groups(['user:read', 'doctor:read'])]
    private string $firstName;

    #[ORM\Column(name: "last_name", type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Le nom est requis")]
    #[Assert\Length(min: 2, max: 50)]
    #[Groups(['user:read', 'doctor:read'])]
    private string $lastName;

    #[ORM\Column(name: "gender", type: "string", length: 10)]
    #[Assert\NotBlank(message: "Le genre est obligatoire")]
    #[Assert\Choice(choices: ['male', 'female'], message: "Le genre doit être 'male' ou 'female'")]
    #[Groups(['user:read', 'doctor:read'])]
    private string $gender;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est requis")]
    #[Assert\Email(message: "Format d'email invalide")]
    #[Groups(['user:read', 'doctor:read'])]
    private string $email;

    #[ORM\Column(name: "address", type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $address;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est requis")]
    #[Assert\Regex(
        pattern: "/^\+[0-9]{1,13}$/",
        message: "Format du numéro de téléphone invalide, il doit commencer par + et contenir maximum 13 chiffres"
    )]
    #[Groups(['user:read', 'doctor:read'])]
    private string $phone;

    // ne jamais l'exposer
    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(message: "Le mot de passe est requis")]
    #[Assert\Length(min: 6, minMessage: "Le mot de passe doit faire au moins {{ limit }} caractères")]
    #[Ignore]
    private string $password;

    #[ORM\Column(type: 'string', nullable: true)]
    #[Groups(['user:read', 'doctor:read'])]
    private ?string $photo = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['user:read'])]
    private ?string $biography = null;

    #[ORM\Column(type: 'date', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $birthDay = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['user:read'])]
    private \DateTimeInterface $dateInscription;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['user:read', 'doctor:read'])]
    private string $role;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read'])]
    private bool $isPhoneVerified = true;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read'])]
    private bool $isEmailVerified = true;

    // ne jamais l'exposer
    #[ORM\Column(type: 'string', length: 255)]
    #[Ignore]
    private string $userToken;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UsersPasswordResetToken::class, cascade: ['remove'])]
    private Collection $passwordResetTokens;

    #[ORM\OneToMany(mappedBy: 'sender', targetEntity: Messages::class)]
    private Collection $sentMessages;

    #[ORM\OneToMany(mappedBy: 'receiver', targetEntity: Messages::class)]
    private Collection $receivedMessages;

    public function __construct()
    {
        $this->sentMessages = new ArrayCollection();
        $this->receivedMessages = new ArrayCollection();
        $this->passwordResetTokens = new ArrayCollection();
        $this->dateInscription = new \DateTime();

        $this->role = 'ROLE_USER';
        $this->userToken = bin2hex(random_bytes(32));
        $this->isActive = false;
        $this->isEmailVerified = false;
        $this->isPhoneVerified = false;
    }

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }
    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getGender(): string
    {
        return $this->gender;
    }
    public function setGender(string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }
    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }
    public function setPhone(string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }
    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getBiography(): ?string
    {
        return $this->biography;
    }
    public function setBiography(?string $biography): self
    {
        $this->biography = $biography;
        return $this;
    }

    public function getBirthDay(): ?\DateTimeInterface
    {
        return $this->birthDay;
    }
    public function setBirthDay(\DateTimeInterface $birthDay): self
    {
        $this->birthDay = $birthDay;
        return $this;
    }

    public function getDateInscription(): \DateTimeInterface
    {
        return $this->dateInscription;
    }
    public function setDateInscription(\DateTimeInterface $dateInscription): self
    {
        $this->dateInscription = $dateInscription;
        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }
    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }
    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
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

    public function isPhoneVerified(): bool
    {
        return $this->isPhoneVerified;
    }
    public function setIsPhoneVerified(bool $isPhoneVerified): self
    {
        $this->isPhoneVerified = $isPhoneVerified;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }
    public function setIsEmailVerified(bool $isEmailVerified): self
    {
        $this->isEmailVerified = $isEmailVerified;
        return $this;
    }

    public function getUserToken(): string
    {
        return $this->userToken;
    }
    public function setUserToken(string $userToken): self
    {
        $this->userToken = $userToken;
        return $this;
    }

    public function getPasswordResetTokens(): Collection
    {
        return $this->passwordResetTokens;
    }

    public function addPasswordResetToken(UsersPasswordResetToken $token): self
    {
        if (!$this->passwordResetTokens->contains($token)) {
            $this->passwordResetTokens->add($token);
            $token->setUser($this);
        }
        return $this;
    }

    public function removePasswordResetToken(UsersPasswordResetToken $token): self
    {
        $this->passwordResetTokens->removeElement($token);
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return [$this->role];
    }

    public function eraseCredentials(): void {}
}
