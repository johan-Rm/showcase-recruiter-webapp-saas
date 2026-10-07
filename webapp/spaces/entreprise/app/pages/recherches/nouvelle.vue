<script setup lang="ts">
import { autoName, fromPrimaryForm } from '#services/search/criteria'

import type { PrimarySearchForm } from '#services/search/criteria'

definePageMeta({ layout: 'cockpit' })

const { t } = useLocale()
const { create } = useSearches()
const draft = useSearchDraft()
const { total, pending: volumePending, failed: volumeFailed, count } = useSearchVolume()

draft.reset()

// À CHAQUE TOUCHE : les critères et le décompte, qui doivent suivre la frappe.
const onChange = (filters: PrimarySearchForm) => {
  draft.criteria.value = fromPrimaryForm(filters)
  count(draft.criteria.value)
}

// SEULEMENT QUAND LES CRITÈRES SONT NOMMABLES : le nom, qui ne doit pas s'écrire lettre à lettre
// pendant qu'on cherche un intitulé de poste. Émis après `change`, donc les critères sont à jour.
const onSettle = () => {
  draft.composedName.value = autoName(draft.criteria.value, t)
}

const rename = (value: string) => {
  draft.manualName.value = value
}

// Deux maillons, et non trois : on ouvre cet écran depuis l'accueil, et le fil dit le chemin
// parcouru. Intercaler « Mes recherches » décrirait une hiérarchie de rubriques par où l'on n'est
// pas passé. Le dernier maillon n'a pas de destination : c'est la page courante.
const trail = computed(() => [
  { label: t('nav.entreprise.dashboard'), to: '/' },
  { label: t('page.entreprise.newSearch.title') },
])

const creating = ref(false)
const errorKey = ref('')

const submit = async () => {
  errorKey.value = ''

  if (0 === draft.criteria.value.length) {
    errorKey.value = 'search.create.noCriteria'
    return
  }

  creating.value = true
  try {
    const created = await create(draft.criteria.value, draft.manualName.value.trim())
    await navigateTo(`/recherches/${created.id}`)
  } catch {
    errorKey.value = 'search.create.error'
  } finally {
    creating.value = false
  }
}
</script>

<template>
  <div data-page="search-new" class="flex min-h-0 flex-1 flex-col gap-element">
    <!-- La gouttière SITUE, elle n'agit plus : le fil d'ariane remplace le bouton de sortie, qui
         annonçait « Retour aux recherches » et ramenait à l'accueil. L'aide passe à droite, en
         pastille — un texte nu à cette place se lirait comme un bouton éteint. -->
    <Gutter data-zone="gutter">
      <Breadcrumb :items="trail" />

      <template #action>
        <!-- Elle s'efface sous `lg` : une bande fine ne tient pas les deux, et savoir où l'on est
             prime sur une aide que le titre de la page redit en partie. -->
        <p class="hidden items-center gap-1.5 text-small text-muted lg:flex">
          <UIcon name="i-lucide-info" class="h-4 w-4 flex-none" aria-hidden="true" />
          {{ $t('search.guide.classic') }}
        </p>
      </template>
    </Gutter>

    <ActionBar data-zone="actionbar">
      <h1 class="flex min-w-0 items-baseline font-display text-h3">
        <span class="sr-only">{{ $t('page.entreprise.newSearch.title') }} ·</span>
        <SearchShellName
          :name="draft.name.value"
          :placeholder="$t('search.sentence.name.placeholder')"
          :label="$t('search.sentence.name.label')"
          :edit-label="$t('search.sentence.name.edit')"
          @rename="rename"
        />
      </h1>

      <template #end>
        <!-- LE NOMBRE PORTE, PAS SON UNITÉ. C'est lui qu'on suit du coin de l'œil en remplissant la
             phrase — il bouge à chaque critère, le mot qui le suit jamais. À la même taille, les
             deux se lisaient comme une phrase ; le compteur se lit maintenant d'un coup d'œil, sans
             quitter la ligne qu'on est en train de composer. Alignés sur la ligne de base, sans
             quoi le chiffre plus grand ferait flotter son unité. -->
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
      <p v-if="errorKey" class="flex-none text-center text-small text-alert" role="alert">
        {{ $t(errorKey) }}
      </p>

      <!-- `launch` : Entrée dans un champ de la phrase. Le composant est le formulaire (Entrée n'a
           d'effet natif que là) ; la page reste celle qui décide et exécute — même partage que
           `change`. Sans cette écoute, Entrée ne fait rien et seul le bouton de la barre lance. -->
      <SearchSentence class="min-h-0 flex-1" @change="onChange" @settle="onSettle" @launch="submit">
        <template #action>
          <!-- LE BOUTON EST LE PIED de la carte : pleine largeur, à angles droits, il en occupe le
               bord bas d'un côté à l'autre — même forme que « Ma sélection » au pied de la colonne
               d'avancement de la Visionneuse. D'où `UButton` et non le bouton du kit, dont le rayon
               n'est pas débrayable : c'est le composant qui rend déjà ce pied-là ailleurs.
               La flèche suit le libellé — elle annonce l'écran d'après, pas une direction à
               l'écran — et se retourne en arabe, où l'on avance vers la gauche. -->
          <UButton
            block
            size="lg"
            class="flex-none rounded-none rounded-b-lg rtl:[&_.iconify]:rotate-180"
            trailing-icon="i-lucide-arrow-right"
            :disabled="creating || 0 === draft.criteria.value.length"
            @click="submit"
          >
            {{ $t('search.sentence.submit') }}
          </UButton>
        </template>
      </SearchSentence>
    </section>
  </div>
</template>
