<script setup lang="ts">
// Page de démo (spike 022) : n'existe que dans la build candidat (404 natif ailleurs).
// Le vrai « projet de retour » (différenciants) arrive avec l'espace candidat (P4).
</script>

<template>
  <UiCard as="section">
    <h1>{{ $t('page.candidat.projet') }}</h1>
    <p>{{ $t('demo.spaceServedBy', { space: $t('space.candidat') }) }}</p>
  </UiCard>
</template>
