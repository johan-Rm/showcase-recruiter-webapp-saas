import { hasAccess, payableCount } from '#services/search/reveal'

import { useCreditsStore } from '../stores/credits'
import { useReferentialsStore } from '../stores/referentials'

import type { CandidateCard } from '#shared/types/candidate-card'
import type { CreditWallet, RevealType } from '#shared/types/reveal'

/**
 * Dévoiler un profil, et ce que ça coûte.
 *
 * Le seul geste PAYANT du produit. Il touche trois choses à la fois — le solde, la carte du profil,
 * et ce que l'écran a le droit de proposer ensuite — d'où un composable plutôt qu'un appel posé dans
 * une vue : les trois doivent bouger ensemble ou pas du tout.
 *
 * Le dévoilement n'est PAS optimiste, contrairement au favori. Il engage de l'argent : afficher des
 * coordonnées avant que le serveur ait confirmé le débit, c'est montrer ce qu'on n'a peut-être pas
 * acheté. On attend la réponse, et c'est l'écran qui relit ensuite son jeu.
 */
export const useReveal = () => {
  const credits = useCreditsStore()
  const referentials = useReferentialsStore()
  const request = useRequestFetch()

  /** Le coût d'UN accès sur UN profil. Trois accès sur un profil en coûtent trois. */
  const cost = computed(() => referentials.getRevealCost)

  const balance = computed(() => credits.getBalance)

  const loadBalance = async (): Promise<void> => {
    try {
      const wallet = await request<CreditWallet>('/api/credits/wallet')
      credits.setBalance(wallet.balance)
    } catch {
      // Un solde illisible ne doit pas empêcher de parcourir : il reste `null`, et l'écran s'abstient
      // alors d'affirmer qu'un geste est impossible.
      credits.setBalance(null)
    }
  }

  /**
   * Ce qu'un lot coûterait VRAIMENT — le compte de ce qui reste à payer, au prix courant.
   *
   * Le compte lui-même vit dans la couche pure : c'est lui que les écrans affichent, et il se teste
   * sans store. Ne reste ici que la multiplication, qui a besoin du référentiel.
   */
  const costOf = (profiles: readonly CandidateCard[], type: RevealType): number =>
    payableCount(profiles, type) * cost.value

  /**
   * Le solde permet-il cette dépense ?
   *
   * `true` quand le solde est INCONNU : on ne refuse pas un geste sur une ignorance. Le serveur
   * tranchera, et son refus sera une information, pas une supposition.
   */
  const affords = (amount: number): boolean => null === balance.value || balance.value >= amount

  /**
   * Dévoile, et remet le solde à jour.
   *
   * Ne relit PAS les profils : ce composable ne connaît aucune recherche, et la carte à rafraîchir
   * appartient à l'écran. C'est donc l'appelant qui relit son jeu après coup — lui seul sait lequel.
   *
   * La relecture n'est pas une précaution : c'est le serveur qui décide ce qui est visible, et la
   * carte ne peut pas deviner un e-mail qu'elle n'avait pas.
   */
  const reveal = async (candidateIds: string[], type: RevealType): Promise<void> => {
    if (0 === candidateIds.length) {
      return
    }

    await request('/api/reveals', { method: 'POST', body: { candidateIds, type } })

    // Le solde bouge tout de suite, puis on lit celui du serveur : entre les deux, l'écran ne ment
    // que sur les profils déjà dévoilés du lot, qui n'ont rien coûté.
    credits.spend(candidateIds.length * cost.value)
    await loadBalance()
  }

  return { cost, balance, loadBalance, costOf, hasAccess, affords, reveal }
}
