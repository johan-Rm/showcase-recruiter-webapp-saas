<script setup lang="ts">
/**
 * Le SOLDE DE CRÉDITS, en pastille de topbar.
 *
 * Il vit ICI et nulle part ailleurs. Il conditionne le geste le plus coûteux du produit et ne dépend
 * d'aucune recherche : le chercher écran par écran n'aurait pas de sens, et l'afficher à deux
 * endroits obligerait à tenir deux affichages d'accord. Il vaut donc pour tout l'espace — les
 * crédits, la fiche
 * entreprise, l'équipe, autant que les deux écrans de recherche.
 *
 * C'est lui qui CHARGE le solde, une fois, pour tout le monde : le store le sert ensuite aux écrans
 * qui décident si un dévoilement est possible. `useAsyncData` et non un `await` de premier niveau —
 * celui-ci ferait de la topbar un composant asynchrone, et la coque entière attendrait un appel qui
 * n'a rien de bloquant. Le gestionnaire doit RENDRE quelque chose, sinon l'appel serait rejoué côté
 * client faute de savoir qu'il a abouti.
 */
const { balance, loadBalance } = useReveal()

useAsyncData('entreprise:credits', async () => {
  await loadBalance()

  return true
})
</script>

<template>
  <!-- Tant que le solde est INCONNU, la pastille ne s'affiche pas. Un zéro par défaut ferait croire
       à un compte vide et découragerait un geste que rien n'empêche.

       Hauteur FIXE, alignée sur la cloche voisine : une pastille est un objet de la même famille que
       les contrôles de la topbar, pas un morceau de texte qui suivrait son propre corps. -->
  <span
    v-if="null !== balance"
    class="credit-balance flex h-8 flex-none items-center gap-1.5 rounded-lg bg-bg px-2.5"
  >
    <Icon name="lucide:coins" class="h-[18px] w-[18px] flex-none text-muted" aria-hidden="true" />

    <!-- Le chiffre est muet au lecteur d'écran : seul, il ne dit pas de quoi il est le compte. La
         phrase complète le remplace. -->
    <span class="text-caption font-extrabold tabular-nums" aria-hidden="true">{{ balance }}</span>
    <span class="sr-only">{{ $t('page.entreprise.credits.wallet', { count: balance }) }}</span>
  </span>
</template>
