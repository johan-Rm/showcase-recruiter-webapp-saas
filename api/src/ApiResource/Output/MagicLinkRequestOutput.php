<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\MagicLinkConsumeInput;
use App\ApiResource\Input\MagicLinkRequestInput;
use App\ApiResource\Input\SocialMagicLinkInput;
use App\State\Processor\MagicLinkConsumeProcessor;
use App\State\Processor\MagicLinkRequestProcessor;
use App\State\Processor\SocialMagicLinkProcessor;

#[ApiResource(
    shortName: 'MagicLink',
    operations: [
        new Post(
            uriTemplate: '/auth/magic-link/request',
            input: MagicLinkRequestInput::class,
            processor: MagicLinkRequestProcessor::class,
            read: false,
        ),
        // Social login : vérifie un id_token OIDC et, s'il est prouvé, émet le MÊME magic link. 202
        // (accepté, traitement asynchrone du point de vue appelant) : rien n'est renvoyé qu'un accusé,
        // l'accès viendra par l'e-mail. Sortie partagée avec la demande classique — un seul écran côté BFF.
        new Post(
            uriTemplate: '/auth/social/magic-link',
            status: 202,
            input: SocialMagicLinkInput::class,
            processor: SocialMagicLinkProcessor::class,
            read: false,
        ),
        new Post(
            uriTemplate: '/auth/magic-link/consume',
            input: MagicLinkConsumeInput::class,
            output: MagicLinkConsumeOutput::class,
            processor: MagicLinkConsumeProcessor::class,
            read: false,
        ),
    ],
)]
final class MagicLinkRequestOutput
{
    public function __construct(public bool $sent = true)
    {
    }
}
