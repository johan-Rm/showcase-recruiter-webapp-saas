/**
 * Une visite de profil telle que l'administration la lit (GET /v1/admin/activity).
 *
 * Forme plate écrite à la main en miroir d'`AdminActivityOutput` côté API : l'enveloppe générée
 * type ses `items` en opaque, l'OpenAPI ne portant pas le paramètre de type d'une collection.
 *
 * `revealed` porte la valeur du fil : visiter n'est pas dévoiler, et un candidat souvent regardé
 * sans jamais être débloqué ne se lit pas comme un autre.
 */
export interface AdminActivityEntry {
  id: string
  viewedAt: string
  recruiterName: string
  /** Absent quand la personne n'a pas d'adresse de contact — l'API omet les champs nuls. */
  recruiterEmail?: string | null
  organizationName: string
  candidateId: string
  /** Nom complet : l'administration voit les profils non masqués. */
  candidateName: string
  revealed: boolean
}

export interface AdminActivityCollection {
  items: AdminActivityEntry[]
  total: number
  page: number
  perPage: number
}
