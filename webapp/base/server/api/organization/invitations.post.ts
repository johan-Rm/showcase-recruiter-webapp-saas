import type { OrganizationInvitation } from '#shared/types/organization'

/**
 * Inviter quelqu'un dans l'équipe : relais.
 *
 * Le RÔLE n'est pas transmis par le client : l'API n'accepte que `ROLE_COMPANY_MEMBER`, et laisser
 * passer une valeur venue du navigateur donnerait l'illusion d'un choix qui n'existe pas.
 */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)
  const body = await readBody<{ email?: string }>(event)

  return await apiFetchForUser<OrganizationInvitation>(
    event,
    `/v1/organizations/${organizationId}/invitations`,
    { method: 'POST', body: { email: body?.email ?? '', role: 'ROLE_COMPANY_MEMBER' } },
  )
})
