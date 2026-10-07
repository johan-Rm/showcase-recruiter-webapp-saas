/**
 * Carte candidat en recherche (GET /v1/search/candidates) — contrat UNIQUE de la Table et de la
 * Visionneuse : une requête peint une page, aucun appel par ligne. L'enveloppe générée
 * (`CandidateSearch.CollectionOutput`) type ses `items` en opaque ; on rétablit ici la forme réelle,
 * en miroir de `CandidateCardOutput` côté API.
 *
 * MASQUÉ tant que le profil n'est pas dévoilé, et c'est le SERVEUR qui masque : `familyName` et les
 * coordonnées arrivent à `null`, jamais floutés côté client. Le flou est une convention d'affichage
 * posée sur un placeholder — la donnée masquée ne descend pas dans le navigateur.
 *
 * La PHOTO échappe à ce régime : elle est servie masqué comme dévoilé. Le masquage porte sur les
 * moyens de contact, pas sur l'anonymat.
 *
 * ⚠️ Ce type est écrit à la MAIN et il le restera : `CandidateCardOutput` n'apparaît nulle part dans
 * l'OpenAPI — la carte n'y existe que nichée dans des collections dont les `items` sont typés
 * `(string | null)[]`. La gate de contrat ne voit donc pas ce fichier : toute évolution de la carte
 * côté API doit être reportée ici à la main, sans quoi rien ne le signalera.
 *
 * ⚠️ ET IL FAUT SUIVRE LES CONSOMMATEURS. Tenir ce miroir à jour ne suffit pas : ajouter un champ
 * OBLIGATOIRE casse tout ce qui construit une carte en littéral — les fixtures de test en premier.
 * Mesuré : les 376 tests de l'API peuvent être verts pendant que le front part rouge.
 */

/**
 * Un critère de la recherche, vu depuis UN profil : satisfait ou non, et par quoi.
 *
 * `facet` et `value` reprennent le critère tel qu'il est stocké — donc tel que la barre haute
 * l'affiche en puce, ce qui permet de rapprocher les deux sans rien recalculer.
 *
 * `matchedOn` porte le libellé DU PROFIL, jamais celui qui a été cherché : « développeur » retrouve
 * un profil affiché « Frontend developer », et la chaîne cherchée n'est nulle part dans la fiche.
 * C'est ce qui permet d'énoncer la correspondance là où il n'y a rien à surligner.
 *
 * ⚠️ `matchedOn` est OPTIONNEL et pas seulement nullable : le sérialiseur OMET la clé quand le
 * critère n'est pas satisfait, il ne l'envoie pas à `null`. Une garde `null !== matchedOn` laisse
 * donc passer l'absence, et l'écran affiche une valeur vide au lieu de son verdict — constaté à
 * l'écran sur un mot-clé non trouvé.
 *
 * `matchedWhen` se lit en paire avec lui : l'un dit **par quoi** le critère est satisfait, l'autre
 * **quand**. Seul le métier en porte un — une langue ou un pays n'ont pas de « quand ».
 *
 * ⚠️ **Son absence ne veut PAS dire « passé »**, elle veut dire qu'on ne sait pas. Le poste du
 * moment n'est résolu que sur la moitié du vivier et il est parfois ambigu ; dans ces cas le serveur
 * se tait plutôt que de deviner. Traiter l'absence comme un passé rangerait ces profils-là avec ceux
 * qui ont réellement changé de métier.
 */
export interface CriterionMatch {
  facet: string
  value: string
  matched: boolean
  matchedOn?: string | null
  /** `CURRENT` : c'est son poste du moment · `PAST` : il en occupe un autre aujourd'hui. */
  matchedWhen?: 'CURRENT' | 'PAST' | null
  /**
   * OÙ, dans le profil, un mot-clé a été vu — absent sur toute autre facette, qui n'a qu'un seul
   * endroit possible.
   *
   * Le serveur s'arrête au PREMIER endroit qui répond, dans l'ordre où l'on lit une fiche :
   * accroche, compétences, postes écrits. Un terme présent à deux endroits n'en annonce donc qu'un,
   * et **rien de ce qui s'affiche ne doit laisser croire à une exclusivité**.
   *
   * Trois valeurs et pas une de plus : ce sont des champs déjà servis au recruteur, dévoilé ou non,
   * ce qui rend leur affichage sûr sans règle de masquage. L'employeur, lui, ré-identifie et attend
   * le dévoilement — il n'entre pas ici.
   */
  matchedIn?: 'HEADLINE' | 'SKILL' | 'JOB_TITLE' | null
}

export interface CandidateLanguage {
  language: string
  /** `null` = non renseigné, jamais « débutant ». */
  proficiency: string | null
}

export interface CandidatePresencePeriod {
  startDate: string
  endDate: string
  city: string | null
}

