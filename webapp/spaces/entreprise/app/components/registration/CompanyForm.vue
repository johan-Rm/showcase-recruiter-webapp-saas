<script setup lang="ts">
import { ACQUISITION_CHANNELS } from '#shared/types/organization'
import { ORGANIZATION_INPUT_CONSTRAINTS } from '#shared/types/organization-constraints'

import type { ResolvedAddress } from '#shared/types/places'

// Onboarding en trois groupes, un seul à l'écran, une JAUGE de progression, et un envoi EXPLICITE au
// dernier groupe (bouton « Envoyer ma demande », ou Entrée) — jamais à la simple sélection d'une
// pastille : c'est ce qui évite toute soumission surprise, au clavier notamment.
//
// AUCUN COMPTEUR D'ÉTAPES : ni « 2/3 » ni pastilles de progression. Le pourcentage mesure ce qui est
// FAIT, pas ce qui reste ; il récompense au lieu d'annoncer la longueur du chemin.
//
// Tout est obligatoire. Site web, immatriculation et description sont sortis du parcours : ils se
// renseigneront depuis l'espace du compte (cf. cadrage inscription.md).
//
// Ce que l'écran ne demande pas : le mot de passe (défini APRÈS validation, par lien à usage unique)
// et l'e-mail (déjà prouvé par le magic link, avant d'arriver ici).
const emit = defineEmits<{ created: [] }>()

// Référentiel et création viennent du composable : un composant ne fait pas de réseau.
const { createOrganization, pending, error, fieldErrors } = useRegistrationCompany()

// D'où le déclarant est ARRIVÉ, capté au premier contact. Ne pré-remplit JAMAIS la provenance : les
// deux ne répondent pas à la même question (cf. useAcquisitionReferrer).
const { referrer } = useAcquisitionReferrer()

// Secteurs : liste fermée servie par l'API (source unique = secteurs.yaml). Le champ devient un
// dropdown. `[]` tant qu'elle n'est pas arrivée — le champ reste utilisable, juste vide.
const { data: sectors } = useSectors()

// Autocomplétion d'adresse (Google Places, proxifiée par le BFF). `usePlacesStatus` dit si une clé est
// posée ; sinon on démarre en saisie manuelle plutôt que de laisser taper une adresse dans le vide.
// Résolu au rendu SSR : le bon mode est déjà là au premier affichage.
const placesEnabled = usePlacesStatus()

// Manuel par défaut (sûr) ; on bascule en recherche dès que Places est confirmé disponible. Le watch
// immédiat cale le mode avant le premier rendu quand le statut est déjà connu (SSR).
const manualAddress = ref(true)
watch(
  placesEnabled,
  (enabled) => {
    if (enabled) {
      manualAddress.value = false
    }
  },
  { immediate: true },
)

/**
 * La recherche d'adresse, portée ICI.
 *
 * `UiRemoteCombobox` ne connaît aucun domaine : il affiche une liste et rend le terme frappé. C'est
 * donc à cet écran d'interroger Places, de débouncer, et de résoudre la fiche choisie — le composant
 * du kit sert aussi bien un intitulé de poste qu'une adresse, et c'est ce qui le rend réutilisable.
 *
 * En deçà de trois caractères on n'interroge pas : trop court pour être discriminant, et ça
 * facturerait pour rien.
 */
const ADDRESS_MIN_CHARS = 3
const ADDRESS_DEBOUNCE_MS = 220

const { suggest: suggestPlaces, resolve: resolvePlace } = usePlaces()

const addressTerm = ref('')
const addressLoading = ref(false)
// Les suggestions telles que la DERNIÈRE réponse les a ramenées, et leur fiche Places associée :
// deux appels en vol peuvent revenir dans le désordre, et c'est le terme courant qui fait foi.
const addressSuggestions = ref<{ value: string; id: string }[]>([])

const runAddressSearch = useDebounceFn(async (input: string) => {
  const { configured, suggestions } = await suggestPlaces(input)

  if (!configured) {
    // Pas de clé : on bascule en saisie manuelle plutôt que de laisser taper dans le vide.
    manualAddress.value = true
    addressLoading.value = false

    return
  }

  if (input === addressTerm.value.trim()) {
    addressSuggestions.value = suggestions.map((item) => ({ value: item.label, id: item.id }))
    addressLoading.value = false
  }
}, ADDRESS_DEBOUNCE_MS)

