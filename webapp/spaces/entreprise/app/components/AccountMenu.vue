<script setup lang="ts">
/**
 * Menu du compte — l'avatar en topbar, et le panneau qui s'ouvre dessous. Reprend le fonctionnement
 * du mockup cockpit v23 : la NAVIGATION vit ici, mêlée aux réglages, plutôt qu'étalée en liens
 * horizontaux. Une seule cible à droite de la topbar, tout le reste s'y déplie.
 *
 * DISCLOSURE, pas `role="menu"` : ce sont des liens de navigation. Un vrai menu applicatif imposerait
 * la navigation aux flèches ; ici Tab suffit, et `role="menu"` à moitié implémenté mentirait au
 * lecteur d'écran. Ouverture au CLIC (le mockup ouvrait au survol — infaisable au tactile), fermeture
 * à Échap et au clic dehors.
 *
 * IDENTITÉ dérivée de l'e-mail : la session ne porte que `email` et `roles`, pas de nom ni
 * d'entreprise (le « Julien Martin / NextGen » du mockup est une donnée de démo). On n'invente pas
 * ce qu'on ne détient pas — l'initiale et le libellé viennent de l'adresse.
 */
const { user } = useUserSession()
const { localePath } = useLocale()
const { logout } = useLogout()

// Partie locale de l'adresse : « julien.martin » de « julien.martin@nextgen.com ». Sert de libellé
// et d'initiale tant que l'API n'expose pas le vrai nom.
const localPart = computed(() => (user.value?.email ?? '').split('@').at(0) ?? '')
const initial = computed(() => (localPart.value.at(0) ?? '?').toUpperCase())

/**
 * DEUX entrées, et non six alignées au même niveau.
 *
 * Le menu SÉPARE ce qui sert au TRAVAIL de ce qui administre le COMPTE. Crédits, équipe, entreprise
 * et profil ne voisinent donc pas avec les recherches : les quatre vivent sous « Mon compte », qui
 * est une page.
 *
 * PAS D'« ACCUEIL » : le logo y ramène, et c'est l'usage — à charge pour `LayoutBrand` de se
 * comporter en lien et de l'annoncer.
 */
const LINKS = [
  { to: '/recherches', key: 'searches' },
  { to: '/mon-compte', key: 'account' },
] as const

const open = ref(false)
const root = ref<HTMLElement>()
const trigger = ref<HTMLButtonElement>()

const close = () => {
  open.value = false
}

// Échap ferme ET rend le focus au bouton : sans ce retour, le focus resterait dans un panneau
// disparu et la tabulation repartirait du début du document.
const onKeydown = (event: KeyboardEvent) => {
  if ('Escape' === event.key && open.value) {
    event.preventDefault()
    close()
    trigger.value?.focus()
  }
}

// Naviguer ferme le menu : on ne le laisse pas ouvert par-dessus la page qu'on vient d'atteindre.
const onNavigate = () => close()

// `onClickOutside` (VueUse) écoute au pointerdown et ignore ce qui est DANS `root` — cliquer un
// lien ne referme donc pas avant que la navigation parte.
onClickOutside(root, close)
</script>

<template>
  <div ref="root" class="account-menu relative flex-none" @keydown="onKeydown">
    <button
      ref="trigger"
      type="button"
      class="flex cursor-pointer items-center gap-2 rounded-lg border-0 bg-transparent p-1 text-text transition-colors hover:bg-accent-soft"
      aria-haspopup="true"
      :aria-expanded="open"
      :aria-label="$t('account.menu')"
      @click="open = !open"
    >
      <span
        class="grid h-8 w-8 flex-none place-items-center rounded-full bg-accent font-semibold text-on-accent"
        aria-hidden="true"
      >
        {{ initial }}
      </span>
      <span class="hidden max-w-40 truncate text-small font-semibold sm:block">{{
        localPart
      }}</span>
      <Icon
        name="lucide:chevron-down"
        class="hidden flex-none transition-transform sm:block"
        :class="{ 'rotate-180': open }"
        aria-hidden="true"
      />
    </button>

    <!-- Panneau ancré sous le bouton, à droite. `dropdown-bg` et ombre franche : c'est une
         surcouche, le guide de marque la détache par l'ombre (cf. style FLAT). -->
    <div
      v-if="open"
      class="absolute end-0 top-[calc(100%+0.5rem)] z-30 flex w-60 flex-col overflow-hidden rounded-lg bg-dropdown py-1 shadow-2xl"
    >
      <nav class="flex flex-col py-1" :aria-label="$t('nav.main')">
        <NuxtLink
          v-for="link in LINKS"
          :key="link.key"
          :to="localePath(link.to)"
          class="px-4 py-2 text-small text-text no-underline transition-colors hover:bg-accent-soft"
          active-class="font-semibold text-accent"
          @click="onNavigate"
        >
          {{ $t(`nav.entreprise.${link.key}`) }}
        </NuxtLink>
      </nav>

      <div class="my-1 h-px bg-line" role="separator" />

      <!-- Réglages : langue et apparence. Le mockup les mettait dans ce même panneau. -->
      <div class="flex items-center justify-between px-4 py-2">
        <span class="text-small text-muted">{{ $t('nav.language') }}</span>
        <LayoutLocaleSwitcher />
      </div>
      <div class="flex items-center justify-between px-4 py-2">
        <span class="text-small text-muted">{{ $t('account.appearance') }}</span>
        <LayoutColorModeToggle />
      </div>

      <div class="my-1 h-px bg-line" role="separator" />

      <button
        type="button"
        class="px-4 py-2 text-start text-small text-text transition-colors hover:bg-accent-soft"
        @click="logout"
      >
        {{ $t('nav.logout') }}
      </button>
    </div>
  </div>
</template>
