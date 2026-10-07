<script setup lang="ts">
import { displayName } from '#services/search/criteria'
import { emptyRefineForm, isRefined, refine } from '#services/search/refine'
import { announceBroad } from '#services/search/card'
import { applyFrozenOrder, orderStack } from '#services/search/stack'
import { isRetained } from '#services/search/states'

import type { RefineForm } from '#services/search/refine'
import type { StackOrder } from '#services/search/stack'
import type { CandidateCard } from '#shared/types/candidate-card'
import type { RevealType } from '#shared/types/reveal'

definePageMeta({
  layout: 'cockpit',
  middleware: [
    (to, from) => {
      to.meta.pageTransition =
        '/recherches/nouvelle' === from.path ? { name: 'slide', mode: 'out-in' } : undefined
    },
  ],
})

const route = useRoute()
const uuid = computed(() => String(route.params.uuid))
const { t } = useLocale()
const { update } = useSearches()

const {
  search,
  candidates,
  total,
  matchCount,
  broadState,
  dismissed,
  seenCount,
  favoriteCount,
  dismissedCount,
  toggleFavorite,
  markSeen,
  dismiss,
  restore,
  pending,
  error,
  refresh,
} = useSearch(() => uuid.value)

const { reveal } = useReveal()
const revealing = ref(false)

const revealOne = async (candidate: CandidateCard, type: RevealType): Promise<void> => {
  if (revealing.value) return

  revealing.value = true
  try {
    await reveal([candidate.id], type)
    await refresh()
  } finally {
    revealing.value = false
  }
}

/**
 * LE CONSEIL « RECHERCHE LARGE », DIT UNE FOIS PUIS TU.
 *
 * Il vivait en bandeau permanent en haut de cet écran, où il prenait une centaine de pixels de
 * hauteur — sur l'écran même où la fiche du candidat se bat pour chacun d'eux. Il passe en
 * notification passagère : la place est rendue, le conseil est dit au moment où l'on arrive.
 *
 * UNE FOIS PAR RECHERCHE ET PAR ONGLET. `useSessionStorage` survit aux rechargements de page et
 * meurt avec l'onglet, ce qui est exactement ce que « session » veut dire pour un utilisateur. Côté
 * serveur il n'existe pas et rend sa valeur par défaut : la notification ne part donc jamais avant
 * que la page soit vivante, ce qui est aussi bien — un toast rendu en HTML ne s'annonce à personne.
 *
 * `watch` ET NON `watchEffect` : l'effet lit la mémoire et l'écrit, ce qui suffirait à le relancer
 * en boucle. Ce qui doit le déclencher est l'état de la recherche, pas le souvenir qu'on en garde.
 *
 * LA DÉCISION N'EST PAS ICI. Quoi annoncer, quoi oublier et quoi laisser intact vit dans la couche
 * pure, avec ses tests — cette page ne fait que fournir l'état et poser la notification.
 */
const announcedBroad = useSessionStorage<string[]>('search:broad-announced', [])
const toast = useToast()

onMounted(() =>
  watch(
    [broadState, uuid],
    ([state, id]) => {
      const decision = announceBroad(announcedBroad.value, id, state)

      announcedBroad.value = [...decision.announced]

      if (!decision.announce) return

      toast.add({
        color: 'warning',
        icon: 'i-lucide-triangle-alert',
        title: t('search.broad.title', { count: matchCount.value }),
        description: t('search.broad.body'),
      })
    },
    { immediate: true },
  ),
)

const editable = computed(() => true === search.value?.isOwn)
const name = computed(() =>
  search.value ? displayName(search.value, t) || t('search.card.unnamed') : '',
)

const rename = async (value: string) => {
  if (search.value) await update(search.value, { name: value })
}

const index = ref(0)
const stack = ref<{ scrollTo: (position: number) => void } | null>(null)
const order = ref<StackOrder>('unseen')
const frozenIds = ref<string[]>([])

const freeze = (): void => {
  frozenIds.value = orderStack(candidates.value, order.value).map((candidate) => candidate.id)
}

const pile = computed(() => applyFrozenOrder(candidates.value, frozenIds.value))

/**
 * L'AFFINAGE — un angle de lecture posé sur le jeu, jamais un critère.
 *
 * Volatil par construction : aucune écriture, aucune adresse, aucun stockage. Une adresse qui
 * porterait ces valeurs redeviendrait une recherche, et la frontière avec l'écran de modification
 * s'effacerait au premier lien partagé.
 */
const refineForm = ref<RefineForm>(emptyRefineForm())
const today = new Date()

