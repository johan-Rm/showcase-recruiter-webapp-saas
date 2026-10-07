<script setup lang="ts">
import type { OrganizationMemberCollection } from '#shared/types/organization'

definePageMeta({ layout: 'cockpit' })

const { user } = useUserSession()
const request = useRequestFetch()
const me = ref<OrganizationMemberCollection['items'][number] | null>(null)
const busy = ref(false)
const form = useTemplateRef('form')

const { pending, error } = useAsyncData('entreprise:profile', async () => {
  const team = await request<OrganizationMemberCollection>('/api/organization/members')
  me.value = team.items.find((member) => member.contactEmail === user.value?.email) ?? null
  return true
})

const change = async (payload: { currentPassword: string; newPassword: string }): Promise<void> => {
  if (busy.value) return
  busy.value = true
  try {
    await request('/api/me/password', { method: 'PUT', body: payload })
    form.value?.saved()
  } catch {
    form.value?.failed()
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div data-page="mon-profil" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-section="navigation">
      <span class="text-small text-muted">{{ $t('nav.entreprise.account') }}</span>
      <template #action><AccountNav /></template>
    </Gutter>

    <section
      data-section="content"
      class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface"
    >
      <div class="flex h-full flex-col gap-block p-block">
        <h1 class="font-display text-h2">{{ $t('page.entreprise.profile.title') }}</h1>
        <p class="max-w-readable text-muted">{{ $t('page.entreprise.profile.lead') }}</p>
        <SearchState v-if="pending" tone="loading" :title="$t('search.loading')" />
        <SearchState
          v-else-if="error"
          tone="error"
          :title="$t('page.entreprise.profile.error')"
          :hint="$t('search.state.errorHint')"
        />

        <template v-else>
          <section data-section="identity" class="flex flex-col gap-element">
            <h2 class="text-label text-muted">{{ $t('page.entreprise.profile.identity') }}</h2>
            <dl class="grid max-w-readable gap-element sm:grid-cols-2">
              <div class="flex flex-col">
                <dt class="text-small text-muted">{{ $t('page.entreprise.profile.name') }}</dt>
                <dd>{{ me ? `${me.givenName} ${me.familyName}` : '—' }}</dd>
              </div>
              <div class="flex flex-col">
                <dt class="text-small text-muted">{{ $t('page.entreprise.profile.email') }}</dt>
                <dd>{{ user?.email ?? '—' }}</dd>
              </div>
              <div class="flex flex-col">
                <dt class="text-small text-muted">{{ $t('page.entreprise.profile.role') }}</dt>
                <dd>{{ me ? $t(`page.entreprise.team.role.${me.organizationRole}`) : '—' }}</dd>
              </div>
              <div class="flex flex-col">
                <dt class="text-small text-muted">{{ $t('page.entreprise.profile.jobTitle') }}</dt>
                <dd>{{ me?.jobTitle || '—' }}</dd>
              </div>
            </dl>
            <p class="max-w-readable text-small text-muted">
              {{ $t('page.entreprise.profile.readOnly') }}
            </p>
          </section>

          <section data-section="password" class="flex flex-col gap-element">
            <h2 class="text-label text-muted">{{ $t('page.entreprise.profile.password') }}</h2>
            <AccountPasswordForm ref="form" :busy="busy" @change="change" />
          </section>
        </template>
      </div>
    </section>
  </div>
</template>
