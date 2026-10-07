<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use App\Domain\Vocabulary\TermNormalizer;
use App\Domain\Vocabulary\TitleConceptResolver;
use App\Entity\Candidate;
use App\Entity\CandidateJobExperience;
use App\Entity\CandidateJobTitle;
use App\Entity\VocabularyConcept;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Projette le parcours d'un candidat en deux formes cherchables, depuis les positions Jarvi.
 *
 * LE SENS — les métiers que le dictionnaire reconnaît dans les intitulés, avec la durée cumulée
 * (task 095). C'est ce qui porte l'élargissement : chercher « software engineer » retrouve
 * « Développeur logiciel ».
 *
 * LA LETTRE — les intitulés eux-mêmes, sous leur forme canonique (task API-037). C'est le filet :
 * un intitulé que le dictionnaire ignore n'a aucune ligne de métier où s'écrire, et le profil qui le
 * porte serait introuvable. Aucune expansion ici, aucune traduction — on garde ce qui est écrit.
 *
 * LES DEUX ENSEMBLE, JAMAIS L'UNE SANS L'AUTRE. Une troisième source de vérité sur « ce que ce
 * profil a fait » finirait par diverger des deux premières : le calcul se fait donc en un seul
 * endroit, appelé par l'ingestion (task 020) et par la reprojection du dictionnaire (task API-035).
 *
 * MISE À JOUR EN PLACE, jamais purge puis réinsertion : Doctrine applique les insertions avant les
 * suppressions, si bien que recréer une ligne identique violerait la contrainte unique. Seul ce qui
 * a disparu du parcours est retiré.
 */
final readonly class JobProjectionSynchronizer
{
    /** La longueur de `candidate_job_title.title`. Mesuré sur le vivier : 100 au maximum. */
    private const int MAX_LENGTH = 190;

    public function __construct(
        private EntityManagerInterface $em,
        private TitleConceptResolver $resolver,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $positions `positions` Jarvi
     *
     * @return int lignes ajoutées ou retirées, les deux projections confondues — zéro quand rien ne
     *             change pour ce candidat, ce qui est le cas de l'immense majorité d'une reprojection
     */
    public function sync(Candidate $candidate, array $positions): int
    {
        return $this->syncExperiences($candidate, $positions) + $this->syncTitles($candidate, $positions);
    }

    /** @param list<array<string, mixed>> $positions */
    private function syncExperiences(Candidate $candidate, array $positions): int
    {
        $wanted = JobExperienceCalculator::fromPositions($positions, $this->resolver);
        $rows = $candidate->getJobExperiences();
        $moved = 0;

        foreach ($rows as $row) {
            $conceptId = $row->concept->getId();
            if (null !== $conceptId && isset($wanted[$conceptId])) {
                $row->months = $wanted[$conceptId]['months'];
                $row->positions = $wanted[$conceptId]['positions'];
                $row->exactPositions = $wanted[$conceptId]['exactPositions'];
                unset($wanted[$conceptId]);
                continue;
            }
            $rows->removeElement($row);   // métier disparu du parcours : orphanRemoval le supprime
            ++$moved;
        }

        foreach ($wanted as $conceptId => $experience) {
            $rows->add(new CandidateJobExperience(
                $candidate,
                $this->em->getReference(VocabularyConcept::class, $conceptId),
                $experience['months'],
                $experience['positions'],
                $experience['exactPositions'],
            ));
            ++$moved;
        }

        return $moved;
    }

    /**
     * Les intitulés canoniques du parcours, dédoublonnés.
     *
     * Ils ne dépendent PAS du dictionnaire : une reprojection ne les déplace donc jamais, elle ne
     * fait que les poser la première fois — ce qui est exactement ce dont le vivier déjà chargé a
     * besoin pour entrer dans le filet.
     *
     * @param list<array<string, mixed>> $positions
     */
    private function syncTitles(Candidate $candidate, array $positions): int
    {
        $normalizer = new TermNormalizer();
        $wanted = [];
        foreach ($positions as $position) {
            $title = $normalizer->canonicalizeTitle((string) ($position['title'] ?? ''));
            // Un intitulé vide ne dit rien, et un intitulé plus long que la colonne serait tronqué
            // en silence par Postgres — mieux vaut ne pas le poser que le poser faux.
            if ('' !== $title && mb_strlen($title) <= self::MAX_LENGTH) {
                $wanted[$title] = true;
            }
        }

        $rows = $candidate->getJobTitles();
        $moved = 0;

        foreach ($rows as $row) {
            if (isset($wanted[$row->title])) {
                unset($wanted[$row->title]);
                continue;
            }
            $rows->removeElement($row);
            ++$moved;
        }

        foreach (array_keys($wanted) as $title) {
            $rows->add(new CandidateJobTitle($candidate, $title));
            ++$moved;
        }

        return $moved;
    }
}
