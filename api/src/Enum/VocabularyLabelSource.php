<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Provenance d'un libellé : d'où vient ce terme. Sert la maintenance et les purges (on peut
 * rejouer une source sans toucher aux autres). `JARVI` = récolté dans le vivier ; `LLM` = fusion
 * de synonymes proposée par le modèle ; `ROME`/`ESCO` = importé d'un référentiel ; `MANUEL` = saisi.
 */
enum VocabularyLabelSource: string
{
    case JARVI = 'JARVI';
    case LLM = 'LLM';
    case ROME = 'ROME';
    case ESCO = 'ESCO';
    case MANUEL = 'MANUEL';
}
