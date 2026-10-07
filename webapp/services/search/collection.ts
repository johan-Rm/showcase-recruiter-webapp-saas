import type { StoredSearch } from '../../shared/types/search'

/**
 * Comment une collection de recherches se présente à l'écran.
 *
 * Couche PURE : les pages reçoivent une collection déjà chargée et lisent ce que ces fonctions en
 * tirent. Le tri et le découpage sont des règles d'AFFICHAGE et non des demandes au serveur — la
 * collection continue de tout servir, comme l'accueil le fait déjà pour ses recherches récentes.
 */

/** Une recherche est close dès qu'elle porte une date : l'absence de clé vaut « active ». */
const isClosed = (search: StoredSearch): boolean => Boolean(search.closedAt)

/**
 * Les trois groupes de l'écran de collection.
 *
 * Le rangement est tout le bénéfice de la clôture : une recherche déclarée finie qui reste au
 * milieu des actives encombre autant qu'avant, et le geste ne servirait qu'à changer une teinte.
 *
 * Les recherches d'un collègue forment un groupe à part parce qu'elles ne s'utilisent pas comme
 * les siennes — sur les miennes je travaille, sur les leurs je regarde. La carte le dit déjà dans
 * son rail, qui montre mon avancement ici et le seul compte des retenus là.
 *
 * **La clôture l'emporte sur l'appartenance** : une recherche partagée ET close descend chez les
 * closes. Le pli veut dire « plus rien à y faire », et c'est vrai quel qu'en soit l'auteur.
 */
export const groupSearches = (
  searches: readonly StoredSearch[],
): { mine: StoredSearch[]; shared: StoredSearch[]; closed: StoredSearch[] } => ({
  mine: searches.filter((search) => !isClosed(search) && search.isOwn),
  shared: searches.filter((search) => !isClosed(search) && !search.isOwn),
  closed: searches.filter(isClosed),
})

/**
 * Combien de recherches l'écran de collection montre HORS du pli — les miennes et celles qu'on m'a
 * partagées.
 *
 * C'est ce que le lien de l'accueil annonce : il doit promettre ce qu'on verra en arrivant, et non
 * ce que l'accueil vient d'afficher. Le total absolu dépasserait ce qui est visible, les closes
 * attendant derrière leur pli.
 */
export const activeCount = (searches: readonly StoredSearch[]): number =>
  searches.filter((search) => !isClosed(search)).length

/**
 * Les dernières recherches À MOI, pour l'accueil.
 *
 * L'accueil sert à REPRENDRE un travail en cours. Une recherche close n'a rien à reprendre — et
 * comme la clôturer la « touche », elle serait la plus récente de toutes. Une recherche d'un
 * collègue non plus : je ne peux ni la modifier, ni la renommer, ni y retenir un profil, et elle
 * occuperait une des six places de l'écran d'arrivée à la place d'une des miennes.
 *
 * L'ordre des opérations n'est pas indifférent : **on filtre AVANT de trier**. Dans l'autre sens,
 * une recherche écartée mais fraîchement touchée prendrait une place servie sans jamais s'afficher.
 */
export const recentToResume = (searches: readonly StoredSearch[], max: number): StoredSearch[] =>
  searches
    .filter((search) => !isClosed(search) && search.isOwn)
    .sort((left, right) => right.updatedAt.localeCompare(left.updatedAt))
    .slice(0, max)

/**
 * L'accueil montre DEUX rangées pleines, quelle que soit la largeur.
 *
 * Un nombre fixe laisserait une rangée bancale : quatre cartes sur une grille à trois colonnes
 * donnent une rangée pleine et une carte orpheline entre deux cases vides. C'est donc la LARGEUR
 * qui commande le nombre, et l'invariant tenu est celui qu'on veut — jamais de trou, jamais de
 * défilement sur cet écran.
 */
export const HOME_ROWS = 2

/**
 * Les colonnes de la grille de l'accueil, palier par palier.
 *
 * Ces valeurs DOIVENT suivre celles du gabarit — `sm:grid-cols-2 xwide:grid-cols-3` — sans quoi le
 * nombre de cartes et la grille basculeraient à des largeurs différentes, et la dernière rangée se
 * casserait au premier ajustement du palier.
 */
export const HOME_COLUMNS = { base: 1, sm: 2, xwide: 3 } as const

/** Combien de cartes tiennent en deux rangées, pour un nombre de colonnes donné. */
export const homeCapacity = (columns: number): number => Math.max(0, columns) * HOME_ROWS

/**
 * Les recherches dont l'accueil précharge les fiches : celles de ses cartes, nommées par l'écran.
 *
 * La capacité prise est celle de la grille la PLUS LARGE — la largeur n'est pas connue au moment du
 * chargement, et précharger pour un écran étroit laisserait les deux dernières cartes sans fiches
 * sur un grand écran, ce qui est le cas fréquent.
 *
 * Le tri des identifiants n'est pas cosmétique : deux demandes du même lot doivent produire la même
 * adresse, sans quoi rien ne permet de reconnaître un appel déjà parti.
 */
export const preloadedSearchIds = (searches: readonly StoredSearch[]): string[] =>
  recentToResume(searches, homeCapacity(HOME_COLUMNS.xwide))
    .map(({ id }) => id)
    .sort()