watch(addressTerm, (value) => {
  const input = value.trim()

  if (input.length < ADDRESS_MIN_CHARS) {
    addressSuggestions.value = []
    addressLoading.value = false

    return
  }

  addressLoading.value = true
  runAddressSearch(input)
})

/**
 * Une adresse choisie renseigne d'un coup les trois champs : ville et pays (les champs VALIDÉS) plus
 * l'adresse formatée (billingAddress, bonus). La sélection est donc ce qui rend le groupe complet.
 *
 * La résolution part APRÈS l'affichage du libellé choisi : l'utilisateur voit sa sélection sans
 * attendre l'aller-retour, et un échec le laisse avec ce qu'il a choisi plutôt qu'un champ vide.
 */
const onAddressSelected = async (suggestion: { value: string }) => {
  const match = addressSuggestions.value.find((item) => item.value === suggestion.value)

  if (!match) {
    return
  }

  try {
    const address: ResolvedAddress = await resolvePlace(match.id)
    form.city = address.city
    form.country = address.country
    form.billingAddress = address.billingAddress || suggestion.value
  } catch {
    // Résolution échouée : on garde le libellé choisi ; la saisie manuelle reste offerte juste en
    // dessous.
  }
}

// Pays pour l'autocomplétion en saisie manuelle : code ISO 3166-1 stocké, nom localisé affiché.
const countries = useCountries()

const { current: locale } = useLocale()

// Nom LOCALISÉ d'un code pays, pour reconstruire une adresse lisible en mode manuel.
const countryName = (code: string): string => {
  if ('' === code) {
    return ''
  }

  try {
    return new Intl.DisplayNames([locale.value], { type: 'region' }).of(code) ?? code
  } catch {
    return code
  }
}

// billingAddress à l'envoi : en mode recherche, Places l'a déjà posée ; en mode manuel, on la
// RECONSTRUIT à partir des champs pour qu'elle reste l'adresse complète (rue, CP + ville, pays).
const composedBillingAddress = (): string | null => {
  if (!manualAddress.value) {
    return form.billingAddress.trim() || null
  }

  const cityLine = [form.postalCode.trim(), form.city.trim()].filter(Boolean).join(' ')
  const parts = [form.street.trim(), cityLine, countryName(form.country)].filter(Boolean)

  return parts.length > 0 ? parts.join(', ') : null
}

const form = reactive({
  // Raison sociale et immatriculation SÉPARÉES : deux informations distinctes, que l'admin recoupe à
  // la revue. `name` (identité d'affichage, non-nulle au contrat) est dérivé de la raison sociale à
  // l'envoi — cf. `submit`.
  legalName: '',
  taxId: '',
  sector: '',
  city: '',
  country: '',
  // Adresse formatée. En mode recherche, posée par Places ; en mode manuel, RECOMPOSÉE au submit à
  // partir de street + postalCode + city + country. Non validée : city + country restent les requis.
  billingAddress: '',
  // Composants de l'adresse manuelle (facultatifs) : rue + numéro, et code postal. Aucun n'existe au
  // contrat API — ils alimentent billingAddress (chaîne libre), comme le format de Places.
  street: '',
  postalCode: '',
  givenName: '',
  familyName: '',
  acquisitionChannel: '',
  acquisitionChannelDetail: '',
})

type FormField = keyof typeof form

const filled = (field: FormField): boolean => form[field].trim() !== ''

// Longueurs max DÉRIVÉES du contrat (shared/types/organization-constraints.ts, généré depuis l'OpenAPI
// — plus aucun littéral recopié, plus de dérive possible). En attribut `maxlength` : le navigateur cape
// la saisie. `street` et `postalCode` n'EXISTENT PAS au contrat (ils alimentent billingAddress) → seules
// bornes UI locales, assumées.
const CONSTRAINTS = ORGANIZATION_INPUT_CONSTRAINTS

const MAX_LENGTH: Partial<Record<FormField, number>> = {
  legalName: CONSTRAINTS.legalName.maxLength,
  taxId: CONSTRAINTS.taxId.maxLength,
  city: CONSTRAINTS.city.maxLength,
  givenName: CONSTRAINTS.givenName.maxLength,
  familyName: CONSTRAINTS.familyName.maxLength,
  acquisitionChannelDetail: CONSTRAINTS.acquisitionChannelDetail.maxLength,
  street: 200,
  postalCode: 16,
}

