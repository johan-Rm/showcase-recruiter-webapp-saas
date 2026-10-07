/**
 * Instantané de supervision de l'ingestion Jarvi (GET /v1/admin/jarvi). Forme plate, hand-authored
 * en miroir de `AdminJarviOutput` côté API.
 */
export interface AdminJarviStream {
  name: string
  cursor: string | null
  lastRunAt: string | null
}

export interface AdminJarvi {
  streams: AdminJarviStream[]
  total: number
  active: number
  inactive: number
  withConsent: number
  synced: number
  pending: number
  failed: number
}
