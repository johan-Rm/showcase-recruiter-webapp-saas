<?php

declare(strict_types=1);

namespace App\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Soft delete global : toute lecture Doctrine exclut les enregistrements marqués supprimés.
 *
 * Sans ce filtre, `AbstractEntity::softDelete()` ne faisait qu'horodater une colonne : la ligne
 * restait visible partout (listes, recherche, référentiels, relations). Le mécanisme donnait donc
 * l'illusion d'une suppression sans en produire l'effet.
 *
 * S'applique à **toutes** les entités qui portent `deletedAt` (soit toutes, via AbstractEntity), y
 * compris quand elles sont chargées par une relation. Activé par défaut ; un administrateur ou une
 * purge RGPD, qui doivent voir le supprimé, le désactivent explicitement :
 *
 *     $em->getFilters()->disable(SoftDeleteFilter::NAME);
 *
 * ⚠️ Un filtre Doctrine ne s'applique qu'aux requêtes DQL/ORM. Une requête SQL native doit porter
 * elle-même sa clause `deleted_at IS NULL`.
 */
final class SoftDeleteFilter extends SQLFilter
{
    public const string NAME = 'soft_delete';

    private const string FIELD = 'deletedAt';

    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (!$targetEntity->hasField(self::FIELD)) {
            return '';
        }

        return \sprintf('%s.%s IS NULL', $targetTableAlias, $targetEntity->getColumnName(self::FIELD));
    }
}
