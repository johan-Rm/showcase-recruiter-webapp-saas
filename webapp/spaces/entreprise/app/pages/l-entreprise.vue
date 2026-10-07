<script setup lang="ts">
import type { Organization } from '#shared/types/organization'

definePageMeta({ layout: 'cockpit' })

const { isOwner } = useAccountSections()
const request = useRequestFetch()
const organization = ref<Organization | null>(null)
const busy = ref(false)
const form = useTemplateRef('form')

const { pending, error } = useAsyncData('entreprise:organization', async () => {
  organization.value = await request<Organization>('/api/organization')
  return true
})

const save = async (fields: Record<string, string>): Promise<void> => {
  if (busy.value) return
  busy.value = true
  try {
    organization.value = await request<Organization>('/api/organization', {
      method: 'PUT',
      body: fields,
    })
    form.value?.saved()
  } catch {
    form.value?.failed()
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div data-page="l-entreprise" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-section="navigation">
      <span class="text-small text-muted">{{ $t('nav.entreprise.account') }}</span>
      <template #action><AccountNav /></template>
    </Gutter>

    <section
      data-section="content"
      class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface"
    >
      <div class="flex h-full flex-col gap-block p-block">
        <h1 class="font-display text-h2">{{ $t('page.entreprise.company.title') }}</h1>
        <p class="max-w-readable text-muted">{{ $t('page.entreprise.company.lead') }}</p>
        <SearchState v-if="pending" tone="loading" :title="$t('search.loading')" />
        <SearchState
          v-else-if="error || !organization"
          tone="error"
          :title="$t('page.entreprise.company.error')"
          :hint="$t('search.state.errorHint')"
        />
        <AccountCompanyForm
          v-else
          ref="form"
          :organization="organization"
          :editable="isOwner"
          :busy="busy"
          @save="save"
        />
      </div>
    </section>
  </div>
</template>
