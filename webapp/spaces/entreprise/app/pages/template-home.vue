<script setup lang="ts">
// Template « Accueil entreprise » — coque cockpit SANS colonnes latérales : topbar → gouttière →
// vue principale pleine largeur, partagée en DEUX COLONNES côte à côte qui remplissent l'écran
// (moteur de recherche · recherches enregistrées).
//
// ZONAGE UNIQUEMENT : que des skeletons et des noms de zone, jamais un composant réel — un
// template dit OÙ les choses vont, l'écran vivant est ailleurs (ici `index.vue`).
//
// Sous `lg`, les deux colonnes s'empilent — le moteur passe d'abord.
//
// Layout STANDARD `cockpit` : ce template compose ses sections en direct (comme une vraie page),
// il ne passe plus par des slots nommés.
definePageMeta({ layout: 'cockpit' })
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-element">
    <!-- Gouttière -->
    <Gutter>
      <WireframeZoneName label="Gouttière" />
      <span class="skeleton h-3 w-48 xwide:h-3.5 xwide:w-64" aria-hidden="true" />
    </Gutter>

    <!-- Vue principale (surface) : DEUX COLONNES à parts égales, occupant toute la hauteur -->
    <section class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface">
      <div class="grid h-full grid-cols-1 gap-block p-block lg:grid-cols-2">
        <!-- ① Moteur de recherche -->
        <section class="relative flex min-h-0 flex-col gap-block">
          <WireframeZoneName label="Moteur de recherche" />
          <span class="skeleton h-11 w-full xwide:h-14" aria-hidden="true" />
          <span v-for="n in 4" :key="n" class="skeleton h-9 w-full xwide:h-11" aria-hidden="true" />
          <span class="skeleton mt-auto h-10 w-40 self-end xwide:h-12" aria-hidden="true" />
        </section>

        <!-- ② Recherches enregistrées.
           AUCUNE bordure ni ombre : la zone qui porte ces deux colonnes est déjà une surface, et le
           guide de marque veut que la séparation vienne du fond, jamais d'un filet (style FLAT).
           `min-h-0` : sans lui, la liste imposerait sa hauteur naturelle à la colonne et c'est la
           PAGE qui défilerait, alors que la coque interdit tout scroll hors des zones. -->
        <section class="relative flex min-h-0 flex-col gap-block">
          <WireframeZoneName label="Mes recherches" />
          <!-- `content-start` : les cartes se posent en haut et gardent leur hauteur, au lieu de
             s'étirer pour combler la colonne. -->
          <div
            class="grid min-h-0 flex-1 grid-cols-[repeat(auto-fill,minmax(min(100%,15rem),1fr))] content-start gap-element overflow-y-auto"
          >
            <span
              v-for="n in 8"
              :key="n"
              class="skeleton h-28 w-full rounded-lg xwide:h-32"
              aria-hidden="true"
            />
          </div>
        </section>
      </div>
    </section>
  </div>
</template>
