<script setup lang="ts">
// Référence du composant, pas son nom : `:is="'NuxtLink'"` ne résout pas et rendrait une
// balise <NuxtLink> littérale — un élément inconnu, donc un lien qui ne clique pas.
import { NuxtLink } from '#components'

interface Props {
  variant?: 'primary' | 'secondary'
  /** Route interne — rend un NuxtLink. */
  to?: string
  /** URL absolue (autre sous-domaine) — rend un <a>. */
  href?: string
  type?: 'button' | 'submit'
  disabled?: boolean
}

const { variant = 'primary', to, href, type = 'button', disabled = false } = defineProps<Props>()

// La racine reste sémantiquement juste : un vrai <button>, sauf si on lui donne un lien.
const tag = computed(() => (to ? NuxtLink : href ? 'a' : 'button'))

const linkAttrs = computed(() => (tag.value === 'button' ? { type, disabled } : { to, href }))

// Primaire : accent plein. Secondaire : pas de fond plein (brand_guidelines.md).
const tone = computed(() =>
  variant === 'primary'
    ? 'bg-accent text-on-accent hover:opacity-90'
    : 'bg-surface-soft text-accent hover:bg-accent-soft',
)
</script>

<template>
  <component
    :is="tag"
    v-bind="linkAttrs"
    class="ui-button inline-flex cursor-pointer items-center gap-2 rounded-lg px-5 py-2.5 font-semibold no-underline transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent active:translate-y-px disabled:cursor-default disabled:opacity-40 disabled:active:translate-y-0"
    :class="tone"
  >
    <slot />
  </component>
</template>
