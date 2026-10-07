<script setup lang="ts">
const { loggedIn, user } = useUserSession()
</script>

<template>
  <p v-if="loggedIn && user" class="auth-session-hint text-small text-muted">
    {{ $t('demo.loggedInAs', { email: user.email }) }}
  </p>
</template>
