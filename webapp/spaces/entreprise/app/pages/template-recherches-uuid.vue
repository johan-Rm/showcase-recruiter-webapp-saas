<script setup lang="ts">
// Template « Une recherche — parcourir les profils » — wireframe skeleton.
// topbar → gouttière → barre haute → viewerBody (vue principale) + viewerAside (à droite).
//
// Le panneau droit est STRUCTUREL (pas un tiroir). <ProfilePanel> est MODAL : il se peint dans le
// top layer, par-dessus toute la coque, sans qu'elle ait à lui réserver une zone. Le bouton
// d'ouverture est un déclencheur d'échafaudage.
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
          v-for="n in 4"
          :key="n"
          class="skeleton h-7 w-20 xwide:h-8 xwide:w-24"
          aria-hidden="true"
        />
      </template>
    </ActionBar>

    <!-- GRILLE : corps (9fr) + panneau (3fr). Colonne unique sous `lg`. -->
    <!-- Conteneur de grille, pas un repère : celui-ci appartient à la coque. Sans nom
         accessible, une `<section>` n'annoncerait rien de plus qu'un `<div>`. -->
    <div
      class="grid min-h-0 flex-1 grid-cols-1 gap-element lg:grid-cols-[minmax(0,9fr)_minmax(280px,3fr)]"
    >
      <!-- viewerBody -->
      <section class="relative min-w-0 overflow-auto rounded-lg bg-surface">
        <div class="flex h-full flex-col gap-block p-block">
          <WireframeZoneName label="Parcourir les profils — corps" />
          <span class="skeleton h-48 w-full rounded-lg xwide:h-64" aria-hidden="true" />
          <span
            v-for="n in 6"
            :key="n"
            class="skeleton h-10 w-full xwide:h-12"
            aria-hidden="true"
          />
        </div>
      </section>

      <!-- viewerAside -->
      <aside
        class="relative hidden min-w-0 overflow-auto rounded-lg bg-surface lg:block"
        :aria-label="$t('cockpit.zone.viewerAside')"
      >
        <div class="flex h-full flex-col gap-block p-block">
          <WireframeZoneName label="Parcourir les profils — panneau" />
          <span class="skeleton h-28 w-full rounded-lg xwide:h-36" aria-hidden="true" />
          <span v-for="n in 5" :key="n" class="skeleton h-9 w-full xwide:h-11" aria-hidden="true" />
        </div>
      </aside>
    </div>

    <ProfilePanel />
  </div>
</template>
