<script setup lang="ts">
// L'ORDRE DU PARCOURS — un menu à icône, posé dans la GOUTTIÈRE de la visionneuse.
//
// DANS LA GOUTTIÈRE ET NON DANS LA COLONNE : un réglage d'écran vit au niveau de l'écran. Dans la
// colonne, il s'arrogerait un RANG DE SECTION — un troisième intitulé en petites capitales de poids
// identique aux deux autres, pour le geste le plus rare des trois — et il couperait la continuité
// entre « où j'en suis » et « ce que ce profil vaut », alors qu'il ne gouverne ni l'un ni l'autre
// mais la PILE. La gouttière est en veille sur cet écran : l'y poser ne coûte aucune hauteur à la
// pile, là où la colonne en prendrait à la liste des critères.
//
// L'ordre actif est ÉCRIT SUR LE BOUTON : une icône seule ne dit rien, et un état ne se signale
// jamais par la seule graisse ou la seule couleur.
import { STACK_ORDERS } from '#services/search/stack'

import type { StackOrder } from '#services/search/stack'

type Props = {
  /** L'ordre courant — le composant l'affiche et relaie le choix, il ne range rien. */
  order: StackOrder
}

const props = defineProps<Props>()

const emit = defineEmits<{ select: [order: StackOrder] }>()

const { t } = useLocale()

/**
 * Les deux ordres, prêts pour le menu.
 *
 * L'actif porte une COCHE en plus de sa place sur le bouton : déplier le menu doit dire lequel on
 * quitte, sinon on choisit à l'aveugle. L'icône est doublée du libellé, jamais seule.
 */
const items = computed(() =>
  STACK_ORDERS.map((value) => ({
    label: t(`search.viewer.panel.order.${value}`),
    icon: value === props.order ? 'i-lucide-check' : undefined,
    onSelect: () => emit('select', value),
  })),
)
</script>

<template>
  <!-- Une RACINE À SOI, et ce n'est pas décoratif : le menu de la bibliothèque ne rend aucun élément
       propre — il passe ses attributs au déclencheur. Le marqueur de composant atterrissait donc sur
       le bouton, et plus rien ne permettait de remonter du bloc au fichier qui le rend
       (`base/app/components/README.md` — « Marqueur de composant sur la racine »). -->
  <div class="search-v2-order-picker flex-none">
    <UDropdownMenu :items="[items]">
      <UButton
        color="neutral"
        variant="ghost"
        size="sm"
        icon="i-lucide-arrow-up-down"
        trailing-icon="i-lucide-chevron-down"
        :aria-label="$t('search.viewer.panel.order.label')"
      >
        {{ $t(`search.viewer.panel.order.${order}`) }}
      </UButton>
    </UDropdownMenu>
  </div>
</template>
