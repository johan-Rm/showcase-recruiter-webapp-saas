<script setup lang="ts">
// Autocomplete sans cadre, assorti à UiInlineInput : un champ souligné qu'on ouvre pour voir toute la
// liste FERMÉE, ou dans lequel on tape le début d'un secteur pour la filtrer. Pattern combobox ARIA
// (input éditable + listbox), navigable au clavier.
//
// Il ne se nomme PAS lui-même : c'est le `<label for>` de l'appelant qui le fait, comme l'input.
//
// Options en deux formes (cf. SelectOption) : une chaîne (libellé = valeur, secteurs) ou un couple
// { label, value } (affiché ≠ stocké — pays : « Maroc » affiché, « MA » stocké). Normalisées en interne.
import type { SelectOption } from '#shared/types/select'

interface Props {
  /** Lie le `<label for>` de l'appelant. */
  id: string
  options: readonly SelectOption[]
  placeholder?: string
  /** Aide de l'appelant, portée par le contrôle : c'est lui que le lecteur d'écran annonce. */
  describedBy?: string
  /** Affiché quand la saisie ne correspond à aucune option. */
  emptyLabel?: string
  /**
   * La valeur qui signifie « aucun choix » : son libellé sert DANS LA LISTE — il faut bien pouvoir
   * revenir en arrière — mais le champ, lui, reste vide. Un filtre qu'on n'a pas posé n'a pas à
   * s'écrire comme un filtre qu'on a posé.
   *
   * Non fournie, rien ne change : chaque valeur affiche son libellé, y compris la première.
   */
  blankValue?: string
}

const {
  id,
  options,
  placeholder = '',
  describedBy,
  emptyLabel = '',
  blankValue,
} = defineProps<Props>()

// La valeur STOCKÉE (le code pour un pays, le libellé lui-même pour un secteur).
const value = defineModel<string>({ required: true })

interface NormalizedOption {
  label: string
  value: string
}

// On ne manipule plus que { label, value } : une chaîne devient { label: s, value: s }.
const items = computed<NormalizedOption[]>(() =>
  options.map((option) => ('string' === typeof option ? { label: option, value: option } : option)),
)

// Le libellé à AFFICHER pour une valeur stockée — rien du tout pour le « aucun choix ».
const labelFor = (stored: string): string =>
  stored === blankValue ? '' : (items.value.find((item) => item.value === stored)?.label ?? '')

// Ce qu'on VOIT dans le champ : le libellé choisi au repos, la saisie en cours pendant le filtrage.
const query = ref('')
const open = ref(false)
// Option surlignée au clavier, indexée sur la liste FILTRÉE ; -1 quand la liste est fermée.
const active = ref(-1)
const root = ref<HTMLElement>()
const list = ref<HTMLElement>()

const listId = `${id}-listbox`
const optionId = (index: number) => `${id}-option-${index}`

// Tant que la saisie égale la valeur choisie (on vient d'ouvrir), on montre TOUT : rouvrir un secteur
// déjà choisi ne doit pas masquer les autres. Dès qu'on tape autre chose, on filtre — « contient »
// et non « commence par », plus tolérant (« conseil » trouve « Conseil et audit »).
const filtered = computed<NormalizedOption[]>(() => {
  const q = query.value.trim().toLowerCase()
  if ('' === q || query.value === labelFor(value.value)) {
    return items.value
  }

  return items.value.filter((item) => item.label.toLowerCase().includes(q))
})

const openList = () => {
  open.value = true
  // On surligne l'option courante si elle est dans la liste filtrée, sinon la première.
  const current = filtered.value.findIndex((item) => item.value === value.value)
  active.value = current >= 0 ? current : 0
}

const choose = (item: NormalizedOption) => {
  value.value = item.value
  query.value = item.label
  open.value = false
  active.value = -1
}

