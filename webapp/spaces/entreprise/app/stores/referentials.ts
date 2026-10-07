import type { AppConfig, AppReferentials } from '#shared/types/config'

// Les MÉTADONNÉES de l'espace : coût d'un dévoilé, pagination, nomenclatures fermées.
//
// Chargées avant tout le reste et plus jamais retouchées — elles ne dépendent d'aucun écran et ne
// changent qu'au rythme d'un déploiement. C'est ce qui autorise les autres stores à s'y adosser sans
// se demander si elles sont à jour.
//
// Hydraté depuis /v1/config ; le store ne touche pas le réseau.
type ReferentialsState = {
  config: AppConfig | null
}

const EMPTY_REFERENTIALS: AppReferentials = {
  educationLevels: [],
  availabilities: [],
  returnHorizons: [],
  languageProficiencies: [],
}

export const useReferentialsStore = defineStore('referentials', {
  state: (): ReferentialsState => ({
    config: null,
  }),

  getters: {
    getConfig(state: ReferentialsState): AppConfig | null {
      return state.config
    },

    /**
     * Les nomenclatures, ou des listes vides tant que la config n'est pas là.
     *
     * Vides et non `null` : un `<select>` sans option est un écran incomplet, pas un écran cassé —
     * la vue n'a pas à distinguer « pas encore chargé » de « rien à proposer ».
     */
    getReferentials(state: ReferentialsState): AppReferentials {
      return state.config?.referentials ?? EMPTY_REFERENTIALS
    },

    /** Le coût d'UN accès. Dévoiler coordonnées, CV et compte rendu en coûte trois. */
    getRevealCost(state: ReferentialsState): number {
      return state.config?.revealCost ?? 0
    },

    getPerPage(state: ReferentialsState): number {
      return state.config?.perPage ?? 0
    },

    /**
     * Ce qu'une recherche donne à travailler — cent profils, et c'est le serveur qui le dit.
     *
     * Zéro tant que la configuration n'est pas là : l'appelant demande alors la taille de page par
     * défaut du serveur plutôt qu'un nombre inventé ici.
     */
    getSearchLoadLimit(state: ReferentialsState): number {
      return state.config?.searchLoadLimit ?? 0
    },
  },

  actions: {
    setConfig(payload: AppConfig | null): void {
      this.config = payload && typeof payload === 'object' ? payload : null
    },

    reset(): void {
      this.config = null
    },
  },
})
