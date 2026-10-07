import { toVolumeQuery, volumeKey } from '#services/search/volume'

import type { SearchCriterion } from '#shared/types/search'

/**
 * Combien de profils les critères en cours de saisie ramèneraient.
 *
 * Le formulaire se remplissait à l'aveugle : chaque suggestion annonce ce qu'elle ramène, mais rien
 * n'annonçait ce que la COMBINAISON ramène. Et comme lancer crée une recherche, découvrir le volume
 * après coup laisse une recherche à nettoyer derrière chaque essai.
 *
 * Débouncé, plus longuement que l'auto-complétion : celle-ci accompagne la frappe, celui-ci suit un
 * formulaire qu'on remplit champ par champ, et le compte n'a d'intérêt qu'une fois le geste posé.
 *
 * Effet EXPLICITE : rien ne part à l'invocation, l'appelant déclenche `count()`.
 *
 * Une panne rend `failed` et laisse `total` à sa dernière valeur connue — un volume est une aide, pas
 * une garde : le lancement reste possible même quand le comptage ne répond plus.
 */
const DEBOUNCE_MS = 400

export const useSearchVolume = () => {
  const total = ref<number | null>(null)
  const pending = ref(false)
  const failed = ref(false)

  // L'empreinte de ce qui est à l'écran. Deux comptages en vol peuvent revenir dans le désordre :
  // une réponse dont l'empreinte a changé entre-temps est PÉRIMÉE et se jette, sinon un compte
  // ancien viendrait recouvrir un compte juste.
  const wanted = ref('')

  const fetchTotal = async (
    criteria: readonly SearchCriterion[],
    requested: string,
  ): Promise<void> => {
    try {
      // Type explicite : sans lui, `$fetch` infère depuis le registre de routes Nitro et l'union
      // fait exploser la profondeur d'inférence (même raison que `useSuggestions`).
      const { total: count } = await $fetch<{ total: number }>('/api/candidates/count', {
        query: toVolumeQuery(criteria),
      })

      if (requested !== wanted.value) {
        return
      }

      total.value = count
      failed.value = false
    } catch {
      if (requested !== wanted.value) {
        return
      }

      failed.value = true
    } finally {
      if (requested === wanted.value) {
        pending.value = false
      }
    }
  }

  const run = useDebounceFn(fetchTotal, DEBOUNCE_MS)

  /**
   * Demande le volume des critères donnés.
   *
   * Sans critère, aucun appel et aucun compte : le vivier entier n'est pas un résultat de recherche,
   * et l'annoncer laisserait croire qu'un formulaire vide cherche quelque chose.
   */
  const count = (criteria: readonly SearchCriterion[]): void => {
    const key = volumeKey(criteria)

    if (key === wanted.value) {
      return
    }

    wanted.value = key

    if (0 === criteria.length) {
      total.value = null
      pending.value = false
      failed.value = false

      return
    }

    pending.value = true
    run(criteria, key)
  }

  return { total, pending, failed, count }
}
