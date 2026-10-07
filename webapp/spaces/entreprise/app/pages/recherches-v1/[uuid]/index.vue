<script setup lang="ts">
import { confrontCriteria, displayName } from '#services/search/criteria'
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
const progress = computed(() =>
  total.value ? Math.round((seenCount.value / total.value) * 100) : 0,
)
const outcomes = computed(() =>
  confrontCriteria(search.value?.criteria ?? [], pile.value[index.value] ?? null),
)

const reviewing = ref(false)
const openReviewed = (): void => {
  reviewing.value = true
}
</script>

<template>
  <div data-page="search-viewer-v1" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-zone="gutter">
      <template #action>
        <SearchV1OrderPicker v-if="total" :order="order" @select="changeOrder" />
      </template>
    </Gutter>

    <SearchV1ActionBar data-zone="actionbar">
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

      <!-- OÙ L'ON EN EST — le rang à droite, le fil sur le bord bas de la bande. Ils vivent ICI et
           non dans la colonne de gauche, masquée sous 1024 px, où l'avancement n'existerait nulle
           part sur téléphone. Ils vont ENSEMBLE, l'un disant la position et l'autre la part déjà
           vue.
           SANS PROFIL, NI L'UN NI L'AUTRE : « 0 / 0 » et une jauge à zéro sur un écran vide
           n'apprennent rien et se lisent comme une panne. -->
      <template v-if="total" #end>
        <SearchV1Position :position="position" :total="total" />
      </template>

      <template v-if="total" #progress>
        <SearchV1Progress :progress="progress" />
      </template>
    </SearchV1ActionBar>

    <section
      data-zone="content"
      class="grid min-h-0 flex-1 grid-cols-1 gap-element lg:grid-cols-[minmax(280px,3fr)_minmax(0,9fr)]"
      :aria-label="$t('page.entreprise.viewer.title')"
    >
      <aside class="hidden min-h-0 min-w-0 lg:block" :aria-label="$t('search.console.title')">
        <SearchV1Console
          :total="total"
          :seen-count="seenCount"
          :loaded-count="pile.length"
          :dismissed-count="dismissed.length"
          :outcomes="outcomes"
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
        <SearchV1Stack
          ref="stack"
          :candidates="pile"
          :editable="editable"
          :pending="pending"
          :error="Boolean(error)"
          :has-criteria="Boolean(search?.criteria.length)"
          @toggle-favorite="toggleFavorite"
          @dismiss="dismiss($event.id)"
          @seen="markSeen($event.id)"
          @reveal="revealOne"
          @visible="index = $event"
        />
      </section>
    </section>

    <SearchV1DismissedDrawer
      :open="reviewing"
      :dismissed="dismissed"
      @update:open="reviewing = $event"
      @restore="restore($event.id)"
    />
  </div>
</template>
