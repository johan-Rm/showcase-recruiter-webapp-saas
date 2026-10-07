<script setup lang="ts">
// Entreprises — file de revue. Seul écran admin avec un back vivant :
// GET /v1/admin/organizations + validate/reject (composable useOrganizationReview).
// Porté ici dans la coque plein écran (gouttière = titre, section haute = filtre, C = la file).
definePageMeta({ layout: 'dashboard' })

// La barre haute annonce l'écran ; le titre reste dans le document, masqué à l'œil, pour qu'un
// lecteur d'écran le trouve dans le contenu et non dans une bande extérieure.
const { t } = useLocale()
usePageTitle(() => t('page.admin.entreprises'))

const { status, organizations, total, pending, error, decide } = useOrganizationReview()
</script>

<template>
  <DashboardBody>
    <template #header>
      <div class="flex flex-col gap-element">
        <h1 class="sr-only">{{ $t('page.admin.entreprises') }}</h1>
        <p class="text-muted">{{ $t('admin.organizations.count', { count: total }) }}</p>
      </div>
    </template>

    <template #summary>
      <ReviewStatusFilter :status="status" @select="(value) => (status = value)" />
    </template>

    <!-- C · la file (défile) -->
    <div class="flex flex-col gap-block">
      <p v-if="error" class="text-alert" role="alert">{{ $t(error) }}</p>
      <p v-else-if="organizations.length === 0" class="text-muted">
        {{ $t('admin.organizations.empty') }}
      </p>
      <template v-else>
        <!-- Table ≥ md ; cartes empilées < md (règle « tableaux → cartes sur mobile »). -->
        <ReviewOrganizationTable
          class="hidden md:block"
          :organizations="organizations"
          :pending="pending"
          @decide="(id, decision) => decide(id, decision)"
        />
        <ul class="flex list-none flex-col gap-block p-0 md:hidden">
          <li v-for="organization in organizations" :key="organization.id">
            <ReviewOrganizationCard
              :organization="organization"
              :pending="pending"
              @decide="(decision) => decide(organization.id, decision)"
            />
          </li>
        </ul>
      </template>
    </div>
  </DashboardBody>
</template>