// Longueur EXACTE du code pays, dérivée du contrat (minLength = maxLength). L'autocomplétion ISO ne
// produit qu'un code valide ou vide ; cette règle reste le garde-fou si une valeur arrivait autrement.
const COUNTRY_LENGTH = CONSTRAINTS.country.maxLength

// Validité AVANT l'envoi. La vérité métier reste au serveur (Symfony Validator) ; ici on n'attrape que
// ce qui, sinon, partirait pour revenir en 422. `maxlength` gère le trop-long ; reste la seule règle
// sémantique qui compte : le pays fait exactement COUNTRY_LENGTH lettres, donc « non vide » ne suffit pas.
const isValid = (field: FormField): boolean =>
  filled(field) && ('country' !== field || form.country.trim().length === COUNTRY_LENGTH)

/**
 * Les groupes, dans l'ordre du sens : l'entreprise, qui la représente, d'où elle vient.
 *
 * La provenance est SEULE dans le dernier groupe : une fois choisie, tout est réuni et le bouton
 * « Envoyer ma demande » (ou Entrée) soumet. Aucun envoi automatique — sélectionner une pastille ne
 * fait que sélectionner, jamais soumettre.
 */
const GROUPS = [
  { key: 'company', fields: ['legalName', 'taxId', 'sector', 'city', 'country'] },
  { key: 'contact', fields: ['givenName', 'familyName'] },
  { key: 'channel', fields: ['acquisitionChannel'] },
] as const satisfies ReadonlyArray<{ key: string; fields: readonly FormField[] }>

const ALL_FIELDS = GROUPS.flatMap((entry) => entry.fields)

// La voix parle avant que le premier groupe paraisse : le glissement part QUAND la frappe s'achève.
const ready = ref(false)
const group = ref(0)

const current = computed(() => GROUPS[group.value]!)
const isLast = computed(() => group.value === GROUPS.length - 1)

// « Autre » ouvre un champ conditionnel « Précisez » : le serveur l'exige alors (When AUTRE →
// NotBlank), et c'est la SEULE règle que l'OpenAPI n'expose pas — donc portée ici. Tant qu'il est
// requis, il compte comme un champ du parcours : il retient la jauge sous 100 %, donc l'envoi.
const AUTRE = 'AUTRE'
const detailRequired = computed(() => AUTRE === form.acquisitionChannel)

// Champs du groupe affiché, augmentés de « Précisez » quand « Autre » est choisi (dernier groupe).
const currentFields = computed<readonly FormField[]>(() =>
  detailRequired.value && 'channel' === current.value.key
    ? [...current.value.fields, 'acquisitionChannelDetail']
    : current.value.fields,
)

// Tous les champs requis pour envoyer — base + « Précisez » quand « Autre ». Dénominateur de la jauge.
const requiredFields = computed<readonly FormField[]>(() =>
  detailRequired.value ? [...ALL_FIELDS, 'acquisitionChannelDetail'] : ALL_FIELDS,
)

const canAdvance = computed(() => currentFields.value.every(isValid))

// « Continuer » n'est JAMAIS désactivé : un bouton grisé n'explique pas ce qui manque. Il reste
// cliquable et, à champs incomplets, déclenche un rappel visible. Celui-ci s'efface de lui-même dès
// que le groupe redevient complet — inutile d'insister quand tout est rempli.
const nudge = ref(false)
watch(canAdvance, (ok) => {
  if (ok) {
    nudge.value = false
  }
})

const remaining = computed(() => requiredFields.value.filter((field) => !isValid(field)).length)
const complete = computed(() => 0 === remaining.value)

// Arrondi à l'entier : une jauge qui affiche « 14,285 % » compte au lieu de rassurer.
const percent = computed(() =>
  Math.round(((requiredFields.value.length - remaining.value) / requiredFields.value.length) * 100),
)

// `role="progressbar"` sans nom accessible n'annonce qu'un pourcentage nu. Il est nommé par
// l'étiquette VISIBLE plutôt que par un `aria-label` recopié — deux sources de vérité dériveraient.
// `useId` et non un identifiant écrit en dur : le composant ne peut pas jurer d'être seul sur la page,
// et un identifiant en double casse le lien pour tout le monde.
const gaugeLabelId = useId()

