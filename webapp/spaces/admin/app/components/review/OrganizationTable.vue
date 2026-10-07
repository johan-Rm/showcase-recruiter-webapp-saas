<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'

import type { OrganizationReviewCard } from '#shared/types/organization'

// File de revue en tableau (desktop). La fiche détaillée reste portée par la carte
// (ReviewOrganizationCard), affichée en mobile. Provenance = signal de légitimité, en colonne.
// UTable gère le scroll horizontal responsive et l'a11y du tableau.
interface Props {
  organizations: OrganizationReviewCard[]
  pending: boolean
}

const { organizations, pending } = defineProps<Props>()

const emit = defineEmits<{ decide: [id: string, decision: 'validate' | 'reject'] }>()

const { t, localePath } = useLocale()

// Le refus ferme le parcours d'inscription : on le fait confirmer par ligne, jamais sur un clic isolé.
const rejecting = ref<string[]>([])
const isRejecting = (id: string) => rejecting.value.includes(id)
const startReject = (id: string) => {
  if (!isRejecting(id)) rejecting.value = [...rejecting.value, id]
}
const cancelReject = (id: string) => {
  rejecting.value = rejecting.value.filter((value) => value !== id)
}
const confirmReject = (id: string) => {
  cancelReject(id)
  emit('decide', id, 'reject')
}

const columns: TableColumn<OrganizationReviewCard>[] = [
  { id: 'name', header: () => t('admin.organizations.name') },
  { id: 'taxid', header: () => t('admin.organizations.taxId') },
  { id: 'location', header: () => t('admin.organizations.location') },
  { id: 'channel', header: () => t('admin.organizations.acquisitionChannel') },
  { id: 'status', header: () => t('admin.organizations.statusHeader') },
  { id: 'actions', header: '' },
]
</script>

<template>
  <UTable :data="organizations" :columns="columns" class="review-organization-table">
    <template #name-cell="{ row }">
      <!-- Le nom mène à la fiche : c'est le seul chemin vers le solde et le journal de crédits. -->
      <NuxtLink
        :to="localePath(`/entreprises/${row.original.id}`)"
        class="font-semibold transition-colors hover:text-accent"
      >
        {{ row.original.name }}
      </NuxtLink>
      <div
        v-if="row.original.legalName && row.original.legalName !== row.original.name"
        class="text-muted"
      >
        {{ row.original.legalName }}
      </div>
      <a
        v-if="row.original.website"
        :href="row.original.website"
        target="_blank"
        rel="noopener noreferrer"
        class="text-accent"
      >
        {{ row.original.website }}
      </a>
    </template>

    <template #taxid-cell="{ row }">
      <span class="tabular-nums">{{ row.original.taxId || '—' }}</span>
    </template>

    <template #location-cell="{ row }">{{ row.original.location || '—' }}</template>

    <template #channel-cell="{ row }">
      <template v-if="row.original.acquisitionChannel">
        {{ $t(`organization.channel.${row.original.acquisitionChannel}`)
        }}<template v-if="row.original.acquisitionChannelDetail">
          — {{ row.original.acquisitionChannelDetail }}</template
        >
      </template>
      <template v-else>—</template>
    </template>

    <template #status-cell="{ row }">
      <UBadge color="primary" variant="soft">{{
        $t(`organization.status.${row.original.validationStatus}`)
      }}</UBadge>
    </template>

    <template #actions-cell="{ row }">
      <div
        v-if="row.original.validationStatus === 'EN_REVUE'"
        class="flex justify-end gap-2 whitespace-nowrap"
      >
        <UButton :disabled="pending" @click="emit('decide', row.original.id, 'validate')">
          {{ $t('admin.organizations.validate') }}
        </UButton>

        <template v-if="isRejecting(row.original.id)">
          <UButton :disabled="pending" @click="confirmReject(row.original.id)">
            {{ $t('admin.organizations.confirmReject') }}
          </UButton>
          <UButton variant="soft" @click="cancelReject(row.original.id)">
            {{ $t('admin.organizations.cancel') }}
          </UButton>
        </template>
        <UButton v-else variant="soft" :disabled="pending" @click="startReject(row.original.id)">
          {{ $t('admin.organizations.reject') }}
        </UButton>
      </div>
    </template>
  </UTable>
</template>
