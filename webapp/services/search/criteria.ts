import type { CandidateCard, CriterionMatch } from '../../shared/types/candidate-card'
import type { SearchCriterion } from '../../shared/types/search'

/**
 * Ce que les deux moteurs de l'accueil produisent → les critères d'une recherche.
 *
 * Métier pur. Le `facet` est le nom que le moteur serveur attend (cf. `SearchCriteriaTranslator`) ;
 * le `group` est le segment qui servira au NOM AUTOMATIQUE de la recherche — c'est pour ça qu'il est
 * porté ici et pas déduit de la facette : deux facettes peuvent nourrir le même segment.
 *
 * L'ORDRE compte : il détermine l'ordre des segments du nom auto. On pose donc les critères dans
 * l'ordre où le recruteur les a lus à l'écran.
 *
 * Une valeur vide n'est jamais posée : un critère sur la chaîne vide filtrerait sur rien.
 */

const criterion = (facet: string, value: string, group: string): SearchCriterion[] =>
  '' === value.trim() ? [] : [{ facet, value: value.trim(), group }]

/**
 * Traduire, INJECTÉ par l'appelant.
 *
 * Cette couche est pure et ne connaît pas `useI18n` (`services/README.md` — « Zéro dépendance
 * framework »). Ce qui compose une chaîne destinée à l'écran reçoit donc son traducteur, comme
 * toute dépendance à un contexte d'exécution.
 */
export type Translate = (key: string, params?: Record<string, unknown>) => string

/**
 * Une valeur prête à être affichée : une clé à traduire, ou une chaîne telle qu'elle a été saisie.
 *
 * Ce que le recruteur a tapé — un mot-clé, une ville — n'a pas de clé ; ce qu'il a choisi dans une
 * liste — un niveau d'études, une disponibilité — en a une. Distinguer les deux ici évite au
 * template de deviner laquelle des deux il tient.
 */
export interface DisplayValue {
  key: string | null
  /** Ce qui s'affiche quand `key` est nul. */
  text: string
  /** Paramètre de pluriel de `key`. */
  count: number | null
}

/** Une valeur d'affichage → la chaîne à poser à l'écran. */
export const renderValue = (value: DisplayValue, translate: Translate): string =>
  null === value.key
    ? value.text
    : translate(value.key, null === value.count ? undefined : { count: value.count })

/**
 * Les facettes qui portent un libellé, et l'ordre n'a pas d'importance : c'est une appartenance.
 *
 * Ce libellé nomme la NATURE du critère — « Mot-clé », « Poste recherché » —, et non ce qu'il vaut.
 * `q` y a sa place depuis que la nature se lit sur sa propre ligne, sous le critère : en préfixe
 * inline elle n'ajoutait rien à « vue », en sous-titre elle dit d'où vient la ligne.
 *
 * Une facette inconnue en reste absente, et n'affiche alors aucune nature — plutôt que sa clé de
 * traduction en clair, ce que ferait un libellé composé à l'aveugle.
 */
const LABELLED_FACETS = [
  'q',
  'job',
  'experienceMin',
  'educationLevel',
  'availability',
  'returnHorizon',
  'geoMobility',
  'hasPiedATerre',
  'star',
  'country',
  'sector',
  'function',
  'experienceSector',
  'language',
  'languageLevel',
  'presenceCity',
  'presenceWithinDays',
]

/**
 * Les facettes dont la valeur est une énumération, et le catalogue qui la traduit.
 *
 * Ces catalogues préexistent : ce sont les traductions canoniques des énumérations du contrat, pas
 * des libellés empruntés à un autre écran. Celui des niveaux de langue est né sur la recherche
 * assistée et n'a jamais bougé de là ; le dupliquer pour la forme le ferait diverger.
 */
const ENUM_CATALOGUES: Record<string, string> = {
  educationLevel: 'search.education',
  availability: 'search.availability',
  returnHorizon: 'search.horizon',
  languageLevel: 'search.ai.proficiency',
}

/** Les facettes qui ne portent qu'un oui : leur valeur est un booléen, pas une donnée. */
const BOOLEAN_FACETS = ['geoMobility', 'hasPiedATerre', 'star']

const booleanValue = (raw: string): DisplayValue => ({
  key: `search.criterion.value.${'false' === raw ? 'no' : 'yes'}`,
  text: raw,
  count: null,
})

