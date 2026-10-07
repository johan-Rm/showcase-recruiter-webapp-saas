<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Nomenclature FERMÉE des secteurs d'activité, déduits du nom de l'employeur (le secteur n'est
 * fourni par aucune source — cf. task 105). Liste de travail (~18) ajustable produit ; `INDEPENDANT`
 * absorbe freelance / indépendant / auto-entrepreneur, `AUTRE` le reste non classable.
 */
enum Sector: string
{
    case CONSEIL_AUDIT = 'CONSEIL_AUDIT';
    case INFORMATIQUE = 'INFORMATIQUE';
    case INDUSTRIE = 'INDUSTRIE';
    case BANQUE_FINANCE = 'BANQUE_FINANCE';
    case ASSURANCE = 'ASSURANCE';
    case TELECOM = 'TELECOM';
    case TRANSPORT_LOGISTIQUE = 'TRANSPORT_LOGISTIQUE';
    case ENERGIE = 'ENERGIE';
    case AERONAUTIQUE = 'AERONAUTIQUE';
    case AUTOMOBILE = 'AUTOMOBILE';
    case SANTE_PHARMA = 'SANTE_PHARMA';
    case DISTRIBUTION = 'DISTRIBUTION';
    case BTP = 'BTP';
    case AGROALIMENTAIRE = 'AGROALIMENTAIRE';
    case PUBLIC_ADMINISTRATION = 'PUBLIC_ADMINISTRATION';
    case EDUCATION = 'EDUCATION';
    case INDEPENDANT = 'INDEPENDANT';
    case AUTRE = 'AUTRE';
}
