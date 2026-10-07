/**
 * Retirer un membre de l'organisation : relais.
 *
 * L'identifiant est celui du RECRUTEUR, pas de la personne : on retire une casquette, on ne supprime
 * pas quelqu'un.
 */
export default defineEventHandler(async (event) => {
  const organizationId = await requireOrganizationId(event)
  const recruiterId = getRouterParam(event, 'recruiterId')

  return await apiFetchForUser(
    event,
    `/v1/organizations/${organizationId}/members/${recruiterId}`,
    { method: 'DELETE' },
  )
})
