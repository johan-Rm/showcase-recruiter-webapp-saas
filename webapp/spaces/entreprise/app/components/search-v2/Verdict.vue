<script setup lang="ts">
// LE VERDICT SUR UN PROFIL : ce que la recherche lui trouve, ce qui lui manque, et les deux gestes
// qui tranchent.
//
// Un seul composant pour DEUX EMPLACEMENTS, et c'est voulu : colonne pleine hauteur à côté de la
// pile sur grand écran, bloc en pied de fiche en dessous. Le même contenu, la même règle, un seul
// endroit à corriger. Ce qui change entre les deux n'est pas ce qu'il montre mais CE QU'IL DÉSIGNE :
// posé dans une fiche il juge celle qui le porte, posé à côté de la pile il juge celle qu'on
// regarde.
//
// IL NOMME CE QU'IL JUGE — mais SEULEMENT quand il est détaché. Deux boutons de verdict à côté d'une
// pile qui défile n'ont pas de sujet visible : sans le nom, « Retenir » ne dit pas qui l'on retient.
// En pied de fiche la question ne se pose pas, le sujet est le bloc qui l'entoure ; l'y nommer
// écrirait le même nom deux fois dans la même carte.
//
// Passif : il émet ses deux intentions et n'exécute rien.
import { confrontCriteria } from '#services/search/criteria'
import { roleOf } from '#services/search/candidate'

import type { CandidateCard } from '#shared/types/candidate-card'
import type { SearchCriterion } from '#shared/types/search'

type Props = {
  /** `null` tant que la pile n'a rien à montrer — le panneau se tait alors plutôt que de mentir. */
  candidate: CandidateCard | null
  criteria: readonly SearchCriterion[]
  /** Faux sur la recherche d'un collègue : on la lit, on n'y tranche rien. */
  editable?: boolean
  /** Vrai quand il vit hors de la fiche : il doit alors nommer le profil sur lequel il porte. */
  detached?: boolean
}

const { candidate, criteria, editable = true, detached = false } = defineProps<Props>()

const emit = defineEmits<{
  toggleFavorite: [candidate: CandidateCard]
  dismiss: [candidate: CandidateCard]
}>()

const outcomes = computed(() => (candidate ? confrontCriteria(criteria, candidate) : []))

// Masqué, le nom se réduit à des initiales — c'est le SERVEUR qui les réduit. Le poste prend le
// relais quand il n'y a pas de nom du tout : il faut un sujet aux deux boutons, pas un tiret.
const subject = computed(() => {
  if (!candidate) return null

  const name = [candidate.givenName, candidate.familyName].filter(Boolean).join(' ')

  return name || roleOf(candidate).text
})
</script>

<template>
  <div v-if="candidate" class="search-v2-verdict flex min-h-0 flex-col gap-block">
    <!-- LE SUJET, puis LES GESTES, et la liste prend le reste. Les boutons EN HAUT et non en pied :
         ils sont ce qu'on répète cent fois, et une liste de longueur variable sous eux les ferait
         changer de hauteur d'un profil à l'autre. Le pied, lui, appartient déjà à la sortie vers la
         Table, dans la colonne d'en face. -->
    <div v-if="editable" class="flex flex-none flex-col gap-element">
      <p v-if="detached && subject" class="truncate font-display text-h3">{{ subject }}</p>

      <div class="flex items-center gap-element">
        <!-- UNE SEULE ÉTOILE, deux couleurs. Le contraire d'une étoile n'est pas une étoile barrée,
             c'est une étoile éteinte : un signe nomme un attribut ACQUIS, jamais son absence. -->
        <UButton
          :variant="candidate.isFavorite ? 'soft' : 'outline'"
          :color="candidate.isFavorite ? 'primary' : 'neutral'"
          icon="i-lucide-star"
          class="flex-1 justify-center"
          @click="emit('toggleFavorite', candidate)"
        >
          {{ candidate.isFavorite ? $t('search.action.retained') : $t('search.action.retain') }}
        </UButton>

        <!-- ÉCARTER sort le profil de la pile : c'est ce qui rend un tri de plusieurs centaines de
             profils fini plutôt qu'infini. Réversible depuis la colonne d'en face, sans quoi on
             n'oserait pas s'en servir. -->
        <UButton
          icon="i-lucide-eye-off"
          color="neutral"
          variant="ghost"
          class="flex-none"
          :aria-label="$t('search.action.dismiss')"
          @click="emit('dismiss', candidate)"
        />
      </div>
    </div>

    <SearchV2MatchList :outcomes="outcomes" />
  </div>
</template>
