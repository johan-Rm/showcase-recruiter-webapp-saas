<script setup lang="ts">
// LES ÉCARTÉS, hors de la pile mais jamais perdus : c'est ce qui permet d'écarter franchement.
// Superposé et sans adresse, comme l'affinage — la corbeille se consulte, elle ne s'habite pas.
//
// L'ouverture vient de la page (`open`), la fermeture repart en événement : la liaison
// bidirectionnelle vit ici, dans le composant, et non dans le template de la page.
//
// RACINE UNIQUE, donc marqueur sur elle — et pas sur la liste interne, où il aurait été plus commode
// à poser. La règle `components-root-marker` l'a signalé, c'est son travail.
import { describeDecision } from '#services/search/candidate'

import type { CandidateCard } from '#shared/types/candidate-card'

type Props = {
  open: boolean
  dismissed: CandidateCard[]
}

defineProps<Props>()

const emit = defineEmits<{
  'update:open': [open: boolean]
  restore: [candidate: CandidateCard]
}>()
</script>

<template>
  <USlideover
    class="search-v2-dismissed-drawer"
    :open="open"
    :title="$t('search.viewer.panel.dismissedTitle')"
    @update:open="emit('update:open', $event)"
  >
    <template #body>
      <ul class="flex flex-col gap-element">
        <li
          v-for="candidate in dismissed"
          :key="candidate.id"
          class="flex items-center gap-element"
        >
          <span class="min-w-0 flex-1 truncate text-small">{{ describeDecision(candidate) }}</span>
          <UButton
            color="neutral"
            variant="outline"
            size="xs"
            icon="i-lucide-undo-2"
            @click="emit('restore', candidate)"
          >
            {{ $t('search.action.restore') }}
          </UButton>
        </li>
      </ul>
    </template>
  </USlideover>
</template>
