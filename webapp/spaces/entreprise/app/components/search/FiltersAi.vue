<script setup lang="ts">
/**
 * Carte de recherche ASSISTÉE — jumelle de <SearchFiltersPrimary>, autre parti pris :
 * au lieu de quatre critères à remplir, une phrase en langage naturel que le serveur traduit
 * en critères (cf. `recherche_assistee_ia.md`).
 *
 * INVARIANT du cadrage, affiché à l'utilisateur : seule la phrase du recruteur part au modèle,
 * jamais les profils. Ce n'est pas une mention rassurante de plus — c'est ce qui rend la
 * fonctionnalité acceptable, donc ça se dit à l'écran.
 *
 * Composant PASSIF, comme sa jumelle : il émet `search` avec la phrase et `remove` avec
 * l'identifiant d'un filtre écarté. Il n'appelle rien — l'aller-retour appartient à un composable.
 *
 * Il ne connaît PAS la forme des critères : l'appelant lui passe une liste déjà prête à afficher.
 * Le contrat de recherche n'est pas écrit ; lui apprendre une structure qui n'existe pas encore
 * l'y attacherait pour rien. Afficher et signaler suffisent.
 */
import type { UnderstoodFilter } from '#shared/types/ai-search'

interface Props {
  /** Critères compris par le serveur, prêts à afficher. */
  filters?: readonly UnderstoodFilter[]
  /** Traduction en cours : l'envoi est verrouillé le temps de l'aller-retour. */
  pending?: boolean
  /** Message d'échec, déjà traduit par l'appelant. */
  error?: string
}

const { filters = [], pending = false, error = '' } = defineProps<Props>()

const emit = defineEmits<{ search: [query: string]; remove: [id: string]; launch: [] }>()

const query = ref('')

// Rien à traduire tant que la phrase est vide, et pas deux envois pendant l'aller-retour.
const canSubmit = computed(() => '' !== query.value.trim() && !pending)

const onSubmit = () => {
  if (canSubmit.value) {
    emit('search', query.value.trim())
  }
}

// Dans une zone de texte, Entrée insère une ligne — c'est la combinaison qui envoie. Sans elle,
// le clavier n'aurait aucun chemin vers la recherche, alors que c'est un champ qu'on remplit vite.
const onKeydown = (event: KeyboardEvent) => {
  if ('Enter' === event.key && (event.metaKey || event.ctrlKey)) {
    event.preventDefault()
    onSubmit()
  }
}

// `useId` et non un identifiant écrit en dur : la carte ne peut pas jurer d'être seule sur la page.
const uid = useId()
</script>

<template>
  <UiCard
    as="form"
    class="search-filters-ai flex h-full flex-col gap-block"
    @submit.prevent="onSubmit"
  >
    <h2 class="text-h3">{{ $t('search.ai.title') }}</h2>

    <UiField
      :id="uid"
      v-slot="{ describedBy }"
      :label="$t('search.ai.query.label')"
      :hint="$t('search.ai.query.hint')"
    >
      <!-- Zone de texte et non champ d'une ligne : on décrit un profil, pas un mot-clé. Le style
           vient de la règle `.ui-field :is(textarea)` du socle — aucun habillage à écrire ici. -->
      <textarea
        :id="uid"
        v-model="query"
        rows="3"
        class="w-full resize-y"
        :placeholder="$t('search.ai.query.placeholder')"
        :aria-describedby="describedBy"
        :disabled="pending"
        @keydown="onKeydown"
      />
    </UiField>

    <!-- Ce que le serveur a compris, et que l'on peut écarter un à un. `aria-live` : la liste
         apparaît après un aller-retour, donc sans annonce elle serait muette au lecteur d'écran. -->
    <section v-if="filters.length > 0" class="flex flex-col gap-element" aria-live="polite">
      <h3 class="text-caption font-semibold tracking-wide text-muted uppercase">
        {{ $t('search.ai.understood') }}
      </h3>
      <ul class="flex flex-wrap gap-element">
        <li
          v-for="filter in filters"
          :key="filter.id"
          class="inline-flex items-center gap-1.5 rounded-lg bg-accent-soft px-2.5 py-1 text-small font-semibold text-accent"
        >
          {{ filter.label }}
          <button
            type="button"
            class="grid cursor-pointer place-items-center rounded-lg border-0 bg-transparent p-0.5 text-accent transition-colors hover:bg-accent-soft"
            :aria-label="$t('search.ai.removeFilter', { filter: filter.label })"
            @click="emit('remove', filter.id)"
          >
            <Icon name="lucide:x" class="h-3.5 w-3.5" aria-hidden="true" />
          </button>
        </li>
      </ul>
    </section>

    <p v-if="error" class="text-small text-alert" role="alert">{{ error }}</p>

    <!-- Deux temps, deux boutons : on fait comprendre la phrase, PUIS on lance. Tant que rien n'est
         compris, il n'y a rien à lancer — le second n'existe donc pas encore. -->
    <div class="mt-auto flex justify-end gap-element">
      <UiButton
        type="submit"
        :variant="filters.length > 0 ? 'secondary' : 'primary'"
        :disabled="!canSubmit"
      >
        {{ pending ? $t('search.ai.pending') : $t('search.ai.submit') }}
      </UiButton>
      <UiButton v-if="filters.length > 0" type="button" @click="emit('launch')">
        {{ $t('search.ai.launch') }}
      </UiButton>
    </div>
  </UiCard>
</template>
