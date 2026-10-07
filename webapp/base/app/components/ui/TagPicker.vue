<script setup lang="ts">
// UNE RANGÉE DE TERMES, puis un bouton qui ouvre un panneau pour en ajouter.
//
// Assorti à `UiRemoteCombobox` — même panneau flottant, même patron ARIA de combobox, mêmes
// suggestions distantes — dont il ne diffère que par le multi-valué. Ce que Nuxt UI ne sait pas
// produire : `UInputMenu` en mode `multiple` place ses chips DANS le champ, et aucune valeur de
// `:ui` ne les en sort. La forme voulue les place AVANT le bouton, et n'affiche aucun champ tant
// qu'on n'ajoute pas.
//
// SANS DOMAINE, comme tout ce qui vit dans `ui/` : il ne sait pas ce qu'est une compétence, ne va
// pas chercher ses suggestions et ne traduit rien. Il reçoit une liste, il rend des gestes. Le
// debounce, le seuil et le « dernière réponse gagne » restent chez l'appelant, qui les partage
// déjà entre plusieurs champs.

/** Une suggestion : sa valeur, et une précision facultative affichée en regard. */
export interface TagSuggestion {
  value: string
  /**
   * Texte de droite, déjà formaté par l'appelant — un décompte, une provenance. Formaté CHEZ LUI
   * parce que le mettre ici obligerait ce composant sans domaine à connaître une clé de traduction
   * métier.
   */
  hint?: string
}

interface Props {
  /** Lie le `<label for>` de l'appelant, porté par le bouton — seul élément toujours présent. */
  id: string
  /**
   * Le bouton occupe toute la largeur et garde son libellé une fois des termes posés.
   *
   * La pastille compacte suppose une rangée qu'on complète ; le bloc, une colonne dont l'ajout est
   * le geste principal. Un bloc pleine largeur réduit à un `+` serait une grande boîte vide.
   */
  block?: boolean
  /**
   * Le panneau s'ouvre À CÔTÉ du bouton, du côté du texte, plutôt qu'en dessous.
   *
   * Sous le bouton, il recouvre les termes déjà posés — la liste qu'on est justement en train de
   * compléter. À côté, il recouvre ce qui est déjà rempli, et gagne la largeur qui manque à une
   * colonne étroite. Sans place à côté (une seule colonne), il prend l'écran : cf. `place()`.
   *
   * SA LARGEUR APPARTIENT À L'APPELANT, par `--tag-picker-beside-w`. Ce composant ignore ce qu'il
   * y a à côté de lui : la largeur à couvrir se déduit de la grille qui l'entoure, que seul
   * l'appelant connaît. À défaut, un plafond prudent, qui ne couvre rien de précis.
   */
  beside?: boolean
  items?: readonly TagSuggestion[]
  /** Une requête est en vol chez l'appelant. */
  loading?: boolean
  /** La dernière requête a échoué : la saisie reste possible, la liste ne peut rien promettre. */
  failed?: boolean
  /** En deçà, l'appelant n'interroge pas : le panneau se tait au lieu d'annoncer « aucun résultat ». */
  minChars?: number
  /** Aide de l'appelant, portée par le bouton. */
  describedBy?: string
  /** Sur le bouton : son libellé tant que la rangée est vide, son nom accessible toujours. */
  addLabel: string
  /** Nom accessible du bouton quand le panneau est ouvert — il devient une fermeture. */
  closeLabel: string
  /** Nom accessible du champ de recherche : il n'a pas de `<label>` visible. */
  searchLabel: string
  searchPlaceholder?: string
  loadingLabel: string
  emptyLabel: string
  /** Le service de suggestions ne répond pas. Distinct de « aucun résultat ». */
  unavailableLabel: string
  /**
   * Ce qu'on écrit sur une suggestion déjà retenue — « ajouté », ou rien.
   *
   * FACULTATIF, contrairement aux autres libellés : la coche suffit à dire l'état, ce mot ne fait que
   * l'appuyer. Un appelant qui n'en veut pas l'omet, plutôt que d'avoir à traduire une décoration.
   */
  addedLabel?: string
  /**
   * Libellés dépendant d'une valeur. Fonctions et non chaînes : l'interpolation appartient à
   * l'appelant, qui seul tient `$t` — un gabarit passé en chaîne se ferait interpoler ici.
   */
  removeLabel: (value: string) => string
  createLabel: (value: string) => string
  /**
   * Le geste qui pose d'un coup tout ce qui reste à prendre : son libellé, et la phrase annoncée
   * une fois qu'il a agi.
   *
   * FACULTATIFS, ET LIÉS : sans libellé, le bouton n'existe pas. Un appelant qui ne veut pas de
   * raccourci n'a rien à traduire, et le composant ne pose ni bouton ni annonce.
   *
   * L'ANNONCE N'EST PAS UNE DÉCORATION. C'est le seul geste du panneau dont l'effet ne se voit pas
   * là où on le déclenche : les termes apparaissent dans une autre zone, et le bouton disparaît au
   * moment du clic. Sans phrase, on l'active et on n'apprend rien.
   */
  addAllLabel?: (count: number) => string
  addedAnnouncement?: (count: number) => string
}

