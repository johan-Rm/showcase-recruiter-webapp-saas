<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\Output\AdminCandidateDetailOutput;
use App\ApiResource\Output\AdminCandidateOutput;
use App\ApiResource\Output\CandidateCardOutput;
use App\ApiResource\Output\CandidateOutput;
use App\ApiResource\Output\CriterionMatchOutput;
use App\Domain\Candidate\MoroccoPresence;
use App\Domain\Candidate\RevealableContent;
use App\Domain\Candidate\RevealedAccess;
use App\Domain\Jarvi\JarviContacts;
use App\Domain\Jarvi\WhatsAppIdentifier;
use App\Domain\Search\CriterionMatch;
use App\Entity\Candidate;
use DateTimeImmutable;

/**
 * Mapping Candidate → Output DTO plat, avec masquage des données sensibles tant que le profil
 * n'a pas payé l'accès correspondant (`$access`) et résolution i18n des libellés de référentiels
 * (fallback FR).
 *
 * Ligne de partage du masquage (task API-018) : on masque ce qui permet d'ATTEINDRE la personne —
 * e-mail, téléphone, identifiant WhatsApp —, jamais ce qui permet de la LIRE. Identité, photo,
 * employeur actuel, employeurs du parcours et profil LinkedIn se servent donc en clair : on paie
 * pour joindre, plus pour savoir qui l'on regarde.
 *
 * Chaque canal a SON accès : payer l'e-mail n'ouvre pas le téléphone. Le salaire, lui, n'est plus
 * exposé du tout — la notion est retirée du produit ; la donnée reste en base.
 */
final class CandidateMapper
{
    /** Aperçu carte : compétences plafonnées ; la liste complète (dump Jarvi) vit dans le détail. */
    private const int CARD_SKILLS_LIMIT = 12;

    /**
     * L'état de travail (`isNew`, `isFavorite`) et la correspondance aux critères restent à `null`
     * par défaut : ils n'existent que DANS une recherche, et le moteur seul n'en connaît aucune.
     *
     * @param list<CriterionMatch>|null $matches
     */
    public static function toCard(
        Candidate $candidate,
        RevealedAccess $access,
        ?bool $isNew = null,
        ?bool $isFavorite = null,
        ?array $matches = null,
        ?bool $isSeen = null,
        ?bool $isDismissed = null,
    ): CandidateCardOutput {
        $person = $candidate->getPerson();
        // Réplica pro toujours présente (shell créé avec la projection), vide tant que non ingérée.
        $jarvi = $candidate->getJarvi();
        $email = self::email($candidate);
        $phone = self::phone($candidate);

        return new CandidateCardOutput(
            $candidate->getUuid()->toString(),
            // L'IDENTITÉ EST GRATUITE (task API-018) : on paie pour ATTEINDRE quelqu'un, pas pour
            // savoir qui il est. Le nom et la photo se servent donc en clair, toujours.
            $person->givenName,
            $person->familyName,
            $jarvi->photoUrl,
            $jarvi->headline,
            $jarvi->currentTitle,
            $jarvi->experienceYears,
            $candidate->educationLevel?->value,
            $person->city,
            $person->country,
            // Chaque canal derrière SON accès : payer l'e-mail n'ouvre pas le téléphone.
            $access->email ? $email : null,
            $access->phone ? $phone : null,
            null !== $email,
            null !== $phone,
            RevealableContent::hasResume($candidate),
            RevealableContent::hasReport($candidate),
            $jarvi->lastInteractionAt,
            $jarvi->lastInteractionType,
            $candidate->desiredSector,
            $candidate->desiredFunction,
            $candidate->availability?->value,
            $candidate->returnHorizon?->value,
            $candidate->returnProject,
            $candidate->geoMobility,
            $candidate->hasPiedATerre,
            $candidate->piedATerreCity,
            self::presencePeriods($candidate),
            MoroccoPresence::isPresentOn($candidate, new DateTimeImmutable('today')),
            \array_slice($jarvi->skills, 0, self::CARD_SKILLS_LIMIT),
            self::spokenLanguages($candidate),
            // Les SECTEURS SEULS, sans leur durée : la carte sert à filtrer et à reconnaître, le
            // détail des mois se lit sur le profil. Ils gardent l'ordre du helper — le plus ancien
            // en tête —, ce qui met le secteur dominant en premier là où l'écran n'en montre qu'un.
            array_column(self::sectorExperiences($candidate), 'sector'),
            null !== $candidate->starInterviewedAt,
            $access->email,
            $access->phone,
            $access->resume,
            $access->report,
            $candidate->getCreatedAt(),
            $candidate->getUpdatedAt(),
            $isNew,
            $isFavorite,
            $isSeen,
            $isDismissed,
            null === $matches ? null : array_map(self::toCriterionMatch(...), $matches),
        );
    }

