<script setup lang="ts">
/**
 * L'AFFINAGE du parcours, en volets repliés — resserrer ce qu'on regarde sans toucher aux critères.
 *
 * Ce panneau ne modifie JAMAIS la recherche : il ne l'enregistre pas, ne la renomme pas, et ce qu'il
 * pose disparaît en quittant l'écran. C'est ce qui l'autorise à vivre dans la colonne d'un écran
 * dont le métier est de choisir, et non de chercher.
 *
 * REPLIÉS PAR DÉFAUT, tous : dépliés, six filtres font une colonne interminable dans deux cent
 * quatre-vingts pixels, et poussent l'avancement hors de vue.
 *
 * CE QUI MÉRITE UN VOLET, ET CE QUI N'EN MÉRITE PAS. Un volet coûte un geste par consultation : il
 * ne se justifie que devant un contenu qu'on ne peut pas laisser ouvert. Les quatre volets tiennent
 * des listes à plusieurs entrées ou plusieurs contrôles — la mobilité et le pied-à-terre suivent
 * l'horizon de retour, puisque tous trois disent comment le retour se prépare. L'interrupteur du
 * poste du moment, lui, est une ligne, et les listes de pays et de langues en font deux et cinq :
 * les enfermer coûtait plus que ce que ça rangeait.
 *
 * Les comptes viennent de `services/search/refine.ts` — un composant ne calcule pas ce qu'il montre.
 */
import { countryLabel, languageLabel } from '#services/search/labels'
import {
  LANGUAGE_LEVELS,
  PRESENCE_WINDOWS,
  carriesJobCriterion,
  countFor,
  refinableCities,
  refinableCountries,
  refinableDesiredFunctions,
  refinableDesiredSectors,
  refinableLanguages,
  refinableSectors,
} from '#services/search/refine'

import type { RefineForm } from '#services/search/refine'
import type { CandidateCard } from '#shared/types/candidate-card'

type Props = {
  /** Le jeu ENTIER, pas la page courante : un compte tiré d'un jeu partiel annonce faux. */
  cards: readonly CandidateCard[]
  /** L'horloge, injectée : elle décide de « présent aujourd'hui » et doit être la même au rendu. */
  today: Date
}

const { cards, today } = defineProps<Props>()

const form = defineModel<RefineForm>({ required: true })

const { t, current } = useLocale()

const HORIZONS = ['M3', 'M6', 'Y1', 'THINKING']
const AVAILABILITIES = ['IMMEDIATE', 'NOTICE_1M', 'NOTICE_3M']

/**
 * Le lien entre une facette et son contrôle.
 *
 * Il REMPLACE le formulaire au lieu de le muter : le parent en dérive la pile affinée, et une
 * mutation en place ne réveillerait pas ce calcul.
 */
const bind = <TFacet extends keyof RefineForm>(facet: TFacet) =>
  computed({
    get: () => form.value[facet],
    set: (value: RefineForm[TFacet]) => {
      form.value = { ...form.value, [facet]: value }
    },
  })

const presence = bind('presence')
const presenceCity = bind('presenceCity')
const returnHorizon = bind('returnHorizon')
const availability = bind('availability')
const hasPiedATerre = bind('hasPiedATerre')
const geoMobility = bind('geoMobility')
const country = bind('country')
const currentJob = bind('currentJob')
/**
 * LA LANGUE EMMÈNE SON NIVEAU. Retirer la langue retire le seuil : resté seul, il ne qualifierait
 * plus aucune entrée et continuerait pourtant de compter comme un affinage posé — un filtre invisible
 * qui filtre encore.
 */
const language = computed({
  get: () => form.value.language,
  set: (value: string) => {
    form.value = {
      ...form.value,
      language: value,
      languageLevel: '' === value ? '' : form.value.languageLevel,
    }
  },
})

const languageLevel = bind('languageLevel')
const experienceSector = bind('experienceSector')
const desiredSector = bind('desiredSector')
const desiredFunction = bind('desiredFunction')
const isStar = bind('isStar')

