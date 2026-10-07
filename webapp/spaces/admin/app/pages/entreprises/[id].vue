<script setup lang="ts">
import { memberName } from '#services/admin/organization'

// Fiche entreprise admin — ce qu'un appel client demande : combien de crédits reste-t-il, sur quel
// abonnement, qui sont les membres, et ce qui a été dépensé.
//
// Le solde seul ne se vérifie pas : c'est le journal qui l'explique, chaque ligne portant le solde
// obtenu APRÈS l'opération. On suit la descente et on retombe sur le chiffre annoncé en tête.
//
// Rien ne s'y corrige. Créditer une entreprise à la main touche à l'argent et demande son propre
// cadrage.
definePageMeta({ layout: 'dashboard' })

const route = useRoute()
const { t, current, localePath } = useLocale()
const { organization, subscription, movements, state } = useOrganization(String(route.params.id))

// Le titre de cet écran n'existe pas dans la navigation : c'est le nom de l'entreprise, et il
// n'arrive qu'après le chargement. Le repli garde la barre haute occupée en attendant.
usePageTitle(() => organization.value?.name || '…')

const location = computed(() =>
  organization.value
    ? [organization.value.city, organization.value.country].filter(Boolean).join(' · ')
    : '',
)

const formatMoment = (value: string): string =>
  new Date(value).toLocaleString(current.value, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })

const formatDay = (value: string): string =>
  new Date(value).toLocaleDateString(current.value, {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })

const memberLabel = (member: { givenName: string; familyName: string }): string =>
  memberName(member, t('admin.organization.unknownMember'))
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <NuxtLink
          :to="localePath('/entreprises')"
          class="inline-flex w-fit items-center gap-1 text-sm text-muted transition-colors hover:text-accent"
        >
          <Icon name="lucide:arrow-left" class="size-4" aria-hidden="true" />
          {{ $t('page.admin.entreprises') }}
        </NuxtLink>
        <h1 class="sr-only">{{ organization?.name || '…' }}</h1>
        <p v-if="location" class="text-muted">{{ location }}</p>
      </div>
    </template>

    <div class="flex flex-col gap-block">
      <p v-if="'notFound' === state" class="text-muted" role="alert">
        {{ $t('admin.organization.notFound') }}
      </p>
      <p v-else-if="'error' === state" class="text-alert" role="alert">
        {{ $t('admin.organization.error') }}
      </p>
      <p v-else-if="'loading' === state" class="text-muted">
        {{ $t('admin.organization.loading') }}
      </p>

      <template v-else-if="organization">
        <!-- Ce qu'on vient chercher en ouvrant la fiche : un chiffre et un plan. -->
        <div class="grid gap-block sm:grid-cols-2">
          <div class="flex flex-col gap-1 rounded-lg bg-surface p-block">
            <span class="text-sm text-muted">{{ $t('admin.organization.balance') }}</span>
            <span class="text-2xl font-medium tabular-nums">{{ organization.creditBalance }}</span>
          </div>
          <div class="flex flex-col gap-1 rounded-lg bg-surface p-block">
            <span class="text-sm text-muted">{{ $t('admin.organization.subscription') }}</span>
            <template v-if="subscription">
              <span class="text-2xl font-medium">{{ subscription.plan }}</span>
              <span class="text-sm text-muted">
                {{ $t(`admin.organization.subscriptionStatus.${subscription.status}`) }}
                <template v-if="subscription.currentPeriodEnd">
                  ·
                  <time :datetime="subscription.currentPeriodEnd">
                    {{
                      $t('admin.organization.until', {
                        date: formatDay(subscription.currentPeriodEnd),
                      })
                    }}
                  </time>
                </template>
              </span>
            </template>
            <!-- Dit explicitement l'absence : un champ muet laisserait croire à un chargement raté. -->
            <span v-else class="text-2xl font-medium text-muted">
              {{ $t('admin.organization.noSubscription') }}
            </span>
          </div>
        </div>

        <section class="flex flex-col gap-element">
          <h2 class="text-sm font-medium text-muted">
            {{ $t('admin.organization.members', { count: organization.members.length }) }}
          </h2>
          <p v-if="0 === organization.members.length" class="text-muted">
            {{ $t('admin.organization.noMember') }}
          </p>
          <ul v-else class="flex list-none flex-col gap-element p-0">
            <li
              v-for="member in organization.members"
              :key="member.id"
              class="flex flex-wrap items-baseline gap-element rounded-lg bg-surface p-element"
            >
              <span class="font-medium">{{ memberLabel(member) }}</span>
              <span v-if="member.contactEmail" class="text-sm text-muted">
                {{ member.contactEmail }}
              </span>
              <!-- Le rôle se dit en français, pas en constante du code. -->
              <UBadge
                :color="'ROLE_COMPANY_OWNER' === member.organizationRole ? 'primary' : 'neutral'"
                variant="soft"
                class="ml-auto"
              >
                {{ $t(`admin.organization.role.${member.organizationRole}`) }}
              </UBadge>
            </li>
          </ul>
        </section>

        <section class="flex flex-col gap-element">
          <h2 class="text-sm font-medium text-muted">{{ $t('admin.organization.ledger') }}</h2>
          <p v-if="0 === movements.length" class="text-muted">
            {{ $t('admin.organization.noMovement') }}
          </p>
          <template v-else>
            <div class="hidden overflow-x-auto md:block">
              <table class="w-full text-sm">
                <caption class="sr-only">
                  {{
                    $t('admin.organization.ledgerCaption')
                  }}
                </caption>
                <thead class="text-left text-muted">
                  <tr>
                    <th scope="col" class="p-element">
                      {{ $t('admin.organization.column.moment') }}
                    </th>
                    <th scope="col" class="p-element">
                      {{ $t('admin.organization.column.nature') }}
                    </th>
                    <th scope="col" class="p-element text-right">
                      {{ $t('admin.organization.column.amount') }}
                    </th>
                    <th scope="col" class="p-element text-right">
                      {{ $t('admin.organization.column.balanceAfter') }}
                    </th>
                    <th scope="col" class="p-element">
                      {{ $t('admin.organization.column.reason') }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="movement in movements"
                    :key="movement.id"
                    class="border-t border-border"
                  >
                    <th scope="row" class="p-element text-left font-normal text-muted">
                      <time :datetime="movement.createdAt">{{
                        formatMoment(movement.createdAt)
                      }}</time>
                    </th>
                    <td class="p-element">
                      {{ $t(`admin.organization.movement.${movement.type}`) }}
                    </td>
                    <td
                      class="p-element text-right tabular-nums font-medium"
                      :class="movement.isCredit ? 'text-accent' : ''"
                    >
                      {{ movement.amount }}
                    </td>
                    <td class="p-element text-right tabular-nums text-muted">
                      {{ movement.balanceAfter }}
                    </td>
                    <td class="p-element text-muted">{{ movement.reason || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <ul class="flex list-none flex-col gap-block p-0 md:hidden">
              <li
                v-for="movement in movements"
                :key="movement.id"
                class="flex flex-col gap-element rounded-lg bg-surface p-block"
              >
                <div class="flex items-baseline justify-between gap-element">
                  <span class="font-medium">
                    {{ $t(`admin.organization.movement.${movement.type}`) }}
                  </span>
                  <span
                    class="tabular-nums font-medium"
                    :class="movement.isCredit ? 'text-accent' : ''"
                  >
                    {{ movement.amount }}
                  </span>
                </div>
                <p class="text-sm text-muted">
                  {{
                    $t('admin.organization.balanceAfterLine', { balance: movement.balanceAfter })
                  }}
                </p>
                <p v-if="movement.reason" class="text-sm">{{ movement.reason }}</p>
                <p class="text-xs text-muted">
                  <time :datetime="movement.createdAt">{{ formatMoment(movement.createdAt) }}</time>
                </p>
              </li>
            </ul>
          </template>
        </section>
      </template>
    </div>
  </DashboardBody>
</template>
