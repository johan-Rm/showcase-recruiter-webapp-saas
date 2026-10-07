<script setup lang="ts">
/**
 * Changer son mot de passe — le seul geste de « Mon profil ».
 *
 * L'ANCIEN est exigé : c'est ce qui distingue un changement volontaire d'une prise de contrôle par
 * une session volée.
 *
 * La checklist de robustesse vient de `usePasswordPolicy`, partagée avec la définition de mot de
 * passe : les deux écrans doivent dire la même chose, et l'API reste juge.
 */
const emit = defineEmits<{ change: [payload: { currentPassword: string; newPassword: string }] }>()

const { busy = false } = defineProps<{ busy?: boolean }>()

const currentPassword = ref('')
const newPassword = ref('')
const status = ref<'' | 'saved' | 'failed'>('')

const { rules, satisfied } = usePasswordPolicy(newPassword)

const submit = (): void => {
  if (busy || !satisfied.value || '' === currentPassword.value) {
    return
  }

  status.value = ''
  emit('change', { currentPassword: currentPassword.value, newPassword: newPassword.value })
}

defineExpose({
  saved: () => {
    status.value = 'saved'
    currentPassword.value = ''
    newPassword.value = ''
  },
  // L'ancien mot de passe reste : c'est le nouveau qu'on refait, pas la preuve d'identité.
  failed: () => (status.value = 'failed'),
})
</script>

<template>
  <form
    class="account-password-form flex max-w-readable flex-col gap-block"
    @submit.prevent="submit()"
  >
    <UiField
      id="password-current"
      v-slot="{ describedBy }"
      :label="$t('page.entreprise.profile.currentPassword')"
    >
      <UiInlineInput
        id="password-current"
        v-model="currentPassword"
        type="password"
        autocomplete="current-password"
        :described-by="describedBy"
      />
    </UiField>

    <UiField
      id="password-new"
      v-slot="{ describedBy }"
      :label="$t('page.entreprise.profile.newPassword')"
    >
      <UiInlineInput
        id="password-new"
        v-model="newPassword"
        type="password"
        autocomplete="new-password"
        :described-by="describedBy"
      />
    </UiField>

    <!-- La checklist montre LAQUELLE manque : un score global dirait « faible » sans dire quoi faire. -->
    <ul class="flex flex-wrap gap-element">
      <li v-for="rule in rules" :key="rule.key">
        <UBadge
          :color="rule.met ? 'primary' : 'neutral'"
          :variant="rule.met ? 'soft' : 'subtle'"
          :icon="rule.met ? 'i-lucide-check' : undefined"
        >
          {{ $t(`auth.passwordRule.${rule.key}`) }}
        </UBadge>
      </li>
    </ul>

    <p v-if="'saved' === status" class="text-small text-accent" role="status">
      {{ $t('page.entreprise.profile.saved') }}
    </p>
    <p v-else-if="'failed' === status" class="text-small text-alert" role="alert">
      {{ $t('page.entreprise.profile.failed') }}
    </p>

    <div class="flex justify-end">
      <UiButton type="submit" :disabled="busy || !satisfied || '' === currentPassword">
        {{ $t('page.entreprise.profile.submit') }}
      </UiButton>
    </div>
  </form>
</template>
