<script setup lang="ts">
// La carte n'impose pas sa sémantique : la page choisit (<section>, <article>, <li>…).
const { as = 'div' } = defineProps<{ as?: string }>()
</script>

<template>
  <component :is="as" class="ui-card rounded-xl border border-line bg-surface p-6">
    <slot />
  </component>
</template>
