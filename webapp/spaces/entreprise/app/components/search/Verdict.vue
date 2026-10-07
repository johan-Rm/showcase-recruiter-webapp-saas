<script setup lang="ts">
// LE VERDICT SUR UN PROFIL : ce que la recherche en pense, les deux gestes qui tranchent, et le
// détail critère par critère.
//
// IL NE DIT QUE LES CRITÈRES. Ce que le profil EST — vu, retenu, nouveau — a rejoint le pied de sa
// fiche : c'est un attribut du candidat, pas un verdict sur des critères, et le loger ici obligeait
// à réserver en permanence une hauteur en bas de colonne pour qu'elle ne saute pas d'un profil au
// suivant. La colonne paie ainsi pour ce qu'elle juge, et pour rien d'autre.
//
// L'ORDRE EST LE PROPOS DE CE BLOC. Il commence par la conclusion — combien de critères sur combien,
// et comment ça s'appelle —, puis vient le geste, puis seulement le détail. À l'envers, il fallait
// lire six lignes et faire le compte de tête sur chaque profil d'une pile qui en compte des
// centaines.
//
// Un seul composant pour DEUX EMPLACEMENTS, et c'est voulu : colonne pleine hauteur à côté de la
// pile sur grand écran, barre en pied de fiche en dessous. Le même contenu, la même règle, un seul
// endroit à corriger. Ce qui change entre les deux n'est pas ce qu'il montre mais CE QU'IL DÉSIGNE :
// posé dans une fiche il juge celle qui le porte, posé à côté de la pile il juge celle qu'on
// regarde.
//
// EN PIED DE FICHE, IL SE REPLIE (`compact`), ET JAMAIS DÉPLIÉ. Déplié il pèse plus de trois cents
// pixels, aucune contrainte de hauteur ne tient plus et la fiche déborde de son écran. Replié, il
// garde ce qu'on répète cent fois — la note et les deux gestes, en une seule rangée qui ne défile
// pas — et range le détail derrière un geste. Rien n'est retiré, tout est rangé.
//
// IL NE NOMME PAS CE QU'IL JUGE. Le nom est déjà en tête de la fiche, à trente centimètres à gauche
// et dans un corps deux fois plus grand.
//
// Passif SUR LES DÉCISIONS : il émet ses deux intentions et n'exécute rien. Ouvrir le détail n'en
// est pas une — ça ne change aucun état du candidat, ne coûte rien, et c'est la lecture qui
// continue. Il l'ouvre donc par l'état partagé, comme la fiche ouvre le profil complet, plutôt que
// par un évènement qui traverserait la pile jusqu'à la page.
import { confrontCriteria, scoreOf } from '#services/search/criteria'

import type { CandidateCard } from '#shared/types/candidate-card'
import type { SearchCriterion } from '#shared/types/search'

type Props = {
  /** `null` tant que la pile n'a rien à montrer — le panneau se tait alors plutôt que de mentir. */
  candidate: CandidateCard | null
  criteria: readonly SearchCriterion[]
  /** Faux sur la recherche d'un collègue : on la lit, on n'y tranche rien. */
  editable?: boolean
  /** Replié en barre de pied de fiche, sous 1280 px : la note, les gestes, et le détail rangé. */
  compact?: boolean
}

const { candidate, criteria, editable = true, compact = false } = defineProps<Props>()

const emit = defineEmits<{
  toggleFavorite: [candidate: CandidateCard]
  dismiss: [candidate: CandidateCard]
}>()

const outcomes = computed(() => (candidate ? confrontCriteria(criteria, candidate) : []))
const score = computed(() => scoreOf(outcomes.value))

const { open: openCriteria } = useCriteriaSheet()
</script>

