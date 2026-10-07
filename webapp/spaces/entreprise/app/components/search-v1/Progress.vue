<script setup lang="ts">
// LE FIL D'AVANCEMENT — la part du jeu déjà VUE.
//
// Il mesure les profils vus et NON le défilement ni le rang : sauter des profils le fait reculer, et
// un rang très avancé peut cohabiter avec un fil au tiers — ce n'est pas une contradiction, c'est ce
// que mesure chacun des deux.
//
// Il vit dans la barre haute, voisin du rang, où tous deux survivent au masquage de la colonne de
// gauche sous 1024 pixels.
//
// IL RESTE FIN. Il court sur toute la largeur : à épaisseur égale, il a déjà beaucoup de présence.
// L'épaissir en ferait un élément de l'interface disputant l'attention à la fiche du candidat, alors
// que c'est un repère.
//
// NOMMÉ, toujours : un `role="progressbar"` sans nom accessible n'annonce qu'un pourcentage nu.
type Props = {
  /** L'avancement en pour cent, déjà calculé — ce composant affiche, il ne mesure pas. */
  progress: number
}

defineProps<Props>()
</script>

<template>
  <div
    class="search-v1-progress h-0.5 w-full bg-line"
    role="progressbar"
    :aria-valuenow="progress"
    aria-valuemin="0"
    aria-valuemax="100"
    :aria-label="$t('search.viewer.panel.progress')"
  >
    <div class="h-full bg-accent transition-[width]" :style="{ width: `${progress}%` }" />
  </div>
</template>