const close = () => {
  open.value = false
  active.value = -1
  // Liste FERMÉE : une saisie qui correspond EXACTEMENT à un libellé le sélectionne ; sinon on
  // abandonne la saisie partielle et on rétablit le dernier libellé — le champ ne garde jamais un
  // texte invalide.
  const match = items.value.find((item) => item.label === query.value)
  if (match) {
    value.value = match.value
  } else {
    query.value = labelFor(value.value)
  }
}

// La valeur posée de l'extérieur (préremplissage, remise à zéro) se reflète dans le champ.
watch(value, (stored) => (query.value = labelFor(stored)), { immediate: true })

// Options arrivées après coup (liste chargée en asynchrone) : on rafraîchit le libellé affiché tant
// qu'on ne tape pas (panneau fermé).
watch(items, () => {
  if (!open.value) {
    query.value = labelFor(value.value)
  }
})

// L'option active suit le clavier : on la ramène dans la fenêtre de défilement du panneau.
watch(active, async (index) => {
  if (index < 0) {
    return
  }

  await nextTick()
  list.value?.querySelector(`#${CSS.escape(optionId(index))}`)?.scrollIntoView({ block: 'nearest' })
})

const onInput = () => {
  open.value = true
  // La frappe rebat les cartes : on repart du haut de la liste filtrée.
  active.value = 0
}

const onKeydown = (event: KeyboardEvent) => {
  if (!open.value) {
    // Fermé : une flèche ouvre. On ne touche à RIEN d'autre — c'est un champ texte, Espace et les
    // lettres doivent s'écrire.
    if ('ArrowDown' === event.key || 'ArrowUp' === event.key) {
      event.preventDefault()
      openList()
    }

    return
  }

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      active.value = Math.min(active.value + 1, filtered.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      active.value = Math.max(active.value - 1, 0)
      break
    case 'Enter':
      // Cette frappe VALIDE l'option : elle ne doit ni soumettre le formulaire (`preventDefault`) ni
      // REMONTER jusqu'à lui (`stopPropagation`). Sans l'arrêt, le `@keydown.enter` du formulaire
      // tenterait aussi d'avancer de groupe et déplacerait le focus sur le champ requis suivant.
      event.preventDefault()
      event.stopPropagation()
      if (active.value >= 0 && filtered.value[active.value]) {
        choose(filtered.value[active.value]!)
      }
      break
    case 'Escape':
      event.preventDefault()
      close()
      break
    case 'Tab':
      // On ne retient pas le focus : Tab ferme et laisse partir vers le champ suivant.
      close()
      break
  }
}

// Clic hors du composant : referme. `onClickOutside` (VueUse) écoute au `pointerdown` et ignore ce qui
// est DANS `root` — cliquer une option ne déclenche donc pas la fermeture avant la sélection.
onClickOutside(root, close)
</script>

<template>
  <div ref="root" class="ui-inline-select">
    <input
      :id="id"
      v-model="query"
      type="text"
      role="combobox"
      class="ui-inline-select__input"
      :placeholder="placeholder"
      :aria-expanded="open"
      aria-autocomplete="list"
      :aria-controls="open ? listId : undefined"
      :aria-activedescendant="open && active >= 0 ? optionId(active) : undefined"
      :aria-describedby="describedBy"
      autocomplete="off"
      @focus="openList"
      @input="onInput"
      @keydown="onKeydown"
    />

    <svg
      class="ui-inline-select__chevron"
      :class="{ 'ui-inline-select__chevron--open': open }"
      viewBox="0 0 12 12"
      aria-hidden="true"
    >
      <path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.4" />
    </svg>

    <ul v-if="open" :id="listId" ref="list" role="listbox" class="ui-inline-select__list">
      <li
        v-for="(item, index) in filtered"
        :id="optionId(index)"
        :key="item.value"
        role="option"
        :aria-selected="item.value === value"
        class="ui-inline-select__option"
        :class="{ 'ui-inline-select__option--active': index === active }"
        @click="choose(item)"
        @pointermove="active = index"
      >
        {{ item.label }}
      </li>

      <li v-if="0 === filtered.length" class="ui-inline-select__empty" role="presentation">
        {{ emptyLabel }}
      </li>
    </ul>
  </div>
