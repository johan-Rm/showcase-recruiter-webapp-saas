<script setup lang="ts">
import type { SelectOption } from '#shared/types/select'

// Libellé + champ sans cadre. Le libellé NOMME, il ne concurrence pas : plus petit et plus effacé
// que la saisie, sinon l'œil hésite entre les deux alors qu'un seul est à lire.
interface Props {
  id: string
  label: string
  placeholder?: string
  /** Aide permanente, à réserver à ce que le libellé ne peut pas dire — un format attendu, une
   *  provenance de l'information. Un exemple de saisie va dans le `placeholder`. */
  hint?: string
  /** Fournies → le champ devient un autocomplete (liste fermée) au lieu d'une saisie libre. */
  options?: readonly SelectOption[]
  /** Message du dropdown quand la saisie ne correspond à aucune option. */
  emptyLabel?: string
  /** Cap natif de longueur (miroir du `maxLength` OpenAPI), transmis à la saisie libre. */
  maxlength?: number
}

const { id, label, placeholder = '', hint, options, emptyLabel, maxlength } = defineProps<Props>()

// L'aide se rattache au contrôle : sans `aria-describedby`, un lecteur d'écran annonce le champ sans
// jamais dire comment le remplir.
const hintId = computed(() => `${id}-hint`)

const value = defineModel<string>({ required: true })
</script>

<template>
  <div class="registration-field flex min-w-0 flex-1 flex-col gap-2.5">
    <label class="registration-field__label" :for="id">{{ label }}</label>

    <UiInlineSelect
      v-if="options"
      :id="id"
      v-model="value"
      :options="options"
      :placeholder="placeholder"
      :empty-label="emptyLabel"
      :described-by="hint ? hintId : undefined"
    />
    <UiInlineInput
      v-else
      :id="id"
      v-model="value"
      :placeholder="placeholder"
      :maxlength="maxlength"
      :described-by="hint ? hintId : undefined"
    />

    <p v-if="hint" :id="hintId" class="registration-field__hint">{{ hint }}</p>
  </div>
</template>

<style scoped>
/* L'air entre le libellé et la saisie fait le gros du travail de leur distinction — plus que la
   taille ou l'opacité. Collés, ils se lisent comme un bloc ; espacés, le libellé se retire. */
.registration-field__hint {
  font: 400 11px / 1.4 var(--font-ui);
  color: var(--muted);
  opacity: 0.62;
}

.registration-field__label {
  font: 600 10px / 1.4 var(--font-ui);
  letter-spacing: 0.07em;
  text-transform: uppercase;
  color: var(--muted);
  opacity: 0.62;
}
</style>