const {
  id,
  block = false,
  beside = false,
  items = [],
  loading = false,
  failed = false,
  minChars = 0,
  describedBy,
  addLabel,
  closeLabel,
  searchLabel,
  searchPlaceholder = '',
  loadingLabel,
  emptyLabel,
  unavailableLabel,
  addedLabel = '',
  removeLabel,
  createLabel,
  addAllLabel,
  addedAnnouncement,
} = defineProps<Props>()

const model = defineModel<string[]>({ required: true })

const emit = defineEmits<{
  /** À chaque frappe : c'est l'appelant qui débounce et qui interroge. */
  'update:term': [string]
}>()

const open = ref(false)
const query = ref('')
// Option surlignée au clavier, indexée sur `items` ; -1 quand rien ne l'est.
const active = ref(-1)

const root = ref<HTMLElement>()
const trigger = ref<HTMLButtonElement>()
const search = ref<HTMLInputElement>()
const list = ref<HTMLElement>()
const panel = ref<HTMLElement>()

/** Confort de lecture : au-delà, la liste défile plutôt que d'occuper l'écran. */
const PANEL_MAX_PX = 300

/** En deçà, le panneau n'est plus consultable : mieux vaut alors le poser de l'autre côté. */
const PANEL_MIN_PX = 200

/** Le seuil à partir duquel un écran tient deux colonnes — même valeur que le `lg` de Tailwind. */
const WIDE_QUERY = '(min-width: 64rem)'

/**
 * Où s'ouvre le panneau, et sur quelle hauteur.
 *
 * Quatre positions, mais un seul balisage : seules les classes changent. En dupliquer le contenu
 * pour chaque position ferait diverger le champ de recherche et la liste au premier correctif.
 *
 * `below` / `above` — le comportement par défaut. Le champ mots-clés est souvent le dernier de sa
 * carte et la coque de l'espace est en `h-dvh` sans défilement de page : à hauteur fixe et vers le
 * bas, le panneau se fait couper. Deux règles, dans cet ordre. **Il reste sous le champ** tant que
 * la place y est consultable : basculer pour sept pixels masquerait tout le formulaire déjà rempli.
 * **Il prend la hauteur disponible**, plafonnée — c'est ce qui fait qu'il ne dépasse jamais.
 *
 * `beside` / `fullscreen` — sur demande de l'appelant. À côté quand l'écran a deux colonnes, sinon
 * l'écran entier : sous le bouton, une colonne unique le rendrait aussi étroit qu'elle, et le
 * clavier virtuel mange déjà la moitié de la hauteur.
 *
 * Relevé à l'ouverture seulement : la mesure porte sur l'ancre et sur la fenêtre, pas sur un contenu
 * qui n'existe pas encore, et le panneau ne saute pas sous les yeux pendant la frappe.
 */
