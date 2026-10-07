<script setup lang="ts">
// Gabarit courant : en-tête applicatif, puis le contenu de la route.
//
// LE REPÈRE PRINCIPAL EST ICI, pas dans les pages — cf. `layouts/README.md`. `min-h-0` neutralise
// la seule classe du thème `UMain`, un `min-h-[calc(100vh-var(--ui-header-height))]` qui suppose
// un header Nuxt UI de 4 rem. Le nôtre n'a pas cette hauteur, et la coque n'en veut pas.
</script>

<template>
  <div>
    <LayoutAppHeader>
      <LayoutLocaleSwitcher />
      <LayoutColorModeToggle />
    </LayoutAppHeader>

    <UMain :ui="{ base: 'min-h-0' }" class="container py-pane">
      <AuthSessionHint />
      <slot />
    </UMain>
  </div>
</template>