const visible = computed(() => refine(pile.value, refineForm.value, today))

/**
 * LE JEU ARRIVE D'UN SEUL COUP, donc on regroupe à chaque fois qu'il change.
 *
 * Il ne change plus qu'en se rétrécissant — un profil écarté quitte la pile — ou en se repeignant
 * après un changement de critères. Dans les deux cas, il n'y a pas de lecture à protéger d'une page
 * qui s'insère : le gel se recalcule, et le curseur repart du haut de ce qui reste.
 */
watch(
  () => candidates.value.map((candidate) => candidate.id).join(),
  () => freeze(),
  { immediate: true },
)

/**
 * UN AFFINAGE POSÉ OU RETIRÉ ROUVRE LE PARCOURS, au premier profil de ce qu'il reste.
 *
 * Garder sa place quand le profil courant survit au filtre aurait été plus doux, mais il aurait
 * fallu expliquer pourquoi on bouge une fois sur deux. Repartir du début le dit sans ambiguïté.
 */
watch(refineForm, async () => {
  index.value = 0
  await nextTick()
  stack.value?.scrollTo(0)
})

const changeOrder = async (next: StackOrder): Promise<void> => {
  const current = pile.value[index.value]?.id

  order.value = next
  freeze()
  await nextTick()

  const position = pile.value.findIndex((candidate) => candidate.id === current)
  if (position >= 0) stack.value?.scrollTo(position)
}

/**
 * LE RANG SUIT L'AFFINAGE, l'avancement non.
 *
 * « 1 / 7 » décrit ce qu'on parcourt, et c'est ce que le rang sert à dire. Les compteurs de la
 * colonne — vus, retenus, écartés — restent ceux de la recherche entière : ce qu'on a vu, on l'a vu,
 * et les voir reculer parce qu'on filtre laisserait croire à une perte.
 */
const stackTotal = computed(() =>
  isRefined(refineForm.value) ? visible.value.length : total.value,
)
const position = computed(() =>
  visible.value.length ? Math.min(index.value + 1, stackTotal.value) : 0,
)
const criteria = computed(() => search.value?.criteria ?? [])

/**
 * LE PROFIL QUE L'ON REGARDE — le sujet du verdict de la colonne de droite, et de ses deux gestes.
 *
 * Il n'existe QUE pour la colonne : dans la pile, chaque fiche porte le sien et n'a rien à demander.
 * C'est aussi ce qui rend `useVisibleProfile` critique — il ne décide plus seulement de ce qui
 * s'affiche, mais de ce sur quoi on écrit.
 */
const current = computed(() => visible.value[index.value] ?? null)

/**
 * Le bloc porte l'anneau du profil retenu, et il faut donc que le profil existe : une pile vide, un
 * jeu qui n'a rien ramené, et `current` vaut `null`. Rien à border alors — et l'anneau se dit
 * ABSENT plutôt que de recevoir un profil qui n'existe pas.
 */
const currentIsRetained = computed(() => null !== current.value && isRetained(current.value))

const reviewing = ref(false)
const openReviewed = (): void => {
  reviewing.value = true
}
</script>

