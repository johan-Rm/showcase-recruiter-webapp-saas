<script setup lang="ts">
// Entrypoint « Le Vivier » — wireframe skeleton de la coque cockpit, forme la plus complète :
// console (3fr) · vue principale (9fr) · inspecteur (3fr). Seuls textes affichés : les NOMS de zones.
//
// Aucune vraie page ne montre encore cette forme à trois colonnes — ce template la garde comme
// référence de zonage.
//
// Layout STANDARD `cockpit` : ce template compose ses sections en direct. L'inspecteur, ici, est
// une colonne statique (le tiroir refermable est une mécanique d'écran vivant, pas de wireframe).
definePageMeta({ layout: 'cockpit' })
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-element">
    <!-- Gouttière -->
    <Gutter>
      <WireframeZoneName label="Gouttière" />
      <span class="skeleton h-3 w-48 xwide:h-3.5 xwide:w-64" aria-hidden="true" />
      <template #action>
        <span class="skeleton h-7 w-28 xwide:h-8 xwide:w-36" aria-hidden="true" />
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

    <!-- GRILLE : console (3fr) · vue principale (9fr) · inspecteur (3fr). Colonne unique sous `lg`. -->
    <!-- Conteneur de grille, pas un repère : celui-ci appartient à la coque. Sans nom
         accessible, une `<section>` n'annoncerait rien de plus qu'un `<div>`. -->
    <div
      class="grid min-h-0 flex-1 grid-cols-1 gap-element lg:grid-cols-[minmax(280px,3fr)_minmax(0,9fr)_minmax(280px,3fr)]"
    >
      <!-- ③ Console -->
      <aside
        class="relative hidden min-w-0 overflow-auto rounded-lg bg-surface lg:block"
        :aria-label="$t('cockpit.zone.console')"
      >
        <div class="flex h-full flex-col gap-element p-block">
          <WireframeZoneName label="Console" />
          <span class="skeleton h-9 w-full xwide:h-11" aria-hidden="true" />
          <span
            v-for="n in 6"
            :key="n"
            class="skeleton h-11 w-full xwide:h-14"
            aria-hidden="true"
          />
        </div>
      </aside>

      <!-- ① Vue principale -->
      <section class="relative min-w-0 overflow-auto rounded-lg bg-surface">
        <div class="flex h-full flex-col gap-element p-block">
          <WireframeZoneName label="Vue principale" />
          <span
            v-for="n in 9"
            :key="n"
            class="skeleton h-12 w-full xwide:h-16"
            aria-hidden="true"
          />
        </div>
      </section>

      <!-- ② Inspecteur -->
      <aside
        class="relative hidden min-w-0 overflow-auto rounded-lg bg-surface lg:block"
        :aria-label="$t('cockpit.zone.inspector')"
      >
        <div class="flex h-full flex-col gap-element p-block">
          <WireframeZoneName label="Inspecteur" />
          <span class="skeleton h-28 w-full xwide:h-36" aria-hidden="true" />
          <span v-for="n in 5" :key="n" class="skeleton h-9 w-full xwide:h-11" aria-hidden="true" />
        </div>
      </aside>
    </div>
  </div>
</template>
