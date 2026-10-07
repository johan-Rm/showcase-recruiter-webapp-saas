import type { CandidateCard } from '#shared/types/candidate-card'

// Le POOL de candidats de l'espace : chaque profil rencontré, une seule fois, indexé par identifiant.
//
// Un pool et non le jeu d'une recherche. Deux raisons :
//
//  · un profil appartient souvent à PLUSIEURS recherches — le garder par recherche en tiendrait
//    autant de copies, et dévoiler ses coordonnées n'en rafraîchirait qu'une ;
//  · le jeu d'une recherche est un ORDRE et une APPARTENANCE, pas des cartes : c'est `resultIds`,
//    et il vit dans le store des recherches.
//
// Ce store ne sait donc rien des recherches — ni identifiant courant, ni favori, ni nouveauté. Ces
// trois-là sont des relations (recherche × candidat) : posées ici, elles se mélangeraient dès que
// deux recherches partagent un profil.
//
// Les cartes y entrent NORMALISÉES (cf. services/search/normalize) : `isFavorite` et `isNew` à
// `null`, leur valeur hors recherche. C'est le composable qui les rattache au dernier moment.
type CandidatesState = {
  byId: Record<string, CandidateCard>
}

export const useCandidatesStore = defineStore('candidates', {
  state: (): CandidatesState => ({
    byId: {},
  }),

  getters: {
    getPool(state: CandidatesState): Record<string, CandidateCard> {
      return state.byId
    },

    getCandidate(state: CandidatesState): (id: string) => CandidateCard | null {
      return (id) => state.byId[id] ?? null
    },

    /** Combien de profils sont chargés — sert à savoir ce qu'il reste à aller chercher. */
    getLoadedCount(state: CandidatesState): number {
      return Object.keys(state.byId).length
    },

    /** Les identifiants d'une liste qui ne sont PAS encore dans le pool. */
    getMissingIds(state: CandidatesState): (ids: readonly string[]) => string[] {
      return (ids) => ids.filter((id) => undefined === state.byId[id])
    },
  },

  actions: {
    /**
     * Range des cartes, en écrasant celles du même identifiant.
     *
     * Écraser et non fusionner champ par champ : une carte est servie entière par l'API, et une
     * fusion partielle laisserait cohabiter deux âges de la même donnée.
     */
    setCandidates(payload: CandidateCard[]): void {
      const byId = { ...this.byId }

      for (const candidate of payload) {
        byId[candidate.id] = candidate
      }

      this.byId = byId
    },

    /**
     * Ce qui vient d'être payé sur un profil, appliqué au pool.
     *
     * Le dévoilement change le profil LUI-MÊME et non sa place dans une recherche : il vaut donc
     * partout à la fois, ce qui est exactement ce que le pool permet.
     */
    setRevealed(candidateId: string, revealed: Partial<CandidateCard>): void {
      const candidate = this.byId[candidateId]
      if (!candidate) {
        return
      }

      this.byId = { ...this.byId, [candidateId]: { ...candidate, ...revealed } }
    },

    reset(): void {
      this.byId = {}
    },
  },
})
