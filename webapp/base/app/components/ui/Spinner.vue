<script setup lang="ts">
// Attente en cours. Décoratif : c'est le texte à côté qui dit CE QU'ON attend — un spinner seul
// n'annonce rien à qui ne le voit pas, et son appelant porte déjà le message.
</script>

<template>
  <span class="ui-spinner" aria-hidden="true" />
</template>

<style scoped>
/* Un anneau incomplet : c'est le vide qui tourne, donc rien à dessiner que des bordures. */
.ui-spinner {
  display: block;
  flex: none;
  width: 1em;
  height: 1em;
  border: 2px solid color-mix(in srgb, currentColor 25%, transparent);
  border-top-color: var(--accent);
  border-radius: 50%;
}

/* Sans mouvement, un anneau figé ferait croire à un décor. Il disparaît plutôt que de mentir : le
   texte à côté suffit à dire qu'on attend. */
@media (prefers-reduced-motion: no-preference) {
  .ui-spinner {
    animation: ui-spinner-turn 0.7s linear infinite;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ui-spinner {
    display: none;
  }
}

@keyframes ui-spinner-turn {
  to {
    transform: rotate(1turn);
  }
}
</style>
