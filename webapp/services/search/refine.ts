import { hasStayWithin, stayCities } from './presence'

import type { CandidateCard } from '../../shared/types/candidate-card'

/**
 * L'AFFINAGE du parcours — resserrer ce qu'on regarde, sans toucher à ce qu'on cherche.
 *
 * Ce n'est pas un critère de recherche, et la distinction porte tout le module : un critère entre
 * dans la recherche, la nomme, la définit et se retrouve à la visite suivante ; un affinage n'est
 * qu'un angle de lecture posé sur le jeu déjà ramené, au même titre que « ne montre que les
 * retenus ». Rien de ce qui est ici n'est envoyé au serveur ni conservé.
 *
 * Il ne porte donc QUE des facettes que la carte de profil sait déjà répondre : le jeu de résultats
 * est matérialisé côté serveur et ne se re-filtre pas à la demande, mais tout ce qui est demandé ici
 * descend avec chaque carte. Ajouter une facette suppose d'abord de vérifier qu'elle y est.
 */
export interface RefineForm {
  /** `''` ne pose rien · `'now'` dit *aujourd'hui* · sinon le nombre de jours de la fenêtre. */
  presence: string
  presenceCity: string
  /** Coché = on veut ceux qui en ont. Décoché = peu importe, jamais « ceux qui n'en ont pas ». */
  hasPiedATerre: boolean
  returnHorizon: string
  geoMobility: boolean
  availability: string
  country: string
  /**
   * Coché = on ne garde que ceux dont le métier cherché est le poste du MOMENT.
   *
   * ⚠️ Il écarte aussi ceux qu'on ne sait pas classer. Le serveur ne résout le poste actuel que sur
   * une moitié du vivier : quand le parcours est ambigu, il se tait plutôt que de deviner, et la
   * mention manque. Compter ce silence comme un « oui » mentirait ; le compter comme un « non » est
   * ce qu'on fait, faute de mieux, et c'est au libellé de le dire.
   */
  currentJob: boolean
  language: string
  /**
   * Le SEUIL de maîtrise, et il ne vaut qu'adossé à une langue — seul, « au moins courant » ne
   * désigne rien.
   *
   * ⚠️ Il ÉCARTE les entrées sans niveau déclaré, un tiers du vivier n'en portant pas. Compter ce
   * silence comme un « oui » mentirait ; c'est au libellé de dire qu'on filtre sur du déclaré.
   */
  languageLevel: string
  experienceSector: string
  /**
   * CE QUE LE CANDIDAT CHERCHE, quand `experienceSector` dit ce qu'il a fait.
   *
   * Deux questions distinctes : un ingénieur qui veut basculer vers la finance porte l'industrie en
   * expérience et la finance en projet. Les confondre rendrait le second invisible.
   */
  desiredSector: string
  desiredFunction: string
  /** Coché = rencontré en entretien par showcase-recruiter-webapp-saas. Décoché = peu importe, jamais « les non vérifiés ». */
  isStar: boolean
}

/**
 * Les paliers de maîtrise, DU PLUS BAS AU PLUS HAUT — l'ordre EST le sens, il porte le seuil.
 *
 * Une valeur hors de cette échelle ne se compare pas, et se comporte donc comme une absence de
 * niveau : écartée dès qu'un seuil est posé.
 */
export const LANGUAGE_LEVELS = [
  'ELEMENTARY',
  'LIMITED_WORKING',
  'PROFESSIONAL_WORKING',
  'FULL_PROFESSIONAL',
  'NATIVE_OR_BILINGUAL',
] as const

/** Les fenêtres offertes, dans l'ordre où elles se lisent — de la plus serrée à la plus large. */
export const PRESENCE_WINDOWS = ['now', '15', '30', '60'] as const

/** Un affinage vierge : rien n'est posé, donc rien n'est resserré. */
export const emptyRefineForm = (): RefineForm => ({
  presence: '',
  presenceCity: '',
  hasPiedATerre: false,
  returnHorizon: '',
  geoMobility: false,
  availability: '',
  country: '',
  currentJob: false,
  language: '',
  languageLevel: '',
  experienceSector: '',
  desiredSector: '',
  desiredFunction: '',
  isStar: false,
})

