<script setup lang="ts">
// Compte admin courant. Coquille UI (skeleton) : formulaire à une colonne, largeur
// contrainte (archétype lecture/édition) dans la coque plein écran.
definePageMeta({ layout: 'dashboard' })

// La barre haute annonce l'écran ; le titre reste dans le document, masqué à l'œil, pour qu'un
// lecteur d'écran le trouve dans le contenu et non dans une bande extérieure.
const { t } = useLocale()
usePageTitle(() => t('page.admin.compte'))
</script>

<template>
  <DashboardBody>
    <template #header>
      <h1 class="sr-only">{{ $t('page.admin.compte') }}</h1>
    </template>

    <!-- C · formulaire (une colonne, largeur contrainte) -->
    <div class="flex w-full max-w-form flex-col gap-block rounded-lg bg-surface p-block">
      <div v-for="n in 4" :key="n" class="flex flex-col gap-2">
        <span class="skeleton h-3 w-28" />
        <span class="skeleton h-10 w-full" />
      </div>
      <div class="flex justify-end gap-2 pt-block">
        <span class="skeleton h-10 w-24" />
        <span class="skeleton h-10 w-32" />
      </div>
    </div>
  </DashboardBody>
</template>
