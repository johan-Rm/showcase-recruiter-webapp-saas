<script setup lang="ts">
import type { NuxtError } from '#app'

const { error } = defineProps<{ error: NuxtError }>()

const { localePath } = useLocale()

// On ne distingue que « introuvable » du reste : le détail technique de l'erreur n'est
// jamais montré à l'utilisateur (il fuiterait l'interne ; le dev a déjà l'overlay Nuxt).
const key = computed(() => (error.statusCode === 404 ? 'notFound' : 'unexpected'))

// clearError purge l'état d'erreur : sans lui, la page d'erreur survit à la navigation.
const backHome = (): Promise<void> => clearError({ redirect: localePath('/') })
</script>

<template>
  <NuxtLayout>
    <UiCard as="section" class="mx-auto mt-12 max-w-lg text-center">
      <p class="font-display text-5xl font-bold text-accent">{{ error.statusCode }}</p>

      <h1 class="mt-4">{{ $t(`error.${key}.title`) }}</h1>
      <p class="mt-2 text-muted">{{ $t(`error.${key}.message`) }}</p>

      <UiButton class="mt-6" @click="backHome">
        {{ $t('error.backHome') }}
      </UiButton>
    </UiCard>
  </NuxtLayout>
</template>
