import { preloadedSearchIds } from '#services/search/collection'
import {
  nextPreloadStep,
  PRELOAD_PAGE_SIZE,
  type PreloadSource,
  type PreloadStep,
} from '#services/search/preload'
import {
  normalizeProfiles,
  normalizeResults,
  normalizeSearch,
  normalizeSearches,
  normalizeWorkspace,
} from '#services/search/normalize'

import { useCandidatesStore } from '../stores/candidates'
import { useReferentialsStore } from '../stores/referentials'
import { useSearchesStore } from '../stores/searches'
import { useUsersStore } from '../stores/users'

import type { BootstrapLoader } from '#shared/types/bootstrap'
import type { AppConfig } from '#shared/types/config'
import type {
  Search,
  SearchCollection,
  SearchProfiles,
  SearchResults,
  SearchWorkspace,
} from '#shared/types/search'

/**
 * Les chargeurs de l'espace entreprise : ils appellent le BFF et hydratent un store, rien de plus.
 *
 * `useRequestFetch` et non `$fetch` brut : au SSR il transmet les en-têtes de la requête entrante
 * (dont le cookie de session), sans quoi le BFF ne trouve pas la session et répond 401 — panne
 * invisible côté client, qui ne casse que le premier rendu.
 */

/**
 * COMBIEN DE FICHES on précharge par recherche, et combien il en faut pour ouvrir.
 *
 * Un seul nombre pour les deux : ce qu'on précharge doit suffire à ouvrir, sinon le préchargement
 * ne dispense de rien.
 */
export const OPENING_CARDS = 30

/**
 * PALIER MÉTADONNÉES : la configuration publique, avant tout le reste.
 *
 * Elle porte ce dont les écrans se servent sans jamais le demander — le coût d'un dévoilement, la
 * taille de page, les nomenclatures fermées. Les écrans les recopiaient en dur faute de canal ; le
 * canal existe maintenant.
 *
 * `isLoaded` la fige après le premier chargement : elle ne dépend d'aucun écran et ne change qu'au
 * rythme d'un déploiement, la relire à chaque navigation serait un appel pour rien.
 */
export const loadConfig = (): BootstrapLoader => {
  const store = useReferentialsStore()
  const request = useRequestFetch()

  return {
    key: '/api/config',
    isLoaded: () => store.getConfig !== null,
    load: async () => {
      store.setConfig(await request<AppConfig>('/api/config'))
    },
  }
}

/**
 * CRITIQUE de l'accueil : la collection des recherches — leurs noms, leurs critères, leurs
 * compteurs.
 *
 * C'est elle qui décide de ce qui s'affiche, elle reste donc devant le rendu. Le plan de travail ne
 * la remplace pas : il porte les jeux, pas de quoi peindre une liste de recherches.
 */
export const loadSearches = (): BootstrapLoader => {
  const searches = useSearchesStore()
  const users = useUsersStore()
  const request = useRequestFetch()

  return {
    key: '/api/searches',
    isLoaded: () => searches.getSearches.length > 0,
    load: async () => {
      const collection = await request<SearchCollection>('/api/searches')
      const normalized = normalizeSearches(collection)

      users.setUsers(normalized.owners)
      searches.setCollection(normalized.searches, collection.total)
    },
  }
}

/**
 * La FICHE d'une recherche, seulement quand la collection ne l'a pas apportée.
 *
 * Sert le lien direct : on ouvre une recherche sans être passé par l'accueil. Et il passe AVANT le
 * jeu — servir le jeu remet à jour le repère de lecture, une fiche lue après lui annoncerait un
 * compte de nouveautés déjà consommé.
 */
export const loadSearch = (searchId: string): BootstrapLoader => {
  const searches = useSearchesStore()
  const users = useUsersStore()
  const request = useRequestFetch()

  return {
    key: `/api/searches/${searchId}`,
    isLoaded: () => null !== searches.getSearchById(searchId),
    load: async () => {
      const fiche = normalizeSearch(await request<Search>(`/api/searches/${searchId}`))

      users.setUsers([fiche.owner])
      searches.setSearch(fiche.search)
    },
  }
}

