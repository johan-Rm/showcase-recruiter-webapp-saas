<script setup lang="ts">
import { autoName, mergeWithPrimary, toPrimaryForm } from '#services/search/criteria'

import type { PrimarySearchForm } from '#services/search/criteria'
import type { SearchCriterion } from '#shared/types/search'

// RETOUCHER une recherche enregistrée — le même écran que la création, rouvert sur des critères qui
// existent déjà. Rien n'est dupliqué : la phrase, la colonne des mots-clés et le décompte sont les
// mêmes composants, seuls le point de départ et le geste final changent.
//
// La page ORCHESTRE : elle lit la recherche, dérive ce qui sera enregistré, décide et exécute.
definePageMeta({ layout: 'cockpit' })

const route = useRoute()
const uuid = computed(() => String(route.params.uuid))

const { t } = useLocale()
const { searches, update, pending, error } = useSearches()
const { total, pending: volumePending, failed: volumeFailed, count } = useSearchVolume()

const search = computed(() => searches.value.find((item) => item.id === uuid.value))

/**
 * ON N'OUVRE PAS CE QUE LE SERVEUR REFUSERAIT.
 *
 * L'écriture est réservée à l'auteur, et une recherche close n'accepte que d'être rouverte — même
 * règle que celle qui décide des gestes du menu (`actionsFor`). Le menu n'y mène donc pas, mais
 * l'adresse se tape : sans ce garde-fou, on remplirait un formulaire pour un refus.
 */
const editable = computed(() => Boolean(search.value?.isOwn) && !search.value?.closedAt)

/** Les critères enregistrés, relus dans la forme du formulaire. */
const initial = computed<PrimarySearchForm | undefined>(() =>
  search.value ? toPrimaryForm(search.value.criteria) : undefined,
)

/**
 * CE QU'ON ENREGISTRERA — dérivé, jamais semé.
 *
 * Tant que rien n'a été touché, ce sont les critères enregistrés ; dès la première frappe, ce que la
 * phrase a composé. Un `null` distingue « pas encore modifié » de « modifié pour aboutir à rien ».
 *
 * DÉRIVER PLUTÔT QUE SEMER n'est pas une préférence de style. La première version chargeait le
 * brouillon partagé par un effet ; le serveur rendait donc la page AVANT que l'effet ait tourné,
 * avec zéro critère, et le navigateur la remplissait ensuite. Le rendu des deux côtés divergeait, et
 * le bouton d'enregistrement restait éteint sur une recherche pourtant remplie — mesuré : quatre
 * critères en mémoire, bouton désactivé. Un calcul dérivé donne le même résultat des deux côtés.
 *
 * Ce brouillon partagé quitte donc cet écran : il appartient à la création, où il sert à traverser
 * la coque. Ici, la recherche est déjà la source.
 */
const edited = ref<SearchCriterion[] | null>(null)

const criteria = computed<SearchCriterion[]>(() => edited.value ?? search.value?.criteria ?? [])

/** Le nom corrigé à la main pendant cette visite ; `null` tant qu'on n'y a pas touché. */
const renamed = ref<string | null>(null)

/**
 * Le nom affiché. Une recherche nommée à la main garde le sien — sinon la barre haute annoncerait le
 * nom que les critères composeraient, pendant que le fil d'ariane afficherait le vrai. Une recherche
 * qui se nomme toute seule, elle, suit ses critères : c'est ce qu'on veut voir bouger en les
 * ajustant.
 */
const shownName = computed(() => {
  if (null !== renamed.value) {
    return renamed.value
  }

  const current = search.value

  if (!current) {
    return ''
  }

  return current.isAutoNamed ? autoName(criteria.value, t) : current.name
})

// Le décompte suit les critères, y compris au premier rendu : c'est un effet de bord assumé, il ne
// touche qu'un nombre affiché.
watch(criteria, (value) => count(value), { immediate: true })

const onChange = (filters: PrimarySearchForm) => {
  edited.value = mergeWithPrimary(search.value?.criteria ?? [], filters)
}

const rename = (value: string) => {
  renamed.value = value
}

const trail = computed(() => [
  { label: t('nav.entreprise.dashboard'), to: '/' },
  { label: search.value?.name || t('search.card.unnamed') },
])

const confirming = ref(false)
const saving = ref(false)
const errorKey = ref('')

/**
 * ON PRÉVIENT AVANT D'ÉCRIRE, et sans chiffre.
 *
 * Changer les critères relance la recherche : des profils déjà retenus ou écartés peuvent en sortir.
 * Ils ne sont pas perdus — ils gardent leur étiquette —, mais l'apprendre après coup serait une
 * mauvaise surprise. Le nombre exact demanderait de savoir QUELS profils un jeu de critères
 * ramènerait avant de l'enregistrer, ce que le serveur ne sait pas dire aujourd'hui.
 */
