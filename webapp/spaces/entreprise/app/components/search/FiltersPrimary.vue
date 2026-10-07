<script setup lang="ts">
/**
 * Le formulaire de recherche par critères — la porte d'entrée du vivier.
 *
 * Quatre champs, dans l'ordre où un recruteur formule sa demande : le MÉTIER d'abord (c'est lui qui
 * définit la population), l'expérience et le niveau d'études qui la resserrent, les mots-clés en
 * dernier — le champ le plus libre, donc le moins structurant.
 *
 * Chaque champ a la forme de ce qu'il demande : une valeur continue prend un curseur, une
 * nomenclature fermée de six valeurs prend des tags qu'on voit toutes d'un coup, un vocabulaire de
 * plusieurs milliers de termes prend une auto-complétion.
 *
 * Composant PASSIF sur la recherche : il collecte et émet `search`, il ne la lance pas. Il fait en
 * revanche ses propres appels d'AUTO-COMPLÉTION — proposer pendant la frappe n'a de sens qu'ici, et
 * remonter chaque touche à la page pour qu'elle interroge à sa place n'aurait rien clarifié.
 */
import { isUnknownJobTerm } from '#services/search/vocabulary'

import type { PrimarySearchForm } from '#services/search/criteria'
import type { SearchSuggestion } from '#shared/types/search'

type Props = {
  /** Valeurs de départ — la carte sert aussi à AJUSTER une recherche existante, pas qu'à en ouvrir une. */
  initial?: PrimarySearchForm
  /** Libellé du bouton : « Rechercher » à l'ouverture, « Appliquer » quand on ajuste. */
  submitLabel?: string
  /**
   * Ce que les critères en cours ramèneraient — `null` quand il n'y a rien à annoncer.
   *
   * Passé par l'appelant, jamais compté ici : le tiroir « Affiner » porte des critères que ce
   * formulaire ne sait pas montrer (une langue, un pays), et un volume calculé sur les seuls champs
   * visibles y serait trop large. Absent, la ligne ne s'affiche pas.
   */
  volume?: { total: number | null; pending: boolean; failed: boolean } | null
  /** Rien à chercher : le formulaire ne laisse pas partir un lancement qui n'a aucun critère. */
  submitDisabled?: boolean
}

const { initial, submitLabel = '', volume = null, submitDisabled = false } = defineProps<Props>()

const emit = defineEmits<{
  search: [filters: PrimarySearchForm]
  /** À chaque frappe : l'appelant s'en sert pour compter, le formulaire ne compte pas lui-même. */
  change: [filters: PrimarySearchForm]
}>()

// Miroir de l'enum API `EducationLevel` — ses 6 valeurs, dans l'ordre des rangs.
const EDUCATION_LEVELS = [
  'BAC',
  'BAC_PLUS_2',
  'LICENCE',
  'MASTER',
  'INGENIEUR',
  'DOCTORAT',
] as const

/**
 * Le curseur s'arrête à quinze ans.
 *
 * Au-delà, l'ancienneté cesse de discriminer : on ne cherche pas « plus de vingt ans » comme un
 * filtre, on le lit sur le profil. La borne haute est donc un plafond de sens, pas un maximum de
 * données.
 */
const EXPERIENCE_MAX = 15

// En deçà, on n'interroge pas : sur une lettre la liste ne renseigne pas, elle occupe l'écran.
// C'est le seuil que l'API applique de son côté — l'annoncer ici évite un aller-retour pour rien.
const MIN_TERM_LENGTH = 2
const DEBOUNCE_MS = 220

const { suggest } = useSuggestions()

const filters = reactive<PrimarySearchForm>({
  jobTitle: initial?.jobTitle ?? '',
  keywords: [...(initial?.keywords ?? [])],
  experienceMin: initial?.experienceMin ?? 0,
  educationLevel: initial?.educationLevel ?? '',
})

// `useId` et non des identifiants écrits en dur : la carte ne peut pas jurer d'être seule sur la
// page, et un identifiant en double casse le lien label ↔ champ pour tout le monde.
const uid = useId()
const fieldId = (name: string) => `${uid}-${name}`

/**
 * Un vocabulaire interrogé pendant la frappe.
 *
 * Débouncé : sans cela chaque touche partirait en requête. La liste garde ce que la DERNIÈRE réponse
 * a ramené — deux appels en vol peuvent revenir dans le désordre, et c'est le terme courant qui fait
 * foi, pas l'ordre d'arrivée.
 */
const useVocabulary = (type: 'JOB' | 'SKILL') => {
  const term = ref('')
  const items = ref<SearchSuggestion[]>([])
  const loading = ref(false)
  const failed = ref(false)

  const run = useDebounceFn(async (input: string) => {
    const results = await suggest(type, input)

    if (input === term.value.trim()) {
      items.value = results.items
      failed.value = results.failed
      loading.value = false
    }
  }, DEBOUNCE_MS)

  watch(term, (value) => {
    const input = value.trim()

    if (input.length < MIN_TERM_LENGTH) {
      items.value = []
      failed.value = false
      loading.value = false

      return
    }

    loading.value = true
    run(input)
  })

  return { term, items, loading, failed }
}

