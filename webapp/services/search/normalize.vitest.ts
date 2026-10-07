import { describe, expect, it } from 'vitest'

import {
  cardsInSearch,
  normalizeProfiles,
  normalizeResults,
  normalizeSearch,
  normalizeSearchWithResults,
  normalizeSearches,
  normalizeWorkspace,
} from './normalize'

import type { CandidateCard, CriterionMatch } from '../../shared/types/candidate-card'
import type {
  Search,
  SearchResults,
  SearchSet,
  SearchWorkspace,
  SearchWorkspaceEntry,
} from '../../shared/types/search'

const candidate = (id: string, overrides: Partial<CandidateCard> = {}): CandidateCard => ({
  id,
  givenName: 'A.',
  familyName: null,
  photoUrl: null,
  headline: null,
  experienceYears: 5,
  city: null,
  country: null,
  contactEmail: null,
  phone: null,
  hasEmail: true,
  hasPhone: false,
  lastInteractionAt: null,
  lastInteractionType: null,
  desiredSector: null,
  desiredFunction: null,
  availability: null,
  returnHorizon: null,
  geoMobility: false,
  hasPiedATerre: false,
  piedATerreCity: null,
  presencePeriods: [],
  skills: [],
  languages: [],
  experienceSectors: [],
  isStar: false,
  revealedEmail: false,
  revealedPhone: false,
  revealedResume: false,
  revealedReport: false,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
  isNew: false,
  isFavorite: false,
  educationLevel: null,
  returnProject: null,
  isInMorocco: false,
  isSeen: false,
  isDismissed: false,
  matches: null,
  ...overrides,
})

const search = (id: string, overrides: Partial<Search> = {}): Search => ({
  id,
  name: 'Devs Casablanca',
  criteria: [],
  isAutoNamed: true,
  isShared: false,
  ownerId: 'owner-1',
  ownerName: 'Omar Alaoui',
  isOwn: true,
  newCount: 0,
  seenCount: 0,
  favoriteCount: 0,
  resultCount: 0,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
  ...overrides,
})

const results = (items: CandidateCard[], resultIds?: string[]): SearchResults => ({
  items,
  total: resultIds?.length ?? items.length,
  page: 1,
  perPage: 30,
  resultIds: resultIds ?? items.map(({ id }) => id),
})

describe('normalizeSearch', () => {
  it("détache le nom de l'auteur, en gardant le lien", () => {
    const { search: stored, owner } = normalizeSearch(search('s1'))

    expect(owner).toEqual({ id: 'owner-1', name: 'Omar Alaoui' })
    expect(stored.ownerId).toBe('owner-1')
    expect('ownerName' in stored).toBe(false)
  })
})

describe('normalizeSearches', () => {
  it('dédoublonne les auteurs partagés par plusieurs recherches', () => {
    const collection = {
      items: [
        search('s1'),
        search('s2'),
        search('s3', { ownerId: 'owner-2', ownerName: 'Leila Mansouri' }),
      ],
      total: 3,
      page: 1,
      perPage: 30,
    }

    const { searches, owners } = normalizeSearches(collection)

    expect(searches).toHaveLength(3)
    expect(owners).toEqual([
      { id: 'owner-1', name: 'Omar Alaoui' },
      { id: 'owner-2', name: 'Leila Mansouri' },
    ])
  })
})

