<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\PasswordSetupConsumeInput;
use App\State\Processor\PasswordSetupConsumeProcessor;

#[ApiResource(
    shortName: 'PasswordSetup',
    operations: [
        new Post(
            uriTemplate: '/auth/password-setup/consume',
            input: PasswordSetupConsumeInput::class,
            processor: PasswordSetupConsumeProcessor::class,
            read: false,
        ),
    ],
)]
final class PasswordSetupOutput
{
    /**
     * `email` permet au client d'ouvrir une session juste après (le mot de passe vient d'être posé,
     * le token prouve la possession de la boîte) — sans redemander l'adresse à l'utilisateur.
     */
    public function __construct(
        public bool $passwordSet = true,
        public string $email = '',
    ) {
    }
}
