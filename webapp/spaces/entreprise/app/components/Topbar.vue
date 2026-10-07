<script setup lang="ts">
/**
 * Cluster droit de la topbar de l'espace entreprise (composant Topbar, propre à la layer entreprise).
 * Reprend le fonctionnement du mockup cockpit v23 : solde, notifications, puis compte — la NAVIGATION
 * vit DANS le menu du compte (cf. AccountMenu), pas en liens horizontaux.
 *
 * Le SOLDE en tête de cluster, et pas dans le menu du compte : il décide de ce qu'on peut se
 * permettre au clic suivant, et rangé dans un menu il faudrait l'ouvrir pour savoir si le geste est
 * possible.
 *
 * Assemblage seul : chaque brique (Notifications, AccountMenu) porte sa propre logique. Une page qui
 * ne rend pas ce slot retombe sur le skeleton du wireframe — c'est ce qui laisse les templates
 * intacts.
 */
</script>

<template>
  <div class="topbar flex flex-none items-center gap-element">
    <CreditBalance />
    <Notifications />
    <AccountMenu />
  </div>
</template>
