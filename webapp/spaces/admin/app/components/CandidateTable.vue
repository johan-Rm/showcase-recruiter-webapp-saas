<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'

import type { AdminCandidate } from '#shared/types/candidate'

// Vivier admin en tableau (desktop). Vue opérateur : coordonnées et identité en clair. Le nom
// ouvre la fiche détaillée dédiée. UTable gère le scroll horizontal (responsive) et l'a11y du tableau.
interface Props {
  candidates: AdminCandidate[]
}

const { candidates } = defineProps<Props>()

const { localePath, t } = useLocale()

const location = (candidate: AdminCandidate): string =>
  [candidate.city, candidate.country].filter(Boolean).join(' · ')

const columns: TableColumn<AdminCandidate>[] = [
  { id: 'name', header: () => t('admin.candidates.name') },
  { id: 'location', header: () => t('admin.candidates.location') },
  { id: 'contact', header: () => t('admin.candidates.contact') },
  { id: 'status', header: () => t('admin.candidates.status') },
]
</script>

<template>
  <UTable :data="candidates" :columns="columns" class="candidate-table">
    <template #name-cell="{ row }">
      <div class="flex items-center gap-3">
        <UiAvatar
          :given-name="row.original.givenName"
          :family-name="row.original.familyName"
          :photo-url="row.original.photoUrl"
          :size="36"
        />
        <div>
          <div class="flex items-center gap-2">
            <NuxtLink
              :to="localePath(`/candidats/${row.original.id}`)"
              class="font-semibold text-text transition-colors hover:text-accent"
            >
              {{ row.original.givenName }} {{ row.original.familyName }}
            </NuxtLink>
            <Icon
              v-if="row.original.isStar"
              name="lucide:star"
              class="size-4 shrink-0 text-accent"
              aria-hidden="true"
            />
          </div>
          <div v-if="row.original.headline" class="line-clamp-1 max-w-md text-muted">
            {{ row.original.headline }}
          </div>
        </div>
      </div>
    </template>

    <template #location-cell="{ row }">
      <span class="whitespace-nowrap">{{ location(row.original) || '—' }}</span>
    </template>

    <template #contact-cell="{ row }">
      <div class="flex items-center gap-2">
        <Icon
          name="lucide:mail"
          class="size-4"
          :class="row.original.contactEmail ? 'text-accent' : 'text-muted opacity-40'"
          aria-hidden="true"
        />
        <Icon
          name="lucide:phone"
          class="size-4"
          :class="row.original.phone ? 'text-accent' : 'text-muted opacity-40'"
          aria-hidden="true"
        />
        <Icon
          name="lucide:linkedin"
          class="size-4"
          :class="row.original.linkedinUrl ? 'text-accent' : 'text-muted opacity-40'"
          aria-hidden="true"
        />
      </div>
    </template>

    <template #status-cell="{ row }">
      <div class="flex flex-wrap items-center gap-2">
        <UBadge color="primary" variant="soft">{{ row.original.visibility }}</UBadge>
        <span v-if="!row.original.active" class="text-xs font-semibold text-alert">
          {{ $t('admin.candidates.inactive') }}
        </span>
      </div>
    </template>
  </UTable>
</template>
