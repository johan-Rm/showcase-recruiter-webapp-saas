import { fieldViolations } from '#services/validation/violations'

import type { components } from '#shared/types/api'
import type { Organization } from '#shared/types/organization'

type OrganizationInput = components['schemas']['Organization.OrganizationInput']

/**
 * Inscription entreprise : référentiel du formulaire + création de l'entreprise.
 *
 * Le magic link qui ouvre la session est du ressort du socle (`useMagicLink`). Les règles de
 * validation restent celles de l'API : ses violations 422 sont exposées champ par champ, jamais
 * réécrites ici.
 */
export const useRegistrationCompany = () => {
  const pending = ref(false)
  const error = ref<string | null>(null)
  const fieldErrors = ref<Record<string, string>>({})

  const createOrganization = async (input: OrganizationInput): Promise<Organization | null> => {
    pending.value = true
    error.value = null
    fieldErrors.value = {}

    try {
      return await $fetch<Organization>('/api/organizations', { method: 'POST', body: input })
    } catch (caught) {
      fieldErrors.value = fieldViolations(caught)
      if (Object.keys(fieldErrors.value).length === 0) {
        error.value = 'inscription.error.unexpected'
      }

      return null
    } finally {
      pending.value = false
    }
  }

  return { pending, error, fieldErrors, createOrganization }
}