/**
 * CRITIQUE d'un écran de recherche : SON jeu, en un appel.
 *
 * Le serveur borne ce qu'il sert et annonce cette borne dans sa configuration : la demander telle
 * quelle rend la pagination sans objet. Sans configuration chargée, on le laisse choisir sa taille
 * par défaut plutôt que d'inventer un nombre ici.
 *
 * `isLoaded` est ce qui rend le préchargement utile : un jeu déjà apporté par le plan de travail
 * rend ce chargeur MUET, et ouvrir la recherche ne coûte alors plus rien.
 */
export const loadSearchSet = (searchId: string): BootstrapLoader => {
  const searches = useSearchesStore()
  const candidates = useCandidatesStore()
  const referentials = useReferentialsStore()
  const request = useRequestFetch()

  return {
    key: `/api/searches/${searchId}/results`,
    // LA PRÉSENCE DES FICHES, ET NON CELLE DE LA LISTE. L'état de travail apporte les identifiants
    // de toutes les recherches ouvertes : demander « connais-je la liste ? » répondrait oui sans
    // qu'aucune fiche ne soit là. Observé au navigateur, avec une seule fiche manquante : l'écran
    // affichait 99 cartes en annonçant 100, sans rien redemander.
    isLoaded: () => {
      const ids = searches.getResultIds(searchId)

      return ids.length > 0 && 0 === candidates.getMissingIds(ids.slice(0, OPENING_CARDS)).length
    },
    load: async () => {
      const limit = referentials.getSearchLoadLimit
      const results = normalizeResults(
        await request<SearchResults>(
          `/api/searches/${searchId}/results${limit > 0 ? `?perPage=${limit}` : ''}`,
        ),
      )

      searches.setResults(searchId, results.set)
      candidates.setCandidates(results.candidates)
    },
  }
}

/**
 * Les CARTES des retenus et des écartés — deux appels que le plan de travail ne remplace pas.
 *
 * Il transporte les identifiants des uns et des autres, mais pas leurs fiches : les écartés sont
 * hors du jeu par construction, et un retenu posé au-delà de la borne n'y figure pas davantage.
 * Sans ces deux appels, l'onglet des retenus et la corbeille compteraient juste et montreraient
 * vide.
 *
 * Chargés avec l'écran plutôt qu'à l'ouverture d'un tiroir : le nombre d'écartés serait sinon
 * inconnu tant qu'on n'aurait pas cliqué, et le lien qui le porte, invisible.
 */
export const loadSearchRetained = (searchId: string): BootstrapLoader => {
  const searches = useSearchesStore()
  const candidates = useCandidatesStore()
  const request = useRequestFetch()

  return {
    key: `/api/searches/${searchId}/results?scope=favorites`,
    // Jamais muet : une liste vide est un état légitime, et rien ne distingue « aucun retenu » de
    // « pas encore demandé ». Le coût est celui d'une liste courte.
    isLoaded: () => false,
    load: async () => {
      const payload = await request<SearchResults>(
        `/api/searches/${searchId}/results?scope=favorites`,
      )

      candidates.setCandidates(normalizeResults(payload).candidates)
      searches.setRetainedIds(searchId, payload.resultIds)
    },
  }
}

export const loadSearchDismissed = (searchId: string): BootstrapLoader => {
  const searches = useSearchesStore()
  const candidates = useCandidatesStore()
  const request = useRequestFetch()

  return {
    key: `/api/searches/${searchId}/results?scope=dismissed`,
    isLoaded: () => false,
    load: async () => {
      const payload = await request<SearchResults>(
        `/api/searches/${searchId}/results?scope=dismissed`,
      )

      // Les cartes vont au pool, mais ce `resultIds` ne remplace PAS celui de la recherche : c'est
      // le jeu des écartés, pas le jeu de la recherche.
      candidates.setCandidates(normalizeResults(payload).candidates)
      searches.setDismissedIds(searchId, payload.resultIds)
    },
  }
}

