<script setup lang="ts">
// Structure de contenu de la coque admin (gabarit A/B/C/D), rendue par chaque page dans le
// slot par défaut du layout `dashboard`. Composant NORMAL à slots nommés — pas un <NuxtLayout>
// imbriqué, dont la racine async casse la transition `out-in` → écran noir.
//
//  · #header  (A) — gouttière : en-tête d'écran (titre, actions).      FIXE
//  · #summary (B) — section haute optionnelle (filtres, KPI…).         FIXE
//  · default  (C) — zone primaire, 2fr.                                DÉFILE (desktop)
//  · #aside   (D) — zone secondaire, 1fr ; absente → C pleine largeur. DÉFILE (desktop)
//
// PLEIN ÉCRAN : A et B fixes ; seules C et D défilent (desktop). < lg (1024px) : le contenu
// A→D défile en une seule colonne.
const slots = useSlots()
const hasSummary = computed(() => Boolean(slots.summary))
const hasAside = computed(() => Boolean(slots.aside))
</script>

<template>
  <div
    class="dashboard-body flex min-h-0 min-w-0 flex-1 flex-col gap-block overflow-y-auto lg:overflow-hidden"
  >
    <!-- A · gouttière (fixe) -->
    <slot name="header" />

    <!-- B · section haute (optionnelle, fixe) -->
    <slot v-if="hasSummary" name="summary" />

    <!-- C · primaire (2fr) + D · secondaire (1fr) — zones scrollables définies (desktop) -->
    <div
      class="grid grid-cols-1 gap-block lg:min-h-0 lg:flex-1 lg:overflow-hidden"
      :class="hasAside ? 'lg:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]' : ''"
    >
      <section class="min-w-0 lg:min-h-0 lg:overflow-y-auto">
        <slot />
      </section>
      <aside v-if="hasAside" class="min-w-0 lg:min-h-0 lg:overflow-y-auto">
        <slot name="aside" />
      </aside>
    </div>
  </div>
</template>
