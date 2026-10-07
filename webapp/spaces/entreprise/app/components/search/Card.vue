<script setup lang="ts">
// Carte d'une recherche dans la collection : ce qu'elle contient, où en est son lecteur, ce qu'elle
// a produit, et les deux portes vers ses résultats (cf. arborescence.md — parcourir et sélectionner
// sont deux routes sur le même jeu, pas deux onglets).
//
// Composant PASSIF : il reçoit une recherche déjà chargée, lit ce que le service en tire, et n'écrit
// rien lui-même : chaque geste du menu s'ANNONCE par un événement, la page l'exécute. Le seul état
// que la carte tient est celui de sa propre saisie, le temps d'un renommage.
//
// PAS de `UCard`, et c'est une exception assumée à « un écran neuf part de Nuxt UI »
// (`base/app/components/README.md`) : `UCard` EMPILE en-tête, corps et pied, alors que le rail est
// une COLONNE à côté du corps. Aucune valeur de `:ui` ne transforme un empilement en deux colonnes.
import { actionsFor, criteriaToShow, railOf } from '#services/search/card'
import { criterionText, displayName } from '#services/search/criteria'

import type { SearchAction } from '#services/search/card'
import type { StoredSearch } from '#shared/types/search'

type Props = {
  search: StoredSearch
  /**
   * Le nom de l'auteur, recomposé par l'appelant. Il ne voyage plus sur la recherche : dix
   * recherches du même collègue en garderaient dix copies, et le renommer les ferait toutes mentir.
   * Il vit dans le store des personnes, `search.ownerId` y renvoie.
   */
  ownerName?: string
  /**
   * Le niveau de titre du nom de la recherche, décidé par l'écran qui pose la carte.
   *
   * Une carte posée sous un intertitre de section descend d'un cran : sans ça, un lecteur d'écran
   * lirait les cartes comme des voisines du titre au lieu de son contenu, et la section
   * « Partagées avec moi » n'annoncerait rien de ce qu'elle contient.
   */
  headingLevel?: 2 | 3
  /**
   * Cette recherche ramène-t-elle plus de profils qu'on n'en trie raisonnablement ?
   *
   * Décidé par l'écran et non ici : le seuil vient de la configuration servie, que la carte n'a pas
   * à lire — elle reste passive.
   */
}

const { search, ownerName = '', headingLevel = 2 } = defineProps<Props>()

// Le composant reste PASSIF : il annonce l'intention, il ne mute pas l'état global
// (`base/app/components/README.md`). C'est la page qui appelle le composable.
const emit = defineEmits<{
  /** Retoucher les critères : la page ouvre l'écran, la carte ne navigue pas elle-même. */
  modify: []
  closure: [isClosed: boolean]
  rename: [name: string]
  duplicate: []
  share: [isShared: boolean]
  delete: []
  /**
   * « C'est celle-là que je vais ouvrir. » Annoncé au survol maintenu, au focus clavier, ou à
   * l'entrée à l'écran quand il n'y a pas de survol. La page en fait ce qu'elle veut — ici, mettre
   * la recherche en tête du chargement de fond.
   */
  intent: []
}>()

// Deux critères montrés, le reste compté : le rail prend de la largeur, et le nom auto dit déjà ce
// que ces étiquettes répéteraient.
const VISIBLE_CRITERIA = 2

// Le survol se teinte à 5 % et non aux 8 % du kit : cette valeur est calibrée pour de petits objets
// posés sur une surface — sur une carte entière, la même deviendrait un aplat.
const HOVER =
  'hover:-translate-y-px focus-within:-translate-y-px' +
  ' hover:bg-[color-mix(in_srgb,var(--accent)_5%,var(--surface))]' +
  ' focus-within:bg-[color-mix(in_srgb,var(--accent)_5%,var(--surface))]'

const { t } = useLocale()

const rail = computed(() => railOf(search))
const actions = computed(() => actionsFor(search))

// Le menu ne naît qu'à partir de DEUX entrées : en dessous, c'est un lien qu'on aurait caché
// derrière un clic de plus — c'est le cas de la carte d'un collègue, qui ne peut que dupliquer.
const hasMenu = computed(() => actions.value.length > 1)

// Renommer se fait SUR PLACE. Le titre porte le calque de clic qui couvre la carte : l'édition doit
// donc le retirer, sinon un clic dans le champ ouvrirait la recherche.
const renaming = ref(false)
const draft = ref('')

