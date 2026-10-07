<script setup lang="ts">
// Tiroir de navigation mobile (< lg). La sidebar desktop étant masquée sous 1024 px, ce tiroir est
// le seul accès de navigation sur petit écran.
//
// Un `USlideover` ancré à gauche : il gère nativement l'overlay, le backdrop, le piège de focus, le
// verrouillage du scroll, Échap et la restauration du focus au déclencheur — là où l'ancienne
// version (Teleport + Transition + gestion manuelle) les recodait à la main.
const { isOpen, close } = useDrawer()
const route = useRoute()

// Fermeture à la navigation.
watch(
  () => route.fullPath,
  () => close(),
)

// Fermer si l'écran repasse en desktop (le hamburger disparaît, le tiroir n'a plus lieu d'être).
const isDesktop = useMediaQuery('(min-width: 1024px)')
watch(isDesktop, (desktop) => {
  if (desktop) close()
})
</script>

<template>
  <!-- Le titre est ANNONCÉ — un panneau doit avoir un nom — mais PAS affiché : la marque, juste
       en dessous, dit déjà à l'œil où l'on est. -->
  <USlideover
    v-model:open="isOpen"
    side="left"
    :title="$t('nav.main')"
    class="mobile-drawer"
    :ui="{ content: 'w-[min(80vw,300px)]', title: 'sr-only' }"
  >
    <template #body>
      <SidebarNav />
    </template>
  </USlideover>
</template>