    /**
     * La traduction d'une correspondance vers sa forme publiée.
     *
     * Nommée parce qu'elle sert à DEUX endroits — la fiche d'un jeu de résultats et le plan de
     * travail. Recopiée, elle donnerait deux vérités sur la même traduction, et la prochaine
     * évolution n'en corrigerait qu'une. C'est ici que les énumérations du domaine deviennent des
     * chaînes : le contrat n'expose pas de type PHP.
     */
    public static function toCriterionMatch(CriterionMatch $match): CriterionMatchOutput
    {
        return new CriterionMatchOutput(
            $match->facet,
            $match->value,
            $match->matched,
            $match->matchedOn,
            $match->matchedWhen?->value,
            $match->matchedIn?->value,
        );
    }

    public static function toDetail(Candidate $candidate, RevealedAccess $access): CandidateOutput
    {
        $person = $candidate->getPerson();
        // Réplica pro toujours présente (shell créé avec la projection), vide tant que non ingérée.
        // Profil pro = réplica JSON opaque Jarvi (lecture seule). Compétences = liste de noms,
        // langues = liste d'objets { language, proficiency } telle qu'ingérée.
        $jarvi = $candidate->getJarvi();
        $skills = $jarvi->skills;
        $languages = $jarvi->languages;
        $email = self::email($candidate);
        $phone = self::phone($candidate);

        return new CandidateOutput(
            $candidate->getUuid()->toString(),
            // Même règle que sur la carte : l'identité est gratuite, l'employeur et le profil
            // LinkedIn avec elle.
            $person->givenName,
            $person->familyName,
            $jarvi->photoUrl,
            $jarvi->headline,
            $jarvi->summary,
            $jarvi->experienceYears,
            $jarvi->currentPositionCertainty->value,
            $jarvi->currentTitle,
            $jarvi->currentCompanyName,
            $jarvi->currentJobConcept?->prefLabel,
            $person->city,
            $person->country,
            $access->email ? $email : null,
            $access->phone ? $phone : null,
            // WhatsApp suit le TÉLÉPHONE et jamais l'identité : son identifiant contient le numéro
            // en clair (`33600000000@s.whatsapp.net`). Le libérer donnerait gratuitement ce qu'on
            // fait payer sous `PHONE` — il suffirait de lire l'identifiant.
            $access->phone ? WhatsAppIdentifier::fromContacts($jarvi->contacts) : null,
            self::linkedinUrl($candidate),
            null !== $email,
            null !== $phone,
            RevealableContent::hasResume($candidate),
            RevealableContent::hasReport($candidate),
            $jarvi->lastInteractionAt,
            $jarvi->lastInteractionType,
            $candidate->desiredSector,
            $candidate->desiredFunction,
            $candidate->availability?->value,
            $candidate->returnHorizon?->value,
            $candidate->geoMobility,
            $skills,
            $languages,
            self::jobExperiences($candidate),
            self::sectorExperiences($candidate),
            self::spokenLanguages($candidate),
            // Les employeurs du parcours ne sont plus masqués : ils font partie de ce qu'on donne
            // à lire pour donner envie de payer le contact.
            $jarvi->workExperiences,
            $jarvi->educations,
            $candidate->educationLevel?->value,
            $access->resume ? $jarvi->resumes : null,
            self::presencePeriods($candidate),
            null !== $candidate->starInterviewedAt,
            $candidate->starInterviewedAt,
            $access->report ? $candidate->starInterviewReport : null,
            $access->email,
            $access->phone,
            $access->resume,
            $access->report,
            $candidate->getCreatedAt(),
            $candidate->getUpdatedAt(),
        );
    }

    /**
     * Vue ADMIN : tout en clair (opérateur de la plateforme) + champs d'exploitation (visibilité,
     * activité, consentement, synchro). Réplica Jarvi toujours présente (shell créé avec la
     * projection), comme dans toCard/toDetail.
     */
    public static function toAdmin(Candidate $candidate): AdminCandidateOutput
    {
        $person = $candidate->getPerson();
        $jarvi = $candidate->getJarvi();

        return new AdminCandidateOutput(
            $candidate->getUuid()->toString(),
            $person->givenName,
            $person->familyName,
            $jarvi->photoUrl,
            $jarvi->headline,
            $jarvi->experienceYears,
            $person->city,
            $person->country,
            self::email($candidate),
            self::phone($candidate),
            self::linkedinUrl($candidate),
            $candidate->desiredSector,
            $candidate->desiredFunction,
            $candidate->availability?->value,
            $candidate->returnHorizon?->value,
            $candidate->geoMobility,
            $candidate->hasPiedATerre,
            $candidate->piedATerreCity,
            \array_slice($jarvi->skills, 0, self::CARD_SKILLS_LIMIT),
            null !== $candidate->starInterviewedAt,
            $candidate->visibility->value,
            $candidate->active,
            $candidate->hasConsent(),
            $candidate->consentGivenAt,
            $candidate->syncStatus->value,
            $candidate->getCreatedAt(),
            $candidate->getUpdatedAt(),
        );
    }