type Placement = 'below' | 'above' | 'beside' | 'fullscreen'

const PANEL_CLASS: Record<Placement, string> = {
  below: 'absolute inset-x-0 top-full z-10 mt-1 rounded-lg p-1.5',
  above: 'absolute inset-x-0 bottom-full z-10 mb-1 rounded-lg p-1.5',
  beside:
    'absolute inset-y-0 end-full z-10 me-element w-[var(--tag-picker-beside-w,min(24rem,45vw))] rounded-lg p-1.5',
  fullscreen: 'fixed inset-0 z-50 p-4',
}

const placement = ref<Placement>('below')
const panelMax = ref(PANEL_MAX_PX)

/**
 * L'ENTRÉE DU PANNEAU, et elle dépend de sa position.
 *
 * À côté, il GLISSE depuis le bouton vers le texte : le mouvement dit d'où il sort, là où une simple
 * apparition laisse chercher ce qui vient de changer. Le sens se lit en propriétés logiques — en
 * arabe, le panneau sort de l'autre bord, donc le glissement s'inverse avec lui.
 *
 * Ailleurs, un fondu : sous le champ, le panneau est déjà à sa place, et le faire coulisser
 * n'ajouterait qu'un déplacement à suivre.
 */
const TRANSITION = computed(() =>
  'beside' === placement.value
    ? {
        enterActive: 'transition duration-200 ease-out',
        leaveActive: 'transition duration-150 ease-in',
        hidden: 'translate-x-4 opacity-0 rtl:-translate-x-4',
      }
    : {
        enterActive: 'transition-opacity duration-150 ease-out',
        leaveActive: 'transition-opacity duration-100 ease-in',
        hidden: 'opacity-0',
      },
)

const isWide = useMediaQuery(WIDE_QUERY)

const panelId = `${id}-panel`
const listId = `${id}-listbox`
const optionId = (index: number) => `${id}-option-${index}`

const term = computed(() => query.value.trim())
const held = (value: string) => model.value.includes(value)

/** En deçà, le geste groupé ne fait gagner aucun clic et double la ligne qu'il surplombe. */
const MIN_GROUPED = 2

/** Ce qu'il reste à prendre, dédoublonné et dans l'ordre affiché — cf. `pendingTags`. */
const pending = computed(() =>
  pendingTags(
    items.map((item) => item.value),
    model.value,
  ),
)

/**
 * Le libellé du geste groupé, ou rien lorsqu'il n'a pas lieu d'être — appelant qui n'en veut pas,
 * liste trop courte, ou tout déjà pris. Un seul calcul décide de son existence, de son texte et de
 * sa place dans le parcours clavier : trois conditions séparées auraient fini par diverger.
 */
const addAllText = computed(() =>
  addAllLabel && pending.value.length >= MIN_GROUPED ? addAllLabel(pending.value.length) : '',
)

/** Ce qui vient d'être fait, à destination des lecteurs d'écran seuls. */
const announcement = ref('')

/** Le terme libre ne dépend pas du vocabulaire : la ligne d'ajout reste offerte même en panne. */
const showCreate = computed(
  () => '' !== term.value && !items.some((item) => item.value === term.value),
)

// Trois notes muettes, exclusives. « Aucun résultat » se tait sous le seuil : l'appelant n'a rien
// demandé, l'absence de liste n'y prouve rien.
const showEmpty = computed(
  () => !loading && !failed && 0 === items.length && term.value.length >= Math.max(minChars, 1),
)

/**
 * Les bords qui découpent réellement le panneau.
 *
 * Le premier ancêtre qui masque son débordement, ou la fenêtre à défaut. Mesurer contre la fenêtre
 * seule serait faux : ce composant est monté aussi bien dans une carte que dans un tiroir, et c'est
 * le conteneur qui décide de ce qu'on voit.
 */
