/**
 * Changer son mot de passe (PUT /v1/me/password) : relais.
 *
 * L'ANCIEN est exigé par l'API, et c'est ce qui distingue un changement volontaire d'une prise de
 * contrôle par une session volée. Le relais ne le valide pas — il le transmet.
 */
export default defineEventHandler(async (event) => {
  const body = await readBody<{ currentPassword?: string; newPassword?: string }>(event)

  return await apiFetchForUser(event, '/v1/me/password', {
    method: 'PUT',
    body: {
      currentPassword: body?.currentPassword ?? '',
      newPassword: body?.newPassword ?? '',
    },
  })
})
