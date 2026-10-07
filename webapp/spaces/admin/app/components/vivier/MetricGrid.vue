<script setup lang="ts">
import { VIVIER_METRIC_META, vivierRatioDenominator } from '../../utils/vivierMetrics'
import type { VivierSnapshot } from '../../utils/vivierMetrics'

// La grille de chiffres d'une section du rapport.
//
// `<dl>` et non une suite de `<div>` : chaque chiffre est un TERME et sa VALEUR, et c'est ce que
// la balise dit. Sans elle, un lecteur d'écran énonce « Profils actifs » puis « 2 561 » comme deux
// textes voisins, sans rien qui les relie.
//
// Les colonnes montent jusqu'à `xwide` (120 rem) : au-delà de `md`, une grille figée à quatre
// colonnes ne se remplit plus, elle étire ses cartes — 398 px de large pour un nombre à quatre
// chiffres sur un écran de 1 920. Le palier est celui du socle, il ne s'improvise pas ici.
interface Props {
  /** Les métriques de la section, dans l'ordre où le back les émet. */
  metrics: Record<string, number>
  /** Le rapport ENTIER : un ratio se rapporte à une autre métrique, parfois d'une autre section. */
  snapshot: VivierSnapshot
}

const { metrics, snapshot } = defineProps<Props>()

// Résolu ici plutôt que dans la carte : c'est cette grille qui a le rapport entier sous la main.
const denominatorOf = (metric: string): number | null => {
  const ratioOf = VIVIER_METRIC_META[metric]?.ratioOf

  return ratioOf ? vivierRatioDenominator(snapshot, ratioOf) : null
}
</script>

<template>
  <dl class="vivier-metric-grid grid grid-cols-2 gap-element md:grid-cols-4 xwide:grid-cols-6">
    <VivierMetricCard
      v-for="(value, metric) in metrics"
      :key="metric"
      :label="metric"
      :value="value"
      :denominator="denominatorOf(metric)"
    />
  </dl>
</template>