const bounds = (el: HTMLElement): { top: number; bottom: number } => {
  for (let node = el.parentElement; node && node !== document.body; node = node.parentElement) {
    const { overflowX, overflowY } = getComputedStyle(node)

    if ('visible' !== overflowX || 'visible' !== overflowY) {
      const box = node.getBoundingClientRect()

      return { top: Math.max(box.top, 0), bottom: Math.min(box.bottom, window.innerHeight) }
    }
  }

  return { top: 0, bottom: window.innerHeight }
}

const setTerm = (value: string): void => {
  query.value = value
  emit('update:term', value)
}

const close = (): void => {
  open.value = false
  active.value = -1
  announcement.value = ''
  setTerm('')
}

const place = (): void => {
  if (beside && !isWide.value) {
    placement.value = 'fullscreen'

    return
  }

  // La rangée est l'ancre : le panneau, hors flux, ne compte pas dans sa boîte.
  const element = root.value

  if (!element) {
    return
  }

  const anchor = element.getBoundingClientRect()
  const { top, bottom } = bounds(element)

  if (beside) {
    // Aucune hauteur à calculer : le panneau épouse celle du bloc, du haut du bouton au bas de la
    // colonne. Il est donc DÉJÀ à sa taille définitive quand il s'ouvre — la liste se remplit
    // dedans, sans le faire grandir sous les yeux à chaque frappe.
    placement.value = 'beside'

    return
  }

  const dessous = bottom - anchor.bottom - 8
  const dessus = anchor.top - top - 8

  placement.value = dessous < PANEL_MIN_PX && dessus > dessous ? 'above' : 'below'
  panelMax.value = Math.min(
    PANEL_MAX_PX,
    Math.max('above' === placement.value ? dessus : dessous, 0),
  )
}

const toggle = async (): Promise<void> => {
  if (open.value) {
    close()

    return
  }

  open.value = true
  place()

  await nextTick()
  search.value?.focus()
}

/** Refermer en rendant la main au bouton — sinon le focus retombe sur le `<body>`. */
const dismiss = (): void => {
  close()
  trigger.value?.focus()
}

/**
 * Poser un terme, ou le reprendre.
 *
 * Depuis le panneau, recliquer une option posée la RETIRE : sans cela, la pastille pleine indiquerait
 * un état sur lequel on ne peut pas agir. Le panneau reste ouvert et la saisie se vide — on pose
 * rarement un seul terme, et fermer à chaque choix coûterait deux gestes par mot.
 */
const choose = (value: string): void => {
  model.value = held(value) ? model.value.filter((item) => item !== value) : [...model.value, value]

  // La saisie se vide SANS prévenir l'appelant : le prévenir lui ferait retirer ses suggestions, et
  // la pastille qui vient de se remplir disparaîtrait avec elles. Or c'est elle qui dit ce qui est
  // posé — et qui permet d'enchaîner un second terme de la même liste sans retaper.
  query.value = ''
  active.value = -1
  search.value?.focus()
}

/**
 * POSER D'UN COUP TOUT CE QUI RESTE À PRENDRE.
 *
 * La saisie n'est pas vidée : c'est elle qui tient la liste à l'écran, et l'effacer ferait
 * disparaître les lignes qui viennent de passer en coche — le contraire de ce qu'on veut montrer
 * après un geste qui en pose plusieurs.
 *
 * Le focus revient au champ parce que le bouton DISPARAÎT en agissant : il ne reste plus rien à
 * prendre. Sans reprise, le focus retombe sur le corps de page et le parcours clavier repart du
 * début de l'écran.
 */
const addAll = async (): Promise<void> => {
  const taken = pending.value

  if (0 === taken.length) {
    return
  }

  model.value = [...model.value, ...taken]
  announcement.value = addedAnnouncement?.(taken.length) ?? ''

  await nextTick()
  search.value?.focus()
}

