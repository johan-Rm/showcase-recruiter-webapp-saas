<script setup lang="ts">
// LE VERDICT D'ENSEMBLE, EN TÊTE DE COLONNE — l'anneau et le palier.
//
// IL VIENT AVANT LE DÉTAIL, et c'est tout l'objet de ce bloc : la colonne disait les six critères
// avant de dire ce qu'ils valent ensemble, et il fallait les lire tous puis faire le compte de tête,
// sur chaque profil, plusieurs centaines de fois.
//
// LE COMPTE NE S'ÉCRIT QU'UNE FOIS, et c'est l'anneau qui le porte. La phrase qui le redonnait en
// toutes lettres était la LÉGENDE de l'anneau — elle explique ce que 3 et 8 comptent —, et une
// légende se lit une fois, pas à chaque profil d'une pile qui en compte des centaines.
//
// LE CHIFFRE EST ÉCRIT, il n'est pas seulement dessiné : un anneau qui porterait son sens par sa
// seule longueur d'arc ne dit rien à qui ne le voit pas, et pas grand-chose à qui le voit mal.
//
// Passif : il reçoit un score déjà calculé (`services/search/criteria`) et n'en dérive rien.
import type { CriteriaScore } from '#services/search/criteria'

type Props = {
  score: CriteriaScore
}

const props = defineProps<Props>()

/**
 * L'anneau se dessine sur une grille de 36, et le rayon en découle.
 *
 * Un `viewBox` fixe rend le tracé indépendant de la taille affichée : la même géométrie sert à
 * cinquante-six pixels comme à trente. L'épaisseur du trait est prise dans la même unité, sans quoi
 * elle grossirait avec l'anneau.
 */
const TRAIT = 3.5
const RAYON = (36 - TRAIT) / 2
const CIRCONFERENCE = 2 * Math.PI * RAYON

/**
 * La part de l'anneau qui reste ÉTEINTE.
 *
 * C'est le décalage du pointillé, et non sa longueur, qui trace l'arc : le trait fait le tour
 * complet, et on le décale d'autant qu'il en manque.
 */
const reste = computed(() => CIRCONFERENCE * (1 - props.score.met / props.score.total))
</script>

<template>
  <div class="search-score flex flex-none items-center gap-element">
    <!-- L'ANNEAU. Le quart de tour à gauche part le remplissage EN HAUT : sans lui, un cercle SVG
         commence à trois heures, et l'arc semble amputé de son début.
         Le chiffre se pose PAR-DESSUS et non dans le SVG : dans le tracé, il tournerait avec lui. -->
    <div class="relative flex-none">
      <svg :viewBox="`0 0 36 36`" class="size-14 -rotate-90" aria-hidden="true">
        <circle
          cx="18"
          cy="18"
          :r="RAYON"
          fill="none"
          stroke="currentColor"
          :stroke-width="TRAIT"
          class="text-surface-soft"
        />
        <!-- `round` sur l'extrémité : un arc coupé net se lit comme une jauge tronquée. -->
        <circle
          cx="18"
          cy="18"
          :r="RAYON"
          fill="none"
          stroke="currentColor"
          stroke-linecap="round"
          :stroke-width="TRAIT"
          :stroke-dasharray="CIRCONFERENCE"
          :stroke-dashoffset="reste"
          class="text-accent"
        />
      </svg>

      <!-- Chiffres TABULAIRES : sans eux le couple se décale d'un profil à l'autre et l'anneau
           semble bouger. -->
      <span
        class="absolute inset-0 grid place-items-center text-small font-semibold tabular-nums"
        aria-hidden="true"
      >
        {{ score.met }}/{{ score.total }}
      </span>
    </div>

    <div class="flex min-w-0 flex-col">
      <!-- LE SEUL TEXTE VISIBLE DU BLOC, donc celui qui doit porter le sens à lui tout seul : il
           nomme À QUOI le profil est comparé — au brief du recruteur, pas à une norme abstraite.
           AU CORPS DU TEXTE ET NON À CELUI D'UN TITRE : c'est le plus long des trois paliers, et un
           cran au-dessus il se coupe dans les deux cent cinquante pixels que l'anneau lui laisse. -->
      <p class="truncate font-display text-body font-semibold">
        {{ $t(`search.viewer.panel.score.tier.${score.tier}`) }}
      </p>
      <!-- LA PHRASE NE SE LIT PLUS, ELLE S'ENTEND. Elle redonnait à l'écran un compte que l'anneau
           dessine et que le chiffre écrit déjà — trois énoncés d'un seul nombre, sur trois lignes,
           relus à chaque profil. Elle reste ici parce qu'elle porte TOUT le sens pour les lecteurs
           d'écran, à qui l'anneau et le chiffre se taisent : sans elle, ce bloc ne dirait plus
           combien de critères sur combien. -->
      <p class="sr-only">
        {{ $t('search.viewer.panel.score.summary', { count: score.met, total: score.total }) }}
      </p>
    </div>
  </div>
</template>