/** Ce qu'un critère DEMANDE, en lisible : son libellé de facette et sa valeur. */
export interface CriterionDescription {
  /**
   * `null` sur une facette non répertoriée — aucune ne l'est aujourd'hui, `LABELLED_FACETS` couvre
   * exactement celles que cette couche produit. La garde protège le contrat, pas un cas connu : la
   * Visionneuse en fait la première ligne de la bulle d'un critère, où son absence laisserait
   * « Casablanca » et « scrum » indiscernables.
   */
  labelKey: string | null
  wanted: DisplayValue
}

/**
 * Un critère → de quoi l'écrire à l'écran.
 *
 * Elle existe parce que la valeur STOCKÉE n'est pas la valeur LISIBLE : le moteur garde `LICENCE` et
 * `1`, et l'écran doit dire « Licence » et « 1 an et plus ». Trois consommateurs en dépendent — le
 * panneau, la barre haute et le nom automatique —, et les laisser composer chacun le sien les
 * ferait afficher deux graphies du même critère sur un même écran.
 */
export const describeCriterion = (criterion: SearchCriterion): CriterionDescription => {
  const { facet, value } = criterion
  const labelKey = LABELLED_FACETS.includes(facet) ? `search.criterion.facet.${facet}` : null

  if ('experienceMin' === facet) {
    const years = Number.parseInt(value, 10)

    return {
      labelKey,
      wanted: Number.isNaN(years)
        ? { key: null, text: value, count: null }
        : { key: 'search.criterion.value.experienceMin', text: value, count: years },
    }
  }

  if (BOOLEAN_FACETS.includes(facet)) {
    return { labelKey, wanted: booleanValue(value) }
  }

  const catalogue = ENUM_CATALOGUES[facet]

  return {
    labelKey,
    wanted: { key: catalogue ? `${catalogue}.${value}` : null, text: value, count: null },
  }
}

/**
 * La valeur d'un critère, en toutes lettres — « Licence » plutôt que `LICENCE`.
 *
 * Un booléen fait exception et rend son LIBELLÉ : une puce « Oui » ne dit pas de quoi elle parle,
 * là où « Profil vérifié » se lit seule. C'est ce qu'on veut sur une puce et dans le nom auto d'une
 * recherche — le bloc de confrontation, lui, garde le couple libellé + valeur, puisqu'il a une
 * colonne pour chacun.
 */
export const criterionText = (criterion: SearchCriterion, translate: Translate): string => {
  const { labelKey, wanted } = describeCriterion(criterion)

  return BOOLEAN_FACETS.includes(criterion.facet) && null !== labelKey
    ? translate(labelKey)
    : renderValue(wanted, translate)
}

/** Un critère de la recherche confronté au profil affiché. */
export interface CriterionOutcome {
  criterion: SearchCriterion
  labelKey: string | null
  wanted: DisplayValue
  /**
   * Satisfait, non satisfait — ou `null` quand il n'y a **rien à confronter**.
   *
   * `null` n'est pas « non satisfait » : c'est l'absence de profil affiché. Le critère reste alors
   * lisible et retirable, ce qui est précisément ce qu'on veut sur une recherche qui ne ramène
   * personne.
   */
  matched: boolean | null
  /** Ce que le PROFIL porte en face — `null` quand la carte ne porte pas la donnée. */
  found: DisplayValue | null
  /**
   * QUAND le profil porte ça — `null` quand on ne le sait pas.
   *
   * Seul le métier en a un : une langue ou un pays n'ont pas de « quand ». `CURRENT` dit que c'est
   * son poste du moment, `PAST` qu'il en occupe un autre aujourd'hui.
   *
   * ⚠️ **`null` ne veut pas dire « passé »**, il veut dire qu'on ne sait pas. Le poste du moment
   * n'est résolu que sur la moitié du vivier et il est parfois ambigu ; le serveur se tait plutôt
   * que de deviner, et l'écran doit se taire avec lui.
   */
  tenure: 'CURRENT' | 'PAST' | null
  /**
   * OÙ le profil porte ça — `null` partout sauf sur un mot-clé satisfait.
   *
   * Un mot-clé est le seul critère à fouiller plusieurs endroits, donc le seul dont la
   * correspondance se juge : un terme lu dans une compétence déclarée ne vaut pas le même crédit
   * qu'un terme lu dans l'accroche que le candidat s'écrit. Les autres facettes n'ont qu'un endroit
   * possible, et l'annoncer n'apprendrait rien.
   *
   * Le type se lit sur le contrat plutôt que de recopier ses trois valeurs : deux listes finissent
   * par diverger, et celle-ci est fermée côté serveur.
   */
  source: NonNullable<CriterionMatch['matchedIn']> | null
}

