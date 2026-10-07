import type { CriterionMatch } from '#shared/types/candidate-card'
import type { SearchSet, StoredSearch } from '#shared/types/search'

// Le domaine RECHERCHE : les recherches du recruteur, celle qu'il regarde, et l'état de travail de
// chacune.
//
// UNE SEULE copie de chaque recherche, indexée par identifiant. La fiche ouverte n'est pas une
// seconde entité : c'est une lecture de `byId` désignée par `currentId`. Deux copies divergeraient au
// premier renommage, et la carte annoncerait un nom que l'écran ouvert a déjà changé.
//
// `order` à part : un objet indexé n'a pas d'ordre, et celui de la collection vient du serveur
// (les plus récentes d'abord).
//
// `sets` porte ce qui relie une recherche à des candidats — appartenance, favoris, nouveautés,
// sélection. Ces quatre listes sont des relations, pas des attributs de candidat : le même profil
// peut être favori ici et pas là. Le pool de candidats, lui, ne les connaît pas.
type SearchesState = {
  byId: Record<string, StoredSearch>
  order: string[]
  total: number
  /** La recherche ouverte. Ici et non dans le store des candidats : c'est une notion de recherche. */
  currentId: string | null
  sets: Record<string, SearchSet>
}

const EMPTY_SET: SearchSet = {
  resultIds: [],
  favoriteIds: [],
  retainedIds: [],
  newIds: [],
  seenIds: [],
  dismissedIds: [],
  selectedIds: [],
  matchesById: {},
}

