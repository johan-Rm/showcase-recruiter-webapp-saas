<script setup lang="ts">
// LE combobox à suggestions DISTANTES du kit, valeur unique, terme libre autorisé.
//
// Il ne cherche rien lui-même : l'appelant lui passe une liste et reçoit le terme frappé. C'est ce
// qui lui permet de servir aussi bien une autocomplétion d'adresse qu'un intitulé de poste — le
// composant tient la mécanique (débouncé par l'appelant, panneau flottant, combobox ARIA), le
// domaine reste dehors.
//
// Assorti à `UiInlineSelect` et `UiTagPicker` : même trait, même panneau, mêmes états surlignés.
// Il ne se nomme PAS lui-même : le `<label for>` de l'appelant le fait, comme les autres champs.

/** Une suggestion : sa valeur, et une mention facultative affichée en face — un décompte, un pays. */
export interface RemoteSuggestion {
  value: string
  hint?: string
}

interface Props {
  /** Lie le `<label for>` de l'appelant. */
  id: string
  /** Ce que la dernière réponse a ramené. L'appelant débounce et ordonne, pas le composant. */
  items?: RemoteSuggestion[]
  loading?: boolean
  placeholder?: string
  /** Aide de l'appelant, portée par le contrôle. */
  describedBy?: string
  /** En deçà, on n'interroge pas — et le panneau ne dit rien : on n'a pas cherché. */
  minChars?: number
  /** Affiché quand la recherche a été faite et n'a rien ramené. */
  emptyLabel?: string
  /** Affiché pendant la recherche. */
  loadingLabel?: string
}

const {
  id,
  items = [],
  loading = false,
  placeholder = '',
  describedBy,
  minChars = 2,
  emptyLabel = '',
  loadingLabel = '',
} = defineProps<Props>()

/** La valeur retenue — le terme libre fait foi tant qu'aucune suggestion n'est choisie. */
const model = defineModel<string>({ required: true })

const emit = defineEmits<{
  /** Le terme frappé, à chaque frappe : c'est l'appelant qui décide quoi en faire. */
  'update:term': [string]
  /** Une suggestion a été choisie — distinct de la frappe libre, qui n'émet que `update:term`. */
  select: [RemoteSuggestion]
}>()

const query = ref(model.value)
const open = ref(false)
// Option surlignée au clavier, indexée sur la liste ; -1 quand rien n'est surligné.
const active = ref(-1)
const root = ref<HTMLElement>()
const list = ref<HTMLElement>()

const listId = `${id}-listbox`
const optionId = (index: number) => `${id}-option-${index}`

// Le modèle posé de l'extérieur (préremplissage, remise à zéro) se reflète dans le champ.
watch(model, (value) => {
  if (value !== query.value) {
    query.value = value
  }
})

/**
 * Le terme libre EST la valeur.
 *
 * Le cadrage l'exige : « l'input fait office de recherche libre ». Attendre une sélection ferait
 * perdre un intitulé que le vocabulaire ne connaît pas encore — et le vivier en produit tous les
 * jours.
 */
const onInput = () => {
  open.value = true
  active.value = -1
  model.value = query.value
  emit('update:term', query.value)
}

const choose = (suggestion: RemoteSuggestion) => {
  open.value = false
  active.value = -1
  query.value = suggestion.value
  model.value = suggestion.value
  emit('select', suggestion)
}

const close = () => {
  open.value = false
  active.value = -1
}

// L'option active suit le clavier : on la ramène dans la fenêtre de défilement du panneau.
watch(active, async (index) => {
  if (index < 0) {
    return
  }

  await nextTick()
  list.value?.querySelector(`#${CSS.escape(optionId(index))}`)?.scrollIntoView({ block: 'nearest' })
})

const onKeydown = (event: KeyboardEvent) => {
  if (!open.value || 0 === items.length) {
    return
  }

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      active.value = Math.min(active.value + 1, items.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      active.value = Math.max(active.value - 1, 0)
      break
    case 'Enter':
      // Cette frappe VALIDE la suggestion : ni soumission du formulaire, ni remontée jusqu'à lui —
      // sinon le formulaire se soumettrait en choisissant une option.
      if (active.value >= 0 && items[active.value]) {
        event.preventDefault()
        event.stopPropagation()
        choose(items[active.value]!)
      }
      break
    case 'Escape':
      event.preventDefault()
      close()
      break
    case 'Tab':
      close()
      break
  }
}

