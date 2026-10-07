<script setup lang="ts">
import { formatCount, gapMetricLabel } from '../../utils/vivierMetrics'

// Le détail d'un manque de qualification : derrière « Ancienneté renseignée 2 208 /2 561 », qui
// manque et pourquoi.
//
// La métrique vient du chemin, le motif filtré de la partie interrogative — les deux se partagent
// et survivent à un rechargement. La page COMPOSE : la répartition et le tableau vivent dans
// `components/vivier/`.
definePageMeta({ layout: 'dashboard' })

const route = useRoute()
const { current, localePath, t } = useLocale()

const metric = String(route.params.metric)
const reason = computed(() => String(route.query.reason ?? ''))

// Un code de métrique inconnu n'est pas une métrique sans manque : la page n'existe pas, et le dire
// vaut mieux qu'une liste vide qui se lirait comme une bonne nouvelle.
const metricLabel = gapMetricLabel(metric)

// Même cas que la fiche candidat : le titre est une donnée, pas une entrée de navigation.
usePageTitle(() => metricLabel ?? t('vivier.gap.unknown'))

const { gap, pending, error } = useVivierGap(metric, reason)

const missing = computed(() => gap.value?.breakdown.reduce((sum, row) => sum + row.count, 0) ?? 0)
</script>

<template>
  <DashboardBody data-page="vivier-metric">
    <template #header>
      <div class="flex flex-col gap-element">
        <NuxtLink
          :to="localePath('/vivier')"
          class="inline-flex w-fit items-center gap-1 text-small text-muted transition-colors hover:text-accent"
        >
          <Icon name="lucide:arrow-left" class="size-4" aria-hidden="true" />
          {{ $t('page.admin.vivier') }}
        </NuxtLink>
        <h1 class="sr-only">
          {{ metricLabel ?? $t('vivier.gap.unknown') }}
        </h1>
        <p v-if="metricLabel && gap" class="text-muted">
          {{ $t('vivier.gap.count', { count: formatCount(missing, current) }) }}
        </p>
      </div>
    </template>

    <!-- B · la répartition par motif, FIXE : c'est elle qui porte le diagnostic, elle ne doit pas
         partir par le haut dès qu'on descend dans la liste. -->
    <template v-if="metricLabel && gap" #summary>
      <VivierGapBreakdown :metric="metric" :rows="gap.breakdown" :selected="reason" />
    </template>

    <!-- C · la liste, seule zone qui défile. Une métrique inconnue passe AVANT l'erreur : le titre
         a déjà dit que la page n'existe pas, un bandeau rouge par-dessus ferait passer une faute de
         frappe pour une panne. -->
    <p v-if="!metricLabel" class="text-muted">{{ $t('vivier.gap.backToState') }}</p>
    <UAlert
      v-else-if="error"
      class="vivier-gap-error"
      color="error"
      variant="soft"
      :title="$t('error.unexpected.title')"
      :description="$t('error.unexpected.message')"
    />
    <p v-else-if="pending" class="text-muted">{{ $t('vivier.loading') }}</p>
    <p v-else-if="!gap || gap.items.length === 0" class="text-muted">
      {{ $t('vivier.gap.empty') }}
    </p>
    <VivierGapTable v-else :items="gap.items" />
  </DashboardBody>
</template>

<style scoped>
/* LE RYTHME DE L'ALERTE en CSS et non en utilitaires passés à `:ui` — même raison que sur l'écran
   parent : la fusion de classes de la bibliothèque ne connaît pas nos paliers et laisse cohabiter
   son `p-4` avec le nôtre. Aucune VALEUR n'est écrite ici, les paliers sont lus. */
.vivier-gap-error {
  padding: var(--spacing-block);
  gap: var(--spacing-element);
}

.vivier-gap-error :deep([data-slot='title']),
.vivier-gap-error :deep([data-slot='description']) {
  font-size: var(--text-small);
  line-height: var(--text-small--line-height);
}
</style>
