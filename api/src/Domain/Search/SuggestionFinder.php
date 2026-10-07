<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Enum\SuggestionKind;
use App\Enum\VocabularyStatus;
use App\Enum\VocabularyType;
use Doctrine\DBAL\Connection;

/**
 * Ce qu'on peut proposer au recruteur pendant qu'il tape — et combien de profils chaque proposition
 * ramènerait.
 *
 * DEUX SOURCES, parce que les deux champs ne cherchent pas la même chose.
 *
 * L'INTITULÉ DE POSTE interroge le dictionnaire de concepts : c'est lui qui fait l'élargissement,
 * « software engineer » devant retrouver « Développeur ». Les propositions sont donc des **concepts**,
 * et les variantes se fondent naturellement — un concept, une ligne, quelle que soit la langue.
 *
 * LES MOTS-CLÉS interrogent les compétences RÉELLEMENT PORTÉES par les profils. Elles ne sont pas
 * rattachées à des concepts côté candidat (seuls les postes le sont), donc pas de fusion bilingue
 * ici : « gestion de projet » et « project management » restent deux propositions.
 *
 * LE COMPTE NE SORT PLUS D'ICI, mais il commande toujours (task API-025). Il servait à prévenir le
 * recruteur — « communication » couvre 28 % du vivier —, et il décrivait du même coup la taille et la
 * composition du portefeuille à qui tapait trois lettres. Il reste donc en base, où il **classe** les
 * propositions de la plus portée à la moins portée, et où il écarte les concepts que personne
 * n'exerce. L'ordre servi en est la seule trace visible.
 *
 * PLUSIEURS PASSAGES, ET CHACUN N'EST PAYÉ QUE SI LE PRÉCÉDENT N'A RIEN RAMENÉ (task API-023).
 *
 * On cherche d'abord le terme au DÉBUT DE N'IMPORTE QUEL MOT du libellé : « logiciel » retrouve
 * « Développeur logiciel », que le préfixe seul laissait passer. Le début de mot, et non la
 * sous-chaîne : « k123 » ne doit pas retrouver « kubernetes sk123 ».
 *
 * Si — et seulement si — ce premier passage ne ramène rien, on rejoue en TOLÉRANT LES FAUTES. Ce qui
 * rend le seuil de similarité économique, c'est justement qu'il ne s'applique jamais au cas nominal :
 * aucun bruit ne peut polluer une recherche qui marchait, et le coût n'est payé que lorsque le
 * recruteur est déjà bloqué.
 *
 * L'INTITULÉ DE POSTE EN INTERCALE UN TROISIÈME, entre les deux (task API-037) : les intitulés
 * réellement écrits dans les parcours, cherchés à la lettre. Il passe AVANT le rattrapage, parce que
 * ce qui existe littéralement vaut mieux que ce qui ressemble — cf. {@see self::jobs()}.
 *
 * En secours, c'est la RESSEMBLANCE qui classe, le nombre de profils ne départageant que les ex æquo.
 * Sans ça, « developeur » place « Ingénieur d'études » (493 profils, ressemblance 0,64) devant
 * « Développeur logiciel » (293, ressemblance 0,82).
 *
 * INSENSIBLE AUX ACCENTS des deux côtés — le terme cherché comme la valeur stockée —, sinon la
 * comparaison ne porte pas sur la même chose.
 *
 * Elle DÉGRADE au lieu de tomber : sans `unaccent`, on retombe sur la comparaison accentuée ; sans
 * `pg_trgm`, le secours ne se déclenche pas. Une base de production qui n'a pas encore reçu
 * `app:search:index` sert moins de propositions, elle n'échoue pas.
 */
final class SuggestionFinder
{
    /** Au-delà, une liste de propositions cesse d'aider et devient un second problème de choix. */
    public const int MAX_LIMIT = 20;

    /**
     * En deçà, on ne tente aucun rattrapage.
     *
     * Un mot court porte trop peu de trigrammes pour être comparé : mesuré, « exel » ne retrouve pas
     * « excel » (0,40) mais ramène quatre métiers sans rapport, tous à 0,60. Le seuil ne peut pas
     * séparer les deux, la LONGUEUR si.
     */
    private const int MIN_APPROXIMATE_LENGTH = 5;

