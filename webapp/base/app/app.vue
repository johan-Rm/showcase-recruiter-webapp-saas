<script setup lang="ts">
// `$i18n` et non `useI18n()` : même raison que dans useLocale — on dépend du contrat de l'app,
// jamais du module vue-i18n directement.
const { $i18n } = useNuxtApp()
const { current, dir } = useLocale()

const space = useSpace()

// lang + dir suivent la locale (AR = RTL) — SSR-safe via useHead.
//
// Titre : repli d'application, faute de quoi AUCUNE page n'en a — `<head>` sans `<title>` est une
// erreur de validation, et un lecteur d'écran annonce le titre AVANT tout le reste. Une page qui
// pose le sien via `useHead({ title })` prend le dessus par le `titleTemplate`.
useHead({
  htmlAttrs: {
    lang: () => current.value,
    dir: () => dir.value,
  },
  title: () => $i18n.t(`space.${space}`),
  titleTemplate: (page) => (page ? `${page} · ${$i18n.t('app.name')}` : $i18n.t('app.name')),
})

// Bootstrap des données : paliers 1-2 bloquants SSR, dédupliqués par callOnce (résolus
// côté serveur puis réhydratés sans re-fetch). Le critique de nav client et le palier 3 sont
// pilotés ci-dessous, cadencés par les jalons deferred-runtime.
const bootstrap = useAppBootstrap()
await callOnce('app.init-core-data', () => bootstrap.initCoreData())

if (import.meta.client) {
  const { runtimeReady, passiveReady } = useDeferredRuntime()
  const route = useRoute()

  // Palier 3 puis anticipation, une fois le rendu utile passé : d'abord l'arrière-plan de la page
  // AFFICHÉE (ce qui la complète sans l'avoir retenue), ensuite le préchargement de tous les autres
  // domaines pour que les navigations suivantes soient instantanées.
  //
  // Dans cet ordre et non en parallèle : les deux se partagent le même réseau, et ce qui sert
  // l'écran sous les yeux passe avant ce qui sert l'écran suivant. `runtimeReady` retombe à chaque
  // navigation (cf. deferred-runtime), donc chaque page rejoue son propre palier 3.
  //
  // Le critique de chaque nav, lui, est attendu par le middleware `bootstrap.global` avant
  // l'affichage (rendu d'un bloc).
  watch(runtimeReady, async (ready) => {
    if (!ready) return

    await bootstrap.loadBackgroundData(route.name, route.params, route.query)
    await bootstrap.preloadAll()
  })

  // Préchargement prédictif N+1 (056) : à passiveReady (utilisateur engagé) puis à chaque
  // navigation, anticipe la donnée de la navigation probable depuis la route courante (ex. fiches
  // des candidats visibles).
  const { prefetchRoute } = usePrefetch()
  watch([passiveReady, () => route.name], ([ready]) => {
    if (ready) prefetchRoute(route.name, route.params)
  })
}
</script>

<template>
  <!-- `UApp` ENVELOPPE TOUT, et ce n'est pas une commodité : c'est lui qui installe les fournisseurs
       dont les surcouches Nuxt UI ont besoin — infobulles, fenêtres, notifications. Sans lui, une
       infobulle ne se contente pas de ne pas s'afficher : elle fait tomber le rendu serveur entier
       sur « Component must be used within TooltipProvider ».
       Il ne rend aucune balise à lui : la racine reste le `div` ci-dessous. -->
  <UApp>
    <div>
      <NuxtLoadingIndicator color="var(--accent)" :height="3" />
      <NuxtRouteAnnouncer />
      <AppBootShell />
      <NuxtLayout>
        <NuxtPage />
      </NuxtLayout>
    </div>
  </UApp>
</template>
