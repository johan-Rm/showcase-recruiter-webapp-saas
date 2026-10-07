<script setup lang="ts">
// Candidats — vivier cross-tenant, en liste. NON masqué : l'API garde `ROLE_ADMIN`.
//
// ÉCRAN PLEIN ÉCRAN, DONC TROIS ZONES : le titre (A) et le taux de remplissage (B) sont FIXES, et
// le tableau (C) est la seule zone qui défile — c'est lui qu'on travaille, il prend tout le reste.
// Le remplissage ne descend JAMAIS dans la zone défilante : il y prendrait la place sans rester
// visible, et le tableau ne montrerait que sept candidats sur un 1920 au lieu de dix.
definePageMeta({ layout: 'dashboard' })

// La barre haute annonce l'écran ; le titre reste dans le document, masqué à l'œil, pour qu'un
// lecteur d'écran le trouve dans le contenu et non dans une bande extérieure.
const { t } = useLocale()
usePageTitle(() => t('page.admin.candidats'))

const { candidates, total, error } = useCandidates()
// Taux de remplissage : agrégat du vivier (mêmes chiffres que /vivier), en bande haute.
const { vivier } = useVivier()
</script>

<template>
  <DashboardBody>
    <!-- A · le titre, sur UNE ligne. Le compteur le suit au lieu de vivre en dessous : le fil
         d'ariane de la barre haute nomme déjà l'écran, et deux lignes de titre coûtaient une demi
         ligne de tableau. La bande prend le palier de gouttière du socle. -->
    <template #header>
      <div class="flex min-h-(--band-gutter) items-center">
        <!-- Le titre part dans la barre haute et se masque ici ; le COMPTEUR reste, lui — il vivait
             dans le titre et l'aurait suivi dans l'ombre. -->
        <h1 class="sr-only">{{ $t('page.admin.candidats') }}</h1>
        <p class="text-body text-muted">
          {{ $t('admin.candidates.count', { count: total }) }}
        </p>
      </div>
    </template>

    <!-- B · le remplissage, FIXE. Le slot est toujours rendu et c'est son CONTENU qui attend les
         données : conditionner le slot ferait disparaître la bande le temps du chargement, et le
         tableau sauterait. -->
    <template #summary>
      <FillRateSection :items="vivier?.fillRate ?? []" />
    </template>

    <!-- C · le tableau, seule zone qui défile -->
    <p v-if="error" class="text-alert" role="alert">{{ $t('error.unexpected.message') }}</p>
    <p v-else-if="candidates.length === 0" class="text-muted">
      {{ $t('admin.candidates.empty') }}
    </p>
    <CandidateTable v-else :candidates="candidates" />
  </DashboardBody>
</template>