<template>
  <!-- REPLIÉ, IL S'EMPILE SOUS 640 px. En une seule rangée, la note et les deux gestes se disputent
       moins de quatre cents pixels : le palier passe alors SOUS le bouton vert — constaté en capture
       à 390 px. Sur deux rangées, chacun garde sa largeur et rien n'est retiré. L'écart se resserre
       aussi : une barre n'est pas une colonne, ses blocs se lisent ensemble.
       RIEN À DIRE, RIEN À MONTRER : sans note à afficher et sans geste à poser — une recherche sans
       critère, lue chez un collègue —, le bloc disparaît. Réduit à ses marges, il laisserait une
       bande vide en pied de fiche, et une bande vide se lit comme une panne. -->
  <div
    v-if="candidate && (score || editable)"
    class="search-verdict flex min-h-0"
    :class="
      compact
        ? 'items-center gap-element max-sm:flex-col max-sm:items-stretch'
        : 'flex-col gap-block'
    "
  >
    <!-- ① LA CONCLUSION D'ABORD. Absente quand il n'y a rien à compter — sans critère, ou sans
         profil à confronter : « 0 sur 6 » se lirait comme un mauvais score là où il n'y a qu'une
         absence. Replié, la conclusion EST le geste d'ouverture du détail : on tape là où on vient
         de lire le compte, et non sur un bouton « voir plus » posé à côté.
         PAS D'`aria-label` : il remplacerait le nom que la note se donne déjà — le palier lu, et le
         compte en toutes lettres pour les lecteurs d'écran. Le chevron dit le reste, à l'œil. -->
    <template v-if="score">
      <UButton
        v-if="compact"
        color="neutral"
        variant="ghost"
        trailing-icon="i-lucide-chevron-up"
        class="min-w-0 flex-1 justify-start"
        :ui="{ trailingIcon: 'ms-auto' }"
        @click="openCriteria(candidate)"
      >
        <SearchScore :score="score" />
      </UButton>

      <SearchScore v-else :score="score" />
    </template>

    <!-- ② LES DEUX GESTES, ce qu'on répète cent fois. EN HAUT et non en pied : une liste de longueur
         variable sous eux les ferait changer de hauteur d'un profil à l'autre. Le pied, lui,
         appartient déjà à la sortie vers la Table, dans la colonne d'en face. -->
    <div v-if="editable" class="flex flex-none items-stretch gap-1">
      <!-- RETENIR PORTE L'APLAT, et c'est le geste de cet écran : il prend toute la largeur qui
           reste, l'autre prend un carré. Deux gestes de même poids obligeraient à lire les deux
           avant de choisir, cent fois de suite.
           UNE SEULE ÉTOILE, deux états. Le contraire d'une étoile n'est pas une étoile barrée, c'est
           une étoile éteinte : un signe nomme un attribut ACQUIS, jamais son absence. L'aplat appelle
           le geste, la version douce dit qu'il est fait. -->
      <UButton
        color="primary"
        :variant="candidate.isFavorite ? 'soft' : 'solid'"
        icon="i-lucide-star"
        size="sm"
        :ui="{ leadingIcon: 'size-3' }"
        class="flex-1 justify-center"
        @click="emit('toggleFavorite', candidate)"
      >
        {{ candidate.isFavorite ? $t('search.action.retained') : $t('search.action.retain') }}
      </UButton>

      <!-- ÉCARTER sort le profil de la pile : c'est ce qui rend un tri de plusieurs centaines de
           profils fini plutôt qu'infini. Réversible depuis la colonne d'en face, sans quoi on
           n'oserait pas s'en servir.
           IL SE RÉDUIT À SON ICÔNE, et l'aplat d'à côté porte la hiérarchie : un geste majeur, un
           geste mineur. Ce que l'icône ne dit plus, l'infobulle et le nom accessible le disent — une
           icône seule n'a jamais à être devinée.
           SA COULEUR EST CELLE DES ÉCARTÉS, `--verdict-missed`, et non `--alert` : la charte réserve
           l'alerte au feedback bloquant, jamais à une donnée. Le geste devient ainsi la troisième
           échelle d'une même notion — le bouton ici, le badge sur le profil, le décompte dans le
           panneau de gauche.
           LE TRAIT RESTE DILUÉ : à pleine opacité il dispute le regard au geste vert d'à côté, et
           « Écarter » vient APRÈS « Retenir ». Sa couleur le nomme, elle ne le promeut pas. -->
      <UTooltip :text="$t('search.action.dismiss')">
        <UButton
          icon="i-lucide-eye-off"
          color="neutral"
          variant="outline"
          size="sm"
          class="text-verdict-missed ring-verdict-missed/40 hover:bg-verdict-missed/10"
          :aria-label="$t('search.action.dismiss')"
          @click="emit('dismiss', candidate)"
        />
      </UTooltip>
    </div>

    <!-- ③ LE DÉTAIL, et c'est la partie qui DÉFILE : la conclusion et les deux gestes restent en
         tête, le geste ne change jamais de place, même sur une recherche à douze critères.
         C'EST AUSSI LA FIN DE LA COLONNE. Rien ne se pose plus dessous, donc plus rien ne lui prend
         de hauteur : la liste va jusqu'au bord, et se termine sur sa note de réserve quand elle
         paraît.
         REPLIÉ, IL N'EST PAS ICI mais dans la feuille qu'ouvre la note. Le rendre puis le masquer
         laisserait sa hauteur dans le calcul de la fiche, qui est exactement ce qu'on cherche à
         retirer. -->
    <SearchMatchList v-if="!compact" :outcomes="outcomes" />
  </div>
</template>
