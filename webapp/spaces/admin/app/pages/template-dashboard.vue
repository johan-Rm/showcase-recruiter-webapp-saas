<script setup lang="ts">
// Template « dashboard » — wireframe skeleton de la coque admin (zoning P3).
// Chaque zone (A/B/C/D) est une boîte nommée : son NOM flotte au CENTRE (absolute), le
// skeleton derrière donne la structure. Route de prévisualisation, à retirer quand les
// écrans réels arrivent. Libellés en dur (dev-facing) — pas de clés i18n pour un échafaudage.
//
// Layout `dashboard` standard + <DashboardBody> (slots nommés #header / #summary / #aside).
definePageMeta({ layout: 'dashboard' })
</script>

<template>
  <DashboardBody>
    <!-- A · en-tête d'écran -->
    <template #header>
      <div class="relative flex min-h-24 items-center rounded-lg bg-surface p-block">
        <span class="zone-name">Gouttière</span>
      </div>
    </template>

    <!-- B · résumé (KPI) -->
    <template #summary>
      <div class="relative rounded-lg bg-surface p-block">
        <span class="zone-name">Section haute</span>
        <div class="grid grid-cols-2 gap-block lg:grid-cols-4">
          <span v-for="n in 4" :key="n" class="sk h-20 w-full" aria-hidden="true" />
        </div>
      </div>
    </template>

    <!-- C · zone primaire (2fr) — grille anti-vide : les cartes se multiplient pour remplir
         la largeur (auto-fill + minmax), sans une seule media query. -->
    <div class="relative rounded-lg bg-surface p-block">
      <span class="zone-name">Zone principale</span>
      <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,18rem),1fr))] gap-block">
        <span v-for="n in 6" :key="n" class="sk h-32 w-full rounded-lg" aria-hidden="true" />
      </div>
    </div>

    <!-- D · zone secondaire (1fr) -->
    <template #aside>
      <div class="relative flex h-full flex-col gap-block rounded-lg bg-surface p-block">
        <span class="zone-name">Zone secondaire</span>
        <span class="sk h-40 w-full" aria-hidden="true" />
        <span class="sk h-40 w-full" aria-hidden="true" />
      </div>
    </template>
  </DashboardBody>
</template>

<style scoped>
/* Nom de zone : flotte au CENTRE de la boîte de zone (relative ci-dessus). */
.zone-name {
  position: absolute;
  inset: 0;
  z-index: 1;
  display: grid;
  place-items: center;
  padding-inline: 0.75rem;
  text-align: center;
  pointer-events: none;
  font-weight: 700;
  font-size: var(--text-small);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--accent);
}
.sk {
  display: block;
  flex: none;
  border-radius: 0.375rem;
  background: var(--surface-soft);
}
@media (prefers-reduced-motion: no-preference) {
  .sk {
    animation: sk-pulse 1.5s ease-in-out infinite;
  }
}
@keyframes sk-pulse {
  50% {
    opacity: 0.55;
  }
}
</style>
