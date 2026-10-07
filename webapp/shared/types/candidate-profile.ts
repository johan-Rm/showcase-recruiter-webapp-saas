/**
 * Profil candidat complet (GET /v1/candidates/{id}) — ce que la vignette de recherche ne porte pas :
 * le résumé, le parcours daté, les formations, les langues, les documents.
 *
 * MASQUÉ tant que le profil n'est pas dévoilé, et c'est le SERVEUR qui masque. Une propriété
 * verrouillée n'arrive pas vide : elle **n'arrive pas du tout**, l'API n'écrivant jamais les valeurs
 * nulles. D'où l'optionnel partout — `contactEmail?` se lit « absent tant que non dévoilé », pas
 * « le candidat n'en a pas ». La question « en a-t-il un ? » a ses propres drapeaux, `hasEmail` et
 * `hasPhone`, qui eux arrivent toujours.
 *
 * ⚠️ Écrit à la MAIN, comme `candidate-card.ts` et pour la même raison : le schéma généré type ses
 * tableaux en `(string | null)[]` — l'OpenAPI ne descend pas dans la forme des expériences ni des
 * formations. La gate de contrat ne voit donc pas ce fichier ; une évolution de `CandidateOutput`
 * côté API doit être reportée ici, rien ne le signalera.
 *
 * ⚠️ QUATRE PROPRIÉTÉS MANQUENT ICI et vivent dans la vignette : `returnProject`, `hasPiedATerre`,
 * `piedATerreCity`, `isInMorocco`. La fiche détaillée est, sur les différenciants Maroc, plus pauvre
 * que la carte de résultat. `services/search/profile.ts` recolle les deux sources.
 */
import type { CandidatePresencePeriod } from './candidate-card'

/** Un poste occupé. `endDate` absent ou nul = poste en cours. */
export interface WorkExperience {
  title: string | null
  companyName: string | null
  startDate: string | null
  endDate: string | null
  source: string | null
}

/**
 * L'école, écrite DIFFÉREMMENT par les deux sources : un nom en clair sur les profils de
 * démonstration, un objet `{ id, name }` sur tout ce qui vient du réseau professionnel.
 *
 * Le type l'annonçait en chaîne seule, et l'écran affichait l'objet tel quel — `{ "id": "65d3…",
 * "name": "UMONS" }` — sur 93 % du vivier. `services/search/profile.ts` en tire le nom.
 */
export type EducationSchool = string | { id?: string; name?: string | null } | null

export interface Education {
  school: EducationSchool
  title: string | null
  startDate: string | null
  endDate: string | null
}

/**
 * Un métier EXERCÉ, dérivé du parcours par le serveur, et sa durée cumulée.
 *
 * `months` est indicatif — le rattachement des intitulés est partiel, la durée sous-estime. Le
 * signal fiable est la PRÉSENCE du métier, et c'est le seul que l'entreprise affiche.
 */
export interface JobExperience {
  job: string | null
  months: number
  positions: number
  exactPositions: number
}

/** Un secteur d'expérience et ses mois cumulés. `sector` est un code d'énumération, pas un libellé. */
export interface SectorExperience {
  sector: string | null
  months: number
}

/** `language` est un code ISO à deux lettres, `proficiency` une valeur de l'énumération API. */
export interface SpokenLanguage {
  language: string | null
  proficiency: string | null
}

/** Un CV rattaché. `fileId` désigne le fichier chez Jarvi ; aucune URL n'est exposée. */
export interface Resume {
  fileId: string | null
  name: string | null
  mimeType: string | null
  parsedAt: string | null
}

export interface CandidateProfile {
  id: string
  givenName?: string
  familyName?: string | null
  photoUrl?: string | null
  headline?: string | null
  summary?: string | null
  experienceYears?: number
  currentPositionCertainty?: string
  currentTitle?: string | null
  /** Ré-identifiant, donc payant : n'arrive qu'une fois le profil dévoilé. */
  currentCompanyName?: string | null
  currentJob?: string | null
  city?: string | null
  country?: string | null
  contactEmail?: string | null
  phone?: string | null
  whatsappNumber?: string | null
  linkedinUrl?: string | null
  hasEmail?: boolean
  hasPhone?: boolean
  lastInteractionAt?: string | null
  lastInteractionType?: string | null
  desiredSector?: string | null
  desiredFunction?: string | null
  availability?: string | null
  returnHorizon?: string | null
  geoMobility?: boolean
  skills?: string[]
  languages?: SpokenLanguage[]
  jobExperiences?: JobExperience[]
  sectorExperiences?: SectorExperience[]
  spokenLanguages?: string[]
  workExperiences?: WorkExperience[]
  educations?: Education[]
  educationLevel?: string | null
  resumes?: Resume[]
  presencePeriods?: CandidatePresencePeriod[]
  isStar?: boolean
  starInterviewedAt?: string | null
  starInterviewReport?: string | null
  revealedEmail?: boolean
  revealedPhone?: boolean
  revealedResume?: boolean
  revealedReport?: boolean
  createdAt?: string
  updatedAt?: string
}
