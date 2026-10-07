import { statesOf } from './states'

import type { CandidateCard, CandidatePresencePeriod } from '#shared/types/candidate-card'
import type {
  CandidateProfile,
  Education,
  EducationSchool,
  Resume,
  SpokenLanguage,
  WorkExperience,
} from '#shared/types/candidate-profile'
import type { ProfileState } from './states'

/**
 * Recolle les DEUX sources d'un même profil : la vignette de résultat, déjà en mémoire, et la fiche
 * complète que le serveur rend à la demande.
 *
 * Aucune ne suffit seule. La fiche porte le résumé, le parcours daté, les formations, les langues et
 * les documents. La vignette porte quatre choses que la fiche ignore — le projet de retour, le
 * pied-à-terre, sa ville et la présence en cours au Maroc —, c'est-à-dire les différenciants qui
 * font tout l'intérêt du vivier. Un écran qui n'en lirait qu'une serait faux dans les deux sens.
 *
 * Le tiroir s'ouvrant TOUJOURS depuis une liste, la vignette est acquise et la fiche arrive après :
 * `profile` est donc nullable, et tout ce qui suit doit rendre un résultat utile sans elle.
 */

/**
 * L'état d'une donnée qui se paie, et il y en a trois — pas deux.
 *
 * Confondre « verrouillé » et « le candidat n'en a pas » est l'erreur qui coûte un crédit : le
 * recruteur paie pour découvrir qu'il n'y avait rien à découvrir. Les drapeaux `hasEmail` et
 * `hasPhone` existent précisément pour lever cette confusion, et l'écran doit la rendre visible.
 */
export type LockState = 'revealed' | 'locked' | 'absent'

export interface LockedValue<TValue> {
  state: LockState
  /** Renseigné pour le seul état `revealed`. */
  value: TValue | null
}

const revealed = <TValue>(value: TValue): LockedValue<TValue> => ({ state: 'revealed', value })
const locked = <TValue>(): LockedValue<TValue> => ({ state: 'locked', value: null })
const absent = <TValue>(): LockedValue<TValue> => ({ state: 'absent', value: null })

/**
 * Une coordonnée : son existence est connue AVANT l'achat, sa valeur seulement après.
 *
 * `has` faux ferme le sujet — inutile de proposer un achat. `has` vrai sans valeur veut dire
 * verrouillé : l'API omet la propriété plutôt que de l'envoyer vide.
 */
const contact = (
  has: boolean | undefined,
  value: string | null | undefined,
): LockedValue<string> => {
  if (!has) return absent()

  const trimmed = value?.trim()

  return trimmed ? revealed(trimmed) : locked()
}

/**
 * Un document : son existence n'est PAS connue avant l'achat.
 *
 * Rien dans le contrat ne dit « ce candidat a un CV » — le tableau arrive nul tant qu'on n'a pas
 * payé, et vide si l'on a payé pour rien. Tant qu'il est verrouillé, on ne peut donc qu'annoncer un
 * achat possible ; c'est la limite du contrat, pas un raccourci d'affichage.
 */
const document = <TValue>(
  isRevealed: boolean | undefined,
  value: TValue | null | undefined,
): LockedValue<TValue> => {
  if (!isRevealed) return locked()

  const isEmpty =
    null === value || undefined === value || (Array.isArray(value) && 0 === value.length)

  return isEmpty ? absent() : revealed(value)
}

/**
 * Poste en cours d'abord, puis du plus récent au plus ancien.
 *
 * Un poste sans date de fin est EN COURS, et passe devant quelle que soit sa date de début : c'est
 * ce que le lecteur cherche en premier, et un poste commencé en 2018 mais toujours tenu prime sur un
 * poste terminé en 2024.
 */
const byRecency = (left: WorkExperience, right: WorkExperience): number => {
  const ongoing = Number(Boolean(left.endDate)) - Number(Boolean(right.endDate))
  if (0 !== ongoing) return ongoing

  return (right.startDate ?? '').localeCompare(left.startDate ?? '')
}

const byEndDate = (left: Education, right: Education): number =>
  (right.endDate ?? right.startDate ?? '').localeCompare(left.endDate ?? left.startDate ?? '')

/** Une formation dont l'école est un NOM, quelle que soit la source qui l'a écrite. */
export interface CareerEducation {
  school: string | null
  title: string | null
  startDate: string | null
  endDate: string | null
}

const schoolName = (school: EducationSchool): string | null => {
  const name = 'string' === typeof school ? school : school?.name

  return name?.trim() || null
}