const next = () => {
  if (canAdvance.value) {
    nudge.value = false
    // Dernier groupe complet : on ENVOIE (bouton « Envoyer ma demande » ou Entrée). Les groupes 1-2
    // avancent. « Autre » exige d'abord son « Précisez » (compté dans `currentFields`).
    if (isLast.value) {
      send()
    } else {
      group.value += 1
    }
    return
  }

  // Incomplet : on le SIGNALE (la consigne apparaît) et on emmène le curseur sur le premier champ
  // manquant. Dire « il manque » et montrer OÙ, plutôt que le silence d'un bouton mort.
  nudge.value = true
  const target = currentFields.value.find((field) => !isValid(field))
  if (target) {
    document.getElementById(target)?.focus()
  }
}

// Revenir en arrière ne perd RIEN : tous les champs vivent dans le même `form`, changer de groupe ne
// fait que changer ceux qu'on affiche. Pas de garde `filled` au retour — on revient justement pour
// corriger, y compris un champ vidé.
const previous = () => {
  if (group.value > 0) {
    nudge.value = false
    group.value -= 1
  }
}

// Les erreurs de champ de l'API n'ont pas de champ encadré où se poser : le groupe les rassemble
// sous lui, sinon un 422 resterait muet.
// `.value` obligatoire : `fieldErrors` est un `ref`. Un template le déballe, pas le script.
const violations = computed(() => Object.values(fieldErrors.value).filter(Boolean))

const submit = async () => {
  const legalName = form.legalName.trim()

  const created = await createOrganization({
    // `name` est l'identité d'AFFICHAGE (fiche admin, tableau recruteur, push Jarvi) et sa colonne est
    // non-nulle : on y met la raison sociale, qui EST le nom de l'entreprise. `legalName` reçoit la
    // même valeur — c'est le champ formel que l'admin lit à la revue.
    name: legalName,
    legalName,
    taxId: form.taxId.trim(),
    // Le dropdown garantit une valeur du référentiel (ou vide) ; le payload la type en enum fermé.
    // `as never` comme la provenance : la valeur runtime vient de la liste API, pas d'une saisie libre.
    sector: form.sector as never,
    city: form.city,
    country: form.country,
    // Recherche : posée par Places. Manuel : recomposée à partir de rue + CP + ville + pays.
    billingAddress: composedBillingAddress(),
    givenName: form.givenName,
    familyName: form.familyName,
    acquisitionChannel: form.acquisitionChannel as never,
    acquisitionReferrer: referrer.value,
    // « Autre » exige un détail (When AUTRE → NotBlank) ; pour tout autre canal, on n'envoie rien ici.
    acquisitionChannelDetail: detailRequired.value ? form.acquisitionChannelDetail.trim() : null,
    // Sortis du parcours : ils se renseigneront depuis l'espace du compte.
    website: null,
    jobTitle: null,
    description: null,
  })

  if (created) {
    emit('created')
  }

  return Boolean(created)
}

/**
 * ENVOI de la demande — déclenché UNIQUEMENT par un geste explicite : le bouton « Envoyer ma demande »
 * (ou Entrée), via `next()` sur le dernier groupe. Sélectionner une pastille de provenance ne fait que
 * la sélectionner. AUCUN envoi « automatique » : c'est ce qui évite une soumission surprise à la
 * navigation clavier (flèches).
 *
 * `sent` empêche le double envoi, mais se relâche sur échec pour laisser retenter — là où un verrou
 * définitif bloquerait après un 422.
 */
const sent = ref(false)

const send = async () => {
  if (sent.value || !complete.value) {
    return
  }

  sent.value = true
  const ok = await submit()
  if (!ok) {
    sent.value = false
  }
}

/**
 * `Entrée` avance d'un groupe. L'interception est OBLIGATOIRE : un `<input>` dans un `<form>`
 * déclenche la soumission implicite du navigateur, qui partirait avec un formulaire incomplet.
 * Ici, elle n'a rien à soumettre — c'est la jauge qui décide — donc elle ne fait qu'avancer.
 */
const onEnter = (event: KeyboardEvent) => {
  // Entrée avec le focus sur un bouton (Retour, Continuer) : on laisse le bouton agir. Sans ce
  // garde-fou, Entrée appellerait toujours `next()` — reculer via « Retour » au clavier avancerait.
  if ((event.target as HTMLElement).closest('button')) {
    return
  }

  event.preventDefault()
  next()
}
</script>

