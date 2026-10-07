<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Vocabulary\VocabularyImporter;
use App\Entity\VocabularyConcept;
use App\Enum\VocabularyStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Relecture des référentiels côté admin (tâche 120) : consultation, bascule tracée, et surtout
 * **survie à la réingestion**.
 *
 * Sans cette dernière propriété, l'écran serait un piège : un admin validerait à la souris, et le
 * prochain `app:vocabulary:import` remettrait tout en `candidat` sans un mot.
 */
final class AdminReferentialTest extends ApiTestCase
{
    public function testAdminOnly(): void
    {
        $this->request('GET', '/v1/admin/referentials/METIER');
        self::assertResponseStatusCodeSame(401);

        $this->request('GET', '/v1/admin/referentials/METIER', $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example'));
        self::assertResponseStatusCodeSame(403);
    }

    public function testUnknownReferentialIsNotFound(): void
    {
        $this->request('GET', '/v1/admin/referentials/PLANETE', $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example'));
        self::assertResponseStatusCodeSame(404);
    }

    public function testListingIsSortedByFrequencyAndCarriesLabels(): void
    {
        // Le vocabulaire n'est pas dans les fixtures : on le sème, sinon toutes les fréquences sont
        // à zéro et la couverture cumulée n'a rien à démontrer.
        $suffix = uniqid();
        self::getContainer()->get(VocabularyImporter::class)->import([
            'competences' => ['concepts' => [
                $this->concept('competence-a-'.$suffix, 'Concept fréquent '.$suffix, 40),
                $this->concept('competence-b-'.$suffix, 'Concept rare '.$suffix, 5),
            ]],
            'postes' => ['concepts' => []],
        ]);

        $this->request('GET', '/v1/admin/referentials/COMPETENCE?perPage=3', $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example'));
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertSame('COMPETENCE', $payload['type']);
        self::assertLessThanOrEqual(3, \count($payload['items']));

        $frequencies = array_column($payload['items'], 'frequency');
        $sorted = $frequencies;
        rsort($sorted);
        self::assertSame($sorted, $frequencies, 'tri par fréquence décroissante');

        // La couverture cumulée croît et culmine à 1 : c'est elle qui dit quand s'arrêter de relire.
        $coverage = array_column($payload['items'], 'cumulativeCoverage');
        self::assertGreaterThan(0.0, $coverage[0]);
        self::assertSame($coverage, array_values(array_unique($coverage)), 'strictement croissante');
        self::assertLessThanOrEqual(1.0, end($coverage));

        self::assertNotEmpty($payload['items'][0]['labels'], 'les libellés accompagnent le concept');
    }

    /** Le geste humain est tracé, et un réimport ne l'efface pas. */
    public function testReviewIsTracedAndSurvivesReimport(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $code = 'competence-review-'.uniqid();
        $importer = self::getContainer()->get(VocabularyImporter::class);
        $importer->import($this->taxonomy($code, 'Concept à relire'));
        $em->clear();

        $this->request('POST', '/v1/admin/referentials/reviews', $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example'), [
            'codes' => [$code, 'code-qui-nexiste-pas'],
            'status' => 'VALIDE',
        ]);
        self::assertResponseIsSuccessful();

        $payload = $this->jsonResponse();
        self::assertSame(1, $payload['reviewed']);
        self::assertSame(['code-qui-nexiste-pas'], $payload['unknownCodes'], 'les codes inconnus sont rendus, pas tus');
        self::assertSame('admin@demo.showcase-recruiter-webapp-saas.example', $payload['reviewedBy']);

        // Le fichier dit toujours « candidat » — qui signifie « pas encore relu », pas « rejeté ».
        $importer->import($this->taxonomy($code, 'Concept à relire'));
        $em->clear();

        $concept = $em->getRepository(VocabularyConcept::class)->findOneBy(['code' => $code]);
        self::assertInstanceOf(VocabularyConcept::class, $concept);
        self::assertSame(VocabularyStatus::VALIDE, $concept->status, 'un réimport ne rétrograde jamais');
        self::assertSame('admin@demo.showcase-recruiter-webapp-saas.example', $concept->reviewedBy);
        self::assertNotNull($concept->reviewedAt);
    }

    public function testCandidatIsNotAnAcceptedDecision(): void
    {
        $this->request('POST', '/v1/admin/referentials/reviews', $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example'), [
            'codes' => ['peu-importe'],
            'status' => 'CANDIDAT',
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    /** @return array<string, mixed> */
    private function concept(string $code, string $label, int $frequency): array
    {
        return [
            'id' => $code,
            'label_canonique' => $label,
            'statut' => 'candidat',
            'frequence_totale' => $frequency,
            'synonymes' => [['terme' => 'terme '.$code, 'frequence' => $frequency, 'langue' => 'fr']],
        ];
    }

    /** @return array<string, mixed> */
    private function taxonomy(string $code, string $label): array
    {
        return [
            'competences' => ['concepts' => [[
                'id' => $code,
                'label_canonique' => $label,
                'statut' => 'candidat',
                'frequence_totale' => 3,
                'synonymes' => [['terme' => 'terme '.$code, 'frequence' => 3, 'langue' => 'fr']],
            ]]],
            'postes' => ['concepts' => []],
        ];
    }
}
