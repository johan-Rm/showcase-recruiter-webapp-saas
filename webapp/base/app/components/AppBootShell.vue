<script setup lang="ts">
// Rideau de boot : overlay plein écran render-first — présent dans le HTML SSR, donc
// visible dès le refresh. Une fois l'app prête, il s'efface en FONDU (opacité) puis se démonte ; un
// simple `v-if` disparaîtrait d'un coup. Réservé aux espaces authentifiés, boot initial uniquement.
const { status } = useAppBootstrap()
const { pageFinished } = useDeferredRuntime()
const space = useSpace()

// Doit correspondre à la durée de la transition CSS ci-dessous (`duration-500`).
const FADE_MS = 500

const active = space !== 'public'
const booted = useState<boolean>('app.boot-shell.booted', () => false)
const faded = ref(false)

// Opaque tant que l'app n'est pas prête ; monté (dans le DOM) tant que le fondu n'est pas terminé.
const visible = computed(() => active && !booted.value)
const mounted = computed(() => active && !faded.value)

if (import.meta.client) {
  watch(
    [pageFinished, status],
    ([finished, current]) => {
      if (finished && current !== 'loading') booted.value = true
    },
    { immediate: true },
  )

  // Retirer du DOM une fois le fondu de sortie joué (annulé si le rideau redevenait visible).
  const { start: scheduleUnmount, stop: cancelUnmount } = useTimeoutFn(
    () => {
      faded.value = true
    },
    FADE_MS,
    { immediate: false },
  )
  watch(booted, (value) => (value ? scheduleUnmount() : cancelUnmount()), { immediate: true })
}
</script>

<template>
  <div
    v-if="mounted"
    id="appbootshell"
    class="app-boot-shell fixed inset-0 z-100 flex flex-col items-center justify-center gap-element bg-bg transition-opacity duration-500 ease-out"
    :class="visible ? 'opacity-100' : 'pointer-events-none opacity-0'"
    role="status"
    aria-live="polite"
    aria-busy="true"
    :aria-label="$t('app.loading')"
  >
    <LayoutLogo class="h-10 w-auto animate-pulse text-text md:h-12" />
    <UiBadge>{{ $t(`space.${space}`) }}</UiBadge>
  </div>
</template>
