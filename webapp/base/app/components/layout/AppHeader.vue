<script setup lang="ts">
// Header applicatif partagé par les coques qui en portent un. La marque et l'accès à la session
// sont invariants ; ce qui varie d'une coque à l'autre passe par le slot, entre les deux.
</script>

<template>
  <header class="layout-app-header border-b border-line bg-surface">
    <div class="container flex flex-wrap items-center justify-between gap-4 py-3">
      <LayoutBrand orientation="vertical" />

      <nav class="flex items-center gap-3" :aria-label="$t('nav.main')">
        <slot />
        <AuthNav />
      </nav>
    </div>
  </header>
</template>
