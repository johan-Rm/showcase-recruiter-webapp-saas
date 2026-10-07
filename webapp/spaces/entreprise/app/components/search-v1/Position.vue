<script setup lang="ts">
// OÙ L'ON EN EST DANS LE JEU — « 8 / 234 ».
//
// Il vit dans la barre haute, et non dans la colonne de gauche : celle-ci est masquée sous 1024
// pixels, où l'on parcourrait alors des centaines de profils sur téléphone sans aucun moyen de
// savoir où l'on en est.
//
// LE COURANT EN GROS, LE TOTAL EN PETIT : le premier change à chaque profil et se suit du coin de
// l'œil, le second est un repère fixe qu'on lit une fois. Les peser pareil obligerait à relire les
// deux pour trouver celui qui bouge.
//
// Chiffres TABULAIRES : sans eux la largeur saute à chaque carte, et le bloc entier tressaute.
//
// LA PHRASE LUE RESTE ENTIÈRE. Le découpage gros/petit est une affaire d'œil ; deux nombres nus
// séparés d'une barre oblique ne s'annoncent pas — d'où le couple « masqué à l'écoute / réservé à
// l'écoute » plutôt qu'un seul texte qui servirait mal les deux.
type Props = {
  position: number
  total: number
}

defineProps<Props>()
</script>

<template>
  <span class="search-v1-position flex-none items-baseline">
    <span aria-hidden="true" class="font-display text-h3 font-semibold tabular-nums">
      {{ position }}
    </span>
    <span aria-hidden="true" class="text-small tabular-nums text-muted"> / {{ total }}</span>
    <span class="sr-only">{{ $t('search.viewer.panel.position', { position, total }) }}</span>
  </span>
</template>
