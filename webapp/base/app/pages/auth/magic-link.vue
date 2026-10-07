<script setup lang="ts">
/**
 * Atterrissage du magic link (`/auth/magic-link?token=…`) — chemin construit par l'API.
 *
 * Vit dans le socle : les deux espaces qui authentifient par lien (candidat, entreprise) y
 * atterrissent, chacun sur SON host — l'API choisit le sous-domaine selon le destinataire.
 */
const route = useRoute()
const space = useSpace()
const { localePath } = useLocale()
const { fetch: refreshSession, user } = useUserSession()
const { consumeLink, failed } = useMagicLink()

// Un compte sans rôle entreprise sort du tunnel d'inscription : il lui reste à déclarer sa société.
// Avec un rôle, il a déjà la sienne — sa place est dans l'espace.
const destination = (): string => {
  const isCompanyMember = (user.value?.roles ?? []).some((role) => role.startsWith('ROLE_COMPANY_'))

  return 'entreprise' === space && !isCompanyMember ? '/inscription/entreprise' : '/'
}

// Le token ne fait que transiter : le BFF l'échange contre une session scellée, puis il disparaît
// de l'URL (`replace`) — une preuve à usage unique n'a rien à faire dans l'historique.
onMounted(async () => {
  const token = String(route.query.token ?? '')

  if (!token || !(await consumeLink(token))) {
    failed.value = true

    return
  }

  await refreshSession()
  await navigateTo(localePath(destination()), { replace: true })
})
</script>

<template>
  <UiCard as="section" class="auth-magic-link mx-auto flex max-w-form flex-col gap-4">
    <template v-if="failed">
      <h1 class="text-h2 font-bold">{{ $t('inscription.link.invalidTitle') }}</h1>
      <p>{{ $t('inscription.link.invalidMessage') }}</p>
      <UiButton :to="localePath('/login')">{{ $t('inscription.link.restart') }}</UiButton>
    </template>

    <template v-else>
      <h1 class="text-h2 font-bold">{{ $t('inscription.link.checking') }}</h1>
      <p class="text-muted">{{ $t('inscription.link.wait') }}</p>
    </template>
  </UiCard>
</template>
