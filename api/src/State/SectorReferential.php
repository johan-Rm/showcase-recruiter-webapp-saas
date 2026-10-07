<?php

declare(strict_types=1);

namespace App\State;

use Symfony\Component\Yaml\Yaml;

/**
 * Référentiel des secteurs d'activité — liste fermée, chargée depuis `config/referentiels/secteurs.yaml`.
 *
 * Source de vérité UNIQUE : la même liste valide l'entrée (Assert\Choice sur OrganizationInput) et
 * alimente le front (SectorController). La valeur métier EST le libellé français du YAML (pas de code
 * séparé), continu avec les données de recherche existantes qui emploient déjà « Informatique »…
 *
 * Placé dans `State` et non dans `Domain` : c'est le seul layer que `ApiResource` (pour la validation)
 * ET `Controller` (pour l'endpoint) peuvent tous deux atteindre (cf. deptrac). `labels()` est statique
 * pour servir de `callback` à Assert\Choice, qui n'accepte pas de service injecté.
 */
final class SectorReferential
{
    /** @var list<string>|null Mémoïsé : Assert\Choice peut rappeler le callback, on ne relit pas le fichier. */
    private static ?array $labels = null;

    /**
     * @return list<string>
     */
    public static function labels(): array
    {
        if (null !== self::$labels) {
            return self::$labels;
        }

        // Chemin relatif à ce fichier (api/src/State) : deux niveaux au-dessus = la racine `api/`.
        $path = \dirname(__DIR__, 2).'/config/referentiels/secteurs.yaml';

        /** @var array{secteurs?: list<string>} $data */
        $data = Yaml::parseFile($path);

        return self::$labels = $data['secteurs'] ?? [];
    }
}
