<script setup lang="ts">
import type { OrganizationReviewCard } from '#shared/types/organization'

interface Props {
  organization: OrganizationReviewCard
  pending: boolean
}

const { organization, pending } = defineProps<Props>()

const { localePath } = useLocale()

const emit = defineEmits<{ decide: [decision: 'validate' | 'reject'] }>()

// Le refus ferme le parcours d'inscription : on le fait confirmer, jamais sur un clic isolé.
const confirmingRejection = ref(false)

const reject = () => {
  confirmingRejection.value = false
  emit('decide', 'reject')
}
</script>

<template>
  <UCard as="article" class="review-organization-card" :ui="{ body: 'flex flex-col gap-4' }">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <h2 class="text-lg font-semibold">
        <!-- Le nom mène à la fiche : c'est le seul chemin vers le solde et le journal de crédits. -->
        <NuxtLink
          :to="localePath(`/entreprises/${organization.id}`)"
          class="transition-colors hover:text-accent"
        >
          {{ organization.name }}
        </NuxtLink>
      </h2>
      <UBadge color="primary" variant="soft">{{
        $t(`organization.status.${organization.validationStatus}`)
      }}</UBadge>
    </div>

    <dl class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
      <div v-if="organization.legalName">
        <dt class="text-sm text-muted">{{ $t('admin.organizations.legalName') }}</dt>
        <dd>{{ organization.legalName }}</dd>
      </div>
      <div v-if="organization.taxId">
        <dt class="text-sm text-muted">{{ $t('admin.organizations.taxId') }}</dt>
        <dd>{{ organization.taxId }}</dd>
      </div>
      <div v-if="organization.sector">
        <dt class="text-sm text-muted">{{ $t('admin.organizations.sector') }}</dt>
        <dd>{{ organization.sector }}</dd>
      </div>
      <div v-if="organization.location">
        <dt class="text-sm text-muted">{{ $t('admin.organizations.location') }}</dt>
        <dd>{{ organization.location }}</dd>
      </div>
      <div v-if="organization.website">
        <dt class="text-sm text-muted">{{ $t('admin.organizations.website') }}</dt>
        <dd>
          <a :href="organization.website" target="_blank" rel="noopener noreferrer">
            {{ organization.website }}
          </a>
        </dd>
      </div>
      <!-- Provenance : signal de légitimité de la revue (cf. cadrage inscription.md). -->
      <div v-if="organization.acquisitionChannel">
        <dt class="text-sm text-muted">{{ $t('admin.organizations.acquisitionChannel') }}</dt>
        <dd>
          {{ $t(`organization.channel.${organization.acquisitionChannel}`) }}
          <template v-if="organization.acquisitionChannelDetail">
            — {{ organization.acquisitionChannelDetail }}
          </template>
        </dd>
      </div>
    </dl>

    <p v-if="organization.description">{{ organization.description }}</p>

    <div v-if="organization.validationStatus === 'EN_REVUE'" class="flex flex-wrap gap-3">
      <UButton :disabled="pending" @click="emit('decide', 'validate')">
        {{ $t('admin.organizations.validate') }}
      </UButton>

      <template v-if="confirmingRejection">
        <UButton :disabled="pending" @click="reject">
          {{ $t('admin.organizations.confirmReject') }}
        </UButton>
        <UButton variant="soft" @click="void (confirmingRejection = false)">
          {{ $t('admin.organizations.cancel') }}
        </UButton>
      </template>
      <UButton v-else variant="soft" :disabled="pending" @click="void (confirmingRejection = true)">
        {{ $t('admin.organizations.reject') }}
      </UButton>
    </div>
  </UCard>
</template>
