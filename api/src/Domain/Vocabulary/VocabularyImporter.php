<?php

declare(strict_types=1);

namespace App\Domain\Vocabulary;

use App\Doctrine\Filter\SoftDeleteFilter;
use App\Entity\VocabularyConcept;
use App\Entity\VocabularyLabel;
use App\Enum\VocabularyLabelSource;
use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Charge une taxonomie enrichie (JSON produit hors ligne) dans les tables `vocabulary_*`.
 * Upsert idempotent : un concept par (type, pref_label), un libellé par (concept, label, lang).
 * Le statut vient du FICHIER (task 113) : `taxonomy.json` est la source de la validation humaine,
 * relisible en `git diff` et survivant à toute réingestion. Sans mention explicite, CANDIDAT.
 *
 * LE FICHIER FAIT AUTORITÉ SUR L'APPARTENANCE, PAS SEULEMENT SUR L'AJOUT (task API-035). L'import
 * savait attacher un libellé à un concept ; il ne savait pas l'en détacher. Un rattachement erroné
 * était donc définitif — le retirer du fichier ne produisait rien, et déplacer un terme d'un concept
 * vers un autre le laissait sur les deux. Un libellé qu'un concept du fichier ne revendique plus est
 * désormais RETIRÉ, en douceur : `deleted_at` est la seule marque que tous les consommateurs
 * respectent déjà (recherche, suggestion, résolution d'intitulé), là où `status` n'en couvre que
 * deux sur trois.
 *
 * LE FILTRE SOFT DELETE EST DONC LEVÉ LE TEMPS DE L'IMPORT. Sans ça, un terme retiré puis rendu au
 * fichier resterait invisible à la relecture et l'import tenterait de le RECRÉER — l'unicité
 * (concept, label, lang) rejetterait le doublon. Le retrait doit rester réversible, donc ce qui a
 * été retiré doit rester lisible ici.
 */
final readonly class VocabularyImporter
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param array<string, mixed> $taxonomy
     */
    public function import(array $taxonomy, bool $dryRun = false): VocabularyImportReport
    {
        $filters = $this->em->getFilters();
        $filtered = $filters->isEnabled(SoftDeleteFilter::NAME);
        if ($filtered) {
            $filters->disable(SoftDeleteFilter::NAME);
        }

        try {
            $report = new VocabularyImportReport();
            $this->importSac($taxonomy['competences'] ?? [], VocabularyType::COMPETENCE, $report);
            $this->importSac($taxonomy['postes'] ?? [], VocabularyType::METIER, $report);

            if (!$dryRun) {
                $this->em->flush();
            }

            return $report;
        } finally {
            if ($filtered) {
                $filters->enable(SoftDeleteFilter::NAME);
            }
        }
    }

    /**
     * Un concept s'identifie par son CODE stable (`competence-001`), pas par son libellé : renommer
     * « Ingénieur d'études » ne doit pas créer un doublon `candidat` et orpheliner le validé.
     *
     * Repli sur le libellé tant que le stock n'a pas de code — il s'en verra attribuer un au passage.
     */
    private function findConcept(VocabularyType $type, string $label, ?string $code): ?VocabularyConcept
    {
        $repository = $this->em->getRepository(VocabularyConcept::class);

        return (null !== $code ? $repository->findOneBy(['code' => $code]) : null)
            ?? $repository->findOneBy(['type' => $type, 'prefLabel' => $label]);
    }

    /**
     * Statut final = celui du fichier, SAUF si le fichier n'exprime aucune opinion.
     *
     * `candidat` est la valeur par défaut de tout ce que la machine produit : elle signifie « pas
     * encore relu », pas « rejeté ». Elle ne doit donc **jamais rétrograder** un concept validé
     * ailleurs — sinon une validation faite depuis l'écran admin (task 120) disparaîtrait au premier
     * réimport, en silence. Une dépréciation reste possible : elle s'exprime explicitement.
     */
    private static function mergedStatus(VocabularyStatus $current, mixed $raw): VocabularyStatus
    {
        $fromFile = self::status($raw);

        return VocabularyStatus::CANDIDAT === $fromFile ? $current : $fromFile;
    }

    /**
     * Statut porté par le FICHIER — c'est lui qui fait autorité (task 113). Le fichier versionné est
     * la source de la validation humaine : elle se relit en `git diff` et survit à toute
     * réingestion. Une valeur absente ou inconnue retombe sur CANDIDAT, jamais sur VALIDE : on
     * n'accorde pas de confiance par défaut.
     */
    private static function status(mixed $raw): VocabularyStatus
    {
        return \is_string($raw)
            ? (VocabularyStatus::tryFrom(mb_strtoupper($raw)) ?? VocabularyStatus::CANDIDAT)
            : VocabularyStatus::CANDIDAT;
    }

    /**
     * @param array<string, mixed> $sac
     */
    private function importSac(array $sac, VocabularyType $type, VocabularyImportReport $report): void
    {
        $concepts = $sac['concepts'] ?? [];
        if (!\is_array($concepts)) {
            return;
        }

        foreach ($concepts as $data) {
            if (!\is_array($data)) {
                continue;
            }
            $concept = $this->upsertConcept($data, $type, $report);
            if ($concept instanceof VocabularyConcept) {
                $this->syncLabels($concept, $data['synonymes'] ?? [], $report);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function upsertConcept(array $data, VocabularyType $type, VocabularyImportReport $report): ?VocabularyConcept
    {
        $label = (string) ($data['label_canonique'] ?? '');
        if ('' === $label) {
            return null;
        }

        $code = \is_string($data['id'] ?? null) && '' !== $data['id'] ? $data['id'] : null;
        $concept = $this->findConcept($type, $label, $code);
        if (!$concept instanceof VocabularyConcept) {
            $concept = new VocabularyConcept($type, $label, $code);
            $this->em->persist($concept);
            ++$report->conceptsCreated;
        } else {
            // Le fichier fait autorité : un concept renommé garde son identité et suit son libellé.
            $concept->prefLabel = $label;
            $concept->code ??= $code;
            ++$report->conceptsUpdated;
        }
        $concept->frequency = (int) ($data['frequence_totale'] ?? 0);
        $concept->escoUri = \is_string($data['esco_uri'] ?? null) ? $data['esco_uri'] : null;
        $concept->status = self::mergedStatus($concept->status, $data['statut'] ?? null);

        return $concept;
    }

    /** Attache au concept ce que le fichier lui donne, et détache le reste. */
    private function syncLabels(VocabularyConcept $concept, mixed $synonymes, VocabularyImportReport $report): void
    {
        $claimed = [];
        foreach (\is_array($synonymes) ? $synonymes : [] as $syn) {
            $key = \is_array($syn) ? $this->importLabel($concept, $syn, $report) : null;
            if (null !== $key) {
                $claimed[] = $key;
            }
        }

        $this->pruneLabels($concept, $claimed, $report);
    }

    /**
     * @param array<string, mixed> $syn
     *
     * @return string|null clé (label, langue) revendiquée par le fichier, `null` si l'entrée est vide
     */
    private function importLabel(VocabularyConcept $concept, array $syn, VocabularyImportReport $report): ?string
    {
        $label = (string) ($syn['terme'] ?? '');
        if ('' === $label) {
            return null;
        }
        $lang = (string) ($syn['langue'] ?? 'fr');

        $existing = $this->em->getRepository(VocabularyLabel::class)
            ->findOneBy(['concept' => $concept, 'label' => $label, 'lang' => $lang]);
        if ($existing instanceof VocabularyLabel) {
            $existing->frequency = (int) ($syn['frequence'] ?? 0);
            if ($existing->isDeleted()) {
                // Le fichier le revendique de nouveau : on rend la ligne plutôt que d'en créer une
                // seconde, que l'unicité (concept, label, lang) refuserait de toute façon.
                $existing->restore();
                ++$report->labelsRestored;
            } else {
                ++$report->labelsUpdated;
            }

            return self::key($label, $lang);
        }

        $this->em->persist(
            new VocabularyLabel($concept, $label, $lang, VocabularyLabelSource::JARVI, (int) ($syn['frequence'] ?? 0)),
        );
        ++$report->labelsCreated;

        return self::key($label, $lang);
    }

    /**
     * Retire les libellés que le concept ne revendique plus dans le fichier.
     *
     * Borné aux concepts que le fichier DÉCRIT : un concept absent garde ses libellés intacts. Une
     * taxonomie tronquée ou un sac oublié ne doit pas vider la base au premier import — le silence
     * ne vaut pas retrait.
     *
     * @param list<string> $claimed
     */
    private function pruneLabels(VocabularyConcept $concept, array $claimed, VocabularyImportReport $report): void
    {
        $keep = array_flip($claimed);

        foreach ($concept->labels as $label) {
            if ($label->isDeleted() || isset($keep[self::key($label->label, $label->lang)])) {
                continue;
            }
            $label->softDelete();
            ++$report->labelsRemoved;
        }
    }

    private static function key(string $label, string $lang): string
    {
        return $label."\0".$lang;
    }
}