/** Depuis la ligne d'ajout : un terme déjà posé ne fait rien — il ne se retire pas par cette porte. */
const create = (): void => {
  if ('' === term.value || held(term.value)) {
    return
  }

  choose(term.value)
}

const remove = async (value: string): Promise<void> => {
  model.value = model.value.filter((item) => item !== value)

  // Le bouton qui portait le focus vient de disparaître ; sans reprise, le focus retombe sur le
  // `<body>` et le parcours clavier recommence au début de la page.
  await nextTick()
  trigger.value?.focus()
}

// L'option active suit le clavier : on la ramène dans la fenêtre de défilement du panneau.
watch(active, async (index) => {
  if (index < 0) {
    return
  }

  await nextTick()
  list.value?.querySelector(`#${CSS.escape(optionId(index))}`)?.scrollIntoView({ block: 'nearest' })
})

const onKeydown = (event: KeyboardEvent): void => {
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      active.value = Math.min(active.value + 1, items.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      active.value = Math.max(active.value - 1, -1)
      break
    case 'Enter':
      // Cette frappe POSE un terme : ni soumission du formulaire qui entoure le champ, ni remontée
      // jusqu'à lui.
      event.preventDefault()
      event.stopPropagation()

      if (active.value >= 0 && items[active.value]) {
        choose(items[active.value]!.value)
      } else {
        create()
      }
      break
    case 'Escape':
      event.preventDefault()
      dismiss()
      break
    case 'Tab':
      // La tabulation quitte le panneau et le referme — SAUF quand le geste groupé est là, car il
      // est l'élément suivant. Sans cette exception, ce bouton n'existerait tout simplement pas au
      // clavier : le panneau se fermerait avant qu'on puisse l'atteindre.
      if ('' === addAllText.value || event.shiftKey) {
        close()
      }
      break
  }
}

// Clic hors du composant : referme sans rien poser, comme les deux autres comboboxes du kit.
onClickOutside(root, () => {
  if (open.value) {
    close()
  }
})
</script>