/**
 * CRITIQUE d'un écran de recherche, EN UN SEUL CHARGEUR — parce que l'ordre y est une règle.
 *
 * Un palier exécute ses chargeurs en parallèle : déclarés côte à côte dans le registre, la fiche et
 * le jeu partiraient ensemble, et la fiche pourrait être lue APRÈS que le jeu a remis à jour le
 * repère de lecture — elle annoncerait alors un compte de nouveautés déjà consommé. Les regrouper
 * est le seul moyen de garder la séquence.
 *
 * Chaque étape garde sa propre garde : la fiche ne part que si la collection ne l'a pas apportée, le
 * jeu que si le plan de travail ne l'a pas déjà posé.
 */
export const loadSearchScreen = (searchId: string): BootstrapLoader => {
  const search = loadSearch(searchId)
  const set = loadSearchSet(searchId)
  // Quelles recherches ont déjà été ouvertes — traversé du serveur au client par `useState`, sinon
  // l'hydratation recommencerait tout ce que le rendu serveur vient de faire.
  //
  // Ce drapeau EXISTE parce que deux chemins mènent ici : l'orchestrateur, et l'écran lui-même. Sans
  // lui, une navigation déclenchait chaque appel DEUX FOIS — constaté au navigateur.
  const opened = useState<Record<string, boolean>>('entreprise.search.opened', () => ({}))

  return {
    key: `entreprise:search:${searchId}`,
    isLoaded: () => true === opened.value[searchId],
    load: async () => {
      if (!search.isLoaded()) await search.load()
      if (!set.isLoaded()) await set.load()

      opened.value = { ...opened.value, [searchId]: true }
    },
  }
}

/**
 * TOUT relire, sans aucune garde — ce que demande un geste qui a changé la donnée côté serveur.
 *
 * Dévoiler un profil, ou remettre un écarté : le jeu en store ne décrit plus la vérité, et les
 * gardes le tairaient précisément parce qu'il est déjà là. La fiche fait exception et garde la
 * sienne : ni l'un ni l'autre de ces gestes ne la change.
 */
export const reloadSearchScreen = async (searchId: string): Promise<void> => {
  const search = loadSearch(searchId)

  if (!search.isLoaded()) await search.load()
  await loadSearchSet(searchId).load()
  await Promise.all([loadSearchRetained(searchId).load(), loadSearchDismissed(searchId).load()])
}

/**
 * L'ÉTAT DE TRAVAIL de toutes les recherches ouvertes — des identifiants, et rien d'autre.
 *
 * Une centaine de kilo-octets pour cinquante-cinq recherches : c'est assez peu pour tout prendre, et
 * ça rend n'importe quelle ouverture immédiate, y compris depuis l'écran qui les liste toutes.
 *
 * `isLoaded` ne regarde AUCUNE recherche en particulier : l'état vaut pour toutes à la fois, et se
 * fier à l'une d'elles le tairait dès qu'un écran aurait chargé la sienne.
 */
export const loadWorkspace = (): BootstrapLoader => {
  const searches = useSearchesStore()
  const request = useRequestFetch()
  const loaded = useState<boolean>('entreprise.workspace.loaded', () => false)

  return {
    key: '/api/searches/workspace',
    isLoaded: () => loaded.value,
    load: async () => {
      // Les explications déjà acquises sont préservées : elles ne voyagent plus avec l'état, et les
      // écraser effacerait le « pourquoi ce profil » d'une recherche déjà ouverte.
      const known = Object.fromEntries(
        searches.getSearches.map((search) => [search.id, searches.getSet(search.id).matchesById]),
      )

      const { sets } = normalizeWorkspace(
        await request<SearchWorkspace>('/api/searches/workspace'),
        known,
      )

      for (const [searchId, set] of Object.entries(sets)) {
        searches.setResults(searchId, set)
      }

      loaded.value = true
    },
  }
}

/**
 * LES FICHES des recherches que l'accueil affiche — et de celles-là seulement.
 *
 * L'écran nomme ce qu'il montre : s'il en affichait six demain, l'appel suivrait sans qu'on touche
 * au serveur. Un serveur qui prendrait « les quatre plus récentes » finirait par précharger une
 * recherche que l'écran n'affiche pas.
 *
 * Trente par recherche : quatre recherches donnent une centaine de fiches uniques après
 * dédoublonnage, de quoi ouvrir n'importe laquelle sans attendre. Un budget global réparti entre
 * elles n'en donnerait que sept ou huit chacune, et l'ouverture redemanderait aussitôt.
 */
