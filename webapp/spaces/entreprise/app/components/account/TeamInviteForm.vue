<script setup lang="ts">
/**
 * Inviter un collègue dans l'équipe.
 *
 * Extrait de la page : une page ORCHESTRE, le code interactif — champs, liaison, soumission — vit
 * dans un composant. Le verrou de conventions ne l'avait pas vu, sa lecture du template s'arrêtant
 * au premier `</template>` ; la règle vaut quand même.
 *
 * Pas de sélecteur de rôle : l'API n'accepte que `ROLE_COMPANY_MEMBER`, et un champ à valeur unique
 * ment sur le choix qu'il offre.
 */
const emit = defineEmits<{ invite: [email: string] }>()

const { busy = false } = defineProps<{ busy?: boolean }>()

const email = ref('')
const failed = ref(false)

/** Le parent ne rend pas la main : il annonce l'échec en rappelant `fail()`. */
const submit = (): void => {
  if ('' === email.value.trim() || busy) {
    return
  }

  failed.value = false
  emit('invite', email.value.trim())
}

const clear = (): void => {
  email.value = ''
}

const fail = (): void => {
  failed.value = true
}

defineExpose({ clear, fail })
</script>

<template>
  <form class="account-team-invite-form flex flex-col gap-element" @submit.prevent="submit()">
    <UiField
      id="team-invite"
      v-slot="{ describedBy }"
      :label="$t('page.entreprise.team.invite.label')"
      :hint="$t('page.entreprise.team.invite.hint')"
    >
      <UiInlineInput
        id="team-invite"
        v-model="email"
        type="email"
        :placeholder="$t('page.entreprise.team.invite.placeholder')"
        :described-by="describedBy"
      />
    </UiField>

    <p v-if="failed" class="max-w-readable text-small text-alert" role="alert">
      {{ $t('page.entreprise.team.invite.failed') }}
    </p>

    <div class="flex justify-end">
      <UiButton type="submit" :disabled="busy">
        {{ $t('page.entreprise.team.invite.submit') }}
      </UiButton>
    </div>
  </form>
</template>
