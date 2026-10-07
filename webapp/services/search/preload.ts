import type { CriterionMatch } from '../../shared/types/candidate-card'

/**
 * CE QU'IL RESTE À CHARGER, et dans quel ordre.
 *
 * Couche PURE : elle décide, elle n'appelle rien. Le moteur qui exécute vit dans les chargeurs de
 * l'espace, avec ses stores et son réseau ; ici on ne manipule que des listes d'identifiants.
 *
 * Le fond remplit les recherches **une par une et entièrement** : le recruteur qui en ouvre une va
 * la parcourir jusqu'au bout, et un chargement qui court derrière lui reste un chargement qu'on
 * attend.
 */

/** Ce qu'un appel rapporte. Cinq fois moins d'allers-retours qu'avec trente, sans réponse énorme. */
export const PRELOAD_PAGE_SIZE = 100

/**
 * DEUX BORNES, ET IL EN FAUT DEUX.
 *
 * Un budget en recherches seul ne borne rien — vingt recherches pèsent de trois cents kilo-octets à
 * huit mégaoctets selon leur taille. Un budget en profils seul serait avalé en entier par une seule
 * recherche énorme, et les dix-neuf suivantes n'auraient rien.
 */
export const MAX_PRELOADED_SEARCHES = 20
export const MAX_PRELOADED_PROFILES = 1500

/** Une recherche, vue par le planificateur : des identifiants et une date, rien d'autre. */
export interface PreloadSource {
  id: string
  updatedAt: string
  /** Absent sur une recherche active : l'API ne sérialise pas les valeurs nulles. */
  closedAt?: string | null
  /** Le jeu ENTIER, écartés compris — c'est ce que sert l'état de travail. */
  resultIds: readonly string[]
  dismissedIds: readonly string[]
  /** Ce que la recherche sait déjà expliquer : une page non expliquée reste à charger. */
  matchesById: Readonly<Record<string, readonly CriterionMatch[]>>
}

/** Le prochain appel à passer : une recherche, un périmètre, une page. */
export interface PreloadStep {
  searchId: string
  /** La pile de travail, ou la corbeille — deux périmètres, deux paginations. */
  scope: 'pile' | 'dismissed'
  /** Numéro de page, à partir de 1, tel que l'API l'attend. */
  page: number
}

/**
 * L'ordre du fond : ce qu'on va regarder d'abord.
 *
 * Les recherches DÉSIGNÉES passent devant, dans l'ordre où on les désigne — la recherche ouverte
 * d'abord, puis celles que l'écran affiche. Le reste suit par date de dernière activité, la plus
 * fraîche en tête : c'est celle qu'on rouvre le plus souvent.
 *
 * Les recherches closes n'entrent jamais : elles ne se travaillent plus.
 */
export const preloadOrder = (
  sources: readonly PreloadSource[],
  priority: readonly string[] = [],
): string[] => {
  const open = sources.filter((source) => !source.closedAt)
  const known = new Set(open.map((source) => source.id))

  const first = priority.filter((id) => known.has(id))
  const seen = new Set(first)

  const rest = open
    .filter((source) => !seen.has(source.id))
    .sort((left, right) => right.updatedAt.localeCompare(left.updatedAt))
    .map((source) => source.id)

  return [...new Set([...first, ...rest])]
}

/**
 * OÙ LE FOND S'ARRÊTE — au premier des deux plafonds atteint.
 *
 * **Le plafond borne un POIDS, il n'est pas un rang dans la file.** Une recherche qui ne tient pas
 * dans ce qui reste est donc SAUTÉE, et l'examen continue : la suivante, plus petite, y tiendra
 * peut-être. S'arrêter net sur elle laisserait le budget inemployé et priverait de préchargement des
 * recherches qui n'y étaient pour rien.
 *
 * Mesuré sur un compte réel : la prendre en entier parce qu'elle « franchit » le seuil faisait
 * charger 4 172 profils là où le plafond en annonçait 1 500 — un plafond qui ne plafonne pas.
 *
 * **La recherche OUVERTE échappe aux deux bornes** — et elle seule. La laisser à moitié chargée
 * serait exactement l'état que ce chargement existe pour supprimer. Ce n'est pas le premier rang de
 * la file qui l'obtient : sur l'accueil, aucune recherche n'est ouverte, et la plus récemment
 * touchée n'a aucune raison d'échapper au plafond.
 */
export const withinBudget = (
  order: readonly string[],
  sizeOf: (searchId: string) => number,
  options: { maxSearches?: number; maxProfiles?: number; always?: string | null } = {},
): string[] => {
  const {
    maxSearches = MAX_PRELOADED_SEARCHES,
    maxProfiles = MAX_PRELOADED_PROFILES,
    always = null,
  } = options
  const exempt = null !== always && order.includes(always)
  const taken = exempt ? [always] : []
  let spent = exempt ? sizeOf(always) : 0

  for (const id of order.filter((candidate) => candidate !== always)) {
    if (taken.length >= maxSearches) break

    const size = sizeOf(id)
    if (spent + size > maxProfiles) continue

    taken.push(id)
    spent += size
  }

  return taken
}

