<script setup lang="ts">
import type { CreditTransactionCollection } from '#shared/types/reveal'

definePageMeta({ layout: 'cockpit' })

const { balance, loadBalance } = useReveal()
const request = useRequestFetch()

const transactions = ref<CreditTransactionCollection['items']>([])

const { pending, error } = useAsyncData('entreprise:credit-journal', async () => {
  const [, journal] = await Promise.all([
    loadBalance(),
    request<CreditTransactionCollection>('/api/credits/transactions'),
  ])
  transactions.value = journal.items
  return true
})
</script>

<template>
  <div data-page="credits" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-section="navigation">
      <span class="text-small text-muted">{{ $t('nav.entreprise.account') }}</span>
      <template #action><AccountNav /></template>
    </Gutter>

    <section
      data-section="content"
      class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface"
    >
      <div class="flex h-full flex-col gap-block p-block">
        <h1 class="font-display text-h2">{{ $t('page.entreprise.credits.title') }}</h1>
        <p class="max-w-readable text-muted">{{ $t('page.entreprise.credits.lead') }}</p>
        <SearchState v-if="pending" tone="loading" :title="$t('search.loading')" />
        <SearchState
          v-else-if="error"
          tone="error"
          :title="$t('page.entreprise.credits.error')"
          :hint="$t('search.state.errorHint')"
        />
        <template v-else>
          <p class="font-display text-h1">
            {{ $t('search.reveal.balance', { count: balance ?? 0 }) }}
          </p>
          <p class="max-w-readable text-muted">{{ $t('page.entreprise.credits.howTo') }}</p>
          <AccountCreditJournal :transactions="transactions" />
        </template>
      </div>
    </section>
  </div>
</template>
