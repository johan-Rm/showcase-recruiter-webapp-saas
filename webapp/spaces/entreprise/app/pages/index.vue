<script setup lang="ts">
import {
  activeCount,
  HOME_COLUMNS,
  homeCapacity,
  recentToResume,
} from '#services/search/collection'
import { fromAiCriteria } from '#services/search/criteria'
import type { SearchCriterion } from '#shared/types/search'

definePageMeta({ layout: 'cockpit' })
type Mode = 'list' | 'ai'
const mode = ref<Mode>('list')
const {
  searches,
  ownerName,
  create,
  setClosed,
  update,
  duplicate,
  remove: removeSearch,
  pending,
  error,
  loaded,
} = useSearches()

// Survoler, focaliser ou faire entrer une carte à l'écran met sa recherche en tête du
// chargement de fond : la page relaie ce que la carte annonce, la carte ne mute rien.
const { intend } = useSearchIntent()
const RECENT_SEARCHES = homeCapacity(HOME_COLUMNS.xwide)
const SHOWN_BASE = homeCapacity(HOME_COLUMNS.base)
const SHOWN_SM = homeCapacity(HOME_COLUMNS.sm)
const recentSearches = computed(() => recentToResume(searches.value, RECENT_SEARCHES))
const collectionCount = computed(() => activeCount(searches.value))
await loaded
if (!error.value && 0 === recentSearches.value.length) await navigateTo('/recherches/nouvelle')
const creating = ref(false)
const createErrorKey = ref('')
const actionErrorKey = ref('')
const cardActions = (search: StoredSearch) => ({
  onClosure: async (isClosed: boolean) => {
    actionErrorKey.value = await setClosed(search, isClosed)
  },
  onRename: async (name: string) => {
    actionErrorKey.value = await update(search, { name })
  },
  onDuplicate: async () => {
    actionErrorKey.value = await duplicate(search)
  },
  onShare: async (isShared: boolean) => {
    actionErrorKey.value = await update(search, { isShared })
  },
  onDelete: async () => {
    actionErrorKey.value = await removeSearch(search)
  },
})
const start = async (criteria: SearchCriterion[], name = '') => {
  createErrorKey.value = ''
  if (0 === criteria.length) {
    createErrorKey.value = 'search.create.noCriteria'
    return
  }
  creating.value = true
  try {
    await navigateTo(`/recherches/${(await create(criteria, name)).id}`)
  } catch {
    createErrorKey.value = 'search.create.error'
  } finally {
    creating.value = false
  }
}
const {
  criteria,
  pending: aiPending,
  errorKey: aiErrorKey,
  removed: aiRemoved,
  run,
  remove,
  reset,
} = useAiSearch()
const openAi = () => {
  reset()
  mode.value = 'ai'
}
const onLaunchAi = () => start(fromAiCriteria(criteria.value, aiRemoved.value))
</script>

<template>
  <div data-page="index" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-section="navigation">
      <span class="text-small text-muted">{{ $t(`search.guide.${mode}`) }}</span>
      <template v-if="'list' !== mode" #action>
        <UiButton type="button" variant="secondary" @click="mode = 'list'">{{
          $t('search.back')
        }}</UiButton>
      </template>
    </Gutter>

    <section
      data-section="content"
      class="relative flex min-h-0 flex-1 flex-col gap-block overflow-auto"
    >
      <h1 class="sr-only">{{ $t('home.entreprise.title') }}</h1>
      <p v-if="createErrorKey" class="text-center text-small text-alert" role="alert">
        {{ $t(createErrorKey) }}
      </p>
      <p v-if="actionErrorKey" class="text-center text-small text-alert" role="alert">
        {{ $t(actionErrorKey) }}
      </p>
      <div v-if="'list' === mode" class="flex flex-none flex-wrap justify-center gap-element">
        <UiButton to="/recherches/nouvelle">{{ $t('search.new') }}</UiButton>
        <UiButton type="button" variant="secondary" @click="openAi">{{
          $t('search.newAi')
        }}</UiButton>
      </div>
      <div class="flex min-h-0 flex-1 flex-col">
        <Transition name="zoom" mode="out-in">
          <SearchFiltersAi
            v-if="'ai' === mode"
            :filters="aiCriteriaToFilters(criteria, $t, aiRemoved)"
            :pending="aiPending"
            :error="aiErrorKey ? $t(aiErrorKey) : ''"
            @search="run"
            @remove="remove"
            @launch="onLaunchAi"
          />
          <p v-else-if="pending" class="m-auto text-muted">{{ $t('search.loading') }}</p>
          <UAlert v-else-if="error" color="error" variant="subtle" :title="$t('search.error')" />
          <div v-else class="flex min-h-0 flex-col gap-block overflow-auto">
            <ul class="grid gap-block sm:grid-cols-2 xwide:grid-cols-3">
              <li
                v-for="(search, index) in recentSearches"
                :key="search.id"
                :class="
                  index >= SHOWN_SM
                    ? 'hidden xwide:block'
                    : index >= SHOWN_BASE
                      ? 'hidden sm:block'
                      : ''
                "
              >
                <SearchCard
                  :search="search"
                  :owner-name="ownerName(search)"
                  v-bind="cardActions(search)"
                  @intent="intend(search.id)"
                />
              </li>
            </ul>
            <UButton
              v-if="collectionCount > SHOWN_BASE"
              to="/recherches"
              color="neutral"
              variant="ghost"
              class="self-center"
              :class="[
                collectionCount <= SHOWN_SM ? 'sm:hidden' : '',
                collectionCount <= RECENT_SEARCHES ? 'xwide:hidden' : '',
              ]"
            >
              {{ $t('search.seeAll', { count: collectionCount }) }}
            </UButton>
          </div>
        </Transition>
      </div>
    </section>
  </div>
</template>
