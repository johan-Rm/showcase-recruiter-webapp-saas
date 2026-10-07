<script setup lang="ts">
// Barre haute — la bande d'actions/critères sous la gouttière, posée sur surface : un contenu à
// gauche (slot par défaut), un au centre, un à droite (`end`), et un fil collé à son bord bas
// (`progress`).
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
// Racine `relative` : ancrage d'un `WireframeZoneName` (absolu) dans les templates wireframe, et du
// fil d'avancement. `overflow-hidden` pour que les coins arrondis le rognent à ses deux extrémités.
const slots = useSlots()
</script>

<template>
  <div
    class="search-v1-action-bar relative grid min-h-(--band-action) flex-none items-center gap-element overflow-hidden rounded-lg bg-surface px-block"
    :class="
      slots.center
        ? 'grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]'
        : 'grid-cols-[minmax(0,1fr)_auto]'
    "
  >
    <div class="flex min-w-0 items-center gap-element">
      <slot />
    </div>

    <!-- LE CENTRE. Sa colonne se dimensionne sur son contenu, les deux autres absorbent le reste à
         parts égales : il ne bouge pas quand le titre s'allonge. -->
    <div v-if="slots.center" class="flex min-w-0 items-center gap-element justify-self-center">
      <slot name="center" />
    </div>

    <div v-if="slots.end" class="flex min-w-0 items-center gap-1 justify-self-end overflow-x-auto">
      <slot name="end" />
    </div>

    <!-- LE FIL, POSÉ SUR le bord bas et non ajouté sous lui : la bande garde sa hauteur bornée.
         Hors flux, il n'est donc pas une case de la grille — son cadre de référence est la boîte de
         la bande, marges intérieures comprises, ce qui lui donne toute la largeur. -->
    <div v-if="slots.progress" class="absolute inset-x-0 bottom-0">
      <slot name="progress" />
    </div>
  </div>
</template>