/**
 * Le menu rend le focus à son bouton déclencheur en se fermant — c'est le bon comportement pour
 * qui navigue au clavier. Mais quand il vient d'ouvrir le champ de renommage, ce retour ARRACHE le
 * focus au champ 100 ms après son apparition : le `blur` validait alors une saisie que personne
 * n'avait faite, et l'édition se refermait toute seule. On lui demande de ne pas reprendre le
 * focus dans ce seul cas.
 */
const keepFocusOnRename = (event: Event): void => {
  if (renaming.value) {
    event.preventDefault()
  }
}

const menuTrigger = useTemplateRef<{ $el: HTMLElement }>('menuTrigger')

const startRename = (): void => {
  draft.value = search.name
  renaming.value = true
}

/**
 * Sortir de l'édition rend le focus au bouton d'où le geste est parti — sinon il retombe sur le
 * document, et qui navigue au clavier repart du haut de la page. C'est la contrepartie du
 * `closeAutoFocus` neutralisé juste au-dessus : ce que le menu ne fait donc pas, on le fait ici.
 */
const endRename = (): void => {
  renaming.value = false
  menuTrigger.value?.$el?.focus()
}

const commitRename = (): void => {
  const wanted = draft.value.trim()

  endRename()

  if (wanted !== search.name.trim()) {
    emit('rename', wanted)
  }
}

// Clôturer garde ses libellés d'origine, sous `search.closure` : ils sont partagés avec le rail et
// le pli de la liste. `t()` rend la clé quand elle manque au lieu d'une valeur fausse — le repli ne
// peut donc pas se faire par un `||`, il s'écrit ici.
const LABEL_KEYS: Record<SearchAction, string> = {
  modify: 'search.manage.modify',
  rename: 'search.manage.rename',
  duplicate: 'search.manage.duplicate',
  share: 'search.manage.share',
  unshare: 'search.manage.unshare',
  close: 'search.closure.close',
  reopen: 'search.closure.reopen',
  delete: 'search.manage.delete',
}

// Le vrai risque n'est pas de supprimer sans réfléchir, c'est de supprimer la MAUVAISE ligne : la
// boîte répète donc le nom. Elle ne se défait pas — l'API supprime pour de bon, sans corbeille.
const confirming = ref(false)

const confirmDelete = (): void => {
  confirming.value = false
  emit('delete')
}

const run = (action: SearchAction): void => {
  const gestes: Record<SearchAction, () => void> = {
    modify: () => emit('modify'),
    rename: startRename,
    duplicate: () => emit('duplicate'),
    share: () => emit('share', true),
    unshare: () => emit('share', false),
    close: () => emit('closure', true),
    reopen: () => emit('closure', false),
    delete: () => {
      confirming.value = true
    },
  }

  gestes[action]()
}
/**
 * COMBIEN DE TEMPS IL FAUT VISER pour que ça compte.
 *
 * Sans délai, traverser une grille de six cartes désigne les six — et c'est la dernière traversée
 * qui gagne, jamais celle qu'on visait. Cent vingt millisecondes écartent le balayage tout en
 * restant bien en deçà des ~300 ms qu'il faut pour viser et cliquer : l'intention est acquise avant.
 */
const INTENT_MS = 120

const root = useTemplateRef<HTMLElement>('root')

const { start: aimAt, stop: stopAiming } = useTimeoutFn(() => emit('intent'), INTENT_MS, {
  immediate: false,
})

// `pointerenter` et non `mouseenter` : un stylet vise comme une souris, et le premier les couvre
// tous les deux. Le focus suit la même règle — qui navigue au clavier ne survole rien, et se
// verrait sinon privé du bénéfice.
useEventListener(root, ['pointerenter', 'focusin'], () => aimAt())
useEventListener(root, ['pointerleave', 'focusout'], () => stopAiming())

/**
 * AU DOIGT, IL N'Y A PAS DE SURVOL : c'est l'entrée de la carte à l'écran qui en tient lieu.
 *
 * Réservé aux écrans sans survol, sinon une grille visible d'un coup désignerait ses six cartes à
 * l'ouverture et la file n'en serait pas réordonnée mais brouillée. Le seuil de moitié écarte les
 * cartes qu'un défilement laisse seulement dépasser.
 */
const canHover = useMediaQuery('(hover: hover)')

useIntersectionObserver(
  root,
  ([entry]) => {
    if (!canHover.value && entry?.isIntersecting) emit('intent')
  },
  { threshold: 0.5 },
)

