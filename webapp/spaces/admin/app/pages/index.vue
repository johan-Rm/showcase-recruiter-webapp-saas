<script setup lang="ts">
// Centre de contrôle — home admin : compteurs temps réel sur 7 jours glissants.
// La tuile « entreprises à revoir » est la seule ACTIONNABLE (elle ouvre la file de revue) ;
// le reste est en lecture, conforme à l'invariant « lecture seule par défaut » du cadrage P3.
definePageMeta({ layout: 'dashboard' })

// La barre haute annonce l'écran ; le titre reste dans le document, masqué à l'œil, pour qu'un
// lecteur d'écran le trouve dans le contenu et non dans une bande extérieure.
const { localePath, t } = useLocale()
usePageTitle(() => t('home.admin.title'))

const { dashboard, error } = useDashboard()
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <h1 class="sr-only">{{ $t('home.admin.title') }}</h1>
        <p class="text-muted">{{ $t('dashboard.subtitle') }}</p>
      </div>
    </template>

    <!-- B · compteurs -->
    <template v-if="dashboard" #summary>
      <div class="grid grid-cols-2 gap-block lg:grid-cols-4">
        <NuxtLink
          :to="localePath('/entreprises')"
          class="flex flex-col gap-element rounded-lg bg-surface p-block no-underline ring-1 ring-accent transition-colors hover:bg-accent-soft"
        >
          <span class="text-small text-muted">{{ $t('dashboard.organizationsInReview') }}</span>
          <span class="text-h2 font-semibold tabular-nums text-text">
            {{ dashboard.organizationsInReview }}
          </span>
        </NuxtLink>

        <div class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <span class="text-small text-muted">{{ $t('dashboard.logins') }}</span>
          <span class="text-h2 font-semibold tabular-nums text-text">
            {{ dashboard.loginsLast7Days }}
          </span>
        </div>

        <div class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <span class="text-small text-muted">{{ $t('dashboard.reveals') }}</span>
          <span class="text-h2 font-semibold tabular-nums text-text">
            {{ dashboard.revealsLast7Days }}
          </span>
        </div>

        <div class="flex flex-col gap-element rounded-lg bg-surface p-block">
          <span class="text-small text-muted">{{ $t('dashboard.signups') }}</span>
          <span class="text-h2 font-semibold tabular-nums text-text">
            {{ dashboard.signupsLast7Days }}
          </span>
        </div>
      </div>
    </template>

    <!-- C · vivier + raccourcis. Les deux états s'excluent : pas d'enveloppe pour les contenir. -->
    <p v-if="error" class="text-alert" role="alert">{{ $t('error.unexpected.message') }}</p>

    <section v-else-if="dashboard" class="flex flex-col gap-block rounded-lg bg-surface p-block">
      <h2 class="text-h3 font-semibold">{{ $t('dashboard.vivier') }}</h2>
      <dl class="grid grid-cols-2 gap-block sm:grid-cols-3">
        <div>
          <dt class="text-small text-muted">{{ $t('dashboard.total') }}</dt>
          <dd class="text-h3 font-semibold tabular-nums">{{ dashboard.candidatesTotal }}</dd>
        </div>
        <div>
          <dt class="text-small text-muted">{{ $t('dashboard.active') }}</dt>
          <dd class="text-h3 font-semibold tabular-nums">{{ dashboard.candidatesActive }}</dd>
        </div>
      </dl>
      <div class="flex flex-wrap gap-block">
        <NuxtLink :to="localePath('/candidats')" class="text-accent">
          {{ $t('dashboard.goToCandidates') }} →
        </NuxtLink>
        <NuxtLink :to="localePath('/jarvi')" class="text-accent">
          {{ $t('dashboard.goToJarvi') }} →
        </NuxtLink>
      </div>
    </section>
  </DashboardBody>
</template>
