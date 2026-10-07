<script setup lang="ts">
// UNE RANGÉE D'OPTIONS EXCLUSIVES, rangées par ce qu'elles laisseraient de profils.
//
// DES PASTILLES ET NON UNE LISTE DÉROULANTE : les options se comptent sur les doigts d'une main.
// Repliées dans un menu, il faudrait l'ouvrir pour découvrir qu'une seule option est cliquable.
//
// REPOSER L'OPTION ACTIVE LA RETIRE. C'est le seul moyen d'annuler un choix dans une rangée sans
// « peu importe » — et cette entrée-là mangerait une pastille sur cinq pour dire *rien*.
//
// UNE OPTION À ZÉRO EST RÉELLEMENT DÉSACTIVÉE, pas seulement pâle : elle mène à un écran vide, et
// un bouton qu'on peut cliquer pour n'obtenir rien se lit comme une panne. Elle reste AFFICHÉE :
// la faire disparaître recomposerait la rangée sous le curseur à chaque affinage, et effacerait
// l'information « cette valeur existe dans la recherche, mais pas avec les filtres posés ».
//
// AUCUN NOMBRE N'EST MONTRÉ, et il n'en reste pas non plus dans le nom lu à voix haute : le rang de
// la barre haute bascule sur le jeu affiné dès qu'un filtre est posé, et il dit mieux ce qu'on veut
// savoir — ce qui reste après TOUS les filtres, quand une étiquette n'annonçait que son effet seul.
// `count` sert donc au seul verdict d'extinction.
type Option = {
  value: string
  label: string
  count: number
}

type Props = {
  options: Option[]
}

const { options } = defineProps<Props>()

const chosen = defineModel<string>({ required: true })

const pick = (value: string): void => {
  chosen.value = chosen.value === value ? '' : value
}
</script>

<template>
  <div class="search-refine-choice flex flex-wrap gap-1">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      :disabled="0 === option.count && chosen !== option.value"
      :aria-pressed="chosen === option.value"
      class="flex min-h-[26px] items-center rounded-lg border px-2 text-caption font-semibold transition-colors"
      :class="
        chosen === option.value
          ? 'border-accent bg-accent/12 text-accent'
          : 0 === option.count
            ? 'cursor-not-allowed border-line text-muted opacity-45'
            : 'cursor-pointer border-line text-text hover:bg-surface-soft'
      "
      @click="pick(option.value)"
    >
      {{ option.label }}
    </button>
  </div>
</template>