    /**
     * La ressemblance minimale d'un secours.
     *
     * Mesuré sur des fautes réelles : « javascrip » 0,90 · « kubernets » 0,80 · « comunication » 0,80
     * · « developeur » 0,77 · « managment » 0,615. Plus sévère, la moitié des fautes courantes reste
     * muette.
     */
    private const float MIN_PROXIMITY = 0.5;

    /**
     * Ce qui doit perdre son pouvoir quand un recruteur le tape.
     *
     * Pas `preg_quote`, qui échappe pour PCRE : il protège `<` et `>`, or `\<` et `\>` sont des
     * frontières de mot dans les expressions régulières de PostgreSQL. Il les rendrait spéciaux au
     * lieu de les rendre littéraux — l'inverse exact de ce qu'on lui demande.
     */
    private const string REGEX_METACHARACTERS = '\\^$.[]|()*+?{}';

    /**
     * `null` tant qu'on n'a pas regardé. La disponibilité des extensions ne change pas pendant la vie
     * du processus : les demander une fois suffit, et une fois est déjà une requête de trop à répéter
     * sur chaque frappe.
     */
    private ?bool $normalizes = null;

    private ?bool $approximates = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * Ce qu'on propose sur un intitulé de poste — et de quelle nature (task API-037).
     *
     * TROIS PASSAGES, ET CHACUN NE JOUE QUE SI LE PRÉCÉDENT N'A RIEN RAMENÉ :
     *
     * 1. **Les concepts, par correspondance exacte** — le cas nominal, celui qui élargit.
     * 2. **Les intitulés ÉCRITS dans les parcours**, littéralement — le filet. La liste ne proposant
     *    que des concepts, un recruteur n'a aucune raison de deviner qu'il peut taper « architecte
     *    d'intérieur » quand ce métier n'est pas au dictionnaire : une voie de recherche que rien
     *    n'annonce n'existe pas.
     * 3. **Les concepts, en tolérant les fautes de frappe** — le rattrapage.
     *
     * L'ORDRE DES DEUX DERNIERS SE DÉCIDE AU NOMBRE DE MOTS TAPÉS, et c'est tout le sujet.
     *
     * Le rattrapage compare le terme au meilleur EXTRAIT d'un libellé : « architecte d'intérieur »
     * ressemble donc fortement à « architecte », et remontait « Architecte (IT/logiciel) » — le
     * contresens même que le filet existe pour corriger, servi à qui avait pourtant tapé son métier
     * en toutes lettres. Lui donner systématiquement la priorité rendait le filet inatteignable dès
     * qu'un concept ressemblait de loin au terme.
     *
     * Mais l'inverser systématiquement coûtait l'autre côté, et c'est mesuré : « developeur » tombe
     * sur l'unique profil ayant écrit « Dévelopeur backend » au lieu de « Développeur logiciel », et
     * « javascrip » sur deux profils, ce mot étant une sous-chaîne de « javascript ».
     *
     * LE DÉPARTAGE EST DANS LA NATURE DES DEUX ERREURS. **Une faute de frappe déforme un mot ; un
     * métier plus précis en ajoute.** Un terme d'un seul mot est donc presque toujours une variante
     * de ce qu'on cherche — le rattrapage passe devant. À partir de deux mots, le recruteur a été
     * plus précis que le dictionnaire, et lui répondre par un concept qui ignore la moitié de sa
     * saisie est un contresens : le filet passe devant.
     *
     * Un concept que personne n'exerce n'est jamais proposé : une suggestion qui ramène zéro profil
     * est le pire échec d'une auto-complétion, et l'ignorer en base la rend impossible par
     * construction.
     *
     * @return array{kind: SuggestionKind, values: list<string>}
     */
    public function jobs(string $term, int $limit): array
    {
        $exact = $this->matchingJobs($term, $limit, false);
        if ([] !== $exact) {
            return ['kind' => SuggestionKind::CONCEPT, 'values' => $exact];
        }

        $literalFirst = self::words($term) > 1;
        if ($literalFirst && [] !== ($written = $this->jobTitles($term, $limit))) {
            return ['kind' => SuggestionKind::LITERAL, 'values' => $written];
        }

        $approximate = $this->tolerates($term) ? $this->matchingJobs($term, $limit, true) : [];
        if ([] !== $approximate) {
            return ['kind' => SuggestionKind::CONCEPT, 'values' => $approximate];
        }

        return $literalFirst
            ? ['kind' => SuggestionKind::CONCEPT, 'values' => []]
            : ['kind' => SuggestionKind::LITERAL, 'values' => $this->jobTitles($term, $limit)];
    }

