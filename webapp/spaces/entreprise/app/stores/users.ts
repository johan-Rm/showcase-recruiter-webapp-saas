import type { SpaceUser } from '#shared/types/user'

// Les personnes CITÉES dans l'espace : l'auteur d'une recherche, celui qui a dévoilé un profil.
//
// À ne pas confondre avec l'utilisateur CONNECTÉ, que porte `useUserSession` (nuxt-auth-utils). Ici
// on ne garde que de quoi mettre un nom en face d'un identifiant.
//
// POURQUOI CE STORE — l'API sert `ownerName` sur chaque recherche qui le cite : dix recherches du
// même collègue, dix fois son nom. Recopié tel quel, il faudrait le corriger en dix endroits le jour
// où il change. Ici il vit une fois, et `ownerId` y renvoie.
//
// Indexé par identifiant, pas en tableau : toute lecture est un `getUser(id)`, jamais un parcours.
type UsersState = {
  byId: Record<string, SpaceUser>
}

export const useUsersStore = defineStore('users', {
  state: (): UsersState => ({
    byId: {},
  }),

  getters: {
    getUser(state: UsersState): (id: string) => SpaceUser | null {
      return (id) => state.byId[id] ?? null
    },

    /**
     * Le nom d'une personne, ou une chaîne vide si on ne la connaît pas encore.
     *
     * Vide et non un « Inconnu » : l'écran qui affiche un auteur sait mieux que ce store quoi mettre
     * à la place, et un libellé de repli posé ici ne serait jamais traduit.
     */
    getUserName(state: UsersState): (id: string) => string {
      return (id) => state.byId[id]?.name ?? ''
    },
  },

  actions: {
    /**
     * Range des personnes, en écrasant celles du même identifiant.
     *
     * Écraser et non ignorer : la donnée qui arrive vient d'être lue, elle est plus fraîche que celle
     * qu'on garde.
     */
    setUsers(payload: SpaceUser[]): void {
      const byId = { ...this.byId }

      for (const user of payload) {
        byId[user.id] = user
      }

      this.byId = byId
    },

    reset(): void {
      this.byId = {}
    },
  },
})
