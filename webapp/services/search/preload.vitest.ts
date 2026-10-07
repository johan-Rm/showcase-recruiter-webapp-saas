import { describe, expect, it } from 'vitest'

import {
  MAX_PRELOADED_PROFILES,
  MAX_PRELOADED_SEARCHES,
  missingPages,
  nextPreloadStep,
  pileOf,
  preloadOrder,
  stepsFor,
  withinBudget,
  withIntent,
  type PreloadSource,
} from './preload'

/**
 * Ce qu'il reste à charger, et dans quel ordre.
 *
 * Trois règles se cassent en silence, et ce sont elles que ces tests tiennent.
 *
 * **Le plafond borne un POIDS, pas un rang.** Une première version prenait en entier toute recherche
 * franchissant le seuil : mesuré sur un compte réel, 4 172 profils chargés là où le plafond en
 * annonçait 1 500.
 *
 * **La pagination porte sur la PILE, pas sur le jeu.** Un profil écarté décale toutes les pages
 * suivantes — on redemanderait des pages déjà là en en sautant d'autres.
 *
 * **Une page incomplète doit pouvoir être abandonnée.** Sans quoi un profil supprimé, ou qu'aucun
 * critère ne sait expliquer, fait redemander la même page sans fin.
 */

const source = (id: string, overrides: Partial<PreloadSource> = {}): PreloadSource => ({
  id,
  updatedAt: '2026-01-01T00:00:00+00:00',
  resultIds: [],
  dismissedIds: [],
  matchesById: {},
  ...overrides,
})

/** Des identifiants en série, pour composer des jeux d'une taille donnée. */
const ids = (count: number, prefix = 'p'): string[] =>
  Array.from({ length: count }, (_, index) => `${prefix}${index}`)

describe('preloadOrder', () => {
  it('met les recherches désignées devant, dans l’ordre où on les désigne', () => {
    const sources = [source('a'), source('b'), source('c')]

    expect(preloadOrder(sources, ['c', 'a'])).toEqual(['c', 'a', 'b'])
  })

  it('range le reste par date d’activité, la plus fraîche d’abord', () => {
    const sources = [
      source('ancienne', { updatedAt: '2026-01-01T00:00:00+00:00' }),
      source('fraîche', { updatedAt: '2026-09-01T00:00:00+00:00' }),
      source('moyenne', { updatedAt: '2026-05-01T00:00:00+00:00' }),
    ]

    expect(preloadOrder(sources)).toEqual(['fraîche', 'moyenne', 'ancienne'])
  })

  /** Une recherche close ne se travaille plus : la précharger serait du poids pour personne. */
  it('n’inclut jamais une recherche close, même désignée', () => {
    const sources = [source('vivante'), source('close', { closedAt: '2026-03-01T00:00:00+00:00' })]

    expect(preloadOrder(sources, ['close'])).toEqual(['vivante'])
  })

  it('ignore une désignation qui ne correspond à rien', () => {
    expect(preloadOrder([source('a')], ['fantôme'])).toEqual(['a'])
  })

  it('ne répète pas une recherche désignée deux fois', () => {
    expect(preloadOrder([source('a'), source('b')], ['a', 'a'])).toEqual(['a', 'b'])
  })
})

describe('withinBudget', () => {
  const sizes: Record<string, number> = { petite: 100, moyenne: 600, énorme: 3239 }
  const sizeOf = (id: string): number => sizes[id] ?? 0

  /**
   * LE PLAFOND BORNE UN POIDS, ET C'EST TOUT SON INTÉRÊT.
   *
   * Prendre en entier la recherche qui franchit le seuil faisait charger 4 172 profils pour un
   * plafond de 1 500 — mesuré au navigateur. Elle est donc sautée, et l'examen continue : les
   * suivantes, plus petites, y tiennent.
   */
  it('saute une recherche qui ne tient pas, et continue avec les suivantes', () => {
    expect(withinBudget(['énorme', 'moyenne', 'petite'], sizeOf)).toEqual(['moyenne', 'petite'])
  })

  it('s’arrête au plafond de profils', () => {
    const sizeOfHundred = (): number => 100

    expect(withinBudget(ids(30, 's'), sizeOfHundred, { maxProfiles: 250 })).toHaveLength(2)
  })

  it('s’arrête au plafond de recherches', () => {
    const sizeOfOne = (): number => 1

    expect(withinBudget(ids(50, 's'), sizeOfOne, { maxSearches: 3 })).toHaveLength(3)
  })

  /**
   * LA RECHERCHE OUVERTE ÉCHAPPE AUX DEUX BORNES, et elle seule : la laisser à moitié chargée serait
   * l'état exact que ce chargement existe pour supprimer.
   */
  it('prend la recherche ouverte quelle que soit sa taille', () => {
    expect(withinBudget(['énorme', 'petite'], sizeOf, { always: 'énorme' })).toContain('énorme')
  })

  /** Sur l'accueil, aucune recherche n'est ouverte : la première de la file n'a aucun privilège. */
  it('n’exempte personne quand aucune recherche n’est ouverte', () => {
    expect(withinBudget(['énorme', 'petite'], sizeOf, { always: null })).toEqual(['petite'])
  })

  it('ne compte pas deux fois la recherche ouverte', () => {
    const taken = withinBudget(['moyenne', 'petite'], sizeOf, { always: 'moyenne' })

    expect(taken.filter((id) => 'moyenne' === id)).toHaveLength(1)
  })

  it('rend une liste vide sur une file vide', () => {
    expect(withinBudget([], sizeOf)).toEqual([])
  })

  it('garde des plafonds par défaut cohérents avec ce que le fond annonce', () => {
    expect(MAX_PRELOADED_SEARCHES).toBe(20)
    expect(MAX_PRELOADED_PROFILES).toBe(1500)
  })
})

