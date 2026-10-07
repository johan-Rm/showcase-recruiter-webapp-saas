<script setup lang="ts">
// L'AVANCEMENT, EN CASES — la jauge posée à gauche du rang « 8 / 234 ».
//
// UN NOMBRE FIXE DE CASES, remplies au prorata, et jamais une case par profil : les recherches vont
// de trois profils à trois mille, et une case par profil n'existe pas au-delà d'une trentaine. Le
// dessin ne bascule donc jamais d'une recherche à l'autre — c'est la même jauge partout, et ce qui
// change est ce qu'elle contient.
//
// LA CASE ENTAMÉE EST CE QUI SAUVE LES GRANDES RECHERCHES. Au huitième profil d'une recherche qui en
// compte trois mille, le prorata ne remplit pas un vingtième de la barre : sans elle, la jauge
// resterait éteinte pendant les cent premiers profils et se lirait comme une panne. Elle dit « c'est
// commencé » là où l'arrondi dirait « rien ».
//
// ELLE NE S'ANNONCE PAS. Le rang qu'elle double, juste à côté, porte déjà sa phrase pour qui écoute
// la page ; deux fois la même information à l'oreille est une gêne, pas un service.
type Props = {
  /** Le rang du profil regardé, à partir de 1. */
  position: number
  total: number
}

const props = defineProps<Props>()

/**
 * Vingt cases, et ce nombre est un compromis de largeur.
 *
 * En dessous, la jauge cesse de distinguer les avancements proches — à dix cases, le tiers et le
 * quart de la recherche donnent le même dessin. Au-dessus, elle mange la barre haute, où elle
 * partage la place avec le rang et les deux entrées vers les filtres.
 */
const CASES = 20

/** L'état de chaque case, dans l'ordre. */
const cases = computed(() => {
  // Une recherche sans résultat n'a pas d'avancement : la jauge reste éteinte plutôt que de diviser
  // par zéro. La page ne la monte alors pas, mais le composant ne s'appuie pas là-dessus.
  const ratio = 0 < props.total ? Math.min(1, Math.max(0, props.position / props.total)) : 0

  const exact = ratio * CASES
  const pleines = Math.floor(exact)
  const entamee = pleines < CASES && exact > pleines

  return Array.from({ length: CASES }, (_, rang) => {
    if (rang < pleines) {
      return 'bg-accent'
    }

    return rang === pleines && entamee ? 'bg-accent/40' : 'bg-surface-soft'
  })
})
</script>

<template>
  <!-- MASQUÉE SUR TÉLÉPHONE : vingt cases et deux entrées de filtres ne tiennent pas ensemble dans
       la bande. C'est la jauge qui cède et non le rang — elle en est le doublon visuel, et lui seul
       porte le chiffre. -->
  <div class="search-progress hidden items-center gap-0.5 opacity-60 sm:flex" aria-hidden="true">
    <!-- CHAQUE CASE PREND SA PART, et c'est ce qui fait remplir la jauge quand on lui donne de la
         place. `w-1.5` reste le PLANCHER, jamais la largeur : sans lui, vingt cases dans une bande
         étroite se réduiraient à des traits d'un pixel.
         ELLES S'ÉLARGISSENT, ELLES NE SE MULTIPLIENT PAS. Le nombre de paliers ne suit pas la
         largeur disponible : à nombre variable, le même avancement ne donnerait plus le même dessin
         d'un écran à l'autre. Mesuré, une case reste plus haute que large jusqu'à 1920 px — elle ne
         devient jamais le damier qu'on pourrait craindre. -->
    <span
      v-for="(teinte, rang) in cases"
      :key="rang"
      class="h-4 min-w-1.5 flex-1 rounded-xs"
      :class="teinte"
    />
  </div>
</template>