export interface CandidateCard {
  id: string
  /** Réduit à l'initiale tant que le profil est masqué. */
  givenName: string
  familyName: string | null
  photoUrl: string | null
  /**
   * L'ACCROCHE que le candidat s'écrit sur son réseau — pas un intitulé de poste. 49 caractères en
   * médiane sur le vivier, 128 au 90ᵉ centile, 752 au maximum : elle porte souvent trois phrases de
   * démarchage, et le métier n'y arrive qu'en quatrième position quand elle le nomme.
   *
   * Ne pas la titrer : c'est `currentTitle` qui dit le poste, avec l'accroche en repli.
   */
  headline: string | null
  /**
   * Le POSTE ACTUEL, dérivé du parcours côté serveur — 25 caractères en médiane.
   * Servi même sur un profil masqué : un intitulé qualifie sans nommer, c'est l'employeur qui
   * ré-identifie et il n'est pas sur la carte.
   *
   * ⚠️ OPTIONNEL et pas seulement nullable : le sérialiseur de la carte OMET la clé quand aucun
   * poste n'est dérivable — 27 % du vivier. Une garde `null !== currentTitle` laisserait donc
   * passer l'absence, comme sur `matchedOn`. Lire par `roleOf()`, qui porte le repli.
   */
  currentTitle?: string | null
  /** Agrégat dérivé du parcours : visible même masqué, il ne désigne personne. */
  experienceYears: number
  /** Sur la carte parce qu'on FILTRE dessus : un critère invisible sur le résultat oblige à ouvrir chaque fiche. */
  educationLevel: string | null
  city: string | null
  country: string | null
  contactEmail: string | null
  phone: string | null
  /**
   * Joignabilité : la PRÉSENCE d'une coordonnée, exposée même masqué — jamais sa valeur. C'est ce
   * qui évite de dépenser un crédit sur un profil injoignable.
   */
  hasEmail: boolean
  hasPhone: boolean
  /**
   * Même chose pour les deux canaux payants restants : le CV compte s'il porte un fichier, le compte
   * rendu s'il a un texte — « rencontré » ne suffit pas, un entretien peut n'avoir jamais été rédigé.
   *
   * **Optionnels, et c'est la transition qui l'impose** : le serveur qui les sert n'est pas encore
   * déployé partout. Absent ne veut pas dire « rien à servir », ça veut dire « on ne sait pas » — et
   * un canal ne s'éteint que sur une réponse explicite. À rendre obligatoires quand la version qui
   * les envoie est partout.
   */
  hasResume?: boolean
  hasReport?: boolean
  /** Dernier échange AVEC le candidat : dit qu'un confrère est déjà passé, jamais par quel canal joindre. */
  lastInteractionAt: string | null
  lastInteractionType: string | null
  desiredSector: string | null
  desiredFunction: string | null
  availability: string | null
  returnHorizon: string | null
  /**
   * Le projet de retour dans les mots du candidat : ce qui sépare « il cherche un poste » de « il
   * veut rentrer », et donc l'élément le plus décisif quand on trie des profils un par un.
   */
  returnProject: string | null
  geoMobility: boolean
  hasPiedATerre: boolean
  piedATerreCity: string | null
  /** Fenêtres de présence au Maroc ★ — visibles même masqué, c'est le cœur de la promesse. */
  presencePeriods: CandidatePresencePeriod[]
  /**
   * Présent au Maroc AUJOURD'HUI — le verdict du serveur, pas un calcul du navigateur : il dépend
   * de « quand », et l'horloge qui compte est celle du serveur.
   *
   * Ne pas confondre avec le critère de recherche « présent sous N jours » : un candidat qui arrive
   * dans cinq jours entre dans une telle recherche sans porter ce drapeau.
   */
  isInMorocco: boolean
  skills: string[]
  /**
   * Les langues parlées, avec leur niveau.
   *
   * ⚠️ `proficiency` est `null` sur une part du vivier, et l'absence ne dit PAS « débutant » : la
   * langue maternelle arrive souvent sans niveau depuis le réseau d'origine. Traiter ce `null`
   * comme un plancher classerait ces profils au plus bas de ce qu'ils maîtrisent le mieux.
   */
  languages: CandidateLanguage[]
  /**
   * TOUS les secteurs exercés, du plus ancien au moins ancien — pas le seul principal.
   *
   * Un candidat passé par la santé puis la banque répond aux deux : c'est chez ceux qui ont
   * bifurqué que la question se pose, et n'en servir qu'un les rendrait invisibles sur la moitié
   * de leur parcours.
   */
  experienceSectors: string[]
  /** Vérifié en entretien par showcase-recruiter-webapp-saas. */
  isStar: boolean
  /**
   * Ce que l'entreprise a PAYÉ sur ce profil — quatre accès indépendants, un crédit chacun.
   *
   * Chaque canal commande le SIEN et rien d'autre : payer l'e-mail n'ouvre pas le téléphone.
   * `revealedResume` ouvre le CV et `revealedReport` le compte rendu, tous deux servis sur la fiche.
   * L'identité, elle, ne se paie plus.
   */
  revealedEmail: boolean
  revealedPhone: boolean
  revealedResume: boolean
  revealedReport: boolean
  createdAt: string
  updatedAt: string
  /**
   * État de travail du candidat DANS une recherche — `null` hors d'une recherche, où la question ne
   * se pose pas. `isNew` est propre au recruteur courant (son repère de lecture), `isFavorite` à la recherche : deux membres voient les mêmes favoris, pas les mêmes nouveautés.
   */
  isNew: boolean | null
  isFavorite: boolean | null
  /**
   * Où en est LE RECRUTEUR COURANT sur ce profil — `null` hors d'une recherche.
   *
   * Personnels tous les deux, contrairement au favori : mon avancement n'est pas celui de mon
   * collègue, et écarter est un jugement relatif à un besoin. Un profil écarté ne figure plus dans
   * le jeu servi par défaut ; il se retrouve avec `?scope=dismissed`.
   */
  isSeen: boolean | null
  isDismissed: boolean | null
  /**
   * Pourquoi ce profil est dans le jeu — `null` hors d'une recherche, où la question n'a pas d'objet.
   *
   * Porte le surlignage des mots-clés trouvés, l'énoncé de la correspondance de poste, et la liste
   * de ce qui MANQUE : le seul moyen de trancher quand un profil entre en ne satisfaisant qu'un
   * critère sur quatre.
   */
  matches: CriterionMatch[] | null
}

export interface CandidateCardCollection {
  items: CandidateCard[]
  total: number
  page: number
  perPage: number
}