describe('missingPages', () => {
  it('ne rend que les pages où il manque quelque chose', () => {
    const liste = ids(10)
    const absent = (id: string): boolean => 'p7' === id

    expect(missingPages(liste, absent, 3)).toEqual([3])
  })

  it('saute sans appel une liste entièrement chargée', () => {
    expect(missingPages(ids(10), () => false, 3)).toEqual([])
  })

  /** Une page dont un SEUL profil manque se redemande entière : c'est ce que l'API sert. */
  it('redemande la page entière pour un seul profil manquant', () => {
    expect(missingPages(ids(200), (id) => 'p150' === id, 100)).toEqual([2])
  })

  it('numérote à partir de 1, comme l’API l’attend', () => {
    expect(missingPages(ids(5), () => true, 2)).toEqual([1, 2, 3])
  })

  it('rend une liste vide sur un jeu vide', () => {
    expect(missingPages([], () => true, 100)).toEqual([])
  })
})

describe('pileOf', () => {
  /**
   * LA PAGINATION PORTE SUR LA PILE, PAS SUR LE JEU. Sans cette soustraction, les numéros de page
   * seraient décalés dès qu'un profil est écarté : on redemanderait des pages déjà là en en sautant
   * d'autres.
   */
  it('retire les écartés du jeu, dans l’ordre du serveur', () => {
    const set = source('s', { resultIds: ['a', 'b', 'c', 'd'], dismissedIds: ['b', 'd'] })

    expect(pileOf(set)).toEqual(['a', 'c'])
  })

  it('rend le jeu entier quand rien n’est écarté', () => {
    expect(pileOf(source('s', { resultIds: ['a', 'b'] }))).toEqual(['a', 'b'])
  })
})

describe('stepsFor', () => {
  it('demande la pile avant la corbeille', () => {
    const set = source('s', { resultIds: ids(4), dismissedIds: ['x'] })

    expect(stepsFor(set, () => true, 2).map((step) => `${step.scope}${step.page}`)).toEqual([
      'pile1',
      'pile2',
      'dismissed1',
    ])
  })

  /**
   * Une fiche peut être là SANS son explication : l'accueil précharge des fiches hors recherche, et
   * elles arrivent nues. Les deux voyagent ensemble, il n'existe pas d'appel pour la seconde seule.
   */
  it('redemande une page dont les fiches sont là mais pas les explications', () => {
    const set = source('s', { resultIds: ['a', 'b'], matchesById: { a: [] } })

    expect(stepsFor(set, () => false, 100)).toEqual([{ searchId: 's', scope: 'pile', page: 1 }])
  })

  it('ne demande rien quand tout est chargé et expliqué', () => {
    const set = source('s', { resultIds: ['a'], matchesById: { a: [] } })

    expect(stepsFor(set, () => false, 100)).toEqual([])
  })

  it('ne demande rien d’une recherche au jeu vide', () => {
    expect(stepsFor(source('s'), () => true, 100)).toEqual([])
  })
})