<template>
  <form
    class="registration-company-form flex h-full flex-col"
    @submit.prevent
    @keydown.enter="onEnter"
  >
    <!-- « Retour » en haut à gauche, HORS FLUX (absolute) : il ne pousse pas le contenu centré et
         reste au même coin quel que soit le groupe. Flèche + mot, action secondaire en muted. Il
         n'apparaît qu'à partir du 2ᵉ groupe. -->
    <Transition name="reveal">
      <button
        v-if="ready && group > 0"
        type="button"
        class="registration-company-form__back"
        @click="previous"
      >
        <Icon
          name="lucide:arrow-left"
          class="registration-company-form__back-icon"
          aria-hidden="true"
        />
        {{ $t('inscription.onboarding.back') }}
      </button>
    </Transition>

    <div class="m-auto flex w-full flex-col gap-7">
      <div>
        <!-- `key` OBLIGATOIRE : la frappe vit dans `onMounted`, qui ne s'exécute qu'une fois. Sans
             remontage, changer de groupe changerait les props sans rejouer la frappe — la voix
             resterait bloquée sur le texte du premier groupe jusqu'au bout du parcours.
             `lead` s'affiche d'emblée, `focus` se tape : chaque groupe a donc sa question posée
             (sans effet) puis sa consigne qui s'écrit. -->
        <UiTypedText
          :key="current.key"
          class="registration-company-form__lead"
          :lead="$t(`inscription.onboarding.${current.key}.lead`)"
          :focus="$t(`inscription.onboarding.${current.key}.focus`)"
          @done="ready = true"
        />
      </div>

      <!-- HAUTEUR RÉSERVÉE, calée sur le groupe le plus haut. Le bloc est centré : sa hauteur commande
           donc sa position. Sans réserve, chaque groupe ayant sa taille, tout se recentrerait à chaque
           passage et la voix monterait alors qu'elle n'a pas changé. -->
      <div class="min-h-64">
        <Transition name="reveal" mode="out-in">
          <div v-if="ready" :key="current.key" class="flex flex-col gap-6">
            <template v-if="'company' === current.key">
              <!-- Raison sociale et immatriculation SÉPARÉES, sur une même ligne : la coque impose
                   zéro scroll, un champ de plus en pleine largeur coûterait une rangée. -->
              <div class="flex gap-5">
                <RegistrationField
                  id="legalName"
                  v-model="form.legalName"
                  :label="$t('inscription.field.legalName')"
                  :maxlength="MAX_LENGTH.legalName"
                />
                <RegistrationField
                  id="taxId"
                  v-model="form.taxId"
                  :label="$t('inscription.field.taxId')"
                  :maxlength="MAX_LENGTH.taxId"
                />
              </div>
              <RegistrationField
                id="sector"
                v-model="form.sector"
                :label="$t('inscription.field.sector')"
                :placeholder="$t('inscription.field.sectorPlaceholder')"
                :options="sectors"
                :empty-label="$t('inscription.field.sectorNoResult')"
              />
              <!-- Adresse : recherche Places (proxifiée) qui renseigne ville + pays + billingAddress
                   d'un coup. Repli « saisir manuellement » — Places absent, adresse introuvable, ou
                   simple choix — vers ville + pays à la main, comme avant. -->
              <div v-if="!manualAddress" class="flex flex-col gap-2.5">
                <label class="registration-company-form__label" for="companyAddress">
                  {{ $t('inscription.field.address') }}
                </label>
                <UiRemoteCombobox
                  id="companyAddress"
                  v-model="form.billingAddress"
                  :items="addressSuggestions"
                  :loading="addressLoading"
                  :min-chars="ADDRESS_MIN_CHARS"
                  :placeholder="$t('inscription.field.addressPlaceholder')"
                  :empty-label="$t('inscription.field.addressNoResult')"
                  :loading-label="$t('inscription.field.addressLoading')"
                  @update:term="addressTerm = $event"
                  @select="onAddressSelected"
                />
                <button
                  type="button"
                  class="registration-company-form__switch"
                  @click="manualAddress = true"
                >
                  {{ $t('inscription.field.addressManual') }}
                </button>
              </div>

              <div v-else class="flex flex-col gap-3">
                <!-- Saisie manuelle : rue + code postal + ville (structurés) + pays en autocomplétion
                     ISO 3166-1. Ces champs RECOMPOSENT billingAddress au submit, comme le format de
                     Places. Ville + Pays restent les seuls requis ; rue et CP sont facultatifs. -->
                <RegistrationField
                  id="street"
                  v-model="form.street"
                  :label="$t('inscription.field.address')"
                  :placeholder="$t('inscription.field.streetPlaceholder')"
                  :maxlength="MAX_LENGTH.street"
                />
                <div class="flex gap-5">
                  <RegistrationField
                    id="postalCode"
                    v-model="form.postalCode"
                    :label="$t('inscription.field.postalCode')"
                    :placeholder="$t('inscription.field.postalCodePlaceholder')"
                    :maxlength="MAX_LENGTH.postalCode"
                  />
                  <RegistrationField
                    id="city"
                    v-model="form.city"
                    :label="$t('inscription.field.city')"
                    :placeholder="$t('inscription.field.cityPlaceholder')"
                    :maxlength="MAX_LENGTH.city"
                  />
                </div>
                <RegistrationField
                  id="country"
                  v-model="form.country"
                  :label="$t('inscription.field.country')"
                  :options="countries"
                  :placeholder="$t('inscription.field.countryPlaceholder')"
                  :empty-label="$t('inscription.field.countryNoResult')"
                />
                <button
                  v-if="placesEnabled"
                  type="button"
                  class="registration-company-form__switch"
                  @click="manualAddress = false"
                >
                  {{ $t('inscription.field.addressSearch') }}
                </button>
              </div>
            </template>

            <div v-else-if="'contact' === current.key" class="flex gap-5">
              <RegistrationField
                id="givenName"
                v-model="form.givenName"
                :label="$t('inscription.field.contactGivenName')"
                :maxlength="MAX_LENGTH.givenName"
              />
              <RegistrationField
                id="familyName"
                v-model="form.familyName"
                :label="$t('inscription.field.contactFamilyName')"
                :maxlength="MAX_LENGTH.familyName"
              />
            </div>

            <fieldset v-else class="border-0 p-0">
              <legend class="registration-company-form__label">
                {{ $t('inscription.field.acquisitionChannel') }}
              </legend>

              <!-- Des pastilles et non un `select` : les six canaux tiennent à l'écran, les montrer
                   évite d'ouvrir un menu pour découvrir un choix qui se lisait d'un coup d'œil. Et
                   le clic est le geste volontaire qui fait partir la demande. -->
              <div class="mt-3 flex flex-wrap gap-2">
                <label
                  v-for="channel in ACQUISITION_CHANNELS"
                  :key="channel"
                  class="registration-company-form__chip"
                >
                  <input
                    v-model="form.acquisitionChannel"
                    type="radio"
                    name="acquisitionChannel"
                    :value="channel"
                    class="sr-only"
                    :disabled="pending"
                  />
                  <span>{{ $t(`organization.channel.${channel}`) }}</span>
                </label>
              </div>

              <!-- « Précisez » : requis par le serveur dès que « Autre » est choisi (When AUTRE →
                   NotBlank), et c'est la SEULE règle que l'OpenAPI n'expose pas. Il devient alors le
                   geste terminal — la jauge ne franchit 100 %, et la demande ne part, qu'une fois
                   renseigné (cf. `detailRequired`, `send`). -->
              <Transition name="reveal">
                <div v-if="detailRequired" class="mt-5">
                  <RegistrationField
                    id="acquisitionChannelDetail"
                    v-model="form.acquisitionChannelDetail"
                    :label="$t('inscription.field.acquisitionChannelDetail')"
                    :placeholder="$t('inscription.field.acquisitionChannelDetailPlaceholder')"
                    :maxlength="MAX_LENGTH.acquisitionChannelDetail"
                  />
                </div>
              </Transition>
            </fieldset>
          </div>
        </Transition>
      </div>

      <!-- Rappel affiché SEULEMENT après une tentative d'avance à champs incomplets. Il double la mise
           au point du focus posé sur le premier champ vide : la consigne dit, le curseur montre. -->
      <p v-if="nudge && !canAdvance" class="registration-company-form__nudge" role="alert">
        {{ $t('inscription.onboarding.incomplete') }}
      </p>

      <p v-if="error" class="text-small text-alert" role="alert">{{ $t(error) }}</p>

      <ul
        v-if="violations.length > 0"
        class="flex flex-col gap-1 text-small text-alert"
        role="alert"
      >
        <li v-for="violation in violations" :key="violation">{{ violation }}</li>
      </ul>
    </div>

    <!-- LA JAUGE FAIT LE SOCLE DU PANNEAU. Au bas et bord à bord, elle mesure l'ÉCRAN et non la
         colonne de texte : posée dans le flux, elle n'était qu'un élément de plus à relire à chaque
         frappe. Ici elle est un décor stable qu'on surveille du coin de l'œil.
         Simple indicateur de progression : elle mesure ce qui est fait, l'envoi passant par le
         bouton « Envoyer ma demande » (ou Entrée) du dernier groupe. -->
    <Transition name="reveal">
      <footer v-if="ready" class="registration-company-form__footer">
        <div class="registration-company-form__status">
          <!-- Nom + séparateur + pourcentage. Seul le pourcentage est en accent : c'est le seul chiffre
               qui bouge, l'accent le fait suivre du coin de l'œil sans relire le libellé. -->
          <span :id="gaugeLabelId" class="registration-company-form__gauge-label">
            <span class="registration-company-form__gauge-name">
              {{ $t('inscription.onboarding.gauge.label') }}
            </span>
            <span class="registration-company-form__gauge-dot" aria-hidden="true">·</span>
            <span class="registration-company-form__gauge-percent">
              {{ $t('inscription.onboarding.gauge.value', { percent }) }}
            </span>
          </span>

          <!-- Un VRAI bouton, pas la consigne « Appuyez sur Entrée » : la touche reste le chemin rapide,
               mais elle ne peut pas être la seule affordance — au tactile il n'y a pas de clavier, et
               une phrase ne se lit pas comme une cible. Il AVANCE (« Continuer ») sauf au dernier
               groupe où il ENVOIE ; `next()` fait la bonne action (cf. script).
               `type="button"` : la soumission passe par `next()`, jamais par le submit natif — c'est ce
               qui empêche une sélection de pastille ou une touche perdue d'envoyer la demande. -->
          <UiButton type="button" @click="next">
            {{ isLast ? $t('inscription.onboarding.send') : $t('inscription.onboarding.next') }}
          </UiButton>
        </div>

        <div
          class="registration-company-form__gauge"
          role="progressbar"
          :aria-labelledby="gaugeLabelId"
          :aria-valuenow="percent"
          aria-valuemin="0"
          aria-valuemax="100"
        >
          <span :style="{ width: `${percent}%` }" />
        </div>
      </footer>
    </Transition>
  </form>