// `undefined` autant que `null` : le serveur OMET `matchedOn` quand le critère échoue, il ne
// l'envoie pas vide. Une garde sur le seul `null` laissait passer l'absence, et la ligne affichait
// une valeur blanche à la place de son verdict.
const raw = (text: string | null | undefined): DisplayValue | null =>
  null === text || undefined === text || '' === text ? null : { key: null, text, count: null }

const fromCatalogue = (catalogue: string, value: string | null): DisplayValue | null =>
  null === value ? null : { key: `${catalogue}.${value}`, text: value, count: null }

/**
 * Ce que le profil porte EN FACE du critère.
 *
 * Lu sur la carte et non sur le match : le serveur n'envoie PAS `matchedOn` dès que le critère
 * échoue, et c'est précisément là qu'on veut la valeur du profil — « demandé cinq ans, profil trois
 * ans » est ce qui fait trancher.
 *
 * `null` quand la carte ne porte rien de comparable : la langue, le secteur d'expérience et les
 * fenêtres de présence n'ont pas de contrepartie. On ne montre pas ce qu'on n'a pas.
 */
const profileValue = (
  criterion: SearchCriterion,
  card: CandidateCard,
  matchedOn: string | null | undefined,
): DisplayValue | null => {
  switch (criterion.facet) {
    case 'q':
    case 'job':
      // Le libellé DU PROFIL reconnu par le serveur : « vue » a trouvé « Vue.js », et la chaîne
      // cherchée n'est nulle part dans la fiche.
      return raw(matchedOn)
    case 'experienceMin':
      return {
        key: 'search.profile.years',
        text: String(card.experienceYears),
        count: card.experienceYears,
      }
    case 'educationLevel':
      return fromCatalogue('search.education', card.educationLevel)
    case 'availability':
      return fromCatalogue('search.availability', card.availability)
    case 'returnHorizon':
      return fromCatalogue('search.horizon', card.returnHorizon)
    case 'geoMobility':
      return booleanValue(String(card.geoMobility))
    case 'hasPiedATerre':
      return booleanValue(String(card.hasPiedATerre))
    case 'star':
      return booleanValue(String(card.isStar))
    case 'country':
      return raw(card.country)
    case 'sector':
      return raw(card.desiredSector)
    case 'function':
      return raw(card.desiredFunction)
    default:
      return null
  }
}

/**
 * Deux valeurs disent-elles la même chose ?
 *
 * INSENSIBLE À LA CASSE, et ce n'est pas une commodité : les compétences viennent du
 * texte libre des profils, où « docker » et « Docker » cohabitent. Comparer au caractère près faisait
 * dépendre l'affichage d'une majuscule — « angular » montrait « Angular », qui n'apprend rien, quand
 * « docker » montrait « Trouvé ». La distinction que le lecteur voyait était typographique, pas
 * sémantique.
 *
 * Ce qui reste affiché est ce qui apprend quelque chose : « react » a trouvé « React Native ».
 */
const sameValue = (left: DisplayValue, right: DisplayValue): boolean =>
  left.key === right.key &&
  left.count === right.count &&
  left.text.toLocaleLowerCase() === right.text.toLocaleLowerCase()

/**
 * Les critères de la recherche confrontés au profil affiché, DANS L'ORDRE CANONIQUE — poste,
 * expérience, études, mots-clés.
 *
 * Le même ordre que le nom composé, et il ne suit PAS celui où les critères ont été posés. Suivre
 * l'enregistrement reléguait l'expérience derrière cinq mots-clés sur une recherche qui en portait
 * autant : un filtre structurant se lisait tout en bas, après le détail.
 *
 * Un critère absent de `matches` est ÉCARTÉ plutôt que déclaré non trouvé — le serveur n'évalue pas
 * `languageLevel`, qui précise `language` et dont la correspondance est déjà portée par elle.
 * L'annoncer manquant serait un verdict qu'on n'a pas.
 *
 * Une valeur de profil IDENTIQUE à celle demandée n'est pas rendue : « Disponibilité · Disponible
 * immédiatement · Disponible immédiatement » occupe deux fois la largeur d'une colonne de deux cent
 * quatre-vingts pixels pour n'apprendre rien. Le verdict prend alors sa place.
 */
