<script setup lang="ts">
// Référentiels qualifiés : consultation ET relecture humaine.
//
// C'est le seul écran admin qui débloque un gain fonctionnel : tant que rien n'est validé, la
// recherche ne peut qu'ÉLARGIR, jamais affirmer. Valider lève ce plafond.
//
// Deux régimes assumés : les métiers se relisent en entier — ils sont ~150 et
// portent les filtres — les compétences se priorisent par fréquence, d'où la couverture cumulée.
definePageMeta({ layout: 'dashboard' })

// La barre haute annonce l'écran ; le titre reste dans le document, masqué à l'œil, pour qu'un
// lecteur d'écran le trouve dans le contenu et non dans une bande extérieure.
const { t } = useLocale()
usePageTitle(() => t('page.admin.referentiels'))

type ReferentialType = 'METIER' | 'COMPETENCE'

const type = ref<ReferentialType>('METIER')
const { referential, pending, error, page, status, review, reviewLabels } = useReferentials(type)

// Sélection remise à zéro dès que le périmètre bouge : valider une sélection invisible serait
// exactement le geste irréversible qu'on veut éviter.
const selected = ref<string[]>([])
watch([type, status, page], () => {
  selected.value = []
})

const toggle = (code: string) => {
  const at = selected.value.indexOf(code)
  if (at === -1) selected.value.push(code)
  else selected.value.splice(at, 1)
}

const decide = async (decision: 'VALIDE' | 'DEPRECIE') => {
  await review(selected.value, decision)
  selected.value = []
}

// Déprécier « mot à mot » : exclure un synonyme parasite de l'élargissement, ou le
// restaurer. Réversible, indépendant du statut du concept.
const toggleLabel = async (label: { uuid: string; status: string }) => {
  await reviewLabels([label.uuid], label.status === 'DEPRECIE' ? 'CANDIDAT' : 'DEPRECIE')
}

const percent = (ratio: number): string => `${Math.round(ratio * 100)} %`
const totalPages = computed(() =>
  Math.max(1, Math.ceil((referential.value?.total ?? 0) / (referential.value?.perPage ?? 25))),
)
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <h1 class="sr-only">{{ $t('page.admin.referentiels') }}</h1>
        <p class="text-muted">{{ $t('referentials.subtitle') }}</p>
      </div>
    </template>

    <!-- B · répartition des statuts : ce qui reste à relire, d'un coup d'œil -->
    <template v-if="referential" #summary>
      <div class="grid grid-cols-3 gap-block">
        <div
          v-for="(count, key) in referential.countsByStatus"
          :key="key"
          class="flex flex-col gap-element rounded-lg bg-surface p-block"
        >
          <span class="text-sm text-muted">{{ $t(`referentials.status.${key}`) }}</span>
          <span class="text-h2 font-semibold tabular-nums">{{ count }}</span>
        </div>
      </div>
    </template>

    <div class="flex flex-col gap-block">
      <!-- Périmètre : type de référentiel + filtre de statut -->
      <div class="flex flex-wrap items-center gap-element">
        <UButton
          v-for="option in ['METIER', 'COMPETENCE'] as ReferentialType[]"
          :key="option"
          :variant="type === option ? 'solid' : 'soft'"
          @click="void (type = option)"
        >
          {{ $t(`referentials.type.${option}`) }}
        </UButton>

        <select v-model="status" class="rounded-lg bg-surface px-element py-2 text-sm">
          <option value="">{{ $t('referentials.filter.all') }}</option>
          <option value="CANDIDAT">{{ $t('referentials.status.CANDIDAT') }}</option>
          <option value="VALIDE">{{ $t('referentials.status.VALIDE') }}</option>
          <option value="DEPRECIE">{{ $t('referentials.status.DEPRECIE') }}</option>
        </select>
      </div>

      <p v-if="error" class="text-danger">{{ $t('error.unexpected.message') }}</p>
      <p v-else-if="pending" class="text-muted">{{ $t('referentials.loading') }}</p>

      <template v-else-if="referential">
        <!-- Barre d'action : n'apparaît qu'avec une sélection, jamais de « tout valider » aveugle -->
        <div
          v-if="selected.length"
          class="flex items-center gap-element rounded-lg bg-surface p-block"
        >
          <span class="text-sm">{{ $t('referentials.selected', { count: selected.length }) }}</span>
          <UButton variant="solid" @click="decide('VALIDE')">
            {{ $t('referentials.action.validate') }}
          </UButton>
          <UButton variant="soft" @click="decide('DEPRECIE')">
            {{ $t('referentials.action.deprecate') }}
          </UButton>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="text-left text-muted">
              <tr>
                <th class="p-element" />
                <th class="p-element">{{ $t('referentials.column.concept') }}</th>
                <th class="p-element text-right">{{ $t('referentials.column.frequency') }}</th>
                <th class="p-element text-right">{{ $t('referentials.column.coverage') }}</th>
                <th class="p-element">{{ $t('referentials.column.status') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in referential.items"
                :key="item.code ?? item.label"
                class="border-t border-border"
              >
                <td class="p-element">
                  <input
                    type="checkbox"
                    :checked="selected.includes(item.code ?? '')"
                    :disabled="!item.code"
                    @change="toggle(item.code ?? '')"
                  />
                </td>
                <td class="p-element">
                  <div class="font-medium">{{ item.label }}</div>
                  <!-- Les libellés SONT la matière du jugement : valider un concept sans les voir
                       ne valide presque rien. Chacun est cliquable — déprécier « mot à mot » exclut
                       ce synonyme de l'élargissement (barré), re-clic restaure. -->
                  <div class="mt-1 flex flex-wrap gap-1">
                    <button
                      v-for="l in item.labels"
                      :key="l.uuid"
                      type="button"
                      class="rounded px-1.5 py-0.5 text-xs transition"
                      :class="
                        l.status === 'DEPRECIE'
                          ? 'text-muted line-through opacity-60 hover:opacity-100'
                          : 'bg-surface text-muted hover:text-text'
                      "
                      :title="
                        l.status === 'DEPRECIE'
                          ? $t('referentials.label.restore')
                          : $t('referentials.label.exclude')
                      "
                      @click="toggleLabel(l)"
                    >
                      {{ l.label }}
                    </button>
                  </div>
                </td>
                <td class="p-element text-right tabular-nums">{{ item.frequency }}</td>
                <td class="p-element text-right tabular-nums text-muted">
                  {{ percent(item.cumulativeCoverage) }}
                </td>
                <td class="p-element">
                  <UBadge color="primary" variant="soft">{{
                    $t(`referentials.status.${item.status}`)
                  }}</UBadge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between">
          <span class="text-sm text-muted">
            {{ $t('referentials.pagination', { page: referential.page, total: totalPages }) }}
          </span>
          <div class="flex gap-element">
            <UButton variant="soft" :disabled="page <= 1" @click="void (page -= 1)">
              {{ $t('referentials.previous') }}
            </UButton>
            <UButton variant="soft" :disabled="page >= totalPages" @click="void (page += 1)">
              {{ $t('referentials.next') }}
            </UButton>
          </div>
        </div>
      </template>
    </div>
  </DashboardBody>
</template>
