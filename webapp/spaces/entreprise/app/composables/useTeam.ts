import type {
  OrganizationInvitation,
  OrganizationInvitationCollection,
  OrganizationMember,
  OrganizationMemberCollection,
} from '#shared/types/organization'

/**
 * L'équipe de l'organisation : ceux qui sont là, et ceux qui sont attendus.
 *
 * DEUX listes et non une. Une invitation n'est pas une personne — elle a une adresse et une échéance,
 * pas un nom ni un poste. Les fondre demanderait une colonne « statut » pour dire lequel est lequel ;
 * deux listes le disent par leur seule existence.
 *
 * Pas de store : ces listes ne servent qu'à cet écran, et rien d'autre ne les lit. Un store leur
 * donnerait une portée globale qu'aucun besoin ne réclame.
 */
export const useTeam = () => {
  const request = useRequestFetch()

  const members = ref<OrganizationMember[]>([])
  const invitations = ref<OrganizationInvitation[]>([])

  const { pending, error, refresh } = useAsyncData('entreprise:team', async () => {
    const [team, pending] = await Promise.all([
      request<OrganizationMemberCollection>('/api/organization/members'),
      request<OrganizationInvitationCollection>('/api/organization/invitations'),
    ])

    members.value = team.items
    invitations.value = pending.items

    return true
  })

  /**
   * Invite, puis relit — l'API date l'invitation et lui donne son échéance, deux choses que le
   * client ne peut pas inventer.
   */
  const invite = async (email: string): Promise<void> => {
    await request('/api/organization/invitations', { method: 'POST', body: { email } })
    await refresh()
  }

  /**
   * Retire un membre. OPTIMISTE : la ligne disparaît au clic, et revient si l'appel échoue.
   *
   * Contrairement au dévoilement, ce geste n'engage rien qu'on ne puisse défaire — réinviter coûte
   * un e-mail.
   */
  const remove = async (member: OrganizationMember): Promise<void> => {
    const before = members.value
    members.value = members.value.filter((item) => item.id !== member.id)

    try {
      await request(`/api/organization/members/${member.id}`, { method: 'DELETE' })
    } catch (failure) {
      members.value = before

      throw failure
    }
  }

  return { members, invitations, invite, remove, pending, error, refresh }
}
