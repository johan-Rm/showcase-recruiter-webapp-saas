import type { CandidateCard } from '../../shared/types/candidate-card'

/**
 * Les deux ordres de parcours de la Visionneuse.
 *
 * `relevance` n'est PAS un tri à calculer : c'est l'ordre que le serveur rend déjà — nombre de
 * critères satisfaits d'abord, profils rencontrés en départage, entrées récentes ensuite. Le front
 * ne reçoit même pas de quoi le recalculer. Le nommer était donc tout le travail : le recruteur
 * ignorait que sa pile était classée, donc ce qu'il perdait en la réordonnant.
 *
 * `unseen` est le DÉFAUT, et c'est lui qui règle le retour sur une recherche entamée : si les non
 * vus sont devant, rouvrir une recherche signifie arriver sur ce qui reste à faire. Aucune position
 * n'est mémorisée — rien à stocker, et aucun état qui survive à l'utilisateur pour le surprendre
 * trois jours plus tard.
 */
export const STACK_ORDERS = ['unseen', 'relevance'] as const

export type StackOrder = (typeof STACK_ORDERS)[number]

/**
 * La pile, NON VUS D'ABORD.
 *
 * Elle REGROUPE, elle ne mélange pas : à l'intérieur des non vus comme des vus, l'ordre reçu du
 * serveur est conservé tel quel. C'est ce qui rend cet ordre sûr — un tri qui perdrait la pertinence
 * à l'intérieur des groupes serait un tri qui dégrade.
 *
 * Les vus passent derrière, ils ne disparaissent pas : se raviser sur un profil qu'on vient de voir
 * est fréquent, et le masquer obligerait à changer d'ordre pour y revenir.
 */
const unseenFirst = (cards: readonly CandidateCard[]): CandidateCard[] => [
  ...cards.filter((candidate) => !candidate.isSeen),
  ...cards.filter((candidate) => candidate.isSeen),
]

/** La pile dans l'ordre demandé — « pertinence » rend l'ordre reçu, intact. */
export const orderStack = (cards: readonly CandidateCard[], order: StackOrder): CandidateCard[] =>
  'unseen' === order ? unseenFirst(cards) : [...cards]

/**
 * LA RECHERCHE RAMÈNE-T-ELLE PLUS QUE CE QU'ELLE DONNE À TRAVAILLER ?
 *
 * Le serveur borne le jeu servi et continue d'annoncer le nombre réel de correspondances. Quand les
 * deux diffèrent, l'écran doit le dire — sans quoi le recruteur croit le vivier plus pauvre qu'il
 * n'est, et ne pense pas à resserrer une recherche trop large.
 *
 * Quand ils se confondent — le cas ordinaire —, la mention se tait : « 32 sur 32 » n'apprend rien et
 * encombre un rang qu'on lit d'un coup d'œil.
 *
 * Ici plutôt que dans chaque vue : deux surfaces l'affichent — le rang de la Visionneuse et l'onglet
 * de la Table — et deux copies de la comparaison finiraient par diverger d'un signe.
 */
export const overflowsLoaded = (loaded: number, matchCount: number): boolean => matchCount > loaded

/**
 * La pile rangée selon un ordre **figé**, exprimé en identifiants.
 *
 * C'est ce qui empêche la pile de se réorganiser SOUS LE CURSEUR. Sans ce gel, marquer un profil
 * comme vu le ferait basculer derrière à l'instant même où on le lit, et la carte suivante sauterait
 * sous les yeux à chaque franchissement. Le regroupement se calcule donc à l'ouverture et sur
 * changement d'ordre, jamais pendant le défilement.
 *
 * Une réconciliation suffit : un profil ÉCARTÉ quitte la pile, son identifiant est simplement
 * ignoré. Les cartes que le gel ne connaît pas ferment la marche — un cas rare, la recherche
 * arrivant d'un seul appel : il faut qu'une carte apparaisse hors du jeu servi.
 */
export const applyFrozenOrder = (
  cards: readonly CandidateCard[],
  frozenIds: readonly string[],
): CandidateCard[] => {
  const byId = new Map(cards.map((candidate) => [candidate.id, candidate]))
  const placed = frozenIds
    .map((id) => byId.get(id))
    .filter((candidate): candidate is CandidateCard => undefined !== candidate)

  const known = new Set(frozenIds)

  return [...placed, ...cards.filter((candidate) => !known.has(candidate.id))]
}
