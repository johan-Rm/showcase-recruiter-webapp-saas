<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use App\Enum\AcquisitionChannel;
use App\State\SectorReferential;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload de création / mise à jour d'une Organization. Champs autorisés en écriture uniquement
 * (protège du mass-assignment : ni balance, ni stripeCustomerId, ni logo ici). Cf. api-structure.md.
 */
final class OrganizationInput
{
    /**
     * Identité du déclarant, demandée à la création (le Magic Link ne capte que l'e-mail). Met à jour
     * le `Person` du déclarant — non éditable via le PUT entreprise (groupe `create` uniquement).
     */
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(max: 100)]
    public string $givenName = '';

    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Length(max: 100)]
    public string $familyName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name = '';

    #[Assert\Length(max: 255)]
    public ?string $legalName = null;

    #[Assert\Length(max: 50)]
    public ?string $taxId = null;

    #[Assert\Length(exactly: 2)]
    public ?string $country = null;

    #[Assert\Length(max: 100)]
    public ?string $city = null;

    public ?string $billingAddress = null;

    #[Assert\Length(max: 255)]
    #[Assert\Url(requireTld: true)]
    public ?string $website = null;

    /**
     * Secteur d'activité : liste FERMÉE (cf. config/referentiels/secteurs.yaml). Le front en fait un
     * dropdown ; `Assert\Choice` referme la porte côté serveur. Null autorisé — `Choice` ignore le
     * null, c'est un `NotBlank` séparé qui l'exigerait si besoin. La valeur EST le libellé français.
     */
    #[Assert\Choice(callback: [SectorReferential::class, 'labels'], message: 'Secteur inconnu.')]
    public ?string $sector = null;

    public ?string $description = null;

    /** Fonction du déclarant dans l'entreprise. Porté par le `Recruiter`, pas par l'`Organization`. */
    #[Assert\Length(max: 255)]
    public ?string $jobTitle = null;

    /**
     * Provenance : **obligatoire** à l'inscription, et **figée** ensuite (le PUT l'ignore) — c'est
     * une donnée d'acquisition, pas une donnée de profil.
     *
     * Obligatoire parce qu'elle compte dans la jauge de l'onboarding : sans elle, la barre ne peut
     * pas atteindre 100 %, donc l'envoi automatique ne part jamais. Le contrat dit ici ce que le
     * produit exige — sinon un autre appelant créerait une entreprise que l'admin ne saurait pas
     * juger (cf. cadrage : « une recommandation nominative est un signal de légitimité »).
     *
     * À ne pas confondre avec `acquisitionReferrer`, qui est MESURÉ et ne la remplace jamais.
     */
    #[Assert\NotBlank(groups: ['create'])]
    #[Assert\Choice(callback: [AcquisitionChannel::class, 'values'], message: 'Provenance inconnue.')]
    public ?string $acquisitionChannel = null;

    #[Assert\Length(max: 255)]
    #[Assert\When(
        expression: 'this.acquisitionChannel == "AUTRE"',
        constraints: [new Assert\NotBlank(message: 'Précisez la provenance.')],
        groups: ['create'],
    )]
    public ?string $acquisitionChannelDetail = null;

    /**
     * Origine d'où le déclarant est ARRIVÉ, mesurée par le front au premier contact — jamais saisie,
     * et **figée** dès l'inscription comme la provenance (le PUT l'ignore).
     *
     * À ne pas confondre avec `acquisitionChannel`, qui est DÉCLARÉ. Les deux ne répondent pas à la
     * même question et ne se remplacent pas : l'un dit comment on nous a connus, l'autre par où l'on
     * est passé. Partiel par nature — vide depuis une application, un client mail ou une URL tapée.
     *
     * `Url` et non une liste : c'est une mesure, pas un choix. On la range telle quelle et on
     * l'interprète à la lecture, plutôt que de refuser ce qu'on n'avait pas prévu.
     */
    #[Assert\Length(max: 255)]
    #[Assert\Url(requireTld: true)]
    public ?string $acquisitionReferrer = null;
}