export const confrontCriteria = (
  unordered: readonly SearchCriterion[],
  card: CandidateCard | null,
): CriterionOutcome[] => {
  const criteria = orderCriteria(unordered)

  // RIEN À CONFRONTER — mais les critères restent. Ils étaient portés par les puces, qui ont
  // disparu ; sans ce cas, une recherche qui ne ramène personne n'afficherait plus aucun critère,
  // donc plus aucun moyen de retirer celui qui est de trop. C'est exactement le moment où l'on veut
  // en défaire un.
  if (null === card || null === card.matches) {
    return criteria.map((criterion) => ({
      criterion,
      ...describeCriterion(criterion),
      matched: null,
      found: null,
      tenure: null,
      source: null,
    }))
  }

  const matchOf = (criterion: SearchCriterion): CriterionMatch | undefined =>
    card.matches?.find(
      (match) => match.facet === criterion.facet && match.value === criterion.value.trim(),
    )

  return criteria.flatMap((criterion) => {
    const match = matchOf(criterion)
    if (!match) {
      return []
    }

    const { labelKey, wanted } = describeCriterion(criterion)
    const found = profileValue(criterion, card, match.matchedOn)

    return [
      {
        criterion,
        labelKey,
        wanted,
        matched: match.matched,
        found: null !== found && sameValue(found, wanted) ? null : found,
        // Un critère qu'on ne satisfait pas n'a ni présent ni passé — le serveur l'omet déjà, on ne
        // s'en remet pas à lui pour autant : les deux règles doivent tomber d'accord.
        tenure: match.matched ? (match.matchedWhen ?? null) : null,
        // MÊME PRUDENCE, et pour la même raison : un critère non satisfait n'a été vu nulle part.
        // `??` et non une garde sur le seul `null` — le sérialiseur OMET la clé, il ne l'envoie pas
        // vide, et la garde stricte laisserait passer `undefined` jusqu'à la clé de traduction.
        source: match.matched ? (match.matchedIn ?? null) : null,
      },
    ]
  })
}

/** Où se situe un profil sur l'échelle des trois paliers. */
export type MatchTier = 'low' | 'medium' | 'high'

/** Le verdict d'ensemble : combien de critères satisfaits, sur combien, et comment ça s'appelle. */
export interface CriteriaScore {
  met: number
  total: number
  tier: MatchTier
}

/**
 * Les deux bascules de l'échelle — un tiers, deux tiers.
 *
 * Trois paliers et pas quatre : un découpage en quarts rend deux paliers INATTEIGNABLES sur une
 * recherche à trois critères, et fait changer le mot pour un seul critère de plus.
 */
const TIER_BOUNDS = { medium: 1 / 3, high: 2 / 3 }

/**
 * Le décompte des critères satisfaits → le verdict d'ensemble.
 *
 * IL COMPTE CE QUE LE SERVEUR A JUGÉ SATISFAIT, poste passé compris : le candidat a exercé ce
 * métier, le moteur a retenu le profil dessus, et un score qui dirait le contraire contredirait le
 * tri qui a ramené ce profil. La ligne le nuance à sa façon — elle porte un tiret et non une coche —,
 * le score, lui, s'accorde avec le tri.
 *
 * `null` QUAND IL N'Y A RIEN À COMPTER : aucun critère, ou aucun profil à confronter. « 0 sur 6 » se
 * lirait alors comme un mauvais score là où il n'y a qu'une absence, et c'est le contraire de ce
 * qu'on veut dire.
 */
export const scoreOf = (outcomes: readonly CriterionOutcome[]): CriteriaScore | null => {
  if (0 === outcomes.length || outcomes.every((outcome) => null === outcome.matched)) {
    return null
  }

  const met = outcomes.filter((outcome) => true === outcome.matched).length
  const ratio = met / outcomes.length

  if (ratio < TIER_BOUNDS.medium) {
    return { met, total: outcomes.length, tier: 'low' }
  }

  return {
    met,
    total: outcomes.length,
    tier: ratio < TIER_BOUNDS.high ? 'medium' : 'high',
  }
}