export const useSearchesStore = defineStore('searches', {
  state: (): SearchesState => ({
    byId: {},
    order: [],
    total: 0,
    currentId: null,
    sets: {},
  }),

  getters: {
    /** Les recherches dans l'ordre du serveur. */
    getSearches(state: SearchesState): StoredSearch[] {
      return state.order
        .map((id) => state.byId[id])
        .filter((search): search is StoredSearch => undefined !== search)
    },

    getTotal(state: SearchesState): number {
      return state.total
    },

    getSearchById(state: SearchesState): (id: string) => StoredSearch | null {
      return (id) => state.byId[id] ?? null
    },

    getCurrentId(state: SearchesState): string | null {
      return state.currentId
    },

    getCurrent(state: SearchesState): StoredSearch | null {
      return state.currentId ? (state.byId[state.currentId] ?? null) : null
    },

    /**
     * L'état de travail d'une recherche, ou un état vide si on ne la connaît pas.
     *
     * Vide et non `null` : les vues en dérivent des listes, et un `null` à traiter partout ne dirait
     * rien de plus que « aucun résultat pour l'instant ».
     */
    getSet(state: SearchesState): (searchId: string) => SearchSet {
      return (searchId) => state.sets[searchId] ?? EMPTY_SET
    },

    getResultIds(state: SearchesState): (searchId: string) => string[] {
      return (searchId) => state.sets[searchId]?.resultIds ?? []
    },

    getFavoriteIds(state: SearchesState): (searchId: string) => string[] {
      return (searchId) => state.sets[searchId]?.favoriteIds ?? []
    },

    /** Les retenus, dans l'ordre de l'API — la LISTE, là où `favoriteIds` n'en porte que le compte. */
    getRetainedIds(state: SearchesState): (searchId: string) => string[] {
      return (searchId) => state.sets[searchId]?.retainedIds ?? []
    },

    /** Ce que le recruteur courant a déjà regardé — son avancement, pas celui de ses collègues. */
    getSeenIds(state: SearchesState): (searchId: string) => string[] {
      return (searchId) => state.sets[searchId]?.seenIds ?? []
    },

    /** Ce qu'il a écarté : sorti de la pile, jamais de la base. */
    getDismissedIds(state: SearchesState): (searchId: string) => string[] {
      return (searchId) => state.sets[searchId]?.dismissedIds ?? []
    },

    getNewIds(state: SearchesState): (searchId: string) => string[] {
      return (searchId) => state.sets[searchId]?.newIds ?? []
    },

    /**
     * Les profils cochés DANS cette recherche.
     *
     * Portée par la recherche et non globale : cocher ici et dévoiler là serait la pire des
     * surprises. Elle survit donc aussi à un aller-retour vers une autre recherche — ce qu'on avait
     * coché est encore là au retour, alors qu'un état global l'aurait perdu.
     */
    getSelectedIds(state: SearchesState): (searchId: string) => string[] {
      return (searchId) => state.sets[searchId]?.selectedIds ?? []
    },
  },

  actions: {
    /** La collection : elle pose l'ordre et le total, et range les recherches dans l'index. */
    setCollection(searches: StoredSearch[], total: number): void {
      const byId = { ...this.byId }

      for (const search of searches) {
        byId[search.id] = search
      }

      this.byId = byId
      this.order = searches.map(({ id }) => id)
      this.total = Math.max(total, searches.length)
    },

    /**
     * Range UNE recherche, en remplaçant celle du même identifiant.
     *
     * Une recherche inconnue prend la tête de l'ordre : c'est le cas de la création, et la plus
     * récente est en tête. Une recherche déjà listée garde sa place — la relire ne la fait pas
     * remonter.
     */
    setSearch(search: StoredSearch): void {
      const known = undefined !== this.byId[search.id]

      this.byId = { ...this.byId, [search.id]: search }

      if (!known) {
        this.order = [search.id, ...this.order]
        this.total = this.total + 1
      }
    },

    removeSearch(searchId: string): void {
      const { [searchId]: _removed, ...byId } = this.byId
      const { [searchId]: _set, ...sets } = this.sets

      this.byId = byId
      this.sets = sets
      this.order = this.order.filter((id) => id !== searchId)
      this.total = Math.max(0, this.total - 1)
      if (this.currentId === searchId) {
        this.currentId = null
      }
    },

    setCurrentId(searchId: string | null): void {
      this.currentId = searchId
    },

    /**
     * Le jeu servi par l'API pour cette recherche.
     *
     * La SÉLECTION est préservée : ajuster un critère ne doit pas vider ce qu'on a coché
     * (cf. vue_la_table.md §5). Elle est en revanche débarrassée des profils sortis du jeu — cocher
     * un profil puis le faire disparaître d'un critère ne doit pas le laisser dans un lot à dévoiler.
     *
     * Les favoris et les nouveautés, eux, ne se cumulent pas d'un appel à l'autre : ils sont lus sur
     * les cartes servies, donc partiels tant que le jeu n'est pas entièrement chargé.
     */
    setResults(searchId: string, set: Omit<SearchSet, 'selectedIds' | 'retainedIds'>): void {
      const stillInSet = new Set(set.resultIds)
      const previous = this.getSet(searchId)

      this.sets = {
        ...this.sets,
        [searchId]: {
          ...set,
          // L'EXPLICATION SURVIT À UN JEU REPEINT, comme les retenus : un périmètre `?scope=` n'en
          // sert pas, et l'écraser par le vide effacerait le « pourquoi ce profil » de la
          // Visionneuse au premier rafraîchissement venu d'un autre écran.
          matchesById:
            0 === Object.keys(set.matchesById).length ? previous.matchesById : set.matchesById,
          selectedIds: this.getSelectedIds(searchId).filter((id) => stillInSet.has(id)),
          // LES RETENUS SURVIVENT À UN JEU REPEINT, filtrés comme la sélection : ils viennent d'un
          // appel à part, et un jeu paginé ne sait pas les reconstituer. Les écraser ici viderait
          // l'onglet par défaut du tableau chaque fois qu'un autre écran rafraîchit la recherche.
          retainedIds: this.getRetainedIds(searchId).filter((id) => stillInSet.has(id)),
        },
      }
    },

    /**
     * Une PAGE de plus, sans toucher à ce que la recherche sait déjà.
     *
     * Au-delà de la première page, l'API ne sert plus les listes d'état — délibérément : `[]` y
     * signifierait « aucun retenu », ce qui serait faux. Les poser telles quelles viderait donc le
     * jeu à chaque palier du chargement de fond, et les compteurs avec.
     *
     * Cette action ne reprend que ce qu'une page apporte VRAIMENT de neuf : l'explication des
     * correspondances des profils qu'elle porte. Tout le reste est laissé intact.
     */
    mergeMatches(searchId: string, matchesById: Record<string, CriterionMatch[]>): void {
      if (0 === Object.keys(matchesById).length) return

      const previous = this.getSet(searchId)

      this.sets = {
        ...this.sets,
        [searchId]: { ...previous, matchesById: { ...previous.matchesById, ...matchesById } },
      }
    },

    /**
     * Les retenus TELS QUE LE SERVEUR les connaît — la liste entière, servie à part.
     *
     * `/results` sert une PAGE : les favoris qui n'y figurent pas resteraient inconnus, et l'onglet
     * compterait ce qu'on a chargé au lieu de ce qu'on a retenu. Un remplacement et non une fusion.
     */
    setFavoriteIds(searchId: string, candidateIds: string[]): void {
      this.sets = {
        ...this.sets,
        [searchId]: { ...this.getSet(searchId), favoriteIds: [...candidateIds] },
      }
    },

    setRetainedIds(searchId: string, candidateIds: string[]): void {
      this.sets = {
        ...this.sets,
        [searchId]: { ...this.getSet(searchId), retainedIds: [...candidateIds] },
      }
    },

    /**
     * Retient un profil dans une recherche, ou cesse de le retenir.
     *
     * Le geste s'affiche avant d'être confirmé par le serveur : retenir doit être instantané. C'est
     * l'appelant qui remet la valeur d'avant si l'appel échoue — le store ne sait pas ce qu'est un
     * échec réseau.
     */
    setFavorite(searchId: string, candidateId: string, isFavorite: boolean): void {
      const current = this.getSet(searchId)
      const favoriteIds = isFavorite
        ? [...new Set([...current.favoriteIds, candidateId])]
        : current.favoriteIds.filter((id) => id !== candidateId)

      this.sets = { ...this.sets, [searchId]: { ...current, favoriteIds } }
    },

    /**
     * Marque des profils comme vus, sans jamais en retirer.
     *
     * En LOT parce que l'écran en produit par paquets, et cumulatif : « vu » ne se défait pas —
     * l'avoir regardé est un fait, pas un choix qu'on révise.
     */
    setSeen(searchId: string, candidateIds: string[]): void {
      const current = this.getSet(searchId)
      const seenIds = [...new Set([...current.seenIds, ...candidateIds])]

      this.sets = { ...this.sets, [searchId]: { ...current, seenIds } }
    },

    /**
     * La corbeille TELLE QUE LE SERVEUR la connaît — elle est servie à part, et non déduite du jeu.
     *
     * L'API sort les écartés du jeu de résultats : la pile ne les porte donc pas, et il faut les
     * demander pour les connaître. Un remplacement et non une fusion : c'est la vérité du serveur.
     */
    setDismissedIds(searchId: string, candidateIds: string[]): void {
      this.sets = {
        ...this.sets,
        [searchId]: { ...this.getSet(searchId), dismissedIds: [...candidateIds] },
      }
    },

    /**
     * Écarte ou remet des profils.
     *
     * Écarter marque AUSSI comme vu : on ne rejette pas ce qu'on n'a pas regardé, et un écarté
     * « non vu » ressortirait au prochain « reprendre au premier non vu ». Remettre efface l'écart,
     * jamais le fait d'avoir vu — c'est la règle que l'API applique déjà, le store la reflète.
     *
     * Un écarté quitte aussi la SÉLECTION et les FAVORIS : retenir et écarter sont deux verdicts
     * opposés sur le même profil, et le dernier posé l'emporte. Sans quoi « Ma sélection » compterait
     * parmi ses retenus un profil que la pile a déjà rendu invisible.
     */
    setDismissed(searchId: string, candidateIds: string[], dismissed: boolean): void {
      const current = this.getSet(searchId)
      const touched = new Set(candidateIds)

      this.sets = {
        ...this.sets,
        [searchId]: {
          ...current,
          seenIds: dismissed
            ? [...new Set([...current.seenIds, ...candidateIds])]
            : current.seenIds,
          favoriteIds: dismissed
            ? current.favoriteIds.filter((id) => !touched.has(id))
            : current.favoriteIds,
          dismissedIds: dismissed
            ? [...new Set([...current.dismissedIds, ...candidateIds])]
            : current.dismissedIds.filter((id) => !touched.has(id)),
          selectedIds: dismissed
            ? current.selectedIds.filter((id) => !touched.has(id))
            : current.selectedIds,
        },
      }
    },

    toggleSelected(searchId: string, candidateId: string): void {
      const current = this.getSet(searchId)
      const selectedIds = current.selectedIds.includes(candidateId)
        ? current.selectedIds.filter((id) => id !== candidateId)
        : [...current.selectedIds, candidateId]

      this.sets = { ...this.sets, [searchId]: { ...current, selectedIds } }
    },

    clearSelection(searchId: string): void {
      this.sets = { ...this.sets, [searchId]: { ...this.getSet(searchId), selectedIds: [] } }
    },

    reset(): void {
      this.byId = {}
      this.order = []
      this.total = 0
      this.currentId = null
      this.sets = {}
    },
  },
})
