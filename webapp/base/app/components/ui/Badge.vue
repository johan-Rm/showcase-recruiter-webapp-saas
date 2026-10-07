<script setup lang="ts">
// Deux objets, une brique.
//
// SANS teinte, c'est le badge de CONTEXTE — « Espace entreprise » sous le logo : un cadre permanent,
// posé une fois, en accent doux. C'est le rendu d'origine, et il ne bouge pas : il n'a pas à se
// mettre à ressembler à un état parce qu'un autre écran en a besoin.
//
// AVEC une teinte, c'est un badge d'ÉTAT — ce qu'un profil est, à côté de quatre autres. La recette
// change alors, et pas seulement la couleur : hauteur fixe pour que la rangée s'aligne, corps plus
// petit, et surtout un texte MÉLANGÉ à l'encre de la page plutôt qu'en teinte pure. C'est ce mélange
// qui rend cinq badges colorés lisibles côte à côte là où cinq aplats saturés crieraient — et c'est
// lui qui fait suivre le contraste d'un thème à l'autre, `--text` changeant avec eux.
type Props = {
  /**
   * Nom de la variable CSS portant la teinte — `--state-met`, `--accent`…
   *
   * Le composant ne connaît aucun état : il reçoit une couleur et l'applique. C'est
   * `services/search/states.ts` qui décide laquelle, et lui seul.
   */
  tint?: string
  icon?: string
}

defineProps<Props>()
</script>

<template>
  <span
    v-if="tint"
    class="ui-badge inline-flex h-6 flex-none items-center gap-1 whitespace-nowrap rounded-lg px-2 text-[11px] font-bold leading-none"
    :style="{
      '--bc': `var(${tint})`,
      color: 'color-mix(in srgb, var(--bc) 62%, var(--text))',
      background: 'color-mix(in srgb, var(--bc) 20%, transparent)',
    }"
  >
    <UIcon v-if="icon" :name="icon" class="h-3 w-3 flex-none" style="color: var(--bc)" />
    <slot />
  </span>

  <span
    v-else
    class="ui-badge inline-flex items-center rounded-lg bg-accent-soft px-2.5 py-0.5 text-xs font-semibold text-accent"
  >
    <slot />
  </span>
</template>
