import type { CandidateCard, CriterionMatch } from '#shared/types/candidate-card'
import type {
  Search,
  SearchCollection,
  SearchResults,
  SearchSet,
  SearchProfiles,
  SearchWithResults,
  SearchWorkspace,
  StoredSearch,
} from '#shared/types/search'
import type { SpaceUser } from '#shared/types/user'

/**
 * Éclate ce que l'API sert en ce que les stores gardent.
 *
 * L'API DÉNORMALISE — c'est son travail : elle sert une recherche avec le nom de son auteur, et des
 * cartes portant leur état dans cette recherche, pour qu'un écran se peigne d'une requête. Le client,
 * lui, garde plusieurs recherches et plusieurs jeux en même temps : ce qui était commode en réponse
 * devient de la donnée en double dans un store, et deux copies d'une même vérité finissent par se
 * contredire.
 *
 * Ce module fait la traduction, et il la fait à l'ENTRÉE : un seul endroit sait à quoi ressemble la
 * réponse, les stores ne connaissent que la forme normalisée.
 *
 * Pur, sans Vue ni Pinia : c'est du remaniement de données, il se teste sans runtime.
 */

/** Ce qu'un jeu de résultats donne aux stores : des cartes d'un côté, des relations de l'autre. */
export interface NormalizedResults {
  /** Pour le pool : les cartes, débarrassées de ce qui appartient à la recherche. */
  candidates: CandidateCard[]
  /**
   * Pour la recherche : ce qui la relie à ces candidats.
   *
   * Deux listes n'en font pas partie. `selectedIds` ne vient jamais du serveur ; `retainedIds` en
   * vient, mais d'un AUTRE appel — le jeu paginé ne connaît pas les retenus posés au-delà de sa
   * première page, et prétendre les normaliser ici en rendrait un sous-ensemble.
   */
  set: Omit<SearchSet, 'selectedIds' | 'retainedIds'>
}

/** Ce qu'une recherche donne aux stores : la recherche sans son auteur, et l'auteur. */
export interface NormalizedSearch {
  search: StoredSearch
  owner: SpaceUser
}

/**
 * Détache le nom de l'auteur de la recherche qui le cite.
 *
 * Le nom part au store des personnes, `ownerId` reste le lien. Dix recherches du même collègue ne
 * gardent plus dix fois son nom — et le renommer ne demande plus de le corriger partout.
 */
export const normalizeSearch = (search: Search): NormalizedSearch => {
  const { ownerName, ...rest } = search

  return {
    search: rest,
    owner: { id: search.ownerId, name: ownerName },
  }
}

/** La même chose sur une collection : les recherches d'un côté, leurs auteurs dédoublonnés de l'autre. */
export const normalizeSearches = (
  collection: SearchCollection,
): { searches: StoredSearch[]; owners: SpaceUser[] } => {
  const normalized = collection.items.map(normalizeSearch)
  const owners = new Map(normalized.map(({ owner }) => [owner.id, owner]))

  return {
    searches: normalized.map(({ search }) => search),
    owners: [...owners.values()],
  }
}

/**
 * Sépare les cartes de leur état dans la recherche.
 *
 * `isNew` et `isFavorite` arrivent SUR la carte parce que l'API sert une recherche à la fois. Gardés
 * là, ils se mélangeraient dès que deux recherches partagent un profil : le pool est global, la
 * relation ne l'est pas. Ils deviennent donc deux listes d'identifiants portées par la recherche, et
 * la carte retourne à `null` — sa valeur hors recherche.
 *
 * `resultIds` vient de la réponse et non des cartes : il porte le jeu servi, là où `items` n'est
 * qu'une page.
 *
 * **RETENUS, ÉCARTÉS ET VUS VIENNENT DE LA RÉPONSE EUX AUSSI, quand elle les porte** : ils décrivent
 * alors la recherche ENTIÈRE. Lus sur les cartes, ils ne décrivaient que ce qui était chargé — un
 * profil retenu hors du jeu servi était compté sans être connu, et il fallait trois appels de
 * rattrapage pour rétablir la vérité.
 *
 * Le repli sur les cartes reste, et il sert : les périmètres `?scope=` ne portent pas ces listes,
 * puisqu'ils sont demandés une fois l'état déjà connu. `isNew`, lui, n'a pas d'équivalent servi et
 * se lit toujours sur les cartes reçues.
 */
export const normalizeResults = (results: SearchResults): NormalizedResults => {
  const favoriteIds: string[] = []
  const newIds: string[] = []
  const seenIds: string[] = []
  const dismissedIds: string[] = []
  const matchesById: Record<string, CriterionMatch[]> = {}

  const candidates = results.items.map((card) => {
    if (true === card.isFavorite) favoriteIds.push(card.id)
    if (true === card.isNew) newIds.push(card.id)
    if (true === card.isSeen) seenIds.push(card.id)
    if (true === card.isDismissed) dismissedIds.push(card.id)
    if (card.matches) matchesById[card.id] = card.matches

    const {
      isFavorite: _favorite,
      isNew: _new,
      isSeen: _seen,
      isDismissed: _dismissed,
      matches: _matches,
      ...pooled
    } = card

    return {
      ...pooled,
      isFavorite: null,
      isNew: null,
      isSeen: null,
      isDismissed: null,
      matches: null,
    }
  })

  return {
    candidates,
    // Pas de `retainedIds` ici, et ce n'est pas un oubli : les CARTES des retenus viennent de leur
    // propre appel (`?scope=favorites`), une liste d'identifiants ne s'affichant pas.
    set: {
      resultIds: results.resultIds,
      favoriteIds: results.favoriteIds ?? favoriteIds,
      newIds,
      seenIds: results.seenIds ?? seenIds,
      dismissedIds: results.dismissedIds ?? dismissedIds,
      matchesById,
    },
  }
}

