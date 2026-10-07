<script setup lang="ts">
// Gouttière — la bande fine sous la topbar : un guide contextuel à gauche (slot par
// défaut), une action facultative à droite (slot `action`).
//
// Composant STRUCTUREL : la page y pose son CONTENU, la barre (hauteur bornée, alignement) vit
// ici une seule fois — les pages ne réécrivent pas la hauteur. Celle-ci vient des paliers
// `--band-*` de `sizing.css`, avec son mode veille : ce fichier a longtemps porté les deux `clamp`
// en propre, au motif que la bande n'appartenait qu'à la coque cockpit. L'espace admin en a eu
// besoin à son tour, et l'argument est tombé avec l'unicité du consommateur.
//
// Racine `relative` : elle sert d'ancrage à un `WireframeZoneName` (posé en absolu) dans les
// templates wireframe.
type Props = {
  /**
   * EN VEILLE : la bande garde sa place mais se réduit, faute de contenu à porter.
   *
   * Un booléen explicite plutôt qu'une hauteur déduite de la présence des slots : un slot qui ne
   * rend qu'un commentaire ou un fragment vide compte comme rempli, et la bande resterait haute
   * sans qu'on sache pourquoi. La page sait ce qu'elle a à dire, elle le déclare.
   *
   * Elle ne DISPARAÎT pas : l'écran garde son rythme vertical, et ce qui reviendra s'y logera sans
   * déplacer le reste.
   */
  standby?: boolean
}

const { standby = false } = defineProps<Props>()

const slots = useSlots()
</script>

<template>
  <div
    class="gutter relative flex flex-none items-center gap-element px-1.5"
    :class="standby ? 'min-h-(--band-gutter-standby)' : 'min-h-(--band-gutter)'"
  >
    <div class="flex min-w-0 items-center gap-element">
      <slot />
    </div>
    <div v-if="slots.action" class="ms-auto flex-none">
      <slot name="action" />
    </div>
  </div>
</template>
