<script setup lang="ts">
const { locales, setLocale } = useLocale()
</script>

<template>
  <select
    :aria-label="$t('nav.language')"
    class="layout-locale-switcher rounded-lg border border-line bg-surface px-2 py-1.5"
    @change="setLocale(($event.target as HTMLSelectElement).value)"
  >
    <option v-for="locale in locales" :key="locale.code" :value="locale.code">
      {{ locale.name }}
    </option>
  </select>
</template>
