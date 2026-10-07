<script setup lang="ts">
// Le fil des visites de profil — qui a regardé qui, en lecture seule.
//
// Visiter n'est pas dévoiler : un recruteur ouvre des fiches masquées sans dépenser de crédit, et
// c'est cet écart que l'écran rend lisible. Un candidat regardé dix fois et jamais débloqué raconte
// une fiche qui accroche sans convaincre ; un candidat jamais regardé raconte une fiche qui ne
// remonte pas.
//
// Rien ne s'y corrige, et rien ne s'y filtre : c'est la matière brute des indicateurs à venir.
definePageMeta({ layout: 'dashboard' })

const { t, current } = useLocale()
usePageTitle(() => t('page.admin.activite'))

const { rows, total, page, totalPages, state } = useActivity()

// Date ET heure : sur un fil, deux consultations du même jour ne se distinguent que par l'heure.
// L'attribut `datetime` porte la valeur machine, le texte porte la version que le lecteur d'écran
// prononcera. La langue vient de l'APPLICATION et non du navigateur : sans elle, un poste configuré
// en anglais affiche « August 18, 2026 » au milieu d'un écran en français.
const formatMoment = (value: string): string =>
  new Date(value).toLocaleString(current.value, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <h1 class="sr-only">{{ $t('page.admin.activite') }}</h1>
        <!-- Le compte ne s'affiche QUE si on le connaît : annoncer « 0 consultation » sous un
             message d'échec affirmerait que personne ne regarde les profils, alors qu'on n'a
             justement rien pu lire. -->
        <p v-if="'list' === state || 'empty' === state" class="text-muted">
          {{ $t('admin.activity.count', { count: total }) }}
        </p>
      </div>
    </template>

    <div class="flex flex-col gap-block">
      <p v-if="'error' === state" class="text-alert" role="alert">
        {{ $t('admin.activity.error') }}
      </p>
      <p v-else-if="'loading' === state" class="text-muted">{{ $t('admin.activity.loading') }}</p>
      <p v-else-if="'empty' === state" class="text-muted">{{ $t('admin.activity.empty') }}</p>

      <template v-else>
        <!-- Table ≥ md ; cartes empilées < md — cinq colonnes ne tiennent pas sur un téléphone. -->
        <div class="hidden overflow-x-auto md:block">
          <table class="w-full text-sm">
            <caption class="sr-only">
              {{
                $t('admin.activity.caption')
              }}
            </caption>
            <thead class="text-left text-muted">
              <tr>
                <th scope="col" class="p-element">{{ $t('admin.activity.column.moment') }}</th>
                <th scope="col" class="p-element">{{ $t('admin.activity.column.recruiter') }}</th>
                <th scope="col" class="p-element">
                  {{ $t('admin.activity.column.organization') }}
                </th>
                <th scope="col" class="p-element">{{ $t('admin.activity.column.candidate') }}</th>
                <th scope="col" class="p-element">{{ $t('admin.activity.column.revealed') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.id" class="border-t border-border">
                <th scope="row" class="p-element text-left font-normal text-muted">
                  <time :datetime="row.viewedAt">{{ formatMoment(row.viewedAt) }}</time>
                </th>
                <td class="p-element">
                  {{ row.recruiterName }}
                  <span v-if="row.recruiterEmail" class="block text-xs text-muted">
                    {{ row.recruiterEmail }}
                  </span>
                </td>
                <td class="p-element">{{ row.organizationName }}</td>
                <td class="p-element font-medium">{{ row.candidateName }}</td>
                <!-- L'écart entre regarder et débloquer : c'est lui qui donne sa valeur au fil. -->
                <td class="p-element">
                  <UBadge :color="row.revealed ? 'primary' : 'neutral'" variant="soft">
                    {{ $t(`admin.activity.revealed.${row.revealed ? 'yes' : 'no'}`) }}
                  </UBadge>
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
              <span class="font-medium">{{ row.candidateName }}</span>
              <UBadge :color="row.revealed ? 'primary' : 'neutral'" variant="soft">
                {{ $t(`admin.activity.revealed.${row.revealed ? 'yes' : 'no'}`) }}
              </UBadge>
            </div>
            <p class="text-sm text-muted">{{ row.recruiterName }} · {{ row.organizationName }}</p>
            <p class="text-xs text-muted">
              <time :datetime="row.viewedAt">{{ formatMoment(row.viewedAt) }}</time>
            </p>
          </li>
        </ul>

        <div class="flex items-center justify-between">
          <span class="text-sm text-muted">
            {{ $t('admin.activity.pagination', { page, total: totalPages }) }}
          </span>
          <div class="flex gap-element">
            <UButton variant="soft" :disabled="page <= 1" @click="void (page -= 1)">
              {{ $t('admin.activity.previous') }}
            </UButton>
            <UButton variant="soft" :disabled="page >= totalPages" @click="void (page += 1)">
              {{ $t('admin.activity.next') }}
            </UButton>
          </div>
        </div>
      </template>
    </div>
  </DashboardBody>
</template>
