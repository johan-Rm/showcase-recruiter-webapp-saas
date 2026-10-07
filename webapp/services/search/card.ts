import { criterionText, displayName } from './criteria'

import type { Translate } from './criteria'
import type { SearchCriterion } from '../../shared/types/search'

/**
 * Ce que le rail d'une carte de recherche a besoin de savoir.
 *
 * Cette couche est PURE : le composant reçoit une recherche déjà chargée et lit ce que ces
 * fonctions en tirent — il ne construit aucune donnée dérivée lui-même
 * (cf. `base/app/components/README.md`, « le composant reste passif »).
 */

/** La recherche telle que le rail la lit : ni store, ni réactivité, juste des faits. */
export interface RailSource {
  name: string
  criteria: readonly SearchCriterion[]
  isOwn: boolean
  newCount: number
  resultCount: number
  seenCount: number
  favoriteCount: number
  /** Optionnel : une valeur nulle n'est pas sérialisée par l'API, la clé est alors absente. */
  closedAt?: string | null
}

export interface SearchRail {
  /** Le grand nombre : la taille du jeu de résultats. Toujours présent, y compris à zéro. */
  count: number
  /**
   * Ce qui a rejoint le jeu depuis la dernière visite du lecteur, **zéro compris**.
   *
   * ZÉRO NE SE REMPLACE PAS PAR `null` : le rail n'afficherait alors rien, et rien du tout se lit
   * « la carte n'a pas fini de charger », pas « j'ai regardé, il n'y a rien » — et
   * c'est justement l'information qu'on vient chercher en survolant sa liste. **Aucun nouveau est
   * une réponse**, elle occupe sa place.
   */
  newCount: number
  /**
   * Où en est le LECTEUR dans cette pile — `null` sur la recherche d'un collègue.
   *
   * L'avancement est personnel là où les retenus appartiennent à la recherche : les poser côte à
   * côte sur la carte d'un autre ferait lire deux sujets dans une seule ligne.
   */
  progress: { seen: number; ratio: number } | null
  /** Ce que la recherche a produit, tous lecteurs confondus. */
  retained: number
  isClosed: boolean
}

/**
 * Le rail d'une recherche.
 *
 * `ratio` est borné à 1 par prudence et non par nécessité : l'API borne déjà `seenCount` au jeu de
 * résultats courant, mais une barre qui dépasserait sa piste serait un défaut
 * visible, et le coût du garde-fou est nul.
 */
export const railOf = (search: RailSource): SearchRail => ({
  count: search.resultCount,
  newCount: search.newCount,
  progress: search.isOwn
    ? {
        seen: search.seenCount,
        ratio: 0 < search.resultCount ? Math.min(1, search.seenCount / search.resultCount) : 0,
      }
    : null,
  retained: search.favoriteCount,
  isClosed: Boolean(search.closedAt),
})

/**
 * Les critères à montrer sous le titre, et combien restent en dehors.
 *
 * Quand le nom est AUTOMATIQUE, il est déjà composé des critères : les réafficher tous ferait dire
 * deux fois la même chose, en moins lisible. On n'expose donc que ce que le nom ne dit pas — la
 * comparaison porte sur le texte rendu, seul niveau où « front office » dans le titre et l'étiquette
 * « front office » sont reconnaissables comme un doublon.
 *
 * Sur un nom saisi à la main, rien n'est retiré : l'auteur a nommé sa recherche comme il l'entend,
 * et le nom ne renseigne plus sur les critères.
 */
export const criteriaToShow = (
  search: { name: string; criteria: readonly SearchCriterion[] },
  translate: Translate,
  max: number,
): { shown: SearchCriterion[]; hidden: number; isEmpty: boolean } => {
  const name = displayName(search, translate).toLocaleLowerCase('fr')
  const isAutoNamed = '' === search.name.trim()

  const candidates = isAutoNamed
    ? search.criteria.filter(
        (c) => !name.includes(criterionText(c, translate).toLocaleLowerCase('fr')),
      )
    : [...search.criteria]

  const shown = candidates.slice(0, max)
  const hidden = Math.max(0, candidates.length - max)

  /* La rangée est vide DEUX fois plus souvent qu'on ne le croit : le titre dit déjà le métier, le
     premier mot-clé, l'expérience et les études, et une recherche qui n'a que ça n'a plus rien à
     afficher en dessous. C'est l'ÉCRAN qui doit l'expliquer, pas la donnée — d'où un
     drapeau sur ce que le lecteur verra, et non sur ce que la recherche contient. */
  return { shown, hidden, isEmpty: 0 === shown.length && 0 === hidden }
}

