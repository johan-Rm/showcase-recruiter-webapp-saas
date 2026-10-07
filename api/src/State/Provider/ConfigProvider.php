<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\ConfigOutput;
use App\ApiResource\Output\ConfigReferentialsOutput;
use App\Domain\Credit\CreditPolicy;
use App\Enum\Availability;
use App\Enum\EducationLevel;
use App\Enum\LanguageProficiency;
use App\Enum\ReturnHorizon;
use App\State\SearchResultsBuilder;
use App\Translation\LocaleResolver;
use BackedEnum;

/**
 * GET /v1/config : valeurs métier publiques (coût d'un dévoilé, crédits offerts, locales,
 * pagination, nomenclatures fermées). Source unique pour le webapp afin d'éviter les constantes
 * dupliquées côté front.
 *
 * @implements ProviderInterface<ConfigOutput>
 */
final readonly class ConfigProvider implements ProviderInterface
{
    /** Taille de page par défaut des collections paginées (cf. api-conventions.md). */
    public const int DEFAULT_PER_PAGE = 30;

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ConfigOutput
    {
        return new ConfigOutput(
            CreditPolicy::REVEAL_COST,
            CreditPolicy::WELCOME_CREDITS,
            LocaleResolver::ENABLED,
            LocaleResolver::SOURCE,
            self::DEFAULT_PER_PAGE,
            SearchResultsBuilder::RECOMMENDED_LIMIT,
            new ConfigReferentialsOutput(
                self::values(EducationLevel::cases()),
                self::values(Availability::cases()),
                self::values(ReturnHorizon::cases()),
                self::values(LanguageProficiency::cases()),
            ),
        );
    }

    /**
     * Les valeurs d'un enum, DANS L'ORDRE DE SES `case` — qui est celui des rangs. Le front affiche
     * la liste telle quelle ; la trier ici casserait « BAC avant DOCTORAT ».
     *
     * @param list<BackedEnum> $cases
     *
     * @return list<string>
     */
    private static function values(array $cases): array
    {
        return array_map(static fn (BackedEnum $case): string => (string) $case->value, $cases);
    }
}
