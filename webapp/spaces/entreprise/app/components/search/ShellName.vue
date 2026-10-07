<script setup lang="ts">
/**
 * Le nom de la recherche dans la barre haute — il se lit, et se corrige.
 *
 * IL SE LIT, IL NE SE SAISIT PAS, sauf quand on le décide. Un champ ouvert en permanence promettait
 * une saisie que presque personne ne fait : le nom arrive déjà écrit. Il s'affiche donc en clair, un
 * clic dessus ouvre la correction, et le champ ne se montre que là.
 *
 * LE MÊME GESTE PARTOUT — sur la recherche qu'on écrit comme sur celle qu'on parcourt. Ce qui change
 * est ce qu'en fait l'appelant : sur un brouillon il retient le nom, sur une recherche existante il
 * l'enregistre. Le composant ne sait pas laquelle des deux il sert, et n'a pas à le savoir.
 *
 * L'EFFET DE FRAPPE est PUREMENT VISUEL : tant que le nom se compose tout seul, il se réécrit à
 * chaque changement de critère en n'effaçant que ce qui a bougé. Le composant garde à tout instant
 * sa valeur finale pour qui le lit autrement qu'à l'œil, et le mouvement refusé la reçoit d'un coup.
 */
import { typingDelay, typingSteps } from '#services/search/typewriter'

interface Props {
  /** Le nom à afficher — composé depuis les critères, ou celui de la recherche ouverte. */
  name: string
  /** Une recherche partagée est en lecture seule : le nom n'est plus cliquable. */
  editable?: boolean
  placeholder?: string
  label?: string
  editLabel?: string
}

const { name, editable = true, placeholder = '', label = '', editLabel = '' } = defineProps<Props>()

const emit = defineEmits<{
  /** Le nom corrigé à la main. Vide = on rend la main à l'auto-nommage. */
  rename: [string]
}>()

const uid = useId()
const fieldId = `${uid}-name`

/** Ce que l'on VOIT — la valeur finale, ou l'une des étapes de sa réécriture. */
const shown = ref(name)

/** Le champ n'existe QUE pendant la correction — le reste du temps, le nom est du texte. */
const editing = ref(false)
const field = ref<{ $el: HTMLInputElement } | HTMLInputElement>()

const reduced = useMediaQuery('(prefers-reduced-motion: reduce)')

let steps: string[] = []
let cursor = 0

// La cadence s'adapte au nombre d'étapes pour que la réécriture tienne dans son budget de temps,
// quel que soit l'écart entre l'ancien nom et le nouveau.
const stepMs = ref(typingDelay(0))

const { pause, resume } = useIntervalFn(
  () => {
    const step = steps[cursor]
    cursor += 1

    if (undefined === step) {
      pause()

      return
    }

    shown.value = step
  },
  stepMs,
  { immediate: false },
)

/**
 * Rejouer la réécriture vers `target`.
 *
 * `useIntervalFn` et non `setInterval` : SSR-safe par construction, et arrêté au démontage du scope
 * sans bookkeeping — c'est la convention du dossier, et un verrou CI la tient.
 */
const rewrite = (target: string) => {
  pause()

  if (reduced.value) {
    shown.value = target

    return
  }

  steps = typingSteps(shown.value, target)
  cursor = 0

  if (0 === steps.length) {
    return
  }

  stepMs.value = typingDelay(steps.length)
  resume()
}

// Le nom a bougé : on le rejoue, sauf si on est en train de le corriger — écraser une saisie en
// cours serait le pire des services.
watch(
  () => name,
  (target) => {
    if (!editing.value) {
      rewrite(target)
    }
  },
)

const edit = async () => {
  editing.value = true

  // Ouvrir sans donner le focus obligerait à un second clic — et laisserait au clavier un champ
  // apparu de nulle part, sans curseur dedans.
  await nextTick()
  const el = field.value
  const input = el instanceof HTMLInputElement ? el : el?.$el

  input?.focus()
  input?.select()
}

const close = () => {
  if (!editing.value) {
    return
  }

  editing.value = false
  emit('rename', shown.value.trim())

  // Un champ vidé rend la main à l'auto-nommage : c'est l'appelant qui décide ce que devient le nom,
  // et il nous le renverra par la prop.
  shown.value = name
}

/**
 * Entrée VALIDE le nom et referme, elle ne lance pas la recherche.
 *
 * Sans cela, la touche remonterait au formulaire de l'écran : on croirait confirmer un nom, on
 * partirait chercher. C'est le même arbitrage que dans le combobox de la phrase, où Entrée choisit
 * une suggestion sans soumettre.
 */
const onEnter = (event: KeyboardEvent) => {
  event.preventDefault()
  event.stopPropagation()
  close()
}
</script>

<template>
  <span class="search-shell-name flex min-w-0 items-baseline gap-element">
    <!-- SANS NOM, RIEN — ni invite, ni cible à cliquer. Sur une recherche qui commence, le nom se compose des
         critères : tant qu'aucun n'est posé, il n'y a rien à nommer, et proposer de renommer ce qui
         n'existe pas encore occupait la place la plus visible de l'écran pour ne rien dire. Le bloc
         revient entier dès le premier critère. Les recherches déjà enregistrées portent toujours un
         nom : elles ne voient pas ce cas. -->
    <template v-if="editing">
      <label class="sr-only" :for="fieldId">{{ label }}</label>
      <!-- La sortie du champ referme la correction — sur le champ et non sur le bloc : le bouton
           disparaît en ouvrant, et sa disparition passerait pour une sortie. -->
      <UiInlineInput
        :id="fieldId"
        ref="field"
        v-model="shown"
        :placeholder="placeholder"
        :maxlength="120"
        @blur="close"
        @keydown.enter="onEnter"
        @keydown.escape="close"
      />
    </template>

    <template v-else-if="shown">
      <!-- LE NOM EST LE BOUTON : viser un crayon de seize pixels est un geste de précision, viser un
           titre ne l'est pas. La couleur ne bouge qu'au survol — un titre souligné ou coloré en
           permanence redeviendrait le champ de saisie qu'on vient justement de lui enlever. -->
      <button
        v-if="editable"
        type="button"
        class="flex min-w-0 cursor-pointer items-baseline text-start transition-colors hover:text-accent focus-visible:rounded-xs focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-accent"
        @click="edit"
      >
        <span class="truncate">{{ shown }}</span>
        <!-- SANS CRAYON : le nom EST la cible, et il est large. L'icône ne servait qu'à annoncer que
             le titre se clique — le pointeur en main et la couleur qui bouge au survol le disent
             aussi, sans ajouter d'objet à côté d'un nom qui peut être long. Ce que l'icône disait
             aux lecteurs d'écran, la mention ci-dessous le dit toujours. -->
        <span class="sr-only">{{ editLabel }}</span>
      </button>

      <span v-else class="truncate">{{ shown }}</span>
    </template>
  </span>
</template>
