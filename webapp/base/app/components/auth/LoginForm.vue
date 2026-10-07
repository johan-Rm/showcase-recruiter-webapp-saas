<script setup lang="ts">
const { login, pending, error } = useLogin()

const email = ref('')
const password = ref('')
const passwordVisible = ref(false)
</script>

<template>
  <form class="auth-login-form flex flex-col gap-4" @submit.prevent="login(email, password)">
    <UiField id="email" :label="$t('auth.email')">
      <input id="email" v-model="email" type="email" name="email" autocomplete="email" required />
    </UiField>

    <UiField id="password" :label="$t('auth.password')">
      <div class="relative">
        <!-- `pe`/`end` et pas `pr`/`right` : en arabe, le bouton bascule du bon côté du champ. -->
        <input
          id="password"
          v-model="password"
          :type="passwordVisible ? 'text' : 'password'"
          class="w-full pe-12"
          name="current-password"
          autocomplete="current-password"
          required
        />

        <button
          type="button"
          class="absolute end-1 top-1 bottom-1 flex w-10 cursor-pointer items-center justify-center rounded-md text-muted transition-colors hover:text-text focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
          :aria-label="$t(passwordVisible ? 'auth.hidePassword' : 'auth.showPassword')"
          :aria-pressed="passwordVisible"
          @click="passwordVisible = !passwordVisible"
        >
          <Icon :name="passwordVisible ? 'lucide:eye-off' : 'lucide:eye'" aria-hidden="true" />
        </button>
      </div>
    </UiField>

    <p v-if="error" class="text-small text-alert" role="alert">{{ $t(error) }}</p>

    <UiButton type="submit" :disabled="pending" class="mt-2 w-full justify-center">
      {{ $t('auth.submit') }}
    </UiButton>
  </form>
</template>
