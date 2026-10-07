<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Put;
use App\ApiResource\Input\ChangePasswordInput;
use App\State\Processor\ChangePasswordProcessor;

#[ApiResource(
    shortName: 'ChangePassword',
    operations: [
        new Put(
            uriTemplate: '/me/password',
            input: ChangePasswordInput::class,
            processor: ChangePasswordProcessor::class,
            read: false,
        ),
    ],
)]
final class ChangePasswordOutput
{
    public function __construct(public bool $passwordChanged = true)
    {
    }
}
