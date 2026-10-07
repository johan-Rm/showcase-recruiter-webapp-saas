import type { CandidateCard } from '#shared/types/candidate-card'

/**
 * Un état porté par un profil : ce qu'il EST, jamais ce qu'il vaut.
 *
 * `tint` nomme une variable CSS et non une couleur : la valeur vit dans `themes.css`, seul endroit
 * où une teinte du kit doit exister. Le composant applique, il ne choisit pas.
 */
export interface ProfileState {
  key: string
  labelKey: string
  icon: string
  tint: string
}

/**
 * Les sept états, DANS L'ORDRE où ils s'affichent — et cet ordre est fixe.
 *
 * Un ordre qui suivrait les états présents ferait sauter la lecture d'une carte à l'autre : sur un
 * écran où l'on tranche cent profils, la place d'un signe compte autant que le signe.
 *
 * L'ordre dit aussi une hiérarchie. Ce que showcase-recruiter-webapp-saas GARANTIT vient d'abord (rencontré), puis ce que
 * le recruteur a DÉCIDÉ (favori), puis ce qui est vrai aujourd'hui et ne le sera peut-être plus
 * demain (disponible, nouveau, au Maroc).
 *
 * LES DEUX DERNIERS NE PARLENT PAS DU PROFIL, ils parlent du PARCOURS : vu, écarté. C'est pourquoi
 * ils ferment la marche, et pourquoi ils ne prennent aucune des quatre teintes d'état. Ils sont
 * aussi les seuls à être PERSONNELS — deux membres d'une même entreprise voient les mêmes favoris,
 * pas le même avancement.
 */
const CATALOGUE: readonly (ProfileState & { holds: (profile: CandidateCard) => boolean })[] = [
  {
    key: 'met',
    labelKey: 'search.badge.met',
    icon: 'i-lucide-handshake',
    tint: '--state-met',
    holds: (profile) => profile.isStar,
  },
  {
    // Le seul état que le recruteur PRODUIT, et le seul qui porte la signature de la marque.
    key: 'favorite',
    labelKey: 'search.badge.favorite',
    icon: 'i-lucide-star',
    tint: '--accent',
    holds: (profile) => true === profile.isFavorite,
  },
  {
    key: 'available',
    labelKey: 'search.badge.available',
    icon: 'i-lucide-clock',
    tint: '--state-available',
    // La seule condition DÉRIVÉE du lot : le serveur envoie une disponibilité, pas un booléen.
    holds: (profile) => 'IMMEDIATE' === profile.availability,
  },
  {
    key: 'new',
    labelKey: 'search.badge.new',
    icon: 'i-lucide-sparkles',
    tint: '--state-new',
    holds: (profile) => true === profile.isNew,
  },
  {
    key: 'morocco',
    labelKey: 'search.badge.inMorocco',
    icon: 'i-lucide-map-pin',
    tint: '--state-morocco',
    holds: (profile) => profile.isInMorocco,
  },
  // GRIS, et c'est ce qui le range dans l'autre famille : les cinq du dessus disent ce qu'un profil
  // est, celui-ci dit ce que j'en ai fait. Une sixième teinte le mettrait au même rang qu'un
  // attribut du candidat.
  // Le MÊME ŒIL que le décompte « Vus » du panneau de gauche : les deux nomment la même chose, à
  // deux échelles — un profil ici, la recherche entière là-bas.
  {
    key: 'seen',
    labelKey: 'search.badge.seen',
    icon: 'i-lucide-eye',
    tint: '--muted',
    holds: (profile) => true === profile.isSeen,
  },
  // Il reprend la teinte du décompte « Écartés », dans le panneau de gauche — même notion, même
  // couleur, à deux échelles.
  // ⚠️ IL N'A AUCUN ÉCRAN OÙ SE MONTRER aujourd'hui : écarter sort le profil de la pile, le tiroir
  // des écartés liste des noms et non des fiches, et la Table ne lit du catalogue que « Rencontré ».
  // Il existe pour que la réponse « où en suis-je » soit entière, et il s'affichera tout seul le
  // jour où un écran montrera un profil écarté.
  {
    key: 'dismissed',
    labelKey: 'search.badge.dismissed',
    icon: 'i-lucide-eye-off',
    tint: '--verdict-missed',
    holds: (profile) => true === profile.isDismissed,
  },
]

/**
 * Les états d'un profil — la SOURCE UNIQUE des trois surfaces qui le montrent.
 *
 * Elle existe parce qu'elles le décrivaient chacune à sa façon : la carte de parcours affichait cinq
 * badges écrits en dur, la Table en réaffichait un seul à sa main, et le panneau de profil aucun. Un
 * même profil, trois vocabulaires — et le recruteur relisait l'écran à chaque bascule.
 *
 * Un état nomme un attribut ACQUIS, jamais son absence : « Non rencontré » n'existe pas, et la liste
 * revient vide quand le profil ne porte rien. C'est au conteneur de disparaître, pas au badge de
 * dire non.
 *
 * ⚠️ LA COMPARAISON EST STRICTE, et pas seulement pour la forme : hors d'une recherche, l'avancement
 * et le favori valent `null` — pas `false`. Une simple véracité confondrait « je n'ai pas vu ce
 * profil » avec « la question ne se pose pas ».
 */
export const statesOf = (profile: CandidateCard): ProfileState[] =>
  CATALOGUE.filter((state) => state.holds(profile)).map(({ key, labelKey, icon, tint }) => ({
    key,
    labelKey,
    icon,
    tint,
  }))

/** Retenu dans la recherche : la carte s'en borde, et c'est le seul état qui déborde du badge. */
export const isRetained = (profile: CandidateCard): boolean => true === profile.isFavorite