describe('nextPreloadStep', () => {
  const sources = [
    source('a', { resultIds: ids(2, 'a'), updatedAt: '2026-09-01T00:00:00+00:00' }),
    source('b', { resultIds: ids(2, 'b'), updatedAt: '2026-01-01T00:00:00+00:00' }),
  ]

  it('rend un seul pas, celui de la recherche en tête', () => {
    expect(nextPreloadStep(sources, () => true)).toEqual({ searchId: 'a', scope: 'pile', page: 1 })
  })

  /**
   * L'ordre se recalcule à CHAQUE tour : ouvrir une recherche la fait passer devant au pas suivant,
   * sans qu'on ait à défaire une file déjà construite.
   */
  it('suit la recherche ouverte dès le pas suivant', () => {
    expect(nextPreloadStep(sources, () => true, { current: 'b' })?.searchId).toBe('b')
  })

  /**
   * CE QUI EMPÊCHE LA BOUCLE INFINIE. Une page peut revenir sans tout ce qu'on en attendait — un
   * profil supprimé, ou qu'aucun critère ne sait expliquer. Elle reste éligible ; sans `skip`, le
   * fond la redemanderait sans fin. L'écarter plutôt que rompre laisse les suivantes se charger.
   */
  it('passe au suivant quand un pas a déjà été tenté', () => {
    const tenté = { searchId: 'a', scope: 'pile' as const, page: 1 }
    const step = nextPreloadStep(sources, () => true, {
      skip: (candidate) => candidate.searchId === tenté.searchId,
    })

    expect(step?.searchId).toBe('b')
  })

  it('rend null quand tout est chargé', () => {
    const complets = sources.map((s) => ({
      ...s,
      matchesById: Object.fromEntries(s.resultIds.map((id) => [id, []])),
    }))

    expect(nextPreloadStep(complets, () => false)).toBeNull()
  })

  it('rend null quand le plafond exclut tout ce qui reste', () => {
    const énorme = [source('énorme', { resultIds: ids(MAX_PRELOADED_PROFILES + 1) })]

    expect(nextPreloadStep(énorme, () => true)).toBeNull()
  })

  it('sert quand même la recherche ouverte au-delà du plafond', () => {
    const énorme = [source('énorme', { resultIds: ids(MAX_PRELOADED_PROFILES + 1) })]

    expect(nextPreloadStep(énorme, () => true, { current: 'énorme' })?.searchId).toBe('énorme')
  })

  it('rend null sans aucune recherche', () => {
    expect(nextPreloadStep([], () => true)).toBeNull()
  })
})

describe('withIntent', () => {
  /**
   * LA DÉSIGNÉE PASSE EN TÊTE. C'est tout ce que fait une intention : elle réordonne une file déjà
   * en marche, elle ne lance rien — c'est ce qui préserve la règle du seul appel en vol.
   */
  it('met la recherche désignée en tête', () => {
    expect(withIntent(['a', 'b'], 'c')).toEqual(['c', 'a', 'b'])
  })

  /**
   * Sans dédoublonnage, survoler la même carte trois fois lui donnerait trois rangs, et la file
   * grossirait à chaque passage de souris sans jamais changer d'avis.
   */
  it('ne fait pas grossir la file quand on redésigne la même', () => {
    expect(withIntent(['a', 'b'], 'a')).toEqual(['a', 'b'])
    expect(withIntent(withIntent(['b'], 'a'), 'a')).toEqual(['a', 'b'])
  })

  /** Les précédentes gardent leur rang derrière : ce sont toutes des recherches qu'on a visées. */
  it('garde les désignations précédentes derrière la nouvelle', () => {
    expect(withIntent(['a', 'b', 'c'], 'c')).toEqual(['c', 'a', 'b'])
  })

  it('part d’une liste vide sans rien inventer', () => {
    expect(withIntent([], 'a')).toEqual(['a'])
  })

  it('ne modifie pas la liste reçue', () => {
    const avant = ['a', 'b']
    withIntent(avant, 'c')

    expect(avant).toEqual(['a', 'b'])
  })
})

describe('une intention face à la file', () => {
  const jeu = (id: string, taille: number, updatedAt: string): PreloadSource =>
    source(id, { resultIds: ids(taille, id), updatedAt })

  /** Le fond consulte les désignées AVANT l'ordre par défaut : c'est là que l'intention agit. */
  it('sert la recherche désignée avant celle que la date mettait en tête', () => {
    const sources = [
      jeu('récente', 2, '2026-09-01T00:00:00+00:00'),
      jeu('visée', 2, '2026-01-01T00:00:00+00:00'),
    ]

    expect(nextPreloadStep(sources, () => true)?.searchId).toBe('récente')
    expect(
      nextPreloadStep(sources, () => true, { priority: withIntent([], 'visée') })?.searchId,
    ).toBe('visée')
  })

  /**
   * UNE INTENTION NE ROUVRE PAS UN BUDGET ÉPUISÉ : elle réordonne ce qui reste à charger. Une
   * recherche hors plafond ne se charge qu'en l'ouvrant.
   */
  it('ne fait pas franchir le plafond à une recherche trop grande', () => {
    const sources = [
      jeu('énorme', MAX_PRELOADED_PROFILES + 1, '2026-01-01T00:00:00+00:00'),
      jeu('petite', 2, '2026-02-01T00:00:00+00:00'),
    ]

    expect(nextPreloadStep(sources, () => true, { priority: ['énorme'] })?.searchId).toBe('petite')
  })
})
