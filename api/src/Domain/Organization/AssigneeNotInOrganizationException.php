<?php

declare(strict_types=1);

namespace App\Domain\Organization;

use DomainException;

final class AssigneeNotInOrganizationException extends DomainException
{
}
