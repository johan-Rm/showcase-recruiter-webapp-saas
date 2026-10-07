<script setup lang="ts">
/**
 * La fiche de l'entreprise, en saisie.
 *
 * Extrait de la page : une page ORCHESTRE, le code interactif vit dans un composant. Le composant
 * porte la saisie ET son statut d'enregistrement — c'est lui qui sait ce que l'utilisateur a tapé,
 * la page ne connaît que la fiche reçue et celle qu'on lui rend.
 *
 * La PROVENANCE n'y figure pas : figée à l'inscription, le `PUT` l'ignore. La montrer sans permettre
 * de la changer serait un piège.
 */
import type { Organization } from '#shared/types/organization'

type Props = {
  /** La fiche servie par l'API — le composant en dérive sa saisie. */
  organization: Organization
  /** Faux pour un membre : il lit la fiche, il ne l'écrit pas. */
  editable?: boolean
  busy?: boolean
}

const { organization, editable = false, busy = false } = defineProps<Props>()

const emit = defineEmits<{ save: [fields: CompanyFields] }>()

/** Exactement les champs que le processeur accepte de réécrire. */
export type CompanyFields = {
  name: string
  legalName: string
  taxId: string
  country: string
  city: string
  billingAddress: string
  website: string
  sector: string
  description: string
}

const { data: sectors } = useSectors()

const status = ref<'' | 'saved' | 'failed'>('')

// `?? ''` et non `?? null` : un `<input>` lié à `null` affiche « null ». Le relais retransforme les
// chaînes vides en `null` à l'écriture — c'est là que la nuance a un sens, pas dans le champ.
const toFields = (from: Organization): CompanyFields => ({
  name: from.name ?? '',
  legalName: from.legalName ?? '',
  taxId: from.taxId ?? '',
  country: from.country ?? '',
  city: from.city ?? '',
  billingAddress: from.billingAddress ?? '',
  website: from.website ?? '',
  sector: from.sector ?? '',
  description: from.description ?? '',
})

const fields = reactive<CompanyFields>(toFields(organization))

// La fiche re-servie après enregistrement écrase la saisie : le serveur normalise ce qu'il accepte,
// et réafficher la saisie laisserait croire qu'une valeur a été retenue telle quelle.
watch(
  () => organization,
  (next) => Object.assign(fields, toFields(next)),
)

const submit = (): void => {
  if (!editable || busy) {
    return
  }

  status.value = ''
  emit('save', { ...fields })
}

defineExpose({
  saved: () => (status.value = 'saved'),
  failed: () => (status.value = 'failed'),
})
</script>

<template>
  <form
    class="account-company-form flex max-w-readable flex-col gap-block"
    @submit.prevent="submit()"
  >
    <UiField id="company-name" v-slot="{ describedBy }" :label="$t('organization.name')">
      <UiInlineInput
        id="company-name"
        v-model="fields.name"
        :disabled="!editable"
        :described-by="describedBy"
      />
    </UiField>

    <div class="grid gap-block sm:grid-cols-2">
      <UiField id="company-legal" v-slot="{ describedBy }" :label="$t('organization.legalName')">
        <UiInlineInput
          id="company-legal"
          v-model="fields.legalName"
          :disabled="!editable"
          :described-by="describedBy"
        />
      </UiField>

      <UiField id="company-tax" v-slot="{ describedBy }" :label="$t('organization.taxId')">
        <UiInlineInput
          id="company-tax"
          v-model="fields.taxId"
          :disabled="!editable"
          :described-by="describedBy"
        />
      </UiField>
    </div>

    <div class="grid gap-block sm:grid-cols-2">
      <UiField id="company-country" v-slot="{ describedBy }" :label="$t('organization.country')">
        <UiInlineInput
          id="company-country"
          v-model="fields.country"
          :maxlength="2"
          :disabled="!editable"
          :described-by="describedBy"
        />
      </UiField>

      <UiField id="company-city" v-slot="{ describedBy }" :label="$t('organization.city')">
        <UiInlineInput
          id="company-city"
          v-model="fields.city"
          :disabled="!editable"
          :described-by="describedBy"
        />
      </UiField>
    </div>

    <UiField
      id="company-billing"
      v-slot="{ describedBy }"
      :label="$t('organization.billingAddress')"
    >
      <UiInlineInput
        id="company-billing"
        v-model="fields.billingAddress"
        :disabled="!editable"
        :described-by="describedBy"
      />
    </UiField>

    <div class="grid gap-block sm:grid-cols-2">
      <UiField id="company-website" v-slot="{ describedBy }" :label="$t('organization.website')">
        <UiInlineInput
          id="company-website"
          v-model="fields.website"
          type="url"
          :placeholder="$t('organization.websitePlaceholder')"
          :disabled="!editable"
          :described-by="describedBy"
        />
      </UiField>

      <!-- Liste FERMÉE : `Assert\Choice` la referme côté serveur, le champ propose sans inventer. -->
      <UiField id="company-sector" v-slot="{ describedBy }" :label="$t('organization.sector')">
        <UiInlineSelect
          id="company-sector"
          v-model="fields.sector"
          :options="sectors.map((value) => ({ value, label: value }))"
          :placeholder="$t('page.entreprise.company.sectorPlaceholder')"
          :empty-label="$t('search.primary.noResult')"
          :disabled="!editable"
          :described-by="describedBy"
        />
      </UiField>
    </div>

    <UiField
      id="company-description"
      v-slot="{ describedBy }"
      :label="$t('organization.description')"
    >
      <UiInlineInput
        id="company-description"
        v-model="fields.description"
        :disabled="!editable"
        :described-by="describedBy"
      />
    </UiField>

    <p v-if="'saved' === status" class="text-small text-accent" role="status">
      {{ $t('page.entreprise.company.saved') }}
    </p>
    <p v-else-if="'failed' === status" class="text-small text-alert" role="alert">
      {{ $t('page.entreprise.company.failed') }}
    </p>

    <div v-if="editable" class="flex justify-end">
      <UiButton type="submit" :disabled="busy">
        {{ $t('page.entreprise.company.submit') }}
      </UiButton>
    </div>
  </form>
</template>