const ask = () => {
  errorKey.value = ''

  if (0 === criteria.value.length) {
    errorKey.value = 'search.create.noCriteria'

    return
  }

  confirming.value = true
}

const save = async () => {
  const current = search.value

  if (!current) {
    return
  }

  confirming.value = false
  saving.value = true

  // Une panne laisse l'écran en place AVEC la saisie : renvoyer à la liste ferait perdre le travail
  // sans que rien ne l'annonce.
  errorKey.value = await update(current, { criteria: criteria.value })
  saving.value = false

  if ('' === errorKey.value) {
    await navigateTo(`/recherches/${current.id}`)
  }
}
</script>

<template>
  <div data-page="recherches-uuid-modifier" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-zone="gutter">
      <Breadcrumb :items="trail" />

      <template #action>
        <p class="hidden items-center gap-1.5 text-small text-muted lg:flex">
          <UIcon name="i-lucide-info" class="h-4 w-4 flex-none" aria-hidden="true" />
          {{ $t('search.guide.edit') }}
        </p>
      </template>
    </Gutter>

    <ActionBar data-zone="actionbar">
      <h1 class="flex min-w-0 items-baseline font-display text-h3">
        <span class="sr-only">{{ $t('page.entreprise.editSearch.title') }} ·</span>
        <SearchShellName
          :name="shownName"
          :placeholder="$t('search.sentence.name.placeholder')"
          :label="$t('search.sentence.name.label')"
          :edit-label="$t('search.sentence.name.edit')"
          @rename="rename"
        />
      </h1>

      <template #end>
        <p class="hidden items-baseline gap-1.5 text-small text-muted sm:flex" role="status">
          <template v-if="volumePending">{{ $t('search.primary.volume.pending') }}</template>
          <template v-else-if="volumeFailed">{{ $t('search.primary.volume.failed') }}</template>
          <template v-else-if="0 === total">{{ $t('search.primary.volume.none') }}</template>
          <template v-else-if="null !== total">
            <strong class="font-display text-h3 font-bold text-accent tabular-nums">
              {{ total }}
            </strong>
            {{ $t('search.sentence.volumeUnit', total) }}
          </template>
        </p>
      </template>
    </ActionBar>

    <section data-zone="content" class="flex min-h-0 flex-1 flex-col gap-element overflow-auto">
      <p v-if="pending" class="m-auto text-muted">{{ $t('search.loading') }}</p>

      <UAlert v-else-if="error" color="error" variant="subtle" :title="$t('search.error')" />

      <!-- Ni introuvable, ni à moi, ni encore ouverte : on le dit et on renvoie à la liste plutôt
           que d'afficher un formulaire dont l'enregistrement serait refusé. -->
      <div v-else-if="!editable" class="m-auto flex max-w-readable flex-col items-center gap-block">
        <p class="text-center text-muted">{{ $t('search.manage.notEditable') }}</p>
        <UButton to="/recherches" color="neutral" variant="outline">
          {{ $t('search.manage.backToList') }}
        </UButton>
      </div>

      <template v-else>
        <p v-if="errorKey" class="flex-none text-center text-small text-alert" role="alert">
          {{ $t(errorKey) }}
        </p>

        <SearchSentence class="min-h-0 flex-1" :initial="initial" @change="onChange" @launch="ask">
          <template #action>
            <UButton
              block
              size="lg"
              class="flex-none rounded-none rounded-b-lg rtl:[&_.iconify]:rotate-180"
              trailing-icon="i-lucide-arrow-right"
              :disabled="saving || 0 === criteria.length"
              @click="ask"
            >
              {{ $t('search.manage.saveAndBrowse') }}
            </UButton>
          </template>
        </SearchSentence>
      </template>
    </section>

    <!-- Ouverture pilotée en lecture + événement, et non par liaison bidirectionnelle : une page
         n'écrit pas de code interactif (règle `pages-no-interactive-template`). Le rendu est le
         même, la responsabilité reste lisible. -->
    <UModal
      :open="confirming"
      :title="$t('search.manage.confirmTitle')"
      @update:open="confirming = $event"
    >
      <template #body>
        <p class="text-muted">{{ $t('search.manage.confirmBody') }}</p>
      </template>

      <template #footer>
        <div class="ms-auto flex gap-element">
          <UButton color="neutral" variant="ghost" @click="confirming = false">
            {{ $t('search.manage.cancel') }}
          </UButton>
          <UButton :loading="saving" @click="save">
            {{ $t('search.manage.saveAndBrowse') }}
          </UButton>
        </div>
      </template>
    </UModal>
  </div>
</template>