    /**
     * Fiche ADMIN détaillée : profil complet non masqué (employeurs, CV, compte rendu en clair) +
     * réplica pro Jarvi + champs d'exploitation. Réplica toujours présente (shell créé avec la projection).
     */
    public static function toAdminDetail(Candidate $candidate): AdminCandidateDetailOutput
    {
        $person = $candidate->getPerson();
        $jarvi = $candidate->getJarvi();

        return new AdminCandidateDetailOutput(
            $candidate->getUuid()->toString(),
            $person->givenName,
            $person->familyName,
            $jarvi->photoUrl,
            $jarvi->headline,
            $jarvi->summary,
            $jarvi->experienceYears,
            $person->city,
            $person->country,
            self::email($candidate),
            self::phone($candidate),
            WhatsAppIdentifier::fromContacts($jarvi->contacts),
            self::linkedinUrl($candidate),
            $candidate->desiredSector,
            $candidate->desiredFunction,
            $candidate->availability?->value,
            $candidate->returnHorizon?->value,
            $candidate->returnProject,
            $candidate->geoMobility,
            $candidate->hasPiedATerre,
            $candidate->piedATerreCity,
            $jarvi->skills,
            $jarvi->languages,
            $jarvi->workExperiences,
            $jarvi->educations,
            $jarvi->resumes,
            self::presencePeriods($candidate),
            null !== $candidate->starInterviewedAt,
            $candidate->starInterviewedAt,
            $candidate->starInterviewReport,
            $candidate->visibility->value,
            $candidate->active,
            $candidate->hasConsent(),
            $candidate->consentGivenAt,
            $candidate->consentSource?->value,
            $candidate->syncStatus->value,
            $candidate->jarviProfileId->toString(),
            $candidate->getCreatedAt(),
            $candidate->getUpdatedAt(),
        );
    }

    /**
     * Coordonnées : la même lecture que le dévoilement, jamais une seconde.
     *
     * Ce que la carte annonce comme joignable et ce que le serveur accepte de facturer doivent être
     * la même chose. Recopiée ici, la résolution `Person` puis réplica Jarvi divergerait sans qu'un
     * test pris isolément ne le voie : l'écran éteindrait un canal facturable, ou l'inverse.
     */
    private static function email(Candidate $candidate): ?string
    {
        return RevealableContent::email($candidate);
    }

    private static function phone(Candidate $candidate): ?string
    {
        return RevealableContent::phone($candidate);
    }

    private static function linkedinUrl(Candidate $candidate): ?string
    {
        return $candidate->getPerson()->linkedinUrl ?? JarviContacts::linkedinUrl($candidate->getJarvi()->rawPayload);
    }

    /** @return list<array{startDate: string, endDate: string, city: ?string}> */
    /**
     * Métiers exercés, du plus long au plus court. Lecture de la table dérivée — jamais un rescan
     * des `workExperiences` JSON, que l'ingestion a déjà agrégés.
     *
     * @return list<array{job: string, months: int, positions: int, exactPositions: int}>
     */
    private static function jobExperiences(Candidate $candidate): array
    {
        $rows = [];
        foreach ($candidate->getJobExperiences() as $experience) {
            $rows[] = [
                'job' => $experience->concept->prefLabel,
                'months' => $experience->months,
                'positions' => $experience->positions,
                'exactPositions' => $experience->exactPositions,
            ];
        }
        usort($rows, static fn (array $a, array $b): int => $b['months'] <=> $a['months']);

        return $rows;
    }

    /**
     * @return list<array{sector: string, months: int}>
     */
    private static function sectorExperiences(Candidate $candidate): array
    {
        $rows = [];
        foreach ($candidate->getSectorExperiences() as $experience) {
            $rows[] = ['sector' => $experience->sector->value, 'months' => $experience->months];
        }
        usort($rows, static fn (array $a, array $b): int => $b['months'] <=> $a['months']);

        return $rows;
    }

    /**
     * @return list<array{language: string, proficiency: ?string}>
     */
    private static function spokenLanguages(Candidate $candidate): array
    {
        $rows = [];
        foreach ($candidate->getLanguages() as $language) {
            $rows[] = ['language' => $language->language, 'proficiency' => $language->proficiency?->value];
        }

        return $rows;
    }

    private static function presencePeriods(Candidate $candidate): array
    {
        $periods = [];
        foreach ($candidate->getPresencePeriods() as $period) {
            $periods[] = [
                'startDate' => $period->startDate->format('Y-m-d'),
                'endDate' => $period->endDate->format('Y-m-d'),
                'city' => $period->city,
            ];
        }

        return $periods;
    }
}
