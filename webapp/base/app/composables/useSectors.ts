/**
 * Liste fermée des secteurs d'activité, servie par l'API (source unique : `config/referentiels/
 * secteurs.yaml`). Le dropdown d'inscription entreprise l'itère.
 *
 * `useFetch` : SSR-safe et mis en cache par sa clé, donc une seule requête même si plusieurs champs
 * la demandent. Défaut `[]` : le dropdown ne casse pas tant que la liste n'est pas arrivée.
 */
export const useSectors = () => {
  return useFetch<string[]>('/api/sectors', {
    key: 'sectors',
    default: () => [],
  })
}