/**
 * Plafond du chemin de REPLI seulement (cf. `autoName`) : au-delà, le nom cesse d'être lisible
 * d'un coup d'œil et devient une énumération. Le chemin nommé, lui, n'en a plus besoin — sa liste
 * de groupes est fixe.
 */
const NAME_SEGMENTS = 3

/**
 * L'ORDRE CANONIQUE des critères — celui du nom composé ET de la liste des critères.
 *
 * Un seul ordre, un seul endroit. Les deux surfaces énonçaient les mêmes critères différemment : le
 * nom intercalait un mot-clé entre le poste et l'expérience, la liste suivait l'ordre
 * d'ENREGISTREMENT et pouvait donc reléguer l'expérience derrière cinq mots-clés. Deux listes
 * séparées auraient re-divergé au premier changement.
 *
 * Il suit la façon dont une recherche se pense : le **poste** d'abord, puis ce qu'on exige de la
 * personne — **expérience**, **études** —, puis le détail des **mots-clés**. L'ordre est fixe et ne
 * suit jamais celui où les critères ont été posés : deux recherches se comparent du regard parce
 * qu'elles disent les mêmes choses au même endroit.
 */
const CANONICAL_GROUPS: readonly string[] = ['metier', 'experience', 'etudes', 'motsCles']

/**
 * Le rang d'un groupe dans l'ordre canonique. Un groupe inconnu passe derrière tous les autres
 * plutôt que d'être perdu — un séjour au Maroc ou une langue reste affiché, en fin de liste.
 */
const rankOf = (group: string): number => {
  const rank = CANONICAL_GROUPS.indexOf(group)

  return -1 === rank ? CANONICAL_GROUPS.length : rank
}

/**
 * Les critères rangés dans l'ordre canonique.
 *
 * Le tri est STABLE, et c'est ce qui permet d'en poser cinq du même groupe : deux mots-clés gardent
 * l'ordre où le recruteur les a saisis. Rien n'est retiré — autant de critères en sortie qu'en
 * entrée, groupes inconnus compris.
 */
export const orderCriteria = (criteria: readonly SearchCriterion[]): SearchCriterion[] =>
  [...criteria].sort((left, right) => rankOf(left.group) - rankOf(right.group))

/**
 * Le seul groupe plafonné, et c'est lui qui borne le titre.
 *
 * Les mots-clés n'ont aucune taille naturelle — dix-neuf sur une même recherche donnaient un titre
 * de vingt mots, joints par des virgules. Les autres groupes en portent au plus une poignée.
 */
const KEYWORDS_GROUP = 'motsCles'

/** Un séjour au Maroc se nomme comme tel, une fois, en tête de son segment. */
const PRESENCE_GROUP = 'presence'

/**
 * Le nom d'une recherche, déduit de ses critères — dans leur ordre de sélection.
 *
 * Une recherche n'a pas de nom tant qu'aucun critère n'est posé : il n'y a rien à nommer avant. Et
 * ce nom n'est PAS persisté tant qu'il est automatique — l'API garde `isAutoNamed`, le front
 * recompose. Persister le composé désactiverait l'auto-nommage et figerait un nom qui mentirait au
 * premier critère changé.
 *
 * Un groupe de facette = un segment, valeurs jointes par « , » ; trois segments au plus, puis « +N ».
 *
 * Les valeurs sont celles que la barre haute affiche, pas celles que le moteur stocke : le nom se
 * lit juste au-dessus des puces qu'il résume, et deux graphies du même critère à dix pixels d'écart
 * se lisent comme deux critères.
 */
export const autoName = (criteria: readonly SearchCriterion[], translate: Translate): string => {
  const segments = new Map<string, string[]>()

  for (const criterion of criteria) {
    const { group, value } = criterion
    if ('' === value.trim()) {
      continue
    }
    segments.set(group, [...(segments.get(group) ?? []), criterionText(criterion, translate)])
  }

  const render = (group: string, values: readonly string[]): string =>
    PRESENCE_GROUP === group ? `Maroc ${values.join(', ')}` : values.join(', ')

  // Les quatre groupes nommés, dans leur ordre à eux, et les mots-clés réduits au premier.
  const named = CANONICAL_GROUPS.filter((group) => segments.has(group)).map((group) => {
    const values = segments.get(group) ?? []

    return render(group, KEYWORDS_GROUP === group ? values.slice(0, 1) : values)
  })

  if (named.length > 0) {
    // Pas de « +N » ici : ce que le nom laisse de côté est compté par les étiquettes de la carte,
    // qui le disent en toutes lettres — « +18 critères » plutôt qu'un « +2 » sans sujet.
    return named.join(' · ')
  }

  // REPLI. Aucun des quatre groupes nommés n'est posé : une recherche dictée du type « des gens au
  // Maroc qui parlent anglais » n'a ni métier ni mot-clé. La règle stricte la rendrait muette alors
  // que ses critères disent quelque chose — un titre variable vaut mieux qu'un titre absent.
  const rest = [...segments.entries()]
    .filter(([group]) => !CANONICAL_GROUPS.includes(group))
    .map(([group, values]) => render(group, values))

  const shown = rest.slice(0, NAME_SEGMENTS).join(' · ')
  const hidden = rest.length - NAME_SEGMENTS

  return hidden > 0 ? `${shown} +${hidden}` : shown
}