const jobs = useVocabulary('JOB')
const skills = useVocabulary('SKILL')

/**
 * Un tag à choix unique : le reposer le RETIRE.
 *
 * Sans cela, un niveau d'études cliqué par erreur ne peut plus être enlevé — il n'y a pas d'option
 * « aucun » dans une nomenclature, et il n'y a aucune raison d'en inventer une.
 */
const toggleEducation = (level: string): void => {
  filters.educationLevel = filters.educationLevel === level ? '' : level
}

/**
 * Les suggestions, réduites à ce qui s'affiche : leur libellé.
 *
 * Le nombre de profils que chacune ramènerait voyage dans la réponse, mais il ne s'affiche PAS : en
 * tapant quelques lettres au hasard, un recruteur relèverait la taille de chaque population et la
 * composition du portefeuille sans avoir dévoilé un seul profil.
 *
 * Le serveur, lui, continue de le calculer — c'est lui qui classe les propositions et qui écarte les
 * métiers que personne n'exerce.
 */
const jobSuggestions = computed(() => jobs.items.value.map((item) => ({ value: item.value })))

/**
 * Le mot tapé ne désigne aucun métier connu — et la ligne de volume doit le DIRE.
 *
 * Le formulaire tranche seul : c'est lui qui interroge le vocabulaire, lui seul sait ce que la liste
 * a répondu. Le faire remonter à la page pour redescendre en `prop` ajouterait un aller-retour sans
 * rien clarifier.
 *
 * La règle vit dans `services/` parce que c'est ELLE qui porte la justesse du message, et qu'un
 * composant ne se teste pas ici.
 */
const jobTermUnknown = computed(() =>
  isUnknownJobTerm({
    term: filters.jobTitle,
    suggestions: jobs.items.value,
    loading: jobs.loading.value,
    failed: jobs.failed.value,
    minChars: MIN_TERM_LENGTH,
  }),
)

const keywordSuggestions = computed(() => skills.items.value.map((item) => ({ value: item.value })))

// Copie et non la référence : l'appelant recevrait sinon un objet qui continue de muter sous lui.
const snapshot = (): PrimarySearchForm => ({ ...filters, keywords: [...filters.keywords] })

const onSubmit = () => emit('search', snapshot())

// Le seul watcher du composant, et il est justifié : il n'y a pas d'événement commun aux quatre
// champs — un curseur, des tags, deux `v-model` — et les écouter un à un multiplierait les endroits
// où l'on peut oublier d'en brancher un. Ce que l'appelant en fait (compter, débouncer) est son
// affaire ; le formulaire se contente de dire qu'il a bougé.
watch(filters, () => emit('change', snapshot()))
</script>

