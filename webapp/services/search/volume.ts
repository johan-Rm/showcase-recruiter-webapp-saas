import type { SearchCriterion } from '../../shared/types/search'

/**
 * Des critères de recherche → les paramètres d'URL qui les comptent.
 *
 * Métier pur. La facette d'un critère EST le nom du paramètre attendu par le moteur : le formulaire
 * pose `job`, l'API lit `job`. On relaie donc sans table de correspondance — en inventer une
 * donnerait deux vocabulaires à garder alignés, et le premier oubli ferait un compte silencieusement
 * trop large.
 *
 * Les mots-clés partent en `q[]` et non en `q` répété : PHP écrase les clés répétées à l'analyse de
 * la requête, seule la notation crochets produit un tableau (cf. `CandidateSearchProvider`). Un
 * `q=php&q=java` compterait « java » seul, sans rien signaler.
 */

/** La seule facette multivaluée du moteur — les autres suivent le « dernier gagne ». */
const MULTI_VALUED_FACET = 'q'

/** Clé de tableau, pour que le moteur reçoive les mots-clés comme une liste et non comme un scalaire. */
const MULTI_VALUED_KEY = `${MULTI_VALUED_FACET}[]`

export type SearchVolumeQuery = Record<string, string | string[]>

/**
 * Les paramètres de comptage, valeurs vides écartées.
 *
 * Rend un objet vide quand aucun critère n'est posé : c'est à l'appelant de ne pas compter le vivier
 * entier, qui n'est pas un résultat de recherche.
 */
export const toVolumeQuery = (criteria: readonly SearchCriterion[]): SearchVolumeQuery => {
  const query: SearchVolumeQuery = {}
  const keywords: string[] = []

  for (const { facet, value } of criteria) {
    const trimmed = value.trim()

    if ('' === trimmed) {
      continue
    }

    if (MULTI_VALUED_FACET === facet) {
      keywords.push(trimmed)
      continue
    }

    query[facet] = trimmed
  }

  if (keywords.length > 0) {
    query[MULTI_VALUED_KEY] = keywords
  }

  return query
}

/**
 * L'empreinte d'un jeu de critères — deux jeux équivalents ont la même.
 *
 * Sert à savoir si une réponse concerne encore ce qui est à l'écran : deux comptages en vol peuvent
 * revenir dans le désordre, et c'est l'état courant du formulaire qui fait foi, pas l'ordre
 * d'arrivée. Elle évite aussi de recompter à l'identique quand un champ change sans changer les
 * critères — passer le curseur de 0 à 0, cocher puis décocher un niveau d'études.
 */
export const volumeKey = (criteria: readonly SearchCriterion[]): string =>
  JSON.stringify(toVolumeQuery(criteria))