const optionsOf = (facet: 'presence' | 'returnHorizon' | 'availability', values: string[]) =>
  values.map((value) => ({
    value,
    label: t(`search.refine.value.${facet}.${value}`),
    count: countFor(cards, form.value, facet, value, today),
  }))

const presenceOptions = computed(() => optionsOf('presence', [...PRESENCE_WINDOWS]))

/**
 * Les paliers, DANS L'ORDRE DE L'ÉCHELLE et non par poids : « notions » avant « bilingue » dit
 * quelque chose que le compte n'a pas à défaire. Même traitement que la présence ou l'horizon.
 *
 * Les libellés viennent du catalogue que la fiche du profil affiche déjà : deux catalogues pour le
 * même mot, sur le même écran, finiraient par diverger.
 */
const levelOptions = computed(() =>
  LANGUAGE_LEVELS.map((level) => ({
    value: level,
    label: t(`search.ai.proficiency.${level}`),
    count: countFor(cards, form.value, 'languageLevel', level, today),
  })),
)
const horizonOptions = computed(() => optionsOf('returnHorizon', HORIZONS))
const availabilityOptions = computed(() => optionsOf('availability', AVAILABILITIES))

/**
 * Les villes offertes : celles du jeu, et rien d'autre.
 *
 * DES PASTILLES ET NON UN MENU, comme les autres facettes : le jeu en porte une poignée, chacune
 * gagne son compte, et reposer la ville active suffit à la retirer — un menu aurait dû loger une
 * entrée « toutes les villes » pour dire *rien*.
 */
const cityOptions = computed(() =>
  refinableCities(cards).map((city) => ({
    value: city,
    label: city,
    count: countFor(cards, form.value, 'presenceCity', city, today),
  })),
)

const countryOptions = computed(() =>
  refinableCountries(cards).map((code) => ({
    value: code,
    label: countryLabel(code, current.value),
    count: countFor(cards, form.value, 'country', code, today),
  })),
)

/**
 * Les langues et les secteurs viennent de la DONNÉE, jamais d'un référentiel figé : le jeu en porte
 * une poignée, et une liste fermée afficherait des options que personne ne satisfait.
 *
 * LES LANGUES SE NOMMENT, LES SECTEURS NON, et l'asymétrie tient à la source : l'exécution porte la
 * nomenclature des langues et sait donc traduire un code jamais rencontré, quand un secteur n'a
 * d'autre référentiel qu'une table à tenir à la main — la première chose qui dériverait.
 */
const languageOptions = computed(() =>
  refinableLanguages(cards).map((code) => ({
    value: code,
    label: languageLabel(code, current.value),
    count: countFor(cards, form.value, 'language', code, today),
  })),
)

const sectorOptions = computed(() =>
  refinableSectors(cards).map((sector) => ({
    value: sector,
    label: sector,
    count: countFor(cards, form.value, 'experienceSector', sector, today),
  })),
)

/**
 * CE QUE LE CANDIDAT CHERCHE, et non ce qu'il a fait — deux rangées à part du secteur d'expérience.
 *
 * Valeurs brutes, comme lui : elles viennent de la source, et aucun référentiel connu de l'exécution
 * ne les nomme. Les traduire demanderait une table à tenir à la main.
 */
const desiredSectorOptions = computed(() =>
  refinableDesiredSectors(cards).map((sector) => ({
    value: sector,
    label: sector,
    count: countFor(cards, form.value, 'desiredSector', sector, today),
  })),
)

const desiredFunctionOptions = computed(() =>
  refinableDesiredFunctions(cards).map((jobFunction) => ({
    value: jobFunction,
    label: jobFunction,
    count: countFor(cards, form.value, 'desiredFunction', jobFunction, today),
  })),
)

const flagCount = (facet: 'hasPiedATerre' | 'geoMobility'): number =>
  countFor(cards, form.value, facet, true, today)

