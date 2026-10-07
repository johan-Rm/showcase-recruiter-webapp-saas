<script setup lang="ts">
import type { SocialProvider } from '#shared/types/social'

// Boutons « Continuer avec … ». Un fournisseur non configuré n'est pas dans la liste (résolu en SSR),
// donc rien ne s'affiche : le tunnel e-mail reste entier, sans bouton mort à cliquer.
//
// Chaque bouton est un lien HTML PLEIN — pas un `fetch`, pas un `NuxtLink` : la cible est une route
// serveur qui répond par une redirection 302 vers le fournisseur. Un routeur client l'intercepterait
// et tenterait de la résoudre comme une page ; `external` force la vraie navigation.
const { providers, startPath } = useSocialLogin()

// Libellés portés par l'i18n, pas par le nom technique du fournisseur.
//
// Deux clés par fournisseur, et ce n'est pas un doublon : côte à côte, chaque bouton dispose d'une
// demi-colonne — « Continuer avec LinkedIn » n'y tient pas. C'est donc le NOM qui s'affiche, et la
// phrase entière qui reste ANNONCÉE : sans elle, un lecteur d'écran ne dirait que « Google », sans
// jamais dire ce que le bouton déclenche.
const name = (provider: SocialProvider): string => `inscription.social.name.${provider}`
const spokenLabel = (provider: SocialProvider): string => `inscription.social.${provider}`
</script>

<template>
  <!-- Côte à côte : deux boutons empilés coûtaient deux blocs de hauteur, et faisaient déborder un
       écran conçu pour tenir dans la fenêtre. `flex-1` plutôt que deux colonnes fixes — avec un seul
       fournisseur configuré, le bouton occupe alors toute la largeur au lieu de laisser un vide. -->
  <div v-if="providers.length > 0" class="registration-social-buttons flex gap-3">
    <NuxtLink
      v-for="provider in providers"
      :key="provider"
      :to="startPath(provider)"
      external
      :aria-label="$t(spokenLabel(provider))"
      class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-line px-4 py-2.5 font-semibold no-underline transition-colors hover:bg-surface-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    >
      <!-- Logos officiels, inline : aucun script ni image tierce chargé (RGPD + CSP inchangés).
           `aria-hidden` — le libellé texte à côté porte déjà le sens, l'icône le répéterait. -->
      <svg v-if="'google' === provider" class="size-5" viewBox="0 0 48 48" aria-hidden="true">
        <path
          fill="#4285F4"
          d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"
        />
        <path
          fill="#34A853"
          d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"
        />
        <path
          fill="#FBBC05"
          d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34C2.85 17.09 2 20.45 2 24s.85 6.91 2.34 9.88l7.35-5.7z"
        />
        <path
          fill="#EA4335"
          d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"
        />
      </svg>

      <svg v-else class="size-5" viewBox="0 0 24 24" fill="#0A66C2" aria-hidden="true">
        <path
          d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"
        />
      </svg>

      <!-- `aria-hidden` : l'intitulé complet est déjà porté par `aria-label` sur le lien, ce nom
           court le répéterait en plus bref. -->
      <span aria-hidden="true">{{ $t(name(provider)) }}</span>
    </NuxtLink>
  </div>
</template>