/**
 * Le nom à AFFICHER : celui que le recruteur a saisi, sinon celui que les critères composent.
 *
 * Vide quand il n'y a rien à nommer — l'écran met alors son propre libellé, c'est à lui de le dire.
 */
export const displayName = (
  search: { name: string; criteria: readonly SearchCriterion[] },
  translate: Translate,
): string => ('' !== search.name.trim() ? search.name.trim() : autoName(search.criteria, translate))

export interface PrimarySearchForm {
  jobTitle: string
  /**
   * PLUSIEURS mots-clés, reliés en OU par le moteur : chacun devient son propre critère.
   *
   * C'est la seule facette multivaluée. En poser deux ÉLARGIT le résultat, là où poser deux fois
   * n'importe quel autre critère le remplace — le formulaire doit le dire, sans quoi on croit
   * resserrer en ajoutant.
   */
  keywords: string[]
  /** Années d'expérience minimum. `0` = toutes : la borne basse porte le « peu importe ». */
  experienceMin: number
  /** Valeur de l'enum API `EducationLevel`, ou '' quand le critère n'est pas posé. */
  educationLevel: string
}

/**
 * Formulaire par critères → critères de recherche.
 *
 * L'ordre suit celui de l'écran : c'est lui qui compose le nom automatique de la recherche.
 *
 * `experienceMin` à `0` ne pose RIEN — pas un critère à zéro. Le moteur ignorerait la valeur (il ne
 * garde que les entiers positifs), mais le critère resterait affiché dans la barre haute comme un
 * filtre actif qui ne filtre pas.
 */
export const fromPrimaryForm = (form: PrimarySearchForm): SearchCriterion[] => [
  ...criterion('job', form.jobTitle, 'metier'),
  ...(form.experienceMin > 0
    ? criterion('experienceMin', String(form.experienceMin), 'experience')
    : []),
  ...criterion('educationLevel', form.educationLevel, 'etudes'),
  ...form.keywords.flatMap((keyword) => criterion('q', keyword, 'motsCles')),
]

export interface AiSearchCriteria {
  keywords: { terms: string[] }[]
  country: string | null
  city: string | null
  languages: { language: string; minProficiency: string | null }[]
  availability: string | null
  returnHorizon: string | null
  geoMobility: boolean | null
}

/**
 * Critères compris par le modèle → critères de recherche.
 *
 * Les mots-clés partent UN PAR UN : le moteur les accumule et les relie en OU. Ils étaient aplatis en
 * une seule chaîne du temps où il n'en gardait qu'un — jointe, « react vue » se cherchait comme une
 * expression, et ne trouvait personne.
 *
 * Seule la PREMIÈRE langue demandée est posée : celle-là reste soumise au « dernier gagne », le
 * moteur n'ayant qu'une valeur par facette hors mots-clés. Perte réelle, assumée — la signaler ici
 * vaut mieux qu'empiler des critères dont un seul survivrait.
 *
 * `removed` porte les critères que le recruteur a écartés à l'écran avant de lancer : ils ne partent
 * pas. Les identifiants sont ceux de `aiCriteriaToFilters` (`kw:0`, `country`, `lang:1`…).
 */