describe('normalizeResults', () => {
  it("sort l'état de travail des cartes pour le porter sur la recherche", () => {
    const { candidates, set } = normalizeResults(
      results([
        candidate('a', { isFavorite: true }),
        candidate('b', { isNew: true }),
        candidate('c'),
      ]),
    )

    expect(set.favoriteIds).toEqual(['a'])
    expect(set.newIds).toEqual(['b'])
    expect(set.seenIds).toEqual([])
    expect(set.dismissedIds).toEqual([])
    // Le pool est GLOBAL : une carte qui y garderait son favori le prêterait à une autre recherche.
    expect(candidates.map(({ isFavorite }) => isFavorite)).toEqual([null, null, null])
    expect(candidates.map(({ isNew }) => isNew)).toEqual([null, null, null])
  })

  it('prend le jeu complet dans la réponse, et non dans la page servie', () => {
    const { candidates, set } = normalizeResults(results([candidate('a')], ['a', 'b', 'c']))

    expect(candidates).toHaveLength(1)
    expect(set.resultIds).toEqual(['a', 'b', 'c'])
  })

  /**
   * Le cœur de la manœuvre : ces listes décrivent la recherche ENTIÈRE, les cartes seulement ce qui
   * est servi. Un profil retenu hors du jeu chargé était compté sans être connu.
   */
  it("préfère l'état servi avec le jeu à ce que les cartes en disent", () => {
    const { set } = normalizeResults({
      ...results([candidate('a', { isFavorite: true }), candidate('b')], ['a', 'b']),
      favoriteIds: ['a', 'z'],
      seenIds: ['b', 'z'],
      dismissedIds: ['z'],
    })

    expect(set.favoriteIds).toEqual(['a', 'z'])
    expect(set.seenIds).toEqual(['b', 'z'])
    expect(set.dismissedIds).toEqual(['z'])
  })

  /**
   * Les périmètres `?scope=` ne portent pas ces listes — ils sont demandés une fois l'état connu.
   * Le repli sur les cartes reste donc nécessaire, et il ne doit pas se déclencher à tort.
   */
  it('retombe sur les cartes quand la réponse ne porte pas ces listes', () => {
    const { set } = normalizeResults(
      results([candidate('a', { isFavorite: true, isSeen: true }), candidate('b')]),
    )

    expect(set.favoriteIds).toEqual(['a'])
    expect(set.seenIds).toEqual(['a'])
  })

  /** `isNew` n'a pas d'équivalent servi : il se lit sur les cartes, servi ou pas. */
  it('lit toujours la nouveauté sur les cartes', () => {
    const { set } = normalizeResults({
      ...results([candidate('a', { isNew: true })], ['a']),
      favoriteIds: [],
      seenIds: [],
      dismissedIds: [],
    })

    expect(set.newIds).toEqual(['a'])
    expect(set.favoriteIds).toEqual([])
  })

  /**
   * Une liste servie VIDE n'est pas une liste absente : elle dit « aucun retenu », et doit écraser
   * ce que les cartes prétendent. Confondre les deux ferait ressusciter un favori qu'on vient de
   * libérer, à la première relecture du jeu.
   */
  it('respecte une liste servie vide', () => {
    const { set } = normalizeResults({
      ...results([candidate('a', { isFavorite: true })], ['a']),
      favoriteIds: [],
    })

    expect(set.favoriteIds).toEqual([])
  })

  /**
   * L'EXPLICATION QUITTE LA CARTE POUR LA RECHERCHE. Le pool est commun : gardée sur la fiche,
   * l'explication de la dernière recherche ouverte écrasait celle des autres.
   */
  it("détache l'explication des critères et l'indexe par profil", () => {
    const { candidates, set } = normalizeResults(
      results([
        candidate('a', {
          matches: [
            {
              facet: 'job',
              value: 'Développeur',
              matched: true,
              matchedOn: 'Frontend developer',
              matchedWhen: null,
              matchedIn: null,
            },
          ],
        }),
      ]),
    )

    expect(candidates[0]?.matches).toBeNull()
    expect(set.matchesById.a?.[0]?.matchedOn).toBe('Frontend developer')
  })

  it("n'invente pas d'entrée pour un profil que rien n'explique", () => {
    const { set } = normalizeResults(results([candidate('a', { matches: null })]))

    expect(set.matchesById).toEqual({})
  })
})

describe('normalizeSearchWithResults', () => {
  it("éclate d'un coup la réponse d'une écriture", () => {
    const normalized = normalizeSearchWithResults({
      search: search('s1'),
      results: results([candidate('a', { isFavorite: true })]),
    })

    expect(normalized.owner.name).toBe('Omar Alaoui')
    expect(normalized.search.id).toBe('s1')
    expect(normalized.set.favoriteIds).toEqual(['a'])
    expect(normalized.candidates).toHaveLength(1)
  })
})

