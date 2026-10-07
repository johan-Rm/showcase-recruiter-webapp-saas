<script setup lang="ts">
/**
 * Le journal des mouvements de crédits, lu comme un relevé bancaire.
 *
 * Le SIGNE porte le sens — `+2` pour des crédits offerts, `−1` pour un dévoilement — et pas la
 * couleur : le guide de marque la réserve aux vues et à l'accent, et cinq types coloriés ne
 * hiérarchiseraient rien.
 */
import type { CreditTransaction } from '#shared/types/reveal'

const { transactions } = defineProps<{ transactions: CreditTransaction[] }>()

/** Le plus récent d'abord : un relevé se lit par le haut. */
const ordered = computed(() =>
  [...transactions].sort((left, right) => right.createdAt.localeCompare(left.createdAt)),
)
</script>

<template>
  <section class="account-credit-journal flex flex-col gap-element">
    <h2 class="text-label text-muted">
      {{ $t('page.entreprise.credits.journal') }}
    </h2>

    <p v-if="0 === ordered.length" class="text-small text-muted">
      {{ $t('page.entreprise.credits.empty') }}
    </p>

    <ul v-else class="flex flex-col divide-y divide-line">
      <li
        v-for="transaction in ordered"
        :key="transaction.id"
        class="flex flex-wrap items-baseline gap-element py-element"
      >
        <span class="min-w-0 flex-1">
          {{ $t(`page.entreprise.credits.type.${transaction.type}`) }}
          <span v-if="transaction.reason" class="text-small text-muted">
            · {{ transaction.reason }}
          </span>
        </span>

        <span class="text-small text-muted tabular-nums">
          {{ $d(new Date(transaction.createdAt)) }}
        </span>

        <span class="w-16 text-end font-semibold tabular-nums">
          {{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }}
        </span>

        <span class="w-24 text-end text-small text-muted tabular-nums">
          {{ $t('page.entreprise.credits.after', { count: transaction.balanceAfter }) }}
        </span>
      </li>
    </ul>
  </section>
</template>
