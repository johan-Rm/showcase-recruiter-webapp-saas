/**
 * Candidat tel que l'API admin le renvoie (GET /v1/admin/candidates). L'enveloppe générée
 * (`AdminCandidate.CollectionOutput`) type ses `items` en opaque — l'OpenAPI ne porte pas le
 * paramètre de type de la collection. On rétablit ici la forme réelle des éléments, une seule fois,
 * en miroir de `AdminCandidateOutput` côté API. NON masqué (vue opérateur).
 *
 * Passera à `components['schemas']['AdminCandidate']` quand la fiche (GET /admin/candidates/{id})
 * exposera le schéma d'item côté contrat.
 */
export interface AdminCandidate {
  id: string
  givenName: string
  familyName: string
  photoUrl: string | null
  headline: string | null
  experienceYears: number
  city: string | null
  country: string | null
  contactEmail: string | null
  phone: string | null
  linkedinUrl: string | null
  desiredSector: string | null
  desiredFunction: string | null
  availability: string | null
  returnHorizon: string | null
  geoMobility: boolean
  hasPiedATerre: boolean
  piedATerreCity: string | null
  skills: string[]
  isStar: boolean
  visibility: string
  active: boolean
  hasConsent: boolean
  consentGivenAt: string | null
  syncStatus: string
  createdAt: string
  updatedAt: string
}

export interface AdminCandidateQueue {
  items: AdminCandidate[]
  total: number
  page: number
  perPage: number
}

/** Réplica Jarvi : formes JSON opaques côté contrat, on type au minimum ce qu'on affiche. */
export interface AdminCandidateWorkExperience {
  title?: string | null
  companyName?: string | null
  startDate?: string | null
  endDate?: string | null
  [key: string]: unknown
}

export interface AdminCandidateLanguage {
  language?: string | null
  proficiency?: string | null
  [key: string]: unknown
}

export interface AdminCandidateEducation {
  title?: string | null
  school?: string | null
  degree?: string | null
  startDate?: string | null
  endDate?: string | null
  [key: string]: unknown
}

export interface AdminCandidatePresence {
  startDate: string
  endDate: string
  city: string | null
}

/** Fiche candidat admin (GET /v1/admin/candidates/{id}) — profil complet non masqué. */
export interface AdminCandidateDetail {
  id: string
  givenName: string
  familyName: string
  photoUrl: string | null
  headline: string | null
  summary: string | null
  experienceYears: number
  city: string | null
  country: string | null
  contactEmail: string | null
  phone: string | null
  whatsappNumber: string | null
  linkedinUrl: string | null
  desiredSector: string | null
  desiredFunction: string | null
  availability: string | null
  returnHorizon: string | null
  returnProject: string | null
  geoMobility: boolean
  hasPiedATerre: boolean
  piedATerreCity: string | null
  skills: string[]
  languages: AdminCandidateLanguage[]
  workExperiences: AdminCandidateWorkExperience[]
  educations: AdminCandidateEducation[]
  resumes: Array<Record<string, unknown>>
  presencePeriods: AdminCandidatePresence[]
  isStar: boolean
  starInterviewedAt: string | null
  starInterviewReport: string | null
  visibility: string
  active: boolean
  hasConsent: boolean
  consentGivenAt: string | null
  consentSource: string | null
  syncStatus: string
  jarviProfileId: string
  createdAt: string
  updatedAt: string
}
