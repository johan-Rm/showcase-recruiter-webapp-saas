<script setup lang="ts">
const colorMode = useColorMode()

const toggle = (): void => {
  colorMode.preference = colorMode.value === 'dark' ? 'light' : 'dark'
}
</script>

<template>
  <button
    type="button"
    class="layout-color-mode-toggle cursor-pointer rounded-lg border border-line px-2 py-1.5"
    :aria-label="$t('nav.toggleTheme')"
    @click="toggle"
  >
    <!-- L'icône dépend du thème RÉSOLU, inconnu au rendu serveur (préférence en localStorage /
         système) : la calculer au SSR provoque un mismatch d'hydratation (lune servie, soleil
         attendu). On la rend donc client-only ; repli = la lune (mode clair par défaut). -->
    <ClientOnly>
      <Icon :name="colorMode.value === 'dark' ? 'lucide:sun' : 'lucide:moon'" aria-hidden="true" />
      <template #fallback>
        <Icon name="lucide:moon" aria-hidden="true" />
      </template>
    </ClientOnly>
  </button>
</template>
