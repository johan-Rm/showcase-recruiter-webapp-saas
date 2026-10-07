<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'

import type { AdminUser } from '#shared/types/user'

// Utilisateurs & droits — comptes tous espaces confondus : rôles, statut, dernière connexion
// (`lastLoginAt`). LECTURE SEULE : l'édition des droits demande `ADMIN_OWNER` et ne se fait pas ici.
definePageMeta({ layout: 'dashboard' })

const { users, total, error } = useUsers()

const { t } = useLocale()

// La barre haute annonce l'écran ; le titre reste dans le document, masqué à l'œil, pour qu'un
// lecteur d'écran le trouve dans le contenu et non dans une bande extérieure.
usePageTitle(() => t('page.admin.utilisateurs'))

const day = (iso: string | null): string | null => (iso ? iso.slice(0, 10) : null)
const shortRole = (role: string): string => role.replace('ROLE_', '')

const columns: TableColumn<AdminUser>[] = [
  { id: 'user', header: () => t('users.user') },
  { id: 'roles', header: () => t('users.roles') },
  { id: 'status', header: () => t('users.status') },
  { id: 'lastlogin', header: () => t('users.lastLogin') },
]
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <h1 class="sr-only">{{ $t('page.admin.utilisateurs') }}</h1>
        <p class="text-muted">{{ $t('users.count', { count: total }) }}</p>
      </div>
    </template>

    <div class="flex flex-col gap-block">
      <p v-if="error" class="text-alert" role="alert">{{ $t('error.unexpected.message') }}</p>
      <p v-else-if="users.length === 0" class="text-muted">{{ $t('users.empty') }}</p>

      <UTable v-else :data="users" :columns="columns" class="admin-users-table">
        <template #user-cell="{ row }">
          <div class="font-semibold">
            {{ row.original.givenName }} {{ row.original.familyName }}
          </div>
          <div class="text-muted">{{ row.original.email }}</div>
        </template>
        <template #roles-cell="{ row }">
          <div class="flex flex-wrap gap-1.5">
            <UBadge v-for="role in row.original.roles" :key="role" color="primary" variant="soft">{{
              shortRole(role)
            }}</UBadge>
          </div>
        </template>
        <template #status-cell="{ row }">
          <UBadge color="primary" variant="soft">{{ row.original.status }}</UBadge>
        </template>
        <template #lastlogin-cell="{ row }">
          <span class="whitespace-nowrap">{{
            day(row.original.lastLoginAt) || $t('users.never')
          }}</span>
        </template>
      </UTable>
    </div>
  </DashboardBody>
</template>
