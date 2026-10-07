<?php

declare(strict_types=1);

namespace App\ApiResource\Input;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Payload d'export d'une sélection de profils (task 154, ex-task 077).
 *
 * `candidateIds` est la sélection de la Table : une ligne ou un lot, jamais « tout » implicitement —
 * ce qui part de la plateforme est ce qu'on a désigné.
 *
 * `gdprAcknowledged` est l'accusé RGPD : de la donnée personnelle dévoilée SORT de la plateforme. Il
 * est exigé à CHAQUE export et journalisé avec lui — l'accusé prouve qui a reconnu quoi, pour cet
 * export-là. Sans lui, 422.
 */
final class SearchExportInput
{
    /**
     * @var list<string>
     */
    #[Assert\NotBlank(message: 'Sélectionnez au moins un profil à exporter.')]
    #[Assert\All([new Assert\Uuid()])]
    public array $candidateIds = [];

    #[Assert\NotNull(message: "L'accusé RGPD est obligatoire pour exporter des profils.")]
    #[Assert\IsTrue(message: "L'accusé RGPD est obligatoire pour exporter des profils.")]
    public ?bool $gdprAcknowledged = null;
}
