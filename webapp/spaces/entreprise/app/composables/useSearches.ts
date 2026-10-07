import { normalizeSearch, normalizeSearchWithResults } from '#services/search/normalize'

import { loadSearches } from '../bootstrap/loaders'

import { useCandidatesStore } from '../stores/candidates'
import { useSearchesStore } from '../stores/searches'
import { useUsersStore } from '../stores/users'

import type { Search, SearchCriterion, SearchWithResults, StoredSearch } from '#shared/types/search'

/**
 * Mes recherches : l'appel BFF vit ici, l'état vit dans les stores (standard des stores).
 *
 * La collection est partagée parce que trois écrans la lisent — la collection en cartes, puis la
 * parcours des profils et Ma sélection, qui ont besoin du nom et des critères de la recherche. Deux
 * sources de vérité pour la même liste divergeraient dès la première duplication.
 *
 * Ce composable ÉCLATE ce que l'API sert avant de le ranger : les recherches d'un côté, leurs
 * auteurs dédoublonnés de l'autre. Aucun store ne connaît la forme des réponses.
 */
export const useSearches = () => {
  const searches = useSearchesStore()
  const users = useUsersStore()
  const candidates = useCandidatesStore()
  // `useRequestFetch` et non `$fetch` : au SSR, ce dernier ne rejoue pas les en-têtes de la requête
  // entrante — dont le cookie de session. Le BFF ne trouverait pas de session et répondrait 401,
  // panne INVISIBLE côté client, le navigateur envoyant le cookie de son côté.
  const request = useRequestFetch()

  // Le chargement passe par le CHARGEUR de l'espace, celui que le registre de bootstrap déclare
  // critique sur l'accueil : une seule définition de ce qu'est « charger mes recherches », quel que
  // soit le chemin d'arrivée. Appelé sans sa garde — un écran qui demande sa collection la veut
  // fraîche, et c'est ce que faisait déjà cet appel.
  //
  // Le payload SSR porte l'état des stores (@pinia/nuxt) : renvoyer la collection la sérialiserait
  // une seconde fois.
  const collection = useAsyncData('entreprise:searches', async () => {
    await loadSearches().load()

    return true
  })

  const { pending, error, refresh } = collection

  /**
   * Lancer une recherche, c'est la CRÉER : elle s'auto-enregistre dès le premier critère, il n'y a
   * pas d'action « Enregistrer » côté produit.
   *
   * LE NOM NE PART QUE S'IL A ÉTÉ CORRIGÉ À LA MAIN. Vide, l'API garde
   * l'auto-nommage actif et recompose le nom depuis les critères : la recherche continuera donc de
   * se renommer quand on ajustera ses critères plus tard. Envoyer le nom COMPOSÉ éteindrait
   * l'auto-nommage et figerait un nom qui mentirait au premier critère changé.
   *
   * La tâche `159` avait décidé qu'aucun nom ne voyageait ici — l'écran ne permettait pas encore
   * d'en saisir un avant de lancer.
   *
   * UN SEUL appel : la réponse porte la recherche ET son jeu de résultats. L'écran ouvert derrière
   * n'a donc plus rien à demander, il lit ce que cet appel vient de ranger.
   */
  const create = async (criteria: SearchCriterion[], name = ''): Promise<StoredSearch> => {
    const normalized = normalizeSearchWithResults(
      await request<SearchWithResults>('/api/searches', {
        method: 'POST',
        body: { criteria, name: name.trim() },
      }),
    )

    users.setUsers([normalized.owner])
    searches.setSearch(normalized.search)
    searches.setResults(normalized.search.id, normalized.set)
    candidates.setCandidates(normalized.candidates)

    return normalized.search
  }

  /**
   * Déclarer une recherche terminée, ou la rouvrir.
   *
   * Rend une CLÉ d'erreur plutôt que de lever : les écrans affichent déjà leurs refus par une clé
   * (cf. `createErrorKey` de l'accueil), et faire remonter un code HTTP jusqu'à une page lui ferait
   * connaître le transport.
   *
   * Le `409` n'est pas une panne : il dit que la recherche a changé d'état ailleurs — deux onglets,
   * ou un autre poste. On RECHARGE alors la collection, sans quoi l'écran continuerait d'afficher
   * une recherche modifiable qui ne l'est plus, et le clic suivant échouerait pareil.
   */
  const setClosed = async (search: StoredSearch, isClosed: boolean): Promise<string> => {
    try {
      const updated = await request<SearchWithResults>(`/api/searches/${search.id}`, {
        method: 'PATCH',
        body: { isClosed },
      })
      const normalized = normalizeSearchWithResults(updated)

      users.setUsers([normalized.owner])
      searches.setSearch(normalized.search)

      return ''
    } catch (error) {
      if (409 === (error as { statusCode?: number }).statusCode) {
        await refresh()

        return 'search.closure.conflict'
      }

      return 'search.closure.error'
    }
  }

  /**
   * Renommer, partager, retoucher les critères — ou plusieurs à la fois.
   *
   * Un nom VIDÉ réactive le nom automatique — règle portée par l'API, que l'écran doit annoncer
   * sinon l'utilisateur croit avoir effacé le nom.
   *
   * CHANGER LES CRITÈRES EST UN LANCEMENT : l'API rejoue le jeu de résultats et le renvoie. On le
   * range donc comme le fait la création — sans quoi la Visionneuse ouvrirait l'ancien jeu sur des
   * critères neufs. La réponse porte ce jeu à chaque appel, y compris pour un simple renommage :
   * l'enregistrer coûte le même prix et évite d'avoir deux chemins à tenir.
   */
  const update = async (
    search: StoredSearch,
    patch: { name?: string; isShared?: boolean; criteria?: SearchCriterion[] },
  ): Promise<string> => {
    try {
      const normalized = normalizeSearchWithResults(
        await request<SearchWithResults>(`/api/searches/${search.id}`, {
          method: 'PATCH',
          body: patch,
        }),
      )

      users.setUsers([normalized.owner])
      searches.setSearch(normalized.search)
      searches.setResults(normalized.search.id, normalized.set)
      candidates.setCandidates(normalized.candidates)

      return ''
    } catch (error) {
      // Le 409 est ATTEIGNABLE ici, et c'est le seul endroit où il l'est : renommer une recherche
      // close est le seul geste que le serveur refuse pour cause d'état.
      if (409 === (error as { statusCode?: number }).statusCode) {
        await refresh()

        return 'search.closure.conflict'
      }

      return 'search.manage.error'
    }
  }

  /** Dupliquer : la copie prend la tête de la collection, l'écran ne bouge pas. */
  const duplicate = async (search: StoredSearch): Promise<string> => {
    try {
      const normalized = normalizeSearch(
        await request<Search>(`/api/searches/${search.id}/duplicate`, { method: 'POST' }),
      )

      users.setUsers([normalized.owner])
      searches.setSearch(normalized.search)

      return ''
    } catch {
      return 'search.manage.error'
    }
  }

  /** Supprimer : définitif côté serveur, d'où la confirmation nommée que l'écran demande avant. */
  const remove = async (search: StoredSearch): Promise<string> => {
    try {
      // Type de retour explicite, et il est OBLIGATOIRE ici : sans lui, le chemin est rapproché des
      // routes déclarées, et `/api/searches/{quelque chose}` en compte une qui ne répond qu'en
      // lecture — le plan de travail. Les méthodes autorisées se réduisent à cette intersection, et
      // supprimer ne compile pas.
      await request<unknown>(`/api/searches/${search.id}`, { method: 'DELETE' })
      searches.removeSearch(search.id)

      return ''
    } catch {
      return 'search.manage.error'
    }
  }

  return {
    searches: computed(() => searches.getSearches),
    total: computed(() => searches.getTotal),
    /** Le nom d'un auteur, recomposé — il ne voyage plus sur la recherche (cf. store `users`). */
    ownerName: (search: StoredSearch): string => users.getUserName(search.ownerId),
    create,
    setClosed,
    update,
    duplicate,
    remove,
    pending,
    error,
    refresh,
    /**
     * La collection, une fois chargée — à attendre quand la page doit DÉCIDER avant de rendre.
     *
     * `pending` ne suffit pas à ça : au rendu serveur il vaut encore `true` pendant le setup, et un
     * `watch` posé dessus ne se rejoue jamais faute d'ordonnanceur. Une page qui redirige selon ce
     * que contient la collection rendrait donc son HTML d'abord et corrigerait dans le navigateur —
     * l'écran vide s'afficherait le temps d'un battement.
     */
    loaded: collection,
  }
}
