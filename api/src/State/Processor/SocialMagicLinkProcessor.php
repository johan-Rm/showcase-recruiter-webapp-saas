<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\SocialMagicLinkInput;
use App\ApiResource\Output\MagicLinkRequestOutput;
use App\Domain\Auth\MagicLinkIssuer;
use App\Domain\Auth\SocialIdentityException;
use App\Domain\Auth\SocialIdentityVerifier;
use App\Enum\MagicLinkAudience;
use App\Enum\SocialProvider;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Social login → magic link. On vérifie l'`id_token`, et à cette SEULE condition on émet un magic
 * link vers l'e-mail vérifié. Aucune session n'est ouverte ici : le social login est un raccourci de
 * saisie d'e-mail, pas une porte d'authentification (cf. tâche 081).
 *
 * La réponse est identique à `magic-link/request` — le BFF a un seul chemin, et l'écran « e-mail
 * envoyé » est le même. Un `id_token` qui ne se prouve pas donne 401, sans révéler LEQUEL des
 * contrôles a échoué. Un e-mail inconnu, lui, ne change rien : l'émetteur envoie le lien sans créer de
 * compte, exactement comme la demande classique — la route n'est pas un oracle de comptes.
 *
 * @implements ProcessorInterface<SocialMagicLinkInput, MagicLinkRequestOutput>
 */
final readonly class SocialMagicLinkProcessor implements ProcessorInterface
{
    public function __construct(
        private SocialIdentityVerifier $verifier,
        private MagicLinkIssuer $issuer,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MagicLinkRequestOutput
    {
        $provider = SocialProvider::from($data->provider);

        try {
            $email = $this->verifier->verifiedEmail($provider, $data->idToken);
        } catch (SocialIdentityException $e) {
            // Message générique : l'appelant n'apprend pas quel contrôle a échoué. Le détail est tracé
            // côté serveur (HttpSocialJwks), pas renvoyé.
            throw new UnauthorizedHttpException('Bearer', 'Identité sociale non vérifiée.', $e);
        }

        $this->issuer->request($email, MagicLinkAudience::tryFrom($data->space ?? ''));

        return new MagicLinkRequestOutput();
    }
}
