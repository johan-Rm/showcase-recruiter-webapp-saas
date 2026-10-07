<script setup lang="ts">
/**
 * Les moments où l'écran n'a rien à montrer — chargement, vide, erreur, refus.
 *
 * Ce sont ceux où l'utilisateur a le plus besoin d'être tenu, et ceux qu'on habille en dernier : les
 * deux écrans de recherche les rendaient en texte muet, posé au milieu, sans rien qui distingue
 * « il n'y a rien » de « ça a échoué ».
 *
 * Un seul composant pour les quatre : leur MISE EN PAGE est la même, seule la nature change. Quatre
 * traitements séparés auraient dérivé l'un de l'autre.
 *
 * L'icône ne code pas la nature par sa COULEUR — le guide réserve la couleur aux vues et à l'accent.
 * Seule l'erreur y échappe : elle n'est pas une nuance de vide, elle demande une action.
 */
type Props = {
  /** Ce qui s'est passé. Gouverne l'icône, le ton, et le rôle ARIA. */
  tone?: 'empty' | 'loading' | 'error'
  icon?: string
  title: string
  /** Une phrase, pas un paragraphe : elle dit quoi faire, ou pourquoi il n'y a rien. */
  hint?: string
}

const { tone = 'empty', icon = '', title, hint = '' } = defineProps<Props>()

const shownIcon = computed(() => {
  if (icon) {
    return icon
  }

  return 'error' === tone ? 'i-lucide-triangle-alert' : 'i-lucide-search-x'
})
</script>

<template>
  <div
    class="search-state m-auto flex max-w-readable flex-col items-center gap-element px-block py-block text-center"
    :role="'error' === tone ? 'alert' : undefined"
  >
    <UiSpinner v-if="'loading' === tone" />
    <Icon
      v-else
      :name="shownIcon"
      class="text-h2"
      :class="'error' === tone ? 'text-alert' : 'text-muted'"
      aria-hidden="true"
    />

    <p class="font-display text-h3" :class="{ 'text-alert': 'error' === tone }">{{ title }}</p>
    <p v-if="hint" class="text-small text-muted">{{ hint }}</p>

    <slot />
  </div>
</template>