/** Au moins un affinage posé — ce qui commande l'affichage du retrait et du décompte. */
export const isRefined = (form: RefineForm): boolean =>
  '' !== form.presence ||
  '' !== form.presenceCity ||
  form.hasPiedATerre ||
  '' !== form.returnHorizon ||
  form.geoMobility ||
  '' !== form.availability ||
  '' !== form.country ||
  form.currentJob ||
  // Le NIVEAU n'entre pas dans ce compte : seul, il ne resserre rien — le prédicat de langue le
  // laisse passer sans langue posée. L'y ajouter annoncerait un affinage qui ne retire aucun profil.
  '' !== form.language ||
  '' !== form.experienceSector ||
  '' !== form.desiredSector ||
  '' !== form.desiredFunction ||
  form.isStar

/**
 * La profondeur d'une ville posée seule.
 *
 * Deux ans : assez pour qu'un séjour annoncé de longue date entre, assez court pour qu'une ville ne
 * ramène pas des périodes que plus personne ne tiendra.
 */
const HORIZON_DAYS = 730

/** `'now'` referme la fenêtre sur le jour même ; toute autre valeur porte son nombre de jours. */
const windowDays = (presence: string): number => ('now' === presence ? 0 : Number(presence))

/**
 * La présence, fenêtre et ville CONFONDUES en une seule question.
 *
 * Les deux facettes se répondent ensemble parce qu'elles portent sur la même période côté serveur.
 * Sans fenêtre mais avec une ville, la question devient *un séjour à Casablanca, un jour* — la
 * fenêtre s'ouvre alors sur tout ce qui reste à venir plutôt que de se refermer sur aujourd'hui.
 */
const matchesPresence = (card: CandidateCard, form: RefineForm, at: Date): boolean => {
  const city = '' === form.presenceCity ? null : form.presenceCity

  if ('' === form.presence) {
    return null === city || hasStayWithin(card.presencePeriods, HORIZON_DAYS, city, at)
  }

  return hasStayWithin(card.presencePeriods, windowDays(form.presence), city, at)
}

/**
 * Le métier cherché est-il le poste du MOMENT ?
 *
 * `matchedWhen` absent ne veut pas dire « passé » : il veut dire que le serveur n'a pas tranché.
 * Seul un `CURRENT` explicite retient le profil — cf. la réserve portée par `currentJob`.
 */
const holdsJobToday = (card: CandidateCard): boolean =>
  card.matches?.some(
    (match) => 'job' === match.facet && match.matched && 'CURRENT' === match.matchedWhen,
  ) ?? false

/**
 * La langue ET son niveau, sur la MÊME entrée du profil.
 *
 * C'est tout l'enjeu : évaluées séparément, « anglais » et « au moins bilingue » seraient satisfaits
 * par un arabophone maternel qui a des notions d'anglais — un profil qui ne répond pourtant pas à la
 * demande. Même piège que la fenêtre de présence et sa ville, qui portent sur la même période.
 *
 * SANS LANGUE POSÉE, le seuil ne s'applique pas : il n'aurait aucune entrée à qualifier.
 */
const matchesLanguage = (card: CandidateCard, form: RefineForm): boolean => {
  if ('' === form.language) return true

  const floor = LANGUAGE_LEVELS.indexOf(form.languageLevel as (typeof LANGUAGE_LEVELS)[number])

  return (card.languages ?? []).some(
    (spoken) =>
      spoken.language === form.language &&
      ('' === form.languageLevel ||
        (null !== spoken.proficiency &&
          LANGUAGE_LEVELS.indexOf(spoken.proficiency as (typeof LANGUAGE_LEVELS)[number]) >=
            floor)),
  )
}

