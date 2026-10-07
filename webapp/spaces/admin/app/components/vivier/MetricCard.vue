<script setup lang="ts">
import { VIVIER_METRIC_META, formatCount, gapRouteOf } from '../../utils/vivierMetrics'

// Un chiffre du rapport : son libellé, sa définition au survol, sa valeur, et la population de
// référence quand la métrique en a une. C'est la brique qui porte le FORMAT des nombres — les
// vingt-sept chiffres de l'écran passent tous par ici, il n'y a donc qu'un endroit à corriger.
//
// La carte devient une PORTE quand deux conditions tiennent : la métrique sait expliquer son
// absence, et il manque réellement quelqu'un. Un lien vers une liste vide est une promesse non
// tenue, et une métrique de répartition (la séniorité) n'a pas d'inverse à montrer.
interface Props {
  /** Le libellé émis par le back, qui sert aussi de clé au registre des définitions. */
  label: string
  value: number
  /** Population de référence déjà résolue par le parent ; absente si la métrique n'a pas de ratio. */
  denominator?: number | null
}

const { label, value, denominator = null } = defineProps<Props>()

const { current, localePath } = useLocale()

// Chaîne vide si le libellé n'est pas au registre → pas de bulle, jamais d'erreur.
const hint = computed(() => VIVIER_METRIC_META[label]?.hint ?? '')

const missing = computed(() => (denominator ? denominator - value : 0))
const gapTo = computed(() => {
  const metric = gapRouteOf(label)

  return metric && missing.value > 0 ? localePath(`/vivier/${metric}`) : null
})
</script>

<template>
  <!-- Un `<div>` entre `<dl>` et son couple `<dt>`/`<dd>` est valide, et c'est ce qui permet
       d'encadrer les deux ensemble — même écriture que la page d'accueil admin. -->
  <div
    class="vivier-metric-card group relative flex flex-col gap-element rounded-lg bg-surface p-block"
  >
    <dt class="flex items-start gap-element text-small text-muted">
      <span :class="gapTo ? 'transition-colors group-hover:text-accent' : ''">{{ label }}</span>
      <!-- La bulle porte un `<button>` : elle reste HORS du lien, qu'aucun élément interactif ne
           peut contenir. Au-dessus de lui pour rester cliquable pour elle-même. -->
      <UiInfoHint v-if="hint" :text="hint" class="relative z-10" />
    </dt>
    <dd class="text-h3 font-semibold tabular-nums">
      {{ formatCount(value, current) }}
      <span v-if="denominator" class="text-small font-normal text-muted">
        /{{ formatCount(denominator, current) }}
      </span>

      <!-- Lien étendu à toute la carte : la cible reste la carte entière, sans envelopper la bulle
           d'aide. Il vit dans le `<dd>` parce qu'un `<div>` de `<dl>` n'accepte que `dt` et `dd` ;
           son positionnement se résout sur la carte, qui porte `relative`.
           Son nom se suffit à lui-même — « cliquer ici » ne dit rien hors contexte. -->
      <NuxtLink
        v-if="gapTo"
        :to="gapTo"
        class="absolute inset-0 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        :aria-label="$t('vivier.gap.open', { metric: label, count: missing })"
      />
    </dd>
  </div>
</template>
