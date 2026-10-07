<script setup lang="ts">
interface Props {
  /** Doit correspondre à l'`id` du contrôle placé dans le slot : c'est ce qui lie le label. */
  id: string
  label: string
  /** Message de l'API (violation 422), déjà traduit côté serveur. */
  error?: string
  wide?: boolean
  /**
   * Marqueur visuel seul : c'est le `required` du contrôle qui porte la contrainte et l'annonce
   * aux lecteurs d'écran. Le doubler ici en `aria-required` ferait annoncer l'obligation deux fois.
   */
  required?: boolean
  /** Aide de saisie permanente, à réserver à ce que le label ne peut pas dire (jargon, provenance
   *  de l'information). Un exemple de format va dans le `placeholder` du contrôle, pas ici. */
  hint?: string
}

const { id, label, error, wide = false, required = false, hint } = defineProps<Props>()

const errorId = computed(() => `${id}-error`)
const hintId = computed(() => `${id}-hint`)

// Aide et erreur se rattachent au contrôle : sans `aria-describedby`, un lecteur d'écran annonce le
// champ sans jamais dire ni comment le remplir, ni pourquoi il est refusé. Le contrôle vivant dans
// le slot, c'est à lui de poser l'attribut — d'où l'exposition par slot prop.
const describedBy = computed(() => {
  const ids = [hint ? hintId.value : null, error ? errorId.value : null].filter(Boolean)

  return ids.length > 0 ? ids.join(' ') : undefined
})
</script>

<template>
  <div class="ui-field flex flex-col gap-1" :class="{ 'sm:col-span-2': wide }">
    <label :for="id">
      {{ label }}
      <span v-if="required" class="text-alert" aria-hidden="true">*</span>
    </label>

    <p v-if="hint" :id="hintId" class="text-sm text-muted">{{ hint }}</p>

    <slot
      :error-id="error ? errorId : undefined"
      :invalid="Boolean(error)"
      :described-by="describedBy"
    />

    <p v-if="error" :id="errorId" class="text-sm text-alert" role="alert">{{ error }}</p>
  </div>
</template>
