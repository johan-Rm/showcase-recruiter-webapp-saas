<script setup lang="ts">
/**
 * La sous-navigation de « Mon compte ».
 *
 * Elle est posée sur CHAQUE rubrique, et pas seulement sur la page d'accueil du compte : c'est ce
 * qui fait de « Mon compte » un endroit où l'on EST, et non une liste qu'on traverse une fois. Sans
 * elle, passer des crédits à l'équipe demanderait de repasser par le menu de l'avatar.
 *
 * Chaque rubrique garde son adresse. Un menu déroulant à deux niveaux aurait économisé un clic au
 * prix du partage, du retour arrière et du clavier.
 */
const { sections } = useAccountSections()
const { localePath } = useLocale()
</script>

<template>
  <nav class="account-nav" :aria-label="$t('nav.entreprise.account')">
    <ul class="flex flex-wrap gap-element">
      <li v-for="section in sections" :key="section.key">
        <!-- `aria-current` vient de NuxtLink sur la route active : l'état ne se lit pas qu'à la
             couleur, il s'annonce. -->
        <NuxtLink
          :to="localePath(section.to)"
          class="rounded-lg px-3 py-1.5 text-small text-muted no-underline transition-colors hover:bg-accent-soft"
          active-class="bg-accent-soft font-semibold text-accent"
        >
          {{ $t(`nav.entreprise.${section.key}`) }}
        </NuxtLink>
      </li>
    </ul>
  </nav>
</template>
