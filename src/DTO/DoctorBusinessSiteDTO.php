<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class DoctorBusinessSiteDTO
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public int $consultationDuration;

    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual(0)]
    public int $consultationFee;

    #[Assert\NotNull]
    #[Assert\Type('array')]
    public array $workingSchedule;
}
