<script setup lang="ts">
const space = useSpace()
const { loggedIn } = useUserSession()
const { logout } = useLogout()
const { localePath } = useLocale()
</script>

<template>
  <template v-if="space !== 'public'">
    <UiButton v-if="loggedIn" @click="logout">
      {{ $t('nav.logout') }}
    </UiButton>
    <UiButton v-else :to="localePath('/login')">
      {{ $t('nav.login') }}
    </UiButton>
  </template>
</template>
