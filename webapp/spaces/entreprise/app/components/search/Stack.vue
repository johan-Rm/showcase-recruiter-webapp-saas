<script setup lang="ts">
// LA PILE : une card par profil, `scroll-snap` vertical — une card = un plein écran. Le défilement
// de la pile est le SEUL de l'écran, aucune card ne défile en interne.
//
// Elle porte ses quatre états — chargement, erreur, aucun critère, jeu vide — parce qu'ils sont
// quatre façons de ne pas montrer de pile : les laisser dans la page, c'était y laisser la moitié
// du bloc.
//
// Sa RÉGION appartient à la page, qui la place et la nomme. La racine ci-dessous ne dit que deux
// choses, et les deux la regardent : elle remplit ce qu'on lui donne, et elle est la colonne flex
// sans laquelle les quatre états ne peuvent pas se centrer.
import type { CandidateCard } from '#shared/types/candidate-card'
import type { RevealType } from '#shared/types/reveal'
import type { SearchCriterion } from '#shared/types/search'

type Props = {
  candidates: CandidateCard[]
  /**
   * Les critères de la recherche, que chaque card confronte au profil qu'elle porte.
   *
   * Ils traversent la pile sans qu'elle s'en serve, à une exception près : leur ABSENCE est un de ses
   * quatre états — sans critère il n'y a pas de résultat, le vivier entier n'est pas une recherche.
   */
  criteria: readonly SearchCriterion[]
  editable: boolean
  pending: boolean
  error: boolean
  /**
   * Un affinage est posé sur le parcours.
   *
   * La pile ne s'en sert que pour DIRE JUSTE quand elle est vide : sans lui, un jeu vidé par un
   * affinage conseille d'élargir des critères qui n'y sont pour rien, et le geste qui sort de
   * l'impasse n'est nulle part.
   */
  refined?: boolean
}

defineProps<Props>()

const emit = defineEmits<{
  toggleFavorite: [candidate: CandidateCard]
  dismiss: [candidate: CandidateCard]
  seen: [candidate: CandidateCard]
  reveal: [candidate: CandidateCard, type: RevealType]
  /** Le rang de la card qu'on regarde — la colonne en tire la position et ce qui manque. */
  visible: [index: number]
  clearRefine: []
}>()

// L'élément défilant reste ICI : c'est lui que `scroll-snap` gouverne, et lui qu'il faut interroger
// pour savoir quelle card occupe l'écran. La page n'a pas à connaître ce détail de rendu.
const stack = ref<HTMLElement | null>(null)
const { index } = useVisibleProfile(stack)

watch(index, (value) => emit('visible', value), { immediate: true })

/**
 * REPRENDRE au rang demandé.
 *
 * Exposé plutôt qu'émis : c'est un geste que la page DÉCLENCHE, et il a besoin de l'élément défilant
 * que ce composant est seul à détenir. On défile jusqu'à l'enfant de ce rang plutôt que d'ancrer par
 * identifiant — c'est le même `scroll-snap` qui gouverne la lecture, et l'y ramener suffit à faire
 * de cette carte la carte courante.
 */
const scrollTo = (position: number): void => {
  stack.value?.children.item(position)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

defineExpose({ scrollTo })
</script>

<template>
  <div class="search-stack flex h-full flex-col">
    <SearchState v-if="pending" tone="loading" :title="$t('search.loading')" />

    <SearchState
      v-else-if="error"
      tone="error"
      :title="$t('search.error')"
      :hint="$t('search.state.errorHint')"
    />

    <SearchState
      v-else-if="0 === criteria.length"
      icon="i-lucide-list-filter"
      :title="$t('search.viewer.noCriteria')"
      :hint="$t('search.state.noCriteriaHint')"
    />

    <!-- VIDÉ PAR L'AFFINAGE, ET NON PAR LA RECHERCHE : le jeu a ramené des profils, c'est l'angle de
         lecture qui n'en laisse aucun. Conseiller d'élargir les critères accuserait ici la seule
         chose qui n'y est pour rien, et le geste qui sort de l'impasse est à portée. -->
    <SearchState
      v-else-if="0 === candidates.length && refined"
      icon="i-lucide-filter-x"
      :title="$t('search.refine.emptyTitle')"
      :hint="$t('search.refine.emptyHint')"
    >
      <UButton size="sm" variant="outline" color="neutral" @click="emit('clearRefine')">
        {{ $t('search.refine.clear') }}
      </UButton>
    </SearchState>

    <SearchState
      v-else-if="0 === candidates.length"
      :title="$t('search.viewer.empty')"
      :hint="$t('search.state.emptyHint')"
    />

    <!-- `role="list"` : c'est une liste de profils, et rien ne le disait — un lecteur d'écran
         annonçait des articles isolés, sans jamais dire combien ni où l'on en est. La région qui
         l'entoure porte déjà le nom, la liste n'en reprend pas un second.

         `relative` ne positionne rien ici : il fait de ce conteneur le BLOC CONTENEUR de ce qu'il
         porte. Les cards contiennent des `sr-only`, que Tailwind rend en `position: absolute` ;
         sans ancêtre positionné, ils se rattachaient au document — donc `overflow-y-auto` ne les
         rognait pas, et chacun se posait à sa profondeur de défilement. Vingt-sept profils
         étiraient le document sur vingt mille pixels, et la coque `h-dvh` sortait du champ.

         SANS ÉCART ENTRE LES CARDS : elles n'ont plus d'aplat à elles, et l'écart montrerait donc
         celui du bloc — une bande vide sous laquelle rien ne se passe. C'est le filet en haut de
         chaque card qui dit où un profil finit. -->
    <div
      v-else
      ref="stack"
      role="list"
      class="relative flex min-h-0 flex-1 snap-y snap-mandatory flex-col overflow-y-auto"
    >
      <SearchProfileCard
        v-for="candidate in candidates"
        :key="candidate.id"
        role="listitem"
        :candidate="candidate"
        :criteria="criteria"
        :editable="editable"
        @toggle-favorite="emit('toggleFavorite', $event)"
        @dismiss="emit('dismiss', candidate)"
        @seen="emit('seen', candidate)"
        @reveal="(profile, type) => emit('reveal', profile, type)"
      />
    </div>
  </div>
</template>
