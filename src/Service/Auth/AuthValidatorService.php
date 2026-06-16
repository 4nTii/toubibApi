<?php

namespace App\Service\Auth;

use App\Entity\Users;
use App\Repository\UsersRepository;

class AuthValidatorService
{
    private UsersRepository $usersRepository;

    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    public function validateEmail(string $email): ?string
    {
        if ($this->usersRepository->findByEmail($email)) {
            return "Cet email est déjà utilisé";
        }
        return null;
    }

    public function validatePassword(string $password): ?string
    {
        if (strlen($password) < 8) {
            return "Le mot de passe doit contenir au moins 8 caractères";
        }

        if (!preg_match('/[A-Z]/', $password)) {
            return "Le mot de passe doit contenir au moins une majuscule";
        }

        if (!preg_match('/[a-z]/', $password)) {
            return "Le mot de passe doit contenir au moins une minuscule";
        }

        if (!preg_match('/[0-9]/', $password)) {
            return "Le mot de passe doit contenir au moins un chiffre";
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            return "Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&* etc.)";
        }

        return null;
    }

    public function validateFirstName(string $firstName): ?string
    {
        if (empty($firstName)) {
            return "Le prénom est requis";
        }

        if (strlen($firstName) < 2 || strlen($firstName) > 50) {
            return "Le prénom doit faire entre 2 et 50 caractères";
        }

        return null;
    }

    public function validateLastName(string $lastName): ?string
    {
        if (empty($lastName)) {
            return "Le nom est requis";
        }

        if (strlen($lastName) < 2 || strlen($lastName) > 50) {
            return "Le nom doit faire entre 2 et 50 caractères";
        }

        return null;
    }

    public function validateAll(Users $user): array
    {
        $erreurs = [];

        $emailErreur = $this->validateEmail($user->getEmail());
        if ($emailErreur) {
            $erreurs['email'] = $emailErreur;
        }

        $passwordErreur = $this->validatePassword($user->getPassword());
        if ($passwordErreur) {
            $erreurs['password'] = $passwordErreur;
        }

        $prenomErreur = $this->validateFirstName($user->getFirstName());
        if ($prenomErreur) {
            $erreurs['firstName'] = $prenomErreur;
        }

        $nomErreur = $this->validateLastName($user->getLastName());
        if ($nomErreur) {
            $erreurs['lastName'] = $nomErreur;
        }

        // Peut être ajouter plus tard les autres validations ici si nécessaire (rôle, photo...)

        return $erreurs;
    }
}
