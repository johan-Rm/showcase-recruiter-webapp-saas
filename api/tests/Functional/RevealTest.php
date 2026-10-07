<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\CreditWallet;
use App\Entity\Organization;
use App\Entity\Person;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Cœur sensible crédits (tâche 005) : dévoilement atomique, idempotent, solde jamais négatif.
 * Entreprise de démo « showcase-recruiter-webapp-saas Demo SARL » (wallet = 2) ; owner@demo en est admin entreprise.
 *
 * **Les dévoilements se comptent en ÉCART, jamais en absolu** (task `API-053`). L'organisation de
 * démo porte un historique — c'est le but des fixtures, un espace qu'on montre n'a pas une liste
 * vide —, si bien qu'un « le total vaut 1 » mesurerait les fixtures autant que le code, et casserait
 * au premier profil de démonstration ajouté. Le solde, lui, s'assène en absolu : les fixtures le
 * remettent à sa valeur d'accueil, et c'est cette valeur que le produit promet.
 */
final class RevealTest extends ApiTestCase
{
    public function testRevealDebitsOneCreditAndRecordsProfile(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();
        $before = $this->revealedTotal($organizationId, $token);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(201);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(1, $this->jsonResponse()['balance']);

        self::assertSame($before + 1, $this->revealedTotal($organizationId, $token), 'un dévoilement, une ligne');

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-transactions", $token);
        $types = array_column($this->jsonResponse()['items'], 'type');
        self::assertContains('CONSUMPTION', $types);
    }

