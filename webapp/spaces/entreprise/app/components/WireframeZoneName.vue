<script setup lang="ts">
/**
 * Nom de zone d'un wireframe : flotte au CENTRE de la boîte qui le contient — celle-ci doit
 * donc être `relative` (les zones de la coque cockpit le sont déjà).
 *
 * Libellé passé en dur par la page : un échafaudage est dev-facing, il ne se traduit pas.
 * Les zones de la coque, elles, portent un vrai `aria-label` traduit.
 */
defineProps<{ label: string }>()
</script>

<template>
  <span
    class="wireframe-zone-name pointer-events-none absolute inset-0 z-[1] grid place-items-center px-element text-center text-small font-bold tracking-[0.08em] text-accent uppercase"
    >{{ label }}</span
  >
</template>
