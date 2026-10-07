/**
 * Compteurs du centre de contrôle admin (GET /v1/admin/dashboard). Forme plate hand-authored en
 * miroir de `AdminDashboardOutput` côté API. Activité sur une fenêtre glissante de 7 jours.
 */
export interface AdminDashboard {
  organizationsInReview: number
  loginsLast7Days: number
  revealsLast7Days: number
  signupsLast7Days: number
  candidatesTotal: number
  candidatesActive: number
}