export const fromAiCriteria = (
  criteria: AiSearchCriteria | null,
  removed: ReadonlySet<string> = new Set(),
): SearchCriterion[] => {
  if (!criteria) {
    return []
  }

  const terms = criteria.keywords
    .filter((_, index) => !removed.has(`kw:${index}`))
    .flatMap((group) => group.terms)

  const language = criteria.languages.find((_, index) => !removed.has(`lang:${index}`))

  return [
    ...terms.flatMap((term) => criterion('q', term, 'motsCles')),
    ...(removed.has('country') ? [] : criterion('country', criteria.country ?? '', 'localisation')),
    ...(removed.has('city') ? [] : criterion('presenceCity', criteria.city ?? '', 'presence')),
    ...(language ? criterion('language', language.language, 'langues') : []),
    ...(language?.minProficiency
      ? criterion('languageLevel', language.minProficiency, 'langues')
      : []),
    ...(removed.has('availability')
      ? []
      : criterion('availability', criteria.availability ?? '', 'dispo')),
    ...(removed.has('returnHorizon')
      ? []
      : criterion('returnHorizon', criteria.returnHorizon ?? '', 'retour')),
    ...(removed.has('geoMobility') || true !== criteria.geoMobility
      ? []
      : criterion('geoMobility', 'true', 'mobilite')),
  ]
}

/** Les facettes que le formulaire par critères POSSÈDE — les seules qu'il a le droit d'écraser. */
const PRIMARY_FACETS = ['job', 'q', 'experienceMin', 'educationLevel'] as const

/**
 * Critères d'une recherche → formulaire pré-rempli.
 *
 * Ce que le formulaire ne sait pas porter (une langue, un pays, un séjour au Maroc) n'apparaît pas :
 * il ne s'en occupe pas, et `mergeWithPrimary` le préserve.
 */
export const toPrimaryForm = (criteria: readonly SearchCriterion[]): PrimarySearchForm => {
  const value = (facet: string) => criteria.find((item) => item.facet === facet)?.value ?? ''
  const experienceMin = Number.parseInt(value('experienceMin'), 10)

  return {
    jobTitle: value('job'),
    // TOUS les mots-clés, et pas seulement le premier : ils s'accumulent, les relire un par un
    // reviendrait à en perdre à chaque ajustement.
    keywords: criteria.filter((item) => 'q' === item.facet).map((item) => item.value),
    experienceMin: Number.isNaN(experienceMin) ? 0 : experienceMin,
    educationLevel: value('educationLevel'),
  }
}

/**
 * Réécrit les critères du formulaire principal SANS toucher aux autres.
 *
 * Une recherche née du langage naturel porte des facettes que ce formulaire ignore — langue, pays,
 * disponibilité. Remplacer la liste entière les effacerait en silence, alors que le recruteur croit
 * n'avoir ajusté qu'un poste. On ne remplace donc que ce que le formulaire possède.
 */
export const mergeWithPrimary = (
  existing: readonly SearchCriterion[],
  form: PrimarySearchForm,
): SearchCriterion[] => [
  ...existing.filter(
    (item) => !PRIMARY_FACETS.includes(item.facet as (typeof PRIMARY_FACETS)[number]),
  ),
  ...fromPrimaryForm(form),
]

/**
 * Les DOUZE filtres que le moteur applique hors filtres classiques.
 *
 * La liste ne vient ni du mockup ni du document de cadrage, mais de `SearchCriteriaTranslator` :
 * c'est le seul endroit qui dise ce qui filtre VRAIMENT. Le mockup propose en plus une ville de
 * résidence et des villes de mobilité que le moteur ne connaît pas — les afficher donnerait des
 * cases sans effet.
 *
 * UNE valeur par filtre, et c'est la vérité du moteur : il ne garde que la dernière posée par
 * facette. Cocher deux pays en remplacerait un par l'autre sans que rien ne le dise.
 */
export interface SecondarySearchForm {
  /** Fenêtre de présence au Maroc, en jours. `0` = peu importe — un seuil, pas une durée. */
  presenceWithinDays: number
  presenceCity: string
  /** Coché = on veut les mobiles. Décoché = peu importe, et non « on veut les immobiles ». */
  geoMobility: boolean
  hasPiedATerre: boolean
  returnHorizon: string
  availability: string
  sector: string
  experienceSector: string
  jobFunction: string
  language: string
  languageLevel: string
  star: boolean
  country: string
}

/** Un formulaire vierge : rien n'est posé, donc rien n'est filtré. */
export const emptySecondaryForm = (): SecondarySearchForm => ({
  presenceWithinDays: 0,
  presenceCity: '',
  geoMobility: false,
  hasPiedATerre: false,
  returnHorizon: '',
  availability: '',
  sector: '',
  experienceSector: '',
  jobFunction: '',
  language: '',
  languageLevel: '',
  star: false,
  country: '',
})