<template>
  <div data-page="search-viewer" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-zone="gutter">
      <template #action>
        <SearchOrderPicker v-if="total" :order="order" @select="changeOrder" />
      </template>
    </Gutter>

    <!-- SA DROITE EST UNE COLONNE, à l'aplomb du verdict — même rapport, même bord. La bande et la
         zone de travail se lisent alors comme deux étages d'une même grille, et non comme deux blocs
         qui se ressemblent. -->
    <ActionBar data-zone="actionbar" end-column>
      <!-- LE SURTITRE DIT CE QU'EST LA LIGNE DU DESSOUS. Un nom de recherche est souvent
           auto-généré depuis les critères — « Product Owner · scrum » se lit alors comme un
           intitulé de poste, et rien ne dit qu'on est DANS une recherche enregistrée.
           MASQUÉ AUX LECTEURS D'ÉCRAN : le titre porte déjà, en mention invisible, le nom de
           l'écran. Les deux ne disent pas la même chose — l'un nomme l'écran, l'autre nomme
           l'objet — mais les entendre tous les deux à chaque lecture du titre serait redondant.
           `text-label` porte le rôle : capitales, graisse et interlettrage d'une étiquette de
           section. SEUL LE CORPS est réécrit, et c'est une valeur à la main assumée — onze pixels
           est le plancher de l'échelle, et ce surtitre doit passer en dessous pour rester un
           filigrane plutôt qu'un titre. Les trois autres attributs du palier restent appariés.
           IL S'EFFACE PAR L'OPACITÉ, jamais en allégeant la graisse : la charte pose que les quatre
           attributs de ce palier ne valent qu'ensemble, et un petit corps espacé mais maigre cesse
           de se lire comme une étiquette pour redevenir du contenu. On le fond, on ne le change
           pas.
           EN ACCENT ET FONDU DE MOITIÉ : la teinte le rattache au produit plutôt qu'au gris des
           textes secondaires, et le fondu l'empêche de disputer l'attention au nom qu'il coiffe.
           L'accent à pleine opacité crierait ici — c'est la couleur du seul geste de décision de
           l'écran. -->
      <div class="flex min-w-0 flex-col">
        <span class="text-label text-[0.5625rem] text-accent opacity-40" aria-hidden="true">
          {{ $t('search.viewer.panel.current') }}
        </span>

        <h1 class="flex min-w-0 items-baseline font-display text-h3">
          <span class="sr-only">{{ $t('page.entreprise.viewer.title') }} ·</span>
          <SearchShellName
            :name="name"
            :editable="editable"
            :placeholder="$t('search.sentence.name.placeholder')"
            :label="$t('search.sentence.name.label')"
            :edit-label="$t('search.sentence.name.edit')"
            @rename="rename"
          />
        </h1>
      </div>

      <!-- PAS D'ENTRÉE VERS LES CRITÈRES ICI, et ce n'est pas un oubli : cet écran sert à CHOISIR
           parmi un jeu déjà constitué — on parcourt, on retient, on écarte. Changer les critères,
           c'est TROUVER, et ça se fait sur l'écran de modification de la recherche. Ce que la
           colonne de gauche propose n'en est pas : un affinage resserre l'angle de lecture sur le
           jeu déjà ramené, comme les vues de la Table, et ne survit pas à l'écran.

           OÙ L'ON EN EST — le rang vit ICI et non dans la colonne de gauche, masquée sous 1024 px,
           où l'avancement n'existerait nulle part sur téléphone.
           SANS PROFIL, PAS DE RANG : « 0 / 0 » sur un écran vide n'apprend rien et se lit comme une
           panne. La jauge tombe avec lui — une barre éteinte y dirait la même panne en plus grand.
           LA JAUGE PRÉCÈDE LE CHIFFRE : elle se balaie, il se lit. L'œil prend l'ordre de grandeur
           en passant et ne s'arrête sur le nombre que s'il le veut.
           ELLE POUSSE, LE RANG TIENT LE BORD : la colonne a une largeur, encore faut-il l'occuper —
           sinon deux cents pixels restent vides à droite du chiffre, et une colonne qu'on ne remplit
           pas se lit comme un contenu mal placé. La jauge prend tout ce qui reste, le rang ne bouge
           pas.
           L'ÉCART ENTRE LES DEUX EST FRANC : collés, la dernière case et le chiffre se lisent comme
           un seul objet, et on ne voit plus où le remplissage s'arrête. -->
      <template v-if="total" #end>
        <SearchProgress
          class="me-element min-w-0 flex-1"
          :position="position"
          :total="stackTotal"
        />
        <SearchPosition :position="position" :total="stackTotal" :match-count="matchCount" />
      </template>
    </ActionBar>

    <!-- LE CONSEIL, SOUS LA BANDE ET AU-DESSUS DU TRAVAIL — là où on le lit en arrivant, sans qu'il
         recouvre quoi que ce soit. Il n'empêche rien : la pile se parcourt exactement comme avant.
         Le nombre est celui des correspondances RÉELLES, pas la taille de la pile chargée. -->

    <!-- DEUX BLOCS SUR GRAND ÉCRAN : où l'on en est à gauche, et LE PROFIL — portrait et verdict
         réunis — à droite. Seule la pile défile ; ce qui l'encadre est immobile, et c'est ce qui
         permet de trancher cent profils sans que le geste change de place. -->
    <section
      data-zone="content"
      class="grid min-h-0 flex-1 grid-cols-1 gap-element lg:grid-cols-[minmax(280px,3fr)_minmax(0,12fr)]"
      :aria-label="$t('page.entreprise.viewer.title')"
    >
      <aside class="hidden min-h-0 min-w-0 lg:block" :aria-label="$t('search.console.title')">
        <!-- La page ASSEMBLE et relaie : elle passe l'affinage et reçoit sa mise à jour, comme
             pour les autres gestes de l'écran. La liaison bidirectionnelle vit dans la console. -->
        <SearchConsole
          :refine="refineForm"
          :seen-count="seenCount"
          :dismissed-count="dismissedCount"
          :favorite-count="favoriteCount"
          :table-to="`/recherches/${uuid}/table`"
          :cards="pile"
          :today="today"
          :refined-count="visible.length"
          @update:refine="refineForm = $event"
          @review="openReviewed"
        />
      </aside>

      <!-- LE PROFIL EN UN SEUL BLOC. Les coins et l'anneau vivent ICI et non sur la fiche : le
           portrait et son verdict sont deux colonnes d'une même carte. Ils ne partagent plus le même
           aplat — la colonne du verdict porte le sien, d'un cran plus creux —, mais aucun trait ne
           les sépare : c'est la profondeur qui les distingue.

           L'ANNEAU SUIT LE PROFIL QU'ON REGARDE, parce qu'il entoure un bloc qui ne défile pas.

           ⚠️ UN CONTOUR (`outline`) ET NON UN ANNEAU INTÉRIEUR, et c'est ce que le fond de la colonne
           impose : un anneau intérieur se peint SOUS les enfants, et la colonne, qui touche le bord
           droit au pixel près, l'effaçait sur trois de ses quatre côtés. Un contour se peint
           par-dessus tout, et son décalage négatif le ramène à l'intérieur du bord — sans rien
           décaler, puisqu'un contour ne prend jamais de place dans la mise en page.

           `overflow-hidden` pour que les coins rognent la pile, qui déborderait sinon par ses
           angles.

           LE VERDICT N'A SA COLONNE QU'À `xl` : à 1024 px, la partager laisserait environ quatre
           cents pixels au portrait. En dessous il retombe en pied de fiche, à l'intérieur du même
           aplat — l'effet est le même, la fiche est simplement plus haute que large. -->
      <div
        class="grid min-h-0 min-w-0 overflow-hidden rounded-lg bg-surface-soft/60 xl:grid-cols-[minmax(0,9fr)_minmax(360px,3fr)]"
        :class="currentIsRetained ? 'outline-2 -outline-offset-2 outline-accent' : ''"
      >
        <section
          class="min-h-0 min-w-0"
          :aria-busy="pending"
          :aria-label="$t('search.viewer.stack.title')"
        >
          <SearchStack
            ref="stack"
            :candidates="visible"
            :criteria="criteria"
            :editable="editable"
            :pending="pending"
            :error="Boolean(error)"
            :refined="isRefined(refineForm)"
            @clear-refine="refineForm = emptyRefineForm()"
            @toggle-favorite="toggleFavorite"
            @dismiss="dismiss($event.id)"
            @seen="markSeen($event.id)"
            @reveal="revealOne"
            @visible="index = $event"
          />
        </section>

        <!-- LE VERDICT, colonne du même bloc — et elle porte SON PROPRE FOND, d'un cran plus creux
             que la fiche. Les deux restent une seule carte, sans couture ni bordure : c'est la
             profondeur qui les distingue, pas un trait. Ce qu'on lit y gagne un ordre — le portrait
             est posé, le jugement est en retrait.
             DES TOKENS ET NON DES VALEURS : les trois paliers suivent le thème, et l'élévation s'y
             inverse. Sur fond sombre chaque marche assombrit, sur fond clair chaque marche éclaircit
             — dans les deux cas, elle recule. -->
        <aside
          class="hidden min-h-0 min-w-0 bg-surface p-block xl:block"
          :aria-label="$t('search.viewer.verdict.region')"
        >
          <SearchVerdict
            class="h-full"
            :candidate="current"
            :criteria="criteria"
            :editable="editable"
            @toggle-favorite="toggleFavorite"
            @dismiss="dismiss($event.id)"
          />
        </aside>
      </div>
    </section>

    <SearchDismissedDrawer
      :open="reviewing"
      :dismissed="dismissed"
      @update:open="reviewing = $event"
      @restore="restore($event.id)"
    />

    <!-- La feuille des critères, ouverte depuis la note en pied de fiche sous 1280 px. Posée ici et
         non dans la pile, pour la même raison que le tiroir : elle recouvre l'écran, et une feuille
         par fiche en ferait quatre-vingt-treize. Les critères viennent de la recherche, le profil
         qu'ils jugent vient du geste. -->
    <SearchCriteriaSheet :criteria="criteria" />

    <!-- Le tiroir du profil complet, ouvert depuis la fiche de chaque card. Posé ici et non dans la
         pile : il recouvre l'écran entier, et son état est partagé avec la Table. -->
    <ProfilePanel />
  </div>
</template>