    /** Les mots du terme tapé — ce qui sépare une faute de frappe d'une précision supplémentaire. */
    private static function words(string $term): int
    {
        $words = preg_split('#\s+#u', trim($term), flags: \PREG_SPLIT_NO_EMPTY);

        return false === $words ? 1 : \count($words);
    }

    /**
     * Intitulés de poste ÉCRITS dans les parcours portant ce terme, les plus portés d'abord.
     *
     * SOUS-CHAÎNE, et non début de mot comme les concepts : c'est le critère exact du repli côté
     * recherche. Proposer selon une règle et chercher selon une autre servirait des propositions qui
     * ne ramènent rien.
     *
     * MÊME SEUIL DE LONGUEUR QUE LE RATTRAPAGE, et pour une raison voisine : sous cinq caractères,
     * une sous-chaîne ne discrimine plus — « dev » ramènerait presque tout le vivier. Le filtre de
     * recherche porte le même seuil, sans quoi on proposerait ce qu'il refuserait de chercher.
     *
     * Aucun rattrapage aux fautes ici : le filet ne promet que la lettre, l'approximer reviendrait à
     * deviner deux fois.
     *
     * @return list<string>
     */
    public function jobTitles(string $term, int $limit): array
    {
        if (mb_strlen(trim($term)) < self::MIN_APPROXIMATE_LENGTH) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT cjt.title AS value,
                   COUNT(DISTINCT cjt.candidate_id) AS profiles,
                   1 AS proximity
            FROM candidate_job_title cjt
            JOIN candidate c ON c.id = cjt.candidate_id AND c.deleted_at IS NULL
                AND c.active AND c.visibility = 'VISIBLE' AND c.consent_given_at IS NOT NULL
            WHERE cjt.deleted_at IS NULL AND %s LIKE %s
            GROUP BY cjt.title
            ORDER BY profiles DESC, cjt.title ASC
            LIMIT :max
            SQL;

        return $this->fetch(
            \sprintf($sql, $this->normalized('cjt.title'), $this->normalizedParameter(':contains')),
            // `prefix()` échappe les jokers et suffixe déjà d'un `%` : le préfixer en fait un
            // « contient », sans dupliquer l'échappement.
            ['contains' => '%'.$this->prefix($term), 'max' => $this->limit($limit)],
        );
    }

    /**
     * Compétences présentes dans le vivier portant ce terme, les plus portées d'abord.
     *
     * Lue depuis la colonne JSON, donc par parcours complet : c'est tenable sur le vivier actuel et
     * ça ne le restera pas — même limite que la recherche par mots-clés, même remède à venir.
     *
     * @return list<string>
     */
    public function skills(string $term, int $limit): array
    {
        return $this->rescued($term, fn (bool $approximate): array => $this->matchingSkills($term, $limit, $approximate));
    }

    /**
     * Le passage exact, puis le secours — jamais les deux mélangés.
     *
     * @param callable(bool): list<string> $find
     *
     * @return list<string>
     */
    private function rescued(string $term, callable $find): array
    {
        $exact = $find(false);

        return [] !== $exact || !$this->tolerates($term) ? $exact : $find(true);
    }

    /** Le rattrapage demande un mot assez long ET l'extension qui sait le mesurer. */
    private function tolerates(string $term): bool
    {
        return mb_strlen(trim($term)) >= self::MIN_APPROXIMATE_LENGTH && $this->approximates();
    }

