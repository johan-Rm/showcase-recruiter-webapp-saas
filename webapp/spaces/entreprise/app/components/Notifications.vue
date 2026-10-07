<script setup lang="ts">
/**
 * Notifications — la cloche en topbar, son badge de non-lues, et le panneau qui les liste.
 * Reprend le fonctionnement du mockup cockpit v23 : panneau ancré à droite, sous la topbar.
 *
 * COQUE PRÊTE À RECEVOIR : la liste et le flux temps réel viendront d'un composable ; ici la carte
 * accepte déjà des notifications par sa prop, affiche le badge et gère l'ouverture. Sans données,
 * elle le DIT — un panneau vide vaut mieux qu'un chargement simulé.
 *
 * INVARIANT du cadrage, à ne jamais enfreindre côté serveur comme côté rendu : une notification
 * DÉCRIT un profil, elle ne l'IDENTIFIE jamais. « Un ingénieur DevOps de votre recherche Tech·France »,
 * pas un nom. Le composant n'affiche que le texte qu'on lui donne — c'est à la source de le garantir.
 */
export interface NotificationItem {
  id: string
  /** Déjà rédigé et traduit par la source, identité JAMAIS incluse (cf. invariant ci-dessus). */
  text: string
  /** Horodatage relatif déjà formaté (« il y a 2 min ») : le formatage appartient à l'appelant. */
  time: string
  read?: boolean
}

interface Props {
  items?: readonly NotificationItem[]
}

const { items = [] } = defineProps<Props>()

const unreadCount = computed(() => items.filter((item) => !item.read).length)

const open = ref(false)
const root = ref<HTMLElement>()
const trigger = ref<HTMLButtonElement>()

const close = () => {
  open.value = false
}

const onKeydown = (event: KeyboardEvent) => {
  if ('Escape' === event.key && open.value) {
    event.preventDefault()
    close()
    trigger.value?.focus()
  }
}

onClickOutside(root, close)
</script>

<template>
  <div ref="root" class="notifications relative flex-none" @keydown="onKeydown">
    <button
      ref="trigger"
      type="button"
      class="relative grid h-8 w-8 cursor-pointer place-items-center rounded-lg border-0 bg-bg text-text transition-colors hover:bg-accent-soft hover:text-accent"
      aria-haspopup="true"
      :aria-expanded="open"
      :aria-label="$t('notifications.open', { count: unreadCount })"
      @click="open = !open"
    >
      <Icon name="lucide:bell" class="h-[18px] w-[18px]" aria-hidden="true" />
      <!-- Badge muet au lecteur d'écran (`aria-hidden`) : le compte est déjà dans l'aria-label du
           bouton, l'annoncer deux fois serait redondant. -->
      <span
        v-if="unreadCount > 0"
        class="absolute end-0 top-0 grid h-4 min-w-4 -translate-y-1/2 translate-x-1/3 place-items-center rounded-full bg-accent px-1 text-[9px] font-bold text-on-accent"
        aria-hidden="true"
      >
        {{ unreadCount }}
      </span>
    </button>

    <!-- Panneau : surcouche ancrée à droite. Ombre franche (guide de marque : les surcouches se
         détachent par l'ombre, pas par une bordure). -->
    <div
      v-if="open"
      class="absolute end-0 top-[calc(100%+0.5rem)] z-30 flex max-h-[70vh] w-80 max-w-[calc(100vw-1rem)] flex-col overflow-hidden rounded-lg bg-dropdown shadow-2xl"
    >
      <header class="flex-none border-b border-line px-4 py-3">
        <h2 class="text-small font-semibold">{{ $t('notifications.title') }}</h2>
      </header>

      <p v-if="0 === items.length" class="m-auto px-4 py-8 text-center text-small text-muted">
        {{ $t('notifications.empty') }}
      </p>

      <ul v-else class="flex min-h-0 flex-col divide-y divide-line overflow-y-auto">
        <li
          v-for="item in items"
          :key="item.id"
          class="flex items-start gap-2.5 px-4 py-3"
          :class="{ 'bg-accent-soft/40': !item.read }"
        >
          <span
            class="mt-1.5 h-2 w-2 flex-none rounded-full"
            :class="item.read ? 'bg-transparent' : 'bg-accent'"
            aria-hidden="true"
          />
          <div class="min-w-0">
            <p class="text-small text-text">{{ item.text }}</p>
            <p class="mt-1 text-caption text-muted">{{ item.time }}</p>
          </div>
        </li>
      </ul>
    </div>
  </div>
</template>