export const loadDisplayedProfiles = (): BootstrapLoader => {
  const searches = useSearchesStore()
  const candidates = useCandidatesStore()
  const request = useRequestFetch()
  const loaded = useState<boolean>('entreprise.profiles.loaded', () => false)

  return {
    key: '/api/searches/profiles',
    isLoaded: () => loaded.value,
    load: async () => {
      const ids = preloadedSearchIds(searches.getSearches)
      if (0 === ids.length) {
        loaded.value = true

        return
      }

      const payload = await request<SearchProfiles>('/api/searches/profiles', {
        query: { ids: ids.join(','), take: OPENING_CARDS },
      })

      candidates.setCandidates(normalizeProfiles(payload))
      loaded.value = true
    },
  }
}

/**
 * LE FOND : il remplit les recherches, une par une et entièrement.
 *
 * Une recherche ouverte affichait trente profils sur cinq cent vingt-cinq et n'en chargeait pas un
 * de plus. Ce chargeur reprend le fil : il parcourt les recherches dans l'ordre de ce qu'on va
 * regarder, et pour chacune demande les pages qui manquent — la pile de travail d'abord, la
 * corbeille ensuite.
 *
 * **UN SEUL APPEL EN VOL, DU DÉBUT À LA FIN.** Ce n'est pas de la prudence : c'est ce qui garantit
 * qu'un geste du recruteur — dévoiler un profil, ouvrir une recherche — ne passe jamais derrière une
 * file de préchargement.
 *
 * **L'ordre se recalcule à CHAQUE tour** et non une fois pour toutes. Ouvrir une recherche pendant
 * que le fond travaille la fait donc passer devant au pas suivant, sans qu'on ait à défaire une file
 * déjà construite — et c'est ce qui rendra les signaux d'intention gratuits.
 *
 * **Muet, et silencieux jusque dans l'échec.** Les compteurs viennent de l'état et sont déjà justes,
 * la pile s'allonge toute seule. Un appel qui échoue arrête le fond sans message : le recruteur n'a
 * pas demandé ce chargement, lui montrer son échec parlerait d'un rouage. Rouvrir la recherche
 * relance le remplissage.
 */
