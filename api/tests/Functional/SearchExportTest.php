<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Person;
use App\Entity\SavedSearch;
use App\Entity\SearchExport;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Export CSV d'une SÉLECTION de profils (tâche 151, ex-tâche 077) : tracé, gratuit, précédé d'un
 * accusé RGPD, et borné au jeu de résultats de la recherche.
 */
final class SearchExportTest extends ApiTestCase
{
    private const string OWNER = 'atlas.owner@demo.showcase-recruiter-webapp-saas.example';
    private const string MEMBER = 'atlas.member@demo.showcase-recruiter-webapp-saas.example';

    public function testExportReturnsExcelReadableCsv(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->sharedSearchId();

        $this->request('POST', "/v1/searches/{$searchId}/export", $token, [
            'candidateIds' => [$this->candidateId('Tazi')],
            'gdprAcknowledged' => true,
        ]);
        self::assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $csv = (string) $response->getContent();

        self::assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        self::assertStringContainsString(
            'filename="profils-finance-france-vers-casablanca.csv"',
            (string) $response->headers->get('Content-Disposition'),
        );

        // BOM UTF-8 + séparateur `;` : Excel FR ouvre le fichier sans manipulation.
        self::assertStringStartsWith("\u{FEFF}", $csv);
        self::assertStringContainsString('Nom;Poste;E-mail;Téléphone;Ville;Pays;Présence Maroc', $csv);

        // Tazi est dévoilée dans les fixtures : ses coordonnées sortent en clair.
        self::assertStringContainsString('Imane Tazi', $csv);
        self::assertStringContainsString('imane@example.com', $csv);
    }

    public function testExportWithoutGdprAcknowledgementIsRefused(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->sharedSearchId();
        $selection = [$this->candidateId('Tazi')];

        $this->request('POST', "/v1/searches/{$searchId}/export", $token, ['candidateIds' => $selection]);
        self::assertResponseStatusCodeSame(422);

        $this->request('POST', "/v1/searches/{$searchId}/export", $token, [
            'candidateIds' => $selection,
            'gdprAcknowledged' => false,
        ]);
        self::assertResponseStatusCodeSame(422);

        self::assertSame(0, $this->exportCount(), 'Un export refusé ne laisse aucune trace.');
    }

    public function testExportWithoutSelectionIsRefused(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', "/v1/searches/{$this->sharedSearchId()}/export", $token, [
            'candidateIds' => [],
            'gdprAcknowledged' => true,
        ]);

        // On n'exporte jamais « tout » implicitement : ce qui sort est ce qu'on a désigné.
        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->exportCount());
    }

    public function testExportIsJournalized(): void
    {
        $token = $this->loginToken(self::OWNER);

        $this->request('POST', "/v1/searches/{$this->sharedSearchId()}/export", $token, [
            'candidateIds' => [$this->candidateId('Tazi')],
            'gdprAcknowledged' => true,
        ]);
        self::assertResponseIsSuccessful();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $export = $em->getRepository(SearchExport::class)->findOneBy([]);
        self::assertInstanceOf(SearchExport::class, $export);

        self::assertSame(1, $export->candidateCount);
        self::assertSame('Omar Alaoui', trim(
            $export->exportedBy->getPerson()->givenName.' '.$export->exportedBy->getPerson()->familyName,
        ));
        self::assertCount(1, $export->candidateUuids, 'Le journal fige les profils réellement sortis.');
    }

    public function testExportDoesNotChargeAnyCredit(): void
    {
        $token = $this->loginToken(self::OWNER);
        $searchId = $this->sharedSearchId();
        $organizationId = $this->organizationId();

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        $before = $this->jsonResponse()['balance'];

        $this->request('POST', "/v1/searches/{$searchId}/export", $token, [
            'candidateIds' => [$this->candidateId('Tazi')],
            'gdprAcknowledged' => true,
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame('0', $this->client->getResponse()->headers->get('X-Credits-Charged'));

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame($before, $this->jsonResponse()['balance'], "L'export ne débite jamais de crédit.");
    }

    public function testNonAuthorCannotExportSharedSearch(): void
    {
        $token = $this->loginToken(self::MEMBER);

        $this->request('POST', "/v1/searches/{$this->sharedSearchId()}/export", $token, [
            'candidateIds' => [$this->candidateId('Tazi')],
            'gdprAcknowledged' => true,
        ]);

        // Une recherche partagée est en lecture seule : un destinataire duplique s'il veut exporter.
        self::assertResponseStatusCodeSame(403);
    }

    public function testSelectingAnUnrevealedProfileFailsTheExport(): void
    {
        $token = $this->loginToken(self::OWNER);

        // El Amrani est dans le jeu de résultats et favori, mais NON dévoilé.
        $this->request('POST', "/v1/searches/{$this->sharedSearchId()}/export", $token, [
            'candidateIds' => [$this->candidateId('El Amrani')],
            'gdprAcknowledged' => true,
        ]);

        // Refus NOMMÉ plutôt que filtrage silencieux : un fichier amputé ne se remarque pas.
        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->exportCount());
    }

    public function testSelectingAProfileOutsideTheSearchFailsTheExport(): void
    {
        $token = $this->loginToken(self::OWNER);

        // Idrissi existe dans le vivier mais n'appartient pas au jeu de résultats de la recherche :
        // sans ce garde-fou, l'export serait une porte de sortie universelle.
        $this->request('POST', "/v1/searches/{$this->sharedSearchId()}/export", $token, [
            'candidateIds' => [$this->candidateId('Idrissi')],
            'gdprAcknowledged' => true,
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->exportCount());
    }

    private function exportCount(): int
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);

        return \count($em->getRepository(SearchExport::class)->findAll());
    }

    private function candidateId(string $familyName): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => $familyName]);
        self::assertInstanceOf(Person::class, $person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertInstanceOf(Candidate::class, $candidate);

        return $candidate->getUuid()->toString();
    }

    private function sharedSearchId(): string
    {
        return $this->demoSearch()->getUuid()->toString();
    }

    private function organizationId(): string
    {
        return $this->demoSearch()->organization->getUuid()->toString();
    }

    private function demoSearch(): SavedSearch
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $search = $em->getRepository(SavedSearch::class)->findOneBy(['name' => 'Finance France vers Casablanca']);
        self::assertInstanceOf(SavedSearch::class, $search);

        return $search;
    }
}
