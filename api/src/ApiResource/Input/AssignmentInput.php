<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'assignation d'un candidat dévoilé à un recruteur responsable.
 */
final class AssignmentInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $assigneeRecruiterId = '';
}
