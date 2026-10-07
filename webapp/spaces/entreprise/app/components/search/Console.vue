<script setup lang="ts">
// LA CONSOLE de la recherche — où j'en suis dans le tri, et la sortie vers l'autre outil. Ce que le
// profil vaut face aux critères se lit DANS sa fiche, contre le profil dont ce verdict parle.
//
// UN SEUL CONTENU, DONC PAS DE BARRE D'ONGLETS : un onglet seul n'est pas un choix. Ce qu'elle
// porte — l'avancement, l'affinage du parcours, la sortie — se lit d'une traite, du haut vers le
// bas, sans qu'aucune part ne se cache derrière une autre.
//
// Elle porte l'aplat et les coins — c'est elle qui est POSÉE dans la grille de la page — et relaie
// les intentions de la colonne. La console consulte et resserre ; elle ne retouche pas la
// recherche, dont les critères se changent sur l'écran de modification.
import type { RefineForm } from '#services/search/refine'
import type { CandidateCard } from '#shared/types/candidate-card'

type Props = {
  seenCount: number
  dismissedCount: number
  /** Les retenus : la console n'en garde que le COMPTE, la liste vit dans la Table. */
  favoriteCount: number
  tableTo: string
  cards: readonly CandidateCard[]
  today: Date
  refinedCount: number
}

defineProps<Props>()

const emit = defineEmits<{ review: [] }>()

const refine = defineModel<RefineForm>('refine', { required: true })
</script>

<template>
  <!-- La surface et les coins appartiennent à la console : c'est elle qui est POSÉE dans la grille,
       et son contenu se pose sur cet aplat sans le redéclarer. -->
  <div class="search-console flex h-full min-h-0 flex-col overflow-hidden rounded-lg bg-surface">
    <!-- `min-h-0` de bout en bout : le `<aside>` de la page est un élément de grille, donc à
         `min-height: auto` — sans lui, il grandit à son contenu et tout ce qui est en dessous
         calcule sa hauteur sur une valeur déjà débordée. C'est ce qui empêche la liste de critères
         de défiler et lui fait POUSSER la colonne. -->
    <SearchControlPanel
      v-model:refine="refine"
      :seen-count="seenCount"
      :dismissed-count="dismissedCount"
      :favorite-count="favoriteCount"
      :table-to="tableTo"
      :cards="cards"
      :today="today"
      :refined-count="refinedCount"
      @review="emit('review')"
    />
  </div>
</template>