// Clic hors du composant : referme (comme UiInlineSelect, au `pointerdown`).
onClickOutside(root, close)

const hasResults = computed(() => items.length > 0)

/**
 * « Aucun résultat » ne se dit qu'APRÈS avoir cherché.
 *
 * Sous le seuil, aucune requête ne part : la liste est vide parce qu'on n'a pas demandé, pas parce
 * qu'il n'y a rien. C'est le défaut d'`UInputMenu` : il propose « Create "c" » dès la première
 * lettre, en lisant sa propre liste vide comme une absence de résultat.
 */
const searched = computed(() => query.value.trim().length >= minChars)
const showEmpty = computed(() => !loading && !hasResults.value && searched.value)

// Le panneau ne se REND que s'il a quelque chose à montrer : recherche en cours, résultats, ou
// « aucun résultat ». Ouvert au focus mais vide, il n'afficherait qu'une boîte creuse.
const panelOpen = computed(() => open.value && (loading || hasResults.value || showEmpty.value))
</script>

<template>
  <div ref="root" class="ui-remote-combobox">
    <input
      :id="id"
      v-model="query"
      type="text"
      role="combobox"
      class="ui-remote-combobox__input"
      :placeholder="placeholder"
      :aria-expanded="panelOpen"
      aria-autocomplete="list"
      :aria-controls="listId"
      :aria-activedescendant="panelOpen && active >= 0 ? optionId(active) : undefined"
      :aria-describedby="describedBy"
      autocomplete="off"
      @focus="open = true"
      @input="onInput"
      @keydown="onKeydown"
    />

    <ul v-if="panelOpen" :id="listId" ref="list" role="listbox" class="ui-remote-combobox__list">
      <li v-if="loading" class="ui-remote-combobox__note" role="presentation">
        {{ loadingLabel }}
      </li>

      <template v-else>
        <li
          v-for="(suggestion, index) in items"
          :id="optionId(index)"
          :key="suggestion.value"
          role="option"
          :aria-selected="index === active"
          class="ui-remote-combobox__option"
          :class="{ 'ui-remote-combobox__option--active': index === active }"
          @click="choose(suggestion)"
          @pointermove="active = index"
        >
          <span class="truncate">{{ suggestion.value }}</span>
          <span v-if="suggestion.hint" class="ui-remote-combobox__hint">{{ suggestion.hint }}</span>
        </li>

        <li v-if="showEmpty" class="ui-remote-combobox__note" role="presentation">
          {{ emptyLabel }}
        </li>
      </template>
    </ul>
  </div>
</template>

<style scoped>
.ui-remote-combobox {
  position: relative;
}

/* Reprend TRAIT POUR TRAIT UiInlineInput / UiInlineSelect : filet neutre au repos, texte neutre,
   accent réservé au focus. L'œil ne distingue pas une saisie libre d'une recherche distante. */
.ui-remote-combobox__input {
  width: 100%;
  padding: 0 0.12em;
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

.ui-remote-combobox__input::placeholder {
  color: var(--muted);
  opacity: 0.7;
}

.ui-remote-combobox__input:hover {
  border-bottom-color: color-mix(in srgb, var(--text) 40%, transparent);
}

.ui-remote-combobox__input:focus-visible {
  outline: none;
  border-bottom-color: var(--accent);
  background: color-mix(in srgb, var(--accent) 5%, transparent);
}

/* Panneau flottant, hors flux : il ne pousse rien. Hauteur plafonnée, défilement interne. */
.ui-remote-combobox__list {
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

.ui-remote-combobox__option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 8px 10px;
  border-radius: var(--radius-md);
  color: var(--text);
  cursor: pointer;
}

/* Survol clavier ET souris passent par la MÊME classe : un seul état surligné. */
.ui-remote-combobox__option--active {
  background: var(--sl-hover);
}

/* La mention en face — un décompte de profils, un pays. Muette, elle ne se lit qu'au besoin. */
.ui-remote-combobox__hint {
  flex: none;
  color: var(--muted);
  font-variant-numeric: tabular-nums;
}

/* Recherche en cours / aucun résultat : muet, non cliquable. */
.ui-remote-combobox__note {
  padding: 8px 10px;
  color: var(--muted);
}
</style>
