<script setup lang="ts">
// LA CONSOLE de la recherche — où j'en suis, et ce que le profil affiché vaut face à mes critères.
//
// UN SEUL CONTENU, DONC PAS DE BARRE D'ONGLETS : un onglet seul n'est pas un choix. Les deux
// entrées vers les filtres vivent dans la barre haute, où elles ne disputent pas la largeur d'une
// colonne de deux cent quatre-vingts pixels à ce qui se lit à chaque profil.
//
// Elle porte l'aplat et les coins — c'est elle qui est POSÉE dans la grille de la page — et relaie
// la seule intention de la colonne : consulter les écartés. La console consulte, elle ne retouche
// pas la recherche.
import type { CriterionOutcome } from '#services/search/criteria'
import type { CandidateCard } from '#shared/types/candidate-card'

type Props = {
  total: number
  seenCount: number
  loadedCount: number
  dismissedCount: number
  outcomes: CriterionOutcome[]
  /** Les retenus : la console n'en garde que le COMPTE, la liste vit dans la Table. */
  favorites: CandidateCard[]
  tableTo: string
}

defineProps<Props>()

const emit = defineEmits<{ review: [] }>()
</script>

<template>
  <!-- La surface et les coins appartiennent à la console : c'est elle qui est POSÉE dans la grille,
       et son contenu se pose sur cet aplat sans le redéclarer. -->
  <div class="search-v1-console flex h-full min-h-0 flex-col overflow-hidden rounded-lg bg-surface">
    <!-- `min-h-0` de bout en bout : le `<aside>` de la page est un élément de grille, donc à
         `min-height: auto` — sans lui, il grandit à son contenu et tout ce qui est en dessous
         calcule sa hauteur sur une valeur déjà débordée. C'est ce qui empêche la liste de critères
         de défiler et lui fait POUSSER la colonne. -->
    <SearchV1ControlPanel
      :total="total"
      :seen-count="seenCount"
      :loaded-count="loadedCount"
      :dismissed-count="dismissedCount"
      :outcomes="outcomes"
      :favorite-count="favorites.length"
      :table-to="tableTo"
      @review="emit('review')"
    />
  </div>
</template>
