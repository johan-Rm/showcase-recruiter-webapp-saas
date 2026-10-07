<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'inscription candidat (parcours public, tâche 015) : crée le compte + la projection.
 * Les différenciants détaillés (présence, prétentions…) sont édités ensuite (tâches 016/017).
 */
final class CandidateOnboardingInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 255)]
    public string $password = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $givenName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $familyName = '';

    // Profil LinkedIn **obligatoire** : identifiant confidentiel servant au rapprochement avec le
    // profil Jarvi importé ou à la création initiale si le profil est absent.
    #[Assert\NotBlank]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    public string $linkedinUrl = '';

    #[Assert\Length(exactly: 2)]
    public ?string $country = null;

    #[Assert\Length(max: 100)]
    public ?string $city = null;

    // CV **optionnel** (PDF), complémentaire du LinkedIn : transmis à Jarvi pour extraction, jamais
    // copié en base showcase-recruiter-webapp-saas. `cvContent` = base64 nu (sans préfixe data-URI). Le contrôle fin
    // (magie PDF, taille décodée) est fait à l'inscription ; la borne ci-dessous protège le payload.
    #[Assert\Length(max: 100)]
    public ?string $cvFileName = null;

    #[Assert\Length(max: 9_500_000)]
    public ?string $cvContent = null;
}