    /** @return list<string> */
    private function matchingJobs(string $term, int $limit, bool $approximate): array
    {
        $sql = <<<'SQL'
            SELECT vc.pref_label AS value,
                   COUNT(DISTINCT cje.candidate_id) AS profiles,
                   %1$s AS proximity
            FROM vocabulary_concept vc
            JOIN candidate_job_experience cje ON cje.concept_id = vc.id
            JOIN candidate c ON c.id = cje.candidate_id AND c.deleted_at IS NULL
                AND c.active AND c.visibility = 'VISIBLE' AND c.consent_given_at IS NOT NULL
            WHERE vc.deleted_at IS NULL AND vc.type = :type
              AND (
                %2$s
                OR EXISTS (
                  SELECT 1 FROM vocabulary_label vl
                  WHERE vl.concept_id = vc.id AND vl.deleted_at IS NULL
                    AND vl.status <> :deprecated AND %3$s
                )
              )
            GROUP BY vc.pref_label
            ORDER BY proximity DESC, profiles DESC, vc.pref_label ASC
            LIMIT :max
            SQL;

        // La proximité d'un concept est la MEILLEURE de celles de son libellé canonique et de ses
        // variantes : « developeur » ressemble davantage à la variante « développeur java » qu'au
        // libellé officiel, et c'est pourtant le même concept qu'il désigne.
        $proximity = $approximate
            ? \sprintf(
                'MAX(GREATEST(%s, COALESCE((SELECT MAX(%s) FROM vocabulary_label vl'
                .' WHERE vl.concept_id = vc.id AND vl.deleted_at IS NULL AND vl.status <> :deprecated), 0)))',
                $this->proximityTo('vc.pref_label'),
                $this->proximityTo('vl.label'),
            )
            : \sprintf(
                'MAX(CASE WHEN %s OR EXISTS (SELECT 1 FROM vocabulary_label vl'
                .' WHERE vl.concept_id = vc.id AND vl.deleted_at IS NULL AND vl.status <> :deprecated'
                .' AND %s) THEN 1 ELSE 0 END)',
                $this->startsWith('vc.pref_label'),
                $this->startsWith('vl.label'),
            );

        $sql = \sprintf(
            $sql,
            $proximity,
            $this->matches('vc.pref_label', $approximate),
            $this->matches('vl.label', $approximate),
        );

        return $this->fetch($sql, [
            'type' => VocabularyType::METIER->value,
            'deprecated' => VocabularyStatus::DEPRECIE->value,
            ...$this->searched($term, $approximate),
            'max' => $this->limit($limit),
        ]);
    }

    /** @return list<string> */
    private function matchingSkills(string $term, int $limit, bool $approximate): array
    {
        // `btrim` : les espaces de bord ne font pas deux compétences. Sans lui, « python » et le même
        // mot suivi d'une espace se proposaient côte à côte, chacun avec sa part des profils.
        $sql = <<<'SQL'
            SELECT btrim(LOWER(skill.value #>> '{}')) AS value,
                   COUNT(DISTINCT cj.candidate_id) AS profiles,
                   %1$s AS proximity
            FROM candidate_jarvi cj
            JOIN candidate c ON c.id = cj.candidate_id AND c.deleted_at IS NULL
                AND c.active AND c.visibility = 'VISIBLE' AND c.consent_given_at IS NOT NULL
            CROSS JOIN LATERAL json_array_elements(cj.skills) AS skill
            WHERE cj.deleted_at IS NULL AND %2$s
            GROUP BY 1
            ORDER BY proximity DESC, profiles DESC, 1 ASC
            LIMIT :max
            SQL;

        // La proposition RENDUE reste la compétence telle qu'elle est écrite : on normalise pour
        // COMPARER, pas pour afficher. Servir « developpeur » à qui a tapé « dev » lui ferait poser
        // un critère que le profil ne porte pas sous cette forme.
        $value = "skill.value #>> '{}'";

        $sql = \sprintf(
            $sql,
            $approximate
                ? \sprintf('MAX(%s)', $this->proximityTo($value))
                : \sprintf('MAX(CASE WHEN %s THEN 1 ELSE 0 END)', $this->startsWith($value)),
            $this->matches($value, $approximate),
        );

        return $this->fetch($sql, [
            ...$this->searched($term, $approximate),
            'max' => $this->limit($limit),
        ]);
    }

    /**
     * Ce que le SQL attend du terme, et il n'attend pas la même chose selon le passage.
     *
     * @return array<string, string|float>
     */
    private function searched(string $term, bool $approximate): array
    {
        return $approximate
            ? ['term' => mb_strtolower(trim($term)), 'proximity' => self::MIN_PROXIMITY]
            : ['pattern' => $this->wordStart($term), 'prefix' => $this->prefix($term)];
    }

    /**
     * @param array<string, mixed> $parameters
     *
     * @return list<string>
     */
    private function fetch(string $sql, array $parameters): array
    {
        /** @var list<array{value: string, profiles: int|string, proximity: float|string}> $rows */
        $rows = $this->connection->fetchAllAssociative($sql, $parameters);

        // Le nombre de porteurs ne remonte pas du SQL : il y a fait son travail — classer — et il n'a
        // rien à faire au-dessus (task API-025).
        return array_map(static fn (array $row): string => $row['value'], $rows);
    }

    /**
     * La comparaison entière — LES DEUX CÔTÉS passent par la même normalisation.
     *
     * Normaliser la seule valeur stockée ne servirait à rien : « dév » ne trouverait plus rien du
     * tout, puisqu'il serait comparé à des chaînes désaccentuées.
     *
     * `unaccent()` est STABLE et non IMMUTABLE — elle dépend d'un dictionnaire — donc inutilisable
     * telle quelle dans un index. Mesuré sur le dictionnaire réel (3358 libellés), le parcours coûte
     * 3 ms contre 0,3 ms pour la comparaison indexée : on ne paie pas une fonction enveloppe
     * `IMMUTABLE`, ni la promesse d'immuabilité qu'elle ferait au moteur, pour trois millisecondes.
     */
    private function matches(string $expression, bool $approximate): string
    {
        return $approximate
            ? \sprintf('%s >= CAST(:proximity AS real)', $this->proximityTo($expression))
            : \sprintf('%s ~ %s', $this->normalized($expression), $this->normalizedParameter(':pattern'));
    }

    /**
     * Ce libellé COMMENCE-t-il par le terme — le critère de classement du passage exact.
     *
     * Trouver le mot au milieu ne doit pas coûter sa place à ce qui commence par lui. Sans ce
     * départage, « dev » remontait « Ingénieur d'études » en tête (493 profils, dont une variante
     * contient « développement ») devant « Développeur logiciel » (293), qui était pourtant le
     * premier résultat avant cette tâche.
     */
    private function startsWith(string $expression): string
    {
        return \sprintf('%s LIKE %s', $this->normalized($expression), $this->normalizedParameter(':prefix'));
    }

    /**
     * Le préfixe échappé pour `LIKE` : `%` et `_` saisis au clavier ne sont pas des jokers.
     *
     * Il ne FILTRE plus rien — c'est le motif de début de mot qui filtre. Il ne sert qu'à classer.
     */
    private function prefix(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], mb_strtolower(trim($term))).'%';
    }

