/**
 * Compte utilisateur admin (GET /v1/admin/users). Forme plate hand-authored en miroir de
 * `AdminUserOutput` côté API (items de collection opaques côté contrat).
 */
export interface AdminUser {
  id: string
  givenName: string
  familyName: string
  email: string
  roles: string[]
  status: string
  lastLoginAt: string | null
  createdAt: string
}

export interface AdminUserQueue {
  items: AdminUser[]
  total: number
  page: number
  perPage: number
}

/**
 * Une personne CITÉE dans un espace : l'auteur d'une recherche, celui qui a dévoilé un profil.
 *
 * À ne pas confondre avec l'utilisateur CONNECTÉ, que porte `useUserSession` — ici on ne stocke que
 * de quoi afficher un nom en face d'un identifiant. L'API sert ce nom en doublon sur chaque objet
 * qui le cite ; ce type existe pour qu'il ne vive qu'une fois côté client.
 */
export interface SpaceUser {
  id: string
  name: string
}
