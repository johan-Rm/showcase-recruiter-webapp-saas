import type { Organization } from '#shared/types/organization'
import type { components } from '#shared/types/api'

type OrganizationInput = components['schemas']['Organization.OrganizationInput']

/**
 * Création de l'entreprise : relais authentifié de POST /v1/organizations.
 * Le déclarant est identifié par la session ouverte au magic link.
 *
 * Le corps n'est pas revalidé ici : la vérité métier est celle de l'API (Symfony Validator).
 * Le BFF relaie ses 422 tels quels — le formulaire les affiche champ par champ.
 */
export default defineEventHandler(async (event) => {
  const body = await readBody<OrganizationInput>(event)

  return await apiFetchForUser<Organization>(event, '/v1/organizations', {
    method: 'POST',
    body,
  })
})
