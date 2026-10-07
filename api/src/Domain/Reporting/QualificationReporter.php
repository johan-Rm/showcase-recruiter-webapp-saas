<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Photographie chiffrée de l'état de qualification du vivier : ce que les traitements ont réellement
 * produit en base, dimension par dimension.
 *
 * Sert à comparer un AVANT et un APRÈS d'exécution de pipeline — c'est la seule façon de constater
 * qu'une chaîne a fait son travail, et d'attraper une régression silencieuse (un référentiel non
 * chargé, un rattachement qui s'effondre).
 *
 * LECTURE SEULE, SQL natif → chaque requête porte sa PROPRE clause `deleted_at IS NULL` (le soft
 * delete Doctrine ne couvre pas le SQL natif, cf. data-integrity.md).
 */
final readonly class QualificationReporter
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * Distributions du vivier : ce qui existe et en quelle quantité, pour décider avant de chercher.
     * Distinct de {@see snapshot()} qui donne les taux ; ici ce sont les têtes de chaque axe.
     *
     * @return array<string, list<array{label: string, count: int}>>
     */
    public function distributions(int $limit = 8): array
    {
        return [
            'sectors' => $this->topRows(
                'SELECT sector AS label, count(DISTINCT candidate_id) AS n FROM candidate_sector_experience GROUP BY sector ORDER BY n DESC LIMIT :limit',
                $limit,
            ),
            'jobs' => $this->topRows(
                <<<'SQL'
                    SELECT c.pref_label AS label, count(DISTINCT j.candidate_id) AS n
                    FROM candidate_job_experience j JOIN vocabulary_concept c ON c.id = j.concept_id
                    GROUP BY c.pref_label ORDER BY n DESC LIMIT :limit
                    SQL,
                $limit,
            ),
            'languages' => $this->topRows(
                'SELECT language AS label, count(*) AS n FROM candidate_language GROUP BY language ORDER BY n DESC LIMIT :limit',
                $limit,
            ),
        ];
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function topRows(string $sql, int $limit): array
    {
        return array_map(
            static fn (array $r): array => ['label' => (string) $r['label'], 'count' => (int) $r['n']],
            $this->connection->fetchAllAssociative($sql, ['limit' => $limit], ['limit' => ParameterType::INTEGER]),
        );
    }

    /** @return array<string, array<string, int>> section → métrique → valeur */
    public function snapshot(): array
    {
        return [
            'Vivier' => $this->vivier(),
            'Qualifications du profil' => $this->profileQualifications(),
            'Poste actuel' => $this->currentPosition(),
            'Tables dérivées' => $this->derivedTables(),
            'Référentiels' => $this->referentials(),
        ];
    }

    /** @return array<string, int> */
    private function vivier(): array
    {
        return [
            'Profils actifs' => $this->count('SELECT count(*) FROM candidate_jarvi WHERE deleted_at IS NULL'),
            'Postes du parcours' => $this->count(<<<'SQL'
                SELECT count(*) FROM candidate_jarvi cj, json_array_elements(cj.work_experiences) we
                WHERE cj.deleted_at IS NULL AND btrim(coalesce(we->>'title', '')) <> ''
                SQL),
            'Formations' => $this->count(<<<'SQL'
                SELECT count(*) FROM candidate_jarvi cj, json_array_elements(cj.educations) ed
                WHERE cj.deleted_at IS NULL
                SQL),
        ];
    }

    /**
     * Taux de remplissage des champs importés de Jarvi : la part du vivier pour laquelle on DÉTIENT
     * réellement chaque coordonnée / pièce de profil. Reprend au flag près la mesure de référence
     * (rapport-taux-remplissage-jarvi.md / scratchpad-taux-remplissage.sql) — e-mail, téléphone et
     * WhatsApp se lisent dans le JSON `contacts`, LinkedIn dans `raw_payload.profileUrl`, l'identité
     * dans `person` : jamais dans les booléens `person.*` de joignabilité que l'import ne remplit pas,
     * sous peine de sous-compter des profils joignables.
     *
     * WhatsApp = un `whatsappIdentifier` (JID `212…@…`) dont la partie numéro tient dans les bornes
     * E.164 (8–15 chiffres) — même règle que {@see \App\Domain\Jarvi\WhatsAppIdentifier} : on ne
     * compte que ce qu'on saurait afficher.
     *
     * Renvoie `filled` et `total` bruts (pas un pourcentage) : la présentation calcule le taux et
     * choisit les champs à montrer. Ordre = du mieux rempli au plus rare, comme dans le rapport.
     *
     * @return list<array{key: string, filled: int, total: int}>
     */
    public function fillRate(): array
    {
        $row = $this->connection->fetchAssociative(<<<'SQL'
            WITH flags AS (
                SELECT
                    btrim(coalesce(p.given_name, '')) <> '' AS f_given_name,
                    btrim(coalesce(p.family_name, '')) <> '' AS f_family_name,
                    EXISTS (SELECT 1 FROM jsonb_array_elements(cj.contacts::jsonb) e
                            WHERE e->>'type' = 'email' AND btrim(coalesce(e->>'value', '')) <> '') AS f_email,
                    EXISTS (SELECT 1 FROM jsonb_array_elements(cj.contacts::jsonb) e
                            WHERE e->>'type' = 'phone' AND btrim(coalesce(e->>'value', '')) <> '') AS f_phone,
                    EXISTS (SELECT 1 FROM jsonb_array_elements(cj.contacts::jsonb) e
                            WHERE split_part(split_part(e->>'whatsappIdentifier', '@', 1), ':', 1) ~ '^\d{8,15}$') AS f_whatsapp,
                    btrim(coalesce(cj.raw_payload::jsonb->>'profileUrl', '')) <> '' AS f_linkedin,
                    btrim(coalesce(cj.photo_url, '')) <> '' AS f_photo,
                    jsonb_array_length(coalesce(cj.work_experiences::jsonb, '[]'::jsonb)) > 0 AS f_employers,
                    jsonb_array_length(coalesce(cj.resumes::jsonb, '[]'::jsonb)) > 0 AS f_resume,
                    EXISTS (SELECT 1 FROM jsonb_array_elements(coalesce(cj.raw_payload::jsonb->'fieldsValues', '[]'::jsonb)) e
                            WHERE e->'field'->>'name' = 'Récap Entretien'
                              AND btrim(coalesce(e->>'value', '')) NOT IN ('', 'null')) AS f_report
                FROM candidate_jarvi cj
                LEFT JOIN candidate c ON c.id = cj.candidate_id
                LEFT JOIN person p ON p.id = c.person_id
                WHERE cj.deleted_at IS NULL
            )
            SELECT
                count(*) AS total,
                count(*) FILTER (WHERE f_given_name) AS given_name,
                count(*) FILTER (WHERE f_family_name) AS family_name,
                count(*) FILTER (WHERE f_linkedin) AS linkedin,
                count(*) FILTER (WHERE f_employers) AS employers,
                count(*) FILTER (WHERE f_photo) AS photo,
                count(*) FILTER (WHERE f_email) AS email,
                count(*) FILTER (WHERE f_phone) AS phone,
                count(*) FILTER (WHERE f_resume) AS resume,
                count(*) FILTER (WHERE f_whatsapp) AS whatsapp,
                count(*) FILTER (WHERE f_report) AS interview_report
            FROM flags
            SQL);
        $row = \is_array($row) ? $row : [];

        $total = (int) ($row['total'] ?? 0);

        return array_map(
            static fn (array $field): array => [
                'key' => $field[0],
                'filled' => (int) ($row[$field[1]] ?? 0),
                'total' => $total,
            ],
            [
                ['givenName', 'given_name'],
                ['familyName', 'family_name'],
                ['linkedin', 'linkedin'],
                ['employers', 'employers'],
                ['photo', 'photo'],
                ['email', 'email'],
                ['phone', 'phone'],
                ['resume', 'resume'],
                ['whatsapp', 'whatsapp'],
                ['interviewReport', 'interview_report'],
            ],
        );
    }

    /** @return array<string, int> */
    private function profileQualifications(): array
    {
        $filled = static fn (string $column): string => \sprintf(
            'SELECT count(*) FROM candidate_jarvi WHERE deleted_at IS NULL AND %s',
            $column,
        );

        return [
            'Ancienneté renseignée' => $this->count($filled('experience_years > 0')),
            'Durée sur le poste actuel' => $this->count($filled('current_role_months > 0')),
            'Niveau d’études dérivé' => $this->count($filled('education_level IS NOT NULL')),
            'Date de modification source' => $this->count($filled('jarvi_updated_at IS NOT NULL')),
            'Séniorité SENIOR' => $this->count($filled("seniority = 'SENIOR'")),
            'Séniorité CONFIRME' => $this->count($filled("seniority = 'CONFIRME'")),
            'Séniorité JUNIOR' => $this->count($filled("seniority = 'JUNIOR'")),
        ];
    }

    /** @return array<string, int> */
    private function currentPosition(): array
    {
        $certainty = static fn (string $value): string => \sprintf(
            "SELECT count(*) FROM candidate_jarvi WHERE deleted_at IS NULL AND current_position_certainty = '%s'",
            $value,
        );

        return [
            'CERTAIN' => $this->count($certainty('CERTAIN')),
            'AMBIGUOUS' => $this->count($certainty('AMBIGUOUS')),
            'ABSENT' => $this->count($certainty('ABSENT')),
            'Métier reconnu' => $this->count(
                'SELECT count(*) FROM candidate_jarvi WHERE deleted_at IS NULL AND current_job_concept_id IS NOT NULL',
            ),
        ];
    }

    /** @return array<string, int> */
    private function derivedTables(): array
    {
        return [
            'Expérience secteur — candidats' => $this->count('SELECT count(DISTINCT candidate_id) FROM candidate_sector_experience'),
            'Expérience secteur — lignes' => $this->count('SELECT count(*) FROM candidate_sector_experience'),
            'Expérience métier — candidats' => $this->count('SELECT count(DISTINCT candidate_id) FROM candidate_job_experience'),
            'Expérience métier — lignes' => $this->count('SELECT count(*) FROM candidate_job_experience'),
            'Expérience métier — postes rattachés' => $this->count('SELECT coalesce(sum(positions), 0) FROM candidate_job_experience'),
            'Expérience métier — dont rattachés à l’identique' => $this->count('SELECT coalesce(sum(exact_positions), 0) FROM candidate_job_experience'),
            'Langues — candidats' => $this->count('SELECT count(DISTINCT candidate_id) FROM candidate_language'),
            'Langues — lignes' => $this->count('SELECT count(*) FROM candidate_language'),
        ];
    }

    /** @return array<string, int> */
    private function referentials(): array
    {
        return [
            'Concepts métier' => $this->count("SELECT count(*) FROM vocabulary_concept WHERE deleted_at IS NULL AND type = 'METIER'"),
            'Concepts compétence' => $this->count("SELECT count(*) FROM vocabulary_concept WHERE deleted_at IS NULL AND type = 'COMPETENCE'"),
            'Libellés' => $this->count('SELECT count(*) FROM vocabulary_label WHERE deleted_at IS NULL'),
            'Concepts proches' => $this->count('SELECT count(*) FROM vocabulary_concept_similarity'),
            'Entreprises classées' => $this->count('SELECT count(*) FROM company_sector'),
        ];
    }

    private function count(string $sql): int
    {
        return (int) $this->connection->fetchOne($sql);
    }
}
