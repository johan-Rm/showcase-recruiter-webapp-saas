// Métadonnées d'affichage des métriques du vivier : définition au survol et ratio sur une
// population de référence. Couplées AUX LIBELLÉS émis par le back (QualificationReporter),
// volontairement en français comme eux — sur un /en admin les libellés restent français, une
// définition traduite serait incohérente. Dégradation propre : un libellé sans entrée n'a ni
// bulle d'aide ni ratio, jamais d'erreur.

/** Population de référence d'un ratio ; chacune est elle-même une métrique du snapshot. */
export type VivierRatioKey = 'actifs' | 'postes' | 'rattaches'

export interface VivierMetricMeta {
  /** Définition métier affichée dans la bulle d'aide. */
  hint: string
  /** Si défini, affiche « valeur / dénominateur » ; le dénominateur est une autre métrique. */
  ratioOf?: VivierRatioKey
}

/** Où lire le dénominateur de chaque ratio dans le snapshot : [section, libellé]. */
const RATIO_SOURCE: Record<VivierRatioKey, readonly [section: string, label: string]> = {
  actifs: ['Vivier', 'Profils actifs'],
  postes: ['Vivier', 'Postes du parcours'],
  rattaches: ['Tables dérivées', 'Expérience métier — postes rattachés'],
}

/** Le rapport tel qu'il arrive : des sections, chacune portant ses métriques nommées. */
export type VivierSnapshot = Record<string, Record<string, number>>

/** Dénominateur d'un ratio, lu dans le snapshot ; null si absent (dégradation propre). */
export const vivierRatioDenominator = (
  snapshot: VivierSnapshot,
  ratioOf: VivierRatioKey,
): number | null => snapshot[RATIO_SOURCE[ratioOf][0]]?.[RATIO_SOURCE[ratioOf][1]] ?? null

/**
 * Un nombre écrit dans la langue de l'interface.
 *
 * `toLocaleString()` sans argument ne prend PAS la langue de l'application : il prend celle de
 * l'environnement qui exécute — le navigateur côté client, le serveur au rendu. L'écran affichait
 * donc « 2,561 » à un lecteur francophone. La langue se passe, elle ne se devine pas.
 */
export const formatCount = (value: number, locale: string): string => value.toLocaleString(locale)

/**
 * Métriques dont on sait expliquer l'absence : le libellé émis par le serveur, et le code que
 * l'écran de détail porte dans son adresse.
 *
 * ⚠️ LA CLÉ EST LA CHAÎNE EXACTE DU SERVEUR, apostrophe courbe comprise (`Niveau d’études dérivé`).
 * Renommer un libellé côté serveur rend la carte non cliquable **sans erreur ni signal** — même
 * mécanique que `VIVIER_METRIC_META` ci-dessous, qui perdrait sa bulle d'aide au même moment. Les
 * deux tables vivent donc dans ce fichier : un renommage les trouve ensemble ou pas du tout.
 *
 * Les trois cartes de séniorité n'y sont pas, et c'est voulu : « qui n'est pas SENIOR » n'est pas
 * un manque, c'est l'autre part d'une répartition.
 */
const GAP_ROUTE: Record<string, string> = {
  'Ancienneté renseignée': 'experienceYears',
  'Durée sur le poste actuel': 'currentRoleMonths',
  'Niveau d’études dérivé': 'educationLevel',
  'Date de modification source': 'jarviUpdatedAt',
}

/** Code d'adresse de l'écran de détail d'une métrique ; `null` si elle n'en a pas. */
export const gapRouteOf = (label: string): string | null => GAP_ROUTE[label] ?? null

/**
 * Libellé d'une métrique à partir du code lu dans l'adresse — la lecture inverse de `gapRouteOf`,
 * sur la même table : l'écran de détail nomme sa métrique, et le serveur ne lui envoie qu'un code.
 * `null` sur un code inconnu, ce qui vaut « cette page n'existe pas ».
 */
export const gapMetricLabel = (metric: string): string | null =>
  Object.keys(GAP_ROUTE).find((label) => GAP_ROUTE[label] === metric) ?? null