    /**
     * À quel point le terme cherché ressemble à un EXTRAIT de cette expression.
     *
     * `word_similarity` et non `similarity` : le terme est comparé au meilleur extrait du libellé, pas
     * au libellé entier. Sans quoi « developeur » ne ressemblerait que faiblement à « Développeur
     * logiciel », dont la moitié des mots lui est étrangère — 0,46 contre 0,77.
     */
    private function proximityTo(string $expression): string
    {
        return \sprintf('word_similarity(%s, %s)', $this->normalizedParameter(':term'), $this->normalized($expression));
    }

    private function normalized(string $expression): string
    {
        return $this->normalizes()
            ? \sprintf('unaccent(LOWER(%s))', $expression)
            : \sprintf('LOWER(%s)', $expression);
    }

    private function normalizedParameter(string $parameter): string
    {
        return $this->normalizes() ? \sprintf('unaccent(%s)', $parameter) : $parameter;
    }

    /** L'extension est-elle posée ? Sans elle, la comparaison reste accentuée — dégradée, pas cassée. */
    private function normalizes(): bool
    {
        return $this->normalizes ??= null !== $this->connection->fetchOne(
            "SELECT to_regprocedure('unaccent(text)')",
        );
    }

    /** Sans `pg_trgm`, le secours ne se déclenche pas : seul le passage exact reste. */
    private function approximates(): bool
    {
        return $this->approximates ??= null !== $this->connection->fetchOne(
            "SELECT to_regprocedure('word_similarity(text,text)')",
        );
    }

    /**
     * Le terme, transformé en « début de n'importe quel mot ».
     *
     * `\m` est la frontière de mot de PostgreSQL, et elle fait tout le travail : la parenthèse et la
     * barre oblique en sont une, donc « logiciel » retrouve « Architecte (IT/logiciel) » sans règle
     * supplémentaire. Une sous-chaîne (`%…%`) aurait aussi retrouvé « k123 » au milieu de
     * « kubernetes sk123 », ce qu'aucun recruteur ne demande.
     *
     * Les métacaractères d'expression régulière tapés au clavier sont neutralisés : une saisie n'est
     * pas un motif, exactement comme `%` et `_` ne devenaient pas des jokers du temps du `LIKE`.
     */
    private function wordStart(string $term): string
    {
        return '\m'.addcslashes(mb_strtolower(trim($term)), self::REGEX_METACHARACTERS);
    }

    private function limit(int $limit): int
    {
        return max(1, min(self::MAX_LIMIT, $limit));
    }
}