describe('cardsInSearch', () => {
  const pool = {
    a: candidate('a', { isFavorite: null, isNew: null }),
    b: candidate('b', { isFavorite: null, isNew: null }),
  }

  const set = (marks: Partial<Omit<SearchSet, 'resultIds' | 'selectedIds'>> = {}) => ({
    favoriteIds: [],
    newIds: [],
    seenIds: [],
    dismissedIds: [],
    ...marks,
  })

  it("rend l'ordre du serveur, pas celui du pool", () => {
    expect(cardsInSearch(['b', 'a'], pool, set()).map(({ id }) => id)).toEqual(['b', 'a'])
  })

  it("réattache l'état de travail au dernier moment", () => {
    const [first] = cardsInSearch(['a'], pool, set({ favoriteIds: ['a'], newIds: ['a'] }))

    expect(first?.isFavorite).toBe(true)
    expect(first?.isNew).toBe(true)
  })

  it('ignore une carte pas encore chargée au lieu de trouer la liste', () => {
    expect(cardsInSearch(['a', 'inconnu', 'b'], pool, set()).map(({ id }) => id)).toEqual([
      'a',
      'b',
    ])
  })

  it('ne contamine pas le pool en réattachant', () => {
    cardsInSearch(['a'], pool, set({ favoriteIds: ['a'], newIds: ['a'] }))

    expect(pool.a.isFavorite).toBeNull()
  })

  // La pile est ce qu'il RESTE à trier : un profil écarté n'y figure plus, sinon écarter ne
  // servirait à rien et le jeu ne se refermerait jamais.
  it('sort les profils écartés de la pile', () => {
    expect(
      cardsInSearch(['a', 'b'], pool, set({ dismissedIds: ['a'] })).map(({ id }) => id),
    ).toEqual(['b'])
  })

  it('marque comme vus les profils déjà franchis', () => {
    const [first] = cardsInSearch(['a'], pool, set({ seenIds: ['a'] }))

    expect(first?.isSeen).toBe(true)
    expect(first?.isDismissed).toBe(false)
  })

  /** L'aller-retour complet : détachée à l'entrée, l'explication est recollée à l'affichage. */
  it("recolle l'explication de CETTE recherche", () => {
    const [first] = cardsInSearch(['a'], pool, {
      ...set(),
      matchesById: {
        a: [
          {
            facet: 'job',
            value: 'Développeur',
            matched: true,
            matchedOn: 'Frontend developer',
            matchedWhen: null,
            matchedIn: null,
          },
        ],
      },
    })

    expect(first?.matches?.[0]?.matchedOn).toBe('Frontend developer')
    expect(pool.a.matches).toBeNull()
  })

  /**
   * Un périmètre `?scope=` ne sert pas d'explication : sans repli, ouvrir l'onglet des retenus
   * effacerait les étiquettes des cartes déjà expliquées.
   */
  it("laisse la carte telle quelle quand la recherche n'explique rien", () => {
    const expliquee = {
      ...candidate('c', { isFavorite: null, isNew: null }),
      matches: [
        {
          facet: 'job',
          value: 'Développeur',
          matched: true,
          matchedOn: 'Dev',
          matchedWhen: null,
          matchedIn: null,
        },
      ],
    }
    const [first] = cardsInSearch(['c'], { c: expliquee }, set())

    expect(first?.matches?.[0]?.matchedOn).toBe('Dev')
  })
})

