<script setup lang="ts">
import { displayName } from '#services/search/criteria'
import { applyFrozenOrder, orderStack } from '#services/search/stack'

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
  favorites,
  dismissed,
  seenCount,
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

watch(
  () => candidates.value.map((candidate) => candidate.id).join(),
  () => freeze(),
  { immediate: true },
)

const changeOrder = async (next: StackOrder): Promise<void> => {
  const current = pile.value[index.value]?.id

  order.value = next
  freeze()
  await nextTick()

  const position = pile.value.findIndex((candidate) => candidate.id === current)
  if (position >= 0) stack.value?.scrollTo(position)
}

const position = computed(() => (pile.value.length ? Math.min(index.value + 1, total.value) : 0))
const criteria = computed(() => search.value?.criteria ?? [])

/**
 * LE PROFIL QUE L'ON REGARDE — le sujet du verdict de la colonne de droite, et de ses deux gestes.
 *
 * Il n'existe QUE pour la colonne : dans la pile, chaque fiche porte le sien et n'a rien à demander.
 * C'est aussi ce qui rend `useVisibleProfile` critique — il ne décide plus seulement de ce qui
 * s'affiche, mais de ce sur quoi on écrit.
 */
const current = computed(() => pile.value[index.value] ?? null)

const reviewing = ref(false)
const openReviewed = (): void => {
  reviewing.value = true
}
</script>

<template>
  <div data-page="search-viewer-v2" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-zone="gutter">
      <template #action>
        <SearchV2OrderPicker v-if="total" :order="order" @select="changeOrder" />
      </template>
    </Gutter>

    <ActionBar data-zone="actionbar">
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

      <!-- LES DEUX ENTRÉES VERS LES FILTRES, AU CENTRE : elles gardent la même abscisse
           quelle que soit la longueur du nom de la recherche, et c'est ce nom qui cède. DÉSACTIVÉES
           tant que rien ne les branche : un bouton d'apparence normale qui ne répond pas au clic se
           lit comme une panne. ABSENTES sur la recherche d'un collègue, qu'on ne modifie pas : la
           page ne déclare alors pas de centre, et la bande reprend sa forme à deux colonnes — rien
           ne vient prendre leur place.
           PRÉSENTES MÊME SANS RÉSULTAT, contrairement au rang et au fil : une recherche qui ne
           ramène personne est exactement le moment où l'on veut élargir ses critères. -->
      <template v-if="editable" #center>
        <UButton color="neutral" variant="outline" size="sm" disabled>
          {{ $t('search.console.primary') }}
        </UButton>
        <UButton color="neutral" variant="outline" size="sm" disabled>
          {{ $t('search.console.secondary') }}
        </UButton>
      </template>

      <!-- OÙ L'ON EN EST — le rang vit ICI et non dans la colonne de gauche, masquée sous 1024 px,
           où l'avancement n'existerait nulle part sur téléphone.
           SANS PROFIL, PAS DE RANG : « 0 / 0 » sur un écran vide n'apprend rien et se lit comme une
           panne. -->
      <template v-if="total" #end>
        <SearchV2Position :position="position" :total="total" />
      </template>
    </ActionBar>

    <!-- TROIS ZONES SUR GRAND ÉCRAN, et la pile est la seule qui défile : où l'on en est à gauche,
         les profils au centre, le verdict à droite. Les deux flancs sont IMMOBILES — c'est ce qui
         permet de trancher cent profils sans que le geste change de place.
         LE VERDICT N'ARRIVE QU'À `xl` : à 1024 px, trois colonnes de page laisseraient environ
         quatre cents pixels au profil lui-même. En dessous il retombe en pied de fiche, où il
         désigne à nouveau la fiche qui le porte. -->
    <section
      data-zone="content"
      class="grid min-h-0 flex-1 grid-cols-1 gap-element lg:grid-cols-[minmax(280px,3fr)_minmax(0,9fr)] xl:grid-cols-[minmax(280px,3fr)_minmax(0,9fr)_minmax(300px,3fr)]"
      :aria-label="$t('page.entreprise.viewer.title')"
    >
      <aside class="hidden min-h-0 min-w-0 lg:block" :aria-label="$t('search.console.title')">
        <SearchV2Console
          :total="total"
          :seen-count="seenCount"
          :loaded-count="pile.length"
          :dismissed-count="dismissed.length"
          :favorites="favorites"
          :table-to="`/recherches/${uuid}/table`"
          @review="openReviewed"
        />
      </aside>

      <section
        class="min-h-0 min-w-0"
        :aria-busy="pending"
        :aria-label="$t('search.viewer.stack.title')"
      >
        <SearchV2Stack
          ref="stack"
          :candidates="pile"
          :criteria="criteria"
          :editable="editable"
          :pending="pending"
          :error="Boolean(error)"
          @toggle-favorite="toggleFavorite"
          @dismiss="dismiss($event.id)"
          @seen="markSeen($event.id)"
          @reveal="revealOne"
          @visible="index = $event"
        />
      </section>

      <!-- LE VERDICT, posé dans la grille et non par-dessus la pile : il occupe sa colonne, la pile
           occupe la sienne, et il n'y a ni chevauchement ni largeur à compenser à la main. -->
      <aside
        class="hidden min-h-0 min-w-0 rounded-lg bg-surface p-block xl:block"
        :aria-label="$t('search.viewer.verdict.region')"
      >
        <SearchV2Verdict
          class="h-full"
          detached
          :candidate="current"
          :criteria="criteria"
          :editable="editable"
          @toggle-favorite="toggleFavorite"
          @dismiss="dismiss($event.id)"
        />
      </aside>
    </section>

    <SearchV2DismissedDrawer
      :open="reviewing"
      :dismissed="dismissed"
      @update:open="reviewing = $event"
      @restore="restore($event.id)"
    />
  </div>
</template>
