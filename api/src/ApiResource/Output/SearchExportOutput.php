<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\ApiResource\Input\SearchExportInput;
use App\State\Processor\SearchExportProcessor;

/**
 * Export CSV d'une sélection de profils d'une recherche (task 154, ex-task 077).
 *
 * On exporte ce qu'on a COCHÉ dans la Table — une ligne ou un lot —, plus une shortlist : le produit
 * n'a plus qu'une liste de rétention, les favoris, et ils se posent avant de payer. L'export, lui,
 * ne porte que sur des profils dévoilés : sans coordonnées, la ligne exportée ne sert à rien.
 *
 * AUCUN crédit n'est débité : la donnée a déjà été payée au dévoilement.
 *
 * Réservé à l'AUTEUR de la recherche — un destinataire d'une recherche partagée la duplique s'il
 * veut exporter la sienne.
 */
#[ApiResource(
    shortName: 'SearchExport',
    operations: [
        // Le processor rend un FICHIER, pas du JSON — API Platform laisse passer une Response.
        // L'opération reste décrite dans l'OpenAPI : elle est au contrat.
        new Post(
            uriTemplate: '/searches/{searchId}/export',
            uriVariables: ['searchId'],
            input: SearchExportInput::class,
            output: false,
            read: false,
            processor: SearchExportProcessor::class,
        ),
    ],
)]
final class SearchExportOutput
{
}
