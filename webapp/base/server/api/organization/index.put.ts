import type { Organization } from '#shared/types/organization'

/**
 * Écrire la fiche de l'organisation connectée : relais.
 *
 * On ne transmet QUE les champs de l'entreprise. L'identité du déclarant et la provenance
 * (« comment nous avez-vous connus ») n'ont de sens qu'à l'inscription : leur validation est portée
 * par le groupe `create`, et le processeur les ignore de toute façon sur une mise à jour.
 */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)
  const body = await readBody<Record<string, unknown>>(event)

  const fields = [
    'name',
    'legalName',
    'taxId',
    'country',
    'city',
    'billingAddress',
    'website',
    'sector',
    'description',
  ] as const

  return await apiFetchForUser<Organization>(event, `/v1/organizations/${organizationId}`, {
    method: 'PUT',
    body: Object.fromEntries(fields.map((field) => [field, body?.[field] ?? null])),
  })
})