<template>
  <div ref="root" class="ui-tag-picker relative flex min-h-0 flex-col gap-element">
    <!-- L'EN-TÊTE APPARTIENT À L'APPELANT, mais il vit ICI : la racine devient alors toute la
         colonne, et le panneau latéral — calé sur elle — s'aligne exactement sur le filet qui la
         borde. Posé au-dessus du composant, il décalait ce panneau de la hauteur d'un titre. -->
    <slot name="header" />
    <!-- LE BOUTON, puis les termes posés. Ils passent à la ligne plutôt que de déborder — sur
         mobile, six mots-clés tiennent sur trois lignes, pas sur une barre.
         EN BLOC, le bouton COIFFE la liste et celle-ci DÉFILE sous lui : une colonne étroite en
         accueille vite une douzaine, et le bouton se dérobait alors vers le bas à chaque ajout.
         Coiffe, il ne bouge plus, et le menu s'ouvre en face de lui. C'est `min-h-0` qui rend la
         liste rétractable ; le bouton, lui, ne se rétracte jamais.
         Le bloc est en colonne SANS retour à la ligne : une colonne qui enroule déverse son
         débordement dans une seconde colonne, à droite, hors de la carte. C'est le défilement de la
         liste qui absorbe le trop-plein, pas un enroulement.
         LE BOUTON EST PREMIER DANS LE DOCUMENT, pour que le parcours au clavier suive ce qu'on voit
         plutôt que de traverser dix-huit croix de retrait avant d'atteindre l'ajout. La rangée
         compacte, elle, le REPLACE en fin de ligne par l'ordre d'affichage : c'est sa forme, et elle
         n'a qu'un décalage d'un élément entre lecture et tabulation. -->
    <div
      class="flex gap-element"
      :class="
        block
          ? 'min-h-0 w-full flex-1 flex-col flex-nowrap items-stretch'
          : 'flex-wrap items-center'
      "
    >
      <!-- IL DIT « AJOUTER », Y COMPRIS PENDANT QUE LE PANNEAU EST OUVERT. Le changer en croix
           obligerait son nom lu à basculer sur « fermer », et le texte affiché dirait alors autre
           chose que le texte annoncé — un écart qu'un lecteur d'écran ne peut pas réconcilier.
           Ouvert, il passe en couleur d'accent : il reste l'origine du panneau, pas sa sortie.
           REFERMER PASSE PAR TROIS PORTES : le recliquer, la touche d'échappement, le clic à
           l'extérieur. Le plein écran garde en plus sa croix, seule sortie quand le bouton passe
           dessous.
           Le libellé n'accompagne le `+` que tant que la rangée est vide — un `+` seul en permanence
           suppose qu'on connaît déjà l'écran, et le recruteur le découvre. En bloc, il ne s'efface
           jamais : cf. `block`. -->
      <button
        :id="id"
        ref="trigger"
        type="button"
        class="flex h-11 flex-none cursor-pointer items-center gap-1 rounded-md border transition-colors hover:border-accent hover:text-text focus-visible:border-accent focus-visible:text-text"
        :class="[
          block ? 'w-full justify-center px-4 text-small' : 'order-last px-3 text-small text-muted',
          open ? 'border-accent text-accent' : 'border-line',
          block && !open ? 'text-text' : '',
        ]"
        :aria-expanded="open"
        :aria-controls="panelId"
        :aria-label="addLabel"
        :aria-describedby="describedBy"
        @click="toggle"
      >
        <UIcon name="i-lucide-plus" class="h-4 w-4" />
        <span v-if="block || !model.length">{{ addLabel }}</span>
      </button>

      <ul
        v-if="model.length"
        class="flex flex-wrap items-center gap-element"
        :class="{ 'min-h-0 w-full overflow-y-auto': block }"
      >
        <li v-for="tag in model" :key="tag" class="min-w-0 max-w-full">
          <span
            class="flex items-center gap-1 rounded-md bg-accent-soft py-0.5 pe-0.5 ps-2.5 text-small text-text"
          >
            <span class="min-w-0 truncate">{{ tag }}</span>
            <button
              type="button"
              class="grid h-6 w-6 flex-none cursor-pointer place-items-center rounded text-muted transition-colors hover:text-text focus-visible:text-text"
              :aria-label="removeLabel(tag)"
              @click="remove(tag)"
            >
              <UIcon name="i-lucide-x" class="h-3.5 w-3.5" />
            </button>
          </span>
        </li>
      </ul>
    </div>

    <!-- LE FOND, en plein écran seulement : le bouton passe dessous, il faut une autre sortie que
         lui. Il est enfant de la racine, donc `onClickOutside` ne le voit pas — d'où son propre clic. -->
    <div
      v-if="open && 'fullscreen' === placement"
      class="fixed inset-0 z-40 bg-black/60"
      @click="dismiss"
    />

    <!-- LE PANNEAU, hors flux : en flux, il ferait sauter toute la carte à chaque ouverture.
         Le plafond de hauteur ne vaut que sous ou sur le champ : à côté et en plein écran, la
         position donne déjà la hauteur, et la liste défile dedans.
         LE FILET DE CONTOUR N'EST PAS DÉCORATIF. `--dropdown-bg` suit `--surface` — un menu se pose
         normalement sur le FOND DE PAGE, dont il se détache. Posé sur une CARTE, il tombe sur la
         même valeur qu'elle : mesuré #212121 des deux côtés en thème sombre, et l'ombre ne sépare
         pas sur un fond sombre (cf. `themes.css`). Le trait est la seule chose qui dessine alors le
         bord. Il vaut pour les deux thèmes : en clair le panneau est déjà plus clair que la carte,
         le filet ne fait que le fermer. -->

    <Transition
      :enter-active-class="TRANSITION.enterActive"
      :enter-from-class="TRANSITION.hidden"
      :leave-active-class="TRANSITION.leaveActive"
      :leave-to-class="TRANSITION.hidden"
    >
      <div
        v-if="open"
        :id="panelId"
        ref="panel"
        class="flex flex-col gap-1 overflow-hidden border border-line bg-dropdown shadow-lg"
        :class="PANEL_CLASS[placement]"
        :style="
          'below' === placement || 'above' === placement
            ? { maxHeight: `${panelMax}px` }
            : undefined
        "
      >
        <!-- En plein écran, le bouton qui a ouvert le panneau est caché derrière : sans cette
             sortie, il ne resterait que la touche d'échappement, absente d'un clavier virtuel. -->
        <button
          v-if="'fullscreen' === placement"
          type="button"
          class="-me-1 flex-none cursor-pointer self-end rounded-md p-2 text-muted transition-colors hover:text-text"
          :aria-label="closeLabel"
          @click="dismiss"
        >
          <UIcon name="i-lucide-x" class="h-5 w-5" />
        </button>

        <!-- LE CHAMP EST ENCADRÉ, ET CE CADRE NE SE RETIRE PAS ICI. L'argument qui le retire
             ailleurs — « le panneau est déjà l'objet qui se détache, un second contour dedans ne
             délimite rien de plus » — vaut d'un panneau étroit, où le champ occupe toute la place.
             Étendu à la largeur de la carte voisine, le champ flotte dans un grand vide et son bord
             d'arrivée ne se devine plus.
             LE CADRE S'ALLUME PLUTÔT QU'IL NE BRILLE EN PERMANENCE : le champ prend le focus à
             l'ouverture et le reprend après chaque terme posé, donc il est allumé presque tout le
             temps — mais il l'est parce qu'il a le focus, pas par décoration. L'anneau du navigateur
             reste écarté : il doublerait ce trait au même endroit. -->
        <div
          class="flex flex-none items-center gap-2 rounded-md border border-line px-3 transition-colors focus-within:border-accent"
        >
          <UIcon name="i-lucide-search" class="h-4 w-4 flex-none text-muted" aria-hidden="true" />
          <input
            ref="search"
            :value="query"
            type="text"
            role="combobox"
            class="w-full bg-transparent py-2.5 text-text placeholder:text-muted focus:outline-none"
            :placeholder="searchPlaceholder"
            :aria-label="searchLabel"
            :aria-expanded="true"
            aria-autocomplete="list"
            :aria-controls="listId"
            :aria-activedescendant="active >= 0 ? optionId(active) : undefined"
            autocomplete="off"
            @input="setTerm(($event.target as HTMLInputElement).value)"
            @keydown="onKeydown"
          />
        </div>

        <!-- LE GESTE GROUPÉ, ET IL VIT HORS DE LA LISTE. Celle-ci est déclarée comme une liste
             d'options ; un bouton dedans y serait annoncé comme une option de plus, et le contrat
             que les lecteurs d'écran lisent ne tiendrait plus. Posé juste au-dessus, il reste
             visuellement rattaché aux propositions qu'il désigne.
             SON LIBELLÉ PORTE LE NOMBRE plutôt que le mot « tout » : le vocabulaire servi est
             plafonné en amont, et « tout » désignerait ce qui est à l'écran en laissant croire à
             l'ensemble des variantes du vivier. -->
        <div v-if="addAllText" class="flex flex-none justify-end">
          <button
            type="button"
            class="cursor-pointer rounded-md px-3 py-1 text-small text-accent transition-colors hover:underline focus-visible:underline"
            @click="addAll"
            @keydown.tab.exact="close"
          >
            {{ addAllText }}
          </button>
        </div>

        <!-- CE QUI VIENT D'ÊTRE FAIT, pour ceux qui ne le voient pas. Toujours rendue tant que le
             panneau est ouvert, et vide au départ : une zone d'annonce apparue EN MÊME TEMPS que son
             texte n'est pas lue, le lecteur d'écran doit l'observer avant qu'elle change. -->
        <p role="status" aria-live="polite" class="sr-only">{{ announcement }}</p>

        <ul :id="listId" ref="list" role="listbox" class="min-h-0 flex-1 overflow-y-auto">
          <li v-if="loading" class="px-3 py-2 text-small text-muted" role="presentation">
            {{ loadingLabel }}
          </li>

          <li v-else-if="failed" class="px-3 py-2 text-small text-muted" role="presentation">
            {{ unavailableLabel }}
          </li>

          <template v-else>
            <li
              v-for="(item, index) in items"
              :id="optionId(index)"
              :key="item.value"
              role="option"
              :aria-selected="held(item.value)"
              class="flex cursor-pointer items-center gap-3 rounded-md px-3 py-2.5"
              :class="{ 'bg-surface-soft': index === active }"
              @click="choose(item.value)"
              @pointermove="active = index"
            >
              <!-- LE MÊME CARRÉ DIT LES DEUX CHOSES : ce qui reste à prendre porte un `+`, ce qui est
                 pris porte une coche pleine. Il fait la taille d'une case à cocher parce qu'il faut
                 pouvoir balayer la liste entière d'un regard — à huit pixels, plein et vide se
                 distinguent mal, et rien n'annonce qu'une ligne se clique.
                 IL N'EST PAS UN BOUTON, et n'est donc pas un arrêt de tabulation : c'est la ligne
                 entière qui s'actionne. Vingt suggestions coûteraient sinon vingt tabulations pour un
                 geste déjà offert. -->
              <span
                class="grid h-5 w-5 flex-none place-items-center rounded border transition-colors"
                :class="
                  held(item.value)
                    ? 'border-accent bg-accent text-on-accent'
                    : 'border-line text-muted'
                "
                aria-hidden="true"
              >
                <UIcon
                  :name="held(item.value) ? 'i-lucide-check' : 'i-lucide-plus'"
                  class="h-3.5 w-3.5"
                />
              </span>
              <!-- L'intitulé PASSE À LA LIGNE plutôt que d'être coupé : une liste de suggestions
                 existe pour être lue, et « système de contrôle et d'acquisition de donnée… » ne
                 renseigne sur rien. Le panneau, lui, garde une largeur fixe — la faire suivre le
                 contenu le ferait sauter à chaque frappe. -->
              <span class="min-w-0 flex-1 text-text">{{ item.value }}</span>
              <!-- LA MENTION DOUBLE LA COCHE, ET C'EST VOULU : la coche se balaie sur la liste
                 entière, le mot se lit sur la ligne qu'on regarde. Elle est MUETTE pour les lecteurs
                 d'écran — la ligne porte déjà son état de sélection, qu'ils annoncent ; l'écrire une
                 seconde fois le ferait entendre en double. -->
              <span
                v-if="addedLabel && held(item.value)"
                class="flex-none text-small text-muted"
                aria-hidden="true"
              >
                {{ addedLabel }}
              </span>
              <span v-else-if="item.hint" class="flex-none text-small tabular-nums text-muted">
                {{ item.hint }}
              </span>
            </li>

            <li v-if="showEmpty" class="px-3 py-2 text-small text-muted" role="presentation">
              {{ emptyLabel }}
            </li>
          </template>

          <!-- LA LIGNE D'AJOUT SURVIT AUX TROIS NOTES, la panne comprise : le terme part tel quel dans
             la recherche plein texte, il ne dépend pas du vocabulaire. L'enfermer dans la branche
             « tout va bien » la faisait disparaître au moment où elle est le plus utile. -->
          <li
            v-if="showCreate"
            role="option"
            :aria-selected="false"
            class="flex cursor-pointer items-center gap-element rounded-md px-3 py-2 text-small text-text hover:bg-surface-soft"
            @click="create"
          >
            <UIcon name="i-lucide-plus" class="h-3.5 w-3.5 flex-none text-muted" />
            {{ createLabel(term) }}
          </li>
        </ul>
      </div>
    </Transition>
  </div>
</template>
