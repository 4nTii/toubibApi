<?php

namespace App\DTO;

use App\DTO\DoctorBusinessSiteDTO;
use Symfony\Component\Validator\Constraints as Assert;

class CreateBusinessSiteDTO
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    public string $name;

    #[Assert\NotBlank]
    public string $address;

    #[Assert\NotBlank]
    public string $ville;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    public string $phone;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    public int $region;

    #[Assert\NotNull]
    #[Assert\Valid]
    public DoctorBusinessSiteDTO $doctorBusinessSite;
}
