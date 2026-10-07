<script setup lang="ts">
// Barre haute — la bande d'actions/critères sous la gouttière, posée sur surface : un contenu à
// gauche (slot par défaut), un au centre, un à droite (`end`).
//
// Composant STRUCTUREL : la page y pose son CONTENU, la barre (hauteur bornée, surface, débordement
// horizontal à droite) vit ici une seule fois. La hauteur vient du palier `--band-action` de
// `sizing.css` — plus haut que celui de la gouttière, parce qu'on manipule des contrôles ici et
// qu'on lit un guide là-bas.
//
// LE CENTRE EST EXACT, et c'est le gabarit de colonnes qui le garantit : deux colonnes souples de
// largeur ÉGALE encadrent une colonne dimensionnée sur son contenu. Un `justify-between` centrerait
// « au milieu de ce qui reste », donc à une abscisse qui se déplace avec la longueur du titre — et
// un repère qui change de place d'un écran à l'autre n'est pas un repère.
//
// LE GABARIT SUIT LE CENTRE, et pas l'inverse. Sans zone centrale, la colonne de gauche reprend
// TOUTE la place libre : c'est ce qui garde la Table et l'écran de création à l'identique. Chez eux
// le titre dispose de la bande entière moins ses commandes ; deux colonnes égales le tronqueraient à
// mi-largeur sans que rien ne l'ait demandé.
//
// Racine `relative` : ancrage d'un `WireframeZoneName` (absolu) dans les templates wireframe.
// `overflow-hidden` pour que les coins arrondis rognent son contenu à ses deux extrémités.
const slots = useSlots()

type Props = {
  /**
   * La zone de fin cesse d'être dimensionnée sur son contenu et devient une COLONNE, à l'aplomb de
   * celle qui la surplombe dans la zone de travail.
   *
   * Facultatif, et faux par défaut : six écrans sur sept n'ont rien sous leur bande qui vaille un
   * alignement, et une bande dont la droite est dimensionnée sur son contenu leur convient. Seul
   * l'écran qui a une colonne sous lui le demande.
   *
   * Il emporte trois choses ensemble, et elles ne se séparent pas : le gabarit de colonnes reprend
   * le RAPPORT de la zone de travail (trois quinzièmes), la marge intérieure de fin disparaît pour
   * que le bord droit tombe sur celui du contenu, et l'écart entre les deux colonnes s'annule — sans
   * quoi la fraction porterait sur une largeur amputée et le bord glisserait.
   *
   * ⚠️ IL NE VAUT QU'À PARTIR DE `xl`, le seuil où la colonne à aligner existe. En dessous, elle
   * retombe sous son voisin et il n'y a plus rien à quoi s'accrocher : la bande reprend alors une
   * fin dimensionnée sur son contenu, sans quoi elle réserverait 360 pixels à une jauge qui en
   * occupe cent.
   */
  endColumn?: boolean
}

const { endColumn = false } = defineProps<Props>()
</script>

<template>
  <div
    class="action-bar relative grid min-h-(--band-action) flex-none items-center overflow-hidden rounded-lg bg-surface ps-block"
    :class="[
      'gap-element pe-block',
      slots.center
        ? 'grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]'
        : 'grid-cols-[minmax(0,1fr)_auto]',
      endColumn ? 'xl:grid-cols-[minmax(0,12fr)_minmax(360px,3fr)] xl:gap-0 xl:pe-0' : '',
    ]"
  >
    <div class="flex min-w-0 items-center gap-element">
      <slot />
    </div>

    <!-- LE CENTRE. Sa colonne se dimensionne sur son contenu, les deux autres absorbent le reste à
         parts égales : il ne bouge pas quand le titre s'allonge. -->
    <div v-if="slots.center" class="flex min-w-0 items-center gap-element justify-self-center">
      <slot name="center" />
    </div>

    <!-- EN COLONNE, LE CONTENU PART DU BORD GAUCHE de la cellule : c'est ce qui le met à l'aplomb de
         la colonne du dessous. Collé à droite il retomberait où il était, et la colonne n'aurait
         servi à rien. Sans colonne, il reste collé à droite — il n'y a rien à quoi s'aligner.
         ET IL PREND LA MÊME MARGE INTÉRIEURE que la colonne qu'il surplombe : celle-ci en a une, et
         son contenu commence donc à dix-huit pixels de son bord. Sans cette marge ici, la cellule
         s'aligne sur la BOÎTE de la colonne au lieu de s'aligner sur ce qu'elle CONTIENT — et c'est
         le contenu qu'on lit. -->
    <div
      v-if="slots.end"
      class="flex min-w-0 items-center gap-1 overflow-x-auto"
      :class="
        endColumn ? 'justify-self-end xl:justify-self-stretch xl:px-block' : 'justify-self-end'
      "
    >
      <slot name="end" />
    </div>
  </div>
</template>