const criteria = computed(() => criteriaToShow(search, t, VISIBLE_CRITERIA))
const name = computed(() => displayName(search, t) || t('search.card.unnamed'))
</script>

<template>
  <!-- `bg-surface` et non le `bg-default` de Nuxt UI : le corps de la page n'a pas de surface, ce
       sont les cartes qui la portent — sur le fond de page, la carte disparaîtrait.

       Ni bordure, ni ombre, ni séparateur : ce qui délimite la carte est l'écart de teinte, et le
       rail le renforce. `overflow-hidden` fait suivre au rail l'arrondi de la carte.

       Une carte close ne prend PAS le survol : il n'y a plus rien à y faire. -->
  <article
    ref="root"
    class="search-card relative grid h-full grid-cols-[6.5rem_minmax(0,1fr)] overflow-hidden rounded-lg bg-surface transition duration-200 has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-2 has-[a:focus-visible]:outline-accent motion-reduce:transition-none motion-reduce:transform-none sm:grid-cols-[8.5rem_minmax(0,1fr)]"
    :class="rail.isClosed ? '' : HOVER"
  >
    <!-- LE RAIL. Sa teinte tient la carte à elle seule ; éteint, il dit que le travail est fini.
         Chiffres à chasse fixe et colonne à largeur fixe : sans ça, « 7 » et « 1 248 » tordraient
         l'alignement d'une carte à l'autre — et c'est cet alignement qui fait tout son intérêt. -->
    <div
      class="flex flex-col items-center justify-center gap-0.5 px-2.5 pb-4 pt-5 text-center tabular-nums"
      :class="rail.isClosed ? 'bg-surface-soft/50' : 'bg-surface-soft'"
    >
      <span class="font-display text-h2 leading-none" :class="{ 'text-muted': rail.isClosed }">
        {{ rail.count }}
      </span>
      <span class="text-[0.65rem] font-bold uppercase tracking-wider text-muted">
        {{ t('search.card.profiles', { count: rail.count }) }}
      </span>

      <!-- La nouveauté porte le vert SIGNATURE : « 12 nouveaux » n'est pas un état de profil, et
           les quatre teintes d'état sont réservées à ce qu'un profil EST. Les emprunter ici
           brouillerait un vocabulaire qui est propre aujourd'hui. -->
      <!-- Une recherche close n'attend plus rien : lui annoncer qu'elle n'a pas de nouveaux profils
           répondrait à une question que personne ne se pose. La clôture passe donc DEVANT. -->
      <span v-if="rail.isClosed" class="mt-2 text-small font-semibold text-muted">
        {{ t('search.card.closed') }}
      </span>
      <span
        v-else-if="0 < rail.newCount"
        class="mt-2 inline-flex items-center gap-1.5 whitespace-nowrap text-small font-bold text-accent"
      >
        <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true" />
        {{ t('search.card.newCount', { count: rail.newCount }) }}
      </span>
      <!-- RIEN QUAND IL N'Y A RIEN — mais la place reste prise.
           La ligne annonçait « pas de nouveaux » : sur une liste où la plupart des recherches n'ont
           rien de neuf, c'était la mention la plus répétée de l'écran et la moins utile. Elle disait
           aussi « la carte a fini de charger », argument tombé depuis que la liste remplace TOUTES
           ses cartes par un message d'attente : aucune n'est jamais rendue à moitié.
           La hauteur, elle, se réserve : sans elle, une carte sans nouveauté serait plus courte que
           sa voisine. `1lh` — une hauteur de ligne du palier qu'occupait le texte, et non une valeur
           en pixels qui se décalerait au premier changement d'échelle typographique.
           Rien à l'intérieur, et surtout aucun caractère de remplissage : une espace insécable se
           ferait annoncer par un lecteur d'écran, qui n'a rien à entendre ici. -->
      <span v-else class="mt-2 h-[1lh] text-small" />

      <div class="mt-auto w-full pt-3.5">
        <!-- L'avancement est PERSONNEL, les retenus appartiennent à la recherche. Sur la carte d'un
             collègue, les poser côte à côte ferait lire deux sujets dans une seule ligne : seul le
             second y figure. -->
        <!-- PAS `bg-muted` : ce nom appartient à Nuxt UI, où il désigne un FOND — et notre
             remappage le branche sur la surface du rail. Le remplissage se peignait donc dans la
             couleur de son propre support, invisible dans les deux thèmes, et la barre se lisait à
             l'envers. `text-muted` vise bien le gris du nuancier — c'est la seule forme que Nuxt
             UI remappe sur la bonne valeur. -->
        <div v-if="rail.progress" class="h-1 w-full overflow-hidden rounded-full bg-line">
          <span
            class="block h-full bg-current text-muted"
            :style="{ width: `${Math.round(rail.progress.ratio * 100)}%` }"
          />
        </div>

        <!-- Deux mesures de nature différente : « vus » est PERSONNEL et grandit à mesure qu'on
             travaille, « retenus » appartient à la recherche et se partage. Alignées dans une
             phrase, elles se lisaient comme deux moments d'une même progression. Le nombre passe
             au-dessus de son mot : on cherche « combien », le libellé n'est qu'une confirmation. -->
        <dl
          v-if="rail.progress"
          class="mt-2 grid grid-cols-2 text-[0.7rem] leading-tight text-muted"
        >
          <div class="flex flex-col items-center">
            <dd class="font-semibold">{{ rail.progress.seen }}</dd>
            <dt>{{ t('search.card.seen', { count: rail.progress.seen }) }}</dt>
          </div>
          <!-- Le trait est POINTILLÉ, et il n'est pas décoratif : plein, il redeviendrait une
               bordure, que le guide de marque écarte au profit d'une séparation par le fond. Le
               pointillé marque une frontière de lecture, pas une cloison. -->
          <div class="flex flex-col items-center border-s border-dashed border-line">
            <dd class="font-semibold">{{ rail.retained }}</dd>
            <dt>{{ t('search.card.retained', { count: rail.retained }) }}</dt>
          </div>
        </dl>
        <!-- Sur la carte d'un collègue, l'avancement personnel n'existe pas — et l'API ne le sert
             pas. Une colonne barrée d'un tiret dirait « information manquante » là où la vérité est
             « cette information n'existe pas pour vous ». Une seule colonne, donc, et pas de trait :
             un séparateur qui ne sépare rien est un trait décoratif. -->
        <p v-else class="mt-1.5 text-center text-[0.7rem] leading-tight text-muted">
          {{ t('search.card.retainedByTeam', { count: rail.retained }) }}
        </p>
      </div>
    </div>

    <!-- LE CORPS. -->
    <div class="flex min-w-0 flex-col gap-2.5 px-4 pb-3 pt-4">
      <!-- Le TITRE porte le lien, et son calque étend le clic à toute la carte. Faire de la carte
           elle-même un lien la ferait réciter tout son contenu à voix haute ; ici le lien s'annonce
           par le nom de la recherche, et l'anneau de focus se pose sur la carte entière. -->
      <div class="flex min-w-0 flex-1 items-start gap-2">
        <!-- En édition, le lien du titre CÈDE la place : son calque couvre toute la carte, et un
             clic dans le champ ouvrirait la recherche. -->
        <UInput
          v-if="renaming"
          v-model="draft"
          autofocus
          class="relative z-10 w-full"
          :placeholder="t('search.manage.renameHint')"
          @blur="commitRename"
          @keyup.enter="commitRename"
          @keyup.esc="endRename"
        />
        <!-- Le NIVEAU du titre vient de l'écran, sa TAILLE n'en dépend pas : `text-h3` est la même
             partout, c'est la place dans le plan du document qui change. -->
        <component :is="`h${headingLevel}`" v-else class="min-w-0 font-display text-h3">
          <NuxtLink
            :to="`/recherches/${search.id}`"
            class="text-current no-underline outline-none after:absolute after:inset-0 after:content-['']"
            :class="{ 'text-muted': rail.isClosed }"
          >
            {{ name }}
          </NuxtLink>
        </component>

        <!-- Le menu passe AU-DESSUS du calque du titre et arrête la propagation : sans quoi
             l'ouvrir ouvrirait la recherche. Toujours visible, jamais au seul survol — sur un écran
             tactile il n'y a pas de survol, et il deviendrait introuvable. -->
        <UDropdownMenu
          v-if="hasMenu"
          :items="[
            actions.map((action) => ({
              label: t(LABEL_KEYS[action]),
              onSelect: () => run(action),
            })),
          ]"
          :content="{ onCloseAutoFocus: keepFocusOnRename }"
          class="relative z-10 ms-auto flex-none"
        >
          <UButton
            ref="menuTrigger"
            color="neutral"
            variant="ghost"
            size="sm"
            icon="i-lucide-ellipsis"
            :aria-label="t('search.manage.menu', { name })"
            @click.stop
          />
        </UDropdownMenu>

        <!-- Un seul geste permis : un lien direct, pas un menu qu'il faut ouvrir pour y trouver
             une ligne. C'est le cas de la recherche d'un collègue. -->
        <UButton
          v-else
          color="neutral"
          variant="ghost"
          size="sm"
          icon="i-lucide-copy"
          class="relative z-10 ms-auto flex-none"
          @click.stop="run(actions[0]!)"
        >
          {{ t(LABEL_KEYS[actions[0]!]) }}
        </UButton>
      </div>

      <!-- L'appartenance et la clôture se disent en texte courant, pas en étiquette : elles
           qualifient la recherche, elles ne la classent pas. -->
      <p v-if="!search.isOwn" class="-mt-1.5 text-small text-muted">
        {{ t('search.card.sharedBy', { name: ownerName }) }}
      </p>
      <p v-else-if="rail.isClosed && search.closedAt" class="-mt-1.5 text-small text-muted">
        {{ t('search.card.closedOn', { date: $d(new Date(search.closedAt)), name: ownerName }) }}
      </p>
      <!-- Sans cette ligne, partager n'aurait AUCUN effet visible : le menu changerait de libellé,
           et rien d'autre. La bascule doit se relire sur la carte, pas dans le menu. -->
      <p v-else-if="search.isShared" class="-mt-1.5 text-small text-muted">
        {{ t('search.card.shared') }}
      </p>

      <!-- Fond plein et pas de bordure pour une étiquette ; l'inverse pour son absence. Le
           pointillé vide dit « ceci n'est pas une étiquette, c'est la place qu'elles auraient
           prise » — une convention qui se lit sans légende.

           Le trait n'est PAS `border-line` : mesuré à 1,05:1 sur fond sombre, l'encadré n'avait
           plus de contour du tout et ne se lisait que comme du texte gris. `--line` sépare deux
           blocs voisins, où la proximité fait déjà la moitié du travail ; ici le trait doit tenir
           SEUL une forme. D'où le gris de texte, atténué pour rester un contour et non un cadre. -->
      <p
        v-if="criteria.isEmpty"
        class="w-fit rounded-md border border-dashed border-[color-mix(in_srgb,var(--muted)_50%,transparent)] px-2 py-0.5 text-[0.75rem] text-muted"
      >
        {{ t('search.card.noKeywords') }}
      </p>
      <ul v-else class="flex flex-wrap gap-1.5">
        <li
          v-for="criterion in criteria.shown"
          :key="`${criterion.facet}-${criterion.value}`"
          class="rounded-md bg-line px-2 py-0.5 text-[0.75rem] text-muted"
        >
          {{ criterionText(criterion, t) }}
        </li>
        <li
          v-if="0 < criteria.hidden"
          class="rounded-md bg-line px-2 py-0.5 text-[0.75rem] text-muted"
        >
          {{ t('search.card.more', { count: criteria.hidden }) }}
        </li>
      </ul>

      <UModal v-model:open="confirming" :title="t('search.manage.deleteTitle', { name })">
        <template #body>
          <p class="text-muted">{{ t('search.manage.deleteBody') }}</p>
        </template>
        <template #footer>
          <div class="flex justify-end gap-2">
            <UButton color="neutral" variant="ghost" @click="confirming = false">
              {{ t('search.manage.cancel') }}
            </UButton>
            <UButton color="error" @click="confirmDelete">
              {{ t('search.manage.delete') }}
            </UButton>
          </div>
        </template>
      </UModal>

      <!-- La ligne d'actions FERME la carte, elle ne l'occupe pas. `relative` la fait passer
           au-dessus du calque du titre — sans lui, le lien recouvrirait les boutons. -->
      <div class="relative mt-auto flex flex-wrap items-center justify-end gap-1 pt-1.5">
        <UButton
          :to="`/recherches/${search.id}/table`"
          color="neutral"
          variant="ghost"
          size="sm"
          icon="i-lucide-star"
        >
          {{ t('page.entreprise.table.title') }}
        </UButton>
        <!-- Close, la carte perd son aplat accent : elle se consulte, elle ne réclame plus rien.
             L'accent est réservé à ce qui appelle une action. -->
        <UButton
          :to="`/recherches/${search.id}`"
          size="sm"
          icon="i-lucide-layers"
          :color="rail.isClosed ? 'neutral' : 'primary'"
          :variant="rail.isClosed ? 'ghost' : 'solid'"
        >
          {{ t('page.entreprise.viewer.title') }}
        </UButton>
      </div>
    </div>
  </article>
</template>
