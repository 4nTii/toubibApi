<?php

namespace App\Controller\Auth\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Nom d\'utilisateur requis')]
        #[Assert\Email(message: 'Format d\'email invalide')]
        public readonly string $username,

        #[Assert\NotBlank(message: 'Mot de passe requis')]
        public readonly string $password,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            username: $data['username'] ?? '',
            password: $data['password'] ?? '',
        );
    }
}
