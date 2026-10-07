<script setup lang="ts">
// Santé du vivier : ce que la base CONTIENT, en lecture seule.
//
// Distinct du dashboard `/` (l'activité) et de `/jarvi` (les runs d'ingestion) :
// trois questions séparées — ce qui se passe · ce que le pipeline a fait · ce que la base contient.
//
// Les chiffres viennent de QualificationReporter, la même source que app:qualification:report :
// l'écran et le CLI ne peuvent pas diverger.
//
// La page COMPOSE, elle ne rend pas : les grilles de chiffres et les palmarès vivent dans
// `components/vivier/`. Elle garde ce qui lui appartient — le plan du document (`<section>`), les
// titres, et le partage entre la zone principale et la zone secondaire de la coque.
definePageMeta({ layout: 'dashboard' })

// La barre haute annonce l'écran ; le titre reste dans le document, masqué à l'œil, pour qu'un
// lecteur d'écran le trouve dans le contenu et non dans une bande extérieure.
const { t } = useLocale()
usePageTitle(() => t('page.admin.vivier'))

// Les trois axes du rapport, dans l'ordre où on veut les lire.
const AXES = ['sectors', 'jobs', 'languages'] as const

const { vivier, pending, error } = useVivier()

const stamp = (iso: string): string => iso.slice(0, 16).replace('T', ' ')
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <h1 class="sr-only">{{ $t('page.admin.vivier') }}</h1>
        <p v-if="vivier" class="text-muted">
          {{ $t('vivier.subtitle', { at: stamp(vivier.generatedAt) }) }}
        </p>
      </div>
    </template>

    <!-- C · le rapport, section par section -->
    <UAlert
      v-if="error"
      class="vivier-error"
      color="error"
      variant="soft"
      :title="$t('error.unexpected.title')"
      :description="$t('error.unexpected.message')"
    />
    <p v-else-if="pending" class="text-muted">{{ $t('vivier.loading') }}</p>

    <div v-else-if="vivier" class="flex flex-col gap-block">
      <section
        v-for="(metrics, section) in vivier.snapshot"
        :key="section"
        class="flex flex-col gap-element"
      >
        <h2 class="font-display text-h3 text-text">{{ section }}</h2>
        <VivierMetricGrid :metrics="metrics" :snapshot="vivier.snapshot" />
      </section>
    </div>

    <!-- D · les palmarès, dans la colonne que la coque réserve. Les chiffres et les têtes de liste
         répondent à deux questions différentes, et chacune défile pour elle-même. Sous `lg`, la
         coque les redescend seule. -->
    <template v-if="vivier" #aside>
      <div class="flex h-full flex-col gap-block">
        <VivierDistributionList
          v-for="axis in AXES"
          :key="axis"
          :heading="$t(`vivier.axis.${axis}`)"
          :rows="vivier.distributions[axis] ?? []"
        />
      </div>
    </template>
  </DashboardBody>
</template>

<style scoped>
/* LE RYTHME DE L'ALERTE en CSS et non en utilitaires passés à `:ui` — même raison que le tableau
   de l'espace entreprise : la fusion de classes de la bibliothèque ne connaît pas nos paliers, et
   laisse cohabiter son `p-4` avec le nôtre. Ici, rien à fusionner.

   `nuxt-ui.css` ne rebranche que les COULEURS et le rayon ; l'espacement et la typographie de la
   bibliothèque restent les siens, figés là où les nôtres sont fluides. Aucune VALEUR n'est
   réécrite ci-dessous : ces règles lisent les variables qui portent déjà les utilitaires.

   Les cibles sont les `data-slot` que le composant émet lui-même — pas ses classes utilitaires,
   qui changeront à la prochaine version. */
.vivier-error {
  padding: var(--spacing-block);
  gap: var(--spacing-element);
}

.vivier-error :deep([data-slot='title']),
.vivier-error :deep([data-slot='description']) {
  font-size: var(--text-small);
  line-height: var(--text-small--line-height);
}
</style>
