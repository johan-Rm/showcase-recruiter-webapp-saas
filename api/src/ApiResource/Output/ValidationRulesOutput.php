<?php

declare(strict_types=1);

namespace App\ApiResource\Output;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Provider\ValidationRulesProvider;

/**
 * Règles de validation d'un payload d'entrée API, dérivées des contraintes Symfony Validator.
 * Sert au webapp pour éviter de recopier les règles simples et croisées côté frontend.
 */
#[ApiResource(
    shortName: 'ValidationRules',
    operations: [
        new Get(
            uriTemplate: '/validation-rules/{form}',
            uriVariables: ['form'],
            provider: ValidationRulesProvider::class,
        ),
    ],
)]
final class ValidationRulesOutput
{
    /**
     * @param array<string, list<array<string, mixed>>> $fields
     * @param list<array<string, mixed>>                $classRules
     */
    public function __construct(
        public string $form,
        public string $inputClass,
        public array $fields,
        public array $classRules = [],
    ) {
    }
}
