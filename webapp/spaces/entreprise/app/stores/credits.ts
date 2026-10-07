import { defineStore } from 'pinia'

/**
 * Le solde de crédits de l'organisation.
 *
 * Un store et non un composable : le solde est LU par la barre haute et ÉCRIT par chaque
 * dévoilement, depuis deux écrans. Porté par un composable, chaque appelant aurait le sien, et le
 * chiffre affiché aurait cessé de suivre la dépense au premier écran de plus.
 *
 * `null` tant qu'il n'a pas été lu : c'est différent de zéro. Zéro interdit le geste suivant,
 * « pas encore lu » ne dit rien — et l'écran ne doit pas refuser un dévoilement sur une ignorance.
 */
type CreditsState = {
  balance: number | null
}

export const useCreditsStore = defineStore('credits', {
  state: (): CreditsState => ({
    balance: null,
  }),

  getters: {
    getBalance(state: CreditsState): number | null {
      return state.balance
    },
  },

  actions: {
    setBalance(balance: number | null): void {
      this.balance = 'number' === typeof balance ? balance : null
    },

    /**
     * Retranche ce qu'une dépense vient de coûter.
     *
     * Le serveur fait autorité et le solde sera relu ; en attendant, le chiffre affiché doit bouger
     * au moment du clic. Un solde qui met un aller-retour à baisser laisse croire que rien n'a été
     * débité, et invite à recliquer.
     */
    spend(cost: number): void {
      if (null !== this.balance) {
        this.balance = Math.max(0, this.balance - cost)
      }
    },
  },
})
