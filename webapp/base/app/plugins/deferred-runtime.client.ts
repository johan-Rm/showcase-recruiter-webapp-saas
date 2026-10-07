/**
 * Cadence les traitements NON critiques pour qu'ils ne concurrencent jamais le rendu initial.
 * Trois jalons franchis dans l'ordre, exposés en lecture via `useDeferredRuntime` :
 *
 *   pageFinished — la navigation Nuxt visible est terminée
 *   runtimeReady — post-rendu contrôlé : deux frames puis `requestIdleCallback` (timeout ~1,2 s)
 *   passiveReady — 1re interaction (pointer / clavier / scroll / touch) ou fallback ~16 s
 *
 * `.client` : ces jalons n'ont de sens qu'après hydratation ; au rendu serveur ils restent à
 * `false`. Le palier CRITIQUE du bootstrap, lui, est résolu en SSR (cf. `useAppBootstrap`) — ce
 * plugin ne gouverne que le différé (palier 3, préchargement N+1, flux SSE).
 *
 * Écoute et minuterie via VueUse (`useEventListener`, `useTimeoutFn`) plutôt que
 * `window.addEventListener` brut : SSR-safe par construction, retrait porté par la fonction `stop`
 * rendue, sans bookkeeping manuel.
 */
type IdleDeadlineLike = {
  didTimeout: boolean
  timeRemaining: () => number
}

type IdleRequestCallback = (deadline: IdleDeadlineLike) => void

const RUNTIME_IDLE_TIMEOUT_MS = 1200
const PASSIVE_FALLBACK_MS = 16000

// Les signaux qui prouvent que l'utilisateur est présent et engagé.
const PASSIVE_EVENTS = ['pointerdown', 'keydown', 'scroll', 'wheel', 'touchstart']

// Deux frames : la première laisse le navigateur peindre, la seconde garantit qu'on est bien
// après ce paint avant d'ordonnancer le différé.
const afterTwoFrames = (callback: () => void): void => {
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      callback()
    })
  })
}

const runInIdle = (callback: () => void): void => {
  const requestIdleCallbackFn = (
    window as Window & {
      requestIdleCallback?: (cb: IdleRequestCallback, opts?: { timeout: number }) => number
    }
  ).requestIdleCallback

  if (typeof requestIdleCallbackFn === 'function') {
    requestIdleCallbackFn(() => callback(), { timeout: RUNTIME_IDLE_TIMEOUT_MS })
    return
  }

  // Safari n'a pas `requestIdleCallback` : repli temporel court.
  setTimeout(callback, 200)
}

export default defineNuxtPlugin((nuxtApp) => {
  const runtimeReady = useState<boolean>('deferred.runtime.ready', () => false)
  const passiveReady = useState<boolean>('deferred.passive.ready', () => false)
  const pageFinished = useState<boolean>('runtime.page-finished', () => false)

  // Filet temporel : `passiveReady` s'ouvre même sans aucune interaction. `immediate: false` →
  // la minuterie ne court qu'une fois armée (à `runtimeReady`, cf. armPassiveGate).
  const { start: armFallback, stop: stopFallback } = useTimeoutFn(
    () => markPassiveReady(),
    PASSIVE_FALLBACK_MS,
    { immediate: false },
  )

  // `stop` des écouteurs d'interaction, rendu par `useEventListener` : un seul appel les retire
  // tous. `null` tant qu'ils ne sont pas armés.
  let stopPassiveListeners: (() => void) | null = null

  const markPassiveReady = (): void => {
    if (passiveReady.value) return
    passiveReady.value = true

    stopFallback()
    stopPassiveListeners?.()
    stopPassiveListeners = null
  }

  const armPassiveGate = (): void => {
    if (passiveReady.value) return

    if (!stopPassiveListeners) {
      stopPassiveListeners = useEventListener(window, PASSIVE_EVENTS, () => markPassiveReady(), {
        once: true,
        passive: true,
      })
    }

    armFallback()
  }

  const scheduleRuntimeReady = (): void => {
    afterTwoFrames(() => {
      runInIdle(() => {
        runtimeReady.value = true
        armPassiveGate()
      })
    })
  }

  // Chaque navigation ré-arme le cycle : la page suivante a son propre palier post-rendu.
  nuxtApp.hook('page:start', () => {
    pageFinished.value = false
    runtimeReady.value = false
  })

  nuxtApp.hook('page:finish', () => {
    pageFinished.value = true
    scheduleRuntimeReady()
  })

  // Filet pour le tout premier affichage, si `page:finish` n'est pas encore passé à ce stade.
  onNuxtReady(() => {
    if (!pageFinished.value) {
      pageFinished.value = true
    }
    scheduleRuntimeReady()
  })
})
