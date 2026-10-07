<script setup lang="ts">
// Notifications et compte, les deux contrôles qui accompagnent l'opérateur d'un écran à l'autre.
//
// UN SEUL composant pour DEUX places : la barre haute sous 1 024 px, le pied de la colonne de
// navigation au-dessus. Les écrire deux fois les aurait condamnés à diverger — c'est arrivé sur la
// navigation, dont les libellés existaient en double et avaient fini par ne plus concorder.
//
// `UPopover` et non un panneau positionné à la main : en pied de colonne, un menu ancré SOUS son
// bouton s'ouvrirait hors de l'écran. La bibliothèque se retourne au contact d'un bord et rend son
// contenu hors du flux, là où l'écriture manuelle ne regardait pas si la place existait.
type Orientation = 'horizontal' | 'vertical'

interface Props {
  orientation: Orientation
  /** Rail réduit : l'avatar seul, sans nom ni chevron. Sans objet à l'horizontale. */
  compact?: boolean
}

const { orientation, compact = false } = defineProps<Props>()

const { localePath } = useLocale()
const { user } = useUserSession()
const { logout } = useLogout()

// Identité : nom lisible tiré de l'e-mail de session, initiales pour l'avatar.
const email = computed(() => user.value?.email ?? '')
const displayName = computed<string>(() => email.value.split('@')[0] || 'Admin')
const initials = computed(() => displayName.value.slice(0, 2).toUpperCase())

// Le côté PRÉFÉRÉ, pas imposé : la bibliothèque le corrige si la place manque. À l'horizontale le
// menu descend ; en colonne il monte, puisqu'il n'y a rien sous un pied de colonne.
const side = computed(() => (orientation === 'vertical' ? 'top' : 'bottom'))
</script>

<template>
  <!-- Les deux contrôles tiennent sur UNE ligne dès que la place existe — y compris en pied de
       colonne dépliée : l'axe de la colonne ne dit rien de l'agencement de son pied, et deux
       boutons empilés y gagnaient de la hauteur pour rien. Seul le rail, large d'une icône, force
       l'empilement.
       En pied de colonne, ils tiennent les DEUX BORDS : le compte ouvre la ligne, la cloche la
       ferme. Dans la barre haute ils restent un groupe serré, puisqu'ils n'y occupent pas la
       largeur mais s'y rangent au bout. -->
  <div
    class="user-controls flex items-center gap-element"
    :class="[
      compact ? 'flex-col' : 'flex-row',
      orientation === 'vertical' && !compact ? 'w-full justify-between' : '',
    ]"
  >
    <!-- COMPTE — porte aussi les deux réglages, thème et langue, rangés ensemble. -->
    <UPopover :content="{ side, align: 'start' }">
      <button type="button" class="account-btn" :aria-label="$t('nav.admin.compte')">
        <span class="avatar" aria-hidden="true">{{ initials }}</span>
        <template v-if="!compact">
          <span class="max-w-[14ch] truncate text-small font-semibold text-text">
            {{ displayName }}
          </span>
          <Icon name="lucide:chevron-down" class="size-4 shrink-0 text-muted" aria-hidden="true" />
        </template>
      </button>

      <template #content>
        <div class="user-controls-panel flex w-60 flex-col p-element">
          <NuxtLink :to="localePath('/compte')" class="menu-item">
            <Icon name="lucide:circle-user" class="size-4 shrink-0" aria-hidden="true" />
            {{ $t('nav.admin.compte') }}
          </NuxtLink>

          <div class="my-1 border-t border-line" />

          <!-- Deux réglages sur le même modèle : le libellé à gauche, le contrôle à droite. Le
               thème rejoint la langue plutôt que de vivre seul dans la barre haute. -->
          <div class="flex items-center justify-between gap-element px-element py-1">
            <span class="text-small text-muted">{{ $t('nav.theme') }}</span>
            <LayoutColorModeToggle />
          </div>
          <div class="flex items-center justify-between gap-element px-element py-1">
            <span class="text-small text-muted">{{ $t('nav.language') }}</span>
            <LayoutLocaleSwitcher />
          </div>

          <div class="my-1 border-t border-line" />

          <button type="button" class="menu-item menu-item--danger" @click="logout">
            <Icon name="lucide:log-out" class="size-4 shrink-0" aria-hidden="true" />
            {{ $t('nav.logout') }}
          </button>
        </div>
      </template>
    </UPopover>
    <!-- NOTIFICATIONS — coquille tant que l'API n'existe pas : le compteur et les trois lignes sont
         des marques de place, déplacées telles quelles. -->
    <UPopover :content="{ side, align: 'start' }">
      <button
        type="button"
        class="control-btn relative inline-grid place-items-center"
        :aria-label="$t('admin.notifications.title')"
      >
        <Icon name="lucide:bell" class="size-[18px]" aria-hidden="true" />
        <span class="badge">3</span>
      </button>

      <template #content>
        <div class="user-controls-panel flex w-80 flex-col">
          <p class="border-b border-line p-element text-small font-semibold">
            {{ $t('admin.notifications.title') }}
          </p>
          <div
            v-for="n in 3"
            :key="n"
            class="flex gap-element border-b border-line p-element last:border-0"
          >
            <span class="mt-1 size-2 shrink-0 rounded-full bg-accent" aria-hidden="true" />
            <div class="flex flex-1 flex-col gap-element">
              <span class="skeleton h-3 w-full" />
              <span class="skeleton h-3 w-2/3" />
            </div>
          </div>
        </div>
      </template>
    </UPopover>
  </div>
