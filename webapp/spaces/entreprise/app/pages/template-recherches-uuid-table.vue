<script setup lang="ts">
// Template « Une recherche — table de profils » — wireframe skeleton.
// topbar → gouttière → barre haute → table pleine largeur (aucune colonne latérale).
//
// <ProfilePanel> est modal (top layer), la table n'a aucune colonne à lui réserver. Le bouton
// d'ouverture est un déclencheur d'échafaudage : en vrai ce sera le clic sur une ligne.
//
// Layout STANDARD `cockpit` : ce template compose ses sections en direct.
definePageMeta({ layout: 'cockpit' })

// Gabarit de colonnes partagé par l'en-tête et les lignes : une seule source pour l'alignement.
// Écrit en littéral, jamais concaténé — Tailwind ne génère que les classes qu'il lit au source.
const COLUMNS = 'grid grid-cols-[minmax(12rem,2fr)_repeat(4,minmax(6rem,1fr))_5rem] gap-element'
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-element">
    <!-- Gouttière -->
    <Gutter>
      <WireframeZoneName label="Gouttière" />
      <span class="skeleton h-3 w-48 xwide:h-3.5 xwide:w-64" aria-hidden="true" />
      <template #action>
        <!-- MAQUETTE : le bouton montre la place qu’il occupe, il n’ouvre rien. Le tiroir
             réclame une fiche de profil, et un squelette statique n’en a aucune. -->
        <UiButton type="button">Ouvrir le profil</UiButton>
      </template>
    </Gutter>

    <!-- Barre haute (surface) -->
    <ActionBar>
      <WireframeZoneName label="Barre haute" />
      <span class="skeleton h-8 w-32 xwide:h-9 xwide:w-40" aria-hidden="true" />
      <template #end>
        <span
          v-for="n in 6"
          :key="n"
          class="skeleton h-7 w-20 xwide:h-8 xwide:w-24"
          aria-hidden="true"
        />
      </template>
    </ActionBar>

    <!-- Table de profils, pleine largeur (surface) -->
    <section class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface">
      <div class="flex h-full flex-col gap-element p-block">
        <WireframeZoneName label="Table de profils" />
        <div :class="COLUMNS" class="flex-none">
          <span v-for="col in 6" :key="col" class="skeleton h-6" aria-hidden="true" />
        </div>
        <div v-for="row in 14" :key="row" :class="COLUMNS" class="flex-none">
          <span v-for="col in 6" :key="col" class="skeleton h-10 xwide:h-12" aria-hidden="true" />
        </div>
      </div>
    </section>

    <ProfilePanel />
  </div>
</template>
