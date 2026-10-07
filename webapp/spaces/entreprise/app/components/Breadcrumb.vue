<script setup lang="ts">
// LE FIL D'ARIANE de la coque entreprise : où l'on est, et par où remonter.
//
// À la racine des composants, et non dans un dossier de feature : il ne sert aucun domaine, il est
// le cadre dans lequel les domaines s'affichent — même rang que la barre haute ou la gouttière.
//
// SANS DOMAINE, comme la coque : il ne connaît ni les recherches ni le vivier, il reçoit des
// maillons déjà traduits par la page. La traduction appartient à qui sait de quel écran il parle.
//
// Décalqué du fil d'ariane de l'espace admin (`spaces/admin/app/components/Topbar.vue`) : même
// balise de navigation nommée, même clé d'accessibilité, même chevron. Ce qui change est l'endroit
// — la gouttière, et non la barre du haut.

/** Un maillon du fil. */
export interface BreadcrumbStep {
  label: string
  /**
   * Absente sur le DERNIER maillon : la page courante n'est pas un lien vers elle-même, et un lien
   * qui ne mène nulle part se clique quand même.
   */
  to?: string
}

const { items } = defineProps<{ items: readonly BreadcrumbStep[] }>()

const { localePath } = useLocale()
</script>

<template>
  <!-- Le fil porte son PROPRE nom accessible : deux repères de navigation homonymes dans une même
       page ne se distinguent pas au lecteur d'écran, et celui-ci n'est pas la navigation
       principale. -->
  <nav
    class="breadcrumb flex min-w-0 items-center gap-1.5 text-small"
    :aria-label="$t('nav.breadcrumb')"
  >
    <template v-for="(step, index) in items" :key="step.label">
      <!-- Le chevron marque la descente dans la hiérarchie : en arabe il se retourne, sinon il
           pointe à contresens de la lecture. -->
      <UIcon
        v-if="index > 0"
        name="i-lucide-chevron-right"
        class="h-4 w-4 flex-none text-muted rtl:rotate-180"
        aria-hidden="true"
      />

      <NuxtLink
        v-if="step.to"
        :to="localePath(step.to)"
        class="flex-none text-muted transition-colors hover:text-accent"
      >
        {{ step.label }}
      </NuxtLink>
      <span v-else class="min-w-0 truncate text-text" aria-current="page">{{ step.label }}</span>
    </template>
  </nav>
</template>
