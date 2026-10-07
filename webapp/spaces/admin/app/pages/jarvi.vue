<script setup lang="ts">
// Jarvi — supervision de l'ingestion : état des flux + agrégats du vivier.
// LECTURE SEULE : aucune relance on-demand ne part d'ici.
definePageMeta({ layout: 'dashboard' })

// La barre haute annonce l'écran ; le titre reste dans le document, masqué à l'œil, pour qu'un
// lecteur d'écran le trouve dans le contenu et non dans une bande extérieure.
const { t } = useLocale()
usePageTitle(() => t('page.admin.jarvi'))

const { jarvi, error } = useJarvi()

const stamp = (iso: string | null): string => (iso ? iso.slice(0, 16).replace('T', ' ') : '—')
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-wrap items-center justify-between gap-block">
        <h1 class="sr-only">{{ $t('page.admin.jarvi') }}</h1>
        <UBadge color="primary" variant="soft" v-if="jarvi">
          {{ jarvi.failed === 0 ? $t('jarvi.healthy') : $t('jarvi.degraded') }}
        </UBadge>
      </div>
    </template>

    <!-- B · KPI vivier -->
    <template v-if="jarvi" #summary>
      <div class="grid grid-cols-2 gap-block lg:grid-cols-4">
        <div class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <span class="text-sm text-muted">{{ $t('jarvi.total') }}</span>
          <span class="text-h2 font-semibold tabular-nums">{{ jarvi.total }}</span>
        </div>
        <div class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <span class="text-sm text-muted">{{ $t('jarvi.active') }}</span>
          <span class="text-h2 font-semibold tabular-nums">{{ jarvi.active }}</span>
        </div>
        <div class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <span class="text-sm text-muted">{{ $t('jarvi.inactive') }}</span>
          <span class="text-h2 font-semibold tabular-nums">{{ jarvi.inactive }}</span>
        </div>
        <div class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <span class="text-sm text-muted">{{ $t('jarvi.withConsent') }}</span>
          <span class="text-h2 font-semibold tabular-nums">{{ jarvi.withConsent }}</span>
        </div>
      </div>
    </template>

    <div class="flex flex-col gap-block">
      <p v-if="error" class="text-alert" role="alert">{{ $t('error.unexpected.message') }}</p>

      <template v-else-if="jarvi">
        <!-- Statut de synchronisation -->
        <section class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <h2 class="text-h3 font-semibold">{{ $t('jarvi.syncTitle') }}</h2>
          <dl class="grid grid-cols-3 gap-block">
            <div>
              <dt class="text-sm text-muted">{{ $t('jarvi.synced') }}</dt>
              <dd class="text-h3 font-semibold tabular-nums">{{ jarvi.synced }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted">{{ $t('jarvi.pending') }}</dt>
              <dd class="text-h3 font-semibold tabular-nums">{{ jarvi.pending }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted">{{ $t('jarvi.failed') }}</dt>
              <dd
                class="text-h3 font-semibold tabular-nums"
                :class="jarvi.failed > 0 ? 'text-alert' : ''"
              >
                {{ jarvi.failed }}
              </dd>
            </div>
          </dl>
        </section>

        <!-- Flux de synchronisation -->
        <section class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <h2 class="text-h3 font-semibold">{{ $t('jarvi.streams') }}</h2>
          <div
            v-for="stream in jarvi.streams"
            :key="stream.name"
            class="flex flex-wrap items-center justify-between gap-block border-b border-line pb-element last:border-0 last:pb-0"
          >
            <span class="font-semibold">{{ stream.name }}</span>
            <div class="flex flex-wrap gap-block text-sm">
              <span>
                <span class="text-muted">{{ $t('jarvi.lastRun') }} : </span
                >{{ stamp(stream.lastRunAt) }}
              </span>
              <span>
                <span class="text-muted">{{ $t('jarvi.cursor') }} : </span
                >{{ stamp(stream.cursor) }}
              </span>
            </div>
          </div>
        </section>
      </template>
    </div>
  </DashboardBody>
</template>
