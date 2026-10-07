<script setup lang="ts">
// Une valeur soumise au masquage — nom, e-mail, téléphone. Trois cas, trois rendus (cf.
// vue_la_table.md §3.1) : la valeur en clair si le profil est dévoilé, un placeholder FLOUTÉ si la
// donnée existe mais reste à dévoiler, un tiret si elle n'existe pas.
//
// Le flou porte sur un PLACEHOLDER : l'API ne descend jamais la donnée masquée dans le navigateur.
// Et il vaut aussi pour les lecteurs d'écran — le placeholder est hors ordre de lecture, la mention
// « masqué » le remplace.
//
// Distinguer « présente mais masquée » de « absente » est un enjeu produit, pas un détail : sans ce
// signal, on grille un crédit sur un profil injoignable.
// Le masque est une CONVENTION D'AFFICHAGE, pas du texte : des points à la place d'une valeur qui
// n'est jamais descendue au navigateur. Il vit donc ici, et les appelants ne le réécrivent pas —
// ils nomment ce qu'ils masquent, la forme est l'affaire de ce composant.
const MASKS = {
  name: '●●●●● ●●●●●●',
  email: '●●●●●●●@●●●●.●●',
  phone: '●● ●● ●● ●● ●●',
} as const

type Props = {
  value?: string | null
  /** La donnée existe-t-elle côté API (`hasEmail`, `hasPhone`) ? */
  present?: boolean
  kind?: keyof typeof MASKS
}

const { value = null, present = false, kind = 'name' } = defineProps<Props>()

const placeholder = computed(() => MASKS[kind])
</script>

<template>
  <span v-if="value" class="search-masked-value">{{ value }}</span>

  <span v-else-if="present" class="search-masked-value" :title="$t('search.masked.hidden')">
    <span class="select-none blur-[3px]" aria-hidden="true">{{ placeholder }}</span>
    <span class="sr-only">{{ $t('search.masked.hidden') }}</span>
  </span>

  <span v-else class="search-masked-value text-muted">
    <span aria-hidden="true">—</span>
    <span class="sr-only">{{ $t('search.masked.none') }}</span>
  </span>
</template>
