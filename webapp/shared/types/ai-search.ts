/**
 * Contrats de la recherche assistée : la phrase du recruteur, traduite par le BFF
 * (server/api/ai/parse-search) en critères structurés, puis affichée par le client
 * (useAiSearch + <SearchFiltersAi>).
 *
 * Invariant du cadrage : la phrase est la SEULE donnée envoyée au modèle — aucun profil du vivier
 * ne transite. Un critère absent de la demande reste null / tableau vide.
 *
 * Ces énumérations (disponibilité, horizon, niveaux de langue) sont posées à la main tant que le
 * contrat de recherche n'existe pas ; à réconcilier avec les types générés depuis openapi.json le
 * jour où la recherche serveur les consommera.
 */

export const AVAILABILITY_VALUES = ['IMMEDIATE', 'NOTICE_1M', 'NOTICE_3M'] as const
export const RETURN_HORIZON_VALUES = ['M3', 'M6', 'Y1', 'THINKING'] as const
export const PROFICIENCY_LEVELS = [
  'ELEMENTARY',
  'LIMITED_WORKING',
  'PROFESSIONAL_WORKING',
  'FULL_PROFESSIONAL',
  'NATIVE_OR_BILINGUAL',
] as const

/** Un concept (métier, compétence, secteur, séniorité) et ses variantes fr/en — matche si UNE matche. */
export interface KeywordGroup {
  terms: string[]
}

export interface LanguageCriterion {
  /** Code ISO 639-1 (fr, en, ar, es, nl, de…). */
  language: string
  /** Niveau minimum exigé, null = peu importe. */
  minProficiency: (typeof PROFICIENCY_LEVELS)[number] | null
}

/** Critères produits par la route IA. ET entre les groupes de mots-clés, OU à l'intérieur d'un groupe. */
export interface SearchCriteria {
  keywords: KeywordGroup[]
  /** Pays de résidence ACTUEL, ISO 3166-1 alpha-2 (FR, BE, MA…). */
  country: string | null
  city: string | null
  /** Langues exigées (ET entre elles). */
  languages: LanguageCriterion[]
  /** Préavis / disponibilité professionnelle. */
  availability: (typeof AVAILABILITY_VALUES)[number] | null
  /** Horizon de retour au Maroc. */
  returnHorizon: (typeof RETURN_HORIZON_VALUES)[number] | null
  geoMobility: boolean | null
}

export const EMPTY_CRITERIA: SearchCriteria = {
  keywords: [],
  country: null,
  city: null,
  languages: [],
  availability: null,
  returnHorizon: null,
  geoMobility: null,
}

/**
 * Un critère prêt à afficher : `id` renvoyé tel quel par l'action « retirer », `label` déjà traduit.
 * <SearchFiltersAi> ne connaît que cette forme — pas la structure des critères.
 */
export interface UnderstoodFilter {
  id: string
  label: string
}