/**
 * L'interrupteur « poste du moment » ne s'offre QUE si la recherche nomme un métier.
 *
 * Sans critère de poste, aucun profil ne porte de correspondance à interroger : l'affinage viderait
 * le jeu sans raison lisible, ce qui est pire qu'un filtre absent.
 */
const offersCurrentJob = computed(() => carriesJobCriterion(cards))

const groups = computed(() => [
  {
    value: 'presence',
    label: t('search.refine.group.presence'),
    posed: presence.value || presenceCity.value,
  },
  {
    value: 'return',
    label: t('search.refine.group.return'),
    posed: returnHorizon.value || form.value.hasPiedATerre || form.value.geoMobility,
  },
  {
    value: 'availability',
    label: t('search.refine.group.availability'),
    posed: availability.value,
  },
  // LE SECTEUR EST LE SEUL DES QUATRE ANCIENS COLOCATAIRES À GARDER UN VOLET, et c'est sa longueur
  // qui le décide : treize à quinze choix là où le pays en offre deux et la langue cinq. Il passe
  // APRÈS les trois autres et non avant — la présence au Maroc est la promesse du produit, et
  // ouvrir la pile sur autre chose qu'elle n'est pas un choix de mise en page neutre.
  {
    value: 'sector',
    label: t('search.refine.group.sector'),
    posed: experienceSector.value,
  },
  // CE QUI EST CHERCHÉ, APRÈS CE QUI A ÉTÉ FAIT. Le secteur d'expérience se lit sur un parcours
  // établi ; le projet, lui, est déclaré et reste rare. Le placer avant aurait ouvert la colonne sur
  // ce que le vivier porte le moins.
  {
    value: 'project',
    label: t('search.refine.group.project'),
    posed: desiredSector.value || desiredFunction.value,
  },
])

// Les comptes portent sur le jeu ENTIER, qui arrive d'un seul appel : ils sont vrais dès
// l'ouverture, et déplier un volet ne déclenche plus rien.
const open = ref<string[]>([])
</script>

