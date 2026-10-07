import { mergeProfile } from '#services/search/profile'

import type { CandidateCard } from '#shared/types/candidate-card'
import type { CandidateProfile } from '#shared/types/candidate-profile'

/**
 * Le tiroir « profil complet » : son ouverture, le profil qu'il montre, et le chargement de la fiche.
 *
 * Séparé de `useCockpit` : celui-ci décrit les ZONES de la coque, le tiroir est une surcouche qui
 * les recouvre toutes. Les mélanger ferait dépendre la coque d'une feature qu'elle ignore.
 *
 * `useState` garde le tout réactif et SSR-safe : le tiroir part toujours fermé côté serveur, il ne
 * s'ouvre que sur une interaction — et son état survit à la navigation entre les deux écrans d'une
 * recherche, qui partagent le même tiroir.
 *
 * **`open()` déclenche un appel réseau**, et c'est assumé : ouvrir le tiroir EST la demande de la
 * fiche. La vignette passée en argument s'affiche immédiatement, la fiche complète la remplace
 * quand elle arrive — un tiroir qui attendrait le réseau donnerait un clic sans effet.
 */
export function useProfilePanel() {
  const isOpen = useState('profile-panel:open', () => false)
  const card = useState<CandidateCard | null>('profile-panel:card', () => null)
  const detail = useState<CandidateProfile | null>('profile-panel:detail', () => null)
  const isLoading = useState('profile-panel:loading', () => false)
  const hasFailed = useState('profile-panel:failed', () => false)

  const request = useRequestFetch()

  /**
   * `null` tant qu'aucun profil n'a été demandé — le tiroir n'a alors rien à peindre.
   *
   * La fusion est refaite à chaque changement plutôt que mémorisée à l'arrivée de la fiche : c'est
   * elle qui rend le remplissage progressif visible, la vignette d'abord, la fiche par-dessus.
   */
  const profile = computed(() => (card.value ? mergeProfile(card.value, detail.value) : null))

  const load = async (id: string): Promise<void> => {
    isLoading.value = true
    hasFailed.value = false

    try {
      detail.value = await request<CandidateProfile>(`/api/candidates/${id}`)
    } catch {
      // La vignette reste affichée : une fiche qui ne se charge pas ne doit rien retirer de ce que
      // le recruteur avait déjà sous les yeux.
      hasFailed.value = true
    } finally {
      isLoading.value = false
    }
  }

  const open = async (candidate: CandidateCard): Promise<void> => {
    card.value = candidate
    detail.value = null
    isOpen.value = true

    await load(candidate.id)
  }

  const retry = async (): Promise<void> => {
    if (card.value) await load(card.value.id)
  }

  /**
   * Le profil ne survit pas à la fermeture : rouvrir le tiroir sur le précédent, le temps qu'un
   * autre se charge, montrerait le mauvais candidat sous le bon geste.
   */
  const close = (): void => {
    isOpen.value = false
    card.value = null
    detail.value = null
    hasFailed.value = false
  }

  return { isOpen, profile, isLoading, hasFailed, open, close, retry }
}
