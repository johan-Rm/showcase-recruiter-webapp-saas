import type { CandidateCard } from '#shared/types/candidate-card'

/**
 * La feuille « ce profil face à vos critères » : son ouverture, et le profil qu'elle juge.
 *
 * ELLE N'EXISTE QUE SOUS 1280 px. Au-dessus, la liste des critères vit dans sa colonne, en
 * permanence sous les yeux — il n'y a rien à ouvrir. En dessous, cette colonne retombe dans la
 * fiche et y pèse plus de trois cents pixels : la ranger derrière un geste est ce qui rend le plein
 * écran tenable sans rien retirer au recruteur.
 *
 * ÉTAT PARTAGÉ PLUTÔT QU'ÉVÉNEMENT, comme le tiroir du profil complet : le geste part d'une fiche
 * enfouie dans la pile, et le faire remonter jusqu'à la page traverserait deux composants qui n'ont
 * rien à en connaître.
 *
 * `useState` garde le tout réactif et SSR-safe : la feuille part toujours fermée côté serveur.
 *
 * ELLE GARDE LE PROFIL qu'on lui a donné, plutôt que de suivre celui qu'on regarde : la pile est
 * inerte tant que la feuille est ouverte, mais un profil qui changerait sous une liste ouverte
 * mentirait, et rien dans le code ne l'empêcherait.
 */
export function useCriteriaSheet() {
  const isOpen = useState('criteria-sheet:open', () => false)
  const candidate = useState<CandidateCard | null>('criteria-sheet:candidate', () => null)

  const open = (profile: CandidateCard): void => {
    candidate.value = profile
    isOpen.value = true
  }

  /**
   * Le profil ne survit pas à la fermeture : rouvrir la feuille sur le précédent, le temps qu'un
   * autre s'affiche, montrerait les critères du mauvais candidat.
   */
  const close = (): void => {
    isOpen.value = false
    candidate.value = null
  }

  return { isOpen, candidate, open, close }
}
