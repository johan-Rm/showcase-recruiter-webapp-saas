import type { OrganizationInvitationCollection } from '#shared/types/organization'

/** Les invitations en attente : relais. */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)

  return await apiFetchForUser<OrganizationInvitationCollection>(
    event,
    `/v1/organizations/${organizationId}/invitations`,
  )
})
