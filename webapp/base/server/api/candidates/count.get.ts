import type { CandidateCardCollection } from '#shared/types/candidate-card'

/**
 * Combien de profils un jeu de critères ramènerait (GET /v1/search/candidates) : relais, réduit au
 * total.
 *
 * Sert à REMPLIR le formulaire, pas à lancer : lancer, c'est créer une recherche, et un recruteur qui
 * tâtonne en laisserait trois derrière lui. Ce comptage, lui, n'écrit rien.
 *
 * Même endpoint que la recherche, donc mêmes filtres implicites — profils visibles, actifs,
 * consentis. Un compte tiré d'ailleurs annoncerait un volume que le lancement ne retrouverait pas.
 *
 * `perPage=1` : l'enveloppe porte `total` quel que soit le nombre d'items, et on ne veut pas payer
 * l'hydratation de trente profils qu'on jette. Zéro n'est pas une option — le moteur plafonne à 1
 * au minimum.
 *
 * On ne rend QUE le total : servir les cartes ici ferait de ce relais une seconde recherche, avec
 * son propre masquage à tenir.
 */
export default defineEventHandler(async (event) => {
  const collection = await apiFetchForUser<CandidateCardCollection>(
    event,
    '/v1/search/candidates',
    { query: { ...getQuery(event), page: 1, perPage: 1 } },
  )

  return { total: collection.total }
})
