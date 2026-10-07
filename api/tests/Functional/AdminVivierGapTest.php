<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\QualificationGapReason;
use App\Enum\QualificationMetric;

/**
 * Le détail d'un manque de qualification (task API-034), en lecture seule et gardé ROLE_ADMIN.
 *
 * L'invariant qui compte : la somme des motifs égale le manque annoncé par `GET /v1/admin/vivier`,
 * soit `Profils actifs` moins le compteur de la métrique. C'est la seule garantie que le classement
 * n'a perdu personne — un profil sans motif serait invisible, et le nombre affiché mentirait.
 */
final class AdminVivierGapTest extends ApiTestCase
{
    public function testAdminOnly(): void
    {
        $this->request('GET', '/v1/admin/vivier/gaps/experienceYears');
        self::assertResponseStatusCodeSame(401);

        $this->request(
            'GET',
            '/v1/admin/vivier/gaps/experienceYears',
            $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example'),
        );
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * Une métrique inconnue n'est pas une métrique sans manque : confondre les deux ferait passer
     * une faute de frappe pour une bonne nouvelle.
     */
    public function testUnknownMetricIsNotFound(): void
    {
        $this->request('GET', '/v1/admin/vivier/gaps/inexistante', $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example'));
        self::assertResponseStatusCodeSame(404);
    }

    public function testUnknownOrForeignReasonIsNotFound(): void
    {
        $token = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');

        $this->request('GET', '/v1/admin/vivier/gaps/experienceYears?reason=PLOP', $token);
        self::assertResponseStatusCodeSame(404);

        // Motif réel, mais d'une AUTRE métrique : un motif n'a de sens que rapporté à la sienne.
        $this->request('GET', '/v1/admin/vivier/gaps/experienceYears?reason=NO_EDUCATION', $token);
        self::assertResponseStatusCodeSame(404);
    }

    public function testBreakdownSumsToTheCounterGap(): void
    {
        $token = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');

        $this->request('GET', '/v1/admin/vivier', $token);
        self::assertResponseIsSuccessful();
        $snapshot = $this->jsonResponse()['snapshot'];
        $active = $snapshot['Vivier']['Profils actifs'];

        $counters = [
            'experienceYears' => 'Ancienneté renseignée',
            'currentRoleMonths' => 'Durée sur le poste actuel',
            'educationLevel' => 'Niveau d’études dérivé',
            'jarviUpdatedAt' => 'Date de modification source',
        ];

        foreach ($counters as $metric => $label) {
            $this->request('GET', '/v1/admin/vivier/gaps/'.$metric, $token);
            self::assertResponseIsSuccessful();
            $payload = $this->jsonResponse();

            self::assertSame($metric, $payload['metric']);
            self::assertNull($payload['reason'], 'sans filtre, la liste porte tout le manque');

            $counted = array_sum(array_column($payload['breakdown'], 'count'));
            self::assertSame(
                $active - $snapshot['Qualifications du profil'][$label],
                $counted,
                $label.' : la somme des motifs doit égaler le manque du compteur',
            );
            self::assertSame($counted, $payload['total']);

            // Aucun motif étranger à la métrique, et aucune ligne sans motif.
            $allowed = array_map(
                static fn (QualificationGapReason $reason): string => $reason->value,
                QualificationMetric::from($metric)->reasons(),
            );
            foreach ($payload['breakdown'] as $row) {
                self::assertContains($row['reason'], $allowed);
            }
            foreach ($payload['items'] as $item) {
                self::assertContains($item['reason'], $allowed);
                self::assertArrayHasKey('id', $item);
            }
        }
    }

    /**
     * Filtrer restreint la LISTE, jamais la répartition : les compteurs qu'on vient de lire à
     * l'écran ne doivent pas bouger quand on en sélectionne un.
     */
    public function testReasonFilterLeavesTheBreakdownIntact(): void
    {
        $token = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');

        $this->request('GET', '/v1/admin/vivier/gaps/educationLevel', $token);
        self::assertResponseIsSuccessful();
        $whole = $this->jsonResponse();

        if ([] === $whole['breakdown']) {
            self::markTestSkipped('aucun manque de niveau d’études dans les fixtures : rien à filtrer');
        }

        $reason = $whole['breakdown'][0]['reason'];
        $expected = $whole['breakdown'][0]['count'];

        $this->request('GET', '/v1/admin/vivier/gaps/educationLevel?reason='.$reason, $token);
        self::assertResponseIsSuccessful();
        $filtered = $this->jsonResponse();

        self::assertSame($reason, $filtered['reason']);
        self::assertSame($whole['breakdown'], $filtered['breakdown']);
        self::assertSame($expected, $filtered['total']);
        foreach ($filtered['items'] as $item) {
            self::assertSame($reason, $item['reason']);
        }
    }

    /**
     * Deux pages ne se recouvrent pas et n'oublient personne : sans ordre total sur la liste, la
     * pagination reclasserait entre deux appels.
     */
    public function testPaginationIsBoundedAndStable(): void
    {
        $token = $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example');

        $this->request('GET', '/v1/admin/vivier/gaps/educationLevel?page=1&perPage=2', $token);
        self::assertResponseIsSuccessful();
        $first = $this->jsonResponse();
        self::assertSame(1, $first['page']);
        self::assertSame(2, $first['perPage']);
        self::assertLessThanOrEqual(2, \count($first['items']));

        $this->request('GET', '/v1/admin/vivier/gaps/educationLevel?page=2&perPage=2', $token);
        self::assertResponseIsSuccessful();
        $second = $this->jsonResponse();

        self::assertSame(
            [],
            array_intersect(array_column($first['items'], 'id'), array_column($second['items'], 'id')),
            'aucun profil ne doit apparaître sur deux pages',
        );

        // Bornes : le plafond protège la base, et une page zéro n'existe pas.
        $this->request('GET', '/v1/admin/vivier/gaps/educationLevel?page=0&perPage=5000', $token);
        self::assertResponseIsSuccessful();
        $bounded = $this->jsonResponse();
        self::assertSame(1, $bounded['page']);
        self::assertSame(100, $bounded['perPage']);
    }
}
