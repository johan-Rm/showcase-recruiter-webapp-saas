<script setup lang="ts">
// Barre haute de l'espace admin : hamburger (< lg) et fil d'ariane à gauche ; à droite, les deux
// contrôles de l'opérateur — notifications et compte — SOUS 1 024 px seulement.
//
// Au-dessus du seuil ils vivent en pied de la colonne de navigation, qui existe alors : la barre
// haute y récupère toute sa largeur pour le seul fil d'ariane. En dessous la colonne n'existe pas —
// c'est un tiroir, fermé par défaut — et les y descendre aurait mis le compte et la pastille de
// notification à deux gestes au lieu d'un.
//
// Pas de chip crédits (propre au recruteur ; l'admin est opérateur).
const { localePath, t } = useLocale()
const { isOpen: drawerOpen, open: openDrawer } = useDrawer()

// Le libellé de la page courante vient de la MÊME source que la sidebar. Une table tenue ici en
// parallèle finit par oublier des écrans, qui n'affichent alors que « Espace admin ».
const { currentLabel } = useNav()

// Au-dessus de `lg`, la barre latérale surligne déjà l'entrée courante : le chemin n'apprend plus
// rien et la bande y sert mieux à nommer l'ÉCRAN. La page déclare ce nom ; à défaut, on retombe sur
// le libellé de navigation, pour qu'un oubli dégrade l'affichage sans le casser.
const { pageTitle } = usePageTitle()
const heading = computed(() => pageTitle.value ?? (currentLabel.value ? t(currentLabel.value) : ''))
</script>

<template>
  <header
    class="topbar flex h-[clamp(48px,6vh,64px)] flex-none items-center justify-between gap-block rounded-lg bg-surface px-block"
  >
    <!-- Hamburger (< lg) + breadcrumb -->
    <div class="flex min-w-0 items-center gap-2">
      <button
        type="button"
        class="icon-btn inline-grid place-items-center lg:hidden"
        :aria-expanded="drawerOpen"
        :aria-label="$t('nav.admin.openMenu')"
        @click="openDrawer"
      >
        <Icon name="lucide:menu" class="size-5" aria-hidden="true" />
      </button>

      <!-- Le fil d'ariane SOUS `lg` seulement : au-dessus, la barre latérale surligne déjà l'entrée
           courante, et il y dirait une troisième fois ce que la colonne et le titre disent déjà. En
           dessous il reste le seul repère de position ET le seul retour à l'accueil en texte.
           Il porte son propre nom : deux repères de navigation homonymes dans la même page ne se
           distinguent pas au lecteur d'écran, et celui-ci n'est pas la nav principale. -->
      <nav
        class="flex min-w-0 items-center gap-1.5 text-sm lg:hidden"
        :aria-label="$t('nav.breadcrumb')"
      >
        <NuxtLink
          :to="localePath('/')"
          class="shrink-0 text-muted transition-colors hover:text-accent"
        >
          {{ $t('space.admin') }}
        </NuxtLink>
        <template v-if="currentLabel">
          <!-- Le chevron marque la descente dans la hiérarchie : en arabe il se retourne, sinon il
               pointe à contresens de la lecture. -->
          <Icon
            name="lucide:chevron-right"
            class="size-4 shrink-0 text-muted rtl:rotate-180"
            aria-hidden="true"
          />
          <span class="truncate font-semibold text-text" aria-current="page">
            {{ $t(currentLabel) }}
          </span>
        </template>
      </nav>

      <!-- Le nom de l'ÉCRAN, au-dessus de `lg`. Il se tronque plutôt que de pousser ce qui le suit :
           un nom de métrique ou de candidat peut être long. -->
      <span class="hidden truncate font-display text-h3 text-text lg:block">{{ heading }}</span>
    </div>

    <!-- Les deux contrôles, sous 1 024 px seulement : au-dessus, le pied de colonne les porte. -->
    <UserControls orientation="horizontal" class="flex-none lg:hidden" />
  </header>
</template>

<style scoped>
.icon-btn {
  position: relative;
  height: 2rem;
  width: 2rem;
  border: 0;
  border-radius: 0.5rem;
  background: var(--bg);
  color: var(--text);
  cursor: pointer;
  transition:
    background-color 0.15s ease,
    color 0.15s ease;
}
.icon-btn:hover {
  background: var(--accent-soft);
  color: var(--accent);
}
</style>