</template>

<style scoped>
/* Repère de positionnement pour le « Retour » en haut à gauche, posé en absolute hors flux. */
.registration-company-form {
  position: relative;
}

.registration-company-form__lead {
  /* Taille FLUIDE du système (sizing.css) au lieu d'un `clamp()` en dur : le hero suit l'échelle. */
  font-family: var(--font-title);
  font-weight: 700;
  font-size: var(--text-h2);
  line-height: var(--text-h2--line-height);
  letter-spacing: -0.02em;
  /* Une ligne réservée, même vide : sans elle le bloc grandit d'une ligne au démarrage de la voix,
     et comme il est centré, tout remonte de la moitié. `1lh` = une hauteur de ligne, quelle que soit
     la taille fluide. Voir UiTypedText, même règle. */
  min-height: 1lh;
}

/* Le pied DÉBORDE le padding du panneau : la jauge doit toucher les deux bords, sinon elle mesure la
   colonne de texte au lieu de l'écran. Les valeurs viennent du panneau (`--panel-pad-*`), qui les
   publie ; les recopier ici les ferait diverger dès qu'il change de gabarit. */
.registration-company-form__footer {
  flex: none;
  margin-inline: calc(var(--panel-pad-inline) * -1);
  margin-block-end: calc(var(--panel-pad-block) * -1);
}