/** Un profil passe l'affinage — ET entre les facettes, chacune ne resserrant que si elle est posée. */
export const matchesRefine = (card: CandidateCard, form: RefineForm, at: Date): boolean =>
  matchesPresence(card, form, at) &&
  (!form.hasPiedATerre || card.hasPiedATerre) &&
  ('' === form.returnHorizon || card.returnHorizon === form.returnHorizon) &&
  (!form.geoMobility || card.geoMobility) &&
  ('' === form.availability || card.availability === form.availability) &&
  ('' === form.country || card.country === form.country) &&
  (!form.currentJob || holdsJobToday(card)) &&
  matchesLanguage(card, form) &&
  ('' === form.experienceSector || sectorsOf(card).includes(form.experienceSector)) &&
  ('' === form.desiredSector || card.desiredSector === form.desiredSector) &&
  ('' === form.desiredFunction || card.desiredFunction === form.desiredFunction) &&
  (!form.isStar || card.isStar)

/** Le jeu resserré, dans son ordre d'origine — l'affinage sélectionne, il ne réordonne pas. */
export const refine = (
  cards: readonly CandidateCard[],
  form: RefineForm,
  at: Date,
): CandidateCard[] => cards.filter((card) => matchesRefine(card, form, at))

/**
 * Combien de profils si l'on posait CETTE option — les autres affinages restant ce qu'ils sont.
 *
 * C'est le comptage facetté, et il tient en une substitution : l'option remplace la valeur de sa
 * propre facette, le reste du formulaire ne bouge pas. Compter sur le jeu déjà resserré par la
 * facette elle-même rendrait toutes les autres options à zéro, et le compteur ne dirait plus que ce
 * qu'on vient de choisir.
 */
export const countFor = <TFacet extends keyof RefineForm>(
  cards: readonly CandidateCard[],
  form: RefineForm,
  facet: TFacet,
  value: RefineForm[TFacet],
  at: Date,
): number => refine(cards, { ...form, [facet]: value }, at).length

/**
 * UN CHOIX UNIQUE N'EN EST PAS UN, et il ne s'offre donc pas.
 *
 * Une liste à un seul bouton ne resserre rien : tous les profils du jeu la satisfont déjà. Elle
 * occupe pourtant son intitulé et sa ligne, et le panneau masque déjà les listes vides pour la
 * raison voisine — un intitulé suivi du vide se lit comme un chargement qui n'a pas abouti. Mesuré :
 * sur la plupart des recherches, le pays n'offre que « FR ».
 *
 * LE SEUIL PORTE SUR LE NOMBRE DE CHOIX, jamais sur le nombre de profils : deux pays dont l'un ne
 * compte qu'un seul profil restent deux choix, et c'est précisément là que l'affinage sert.
 */
const offered = (values: string[]): string[] => (values.length > 1 ? values : [])

/**
 * LES VALEURS RANGÉES PAR POIDS — la plus portée d'abord.
 *
 * L'ordre est le seul repère qui reste depuis que les étiquettes ne portent plus leur nombre : à
 * l'alphabet, la valeur qui tient les trois quarts du jeu se retrouve enfouie entre deux valeurs à
 * un profil, et rien à l'écran ne dit laquelle vaut le clic.
 *
 * IL NE SUIT PAS LES FILTRES POSÉS : le poids est celui du jeu, jamais celui de l'affinage en cours,
 * sans quoi les étiquettes se réordonneraient sous le curseur à chaque clic. Il n'est pas figé pour
 * autant — le jeu se remplissant en arrière-plan, l'ordre se stabilise pendant le chargement.
 *
 * On compte des PROFILS et non des occurrences — une carte qui listerait deux fois la même langue
 * ne pèse pas double. À poids égal, le code départage : cette couche ignore la langue d'affichage,
 * et un ordre qui dépendrait du nom ne serait plus le même des deux côtés du rendu.
 */
const byWeight = (perCard: readonly (readonly string[])[]): string[] => {
  const weight = new Map<string, number>()

  for (const values of perCard) {
    for (const value of new Set(values)) {
      weight.set(value, (weight.get(value) ?? 0) + 1)
    }
  }

  return [...weight.keys()].sort(
    (left, right) =>
      (weight.get(right) ?? 0) - (weight.get(left) ?? 0) || left.localeCompare(right),
  )
}

