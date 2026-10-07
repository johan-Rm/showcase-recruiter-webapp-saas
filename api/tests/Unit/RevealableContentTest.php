<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Candidate\RevealableContent;
use App\Entity\Candidate;
use App\Entity\CandidateJarvi;
use App\Entity\Person;
use App\Enum\RevealType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

/**
 * Ce qu'un profil a réellement à servir sur chaque canal payant.
 *
 * Deux lecteurs en dépendent : le dévoilement, qui refuse de facturer du vide, et la carte
 * candidat, qui éteint le canal avant le clic. Ils doivent dire la MÊME chose — d'où des cas
 * nommés un par un plutôt qu'un test paramétré : chacun garde une règle produit, pas une branche.
 */
final class RevealableContentTest extends TestCase
{
    public function testUneAdresseDeclareeParLaPersonnePrimeSurLaReplica(): void
    {
        $candidate = self::candidate(
            contactEmail: 'declaree@example.com',
            contacts: [['type' => 'email', 'value' => 'replica@example.com']],
        );

        self::assertSame('declaree@example.com', RevealableContent::email($candidate));
        self::assertTrue(RevealableContent::has($candidate, RevealType::EMAIL));
    }

    /** Sans ce repli, les coordonnées d'un profil sourcé resteraient invisibles alors qu'on les détient. */
    public function testSansAdresseDeclareeLaReplicaPrendLeRelais(): void
    {
        $candidate = self::candidate(contacts: [['type' => 'email', 'value' => 'replica@example.com']]);

        self::assertSame('replica@example.com', RevealableContent::email($candidate));
        self::assertTrue(RevealableContent::has($candidate, RevealType::EMAIL));
    }

    public function testAucuneAdresseNulPart(): void
    {
        $candidate = self::candidate(contacts: [['type' => 'phone', 'value' => '+33600000001']]);

        self::assertNull(RevealableContent::email($candidate));
        self::assertFalse(RevealableContent::has($candidate, RevealType::EMAIL));
        self::assertTrue(RevealableContent::has($candidate, RevealType::PHONE));
    }

    public function testUnCvPorteUnFichier(): void
    {
        $candidate = self::candidate(resumes: [['fileId' => 'f-1', 'name' => 'cv.pdf']]);

        self::assertTrue(RevealableContent::hasResume($candidate));
        self::assertTrue(RevealableContent::has($candidate, RevealType::RESUME));
    }

    /**
     * Le cas qui sépare « la liste n'est pas vide » de « le CV existe ».
     *
     * Une entrée sans identifiant de fichier ne se télécharge pas : la compter reviendrait à vendre
     * un document qu'on ne saurait pas servir.
     */
    public function testUneEntreeDeCvSansFichierNeComptePas(): void
    {
        $candidate = self::candidate(resumes: [['name' => 'cv.pdf', 'mimeType' => 'application/pdf']]);

        self::assertFalse(RevealableContent::hasResume($candidate));
        self::assertFalse(RevealableContent::has($candidate, RevealType::RESUME));
    }

    public function testUneSeuleEntreeAvecFichierSuffit(): void
    {
        $candidate = self::candidate(resumes: [
            ['name' => 'brouillon.pdf'],
            ['fileId' => 'f-2', 'name' => 'cv.pdf'],
        ]);

        self::assertTrue(RevealableContent::hasResume($candidate));
    }

    public function testAucunCv(): void
    {
        $candidate = self::candidate();

        self::assertFalse(RevealableContent::hasResume($candidate));
    }

    public function testUnCompteRenduEcritCompte(): void
    {
        $candidate = self::candidate();
        $candidate->starInterviewedAt = new DateTimeImmutable('2026-06-18 10:00:00');
        $candidate->starInterviewReport = 'Entretien réalisé : projet de retour cohérent.';

        self::assertTrue(RevealableContent::hasReport($candidate));
        self::assertTrue(RevealableContent::has($candidate, RevealType::INTERVIEW_REPORT));
    }

    /**
     * Le cas qui sépare « rencontré » de « compte rendu ».
     *
     * L'indicateur d'entretien est déjà sur la carte, donc tentant parce que gratuit. S'en servir
     * ferait proposer l'achat d'un document que personne n'a rédigé.
     */
    public function testRencontreSansCompteRenduEcritNeComptePas(): void
    {
        $candidate = self::candidate();
        $candidate->starInterviewedAt = new DateTimeImmutable('2026-06-18 10:00:00');

        self::assertFalse(RevealableContent::hasReport($candidate));
        self::assertFalse(RevealableContent::has($candidate, RevealType::INTERVIEW_REPORT));
    }

    public function testUnCompteRenduReduitADesEspacesEstVide(): void
    {
        $candidate = self::candidate();
        $candidate->starInterviewReport = "   \n  ";

        self::assertFalse(RevealableContent::hasReport($candidate));
    }

    /** Un profil jamais synchronisé n'a pas de réplica : rien à servir, et surtout pas d'erreur. */
    public function testSansReplicaToutSortAFaux(): void
    {
        $candidate = new Candidate(new Person('Jamais', 'Synchronisé'), Uuid::v7());

        self::assertNull(RevealableContent::email($candidate));
        self::assertNull(RevealableContent::phone($candidate));
        self::assertFalse(RevealableContent::hasResume($candidate));
        self::assertFalse(RevealableContent::hasReport($candidate));
        foreach (RevealType::cases() as $type) {
            self::assertFalse(RevealableContent::has($candidate, $type), $type->value);
        }
    }

    /**
     * @param list<array<string, mixed>> $contacts
     * @param list<array<string, mixed>> $resumes
     */
    private static function candidate(?string $contactEmail = null, array $contacts = [], array $resumes = []): Candidate
    {
        $candidate = new Candidate(new Person('Yassine', 'El Amrani', contactEmail: $contactEmail), Uuid::v7());
        $candidate->setJarvi(new CandidateJarvi($candidate, resumes: $resumes, contacts: $contacts));

        return $candidate;
    }
}
