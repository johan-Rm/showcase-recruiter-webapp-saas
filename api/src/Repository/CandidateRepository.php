<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Candidate;
use App\Entity\CandidateJobTitle;
use App\Entity\PresencePeriod;
use App\Enum\EducationLevel;
use App\Enum\LanguageProficiency;
use App\Enum\ProfileVisibility;
use App\Enum\SyncStatus;
use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Candidate>
 */
final class CandidateRepository extends ServiceEntityRepository
{
    /**
     * En deçà, le repli sur l'intitulé écrit ne se déclenche pas.
     *
     * Il cherche une sous-chaîne : « dev » ramènerait presque tout le vivier, et une liste qui
     * ramène tout ne renseigne pas plus qu'une liste vide. Même seuil que le rattrapage aux fautes
     * de frappe des suggestions (task API-023), pour la même raison — un terme court ne discrimine
     * pas.
     */
    private const int MIN_JOB_FALLBACK_LENGTH = 5;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Candidate::class);
    }

    public function findOneByUuid(string $uuid): ?Candidate
    {
        if (!Uuid::isValid($uuid)) {
            return null;
        }

        return $this->findOneBy(['uuid' => Uuid::fromString($uuid)]);
    }

    /**
     * Plusieurs profils par leurs identifiants publics, **prêts à mettre en carte**.
     *
     * Les jointures ne sont pas décoratives : la mise en carte lit la personne et la réplica pro de
     * chaque profil, et sans elles Doctrine irait les chercher une par une — mille profils, deux
     * mille requêtes.
     *
     * Les identifiants invalides sont ignorés plutôt que refusés : cette lecture sert un plan de
     * travail déjà constitué, pas une saisie utilisateur.
     *
     * @param list<string> $uuids
     *
     * @return list<Candidate>
     */
    public function findByUuids(array $uuids): array
    {
        $valid = array_values(array_filter($uuids, static fn (string $uuid): bool => Uuid::isValid($uuid)));
        if ([] === $valid) {
            return [];
        }

        /** @var list<Candidate> $candidates */
        $candidates = $this->createQueryBuilder('c')
            ->addSelect('p', 'j')
            ->innerJoin('c.person', 'p')
            ->leftJoin('c.jarvi', 'j')
            ->andWhere('c.uuid IN (:uuids)')
            ->setParameter('uuids', array_map(static fn (string $uuid): Uuid => Uuid::fromString($uuid), $valid))
            ->getQuery()
            ->getResult();

        $this->warmCardCollections($candidates);

        return $candidates;
    }

    /**
     * Remplit d'avance les trois collections que la mise en carte lit — présences, langues, secteurs.
     *
     * Sans elles, chaque fiche va chercher les siennes au moment d'être mise en carte : trois
     * requêtes par profil. Sur une page de trente, personne ne le voit ; sur un millier de profils,
     * c'est **trois mille requêtes** et plusieurs secondes — mesuré avant de le corriger.
     *
     * Une requête PAR COLLECTION et non une seule qui les joindrait toutes : trois jointures de
     * collections sur la même racine produisent un produit cartésien, et ce qu'on gagnerait en
     * allers-retours se perdrait en lignes. Trois requêtes, quel que soit le nombre de profils.
     *
     * @param list<Candidate> $candidates
     */
    private function warmCardCollections(array $candidates): void
    {
        if ([] === $candidates) {
            return;
        }

        foreach (['presencePeriods', 'languages', 'sectorExperiences'] as $collection) {
            $this->getEntityManager()
                ->createQuery(
                    \sprintf('SELECT c, x FROM %s c LEFT JOIN c.%s x WHERE c IN (:candidates)', Candidate::class, $collection),
                )
                ->setParameter('candidates', $candidates)
                ->getResult();
        }
    }

    /**
     * Recherche une projection par son ancre Jarvi (clé d'idempotence de l'ingestion, tâche 020).
     */
    public function findOneByJarviProfileId(string $jarviProfileId): ?Candidate
    {
        if (!Uuid::isValid($jarviProfileId)) {
            return null;
        }

        return $this->findOneBy(['jarviProfileId' => Uuid::fromString($jarviProfileId)]);
    }

    /**
     * Rattachement Magic Link candidat (tâche 033) : retrouve une projection par email de contact.
     * D'abord l'email showcase-recruiter-webapp-saas (`Person.contactEmail`, candidats onboardés ou déjà rattachés), puis
     * les emails de la réplica Jarvi (`candidate_jarvi.contacts`, profils importés sans compte).
     */
    public function findOneByContactEmail(string $email): ?Candidate
    {
        $normalized = mb_strtolower(trim($email));
        if ('' === $normalized) {
            return null;
        }

        $byPerson = $this->createQueryBuilder('c')
            ->innerJoin('c.person', 'p')
            ->andWhere('LOWER(p.contactEmail) = :email')->setParameter('email', $normalized)
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();

        return $byPerson instanceof Candidate ? $byPerson : $this->findOneByJarviContactEmail($normalized);
    }

    /**
     * Retrouve une projection dont la réplica Jarvi (JSON opaque) porte cet email de contact.
     * SQL natif faute d'opérateurs JSON portables en DQL. `$email` doit déjà être normalisé.
     *
     * `deleted_at IS NULL` reste écrit à la main ICI, contrairement au reste du repository : le
     * filtre Doctrine `soft_delete` ne s'applique qu'aux requêtes ORM, jamais au SQL natif.
     */
    private function findOneByJarviContactEmail(string $email): ?Candidate
    {
        $sql = <<<'SQL'
            SELECT c.id
            FROM candidate c
            JOIN candidate_jarvi cj ON cj.candidate_id = c.id
            WHERE c.deleted_at IS NULL
              AND json_typeof(cj.contacts) = 'array'
              AND EXISTS (
                SELECT 1 FROM json_array_elements(cj.contacts) AS contact
                WHERE contact->>'type' = 'email' AND LOWER(contact->>'value') = :email
              )
            LIMIT 1
            SQL;
        $id = $this->getEntityManager()->getConnection()->fetchOne($sql, ['email' => $email]);

        return false === $id ? null : $this->find($id);
    }

    /**
     * Projections actuellement actives (dans le périmètre), pour la réconciliation (tâche 020).
     *
     * @return list<Candidate>
     */
    public function findActive(): array
    {
        return $this->findBy(['active' => true]);
    }

    /**
     * Projections désactivées (sorties du périmètre) mais non supprimées — candidates à une
     * réactivation si elles reviennent dans le périmètre Jarvi (tâche 020).
     *
     * @return list<Candidate>
     */
    public function findInactive(): array
    {
        return $this->findBy(['active' => false]);
    }

    /**
     * Vivier COMPLET pour l'espace admin (opérateur) : AUCUN filtre de visibilité, consentement ou
     * activité — contrairement à search(), l'admin voit tout le vivier (masqués, non consentis,
     * désactivés). Person + réplica Jarvi fetch-joinées (lues par chaque ligne) ; presencePeriods
     * chargées en une requête pour la page. Les soft-delete restent exclus (filtre Doctrine ORM).
     *
     * @return list<Candidate>
     */
    public function findForAdmin(int $page, int $perPage): array
    {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.person', 'p')->addSelect('p')
            ->leftJoin('c.jarvi', 'j')->addSelect('j')
            ->orderBy('c.createdAt', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($qb->getQuery(), fetchJoinCollection: false);
        $items = array_values(iterator_to_array($paginator));

        // Initialise les presencePeriods de toute la page en UNE requête (cf. search() : évite le N+1).
        if ([] !== $items) {
            $this->getEntityManager()->createQuery(
                'SELECT c, pp FROM App\Entity\Candidate c LEFT JOIN c.presencePeriods pp WHERE c IN (:page)'
            )->setParameter('page', $items)->getResult();
        }

        return $items;
    }

    /** Total du vivier admin (aucun filtre ; soft-delete exclus par le filtre Doctrine ORM). */
    public function countForAdmin(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Instantané de supervision du vivier (tâche 101) : volumes par état pour l'écran Jarvi admin.
     * Aucun filtre (l'admin voit tout) ; soft-delete exclus par le filtre Doctrine ORM.
     *
     * @return array{total: int, active: int, inactive: int, withConsent: int, synced: int, pending: int, failed: int}
     */
    public function vivierStats(): array
    {
        $total = $this->countForAdmin();
        $active = (int) $this->createQueryBuilder('c')->select('COUNT(c.id)')
            ->andWhere('c.active = true')->getQuery()->getSingleScalarResult();
        $withConsent = (int) $this->createQueryBuilder('c')->select('COUNT(c.id)')
            ->andWhere('c.consentGivenAt IS NOT NULL')->getQuery()->getSingleScalarResult();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $total - $active,
            'withConsent' => $withConsent,
            'synced' => $this->countByStatus(SyncStatus::SYNCED),
            'pending' => $this->countByStatus(SyncStatus::PENDING),
            'failed' => $this->countByStatus(SyncStatus::FAILED),
        ];
    }

    private function countByStatus(SyncStatus $status): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.syncStatus = :status')->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche filtrée du vivier (profils VISIBLE uniquement), paginée.
     *
     * @return array{0: list<Candidate>, 1: int} [page d'items, total]
     */
    public function search(CandidateSearchCriteria $criteria): array
    {
        $qb = $this->searchableQuery()
            // Réplica pro Jarvi fetch-joinée : lue par toutes les cartes (headline, skills) → évite
            // le N+1 sur la page.
            ->addSelect('p')
            ->addSelect('j')
            ->orderBy('c.updatedAt', 'DESC')
            ->addOrderBy('c.id', 'DESC');

        $this->applyFilters($qb, $criteria);

        $page = max(1, $criteria->page);
        $perPage = max(1, min(2000, $criteria->perPage));
        $qb->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);

        $paginator = new Paginator($qb->getQuery(), fetchJoinCollection: false);
        $items = array_values(iterator_to_array($paginator));

        // Initialise les collections presencePeriods de toute la page en UNE requête : sans ça,
        // le mapping les charge candidat par candidat (N+1 mesuré à ~0,8 s sur 1 626 profils).
        if ([] !== $items) {
            $this->getEntityManager()->createQuery(
                'SELECT c, pp FROM App\Entity\Candidate c LEFT JOIN c.presencePeriods pp WHERE c IN (:page)'
            )->setParameter('page', $items)->getResult();
        }

        return [$items, \count($paginator)];
    }

    /**
     * Ids internes de TOUS les candidats matchant les critères — sans pagination ni hydratation.
     *
     * Sert au recalcul d'appartenance (SearchResultMembership, task 072) : on compare des jeux
     * d'ids, on n'affiche rien.
     *
     * @return list<int>
     */
    public function matchingIds(CandidateSearchCriteria $criteria): array
    {
        $qb = $this->searchableQuery()->select('c.id');
        $this->applyFilters($qb, $criteria);

        /** @var list<array{id: int|string}> $rows */
        $rows = $qb->getQuery()->getScalarResult();

        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    /**
     * Socle commun à la recherche et au recalcul d'appartenance : le vivier réellement exposable
     * (profils VISIBLE, dans le périmètre Jarvi, consentis), avec les jointures dont dépendent les
     * filtres (`p` = person, `j` = réplica Jarvi).
     */
    private function searchableQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.person', 'p')
            ->leftJoin('c.jarvi', 'j')
            ->andWhere('c.visibility = :visible')->setParameter('visible', ProfileVisibility::VISIBLE)
            // Tâche 014 : la recherche tourne sur la réplica locale → on exclut les profils sortis
            // du périmètre Jarvi (désactivés par la réconciliation, tâche 020).
            ->andWhere('c.active = true')
            ->andWhere('c.consentGivenAt IS NOT NULL');
    }

    /** Traduit chaque critère renseigné en clause WHERE (les critères absents sont ignorés). */
    private function applyFilters(QueryBuilder $qb, CandidateSearchCriteria $criteria): void
    {
        // Mots-clés : OU entre eux, ET avec le reste. Chacun cherche dans TROIS endroits du profil,
        // dans l'ordre où on lit une fiche — ligne de titre, compétences, intitulés de poste écrits.
        //
        // LE TEXTE DE PRÉSENTATION EN EST ABSENT, et ce n'est pas un oubli. Il est renseigné sur 2 %
        // du vivier, et l'y chercher coûte à lui seul autant que tout le reste : faute d'index de
        // recherche floue sur cette colonne, une seule branche non indexée fait abandonner les index
        // de TOUTES les autres. Mesuré sur 3 898 profils : 3 ms sans lui, 53 à 74 ms avec.
        //
        // CES TROIS-LÀ, ET PAS LES AUTRES. Ce sont des champs DÉJÀ SERVIS au recruteur, dévoilé ou
        // non, ce qui rend sûr l'extrait que la correspondance en rapporte. L'employeur, lui, attend
        // le dévoilement — il ré-identifie là où un intitulé qualifie sans nommer —, et une
        // description d'expérience en texte libre peut le nommer sans qu'aucune règle ne puisse le
        // prévoir (cf. `CandidateCardOutput`).
        //
        // LES INTITULÉS PASSENT PAR LEUR PROJECTION, jamais par la structure brute du parcours.
        // Celle-ci sérialise aussi les NOMS de ses rubriques : la lire d'un bloc ferait remonter tout
        // le vivier dès qu'un recruteur tape « poste » ou « entreprise ». Les compétences, elles,
        // sont une simple liste de textes — les lire d'un bloc reste sans danger, et sans index
        // utilisable de toute façon pour une recherche par sous-chaîne.
        if ([] !== $criteria->keywords) {
            $alternatives = [];
            foreach ($criteria->keywords as $index => $keyword) {
                $parameter = 'keyword'.$index;
                $alternatives[] = \sprintf(
                    '(LOWER(j.headline) LIKE :%1$s'
                    .' OR LOWER(TEXT(j.skills)) LIKE :%1$s'
                    // L'ALIAS PORTE LE RANG DU MOT-CLÉ. Les mots-clés se relient en OU dans UNE
                    // SEULE requête : un alias fixe se retrouverait déclaré autant de fois qu'il y a
                    // de mots, et Doctrine refuse la requête entière — pas seulement la seconde
                    // sous-requête.
                    .' OR EXISTS (SELECT 1 FROM %2$s %3$s WHERE %3$s.candidate = c'
                    .' AND %3$s.deletedAt IS NULL AND LOWER(%3$s.title) LIKE :%1$s))',
                    $parameter,
                    CandidateJobTitle::class,
                    'kjt'.$index,
                );
                $qb->setParameter($parameter, '%'.mb_strtolower($keyword).'%');
            }

            $qb->andWhere('('.implode(' OR ', $alternatives).')');
        }
        // L'EXPÉRIENCE SE COMPTE DANS LE MÉTIER CHERCHÉ quand il y en a un — la borne part alors avec
        // le filtre de métier, qui seul sait sur quelle correspondance elle porte. Ici ne reste que
        // la carrière entière, celle d'une recherche qui ne nomme aucun métier.
        if (
            null !== $criteria->experienceMin && $criteria->experienceMin > 0
            && (null === $criteria->job || '' === $criteria->job)
        ) {
            $qb->andWhere('j.experienceYears >= :experienceMin')
                ->setParameter('experienceMin', $criteria->experienceMin);
        }
        if (null !== $criteria->availability) {
            $qb->andWhere('c.availability = :availability')->setParameter('availability', $criteria->availability);
        }
        if (null !== $criteria->returnHorizon) {
            $qb->andWhere('c.returnHorizon = :returnHorizon')->setParameter('returnHorizon', $criteria->returnHorizon);
        }
        // Niveau d'études (task 112/123) : « au moins » ce niveau, et EXCLUT les profils sans niveau
        // reconnu (≈ 23 % du vivier) — on ne suppose pas un diplôme absent, comme pour la langue.
        if (null !== $criteria->educationLevel) {
            $qb->andWhere('c.educationLevel IN (:educationLevels)')
                ->setParameter('educationLevels', EducationLevel::atLeast($criteria->educationLevel));
        }
        if (null !== $criteria->geoMobility) {
            $qb->andWhere('c.geoMobility = :geoMobility')->setParameter('geoMobility', $criteria->geoMobility);
        }
        if (null !== $criteria->hasPiedATerre) {
            $qb->andWhere('c.hasPiedATerre = :hasPiedATerre')->setParameter('hasPiedATerre', $criteria->hasPiedATerre);
        }
        if (null !== $criteria->star) {
            $qb->andWhere($criteria->star ? 'c.starInterviewedAt IS NOT NULL' : 'c.starInterviewedAt IS NULL');
        }
        if (null !== $criteria->country && '' !== $criteria->country) {
            $qb->andWhere('UPPER(p.country) = :country')->setParameter('country', mb_strtoupper($criteria->country));
        }
        $this->applyPresenceFilter($qb, $criteria);
        // Secteur et fonction sont des valeurs libres (Jarvi est maître du profil pro) : on compare
        // sans tenir compte de la casse, et les listes du cockpit se déduiront des valeurs présentes.
        if (null !== $criteria->sector && '' !== $criteria->sector) {
            $qb->andWhere('LOWER(c.desiredSector) = :sector')->setParameter('sector', mb_strtolower($criteria->sector));
        }
        // Secteur d'EXPÉRIENCE (parcours), distinct du secteur RECHERCHÉ ci-dessus. EXISTS plutôt
        // qu'une jointure : un candidat a plusieurs secteurs, une jointure dupliquerait ses lignes
        // et fausserait la pagination.
        if (null !== $criteria->experienceSector) {
            $qb->andWhere('EXISTS (SELECT 1 FROM App\\Entity\\CandidateSectorExperience cse WHERE cse.candidate = c AND cse.sector = :experienceSector)')
                ->setParameter('experienceSector', $criteria->experienceSector);
        }
        // Métier EXERCÉ (task 095), avec EXPANSION par le vocabulaire (task 125) : le terme cherché
        // résout vers le(s) concept(s) via le libellé canonique OU un libellé VIVANT — les libellés
        // dépréciés « mot à mot » (task 124) ne résolvent plus — puis on matche le concept exercé du
        // candidat. Chercher « software engineer » retrouve donc le concept « Développeur logiciel ».
        if (null !== $criteria->job && '' !== $criteria->job) {
            $this->applyJobFilter($qb, $criteria->job, $criteria->experienceMin);
        }
        // Langue parlée. Le niveau minimum n'a de sens qu'adossé à une langue, et il EXCLUT les
        // entrées sans niveau : un tiers du vivier n'en porte pas, on ne le suppose pas.
        if (null !== $criteria->language && '' !== $criteria->language) {
            $dql = 'EXISTS (SELECT 1 FROM App\\Entity\\CandidateLanguage cl WHERE cl.candidate = c AND cl.language = :language';
            if (null !== $criteria->languageLevel) {
                $dql .= ' AND cl.proficiency IN (:languageLevels)';
            }
            $qb->andWhere($dql.')')->setParameter('language', mb_strtolower($criteria->language));
            if (null !== $criteria->languageLevel) {
                $qb->setParameter('languageLevels', LanguageProficiency::atLeast($criteria->languageLevel));
            }
        }
        if (null !== $criteria->function && '' !== $criteria->function) {
            $qb->andWhere('LOWER(c.desiredFunction) = :function')->setParameter('function', mb_strtolower($criteria->function));
        }
    }

    /**
     * Le métier exercé, avec son filet.
     *
     * D'ABORD LE SENS (task 095, expansion task 125) : le terme résout vers un ou plusieurs concepts
     * par le libellé canonique ou un libellé VIVANT — les libellés dépréciés « mot à mot »
     * (task 124) ne résolvent plus —, puis on retient les candidats qui exercent ce concept.
     * Chercher « software engineer » retrouve ainsi « Développeur logiciel ». La PRÉSENCE du métier
     * suffit à faire entrer ; la durée n'entre en jeu que si le recruteur l'exige, et elle se compte
     * alors sur ce que le dictionnaire a rattaché — avec la réserve dite plus bas.
     *
     * PUIS LA LETTRE, ET SEULEMENT SI LE DICTIONNAIRE NE CONNAÎT PAS LE TERME (task API-037). Sans
     * ce filet, un intitulé hors dictionnaire est hors d'atteinte définitivement — 12 % du vivier
     * ne porte aucun métier reconnu, et 5 439 intitulés n'y apparaissent qu'une fois : c'est une
     * traîne, aucun rangement ne la rattrapera. Le repli cherche l'intitulé tel qu'il est écrit,
     * sans expansion ni traduction, ce qui est précisément tout ce qu'on peut promettre sur un
     * terme que le dictionnaire ignore.
     *
     * LA CONDITION DE REPLI EST DANS LA MÊME REQUÊTE, pas décidée en amont. Elle ne dépend pas du
     * candidat — Postgres l'évalue une fois —, et surtout la règle ne se dédouble pas entre une
     * couche qui déciderait et une qui appliquerait. Deux lectures d'une même règle finissent par
     * diverger.
     *
     * LA BORNE D'EXPÉRIENCE VIENT AVEC, et elle ne compte pas la même chose des deux côtés.
     *
     * Par le CONCEPT, elle compte les mois exercés DANS ce métier, sur la correspondance même qui
     * fait entrer le candidat : « architecte, 5 ans » cesse de retenir un architecte de fraîche date
     * qui a fait dix ans d'autre chose.
     *
     * Par le REPLI, il n'y a rien à compter — les intitulés bruts ne portent pas de dates — et la
     * borne retombe sur la carrière entière. Sans ce retour en arrière, un terme que le dictionnaire
     * ignore, posé avec une exigence d'expérience, ne ramènerait plus personne.
     *
     * ⚠️ CE QUE CETTE DURÉE NE VOIT PAS : elle ne totalise que les postes que le dictionnaire a su
     * rattacher — 73 % de ceux du vivier. Un candidat dont un poste porte un intitulé inconnu voit
     * ces mois-là manquer à son compte. Le filtre est donc **strictement plus sévère** que la
     * réalité : il n'ajoute jamais un profil qui ne convient pas, il en écarte qui conviendraient.
     */
    private function applyJobFilter(QueryBuilder $qb, string $job, ?int $experienceMin): void
    {
        $term = mb_strtolower($job);
        $years = null !== $experienceMin && $experienceMin > 0 ? $experienceMin : null;

        $byConcept = 'EXISTS (SELECT 1 FROM App\\Entity\\CandidateJobExperience cje JOIN cje.concept jc '
            .'WHERE cje.candidate = c AND ('
            .'LOWER(jc.prefLabel) = :jobTerm '
            .'OR EXISTS (SELECT 1 FROM App\\Entity\\VocabularyLabel jl '
            .'WHERE jl.concept = jc AND LOWER(jl.label) = :jobTerm AND jl.status != :jobLabelLive))'
            .(null === $years ? '' : ' AND cje.months >= :jobMonthsMin')
            .')';

        $qb->setParameter('jobTerm', $term)
            ->setParameter('jobLabelLive', VocabularyStatus::DEPRECIE);

        if (null !== $years) {
            $qb->setParameter('jobMonthsMin', $years * 12);
        }

        // Trop court pour chercher un texte libre : « dev » ramènerait presque tout le vivier. Le
        // terme reste résolu par le dictionnaire, et ne trouve rien s'il y est inconnu — comme avant.
        // Le repli n'ayant pas lieu, sa borne de carrière n'est pas posée : un paramètre déclaré
        // qu'aucune clause ne consomme fait échouer la requête entière.
        if (mb_strlen($term) < self::MIN_JOB_FALLBACK_LENGTH) {
            $qb->andWhere($byConcept);

            return;
        }

        if (null !== $years) {
            $qb->setParameter('jobCareerMin', $years);
        }

        $qb->andWhere(\sprintf(
            '%s OR (NOT EXISTS (SELECT 1 FROM App\\Entity\\VocabularyConcept fc '
            .'WHERE fc.type = :jobConceptType AND ('
            .'LOWER(fc.prefLabel) = :jobTerm '
            .'OR EXISTS (SELECT 1 FROM App\\Entity\\VocabularyLabel fl '
            .'WHERE fl.concept = fc AND LOWER(fl.label) = :jobTerm AND fl.status != :jobLabelLive))) '
            .'AND EXISTS (SELECT 1 FROM App\\Entity\\CandidateJobTitle cjt '
            .'WHERE cjt.candidate = c AND LOWER(cjt.title) LIKE :jobPattern)%s)',
            $byConcept,
            null === $years ? '' : ' AND j.experienceYears >= :jobCareerMin',
        ))
            ->setParameter('jobConceptType', VocabularyType::METIER)
            ->setParameter('jobPattern', '%'.self::escapeLike($term).'%');
    }

    /** `%` et `_` tapés au clavier sont des caractères, pas des jokers. */
    private static function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    /**
     * ★ Présence Maroc : au moins une période chevauchant la fenêtre [aujourd'hui, +N jours]
     * (`presenceWithinDays`) et/ou dans la ville demandée (`presenceCity`). Les deux critères
     * portent sur la MÊME période (EXISTS unique).
     */
    private function applyPresenceFilter(QueryBuilder $qb, CandidateSearchCriteria $criteria): void
    {
        $withinDays = $criteria->presenceWithinDays;
        $city = null !== $criteria->presenceCity && '' !== $criteria->presenceCity ? $criteria->presenceCity : null;
        if (null === $withinDays && null === $city) {
            return;
        }

        $conditions = ['pp.candidate = c'];
        if (null !== $withinDays) {
            $conditions[] = 'pp.endDate >= :presenceFrom AND pp.startDate <= :presenceUntil';
            $today = new DateTimeImmutable('today');
            $qb->setParameter('presenceFrom', $today)
                ->setParameter('presenceUntil', $today->modify(\sprintf('+%d days', $withinDays)));
        }
        if (null !== $city) {
            $conditions[] = 'LOWER(pp.city) = :presenceCity';
            $qb->setParameter('presenceCity', mb_strtolower($city));
        }

        $qb->andWhere(\sprintf(
            'EXISTS (SELECT pp.id FROM %s pp WHERE %s)',
            PresencePeriod::class,
            implode(' AND ', $conditions),
        ));
    }
}