/**
 * Les pages d'une liste dont il manque au moins une fiche.
 *
 * On raisonne en PAGES et non en identifiants parce que c'est ce que l'API sert : une page dont un
 * seul profil manque se redemande entière. Les pages complètes sont sautées sans appel — c'est ce
 * qui rend le fond idempotent, et ce qui fait qu'une recherche partagée avec une autre déjà chargée
 * ne coûte presque rien.
 */
export const missingPages = (
  ids: readonly string[],
  isMissing: (id: string) => boolean,
  perPage: number = PRELOAD_PAGE_SIZE,
): number[] => {
  const pages: number[] = []

  for (let start = 0; start < ids.length; start += perPage) {
    if (ids.slice(start, start + perPage).some(isMissing)) {
      pages.push(1 + start / perPage)
    }
  }

  return pages
}

/**
 * La pile de travail d'une recherche : son jeu, écartés retirés.
 *
 * Recalculée ici et non demandée au serveur : c'est exactement ce que sa pagination sert, et les
 * deux listes sont déjà en mémoire. Sans cette soustraction, les numéros de page seraient décalés
 * dès qu'un profil est écarté — on redemanderait des pages déjà là en en sautant d'autres.
 */
export const pileOf = (source: PreloadSource): string[] => {
  const dismissed = new Set(source.dismissedIds)

  return source.resultIds.filter((id) => !dismissed.has(id))
}

/**
 * Ce qu'il reste à charger pour UNE recherche, la pile avant la corbeille.
 *
 * Une fiche peut être là sans son explication — l'accueil précharge des fiches sans recherche, et
 * elles arrivent nues. Une page qui manque de l'une ou de l'autre se redemande : les deux voyagent
 * ensemble, et il n'y a pas d'appel pour n'aller chercher que la seconde.
 */
export const stepsFor = (
  source: PreloadSource,
  isCardMissing: (id: string) => boolean,
  perPage: number = PRELOAD_PAGE_SIZE,
): PreloadStep[] => {
  const incomplete = (id: string): boolean => isCardMissing(id) || !(id in source.matchesById)

  return [
    ...missingPages(pileOf(source), incomplete, perPage).map((page): PreloadStep => ({
      searchId: source.id,
      scope: 'pile',
      page,
    })),
    // Les écartés sont hors pile par construction : c'est leur propre pagination, et elle vient
    // après — on remplit d'abord ce que le recruteur regarde, jamais ce qu'il a jeté.
    ...missingPages(source.dismissedIds, isCardMissing, perPage).map((page): PreloadStep => ({
      searchId: source.id,
      scope: 'dismissed',
      page,
    })),
  ]
}

/**
 * UNE INTENTION DÉSIGNE LA PROCHAINE — survol, focus, ou entrée à l'écran au doigt.
 *
 * La désignée passe en tête, les précédentes gardent leur rang derrière elle : ce sont toutes des
 * recherches que quelqu'un a visées, et elles valent mieux que l'ordre par défaut.
 *
 * **Une intention répétée n'empile pas.** Sans dédoublonnage, survoler la même carte trois fois lui
 * donnerait trois rangs, et la file grossirait à chaque passage de souris sans jamais changer d'avis.
 *
 * Rien ne se charge ici : cette liste ne fait que **réordonner** une file déjà en marche. C'est ce
 * qui préserve la règle du seul appel en vol — le palier en cours finit, la désignée passe ensuite.
 */
export const withIntent = (intended: readonly string[], searchId: string): string[] => [
  searchId,
  ...intended.filter((id) => id !== searchId),
]

/**
 * LE PROCHAIN APPEL, et un seul.
 *
 * Rendre un pas à la fois plutôt qu'un plan complet n'est pas un détail : l'ordre se recalcule à
 * chaque tour, donc ouvrir une recherche ou en survoler une la fait passer devant **sans qu'on ait à
 * défaire une file déjà construite**. Un plan figé aurait continué à remplir ce que le recruteur
 * venait de quitter.
 *
 * `skip` écarte ce qui a déjà été tenté, et il n'est pas facultatif dans les faits : une page peut
 * revenir sans tout ce qu'on en attendait — un profil supprimé entre l'état et l'appel, ou un profil
 * qu'aucun critère ne sait expliquer. Elle resterait incomplète, donc éligible, et le fond la
 * redemanderait sans fin. Écarter le pas plutôt que rompre la boucle laisse les recherches suivantes
 * se charger.
 *
 * `null` dit que le fond n'a plus rien à faire — tout est chargé, ou le budget est épuisé.
 */
export const nextPreloadStep = (
  sources: readonly PreloadSource[],
  isCardMissing: (id: string) => boolean,
  options: {
    /** La recherche ouverte : elle mène la file ET échappe au plafond. `null` sur l'accueil. */
    current?: string | null
    priority?: readonly string[]
    skip?: (step: PreloadStep) => boolean
    perPage?: number
  } = {},
): PreloadStep | null => {
  const { current = null, priority = [], skip = () => false, perPage = PRELOAD_PAGE_SIZE } = options
  const byId = new Map(sources.map((source) => [source.id, source]))
  const order = withinBudget(
    preloadOrder(sources, [...(current ? [current] : []), ...priority]),
    (id) => byId.get(id)?.resultIds.length ?? 0,
    { always: current },
  )

  for (const id of order) {
    const source = byId.get(id)
    if (!source) continue

    const step = stepsFor(source, isCardMissing, perPage).find((candidate) => !skip(candidate))

    if (step) return step
  }

  return null
}