describe('normalizeWorkspace', () => {
  const workspace = (entries: SearchWorkspaceEntry[]): SearchWorkspace => ({ searches: entries })

  const entry = (
    id: string,
    overrides: Partial<SearchWorkspaceEntry> = {},
  ): SearchWorkspaceEntry => ({
    id,
    resultIds: [],
    favoriteIds: [],
    dismissedIds: [],
    seenIds: [],
    newIds: [],
    ...overrides,
  })

  const match = (value: string): CriterionMatch => ({
    facet: 'job',
    value,
    matched: true,
    matchedOn: value,
    matchedWhen: null,
    matchedIn: null,
  })

  /**
   * LA RÈGLE DE CETTE ROUTE : rien ne se déduit des fiches en mémoire.
   *
   * Un compte tiré du chargé vaut « parmi ce que j'ai », pas « dans cette recherche » — et il
   * rétrécit à mesure qu'on charge moins. Les cinq listes viennent du serveur, telles quelles.
   */
  it('reprend les cinq listes telles que le serveur les sert', () => {
    const { sets } = normalizeWorkspace(
      workspace([
        entry('s1', {
          resultIds: ['a', 'b'],
          favoriteIds: ['a'],
          dismissedIds: ['z'],
          seenIds: ['b'],
          newIds: ['b'],
        }),
      ]),
    )

    expect(sets.s1?.resultIds).toEqual(['a', 'b'])
    expect(sets.s1?.favoriteIds).toEqual(['a'])
    expect(sets.s1?.dismissedIds).toEqual(['z'])
    expect(sets.s1?.seenIds).toEqual(['b'])
    expect(sets.s1?.newIds).toEqual(['b'])
  })

  /**
   * Les nouveautés viennent du SERVEUR et d'aucune fiche : c'était la dernière des cinq listes à se
   * reconstituer localement, et elle mentait dès que le chargement était partiel.
   */
  it('ne reconstitue aucune liste à partir des fiches reçues', () => {
    const { sets } = normalizeWorkspace(
      workspace([entry('s1', { resultIds: ['a', 'b', 'c'], newIds: ['c'] })]),
    )

    expect(sets.s1?.newIds).toEqual(['c'])
  })

  it('range un jeu par recherche, sans les mélanger', () => {
    const { sets } = normalizeWorkspace(
      workspace([entry('s1', { resultIds: ['a', 'b'] }), entry('s2', { resultIds: ['b'] })]),
    )

    expect(Object.keys(sets)).toEqual(['s1', 's2'])
    expect(sets.s1?.resultIds).toEqual(['a', 'b'])
    expect(sets.s2?.resultIds).toEqual(['b'])
  })

  /**
   * L'état ne porte PLUS les explications — elles coûtent quinze fois un identifiant et ne servent
   * qu'aux profils affichés. Les écraser à chaque relecture effacerait le « pourquoi ce profil »
   * d'une recherche déjà ouverte.
   */
  it("préserve les explications déjà acquises, que l'état ne rapporte plus", () => {
    const { sets } = normalizeWorkspace(workspace([entry('s1', { resultIds: ['a'] })]), {
      s1: { a: [match('Développeur')] },
    })

    expect(sets.s1?.matchesById.a?.[0]?.value).toBe('Développeur')
  })

  /**
   * L'explication appartient au COUPLE (recherche, profil) : le même profil répond à deux recherches
   * pour deux motifs, et une seule des deux versions survivrait sur une fiche partagée.
   */
  it('garde les explications de chaque recherche séparées', () => {
    const { sets } = normalizeWorkspace(
      workspace([entry('s1', { resultIds: ['a'] }), entry('s2', { resultIds: ['a'] })]),
      { s1: { a: [match('Développeur')] }, s2: { a: [match('Chef de projet')] } },
    )

    expect(sets.s1?.matchesById.a?.[0]?.value).toBe('Développeur')
    expect(sets.s2?.matchesById.a?.[0]?.value).toBe('Chef de projet')
  })

  it("laisse les explications vides quand rien n'est encore connu", () => {
    const { sets } = normalizeWorkspace(workspace([entry('s1', { resultIds: ['a'] })]))

    expect(sets.s1?.matchesById).toEqual({})
  })

  it('rend un plan vide sans rien inventer', () => {
    expect(normalizeWorkspace(workspace([]))).toEqual({ sets: {} })
  })
})

describe('normalizeProfiles', () => {
  /**
   * LES FICHES DU LOT N'APPARTIENNENT À AUCUNE RECHERCHE, et c'est ce que la liste commune attend :
   * le serveur sert un lot dédoublonné sans savoir de quelle recherche on parle, la relation se
   * recolle à l'affichage.
   *
   * Une fiche qui arriverait avec un `isFavorite` figé le porterait dans TOUTES les recherches où
   * elle apparaît — le pool est global, la relation ne l'est pas.
   */
  it('range les fiches sans aucun état de recherche', () => {
    const [premier] = normalizeProfiles({
      items: [candidate('a', { isFavorite: true, isNew: true, isSeen: true, isDismissed: true })],
    })

    expect(premier?.isFavorite).toBeNull()
    expect(premier?.isNew).toBeNull()
    expect(premier?.isSeen).toBeNull()
    expect(premier?.isDismissed).toBeNull()
    expect(premier?.matches).toBeNull()
  })

  it('garde tout ce qui décrit le profil lui-même', () => {
    const [premier] = normalizeProfiles({
      items: [candidate('a', { givenName: 'L.', experienceYears: 12 })],
    })

    expect(premier?.id).toBe('a')
    expect(premier?.givenName).toBe('L.')
    expect(premier?.experienceYears).toBe(12)
  })

  it('rend une liste vide sur un lot vide', () => {
    expect(normalizeProfiles({ items: [] })).toEqual([])
  })
})
