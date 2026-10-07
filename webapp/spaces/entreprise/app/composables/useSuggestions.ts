import type { SearchSuggestion, SearchSuggestionCollection } from '#shared/types/search'

/** Les deux vocabulaires interrogeables : les intitulés de poste, et les compétences. */
export type SuggestionType = 'JOB' | 'SKILL'

/**
 * L'auto-complétion des champs de recherche.
 *
 * Composable et non service : il fait du réseau. Sans état réactif pour autant — il rend une
 * fonction, et c'est le champ qui garde ce qu'il en fait. Deux champs sur le même écran interrogent
 * ainsi le même vocabulaire sans partager une liste qui se marcherait dessus.
 *
 * Une erreur n'interrompt pas : une auto-complétion qui échoue doit laisser saisir. Le recruteur
 * peut toujours taper son terme et lancer.
 *
 * Mais elle se DIT. Rendre une liste vide sur erreur rendait la panne et le vocabulaire vide
 * indistinguables, et le champ ne pouvait donc pas dire lequel des deux il montrait : « aucun
 * résultat » s'affichait quand le service ne répondait pas.
 */
export const useSuggestions = () => {
  const suggest = async (
    type: SuggestionType,
    term: string,
  ): Promise<{ items: SearchSuggestion[]; failed: boolean }> => {
    try {
      // Type EXPLICITE : sans lui, `$fetch` infère la réponse depuis le registre de routes Nitro, et
      // l'union fait exploser la profondeur d'inférence (TS2321).
      const collection = await $fetch<SearchSuggestionCollection>('/api/suggestions', {
        query: { type, q: term },
      })

      return { items: collection.items, failed: false }
    } catch {
      return { items: [], failed: true }
    }
  }

  return { suggest }
}