/** Métadonnées par libellé de métrique, exactement tels qu'émis par le back. */
export const VIVIER_METRIC_META: Record<string, VivierMetricMeta> = {
  // Vivier
  'Profils actifs': { hint: 'Candidats non supprimés présents dans la base.' },
  'Postes du parcours': {
    hint: 'Total des postes du parcours ayant un intitulé renseigné, tous candidats confondus (les positions Jarvi).',
  },
  Formations: { hint: 'Total des formations recensées, tous candidats confondus.' },

  // Qualifications du profil
  'Ancienneté renseignée': {
    hint: "Candidats dont l'expérience totale, en années, a pu être calculée.",
    ratioOf: 'actifs',
  },
  'Durée sur le poste actuel': {
    hint: "Candidats dont l'ancienneté sur le poste courant, en mois, est connue.",
    ratioOf: 'actifs',
  },
  'Niveau d’études dérivé': {
    hint: "Candidats pour qui un niveau d'études a pu être déduit des formations.",
    ratioOf: 'actifs',
  },
  'Date de modification source': {
    hint: 'Candidats portant une date de dernière modification côté Jarvi.',
    ratioOf: 'actifs',
  },
  'Séniorité SENIOR': { hint: 'Candidats classés SENIOR.', ratioOf: 'actifs' },
  'Séniorité CONFIRME': { hint: 'Candidats classés CONFIRMÉ.', ratioOf: 'actifs' },
  'Séniorité JUNIOR': { hint: 'Candidats classés JUNIOR.', ratioOf: 'actifs' },

  // Poste actuel
  CERTAIN: {
    hint: 'Candidats dont le poste actuel est identifié sans ambiguïté.',
    ratioOf: 'actifs',
  },
  AMBIGUOUS: {
    hint: 'Candidats dont le poste actuel est plausible mais non tranché (plusieurs postes concurrents).',
    ratioOf: 'actifs',
  },
  ABSENT: {
    hint: 'Candidats sans poste actuel identifiable dans le parcours.',
    ratioOf: 'actifs',
  },
  'Métier reconnu': {
    hint: 'Candidats dont le poste actuel a été rattaché à un concept métier du référentiel.',
    ratioOf: 'actifs',
  },

  // Tables dérivées
  'Expérience secteur — candidats': {
    hint: "Candidats distincts ayant au moins une ligne d'expérience sectorielle dérivée.",
    ratioOf: 'actifs',
  },
  'Expérience secteur — lignes': {
    hint: "Total des lignes d'expérience par secteur (une par couple candidat × secteur).",
  },
  'Expérience métier — candidats': {
    hint: "Candidats distincts ayant au moins une ligne d'expérience métier dérivée.",
    ratioOf: 'actifs',
  },
  'Expérience métier — lignes': {
    hint: "Total des lignes d'expérience par métier (une par couple candidat × concept métier).",
  },
  'Expérience métier — postes rattachés': {
    hint: 'Postes du parcours rattachés à un concept métier du référentiel.',
    ratioOf: 'postes',
  },
  'Expérience métier — dont rattachés à l’identique': {
    hint: "Parmi les postes rattachés, ceux dont l'intitulé correspond exactement à un libellé du référentiel, sans rapprochement approché.",
    ratioOf: 'rattaches',
  },
  'Langues — candidats': {
    hint: 'Candidats distincts déclarant au moins une langue.',
    ratioOf: 'actifs',
  },
  'Langues — lignes': {
    hint: 'Total des lignes de langue (une par couple candidat × langue).',
  },

  // Référentiels
  'Concepts métier': { hint: 'Entrées de type MÉTIER dans le vocabulaire.' },
  'Concepts compétence': { hint: 'Entrées de type COMPÉTENCE dans le vocabulaire.' },
  Libellés: { hint: 'Variantes textuelles rattachées aux concepts (synonymes, formes).' },
  'Concepts proches': { hint: 'Paires de concepts jugés proches (rapprochement sémantique).' },
  'Entreprises classées': {
    hint: "Entreprises pour lesquelles un secteur d'activité a été déterminé.",
  },
}