    public function testEachAccessIsBilledSeparately(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();
        $before = $this->revealedTotal($organizationId, $token);

        // Deux accès sur le MÊME candidat : deux crédits, deux lignes. Payer les coordonnées ne
        // donne rien sur le CV — c'est la règle que ce test garde.
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(201);
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'RESUME']);
        self::assertResponseStatusCodeSame(201);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(0, $this->jsonResponse()['balance'], 'Un crédit par accès, pas un par candidat.');

        $this->request('GET', "/v1/organizations/{$organizationId}/revealed-profiles", $token);
        $payload = $this->jsonResponse();
        self::assertSame($before + 2, $payload['total'], 'deux accès, deux lignes');
        // Servis du plus récent au plus ancien, comme toute la liste des dévoilements : les deux
        // lignes de tête sont celles que ce test vient de créer, dans l'ordre inverse de leur pose.
        self::assertSame(['RESUME', 'EMAIL'], array_column(\array_slice($payload['items'], 0, 2), 'type'));
    }

    public function testRevealWithoutTypeIsRefused(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();

        // Sans type, on ne sait pas ce qu'on facture : 422 plutôt qu'un accès choisi par défaut.
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId]);
        self::assertResponseStatusCodeSame(422);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(2, $this->jsonResponse()['balance'], 'Un refus ne débite rien.');
    }

    public function testRevealIsIdempotent(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();
        $before = $this->revealedTotal($organizationId, $token);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseIsSuccessful();
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseIsSuccessful();

        // Aucun second débit : solde reste à 1, et UNE seule ligne s'est ajoutée.
        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(1, $this->jsonResponse()['balance']);
        self::assertSame($before + 1, $this->revealedTotal($organizationId, $token));
    }

    public function testCompanyMemberCanReadCreditsAndRevealProfile(): void
    {
        $token = $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->atlasOrganizationAndCandidateIds();

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertResponseIsSuccessful();
        self::assertSame(25, $this->jsonResponse()['balance']);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-transactions", $token);
        self::assertResponseIsSuccessful();
        self::assertGreaterThanOrEqual(1, $this->jsonResponse()['total']);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(201);

        $this->request('GET', "/v1/organizations/{$organizationId}/revealed-profiles", $token);
        self::assertResponseIsSuccessful();
        self::assertGreaterThanOrEqual(1, $this->jsonResponse()['total']);
    }

    public function testRevealWithInsufficientCreditsIsRejected(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();
        $before = $this->revealedTotal($organizationId, $token);
        $this->drainWallet($organizationId);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(402);

        // Aucun débit, et RIEN ne s'est ajouté : un refus se prouve par l'immobilité.
        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(0, $this->jsonResponse()['balance']);
        self::assertSame($before, $this->revealedTotal($organizationId, $token));
    }

    public function testNonMemberCannotReveal(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(403);
    }

    public function testNonMemberCannotReadWallet(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        [$organizationId] = $this->organizationAndCandidateIds();

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * L'entreprise en revue travaille ; l'entreprise REFUSÉE est coupée (task `API-038`).
     *
     * Ce test disait l'inverse jusqu'à `API-038` : une entreprise non encore validée n'avait accès à
     * rien. La règle s'est inversée — l'entrée est immédiate, la vérification devient un contrôle a
     * posteriori — et c'est la seule garde qui reste : ce qui bloque n'est plus l'absence de
     * validation, c'est le refus.
     *
     * LE REFUS COUPE À L'AUTHENTIFICATION, pas par un 403 sur chaque route : le jeton déjà émis
     * cesse de valoir dès la décision, sans quoi une session ouverte avant elle continuerait de
     * dépenser des crédits jusqu'à expiration. C'est pour ça que le second appel attend un 401 et
     * non un 403.
     */
    public function testOrganizationUnderReviewWorksAndRejectedOneIsCutOff(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('POST', '/v1/organizations', $token, [
            'name' => 'Atlas Recrut',
            'givenName' => 'Nadia',
            'familyName' => 'Cherkaoui',
            'acquisitionChannel' => 'LINKEDIN',
        ]);
        $organizationId = $this->jsonResponse()['id'];
        [, $candidateId] = $this->organizationAndCandidateIds();

        // En revue : les deux crédits offerts sont lisibles et dépensables tout de suite.
        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertResponseIsSuccessful();

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-transactions", $token);
        self::assertResponseIsSuccessful();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(201);

        // Un administrateur refuse l'entreprise — le geste qui remplace l'ancienne barrière.
        $this->request('POST', "/v1/organizations/{$organizationId}/reject", $this->loginToken('admin@demo.showcase-recruiter-webapp-saas.example'));
        self::assertResponseIsSuccessful();

        // Le MÊME jeton ne vaut plus rien : la coupure ne dépend pas d'une reconnexion.
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'RESUME']);
        self::assertResponseStatusCodeSame(401);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertResponseStatusCodeSame(401);
    }

    public function testCannotRevealCandidateWithoutConsent(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => 'El Amrani']);
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($candidate);
        $candidate->withdrawConsent();
        $em->flush();

        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, [
            'candidateId' => $candidate->getUuid()->toString(),
            'type' => 'EMAIL',
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testCannotRevealInactiveCandidate(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => 'El Amrani']);
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($candidate);
        $candidate->active = false;
        $em->flush();

        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, [
            'candidateId' => $candidate->getUuid()->toString(),
            'type' => 'EMAIL',
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    public function testCannotRevealHiddenCandidate(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();
        $candidateId = $this->candidateUuidByFamilyName('Idrissi');

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(404);
    }

    public function testBatchRevealDebitsOnceProfileAndIsAllOrNothing(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();

        // Le portefeuille de démo porte 2 crédits : deux profils passent, trois ne passent pas.
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => [
                $this->candidateUuidByFamilyName('El Amrani'),
                $this->candidateUuidByFamilyName('Tazi'),
            ],
            'type' => 'EMAIL',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(2, $this->jsonResponse()['total']);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(0, $this->jsonResponse()['balance'], 'Un crédit par profil, pas un par lot.');
    }

    public function testBatchRevealRefusesTheWholeLotWhenCreditsAreShort(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();
        $before = $this->revealedTotal($organizationId, $token);
        $this->drainWallet($organizationId);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => [$this->candidateUuidByFamilyName('El Amrani')],
            'type' => 'EMAIL',
        ]);
        self::assertResponseStatusCodeSame(402);

        // TOUT OU RIEN : rien ne s'ajoute, mieux vaut ça qu'un lot à moitié débité.
        self::assertSame($before, $this->revealedTotal($organizationId, $token));
    }

    public function testBatchRevealDoesNotRebillWhatIsAlreadyPaid(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();
        $elAmrani = $this->candidateUuidByFamilyName('El Amrani');

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => [$elAmrani],
            'type' => 'EMAIL',
        ]);
        self::assertResponseIsSuccessful();

        // Recocher un profil déjà dévoilé ne facture rien — et le lot le renvoie quand même.
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => [$elAmrani],
            'type' => 'EMAIL',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->jsonResponse()['total']);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(1, $this->jsonResponse()['balance'], 'Un seul débit pour deux demandes.');
    }

    public function testBatchRevealIsCapped(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();

        // 51 identifiants : le plafond refuse SEC, il ne tronque pas — dévoiler les 50 premiers
        // laisserait croire que tout est passé.
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => array_fill(0, 51, $this->candidateUuidByFamilyName('El Amrani')),
            'type' => 'EMAIL',
        ]);
        self::assertResponseStatusCodeSame(422);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(2, $this->jsonResponse()['balance'], 'Un refus ne débite rien.');
    }

    public function testBatchRevealNamesTheProfilesItCannotReach(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => [
                $this->candidateUuidByFamilyName('El Amrani'),
                '01900000-0000-7000-8000-00000000ffff',
            ],
            'type' => 'EMAIL',
        ]);

        // Le lot entier échoue : on ne débite pas 1 profil quand l'utilisateur en a coché 2.
        self::assertResponseStatusCodeSame(422);
        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(2, $this->jsonResponse()['balance']);
    }

    public function testRevealIsRefusedWhenTheChannelHasNothingToServe(): void
    {
        $this->stripEmail('El Amrani');
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();
        $before = $this->revealedTotal($organizationId, $token);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(422);

        // Le refus se prouve par l'immobilité : ni crédit, ni ligne. Sans cette garde, l'appel
        // répondait 201 et facturait une adresse qui n'existe pas.
        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(2, $this->jsonResponse()['balance']);
        self::assertSame($before, $this->revealedTotal($organizationId, $token));
    }

    public function testBatchRevealBillsOnlyTheProfilesThatHaveTheData(): void
    {
        $this->stripEmail('El Amrani');
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();
        $tazi = $this->candidateUuidByFamilyName('Tazi');

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => [$this->candidateUuidByFamilyName('El Amrani'), $tazi],
            'type' => 'EMAIL',
        ]);
        self::assertResponseIsSuccessful();

        // Le lot rend ce qui a été dévoilé, pas ce qui a été demandé : il est plus court que la
        // sélection, et ne porte que le profil joignable.
        $payload = $this->jsonResponse();
        self::assertSame(1, $payload['total']);
        self::assertSame([$tazi], array_column($payload['items'], 'candidateId'));

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(1, $this->jsonResponse()['balance'], 'Un crédit pour le seul profil qui a la donnée.');
    }

    public function testBatchRevealOnAnEntirelyEmptyChannelSucceedsWithoutDebit(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId] = $this->organizationAndCandidateIds();
        $before = $this->revealedTotal($organizationId, $token);

        // Aucun de ces deux profils n'a de compte rendu : le lot aboutit sans rien faire, comme un
        // lot entièrement déjà payé. Un code d'erreur ici punirait l'écran d'avoir proposé.
        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => [
                $this->candidateUuidByFamilyName('El Amrani'),
                $this->candidateUuidByFamilyName('Ouazzani'),
            ],
            'type' => 'INTERVIEW_REPORT',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->jsonResponse()['total']);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(2, $this->jsonResponse()['balance']);
        self::assertSame($before, $this->revealedTotal($organizationId, $token));
    }

    public function testAChannelBothEmptyAndAlreadyPaidKeepsItsLine(): void
    {
        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'EMAIL']);
        self::assertResponseStatusCodeSame(201);
        $after = $this->revealedTotal($organizationId, $token);

        // L'adresse disparaît d'une synchro à l'autre, sur un accès déjà payé — le cas des trois
        // dévoilements fautifs mesurés. L'accès reste acquis, il ne se recrée ni ne se repaie.
        $this->stripEmail('El Amrani');

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles/batch", $token, [
            'candidateIds' => [$candidateId],
            'type' => 'EMAIL',
        ]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->jsonResponse()['total'], 'La ligne existante est rendue, pas oubliée.');

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(1, $this->jsonResponse()['balance'], 'Un seul débit, celui du premier appel.');
        self::assertSame($after, $this->revealedTotal($organizationId, $token));
    }

    public function testInterviewedWithoutWrittenReportIsNotBillable(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->candidateByFamilyName('El Amrani');
        // Rencontré, mais le compte rendu n'a pas été rédigé : s'appuyer sur la date d'entretien
        // vendrait un document vide.
        $candidate->starInterviewedAt = new DateTimeImmutable('2026-06-18 10:00:00');
        $candidate->starInterviewReport = '   ';
        $em->flush();

        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'INTERVIEW_REPORT']);
        self::assertResponseStatusCodeSame(422);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(2, $this->jsonResponse()['balance']);
    }

    public function testResumeEntryWithoutFileIsNotBillable(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->candidateByFamilyName('El Amrani');
        // Une entrée sans identifiant de fichier n'est pas téléchargeable : la liste n'est pas vide,
        // le CV n'existe pas pour autant.
        $candidate->getJarvi()->resumes = [['name' => 'cv-demo.pdf', 'mimeType' => 'application/pdf']];
        $em->flush();

        $token = $this->loginToken('owner@demo.showcase-recruiter-webapp-saas.example');
        [$organizationId, $candidateId] = $this->organizationAndCandidateIds();

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $candidateId, 'type' => 'RESUME']);
        self::assertResponseStatusCodeSame(422);

        $this->request('GET', "/v1/organizations/{$organizationId}/credit-wallet", $token);
        self::assertSame(2, $this->jsonResponse()['balance']);
    }

    /** Prive un profil d'e-mail des DEUX côtés : ce que la personne déclare, et ce que Jarvi réplique. */
    private function stripEmail(string $familyName): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->candidateByFamilyName($familyName);
        $candidate->getPerson()->contactEmail = null;
        $candidate->getJarvi()->contacts = array_values(array_filter(
            $candidate->getJarvi()->contacts,
            static fn (array $contact): bool => 'email' !== ($contact['type'] ?? null),
        ));
        $em->flush();
    }

    private function candidateByFamilyName(string $familyName): Candidate
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => $familyName]);
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($candidate);

        return $candidate;
    }

    private function organizationAndCandidateIds(): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'showcase-recruiter-webapp-saas Demo SARL']);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => 'El Amrani']);
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($organization);
        self::assertNotNull($candidate);

        return [$organization->getUuid()->toString(), $candidate->getUuid()->toString()];
    }

    /**
     * Combien de profils cette organisation a déjà dévoilés.
     *
     * Le point de départ, jamais une valeur attendue : les fixtures en sèment, et c'est voulu.
     */
    private function revealedTotal(string $organizationUuid, string $token): int
    {
        $this->request('GET', "/v1/organizations/{$organizationUuid}/revealed-profiles", $token);
        self::assertResponseIsSuccessful();

        return (int) $this->jsonResponse()['total'];
    }

    private function drainWallet(string $organizationUuid): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'showcase-recruiter-webapp-saas Demo SARL']);
        $wallet = $em->getRepository(CreditWallet::class)->findOneBy(['organization' => $organization]);
        self::assertNotNull($wallet);
        $wallet->balance = 0;
        $em->flush();
    }

    private function candidateUuidByFamilyName(string $familyName): string
    {
        return $this->candidateByFamilyName($familyName)->getUuid()->toString();
    }

    /** @return array{0: string, 1: string} [organizationUuid, candidateUuid] */
    private function atlasOrganizationAndCandidateIds(): array
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'Atlas Talent Conseil']);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => 'El Amrani']);
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($organization);
        self::assertNotNull($candidate);

        return [$organization->getUuid()->toString(), $candidate->getUuid()->toString()];
    }
}
