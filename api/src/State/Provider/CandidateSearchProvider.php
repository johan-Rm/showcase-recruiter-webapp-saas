<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CandidateCardOutput;
use App\ApiResource\Output\CandidateOutput;
use App\ApiResource\Output\CollectionOutput;
use App\Domain\Candidate\RevealedAccess;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Enum\Availability;
use App\Enum\EducationLevel;
use App\Enum\LanguageProficiency;
use App\Enum\ReturnHorizon;
use App\Enum\Sector;
use App\Repository\CandidateRepository;
use App\Repository\CandidateSearchCriteria;
use App\Repository\RevealedProfileRepository;
use App\State\CandidateMapper;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/search/candidates : recherche filtrée du vivier (recruteur). Cartes masquées tant que
 * non dévoilées par l'entreprise du recruteur. Enveloppe plate paginée.
 *
 * `?view=full` : items en profil détaillé (réplica Jarvi incluse) au lieu de la carte allégée —
 * mêmes règles de masquage que GET /candidates/{id}.
 *
 * @implements ProviderInterface<CollectionOutput<CandidateCardOutput|CandidateOutput>>
 */
final readonly class CandidateSearchProvider implements ProviderInterface
{
    public function __construct(
        private CandidateRepository $candidates,
        private RevealedProfileRepository $revealed,
        private Security $security,
        private RequestStack $requestStack,
        private CurrentRecruiterResolver $currentRecruiterResolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveFirstActive($user)
            : null;
        if (!$recruiter instanceof Recruiter || $recruiter->organization->isRejected()) {
            throw new AccessDeniedHttpException();
        }

        $request = $this->requestStack->getCurrentRequest() ?? new Request();
        $criteria = $this->criteria($request);
        $full = 'full' === $request->query->get('view');

        [$items, $total] = $this->candidates->search($criteria);

        $access = $this->revealed->revealedTypesByCandidate($recruiter->organization);

        $cards = array_map(
            static function ($candidate) use ($access, $full): object {
                $granted = RevealedAccess::fromTypes($access[(int) $candidate->getId()] ?? []);

                return $full
                    ? CandidateMapper::toDetail($candidate, $granted)
                    : CandidateMapper::toCard($candidate, $granted);
            },
            $items,
        );

        return new CollectionOutput($cards, $total, $criteria->page, $criteria->perPage);
    }

    private function criteria(Request $request): CandidateSearchCriteria
    {
        return new CandidateSearchCriteria(
            // Les mots-clés se cumulent : `?q[]=php&q[]=java` cherche l'un OU l'autre, exactement
            // comme une recherche enregistrée. C'est la SEULE facette multivaluée du moteur, et cet
            // endpoint ne savait pas la lui donner — un seul terme passait, les autres tombaient.
            //
            // Aucun plafond, délibérément : une recherche enregistrée n'est pas bornée non plus, et
            // borner ici recréerait l'asymétrie que cette task vient de supprimer.
            keywords: self::keywords($request),
            sector: self::nullableString($request->query->get('sector')),
            experienceSector: Sector::tryFrom($request->query->get('experienceSector', '')),
            job: self::nullableString($request->query->get('job')),
            experienceMin: $request->query->has('experienceMin') ? max(0, $request->query->getInt('experienceMin')) : null,
            language: self::nullableString($request->query->get('language')),
            languageLevel: LanguageProficiency::tryFrom($request->query->get('languageLevel', '')),
            function: self::nullableString($request->query->get('function')),
            availability: Availability::tryFrom($request->query->get('availability', '')),
            educationLevel: EducationLevel::tryFrom($request->query->get('educationLevel', '')),
            returnHorizon: ReturnHorizon::tryFrom($request->query->get('returnHorizon', '')),
            geoMobility: self::nullableBool($request->query->get('geoMobility')),
            hasPiedATerre: self::nullableBool($request->query->get('hasPiedATerre')),
            star: self::nullableBool($request->query->get('star')),
            country: self::nullableString($request->query->get('country')),
            presenceWithinDays: self::positiveInt($request->query->get('presenceWithinDays')),
            presenceCity: self::nullableString($request->query->get('presenceCity')),
            page: max(1, $request->query->getInt('page', 1)),
            perPage: max(1, min(2000, $request->query->getInt('perPage', 30))),
        );
    }

    /**
     * Les mots-clés de la requête, vides écartés.
     *
     * DEUX FORMES acceptées, et ce n'est pas un caprice : `?q=php` reste valide — l'endpoint sert
     * aussi une recherche libre à un seul terme —, et `?q[]=php&q[]=java` cumule.
     *
     * **`?q=php&q=java` ne cumulera jamais** : PHP écrase les clés répétées à l'analyse de la
     * requête, bien avant ce code. Seule la notation crochets produit un tableau. On lit donc la
     * valeur brute et on accepte les deux types, plutôt que `all('q')` — qui lève une erreur sur un
     * scalaire et transformerait `?q=php` en réponse 400.
     *
     * Les valeurs non textuelles sont ignorées : une requête mal formée ne doit pas faire tomber la
     * recherche.
     *
     * @return list<string>
     */
    private static function keywords(Request $request): array
    {
        $raw = $request->query->all()['q'] ?? null;

        $terms = [];
        foreach (\is_array($raw) ? $raw : [$raw] as $value) {
            $term = self::nullableString($value);
            if (null !== $term) {
                $terms[] = $term;
            }
        }

        return $terms;
    }

    private static function nullableString(mixed $value): ?string
    {
        return \is_string($value) && '' !== $value ? $value : null;
    }

    private static function nullableBool(mixed $value): ?bool
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        return \in_array($value, ['1', 'true'], true);
    }

    /** Entier strictement positif, sinon null (paramètre ignoré). */
    private static function positiveInt(mixed $value): ?int
    {
        if (!\is_string($value) || !ctype_digit($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
