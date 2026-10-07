<script setup lang="ts">
import { formatCount } from '../../utils/vivierMetrics'

// La répartition d'un manque par motif, en étiquettes sélectionnables.
//
// C'est ici que se lit le diagnostic : voir que sur 340 profils sans ancienneté, 202 ont simplement
// moins d'un an de carrière change la décision qui suit. Les effectifs affichés portent TOUJOURS le
// manque entier — le serveur ne les restreint jamais —, sinon sélectionner un motif ferait
// disparaître la comparaison qui donne son sens au chiffre.
interface Props {
  /** Code de la métrique, qui porte l'adresse de l'écran. */
  metric: string
  rows: { reason: string; count: number }[]
  /** Motif sélectionné ; chaîne vide quand la liste porte tout le manque. */
  selected: string
}

const { metric, rows, selected } = defineProps<Props>()

const { current, localePath } = useLocale()

const total = computed(() => rows.reduce((sum, row) => sum + row.count, 0))

// L'étiquette « Tous » ouvre la liste, les autres la restreignent : même forme pour les deux, un
// motif vide valant « aucun filtre ».
const chips = computed(() => [
  { reason: '', label: 'vivier.gap.all', count: total.value },
  ...rows.map((row) => ({ ...row, label: `vivier.gap.reason.${row.reason}` })),
])

const chipTo = (reason: string): string =>
  localePath(reason ? `/vivier/${metric}?reason=${reason}` : `/vivier/${metric}`)
</script>

<template>
  <!-- Une navigation : chaque étiquette est une adresse, pas un état d'interface. Le motif choisi
       se partage et survit à un rechargement. -->
  <nav
    class="vivier-gap-breakdown flex flex-wrap gap-element"
    :aria-label="$t('vivier.gap.filter')"
  >
    <NuxtLink
      v-for="chip in chips"
      :key="chip.reason"
      :to="chipTo(chip.reason)"
      :aria-current="chip.reason === selected ? 'true' : undefined"
      class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-small transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
      :class="
        chip.reason === selected
          ? 'bg-accent-soft font-semibold text-accent'
          : 'bg-surface text-muted hover:text-accent'
      "
    >
      {{ $t(chip.label) }}
      <!-- L'effectif est une donnée, pas une décoration : il reste lu, jamais masqué au lecteur
           d'écran. C'est la seule information qui distingue un motif marginal d'un motif dominant. -->
      <span class="tabular-nums">{{ formatCount(chip.count, current) }}</span>
    </NuxtLink>
  </nav>
</template>
