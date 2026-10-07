<script setup lang="ts">
// Template « Accueil entreprise » — VARIANTE de `template-home`, à comparer avec elle.
//
// Là où `template-home` réserve une colonne au moteur de recherche en PERMANENCE, celle-ci donne
// toute la place aux recherches existantes : le moteur ne se révèle QUE sur demande, et prend
// alors la place de la liste dans la même section.
//
// ZONAGE UNIQUEMENT : que des skeletons et des noms de zone, jamais un composant réel — un
// template dit OÙ les choses vont. La bascule vivante est portée par `index.vue`.
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

    <!-- Vue principale (surface), pleine largeur et pleine hauteur -->
    <section class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface">
      <div class="flex h-full flex-col gap-block p-block">
        <!-- Action « créer une recherche » : hauteur propre, elle ne s'étire pas. -->
        <div class="relative flex min-h-10 flex-none items-center">
          <WireframeZoneName label="Créer une recherche" />
          <span class="skeleton h-10 w-52 xwide:h-12 xwide:w-60" aria-hidden="true" />
        </div>

        <!-- Recherches enregistrées : tout le reste, en largeur comme en hauteur. -->
        <div class="relative flex min-h-0 flex-1 flex-col">
          <WireframeZoneName label="Mes recherches" />
          <div
            class="grid min-h-0 flex-1 grid-cols-[repeat(auto-fill,minmax(min(100%,15rem),1fr))] content-start gap-block overflow-y-auto"
          >
            <span
              v-for="n in 12"
              :key="n"
              class="skeleton h-28 w-full rounded-lg xwide:h-32"
              aria-hidden="true"
            />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>
