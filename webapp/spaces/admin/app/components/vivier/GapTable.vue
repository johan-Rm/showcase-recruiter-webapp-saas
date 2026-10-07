<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'

// Les profils qu'une qualification ne compte pas. Le nom ouvre la fiche complète — c'est là qu'on
// va voir la donnée elle-même, l'écran ne fait que désigner qui regarder et pourquoi.
//
// Le motif arrive en code depuis le serveur et se nomme ici, dans la langue de l'interface.
interface GapItem {
  id: string
  givenName: string
  familyName: string
  city: string | null
  country: string | null
  reason: string
}

interface Props {
  items: GapItem[]
}

const { items } = defineProps<Props>()

const { localePath, t } = useLocale()

const location = (item: GapItem): string => [item.city, item.country].filter(Boolean).join(' · ')

const columns: TableColumn<GapItem>[] = [
  { id: 'name', header: () => t('vivier.gap.column.name') },
  { id: 'location', header: () => t('vivier.gap.column.location') },
  { id: 'reason', header: () => t('vivier.gap.column.reason') },
]
</script>

<template>
  <UTable :data="items" :columns="columns" class="vivier-gap-table">
    <template #name-cell="{ row }">
      <NuxtLink
        :to="localePath(`/candidats/${row.original.id}`)"
        class="font-semibold text-text transition-colors hover:text-accent"
      >
        {{ row.original.givenName }} {{ row.original.familyName }}
      </NuxtLink>
    </template>

    <template #location-cell="{ row }">
      <span class="whitespace-nowrap text-muted">{{ location(row.original) || '—' }}</span>
    </template>

    <template #reason-cell="{ row }">
      <span class="whitespace-nowrap">{{ $t(`vivier.gap.reason.${row.original.reason}`) }}</span>
    </template>
  </UTable>
</template>
