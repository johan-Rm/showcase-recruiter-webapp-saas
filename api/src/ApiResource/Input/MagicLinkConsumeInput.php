<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class MagicLinkConsumeInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $token = '';
}
