<script setup lang="ts">
// Panneau de marque du login, avec une photo par thème (bg-1 en clair, bg-2 en sombre).
// Le voile en bas garde le nom et la tagline lisibles quelle que soit la photo.
</script>

<template>
  <aside
    class="auth-showcase relative isolate flex-col justify-end overflow-hidden rounded-2xl p-8"
  >
    <div class="auth-showcase__scrim" aria-hidden="true" />

    <!-- Le nom n'est PAS du texte visible : il reste au lecteur d'écran, seul à en avoir besoin. -->
    <p class="auth-showcase__mark relative text-white">
      <LayoutLogo class="h-7 w-auto" />
      <span class="sr-only">{{ $t('app.name') }}</span>
    </p>
    <i18n-t
      keypath="app.tagline"
      tag="p"
      scope="global"
      class="auth-showcase__tagline relative mt-2 text-small text-white/60"
    >
      <template #break><br /></template>
    </i18n-t>
  </aside>
</template>

<style scoped>
/* Le fond uni tient le panneau tant que la photo n'est pas décodée : sans lui, le premier écran
   de l'app flasherait en blanc. */
.auth-showcase {
  background: #060d0f;
}

/* La photo vit sur sa propre couche, et non sur l'aside : le zoom d'entrée ne doit emporter ni le
   nom, ni la tagline, ni les coins arrondis. Absolue, donc hors du flux flex du parent ; générée
   avant le voile, donc peinte dessous sans z-index.
   Cadrage sur l'horizon — c'est le sujet, il ne doit pas être rogné. */
.auth-showcase::before {
  content: '';
  position: absolute;
  inset: 0;
  background: url('/images/login-bg-1.webp') center 45% / cover no-repeat;
}

/* Entrée de la photo : elle éclot du fond uni une fois le formulaire posé.
   Sous `no-preference` uniquement — l'état par défaut reste la photo visible. La règle globale de
   `transitions.css` neutralise `animation-duration` mais PAS `animation-delay` : porter l'entrée
   ici plutôt que là-bas évite de laisser la photo invisible pendant le délai en mouvement réduit.
   Le zoom part au-dessus de 1 : en dessous, l'échelle découvrirait les bords du panneau. */
/* La marque monte APRÈS la photo, jamais pendant : elle éclot d'une image déjà posée, au lieu de
   lutter avec elle. Les délais s'enchaînent sur la durée réelle de l'entrée — 240 ms d'attente puis
   400 ms de fondu-zoom, soit 640 ms avant que le panneau soit stable. Trois nombres liés : bouger
   l'un sans les autres remet la marque au milieu du zoom.
   Le logo précède la tagline : on lit la marque, puis ce qu'elle promet. */
@media (prefers-reduced-motion: no-preference) {
  .auth-showcase::before {
    animation: auth-showcase-in var(--transition-slow) 240ms backwards;
  }

  .auth-showcase__mark {
    animation: auth-showcase-rise var(--transition-base) 700ms backwards;
  }

  .auth-showcase__tagline {
    animation: auth-showcase-rise var(--transition-base) 840ms backwards;
  }
}

@keyframes auth-showcase-in {
  from {
    opacity: 0;
    transform: scale(1.06);
  }
}

/* Une montée courte, pas un zoom : la photo a déjà occupé l'espace, la marque n'a qu'à s'y poser. */
@keyframes auth-showcase-rise {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
}

/* Voile sombre : le nom et la tagline tombent sur la zone la plus claire de la photo,
   où du blanc sur eau passerait sous le seuil de contraste. */
.auth-showcase__scrim {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgb(4 10 12 / 85%) 0%,
    rgb(4 10 12 / 40%) 35%,
    transparent 65%
  );
}
</style>