/**
 * L'état de travail, rangé par recherche.
 *
 * **RIEN N'Y EST DÉDUIT DES FICHES.** Les cinq listes viennent du serveur et décrivent la recherche
 * entière ; un compte tiré de ce qui est chargé vaudrait « parmi ce que j'ai » et rétrécirait avec le
 * chargement. C'est toute la raison d'être de cette route.
 *
 * L'explication des correspondances n'y est plus : elle coûte quinze fois un identifiant et ne sert
 * qu'aux profils affichés. Ce qui est déjà connu d'une recherche est donc préservé — sans quoi
 * recharger l'état effacerait les explications acquises à l'ouverture.
 */
export const normalizeWorkspace = (
  workspace: SearchWorkspace,
  known: Readonly<Record<string, Readonly<Record<string, CriterionMatch[]>>>> = {},
): { sets: Record<string, NormalizedResults['set']> } => {
  const sets: Record<string, NormalizedResults['set']> = {}

  for (const entry of workspace.searches) {
    sets[entry.id] = {
      resultIds: entry.resultIds,
      favoriteIds: entry.favoriteIds,
      newIds: entry.newIds,
      seenIds: entry.seenIds,
      dismissedIds: entry.dismissedIds,
      matchesById: { ...(known[entry.id] ?? {}) },
    }
  }

  return { sets }
}

/**
 * Les fiches d'un lot, prêtes pour la liste commune.
 *
 * Elles arrivent SANS état de travail — le serveur ne sait pas de quelle recherche on parle —, et
 * c'est exactement ce que la liste commune attend : la relation se recolle à l'affichage.
 */
export const normalizeProfiles = (payload: SearchProfiles): CandidateCard[] =>
  payload.items.map((card) => ({
    ...card,
    isFavorite: null,
    isNew: null,
    isSeen: null,
    isDismissed: null,
    matches: null,
  }))

/** L'écriture d'une recherche renvoie les deux d'un coup : on les éclate d'un coup. */
export const normalizeSearchWithResults = (
  payload: SearchWithResults,
): NormalizedSearch & NormalizedResults => ({
  ...normalizeSearch(payload.search),
  ...normalizeResults(payload.results),
})

/**
 * Recompose une carte TELLE QUE L'ÉCRAN LA VEUT : le candidat du pool, remis dans le contexte d'une
 * recherche.
 *
 * C'est l'opération inverse de `normalizeResults`, et elle a lieu au dernier moment. Les composants
 * continuent donc de recevoir une `CandidateCard` complète, avec son `isFavorite` et son `isNew` —
 * la normalisation vit dans les stores, elle ne remonte pas jusqu'aux vues.
 */
export const cardInSearch = (
  candidate: CandidateCard,
  set: {
    favoriteIds: ReadonlySet<string>
    newIds: ReadonlySet<string>
    seenIds: ReadonlySet<string>
    matchesById?: Readonly<Record<string, CriterionMatch[]>>
  },
): CandidateCard => ({
  ...candidate,
  isFavorite: set.favoriteIds.has(candidate.id),
  isNew: set.newIds.has(candidate.id),
  isSeen: set.seenIds.has(candidate.id),
  // La pile ne montre QUE ce qui reste à trier : ce qui s'y trouve n'est pas écarté.
  isDismissed: false,
  // POURQUOI ce profil, dans CETTE recherche : recollé ici et nulle part ailleurs. Absent du jeu
  // (un périmètre `?scope=` n'en sert pas), on garde ce que la carte portait.
  matches: set.matchesById?.[candidate.id] ?? candidate.matches,
})

/**
 * Le jeu d'une recherche dans l'ordre du serveur, cartes manquantes ignorées.
 *
 * L'ordre vient de `resultIds` et non du pool : c'est le serveur qui range (profils vérifiés en
 * tête), et un pool est un sac sans ordre. Une carte pas encore chargée est simplement absente — la
 * liste se remplit à mesure, elle ne troue pas.
 */
export const cardsInSearch = (
  resultIds: readonly string[],
  pool: Readonly<Record<string, CandidateCard>>,
  set: {
    favoriteIds: readonly string[]
    newIds: readonly string[]
    seenIds: readonly string[]
    dismissedIds: readonly string[]
    matchesById?: Readonly<Record<string, CriterionMatch[]>>
  },
): CandidateCard[] => {
  const marks = {
    favoriteIds: new Set(set.favoriteIds),
    newIds: new Set(set.newIds),
    seenIds: new Set(set.seenIds),
    matchesById: set.matchesById,
  }
  const dismissed = new Set(set.dismissedIds)

  return resultIds
    .filter((id) => !dismissed.has(id))
    .map((id) => pool[id])
    .filter((candidate): candidate is CandidateCard => undefined !== candidate)
    .map((candidate) => cardInSearch(candidate, marks))
}
