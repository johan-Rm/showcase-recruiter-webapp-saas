<script setup lang="ts">
import { formatCount } from '../../utils/vivierMetrics'

import type { components } from '#shared/types/api'

// Le palmarès d'un axe — secteurs d'expérience, métiers exercés, langues : les têtes de liste et
// leur volume, pour savoir ce que la base contient avant de lancer une recherche.
type Distribution = components['schemas']['AdminVivier']['distributions'][string]

interface Props {
  /** Titre déjà traduit : le composant affiche, il ne décide pas de ce qui est affiché. */
  heading: string
  rows: Distribution
}

const { heading, rows } = defineProps<Props>()

const { current } = useLocale()
</script>

<template>
  <div class="vivier-distribution-list flex flex-col gap-element rounded-lg bg-surface p-block">
    <h3 class="text-small font-semibold text-muted">{{ heading }}</h3>

    <!-- Un axe sans donnée le dit. Un titre suivi de rien se lit comme un chargement bloqué. -->
    <p v-if="rows.length === 0" class="text-small text-muted">{{ $t('vivier.empty') }}</p>

    <ul v-else class="flex flex-col gap-element">
      <li
        v-for="row in rows"
        :key="row.label"
        class="flex items-center justify-between gap-element text-small"
      >
        <span class="truncate">{{ row.label }}</span>
        <span class="tabular-nums text-muted">{{ formatCount(row.count, current) }}</span>
      </li>
    </ul>
  </div>
</template>
