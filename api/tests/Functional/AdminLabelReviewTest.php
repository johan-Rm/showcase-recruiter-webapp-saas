<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Vocabulary\VocabularyImporter;
use App\Entity\VocabularyLabel;
use App\Enum\VocabularyStatus;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Relecture d'un LIBELLÉ « mot à mot » (tâche 124) : exclure un synonyme parasite de l'élargissement
 * sans toucher au concept, de façon **tracée** et **réversible**, et qui **survit à la réingestion**.
 */
final class AdminLabelReviewTest extends ApiTestCase
{
    private const string ADMIN = 'admin@demo.showcase-recruiter-webapp-saas.example';

    public function testAdminOnly(): void
    {
        $this->request('POST', '/v1/admin/referentials/labels/reviews', null, ['labelIds' => ['x'], 'status' => 'DEPRECIE']);
        self::assertResponseStatusCodeSame(401);

        $this->request('POST', '/v1/admin/referentials/labels/reviews', $this->loginToken('atlas.member@demo.showcase-recruiter-webapp-saas.example'), [
            'labelIds' => ['x'],
            'status' => 'DEPRECIE',
        ]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testValideIsRejected(): void
    {
        // Un libellé ne se valide pas seul : `Assert\Choice` n'accepte que DEPRECIE / CANDIDAT.
        $this->request('POST', '/v1/admin/referentials/labels/reviews', $this->loginToken(self::ADMIN), [
            'labelIds' => ['01920000-0000-7000-8000-000000000000'],
            'status' => 'VALIDE',
        ]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testDeprecateIsTracedThenReversibleAndSurvivesReimport(): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $importer = self::getContainer()->get(VocabularyImporter::class);

        $code = 'competence-lbl-'.uniqid();
        $term = 'stagiaire parasite-'.uniqid();
        $importer->import($this->taxonomy($code, $term));
        $em->clear();
        $uuid = $this->labelUuid($em, $term);

        // Déprécier : exclut ce libellé, trace le geste, et rend les identifiants inconnus.
        $this->request('POST', '/v1/admin/referentials/labels/reviews', $this->loginToken(self::ADMIN), [
            'labelIds' => [$uuid, 'pas-un-uuid'],
            'status' => 'DEPRECIE',
        ]);
        self::assertResponseIsSuccessful();
        $payload = $this->jsonResponse();
        self::assertSame(1, $payload['reviewed']);
        self::assertSame('DEPRECIE', $payload['status']);
        self::assertSame(['pas-un-uuid'], $payload['unknownIds'], 'les uuids invalides ou absents sont rendus, pas tus');
        self::assertSame(self::ADMIN, $payload['reviewedBy']);

        // clear() avant chaque relecture : sans lui, l'identity map du test rendrait l'instance
        // d'avant le POST (la base, elle, est bien à jour).
        $em->clear();
        $label = $this->label($em, $term);
        self::assertSame(VocabularyStatus::DEPRECIE, $label->status);
        self::assertSame(self::ADMIN, $label->reviewedBy);
        self::assertNotNull($label->reviewedAt);

        // La réingestion ne réécrase que la fréquence : la décision humaine survit.
        $importer->import($this->taxonomy($code, $term));
        $em->clear();
        self::assertSame(VocabularyStatus::DEPRECIE, $this->label($em, $term)->status, 'un réimport ne restaure pas un libellé déprécié');

        // Restaurer : retour à CANDIDAT, réversible.
        $uuid = $this->labelUuid($em, $term);
        $this->request('POST', '/v1/admin/referentials/labels/reviews', $this->loginToken(self::ADMIN), [
            'labelIds' => [$uuid],
            'status' => 'CANDIDAT',
        ]);
        self::assertResponseIsSuccessful();
        $em->clear();
        self::assertSame(VocabularyStatus::CANDIDAT, $this->label($em, $term)->status, 'restaurer ramène le libellé dans l’élargissement');
    }

    private function labelUuid(EntityManagerInterface $em, string $term): string
    {
        return $this->label($em, $term)->getUuid()->toRfc4122();
    }

    private function label(EntityManagerInterface $em, string $term): VocabularyLabel
    {
        $label = $em->getRepository(VocabularyLabel::class)->findOneBy(['label' => $term]);
        self::assertInstanceOf(VocabularyLabel::class, $label);

        return $label;
    }

    /** @return array<string, mixed> */
    private function taxonomy(string $code, string $term): array
    {
        return [
            'competences' => ['concepts' => [[
                'id' => $code,
                'label_canonique' => 'Concept '.$code,
                'statut' => 'candidat',
                'frequence_totale' => 10,
                'synonymes' => [['terme' => $term, 'frequence' => 10, 'langue' => 'fr']],
            ]]],
            'postes' => ['concepts' => []],
        ];
    }
}
