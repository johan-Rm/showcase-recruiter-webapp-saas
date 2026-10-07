import type { OrganizationMemberCollection } from '#shared/types/organization'

/** L'équipe de l'organisation connectée : relais. L'organisation vient de la session. */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)

  return await apiFetchForUser<OrganizationMemberCollection>(
    event,
    `/v1/organizations/${organizationId}/members`,
  )
})