</template>

<style scoped>
.ui-inline-select {
  position: relative;
}

/* Le champ reprend TRAIT POUR TRAIT UiInlineInput : filet neutre au repos, texte de la réponse en
   neutre, accent réservé au focus. L'œil ne doit pas distinguer une saisie libre d'un autocomplete.
   Réserve à droite pour le chevron. */
.ui-inline-select__input {
  width: 100%;
  padding: 0 1.4em 0 0.12em;
  border: 0;
  border-bottom: 1px solid var(--line);
  border-radius: 0;
  background: none;
  color: var(--text);
  font: inherit;
  transition:
    border-color var(--transition-fast),
    background-color var(--transition-fast);
}

.ui-inline-select__input::placeholder {
  color: var(--muted);
  opacity: 0.7;
}

.ui-inline-select__input:hover {
  border-bottom-color: color-mix(in srgb, var(--text) 40%, transparent);
}

.ui-inline-select__input:focus-visible {
  outline: none;
  border-bottom-color: var(--accent);
  background: color-mix(in srgb, var(--accent) 5%, transparent);
}

/* Décoratif : il indique l'ouverture, il ne se clique pas — le focus du champ ouvre, Échap / clic
   dehors ferment. `pointer-events: none` pour taper/sélectionner à travers. */
.ui-inline-select__chevron {
  position: absolute;
  inset-inline-end: 0.12em;
  top: 50%;
  width: 0.9em;
  height: 0.9em;
  color: var(--muted);
  pointer-events: none;
  transform: translateY(-50%);
  transition: transform var(--transition-fast);
}

.ui-inline-select__chevron--open {
  transform: translateY(-50%) rotate(180deg);
}

/* Le panneau flotte SOUS le champ, hors flux : il ne pousse rien, la page ne défile pas. Hauteur
   plafonnée, défilement interne — les secteurs ne tiennent pas tous d'un coup. */
.ui-inline-select__list {
  position: absolute;
  z-index: 10;
  /* LA LARGEUR SUIT LE CONTENU, pas le champ. Clouée à celle du champ, la liste tronquait ce
     qu'elle est censée faire lire — « Développeur l… », « Commercial te… ». Elle part donc du bord
     de lecture du champ, ne descend jamais sous sa largeur, et s'étend jusqu'à ce que ses intitulés
     tiennent. Le plafond évite qu'un libellé aberrant traverse l'écran. */
  inset-inline-start: 0;
  min-width: 100%;
  width: max-content;
  max-width: min(28rem, 90vw);
  top: calc(100% + 4px);
  max-height: 240px;
  overflow-y: auto;
  margin: 0;
  padding: 6px;
  list-style: none;
  background: var(--dropdown-bg);
  border-radius: var(--radius-lg);
  box-shadow: 0 12px 32px color-mix(in srgb, #000 28%, transparent);
}

.ui-inline-select__option {
  padding: 8px 10px;
  border-radius: var(--radius-md);
  color: var(--text);
  cursor: pointer;
}

/* Survol clavier ET souris passent par la MÊME classe : un seul état surligné, jamais deux. */
.ui-inline-select__option--active {
  background: var(--sl-hover);
}

/* L'option choisie se marque par l'accent, sans coche : la liste est courte, la couleur suffit. */
.ui-inline-select__option[aria-selected='true'] {
  color: var(--accent);
  font-weight: 600;
}

/* Aucun résultat : muet, non cliquable — un cul-de-sac annoncé, pas une option. */
.ui-inline-select__empty {
  padding: 8px 10px;
  color: var(--muted);
}
</style>