/* `center` et non `baseline` : la ligne de base d'un libellé de 10 px n'a rien à voir avec celle d'un
   bouton à padding — alignés dessus, ils se décalent visiblement. */
.registration-company-form__status {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  /* Le texte reprend l'alignement du panneau ; seule la barre sous lui va d'un bord à l'autre. */
  padding: 0 var(--panel-pad-inline) 14px;
}

/* Aucun arrondi, contrairement à la version en flux : collée aux trois bords, une barre arrondie
   flotterait au lieu de tenir. Et pas de `height` sur le remplissage — `inset-block: 0` le fait
   suivre la piste, qui reste seule à décider de l'épaisseur. */
.registration-company-form__gauge {
  position: relative;
  height: 3px;
  background: color-mix(in srgb, var(--text) 10%, transparent);
}

.registration-company-form__gauge > span {
  position: absolute;
  inset-block: 0;
  /* `inset-inline-start` et non `left` : en arabe, la jauge se remplit depuis l'autre bord sans une
     règle de plus à maintenir. */
  inset-inline-start: 0;
  background: var(--accent);
  transition: width var(--transition-base);
}

.registration-company-form__label {
  font: 600 10px / 1.4 var(--font-ui);
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: var(--muted);
  opacity: 0.62;
  /* Insécable : le « % » ne doit jamais passer seul à la ligne. `flex: none` en prime — sans lui, le
     texte de statut à droite comprimerait le label et le forcerait à se couper. */
  white-space: nowrap;
  flex: none;
}

