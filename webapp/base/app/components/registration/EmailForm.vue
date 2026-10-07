<script setup lang="ts">
const emit = defineEmits<{ sent: [email: string] }>()

const { requestLink, pending, failed } = useMagicLink()

const email = ref('')

const submit = async () => {
  if (await requestLink(email.value)) {
    emit('sent', email.value)
  }
}
</script>

<template>
  <form class="registration-email-form flex flex-col gap-4" @submit.prevent="submit">
    <UiField id="email" :label="$t('auth.email')">
      <input id="email" v-model="email" type="email" name="email" autocomplete="email" required />
    </UiField>

    <p v-if="failed" class="text-sm text-alert" role="alert">
      {{ $t('inscription.error.unexpected') }}
    </p>

    <UiButton type="submit" :disabled="pending">
      {{ $t('inscription.start.submit') }}
    </UiButton>
  </form>
</template>
