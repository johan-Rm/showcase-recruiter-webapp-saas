<script setup lang="ts">
import { overflowsLoaded } from '#services/search/stack'

// OÙ L'ON EN EST DANS LE JEU — « 8 / 100 », et « sur 352 » quand la recherche déborde.
//
// Il vit dans la barre haute, et non dans la colonne de gauche : celle-ci est masquée sous 1024
// pixels, où l'on parcourrait alors des centaines de profils sur téléphone sans aucun moyen de
// savoir où l'on en est.
//
// LE COURANT EN GROS, LE TOTAL EN PETIT : le premier change à chaque profil et se suit du coin de
// l'œil, le second est un repère fixe qu'on lit une fois. Les peser pareil obligerait à relire les
// deux pour trouver celui qui bouge.
//
// TROIS NOMBRES AU PLUS, ET ILS NE DISENT PAS LA MÊME CHOSE : le rang décrit la pile qu'on traverse,
// et la mention finale ce que la recherche ramène VRAIMENT. Elle n'apparaît que si le second dépasse
// le premier — sur une recherche courte, tout est chargé et un « sur 28 » redondant ne ferait
// qu'encombrer. Sans elle, le recruteur croirait le vivier plus pauvre qu'il n'est.
//
// Chiffres TABULAIRES : sans eux la largeur saute à chaque carte, et le bloc entier tressaute.
//
// LA PHRASE LUE RESTE ENTIÈRE. Le découpage gros/petit est une affaire d'œil ; des nombres nus
// séparés d'une barre oblique ne s'annoncent pas — d'où le couple « masqué à l'écoute / réservé à
// l'écoute » plutôt qu'un seul texte qui servirait mal les deux.
type Props = {
  position: number
  total: number
  /** Ce que la recherche ramène, borne du chargement non comprise. */
  matchCount?: number
}

const { position, total, matchCount = 0 } = defineProps<Props>()

const overflows = computed(() => overflowsLoaded(total, matchCount))
</script>

<template>
  <span class="search-position flex-none items-baseline">
    <span aria-hidden="true" class="font-display text-h3 font-semibold tabular-nums">
      {{ position }}
    </span>
    <span aria-hidden="true" class="text-small tabular-nums text-muted"> / {{ total }}</span>
    <span v-if="overflows" aria-hidden="true" class="text-small tabular-nums text-muted">
      &nbsp;{{ $t('search.viewer.panel.of', { count: matchCount }) }}</span
    >
    <span class="sr-only">{{
      overflows
        ? $t('search.viewer.panel.positionOf', { position, total, count: matchCount })
        : $t('search.viewer.panel.position', { position, total })
    }}</span>
  </span>
</template>