/**
 * Un interrupteur DÉCOCHÉ ne pose rien.
 *
 * Poser `false` filtrerait sur « pas mobile au Maroc », ce que personne ne cherche — et le critère
 * s'afficherait en puce comme un filtre actif. Même raison qu'`experienceMin` à zéro.
 */
const flag = (facet: string, on: boolean, group: string): SearchCriterion[] =>
  on ? [{ facet, value: 'true', group }] : []

/**
 * Formulaire secondaire → critères de recherche.
 *
 * Les `group` réutilisent ceux des deux autres moteurs : c'est eux qui composent le nom automatique
 * d'une recherche, et deux libellés différents pour la même famille le couperaient en deux segments.
 */
export const fromSecondaryForm = (form: SecondarySearchForm): SearchCriterion[] => [
  ...(form.presenceWithinDays > 0
    ? criterion('presenceWithinDays', String(form.presenceWithinDays), 'presence')
    : []),
  ...criterion('presenceCity', form.presenceCity, 'presence'),
  ...flag('geoMobility', form.geoMobility, 'mobilite'),
  ...flag('hasPiedATerre', form.hasPiedATerre, 'presence'),
  ...criterion('returnHorizon', form.returnHorizon, 'retour'),
  ...criterion('availability', form.availability, 'dispo'),
  ...criterion('sector', form.sector, 'metier'),
  ...criterion('experienceSector', form.experienceSector, 'metier'),
  ...criterion('function', form.jobFunction, 'metier'),
  ...criterion('language', form.language, 'langues'),
  ...criterion('languageLevel', form.languageLevel, 'langues'),
  ...flag('star', form.star, 'qualite'),
  ...criterion('country', form.country, 'localisation'),
]

/** Les facettes que le formulaire secondaire POSSÈDE — les seules qu'il a le droit d'écraser. */
const SECONDARY_FACETS = [
  'presenceWithinDays',
  'presenceCity',
  'geoMobility',
  'hasPiedATerre',
  'returnHorizon',
  'availability',
  'sector',
  'experienceSector',
  'function',
  'language',
  'languageLevel',
  'star',
  'country',
] as const

/**
 * Critères d'une recherche → formulaire secondaire pré-rempli.
 *
 * Le va-et-vient doit être FIDÈLE : relire puis réécrire sans rien toucher ne doit perdre aucune
 * facette. Une perte ici efface un filtre en silence, et rien à l'écran ne le signale.
 */
export const toSecondaryForm = (criteria: readonly SearchCriterion[]): SecondarySearchForm => {
  const value = (facet: string) => criteria.find((item) => item.facet === facet)?.value ?? ''
  const on = (facet: string) => ['1', 'true'].includes(value(facet))
  const days = Number.parseInt(value('presenceWithinDays'), 10)

  return {
    presenceWithinDays: Number.isNaN(days) ? 0 : days,
    presenceCity: value('presenceCity'),
    geoMobility: on('geoMobility'),
    hasPiedATerre: on('hasPiedATerre'),
    returnHorizon: value('returnHorizon'),
    availability: value('availability'),
    sector: value('sector'),
    experienceSector: value('experienceSector'),
    jobFunction: value('function'),
    language: value('language'),
    languageLevel: value('languageLevel'),
    star: on('star'),
    country: value('country'),
  }
}

/**
 * Réécrit les critères secondaires SANS toucher aux autres.
 *
 * Le pendant exact de `mergeWithPrimary` : ajuster un pays ne doit pas effacer un intitulé de poste,
 * ni un mot-clé posé par la recherche en langage naturel.
 */
export const mergeWithSecondary = (
  existing: readonly SearchCriterion[],
  form: SecondarySearchForm,
): SearchCriterion[] => [
  ...existing.filter(
    (item) => !SECONDARY_FACETS.includes(item.facet as (typeof SECONDARY_FACETS)[number]),
  ),
  ...fromSecondaryForm(form),
]

/** Retire un critère par sa facette et sa valeur — l'ajustement à l'unité, depuis la barre haute. */
export const withoutCriterion = (
  criteria: readonly SearchCriterion[],
  facet: string,
  value: string,
): SearchCriterion[] => criteria.filter((item) => !(item.facet === facet && item.value === value))
