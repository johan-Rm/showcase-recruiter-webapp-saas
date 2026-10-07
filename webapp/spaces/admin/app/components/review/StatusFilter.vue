<script setup lang="ts">
import { ORGANIZATION_REVIEW_STATUSES } from '#shared/types/organization'
import type { OrganizationReviewStatus } from '#shared/types/organization'

interface Props {
  status: OrganizationReviewStatus
}

const { status } = defineProps<Props>()

// Prop + événement plutôt que `v-model` : la liaison bidirectionnelle est du code interactif, qui
// n'a pas sa place dans une page (cf. base/app/pages/README.md).
const emit = defineEmits<{ select: [status: OrganizationReviewStatus] }>()
</script>

<template>
  <nav
    class="review-status-filter flex flex-wrap gap-2"
    :aria-label="$t('admin.organizations.filter')"
  >
    <UButton
      v-for="value in ORGANIZATION_REVIEW_STATUSES"
      :key="value"
      :variant="status === value ? 'solid' : 'soft'"
      :aria-current="status === value"
      @click="emit('select', value)"
    >
      {{ $t(`organization.status.${value}`) }}
    </UButton>
  </nav>
</template>
