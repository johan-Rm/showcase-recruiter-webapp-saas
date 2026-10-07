<script setup lang="ts">
/**
 * Les DOUZE filtres que le moteur applique et qu'aucun formulaire ne posait.
 *
 * Ils n'étaient accessibles que par la recherche en langage naturel : pour « présent à Casablanca
 * dans les trente jours », il fallait espérer que le modèle comprenne. Ce sont pourtant les
 * différenciants du produit — la présence physique au Maroc au bon moment est la promesse.
 *
 * UN CHOIX PAR FILTRE, et c'est la vérité du moteur : il ne garde que la dernière valeur posée par
 * facette. Des cases à cocher multiples laisseraient croire qu'on élargit alors qu'on remplace.
 *
 * Composant PASSIF : il collecte et émet, il ne lance pas la recherche.
 */
import { emptySecondaryForm } from '#services/search/criteria'

import type { SecondarySearchForm } from '#services/search/criteria'

type Props = {
  /** Valeurs de départ — le formulaire sert à AJUSTER une recherche, pas qu'à en ouvrir une. */
  initial?: SecondarySearchForm
  busy?: boolean
}

const { initial, busy = false } = defineProps<Props>()

const emit = defineEmits<{ apply: [filters: SecondarySearchForm] }>()

const form = reactive<SecondarySearchForm>({ ...emptySecondaryForm(), ...initial })

const { t } = useLocale()
const countries = useCountries()
const { data: sectors } = useSectors()

/** Une liste fermée + son « peu importe » : sans lui, on ne peut plus retirer un filtre posé. */
const withAny = (values: readonly string[], catalogue: string) => [
  { value: '', label: t('search.secondary.any') },
  ...values.map((value) => ({ value, label: t(`${catalogue}.${value}`) })),
]

const HORIZONS = ['M3', 'M6', 'Y1', 'THINKING']
const AVAILABILITIES = ['IMMEDIATE', 'NOTICE_1M', 'NOTICE_3M']
const PROFICIENCIES = [
  'ELEMENTARY',
  'LIMITED_WORKING',
  'PROFESSIONAL_WORKING',
  'FULL_PROFESSIONAL',
  'NATIVE_OR_BILINGUAL',
]

/**
 * La fenêtre de présence est un SEUIL : « sous trente jours » contient déjà « sous quinze ».
 * D'où une liste ordonnée et non des cases — cocher deux fenêtres n'aurait aucun sens.
 */
const PRESENCE_WINDOWS = [0, 15, 30, 60]

const presenceOptions = computed(() =>
  PRESENCE_WINDOWS.map((days) => ({
    value: days,
    label:
      0 === days ? t('search.secondary.any') : t('search.secondary.withinDays', { count: days }),
  })),
)

const sectorOptions = computed(() => [
  { value: '', label: t('search.secondary.any') },
  ...(sectors.value ?? []).map((value) => ({ value, label: value })),
])

/**
 * Le groupe MAROC est ouvert par défaut : c'est la promesse du produit, et le seul filtre qu'aucun
 * concurrent ne propose. Les autres se déplient à la demande — douze filtres dépliés feraient une
 * colonne interminable dans deux cent quatre-vingts pixels.
 */
const groups = computed(() => [
  { value: 'morocco', label: t('search.secondary.group.morocco') },
  { value: 'return', label: t('search.secondary.group.return') },
  { value: 'job', label: t('search.secondary.group.job') },
  { value: 'profile', label: t('search.secondary.group.profile') },
  { value: 'place', label: t('search.secondary.group.place') },
])

const open = ref(['morocco'])
</script>

<template>
  <form
    class="search-filters-secondary flex flex-col gap-block"
    @submit.prevent="emit('apply', form)"
  >
    <UAccordion v-model="open" :items="groups" type="multiple">
      <template #body="{ item }">
        <div class="flex flex-col gap-element pb-element">
          <template v-if="'morocco' === item.value">
            <UiField id="presence" :label="$t('search.criterion.facet.presenceWithinDays')">
              <USelect
                id="presence"
                v-model="form.presenceWithinDays"
                :items="presenceOptions"
                class="w-full"
              />
            </UiField>

            <UiField id="presenceCity" :label="$t('search.criterion.facet.presenceCity')">
              <UInput
                id="presenceCity"
                v-model="form.presenceCity"
                :placeholder="$t('search.secondary.cityPlaceholder')"
              />
            </UiField>

            <USwitch v-model="form.geoMobility" :label="$t('search.criterion.facet.geoMobility')" />
            <USwitch
              v-model="form.hasPiedATerre"
              :label="$t('search.criterion.facet.hasPiedATerre')"
            />
          </template>

          <template v-else-if="'return' === item.value">
            <UiField id="returnHorizon" :label="$t('search.criterion.facet.returnHorizon')">
              <USelect
                id="returnHorizon"
                v-model="form.returnHorizon"
                :items="withAny(HORIZONS, 'search.horizon')"
                class="w-full"
              />
            </UiField>

            <UiField id="availability" :label="$t('search.criterion.facet.availability')">
              <USelect
                id="availability"
                v-model="form.availability"
                :items="withAny(AVAILABILITIES, 'search.availability')"
                class="w-full"
              />
            </UiField>
          </template>

          <template v-else-if="'job' === item.value">
            <UiField id="sector" :label="$t('search.criterion.facet.sector')">
              <UInput
                id="sector"
                v-model="form.sector"
                :placeholder="$t('search.secondary.sectorPlaceholder')"
              />
            </UiField>

            <!-- Le SEUL des trois qui soit une nomenclature fermée : le secteur d'expérience est une
                 énumération du contrat, là où le secteur et la fonction VISÉS sont ce que le candidat
                 a écrit. -->
            <UiField id="experienceSector" :label="$t('search.criterion.facet.experienceSector')">
              <USelect
                id="experienceSector"
                v-model="form.experienceSector"
                :items="sectorOptions"
                class="w-full"
              />
            </UiField>

            <UiField id="jobFunction" :label="$t('search.criterion.facet.function')">
              <UInput
                id="jobFunction"
                v-model="form.jobFunction"
                :placeholder="$t('search.secondary.functionPlaceholder')"
              />
            </UiField>
          </template>

          <template v-else-if="'profile' === item.value">
            <UiField id="language" :label="$t('search.criterion.facet.language')">
              <UInput
                id="language"
                v-model="form.language"
                :placeholder="$t('search.secondary.languagePlaceholder')"
              />
            </UiField>

            <UiField id="languageLevel" :label="$t('search.criterion.facet.languageLevel')">
              <USelect
                id="languageLevel"
                v-model="form.languageLevel"
                :items="withAny(PROFICIENCIES, 'search.ai.proficiency')"
                class="w-full"
              />
            </UiField>

            <USwitch v-model="form.star" :label="$t('search.criterion.facet.star')" />
          </template>

          <template v-else>
            <UiField id="country" :label="$t('search.criterion.facet.country')">
              <USelect
                id="country"
                v-model="form.country"
                :items="[{ value: '', label: $t('search.secondary.any') }, ...countries]"
                class="w-full"
              />
            </UiField>
          </template>
        </div>
      </template>
    </UAccordion>

    <!-- APPLIQUER, et pas à chaque case : rejouer la recherche à chaque clic reconstruit la pile
         sous les yeux et fait perdre le profil qu'on lisait. -->
    <UButton type="submit" block :loading="busy">{{ $t('search.criteria.apply') }}</UButton>
  </form>
</template>
