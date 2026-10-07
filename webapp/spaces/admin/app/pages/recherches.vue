<script setup lang="ts">
// Les recherches qui débordent — inventaire de supervision, en lecture seule.
//
// Une recherche d'entreprise ne sert que cent profils tout en annonçant son nombre réel de
// correspondances. Cet écran est le SEUL endroit où l'écart se voit côté showcase-recruiter-webapp-saas : il dit soit une
// recherche trop large, qu'on peut aider à resserrer, soit un métier où le vivier déborde.
//
// Rien ne s'y corrige. Agir sur la recherche d'un client — la fermer, la modifier, écrire à son
// propriétaire — est une décision produit qui n'a pas été prise.
definePageMeta({ layout: 'dashboard' })

const { t, current } = useLocale()
usePageTitle(() => t('page.admin.recherches'))

const { rows, total, page, totalPages, state } = useOverflowingSearches()

// Date lisible, et lisible À VOIX HAUTE : l'attribut `datetime` porte la valeur machine, le texte
// porte la version que le lecteur d'écran prononcera.
//
// La langue vient de l'APPLICATION et non du navigateur : sans elle, un poste configuré en anglais
// affiche « August 12, 2026 » au milieu d'un écran en français.
const formatDate = (value: string): string =>
  new Date(value).toLocaleDateString(current.value, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <h1 class="sr-only">{{ $t('page.admin.recherches') }}</h1>
        <p class="text-muted">{{ $t('admin.overflow.count', { count: total }) }}</p>
      </div>
    </template>

    <div class="flex flex-col gap-block">
      <p v-if="'error' === state" class="text-alert" role="alert">
        {{ $t('admin.overflow.error') }}
      </p>
      <p v-else-if="'loading' === state" class="text-muted">{{ $t('admin.overflow.loading') }}</p>
      <p v-else-if="'empty' === state" class="text-muted">{{ $t('admin.overflow.empty') }}</p>

      <template v-else>
        <!-- Table ≥ md ; cartes empilées < md — sept colonnes ne tiennent pas sur un téléphone. -->
        <div class="hidden overflow-x-auto md:block">
          <table class="w-full text-sm">
            <caption class="sr-only">
              {{
                $t('admin.overflow.caption')
              }}
            </caption>
            <thead class="text-left text-muted">
              <tr>
                <th scope="col" class="p-element">{{ $t('admin.overflow.column.search') }}</th>
                <th scope="col" class="p-element">
                  {{ $t('admin.overflow.column.organization') }}
                </th>
                <th scope="col" class="p-element">{{ $t('admin.overflow.column.owner') }}</th>
                <th scope="col" class="p-element text-right">
                  {{ $t('admin.overflow.column.matches') }}
                </th>
                <th scope="col" class="p-element text-right">
                  {{ $t('admin.overflow.column.hidden') }}
                </th>
                <th scope="col" class="p-element">{{ $t('admin.overflow.column.state') }}</th>
                <th scope="col" class="p-element">{{ $t('admin.overflow.column.activity') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.id" class="border-t border-border">
                <th scope="row" class="p-element text-left font-medium">
                  {{ row.title || $t('admin.overflow.unnamed') }}
                  <span v-if="row.isAutoNamed" class="block text-xs font-normal text-muted">
                    {{ $t('admin.overflow.fromCriteria') }}
                  </span>
                </th>
                <td class="p-element">{{ row.organizationName }}</td>
                <td class="p-element">{{ row.ownerName }}</td>
                <!-- Le nombre RÉEL, jamais « 100+ » : c'est lui qui distingue une recherche à
                     resserrer d'une demande que le vivier couvre largement. -->
                <td class="p-element text-right tabular-nums font-medium">{{ row.matchCount }}</td>
                <td class="p-element text-right tabular-nums text-muted">{{ row.hiddenCount }}</td>
                <td class="p-element">
                  <UBadge :color="row.isClosed ? 'neutral' : 'primary'" variant="soft">
                    {{ $t(`admin.overflow.state.${row.isClosed ? 'closed' : 'open'}`) }}
                  </UBadge>
                </td>
                <td class="p-element text-muted">
                  <time :datetime="row.lastActivityAt">{{ formatDate(row.lastActivityAt) }}</time>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <ul class="flex list-none flex-col gap-block p-0 md:hidden">
          <li
            v-for="row in rows"
            :key="row.id"
            class="flex flex-col gap-element rounded-lg bg-surface p-block"
          >
            <div class="flex items-baseline justify-between gap-element">
              <span class="font-medium">{{ row.title || $t('admin.overflow.unnamed') }}</span>
              <UBadge :color="row.isClosed ? 'neutral' : 'primary'" variant="soft">
                {{ $t(`admin.overflow.state.${row.isClosed ? 'closed' : 'open'}`) }}
              </UBadge>
            </div>
            <p class="text-sm text-muted">{{ row.organizationName }} · {{ row.ownerName }}</p>
            <p class="text-sm">
              {{
                $t('admin.overflow.matchesLine', { count: row.matchCount, hidden: row.hiddenCount })
              }}
            </p>
            <p class="text-xs text-muted">
              {{ $t('admin.overflow.column.activity') }} ·
              <time :datetime="row.lastActivityAt">{{ formatDate(row.lastActivityAt) }}</time>
            </p>
          </li>
        </ul>

        <div class="flex items-center justify-between">
          <span class="text-sm text-muted">
            {{ $t('admin.overflow.pagination', { page, total: totalPages }) }}
          </span>
          <div class="flex gap-element">
            <UButton variant="soft" :disabled="page <= 1" @click="void (page -= 1)">
              {{ $t('admin.overflow.previous') }}
            </UButton>
            <UButton variant="soft" :disabled="page >= totalPages" @click="void (page += 1)">
              {{ $t('admin.overflow.next') }}
            </UButton>
          </div>
        </div>
      </template>
    </div>
  </DashboardBody>
</template>
