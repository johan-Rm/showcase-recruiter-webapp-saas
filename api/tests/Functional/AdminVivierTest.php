<?php

declare(strict_types=1);

namespace App\Tests\Functional;

/**
 * Santé du vivier côté admin (tâche 119) : agrégats en lecture seule, gardés ROLE_ADMIN.
 *
 * L'invariant qui compte : les chiffres viennent de `QualificationReporter`, la même source que
 * `app:qualification:report`. Écran et CLI ne peuvent pas diverger.
 */
final class AdminVivierTest extends ApiTestCase
{
    public function testAdminOnly(): void
    {
        $this->request('GET', '/v1/admin/vivier');
        self::assertResponseStatusCodeSame(401);

        $this->request('GET', '/v1/admin/vivier', $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example'));
        self::assertResponseStatusCodeSame(403);
    }

    public function testReturnsSnapshotAndDistributions(): void
    {
        $this->request('GET', '/v1/admin/vivier', $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example'));
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();

        // Les cinq sections du rapport CLI, structure identique.
        self::assertArrayHasKey('Vivier', $payload['snapshot']);
        self::assertArrayHasKey('Référentiels', $payload['snapshot']);
        self::assertIsInt($payload['snapshot']['Vivier']['Profils actifs']);

        // Distributions : trois axes, chaque entrée porte label + count.
        self::assertArrayHasKey('sectors', $payload['distributions']);
        self::assertArrayHasKey('jobs', $payload['distributions']);
        self::assertArrayHasKey('languages', $payload['distributions']);
        foreach ($payload['distributions']['sectors'] as $row) {
            self::assertArrayHasKey('label', $row);
            self::assertArrayHasKey('count', $row);
        }

        // Taux de remplissage : une entrée par champ, chacune porte key + filled + total cohérents.
        self::assertNotEmpty($payload['fillRate']);
        foreach ($payload['fillRate'] as $row) {
            self::assertArrayHasKey('key', $row);
            self::assertIsInt($row['filled']);
            self::assertIsInt($row['total']);
            self::assertLessThanOrEqual($row['total'], $row['filled'], 'un rempli ne dépasse jamais le total');
        }

        self::assertNotEmpty($payload['generatedAt'], 'le taux est daté — un chiffre sans date ment vite');
    }
}