export const loadPreloadQueue = (): BootstrapLoader => {
  const searches = useSearchesStore()
  const candidates = useCandidatesStore()
  const request = useRequestFetch()
  // Traversé du serveur au client par `useState` : sans lui, l'hydratation relancerait un fond que
  // le rendu serveur vient de mener, et deux files tourneraient de front.
  const done = useState<boolean>('entreprise.preload.done', () => false)
  const running = useState<boolean>('entreprise.preload.running', () => false)

  /** Ce que le planificateur lit : des identifiants et une date, jamais une fiche. */
  const sources = (): PreloadSource[] =>
    searches.getSearches.map((search) => {
      const set = searches.getSet(search.id)

      return {
        id: search.id,
        updatedAt: search.updatedAt,
        closedAt: search.closedAt,
        resultIds: set.resultIds,
        dismissedIds: set.dismissedIds,
        matchesById: set.matchesById,
      }
    })

  // Ce que le recruteur a VISÉ — survolée, focalisée, ou entrée à l'écran au doigt.
  const intended = useState<string[]>('entreprise.preload.intended', () => [])

  /**
   * QUI PASSE DEVANT, après la recherche ouverte.
   *
   * Les recherches DÉSIGNÉES d'abord : quelqu'un vient de les viser, et c'est le signal le plus
   * frais qu'on ait de ce qui va s'ouvrir. Les recherches affichées ensuite, parce que ce sont les
   * prochaines qu'on peut atteindre d'un clic.
   *
   * La recherche ouverte, elle, se nomme à part — elle mène la file ET échappe au plafond, ce qu'un
   * simple rang ne dirait pas.
   */
  const displayed = (): string[] => [...intended.value, ...preloadedSearchIds(searches.getSearches)]

  const fetchStep = async (step: PreloadStep): Promise<void> => {
    const scope = 'dismissed' === step.scope ? '&scope=dismissed' : ''
    const results = normalizeResults(
      await request<SearchResults>(
        `/api/searches/${step.searchId}/results?page=${step.page}&perPage=${PRELOAD_PAGE_SIZE}${scope}`,
      ),
    )

    candidates.setCandidates(results.candidates)
    // Au-delà de la première page, la réponse ne porte plus les listes d'état — et c'est voulu :
    // on ne reprend donc que les explications, et le jeu reste celui de l'état de travail.
    searches.mergeMatches(step.searchId, results.set.matchesById)
  }

  return {
    key: 'entreprise:preload:queue',
    // Vrai pendant qu'il tourne, et pas seulement une fois fini : le palier d'arrière-plan est
    // rejoué à CHAQUE navigation, et sans cette garde une seconde file démarrerait par-dessus.
    isLoaded: () => done.value || running.value,
    load: async () => {
      running.value = true
      // CE QUI EMPÊCHE LA BOUCLE INFINIE, et il en faut une.
      //
      // Une page peut revenir sans tout ce qu'on en attendait : un profil supprimé entre l'état et
      // l'appel, ou un profil qu'aucun critère ne sait expliquer — le serveur n'a alors rien à dire
      // de lui, légitimement. Le planificateur redemanderait cette page sans fin, puisqu'elle reste
      // incomplète. Une page tentée ne se retente donc jamais dans la même session de fond.
      const attempted = new Set<string>()
      const mark = (step: PreloadStep): string => `${step.searchId}:${step.scope}:${step.page}`
      const missing = (id: string): boolean => undefined === candidates.getPool[id]

      try {
        for (;;) {
          const step = nextPreloadStep(sources(), missing, {
            current: searches.getCurrentId,
            priority: displayed(),
            skip: (candidate) => attempted.has(mark(candidate)),
          })
          if (null === step) break

          attempted.add(mark(step))
          await fetchStep(step)
        }

        done.value = true
      } catch {
        // Silence délibéré : l'écran reste utilisable avec ce qui est arrivé, et les compteurs
        // restent justes. `done` n'est pas posé — rouvrir une recherche relancera le fond.
      } finally {
        running.value = false
      }
    },
  }
}

/**
 * ARRIÈRE-PLAN DE L'ACCUEIL : l'état, PUIS les fiches — en un seul chargeur, parce que l'ordre est
 * une règle.
 *
 * Un palier exécute ses chargeurs en parallèle. Déclarés côte à côte, les deux partiraient ensemble
 * et les fiches pourraient arriver avant qu'on sache à quelle recherche elles appartiennent — or ce
 * sont les listes de l'état qui nomment les recherches à précharger.
 *
 * Jamais bloquant : l'accueil s'affiche d'abord, ses compteurs venant de la collection et non d'ici.
 */
export const loadHomeBackground = (): BootstrapLoader => {
  const state = loadWorkspace()
  const profiles = loadDisplayedProfiles()
  const queue = loadPreloadQueue()

  return {
    key: 'entreprise:home:background',
    isLoaded: () => state.isLoaded() && profiles.isLoaded() && queue.isLoaded(),
    load: async () => {
      if (!state.isLoaded()) await state.load()
      // Les trente premières de chaque carte AVANT le fond : elles rendent les six recherches de
      // l'accueil ouvrables en dix secondes, là où la file entière met une minute.
      if (!profiles.isLoaded()) await profiles.load()
      if (!queue.isLoaded()) await queue.load()
    },
  }
}

/**
 * ARRIÈRE-PLAN D'UN ÉCRAN DE RECHERCHE : l'état, puis le fond.
 *
 * Même séquence que l'accueil, sans l'étape des fiches affichées — l'écran a déjà chargé sa propre
 * première page de façon bloquante, et le fond enchaîne sur le reste en la mettant en tête.
 */
export const loadSearchBackground = (): BootstrapLoader => {
  const state = loadWorkspace()
  const queue = loadPreloadQueue()

  return {
    key: 'entreprise:search:background',
    isLoaded: () => state.isLoaded() && queue.isLoaded(),
    load: async () => {
      if (!state.isLoaded()) await state.load()
      if (!queue.isLoaded()) await queue.load()
    },
  }
}