/**
 * Le profil tel que le tiroir le lit — cinq blocs, dans l'ordre où l'on décide.
 *
 * Chaque bloc porte `isEmpty` plutôt que de laisser le template additionner des conditions : une
 * section vide ne doit pas s'afficher, pas même en creux avec son titre suivi de rien.
 *
 * LE DÉCOUPAGE ÉPOUSE LES DEUX COLONNES du tiroir : `career` ne porte que ce qui est daté, donc ce
 * que la frise sait rendre, et son `isEmpty` répond à lui seul « la colonne de droite a-t-elle
 * quelque chose à montrer ». Tout le reste se lit à gauche.
 */
export interface ProfileIdentity {
  id: string
  givenName: string | null
  familyName: string | null
  photoUrl: string | null
  /**
   * CE QUE LA PERSONNE DIT D'ELLE-MÊME, et c'est pour cela que les deux voyagent avec l'identité
   * plutôt qu'avec le parcours : l'accroche qu'elle écrit sur son réseau, puis le résumé.
   *
   * L'accroche ne sert PAS d'intitulé — la card de la Visionneuse le refuse à raison, elle monte à
   * 752 caractères sur le vivier et s'arrogerait le bandeau. Elle se lit comme une phrase, sous le
   * nom, et elle est ce que 99,7 % des profils portent quand le résumé n'en a qu'un sur cent.
   */
  headline: string | null
  summary: string | null
  currentTitle: string | null
  city: string | null
  country: string | null
  experienceYears: number | null
  educationLevel: string | null
  isStar: boolean
  /**
   * GRATUITS tous les deux, et ce n'est pas un oubli de masquage : l'employeur du moment et le
   * profil public suivent l'identité, qu'on donne à lire pour donner envie de payer le CONTACT.
   * Ce qui se paie, ce sont les moyens de joindre — pas ce qui se trouve déjà en cherchant un nom.
   */
  company: string | null
  linkedinUrl: string | null
}

export interface ProfileMorocco {
  returnHorizon: string | null
  availability: string | null
  desiredSector: string | null
  desiredFunction: string | null
  returnProject: string | null
  geoMobility: boolean
  hasPiedATerre: boolean
  piedATerreCity: string | null
  isInMorocco: boolean
  presencePeriods: CandidatePresencePeriod[]
  isEmpty: boolean
}

/**
 * LES MOTS DU PROFIL — ce qui n'a pas de date, donc rien à faire sur une frise.
 *
 * Métiers, secteurs, compétences et langues répondent à « que sait-il faire, et où », quand le
 * parcours répond à « qu'a-t-il fait, et quand ». Deux questions, deux colonnes à l'écran, deux
 * blocs ici — les mélanger obligeait le tiroir à trier une même liste selon la présence d'une date.
 *
 * Les métiers et les secteurs n'arrivent qu'avec la fiche : la vignette ne les porte pas.
 */
export interface ProfileExpertise {
  jobs: string[]
  sectors: string[]
  skills: string[]
  languages: SpokenLanguage[]
  isEmpty: boolean
}

/** Le parcours daté, et lui seul : c'est exactement ce que la frise sait rendre. */
export interface ProfileCareer {
  workExperiences: WorkExperience[]
  educations: CareerEducation[]
  isEmpty: boolean
}

export interface ProfileLocked {
  email: LockedValue<string>
  phone: LockedValue<string>
  resume: LockedValue<Resume[] | Resume>
  report: LockedValue<string>
}

export interface FullProfile {
  identity: ProfileIdentity
  morocco: ProfileMorocco
  expertise: ProfileExpertise
  career: ProfileCareer
  locked: ProfileLocked
  /**
   * Ce que le profil EST, dans le vocabulaire commun aux surfaces qui le montrent — rencontré,
   * favori, disponible, nouveau, au Maroc, vu, écarté.
   *
   * Il vient du catalogue de `states.ts` et non d'une lecture locale des drapeaux : les trois
   * surfaces qui montrent un profil le décrivent alors avec les mêmes mots, et deux de ces états —
   * rencontré par showcase-recruiter-webapp-saas, au Maroc en ce moment — sont les signaux les plus forts du produit.
   */
  states: ProfileState[]
  /** Faux tant que la fiche n'est pas arrivée : le tiroir affiche alors la vignette seule. */
  isComplete: boolean
}

const text = (value: string | null | undefined): string | null => value?.trim() || null

const isPresent = (value: string | null): value is string => null !== value && '' !== value

