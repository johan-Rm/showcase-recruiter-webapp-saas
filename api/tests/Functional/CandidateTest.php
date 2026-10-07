<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Candidate;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\PresencePeriod;
use App\Enum\CurrentPositionCertainty;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Recherche & lecture candidats (tâche 006) : visibilité, masquage avant/après dévoilement, i18n.
 */
final class CandidateTest extends ApiTestCase
{
    private const string OWNER_EMAIL = 'owner@demo.showcase-recruiter-webapp-saas.example';
    private const string SEARCH_URI = '/v1/search/candidates';
    private const string SEARCH_IT_URI = self::SEARCH_URI.'?sector=informatique';
    private const string YASSINE = 'El Amrani';

    public function testSearchReturnsOnlyVisibleMaskedCards(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_URI, $token);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        // 5 VISIBLE ; Karim (HIDDEN) exclu — la population de démo s'est étoffée en API-042.
        self::assertSame(5, $payload['total']);
        foreach ($payload['items'] as $card) {
            self::assertFalse($card['revealedEmail']);
            // L'IDENTITÉ EST GRATUITE (task API-018) : le nom sort en clair sans dévoilement.
            self::assertStringNotContainsString('.', $card['familyName']);
        }
    }

    public function testSearchFilterBySector(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI, $token);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame(1, $payload['total']);
        self::assertSame('Yassine', $payload['items'][0]['givenName']); // identité gratuite
    }

    public function testSearchCardExposesDifferentiators(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI, $token);

        self::assertResponseIsSuccessful();
        $card = $this->jsonResponse()['items'][0];
        self::assertTrue($card['hasPiedATerre']);
        self::assertSame('Casablanca', $card['piedATerreCity']);
        self::assertNotEmpty($card['presencePeriods']);
        self::assertArrayHasKey('createdAt', $card);
        self::assertArrayHasKey('updatedAt', $card);
        // La carte reste allégée : les blocs Jarvi lourds vivent dans la vue full / le détail.
        self::assertArrayNotHasKey('workExperiences', $card);
        self::assertArrayNotHasKey('summary', $card);
        // Salaire : notion retirée du produit — plus jamais sérialisée (task 074).
        self::assertArrayNotHasKey('salaryExpected', $card);
        self::assertArrayNotHasKey('salaryMin', $card);
    }

    /** La colonne « Exp. » de la Table : un agrégat, visible même masqué (task 086). */
    public function testSearchCardExposesExperienceYearsWhileMasked(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI, $token);

        self::assertResponseIsSuccessful();
        $card = $this->jsonResponse()['items'][0];
        self::assertFalse($card['revealedEmail']);
        // Fixture : poste unique en cours depuis 2018 → au moins 7 ans de carrière.
        self::assertGreaterThanOrEqual(7, $card['experienceYears']);
    }

    /**
     * Le POSTE ACTUEL sur la carte (task API-030), et servi MASQUÉ : un intitulé qualifie sans
     * nommer, c'est l'employeur qui ré-identifie et lui reste derrière le dévoilement.
     *
     * Sans lui, la Table et la Visionneuse titrent sur `headline` — l'accroche que le candidat
     * s'écrit, qui monte à 752 caractères sur le vivier réel et place le métier en quatrième
     * position quand elle le nomme.
     */
    public function testSearchCardExposesCurrentTitleWhileMasked(): void
    {
        $jarvi = $this->candidateEntity(self::YASSINE)->getJarvi();
        self::assertNotNull($jarvi);
        $jarvi->currentTitle = 'Développeur & DevOps freelance';
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI, $token);

        self::assertResponseIsSuccessful();
        $card = $this->jsonResponse()['items'][0];
        self::assertFalse($card['revealedEmail']);
        self::assertSame('Développeur & DevOps freelance', $card['currentTitle']);
        // L'EMPLOYEUR NE SUIT PAS : lui ré-identifie, et il se paie.
        self::assertArrayNotHasKey('currentCompanyName', $card);
    }

    /**
     * Aucun poste dérivable — 27 % du vivier réel, dont le parcours ne porte aucune expérience
     * ouverte. La clé est ABSENTE et non `null` : le normaliseur de la carte omet les nulls, si
     * bien qu'une garde `null !== currentTitle` côté front laisserait passer l'absence.
     */
    public function testSearchCardOmitsCurrentTitleWithoutADerivedPosition(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $jarvi = $this->candidateEntity(self::YASSINE)->getJarvi();
        self::assertNotNull($jarvi);
        $jarvi->currentTitle = null;
        $jarvi->currentPositionCertainty = CurrentPositionCertainty::ABSENT;
        $em->flush();

        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI, $token);

        self::assertResponseIsSuccessful();
        $card = $this->jsonResponse()['items'][0];
        self::assertArrayNotHasKey('currentTitle', $card);
        // L'accroche, elle, reste servie : c'est sur elle que le front se replie.
        self::assertArrayHasKey('headline', $card);
    }

    /**
     * Joignabilité (task 074) : la PRÉSENCE d'une coordonnée est exposée avant dévoilement, jamais
     * sa valeur — c'est ce qui évite de dépenser un crédit sur un profil injoignable.
     */
    public function testSearchCardExposesReachabilityWithoutValueWhileMasked(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI, $token);

        self::assertResponseIsSuccessful();
        $card = $this->jsonResponse()['items'][0];
        self::assertTrue($card['hasEmail']);
        self::assertTrue($card['hasPhone']);
        self::assertNull($card['contactEmail']);
        self::assertNull($card['phone']);
    }

    /**
     * Les DEUX autres canaux payants disent aussi leur présence (task `API-064`).
     *
     * Sans eux, l'écran annonce un coût pour un CV qui n'existe pas : le serveur refuse ensuite de
     * le facturer, mais le bouton l'aura proposé. Yassine a un CV et n'a pas été reçu en entretien —
     * les deux booléens se séparent donc sur le même profil, et aucun contenu ne sort pour autant.
     */
    public function testSearchCardTellsWhetherResumeAndReportExistWithoutServingThem(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI, $token);

        self::assertResponseIsSuccessful();
        $card = $this->jsonResponse()['items'][0];
        self::assertTrue($card['hasResume']);
        self::assertFalse($card['hasReport']);
        self::assertFalse($card['revealedResume']);
        self::assertFalse($card['revealedReport']);
    }

    /** La fiche porte les mêmes deux signaux, et pas davantage : la valeur reste derrière l'accès. */
    public function testDetailTellsWhetherResumeAndReportExistWithoutServingThem(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', '/v1/candidates/'.$this->candidateId(self::YASSINE), $token);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertTrue($payload['hasResume']);
        self::assertFalse($payload['hasReport']);
        self::assertNull($payload['resumes']);
        self::assertNull($payload['starInterviewReport']);
    }

    public function testSearchFullViewExposesJarviProfileStillMasked(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI.'&view=full', $token);

        self::assertResponseIsSuccessful();
        $item = $this->jsonResponse()['items'][0];
        // L'identité, l'employeur et le profil LinkedIn se lisent SANS crédit : on paie pour
        // atteindre la personne, pas pour savoir qui elle est.
        self::assertSame('Yassine', $item['givenName']);
        self::assertSame(self::YASSINE, $item['familyName']);
        self::assertNotEmpty($item['summary']);
        self::assertNotEmpty($item['workExperiences']);
        self::assertNotEmpty($item['educations']);
        self::assertNotEmpty($item['languages']);
        self::assertNull($item['contactEmail'] ?? null);
        self::assertNull($item['resumes'] ?? null); // métadonnées CV réservées au dévoilé
        // La photo est servie masqué comme dévoilé : le masquage porte sur les MOYENS DE CONTACT,
        // pas sur l'anonymat. Choix produit assumé — un visage désigne, et on l'accepte.
        self::assertNotNull($item['photoUrl'] ?? null);
        self::assertNull($item['whatsappNumber'] ?? null); // le JID WhatsApp contient le numéro
    }

    /**
     * Le parcours se lit ENTIER sans crédit (task API-018), employeurs compris.
     *
     * C'était l'inverse jusqu'ici : « poste + employeur + ville » ré-identifie, donc l'employeur
     * était masqué. Le produit a tranché autrement — on donne à lire pour donner envie de payer le
     * contact, et ce qui se paie désormais est le MOYEN DE JOINDRE, jamais ce qui décrit.
     */
    public function testUnrevealedProfileStillExposesEmployerNames(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI.'&view=full', $token);

        self::assertResponseIsSuccessful();
        $item = $this->jsonResponse()['items'][0];
        self::assertFalse($item['revealedEmail']);
        self::assertFalse($item['revealedPhone']);
        foreach ($item['workExperiences'] as $experience) {
            self::assertNotEmpty($experience['companyName']);
            self::assertNotEmpty($experience['title']);
            self::assertArrayHasKey('startDate', $experience);
        }
        // Les canaux, eux, restent fermés : c'est la seule chose que le crédit achète encore.
        self::assertNull($item['contactEmail'] ?? null);
        self::assertNull($item['phone'] ?? null);
        self::assertNull($item['whatsappNumber'] ?? null);
    }

    public function testSearchFullViewUnmasksRevealedProfiles(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $organizationId = $this->organizationId();
        $id = $this->candidateId(self::YASSINE);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $id, 'type' => 'EMAIL']);
        self::assertResponseIsSuccessful();

        $this->request('GET', self::SEARCH_IT_URI.'&view=full', $token);
        self::assertResponseIsSuccessful();
        $item = $this->jsonResponse()['items'][0];
        self::assertTrue($item['revealedEmail']);
        self::assertSame('Yassine', $item['givenName']);
        self::assertSame(self::YASSINE, $item['familyName']);
        self::assertSame('yassine@example.com', $item['contactEmail']);

        // LE CŒUR DE LA TASK API-018 : payer l'e-mail n'ouvre PAS le téléphone. Et l'identifiant
        // WhatsApp suit le téléphone, parce qu'il contient le numéro en clair.
        self::assertFalse($item['revealedPhone']);
        self::assertNull($item['phone']);
        self::assertNull($item['whatsappNumber']);

        // Le CV est un accès à part : un canal payé ne l'ouvre pas davantage.
        self::assertFalse($item['revealedResume']);
        self::assertNull($item['resumes']);
        self::assertStringContainsString('/v1/files/', (string) $item['photoUrl']);
        // L'employeur ne se paie plus : il était déjà là avant le dévoilement.
        self::assertSame('Rivage Logiciels', $item['workExperiences'][0]['companyName']);
    }

    /** Le second canal s'achète séparément, et n'ouvre que lui. */
    public function testPhoneIsRevealedOnItsOwnAccess(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $organizationId = $this->organizationId();
        $id = $this->candidateId(self::YASSINE);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $id, 'type' => 'PHONE']);
        self::assertResponseIsSuccessful();

        $this->request('GET', self::SEARCH_IT_URI.'&view=full', $token);
        self::assertResponseIsSuccessful();
        $item = $this->jsonResponse()['items'][0];

        self::assertTrue($item['revealedPhone']);
        self::assertSame('+33600000001', $item['phone']);
        self::assertSame('+33600000001', $item['whatsappNumber']);
        // L'e-mail, lui, n'a pas été acheté : il reste fermé.
        self::assertFalse($item['revealedEmail']);
        self::assertNull($item['contactEmail']);
    }

    /**
     * Les mots-clés se cumulent en OU (task API-020).
     *
     * L'endpoint ne gardait qu'un terme : les autres disparaissaient en silence, et le formulaire —
     * qui en accumule — ne pouvait pas faire compter la combinaison qu'il détient. Les trois cas
     * ensemble sont ce qui distingue un OU d'un « un seul passe » : chacun pris seul rend un profil,
     * les deux ensemble en rendent deux.
     *
     * La notation est `q[]` : PHP écrase les clés répétées à l'analyse de la requête, donc
     * `?q=php&q=java` ne cumulera jamais, quoi que fasse le code applicatif.
     */
    public function testSearchCombinesRepeatedKeywords(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_URI.'?q=PHP', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->jsonResponse()['total'], 'PHP ne concerne que Yassine.');

        $this->request('GET', self::SEARCH_URI.'?q=Sage', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->jsonResponse()['total'], 'Sage ne concerne qu’Imane.');

        $this->request('GET', self::SEARCH_URI.'?q[]=PHP&q[]=Sage', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(
            2,
            $this->jsonResponse()['total'],
            'Les deux termes se relient en OU ; avec un seul terme retenu le total serait resté à 1.',
        );
    }

    /** Un seul terme rend exactement ce qu'il rendait : c'est la non-régression qui compte le plus. */
    public function testSearchWithASingleKeywordIsUnchanged(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_URI.'?q=PHP', $token);
        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();

        self::assertSame(1, $payload['total']);
        self::assertSame(self::YASSINE, $payload['items'][0]['familyName']);
    }

    /** Une valeur vide ne filtre rien : elle est écartée, pas cherchée. */
    public function testSearchIgnoresEmptyKeywords(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_URI.'?q[]=&q[]=PHP', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->jsonResponse()['total']);
    }

    public function testSearchFilterByStar(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_URI.'?star=1', $token);
        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame(1, $payload['total']);
        self::assertSame('Imane', $payload['items'][0]['givenName']); // identité gratuite
        self::assertTrue($payload['items'][0]['isStar']);

        $this->request('GET', self::SEARCH_URI.'?star=0', $token);
        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame(4, $payload['total'], 'les visibles moins l’unique profil vedette');
        self::assertNotContains('Imane', array_column($payload['items'], 'givenName'));
    }

    public function testSearchFilterByCountry(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_URI.'?country=fr', $token);
        self::assertResponseIsSuccessful();
        // 4 des 5 visibles sont en France ; Salma Ouazzani est en Belgique.
        self::assertSame(4, $this->jsonResponse()['total']);

        $this->request('GET', self::SEARCH_URI.'?country=ma', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->jsonResponse()['total']);
    }

    public function testSearchFilterByPiedATerre(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_URI.'?hasPiedATerre=1', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(5, $this->jsonResponse()['total']);

        $this->request('GET', self::SEARCH_URI.'?hasPiedATerre=0', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->jsonResponse()['total']);
    }

    public function testSearchFilterByPresenceWindow(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $today = new DateTimeImmutable('today');
        $this->setPresencePeriod($em, self::YASSINE, $today->modify('+5 days'), $today->modify('+10 days'));
        $this->setPresencePeriod($em, 'Tazi', $today->modify('+40 days'), $today->modify('+50 days'));
        $em->flush();

        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_URI.'?presenceWithinDays=15', $token);
        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame(1, $payload['total']);
        self::assertSame('Yassine', $payload['items'][0]['givenName']); // identité gratuite

        $this->request('GET', self::SEARCH_URI.'?presenceWithinDays=60', $token);
        self::assertResponseIsSuccessful();
        // Les deux replacés ci-dessus, plus les trois autres visibles dont le séjour tombe entre
        // +30 et +45 jours.
        self::assertSame(5, $this->jsonResponse()['total']);
    }

    /**
     * Le badge « Au Maroc » descend avec la carte, et il dit AUJOURD'HUI.
     *
     * Le verdict était calculé dans le navigateur : il suivait donc l'horloge du poste, et une page
     * ouverte depuis la veille affichait un badge périmé.
     *
     * Il ne se confond pas avec le critère `presenceWithinDays` : un candidat qui arrive dans cinq
     * jours entre dans une recherche « sous 15 jours » **sans** être au Maroc à l'instant. Deux
     * questions différentes sous un même nom — les aligner ferait mentir l'une des deux.
     */
    public function testCardCarriesTheMoroccoVerdictForTodayAndNotTheSearchWindow(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $today = new DateTimeImmutable('today');
        $this->setPresencePeriod($em, self::YASSINE, $today->modify('-2 days'), $today->modify('+3 days'));
        $this->setPresencePeriod($em, 'Tazi', $today->modify('+5 days'), $today->modify('+10 days'));
        $em->flush();

        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_URI.'?presenceWithinDays=15&perPage=200', $token);
        self::assertResponseIsSuccessful();

        $cards = [];
        foreach ($this->jsonResponse()['items'] as $card) {
            $cards[$card['id']] = $card['isInMorocco'];
        }

        // Les deux passent le FILTRE (séjour dans les 15 jours), un seul porte le BADGE.
        self::assertCount(2, $cards);
        self::assertContains(true, $cards, 'celui dont le séjour couvre aujourd’hui');
        self::assertContains(false, $cards, 'celui qui arrive dans cinq jours');
    }

    public function testSearchFilterByPresenceCity(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_URI.'?presenceCity=casablanca', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(5, $this->jsonResponse()['total']);

        $this->request('GET', self::SEARCH_URI.'?presenceCity=rabat', $token);
        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->jsonResponse()['total']);
    }

    public function testSearchCardCapsSkillsPreviewButDetailKeepsAll(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->candidateEntity(self::YASSINE);
        $jarvi = $candidate->getJarvi();
        self::assertNotNull($jarvi);
        $jarvi->skills = array_map(static fn (int $i): string => "Compétence {$i}", range(1, 20));
        $em->flush();

        $token = $this->loginToken(self::OWNER_EMAIL);

        $this->request('GET', self::SEARCH_IT_URI, $token);
        self::assertResponseIsSuccessful();
        self::assertCount(12, $this->jsonResponse()['items'][0]['skills']);

        $this->request('GET', "/v1/candidates/{$candidate->getUuid()->toString()}", $token);
        self::assertResponseIsSuccessful();
        self::assertCount(20, $this->jsonResponse()['skills']);
    }

    public function testItemIsMaskedWhenNotRevealed(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $id = $this->candidateId(self::YASSINE);

        $this->request('GET', "/v1/candidates/{$id}", $token);

        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertFalse($payload['revealedEmail']);
        // Le canal reste fermé, l'identité non : c'est toute la bascule de la task API-018.
        self::assertNull($payload['contactEmail']);
        self::assertSame(self::YASSINE, $payload['familyName']);
        self::assertSame('Informatique', $payload['desiredSector']);
        self::assertNotEmpty($payload['workExperiences']);
        self::assertNull($payload['resumes'] ?? null);
    }

    public function testItemIsUnmaskedAfterReveal(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $organizationId = $this->organizationId();
        $id = $this->candidateId(self::YASSINE);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, ['candidateId' => $id, 'type' => 'EMAIL']);
        self::assertResponseIsSuccessful();

        $this->request('GET', "/v1/candidates/{$id}", $token);
        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertTrue($payload['revealedEmail']);
        self::assertSame('yassine@example.com', $payload['contactEmail']);
        self::assertSame(self::YASSINE, $payload['familyName']);

        // Trois accès INDÉPENDANTS : le CV reste fermé tant qu'on ne l'a pas payé lui aussi.
        self::assertFalse($payload['revealedResume']);
        self::assertNull($payload['resumes']);

        $this->request('POST', "/v1/organizations/{$organizationId}/revealed-profiles", $token, [
            'candidateId' => $id,
            'type' => 'RESUME',
        ]);
        self::assertResponseIsSuccessful();

        $this->request('GET', "/v1/candidates/{$id}", $token);
        $payload = $this->jsonResponse();
        self::assertTrue($payload['revealedResume']);
        self::assertNotEmpty($payload['resumes'], 'Le second crédit ouvre le CV, et lui seul.');
        self::assertFalse($payload['revealedReport'], 'Le compte rendu, lui, reste à dévoiler.');
    }

    public function testHiddenCandidateIsNotFound(): void
    {
        $token = $this->loginToken(self::OWNER_EMAIL);
        $id = $this->candidateId('Idrissi'); // Karim, HIDDEN

        $this->request('GET', "/v1/candidates/{$id}", $token);
        self::assertResponseStatusCodeSame(404);
    }

    public function testNonRecruiterCannotSearch(): void
    {
        $token = $this->loginToken('nadia.cherkaoui@entreprise-abc.test');
        $this->request('GET', self::SEARCH_URI, $token);
        self::assertResponseStatusCodeSame(403);
    }

    public function testSearchExcludesInactivePerimeterProfiles(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => 'Tazi']); // Imane, VISIBLE
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($candidate);
        $candidate->active = false; // sortie de périmètre (tâche 020/014)
        $em->flush();

        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_URI, $token);

        self::assertResponseIsSuccessful();
        // Avant : 5 VISIBLE. Imane désactivée → 4.
        self::assertSame(4, $this->jsonResponse()['total']);
    }

    public function testSearchExcludesProfilesWithoutConsent(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->candidateEntity(self::YASSINE);
        $candidate->withdrawConsent();
        $em->flush();

        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', self::SEARCH_IT_URI, $token);

        self::assertResponseIsSuccessful();
        self::assertSame(0, $this->jsonResponse()['total']);
    }

    public function testItemWithoutConsentIsNotFound(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->candidateEntity(self::YASSINE);
        $candidate->withdrawConsent();
        $em->flush();

        $token = $this->loginToken(self::OWNER_EMAIL);
        $this->request('GET', "/v1/candidates/{$candidate->getUuid()->toString()}", $token);

        self::assertResponseStatusCodeSame(404);
    }

    public function testAlreadyRevealedCandidateRemainsReadableAfterConsentWithdrawal(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $candidate = $this->candidateEntity('Tazi'); // already revealed by Atlas Talent Conseil fixtures.
        $candidate->withdrawConsent();
        $em->flush();

        $token = $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example');
        $this->request('GET', "/v1/candidates/{$candidate->getUuid()->toString()}", $token);

        self::assertResponseIsSuccessful();
        self::assertTrue($this->jsonResponse()['revealedEmail']);
    }

    private function candidateId(string $familyName): string
    {
        return $this->candidateEntity($familyName)->getUuid()->toString();
    }

    private function candidateEntity(string $familyName): Candidate
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $person = $em->getRepository(Person::class)->findOneBy(['familyName' => $familyName]);
        self::assertNotNull($person);
        $candidate = $em->getRepository(Candidate::class)->findOneBy(['person' => $person]);
        self::assertNotNull($candidate);

        return $candidate;
    }

    private function setPresencePeriod(EntityManagerInterface $em, string $familyName, DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        $period = $em->getRepository(PresencePeriod::class)->findOneBy(['candidate' => $this->candidateEntity($familyName)]);
        self::assertNotNull($period);
        $period->startDate = $start;
        $period->endDate = $end;
    }

    private function organizationId(): string
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $organization = $em->getRepository(Organization::class)->findOneBy(['name' => 'showcase-recruiter-webapp-saas Demo SARL']);
        self::assertNotNull($organization);

        return $organization->getUuid()->toString();
    }
}
