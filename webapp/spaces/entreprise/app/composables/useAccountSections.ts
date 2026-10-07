/** Une rubrique de « Mon compte » : son adresse, et la clé qui la nomme. */
export interface AccountSection {
  to: string
  key: string
  /** Réservée au propriétaire — administrer l'équipe et l'entreprise n'appartient qu'à lui. */
  owner?: boolean
}

/**
 * Les rubriques de « Mon compte », en un seul endroit.
 *
 * Le menu de l'avatar et la sous-navigation de la page les affichent tous les deux. Recopiées, les
 * deux listes auraient divergé à la première rubrique ajoutée — et le filtre de rôle avec elles, ce
 * qui aurait fini par montrer à un membre une entrée qu'il ne peut pas ouvrir.
 *
 * Composable et non service : la liste dépend de la SESSION, donc du réactif.
 */
export const useAccountSections = () => {
  const { user } = useUserSession()

  const isOwner = computed(() => Boolean(user.value?.roles?.includes('ROLE_COMPANY_OWNER')))

  const sections = computed<AccountSection[]>(() =>
    [
      { to: '/credits', key: 'credits' },
      { to: '/l-equipe', key: 'team', owner: true },
      { to: '/l-entreprise', key: 'company', owner: true },
      { to: '/mon-profil', key: 'profile' },
    ].filter((section) => !section.owner || isOwner.value),
  )

  return { sections, isOwner }
}
