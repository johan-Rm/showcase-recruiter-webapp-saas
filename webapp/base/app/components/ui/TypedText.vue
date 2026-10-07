<script setup lang="ts">
// Titre d'onboarding en DEUX LIGNES : la question (`lead`) posée d'emblée, sans effet, puis la
// consigne (`focus`) qui s'écrit en dessous, en accent. Le découpage guide le regard.
//
// L'effet est PUREMENT VISUEL. Le texte complet est rendu en `sr-only`, les couches animées en
// `aria-hidden` : sans cette séparation, un lecteur d'écran ré-annoncerait la phrase à chaque
// caractère et l'effet la rendrait illisible à qui en dépend.
//
// La hauteur du focus est réservée par un FANTÔME (le texte complet, invisible mais compté dans le
// flux) : le bloc parent étant centré, sans réserve il grandirait pendant la frappe et tout ce qui
// est au-dessus remonterait de la moitié.
interface Props {
  lead: string
  focus: string
  /** Silence avant que la frappe du focus commence. Sans lui, elle part pendant qu'on lit le lead. */
  delay?: number
}

const { lead, focus, delay = 0 } = defineProps<Props>()

const emit = defineEmits<{ done: [] }>()

// Vide au départ, y compris au rendu serveur : la couche visible se remplit à la frappe. Le contenu,
// lui, est servi par `sr-only` — rien n'est perdu si le JS n'arrive jamais.
const typedFocus = ref('')

// ~28 ms/caractère : au-delà on attend, en deçà on ne lit plus rien.
const STEP_MS = 28

let timer: ReturnType<typeof setTimeout> | undefined

const typeFocus = () => {
  const step = () => {
    if (typedFocus.value.length >= focus.length) {
      emit('done')

      return
    }

    typedFocus.value = focus.slice(0, typedFocus.value.length + 1)
    timer = setTimeout(step, STEP_MS)
  }

  step()
}

onMounted(() => {
  // La frappe est du mouvement : qui l'a refusée reçoit la consigne entière, tout de suite.
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    typedFocus.value = focus
    emit('done')

    return
  }

  timer = setTimeout(typeFocus, delay)
})

onBeforeUnmount(() => clearTimeout(timer))
</script>

<template>
  <div class="ui-typed-text">
    <span class="sr-only">{{ lead }} {{ focus }}</span>

    <!-- Ligne 1 : la question, posée d'emblée, sans effet. -->
    <span aria-hidden="true" class="ui-typed-text__line">{{ lead }}</span>

    <!-- Ligne 2 : la consigne qui s'écrit, en accent. Le fantôme réserve sa hauteur finale. -->
    <span aria-hidden="true" class="ui-typed-text__line ui-typed-text__focus">
      <span class="ui-typed-text__ghost">{{ focus }}</span>
      <span class="ui-typed-text__typed text-accent">{{ typedFocus }}</span>
    </span>
  </div>
</template>

<style scoped>
.ui-typed-text__line {
  display: block;
}

.ui-typed-text__focus {
  position: relative;
}

/* Réserve la place du focus complet ; jamais affiché, mais compté dans le flux — c'est lui qui fige
   la hauteur avant que la frappe commence. */
.ui-typed-text__ghost {
  visibility: hidden;
}

/* La portion tapée, posée par-dessus le fantôme. `inset-inline: 0` lui donne la même largeur, donc
   la même coupe de ligne : le texte tapé se cale exactement sur le fantôme. */
.ui-typed-text__typed {
  position: absolute;
  inset-block-start: 0;
  inset-inline: 0;
}
</style>