/** Les villes de séjour offertes au choix : celles du jeu, et rien d'autre. */
export const refinableCities = (cards: readonly CandidateCard[]): string[] =>
  offered(byWeight(cards.map((card) => stayCities([card.presencePeriods]))))

/**
 * Les pays offerts : ceux du jeu, rangés.
 *
 * Une liste fermée de pays européens afficherait des options mortes sur une recherche qui n'en
 * ramène que trois — et en oublierait une que le vivier porte pourtant.
 *
 * LA GARDE PORTE SUR LE TYPE, pas sur `null` : le contrat annonce `string | null`, la carte arrive
 * parfois SANS la clé, et `null !== undefined` laissait alors passer jusqu'à `undefined.trim()`.
 * Même raison que le repli des langues et des secteurs plus bas — ce miroir est écrit à la main,
 * aucune garde ne le confronte au serveur.
 */
export const refinableCountries = (cards: readonly CandidateCard[]): string[] =>
  offered(
    byWeight(
      cards.map((card) =>
        'string' === typeof card.country && '' !== card.country.trim() ? [card.country] : [],
      ),
    ),
  )

/**
 * Les deux champs les plus RÉCENTS de la carte, lus avec leur absence.
 *
 * Le type les donne pour obligatoires, et pourtant : ce miroir est écrit à la main, aucune garde ne
 * le confronte au serveur, et une carte peut arriver sans eux — d'une API qui n'a pas encore le
 * déploiement, ou d'un profil déjà en mémoire depuis avant. Sans ce repli, le calcul des options
 * jette, le panneau entier cesse de rendre, et ses volets s'ouvrent sur du vide.
 *
 * La garde saute le jour où le contrat sera vérifié des deux côtés — pas avant.
 */
const spokenLanguages = (card: CandidateCard): string[] =>
  (card.languages ?? []).map((spoken) => spoken.language)

const sectorsOf = (card: CandidateCard): string[] => card.experienceSectors ?? []

/** Les langues offertes : celles que le jeu porte, rangées. Le niveau ne sert pas ici. */
export const refinableLanguages = (cards: readonly CandidateCard[]): string[] =>
  offered(byWeight(cards.map(spokenLanguages)))

/**
 * Les secteurs offerts : ceux du jeu, rangés.
 *
 * Un candidat en porte plusieurs, et chacun compte — ils entrent donc tous dans la liste, sans quoi
 * un profil qui a bifurqué ne serait offert que sur la moitié de son parcours.
 */
export const refinableSectors = (cards: readonly CandidateCard[]): string[] =>
  offered(byWeight(cards.map(sectorsOf)))

/**
 * Ce que les profils du jeu CHERCHENT — secteur et fonction visés.
 *
 * Une valeur par carte, ou aucune : un candidat n'a qu'un projet à la fois, contrairement à son
 * parcours qui en porte plusieurs. Même forme que le pays, donc, et non que le secteur d'expérience.
 *
 * LA GARDE PORTE SUR LE TYPE, pas sur `null` : ce miroir de la carte est écrit à la main, aucune
 * garde ne le confronte au serveur, et une carte peut arriver sans la clé.
 */
const wanted = (value: string | null | undefined): string[] =>
  'string' === typeof value && '' !== value.trim() ? [value] : []

export const refinableDesiredSectors = (cards: readonly CandidateCard[]): string[] =>
  offered(byWeight(cards.map((card) => wanted(card.desiredSector))))

export const refinableDesiredFunctions = (cards: readonly CandidateCard[]): string[] =>
  offered(byWeight(cards.map((card) => wanted(card.desiredFunction))))

/**
 * La recherche pose-t-elle un critère de métier ?
 *
 * Sans lui, aucun profil ne porte de correspondance de poste, et l'affinage « poste du moment »
 * viderait le jeu sans qu'on puisse comprendre pourquoi. L'écran ne l'offre alors pas.
 *
 * Se lit sur les cartes plutôt que sur les critères de la recherche : c'est la même information, et
 * elle évite au panneau de connaître une seconde source.
 */
export const carriesJobCriterion = (cards: readonly CandidateCard[]): boolean =>
  cards.some((card) => card.matches?.some((match) => 'job' === match.facet) ?? false)
