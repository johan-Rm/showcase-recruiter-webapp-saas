<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\PasswordResetConsumeInput;
use App\ApiResource\Input\PasswordResetRequestInput;
use App\State\Processor\PasswordResetConsumeProcessor;
use App\State\Processor\PasswordResetRequestProcessor;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'PasswordReset',
    operations: [
        new Post(
            uriTemplate: '/auth/password-reset/request',
            status: Response::HTTP_ACCEPTED,
            input: PasswordResetRequestInput::class,
            processor: PasswordResetRequestProcessor::class,
            read: false,
        ),
        new Post(
            uriTemplate: '/auth/password-reset/consume',
            input: PasswordResetConsumeInput::class,
            processor: PasswordResetConsumeProcessor::class,
            read: false,
        ),
    ],
)]
final class PasswordResetOutput
{
    public function __construct(public bool $accepted = true)
    {
    }
}
