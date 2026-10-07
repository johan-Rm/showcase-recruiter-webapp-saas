<script setup lang="ts">
/**
 * PASTILLE de cellule — une donnée répétée sur chaque ligne du tableau.
 *
 * Elle se distingue par son ICÔNE et son LIBELLÉ, jamais par la couleur : c'est la règle écrite de
 * la charte, et c'est aussi ce qui garde l'accent disponible pour les états de ligne — les seuls
 * endroits du tableau où il code réellement quelque chose.
 *
 * HAUTEUR FIXE : c'est elle qui fait qu'une colonne de pastilles s'aligne, au lieu de suivre la
 * longueur de chaque valeur.
 *
 * L'absence de donnée ne prend PAS de pastille : une pastille est un signal, et il n'y a rien à
 * signaler. L'appelant affiche un tiret.
 */
type Props = {
  /** Nom d'icône de la bibliothèque (`i-lucide-…`). */
  icon: string
}

const { icon } = defineProps<Props>()
</script>

<template>
  <span
    class="search-pill inline-flex h-6 items-center gap-1.5 whitespace-nowrap rounded-lg bg-dropdown px-2 text-caption"
  >
    <UIcon :name="icon" class="h-3.5 w-3.5 flex-none text-muted" aria-hidden="true" />
    <slot />
  </span>
</template>