export const mergeProfile = (
  card: CandidateCard,
  profile: CandidateProfile | null,
): FullProfile => {
  // La FICHE gagne sur la vignette partout où elle a la donnée : elle est plus fraîche, et c'est
  // elle qui porte la version longue. La vignette reste le socle, jamais un simple secours — les
  // quatre différenciants Maroc ne viennent que d'elle.
  const identity: ProfileIdentity = {
    id: card.id,
    givenName: text(profile?.givenName ?? card.givenName),
    familyName: text(profile?.familyName ?? card.familyName),
    photoUrl: text(profile?.photoUrl ?? card.photoUrl),
    headline: text(profile?.headline ?? card.headline),
    summary: text(profile?.summary),
    currentTitle: text(profile?.currentTitle ?? card.currentTitle),
    city: text(profile?.city ?? card.city),
    country: text(profile?.country ?? card.country),
    experienceYears: profile?.experienceYears ?? card.experienceYears ?? null,
    educationLevel: text(profile?.educationLevel ?? card.educationLevel),
    isStar: profile?.isStar ?? card.isStar ?? false,
    company: text(profile?.currentCompanyName),
    linkedinUrl: text(profile?.linkedinUrl),
  }

  const presencePeriods = profile?.presencePeriods ?? card.presencePeriods ?? []

  const morocco: ProfileMorocco = {
    returnHorizon: text(profile?.returnHorizon ?? card.returnHorizon),
    availability: text(profile?.availability ?? card.availability),
    desiredSector: text(profile?.desiredSector ?? card.desiredSector),
    desiredFunction: text(profile?.desiredFunction ?? card.desiredFunction),
    // Les quatre que la fiche ne porte pas : la vignette est la seule source.
    returnProject: text(card.returnProject),
    geoMobility: profile?.geoMobility ?? card.geoMobility ?? false,
    hasPiedATerre: card.hasPiedATerre ?? false,
    piedATerreCity: text(card.piedATerreCity),
    isInMorocco: card.isInMorocco ?? false,
    presencePeriods,
    isEmpty: false,
  }

  morocco.isEmpty =
    !morocco.returnHorizon &&
    !morocco.availability &&
    !morocco.desiredSector &&
    !morocco.desiredFunction &&
    !morocco.returnProject &&
    !morocco.geoMobility &&
    !morocco.hasPiedATerre &&
    0 === morocco.presencePeriods.length

  // Le serveur envoie des OBJETS — métier, mois cumulés, nombre de postes — et l'entreprise n'affiche
  // que le nom : les mois sous-estiment, le rattachement des intitulés étant partiel. Ne garder que
  // ce qui se lit évite d'exposer un chiffre qu'il faudrait accompagner d'un avertissement.
  const expertise: ProfileExpertise = {
    jobs: (profile?.jobExperiences ?? []).map((entry) => entry.job).filter(isPresent),
    sectors: (profile?.sectorExperiences ?? []).map((entry) => entry.sector).filter(isPresent),
    skills: profile?.skills ?? card.skills ?? [],
    languages: profile?.languages ?? [],
    isEmpty: false,
  }

  expertise.isEmpty =
    0 === expertise.jobs.length &&
    0 === expertise.sectors.length &&
    0 === expertise.skills.length &&
    0 === expertise.languages.length

  const career: ProfileCareer = {
    workExperiences: [...(profile?.workExperiences ?? [])].sort(byRecency),
    educations: [...(profile?.educations ?? [])]
      .sort(byEndDate)
      .map((education) => ({ ...education, school: schoolName(education.school) })),
    isEmpty: false,
  }

  career.isEmpty = 0 === career.workExperiences.length && 0 === career.educations.length

  // Les drapeaux se lisent sur la fiche EN PRIORITÉ, et retombent sur la vignette : tant que la
  // fiche n'est pas arrivée, c'est la vignette qui sait déjà si le profil est joignable, et un
  // `undefined` intermédiaire ferait clignoter la ligne en « pas de téléphone » avant de se corriger.
  const flag = (key: 'hasEmail' | 'hasPhone' | 'revealedResume' | 'revealedReport'): boolean =>
    profile?.[key] ?? card[key] ?? false

  return {
    identity,
    morocco,
    expertise,
    career,
    states: statesOf(card),
    locked: {
      email: contact(flag('hasEmail'), profile?.contactEmail),
      phone: contact(flag('hasPhone'), profile?.phone),
      resume: document(flag('revealedResume'), profile?.resumes),
      // Le compte rendu est le SEUL document dont on sache l'existence sans l'avoir payé : il
      // n'existe que si showcase-recruiter-webapp-saas a mené l'entretien, et `isStar` le dit gratuitement. Proposer un
      // achat sur un profil jamais rencontré ferait payer du vide.
      report: identity.isStar
        ? document(flag('revealedReport'), profile?.starInterviewReport)
        : absent(),
    },
    isComplete: null !== profile,
  }
}
