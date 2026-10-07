<script setup lang="ts">
const spaceUrl = useSpaceUrl()
</script>

<template>
  <section>
    <h1>{{ $t('home.public.title') }}</h1>
    <i18n-t keypath="app.tagline" tag="p" scope="global">
      <template #break><br /></template>
    </i18n-t>
    <div class="mt-6 flex flex-wrap gap-4">
      <UiButton :href="spaceUrl('candidat')">{{ $t('home.public.candidateCta') }}</UiButton>
      <UiButton :href="spaceUrl('entreprise')">{{ $t('home.public.companyCta') }}</UiButton>
    </div>
  </section>
</template>