<template>
  <!-- Une pile de volets encadrée de ce qui n'en mérite pas : la région et son nom appartiennent à
       qui le place. -->
  <div class="search-refine-panel flex min-h-0 flex-col">
    <!-- L'INTERRUPTEUR OUVRE LE PANNEAU, HORS DE TOUT VOLET. C'est une ligne, et c'était le filtre
         qui coûtait le plus de gestes — déplier, puis descendre sous trois listes. Le rapport entre
         ce qu'il pèse et ce qu'il décide était inversé.
         LE LIBELLÉ AFFIRME, ET C'EST LUI QUI PORTE LA RÉSERVE. « Occupe ce poste aujourd'hui » ne
         peut se dire que d'un profil dont le poste du moment est établi — le serveur ne le résout
         que sur trois quarts du vivier, et le quart restant sort donc du jeu. Un libellé qui ne
         promet rien de précis, lui, oblige à rattraper en petits caractères sous l'interrupteur,
         c'est-à-dire à l'endroit que personne ne lit. -->
    <USwitch
      v-if="offersCurrentJob"
      v-model="currentJob"
      size="sm"
      class="pb-element"
      :label="$t('search.refine.currentJob')"
    />

    <!-- LE SECOND INTERRUPTEUR, et il reste dehors pour la même raison que le premier : il tient sur
         une ligne, et un volet coûte un geste à chaque consultation. C'est aussi le filtre le plus
         qualifiant du produit — rencontré en entretien, pas seulement importé —, donc celui qu'on
         veut lire sans rien déplier.
         DÉCOCHÉ NE VEUT PAS DIRE « les non vérifiés » : il veut dire « peu importe ». -->
    <USwitch v-model="isStar" size="sm" class="pb-element" :label="$t('search.refine.star')" />

    <UAccordion v-model="open" :items="groups" type="multiple" :ui="{ trigger: 'py-2' }">
      <template #default="{ item }">
        <span class="flex min-w-0 items-center gap-1.5 text-caption font-semibold">
          <span class="truncate">{{ item.label }}</span>
          <!-- LE POINT DIT QU'UN VOLET FERMÉ TIENT QUELQUE CHOSE. Sans lui, replier revient à
               cacher un filtre actif, et le jeu paraît resserré sans raison visible. -->
          <span
            v-if="item.posed"
            class="h-1.5 w-1.5 flex-none rounded-full bg-accent"
            aria-hidden="true"
          />
        </span>
      </template>

      <template #body="{ item }">
        <div class="flex flex-col gap-element pb-element">
          <template v-if="'presence' === item.value">
            <SearchRefineChoice v-model="presence" :options="presenceOptions" />
            <!-- LA VILLE NE S'OFFRE QUE SI LE JEU EN PORTE UNE : un intitulé suivi du vide se lit
                 comme un chargement qui n'a pas abouti. -->
            <div v-if="cityOptions.length" class="flex flex-col gap-1">
              <p class="text-caption text-muted">
                {{ $t('search.criterion.facet.presenceCity') }}
              </p>
              <SearchRefineChoice v-model="presenceCity" :options="cityOptions" />
            </div>
          </template>

          <template v-else-if="'return' === item.value">
            <SearchRefineChoice v-model="returnHorizon" :options="horizonOptions" />
            <USwitch
              v-model="hasPiedATerre"
              size="sm"
              :label="`${$t('search.criterion.facet.hasPiedATerre')} (${flagCount('hasPiedATerre')})`"
            />
            <USwitch
              v-model="geoMobility"
              size="sm"
              :label="`${$t('search.criterion.facet.geoMobility')} (${flagCount('geoMobility')})`"
            />
          </template>

          <template v-else-if="'availability' === item.value">
            <SearchRefineChoice v-model="availability" :options="availabilityOptions" />
          </template>

          <template v-else-if="'sector' === item.value">
            <SearchRefineChoice v-model="experienceSector" :options="sectorOptions" />
          </template>

          <template v-else>
            <div v-if="desiredSectorOptions.length" class="flex flex-col gap-1">
              <p class="text-caption text-muted">{{ $t('search.criterion.facet.sector') }}</p>
              <SearchRefineChoice v-model="desiredSector" :options="desiredSectorOptions" />
            </div>

            <div v-if="desiredFunctionOptions.length" class="flex flex-col gap-1">
              <p class="text-caption text-muted">{{ $t('search.criterion.facet.function') }}</p>
              <SearchRefineChoice v-model="desiredFunction" :options="desiredFunctionOptions" />
            </div>
          </template>
        </div>
      </template>
    </UAccordion>

    <!-- LES DEUX LISTES COURTES RESTENT DEHORS, et c'est leur longueur qui le permet : deux pays,
         cinq langues. Un volet coûte un geste par consultation ; sous ce volume, le geste coûte plus
         que ce qu'il économise. Elles se lisent APRÈS les volets, parce qu'aucune des deux ne porte
         la promesse du produit — on les consulte quand le reste n'a pas suffi. -->
    <div class="flex flex-col gap-element pt-element">
      <div v-if="countryOptions.length" class="flex flex-col gap-1">
        <p class="text-caption text-muted">{{ $t('search.criterion.facet.country') }}</p>
        <SearchRefineChoice v-model="country" :options="countryOptions" />
      </div>

      <div v-if="languageOptions.length" class="flex flex-col gap-1">
        <p class="text-caption text-muted">{{ $t('search.criterion.facet.language') }}</p>
        <SearchRefineChoice v-model="language" :options="languageOptions" />
      </div>

      <!-- LE SEUIL NE S'OFFRE QU'ADOSSÉ À UNE LANGUE : sans elle, « au moins courant » ne qualifie
           aucune entrée. Le libellé porte sa réserve — « déclaré » dit qu'on filtre sur ce qui a été
           renseigné, et donc que le silence sort. -->
      <div v-if="'' !== language" class="flex flex-col gap-1">
        <p class="text-caption text-muted">{{ $t('search.refine.languageLevel') }}</p>
        <SearchRefineChoice v-model="languageLevel" :options="levelOptions" />
      </div>
    </div>
  </div>
</template>
