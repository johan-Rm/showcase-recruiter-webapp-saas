<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

/**
 * Les nomenclatures fermées que le webapp affiche en listes de choix (niveau d'études, disponibilité,
 * horizon de retour, niveau de langue).
 *
 * POURQUOI ICI — ces énumérations n'atteignaient le contrat que par accident : un Input qui les type
 * les fait apparaître dans l'OpenAPI, un Output qui les sert en `string` ne les montre pas. Trois des
 * quatre étaient donc absentes du contrat, et le front les recopiait à la main — une liste recopiée
 * dérive de sa source au premier ajout de valeur.
 *
 * VALEURS SEULEMENT, pas de libellés : ceux-ci vivent dans l'i18n du front (`search.education.*`), ce
 * qui garde cette réponse indépendante de la locale — donc la même pour tout le monde, et cachable.
 *
 * L'ORDRE EST SIGNIFIANT : celui des `case` de chaque enum, qui est celui des rangs (BAC → DOCTORAT,
 * ELEMENTARY → NATIVE_OR_BILINGUAL). Le front l'affiche tel quel, il ne retrie pas.
 */
final class ConfigReferentialsOutput
{
    /**
     * @param list<string> $educationLevels
     * @param list<string> $availabilities
     * @param list<string> $returnHorizons
     * @param list<string> $languageProficiencies
     */
    public function __construct(
        public array $educationLevels,
        public array $availabilities,
        public array $returnHorizons,
        public array $languageProficiencies,
    ) {
    }
}
