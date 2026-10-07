<script setup lang="ts">
// Template « Mes recherches » — wireframe skeleton. topbar → gouttière → liste des recherches en
// cartes, pleine largeur (ni barre haute, ni colonnes latérales).
//
// Libellés de zone en dur : un échafaudage est dev-facing, il ne se traduit pas.
//
// Layout STANDARD `cockpit` : ce template compose ses sections en direct.
definePageMeta({ layout: 'cockpit' })
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-element">
    <!-- Gouttière -->
    <Gutter>
      <WireframeZoneName label="Gouttière" />
      <span class="skeleton h-3 w-48 xwide:h-3.5 xwide:w-64" aria-hidden="true" />
    </Gutter>

    <!-- Vue principale (surface) : grille anti-vide, les cartes remplissent la largeur. -->
    <section class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface">
      <div class="flex h-full flex-col gap-block p-block">
        <WireframeZoneName label="Liste des recherches" />
        <div class="grid grid-cols-[repeat(auto-fill,minmax(min(100%,18rem),1fr))] gap-block">
          <span
            v-for="n in 9"
            :key="n"
            class="skeleton h-40 w-full rounded-lg xwide:h-48"
            aria-hidden="true"
          />
        </div>
      </div>
    </section>
  </div>
</template>
