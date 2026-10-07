<script setup lang="ts">
definePageMeta({ layout: 'cockpit' })

const { sections } = useAccountSections()
const { localePath } = useLocale()
</script>

<template>
  <div data-page="mon-compte" class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter data-section="navigation">
      <span class="text-small text-muted">{{ $t('nav.entreprise.account') }}</span>
    </Gutter>

    <section
      data-section="content"
      class="relative min-h-0 flex-1 overflow-auto rounded-lg bg-surface"
    >
      <div class="flex h-full flex-col gap-block p-block">
        <h1 class="font-display text-h2">{{ $t('page.entreprise.account.title') }}</h1>
        <p class="max-w-readable text-muted">{{ $t('page.entreprise.account.lead') }}</p>
        <ul class="grid gap-element sm:grid-cols-2">
          <li v-for="section in sections" :key="section.key">
            <NuxtLink
              :to="localePath(section.to)"
              class="flex h-full flex-col gap-1 rounded-lg bg-elevated p-block text-text no-underline transition-colors hover:bg-accent-soft"
            >
              <strong class="font-display text-h3">{{
                $t(`page.entreprise.${section.key}.title`)
              }}</strong>
              <span class="max-w-readable text-small text-muted">{{
                $t(`page.entreprise.${section.key}.lead`)
              }}</span>
            </NuxtLink>
          </li>
        </ul>
      </div>
    </section>
  </div>
</template>
