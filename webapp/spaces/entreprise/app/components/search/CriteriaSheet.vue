<script setup lang="ts">
// LA FEUILLE DES CRITÈRES — le détail « ce profil face à vos critères », rangé derrière un geste
// sous 1280 px.
//
// ELLE MONTE PAR LE BAS, comme le tiroir du profil complet : on est par-dessus l'écran de travail,
// on ne l'a pas quitté. Le kit rend déjà la poignée, le glissement, `Échap`, la page inerte et le
// piège à focus — d'où le composant du kit plutôt qu'une fenêtre écrite à la main.
//
// ELLE NE PORTE AUCUN GESTE. Retenir et écarter restent en pied de fiche, visibles en permanence :
// les ranger ici, c'est cacher la raison d'être de l'écran derrière un clic. Ici on LIT pourquoi le
// profil est là ; on tranche dehors.
//
// LA MÊME LISTE QUE LA COLONNE, au composant près. Ce qui change entre les deux emplacements n'est
// pas ce qui est montré mais l'endroit d'où on le regarde.
import { confrontCriteria } from '#services/search/criteria'

import type { SearchCriterion } from '#shared/types/search'

type Props = {
  criteria: readonly SearchCriterion[]
}

const { criteria } = defineProps<Props>()

const { isOpen, candidate, close } = useCriteriaSheet()

const outcomes = computed(() =>
  candidate.value ? confrontCriteria(criteria, candidate.value) : [],
)
</script>

<template>
  <!-- HAUTEUR FIXE et non hauteur du contenu : une feuille qui changerait de taille selon le nombre
       de critères rendrait le geste imprévisible d'un profil à l'autre. Le corps défile.
       Le titre du kit est conservé — c'est lui qui rattache le dialogue à son nom pour les lecteurs
       d'écran ; l'écraser retirerait ce lien. -->
  <UDrawer
    v-model:open="isOpen"
    class="search-criteria-sheet"
    :title="$t('search.viewer.panel.matches')"
    :description="$t('search.viewer.panel.matches')"
    :ui="{
      description: 'sr-only',
      content: 'h-[calc(100dvh-8rem)]',
      container: 'overflow-hidden gap-block',
      body: 'min-h-0 overflow-y-auto',
    }"
    @update:open="(opened: boolean) => !opened && close()"
  >
    <template #body>
      <SearchMatchList :outcomes="outcomes" />
    </template>
  </UDrawer>
</template>
