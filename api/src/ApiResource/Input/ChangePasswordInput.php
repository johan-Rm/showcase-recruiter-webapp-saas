<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangePasswordInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $currentPassword = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $newPassword = '';
}