/**
 * UNE RECHERCHE EST-ELLE TROP LARGE POUR SE TRIER ?
 *
 * Cent profils est un CONSEIL et non une limite : une recherche qui en ramène 352 se parcourt
 * entièrement. Mais trier 352 profils un par un est un travail que personne ne mène jusqu'au bout,
 * et rien ne le disait au recruteur — l'écran de supervision, lui, les liste déjà.
 *
 * Le seuil est un ARGUMENT et non une constante d'ici : le même nombre gouverne le conseil au
 * recruteur et l'inventaire de supervision, et il vient du serveur. Deux réglages finiraient par
 * signaler à l'un ce qu'ils taisent à l'autre.
 *
 * Un seuil à zéro — la configuration n'est pas encore là — ne conseille RIEN : mieux vaut se taire
 * qu'avertir sur un nombre inventé.
 *
 * Une recherche close ne se travaille plus : lui conseiller de resserrer ses critères propose un
 * geste qui n'a plus d'objet.
 */
export const isBroadSearch = (
  search: { resultCount: number; closedAt?: string | null },
  threshold: number,
): boolean => 0 < threshold && !search.closedAt && search.resultCount > threshold

/**
 * L'état d'une recherche face au seuil, TROIS valeurs et non deux.
 *
 * `unknown` n'est pas un détail de chargement : tant que le seuil servi n'est pas là, on ne sait
 * pas, et ne pas savoir n'est pas la même chose que savoir que non. C'est ce qui empêche une
 * recherche déjà annoncée de l'être une seconde fois au rechargement de la page, le temps que la
 * configuration arrive.
 */
export type BroadState = 'broad' | 'narrow' | 'unknown'

/**
 * FAUT-IL ANNONCER QU'UNE RECHERCHE EST LARGE, ET QUE RETENIR ENSUITE ?
 *
 * Le conseil se dit UNE FOIS par recherche : répété à chaque ouverture, il devient du bruit et se
 * fait ignorer précisément par ceux qu'il vise — or un recruteur ouvre et referme la même recherche
 * plusieurs fois par jour.
 *
 * MAIS UN NOUVEAU FRANCHISSEMENT SE RÉANNONCE. Une recherche repassée sous le seuil puis au-dessus
 * — le recruteur a resserré ses critères, puis les a rouverts — est un fait nouveau. D'où la mémoire
 * qui OUBLIE sur `narrow` : sans cet oubli, un élargissement provoqué à l'instant resterait muet.
 *
 * `unknown` NE TOUCHE À RIEN, ni pour annoncer ni pour oublier. C'est la seule valeur des trois qui
 * laisse la mémoire exactement dans l'état où elle était.
 *
 * Fonction PURE et mémoire passée en argument : ce qui la stocke — la session du navigateur — n'est
 * pas son affaire, et c'est ce qui la rend testable sans navigateur.
 */
export const announceBroad = (
  announced: readonly string[],
  searchId: string,
  state: BroadState,
): { announce: boolean; announced: readonly string[] } => {
  if ('unknown' === state) return { announce: false, announced }

  if ('narrow' === state) {
    return { announce: false, announced: announced.filter((id) => id !== searchId) }
  }

  if (announced.includes(searchId)) return { announce: false, announced }

  return { announce: true, announced: [...announced, searchId] }
}

/**
 * Les gestes qu'une carte peut proposer.
 *
 * `share` et `unshare` sont DEUX entrées et non une bascule anonyme : le libellé doit dire ce que
 * le clic va faire, pas l'état courant.
 */
export type SearchAction =
  'modify' | 'rename' | 'duplicate' | 'share' | 'unshare' | 'close' | 'reopen' | 'delete'

/**
 * Ce que la carte propose, dans l'ordre où le menu l'affiche.
 *
 * L'écran ne propose JAMAIS ce que le serveur refuserait : l'écriture est réservée à l'auteur, et
 * une recherche close n'accepte que d'être rouverte. Proposer puis échouer apprend à l'utilisateur
 * que l'interface ment.
 *
 * Sur la recherche d'un collègue, la liste se réduit à un seul geste — l'écran le rend alors en
 * LIEN DIRECT, pas en menu : un menu à une entrée est un lien qu'on a caché derrière un clic.
 */
export const actionsFor = (search: {
  isOwn: boolean
  isShared: boolean
  closedAt?: string | null
}): SearchAction[] => {
  if (!search.isOwn) {
    return ['duplicate']
  }

  if (search.closedAt) {
    // Rouvrir d'abord : c'est le seul geste qui rend la recherche modifiable à nouveau. Supprimer
    // reste permis — supprimer n'est pas modifier.
    return ['reopen', 'delete']
  }

  // MODIFIER EN TÊTE : c'est le geste qui touche à ce que la recherche cherche, quand renommer ne
  // touche qu'à son étiquette. Il suit les mêmes interdits que les autres écritures — pas sur la
  // recherche d'un collègue, pas sur une recherche close, que les deux branches ci-dessus écartent
  // déjà.
  return ['modify', 'rename', 'duplicate', search.isShared ? 'unshare' : 'share', 'close', 'delete']
}