<template>
  <!-- L'aplat reste pleine largeur — c'est la surface de l'outil —, mais le CONTENU se borne et se
       centre : quatre champs étalés sur 1 900 px, c'était deux mots à saisir dans un champ de la
       largeur de l'écran. La carte cesse aussi de remplir la hauteur, pour que le bouton suive le
       dernier champ au lieu d'être renvoyé dans un coin.
       Sans effet dans le tiroir « Affiner », déjà plus étroit que la borne. -->
  <UiCard as="form" class="search-filters-primary flex flex-col" @submit.prevent="onSubmit">
    <div class="mx-auto flex w-full max-w-readable flex-col gap-block">
      <!-- ① LE MÉTIER. Les rôles proches sont inclus par le SERVEUR (dictionnaire de concepts) :
           l'UI n'élargit rien elle-même, elle le DIT seulement. -->
      <UiField
        :id="fieldId('jobTitle')"
        v-slot="{ describedBy }"
        :label="$t('search.primary.jobTitle.label')"
        :hint="$t('search.primary.jobTitle.hint')"
      >
        <!-- Le terme libre reste la valeur tant qu'aucune suggestion n'est choisie, comme le cadrage
             l'exige. -->
        <UiRemoteCombobox
          :id="fieldId('jobTitle')"
          v-model="filters.jobTitle"
          :items="jobSuggestions"
          :loading="jobs.loading.value"
          :min-chars="MIN_TERM_LENGTH"
          :placeholder="$t('search.primary.jobTitle.placeholder')"
          :described-by="describedBy"
          :loading-label="$t('search.primary.jobTitle.loading')"
          :empty-label="$t('search.primary.noResult')"
          @update:term="jobs.term.value = $event"
        />
      </UiField>

      <!-- ② L'EXPÉRIENCE. Facultative, et `0` porte le « peu importe » : la borne basse EST l'état
           vide, ce qui évite d'inventer un état neutre à un curseur qui n'en a pas. -->
      <UiField
        :id="fieldId('experience')"
        v-slot="{ describedBy }"
        :label="$t('search.primary.experience.label')"
      >
        <!-- La valeur lue reste COLLÉE à la piste : quinze crans étalés sur toute la largeur
             plaçaient « 5 ans » à l'autre bout de la ligne, hors du regard qui suit la poignée. -->
        <div class="flex max-w-md items-center gap-element">
          <USlider
            :id="fieldId('experience')"
            v-model="filters.experienceMin"
            :min="0"
            :max="EXPERIENCE_MAX"
            :aria-describedby="describedBy"
            class="min-w-0 flex-1"
          />
          <output :for="fieldId('experience')" class="w-28 flex-none text-small text-muted">
            {{
              filters.experienceMin > 0
                ? $t('search.primary.experience.value', { count: filters.experienceMin })
                : $t('search.primary.experience.any')
            }}
          </output>
        </div>
      </UiField>

      <!-- ③ LE NIVEAU D'ÉTUDES. Six valeurs : toutes visibles d'un coup valent mieux qu'une liste
           qu'il faut ouvrir pour savoir ce qu'elle contient. -->
      <UiField :id="fieldId('education')" :label="$t('search.primary.education.label')">
        <ul class="flex flex-wrap gap-element">
          <li v-for="level in EDUCATION_LEVELS" :key="level">
            <UButton
              :color="filters.educationLevel === level ? 'primary' : 'neutral'"
              :variant="filters.educationLevel === level ? 'soft' : 'outline'"
              size="sm"
              :aria-pressed="filters.educationLevel === level"
              @click="toggleEducation(level)"
            >
              {{ $t(`search.education.${level}`) }}
            </UButton>
          </li>
        </ul>
      </UiField>

      <!-- ④ LES MOTS-CLÉS, en dernier parce que ce sont les plus libres. Plusieurs, reliés en OU :
           en ajouter ÉLARGIT le résultat, et c'est contre-intuitif. AUCUNE NOTE NE L'EXPLIQUE ICI :
           l'écran de la phrase de recherche l'énonce dans l'intitulé de sa colonne — « qui maîtrise
           l'un de ces mots-clés » —, au moment où l'on s'en sert plutôt qu'en bas de champ. Et une
           note qui énumérerait les endroits fouillés vieillirait à chaque élargissement du moteur. -->
      <UiField
        :id="fieldId('keywords')"
        v-slot="{ describedBy }"
        :label="$t('search.primary.keywords.label')"
      >
        <UiTagPicker
          :id="fieldId('keywords')"
          v-model="filters.keywords"
          :items="keywordSuggestions"
          :loading="skills.loading.value"
          :failed="skills.failed.value"
          :min-chars="MIN_TERM_LENGTH"
          :described-by="describedBy"
          :add-label="$t('search.primary.keywords.add')"
          :close-label="$t('search.primary.keywords.close')"
          :search-label="$t('search.primary.keywords.label')"
          :search-placeholder="$t('search.primary.keywords.placeholder')"
          :loading-label="$t('search.primary.keywords.loading')"
          :empty-label="$t('search.primary.noResult')"
          :unavailable-label="$t('search.primary.keywords.unavailable')"
          :remove-label="(value) => $t('search.primary.keywords.remove', { value })"
          :create-label="(value) => $t('search.primary.keywords.create', { value })"
          @update:term="skills.term.value = $event"
        />
      </UiField>

      <!-- LE VOLUME, collé au bouton : c'est au moment de lancer qu'il sert, et lancer CRÉE une
           recherche. Annoncé en `role="status"`, il est lu sans voler le focus au champ en cours.
           Aucun compte ne ferme le bouton — un volume est une aide, pas une garde : on peut vouloir
           lancer à zéro, et une panne de comptage ne doit rien empêcher. Seule l'ABSENCE de critère
           le ferme, et c'est l'appelant qui le dit. -->
      <div class="flex flex-wrap items-center justify-end gap-element">
        <p v-if="volume" class="text-small text-muted" role="status">
          <template v-if="volume.pending">{{ $t('search.primary.volume.pending') }}</template>
          <template v-else-if="volume.failed">{{ $t('search.primary.volume.failed') }}</template>
          <!-- AVANT le compte à zéro, et à sa place : « aucun profil ne correspond » et « ce mot
               n'est pas un métier connu » décrivent deux situations opposées, et le second cas
               produit toujours un zéro. Les afficher tous deux dirait deux fois la même chose en se
               contredisant à moitié. -->
          <template v-else-if="jobTermUnknown">
            {{ $t('search.primary.volume.unknownJob', { term: filters.jobTitle.trim() }) }}
          </template>
          <template v-else-if="0 === volume.total">{{ $t('search.primary.volume.none') }}</template>
          <template v-else-if="null !== volume.total">
            {{ $t('search.primary.volume.count', { count: volume.total }) }}
          </template>
        </p>

        <UiButton type="submit" :disabled="submitDisabled">
          {{ submitLabel || $t('search.primary.submit') }}
        </UiButton>
      </div>
    </div>
  </UiCard>
</template>