/* Libellé de jauge éclaté en trois morceaux : nom, séparateur, pourcentage. L'accent va au SEUL
   pourcentage — et comme l'opacité ne se surcharge pas sur un enfant, le nom porte son estompe (0.62)
   à part, sinon elle ternirait aussi l'accent. Même filet typographique que `__label`. */
.registration-company-form__gauge-label {
  display: inline-flex;
  align-items: baseline;
  gap: 6px;
  font: 600 10px / 1.4 var(--font-ui);
  letter-spacing: 0.07em;
  text-transform: uppercase;
  white-space: nowrap;
  flex: none;
}

.registration-company-form__gauge-name,
.registration-company-form__gauge-dot {
  color: var(--muted);
  opacity: 0.62;
}

.registration-company-form__gauge-percent {
  color: var(--accent);
}

/* Rappel de champs manquants : signale sans dramatiser — ce n'est pas une erreur mais une consigne.
   Accent (couleur de guidage), pas de rouge d'alerte. */
.registration-company-form__nudge {
  color: var(--accent);
  font: 600 12px / 1.4 var(--font-ui);
}

/* Bascule discrète recherche d'adresse ↔ saisie manuelle : lien secondaire en muted, aligné à gauche
   sous le champ, avivé au survol. */
.registration-company-form__switch {
  align-self: flex-start;
  border: 0;
  background: none;
  padding: 0;
  color: var(--muted);
  font: 500 12px / 1.4 var(--font-ui);
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.registration-company-form__switch:hover {
  color: var(--text);
}

/* « Retour » : action SECONDAIRE en muted — l'accent reste à « Continuer ». Posé HORS FLUX en haut à
   gauche du panneau : la flèche porte le sens du recul, le soulignement devient superflu. */
.registration-company-form__back {
  position: absolute;
  inset-block-start: 0;
  inset-inline-start: 0;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  border: 0;
  background: none;
  padding: 0;
  color: var(--muted);
  font: 600 12px / 1 var(--font-ui);
  cursor: pointer;
}

.registration-company-form__back:hover {
  color: var(--text);
}

.registration-company-form__back-icon {
  width: 15px;
  height: 15px;
}

/* Le composant chip du système : filet en `inset`, pas de bordure. L'état actif se voit sans couleur
   criarde — accent dilué, texte accent. Surtout PAS un aplat plein : la règle dit UNE SEULE action
   « filled » par écran, et c'est la jauge. */
.registration-company-form__chip {
  display: inline-flex;
  align-items: center;
  height: 32px;
  padding: 0 13px;
  border-radius: var(--radius-lg);
  box-shadow: inset 0 0 0 1px var(--line);
  font: 700 12px / 1 var(--font-ui);
  cursor: pointer;
  transition:
    background-color var(--transition-fast),
    box-shadow var(--transition-fast),
    color var(--transition-fast);
}

.registration-company-form__chip:hover {
  background: var(--sl-hover);
}

/* Le focus vit sur un contrôle invisible : sans cette remontée sur l'étiquette, la tabulation
   traverserait les six canaux sans qu'on voie jamais où l'on est. */
.registration-company-form__chip:has(input:focus-visible) {
  outline: 2px solid var(--accent);
  outline-offset: 2px;
}

.registration-company-form__chip:has(input:checked) {
  background: var(--accent-soft);
  color: var(--accent);
  box-shadow: none;
}

.registration-company-form__chip:has(input:disabled) {
  cursor: default;
  opacity: 0.5;
}
</style>
