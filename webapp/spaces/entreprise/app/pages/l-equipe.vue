<script setup lang="ts">
definePageMeta({ layout: 'cockpit' })

const { members, invitations, invite, remove, pending, error } = useTeam()
const { isOwner } = useAccountSections()
const { user } = useUserSession()
const busy = ref(false)
const inviteForm = useTemplateRef('inviteForm')
const isSelf = (contactEmail: string | null): boolean =>
  Boolean(contactEmail) && contactEmail === user.value?.email

const send = async (address: string): Promise<void> => {
  busy.value = true
  try {
    await invite(address)
    inviteForm.value?.clear()
  } catch {
    inviteForm.value?.fail()
  } finally {
    busy.value = false
  }
}

const drop = async (member: (typeof members.value)[number]): Promise<void> => {
  busy.value = true
  try {
    await remove(member)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div data-page="l-equipe" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-section="navigation">
      <span class="text-small text-muted">{{ $t('nav.entreprise.account') }}</span>
      <template #action><AccountNav /></template>
    </Gutter>

    <section
      data-section="content"
      class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface"
    >
      <div class="flex h-full flex-col gap-block p-block">
        <h1 class="font-display text-h2">{{ $t('page.entreprise.team.title') }}</h1>
        <p class="max-w-readable text-muted">{{ $t('page.entreprise.team.lead') }}</p>
        <SearchState v-if="pending" tone="loading" :title="$t('search.loading')" />
        <SearchState
          v-else-if="error"
          tone="error"
          :title="$t('page.entreprise.team.error')"
          :hint="$t('search.state.errorHint')"
        />

        <template v-else>
          <section data-section="members" class="flex flex-col gap-element">
            <h2 class="text-label text-muted">
              {{ $t('page.entreprise.team.members', { count: members.length }) }}
            </h2>
            <ul class="flex flex-col divide-y divide-line">
              <li
                v-for="member in members"
                :key="member.id"
                class="flex flex-wrap items-center gap-element py-element"
              >
                <div class="flex min-w-0 flex-1 flex-col">
                  <span class="truncate font-semibold"
                    >{{ member.givenName }} {{ member.familyName }}</span
                  >
                  <span class="truncate text-small text-muted"
                    >{{ member.contactEmail
                    }}<template v-if="member.jobTitle"> · {{ member.jobTitle }}</template></span
                  >
                </div>
                <UBadge
                  :color="'ROLE_COMPANY_OWNER' === member.organizationRole ? 'primary' : 'neutral'"
                  variant="subtle"
                  >{{ $t(`page.entreprise.team.role.${member.organizationRole}`) }}</UBadge
                >
                <UButton
                  v-if="isOwner && !isSelf(member.contactEmail)"
                  icon="i-lucide-user-minus"
                  color="neutral"
                  variant="ghost"
                  size="xs"
                  :disabled="busy"
                  :aria-label="$t('page.entreprise.team.remove', { name: member.givenName })"
                  @click="drop(member)"
                />
              </li>
            </ul>
          </section>

          <section
            v-if="invitations.length"
            data-section="invitations"
            class="flex flex-col gap-element"
          >
            <h2 class="text-label text-muted">
              {{ $t('page.entreprise.team.pending', { count: invitations.length }) }}
            </h2>
            <ul class="flex flex-col divide-y divide-line">
              <li
                v-for="invitation in invitations"
                :key="invitation.id"
                class="flex flex-wrap items-center gap-element py-element"
              >
                <span class="min-w-0 flex-1 truncate">{{ invitation.email }}</span>
                <span class="text-small text-muted">{{
                  $t('page.entreprise.team.expires', { date: $d(new Date(invitation.expiresAt)) })
                }}</span>
              </li>
            </ul>
          </section>

          <AccountTeamInviteForm v-if="isOwner" ref="inviteForm" :busy="busy" @invite="send" />
        </template>
      </div>
    </section>
  </div>
</template>