</template>

<style scoped>
/* Le RYTHME du panneau est rebranché sur les jetons du socle : `nuxt-ui.css` ne fait le pont que
   pour les couleurs et le rayon, l'espacement et la typographie de la bibliothèque restent les
   siens — figés là où les nôtres sont fluides. Même méthode que le tableau de l'espace entreprise
   et l'alerte de l'écran « État de la base » : on cible les `data-slot` que le composant émet, pas
   ses classes utilitaires, qui changeront de version en version. */
.user-controls :deep([data-slot='content']) {
  padding: 0;
}

/* La MISE EN PAGE de ces boutons vit dans le template, en utilitaires, et pas ici : une déclaration
   de style local pèse plus lourd qu'une classe et battrait silencieusement une classe de rupture
   comme `lg:hidden`. Rien ne le vérifie. */
.control-btn {
  height: 2rem;
  width: 2rem;
  border: 0;
  border-radius: 0.5rem;
  background: var(--bg);
  color: var(--text);
  cursor: pointer;
}
.control-btn:hover {
  background: var(--accent-soft);
  color: var(--accent);
}
.badge {
  position: absolute;
  top: -2px;
  right: -2px;
  display: grid;
  place-items: center;
  height: 15px;
  min-width: 15px;
  padding: 0 3px;
  border-radius: 999px;
  background: var(--accent);
  color: var(--on-accent);
  font-size: 9px;
  font-weight: 800;
  line-height: 1;
}
.account-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: 0;
  border-radius: 0.5rem;
  background: transparent;
  padding: 0.25rem 0.375rem;
  color: var(--text);
  cursor: pointer;
}
.account-btn:hover {
  background: var(--sl-hover);
}
.avatar {
  display: grid;
  place-items: center;
  height: 2rem;
  width: 2rem;
  flex: none;
  border-radius: 999px;
  background: var(--accent);
  color: var(--on-accent);
  font-size: 0.75rem;
  font-weight: 700;
}
.menu-item {
  display: flex;
  width: 100%;
  align-items: center;
  gap: 0.5rem;
  border: 0;
  border-radius: 0.375rem;
  background: transparent;
  padding: 0.5rem 0.75rem;
  color: var(--text);
  font-size: 0.875rem;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
}
.menu-item:hover {
  background: var(--sl-hover);
}
.menu-item--danger {
  color: var(--alert);
}
</style>
