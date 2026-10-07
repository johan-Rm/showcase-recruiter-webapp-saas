import { describe, expect, it } from 'vitest'

import {
  carriesJobCriterion,
  countFor,
  emptyRefineForm,
  isRefined,
  matchesRefine,
  refinableCities,
  refinableCountries,
  refinableDesiredFunctions,
  refinableDesiredSectors,
  refinableLanguages,
  refinableSectors,
  refine,
} from './refine'

import type { RefineForm } from './refine'
import type { CandidateCard } from '../../shared/types/candidate-card'

const AT = new Date('2026-07-01T12:00:00Z')

/** Une carte réduite à ce que l'affinage regarde. */
const card = (id: string, traits: Partial<CandidateCard> = {}): CandidateCard =>
  ({
    id,
    presencePeriods: [],
    hasPiedATerre: false,
    geoMobility: false,
    returnHorizon: null,
    availability: null,
    country: null,
    matches: null,
    languages: [],
    experienceSectors: [],
    desiredSector: null,
    desiredFunction: null,
    isStar: false,
    ...traits,
  }) as CandidateCard

/** Une correspondance de métier, telle que le serveur la rend — `when` absent = il n'a pas tranché. */
const jobMatch = (when?: 'CURRENT' | 'PAST') => [
  { facet: 'job', value: 'logisticien', matched: true, ...(when ? { matchedWhen: when } : {}) },
]

const form = (posed: Partial<RefineForm> = {}): RefineForm => ({ ...emptyRefineForm(), ...posed })

const ids = (cards: readonly CandidateCard[]): string[] => cards.map((candidate) => candidate.id)

describe('isRefined', () => {
  it('est faux sur un formulaire vierge', () => {
    expect(isRefined(emptyRefineForm())).toBe(false)
  })

  it('est vrai dès qu’une facette est posée', () => {
    expect(isRefined(form({ availability: 'IMMEDIATE' }))).toBe(true)
    expect(isRefined(form({ geoMobility: true }))).toBe(true)
  })
})

describe('matchesRefine', () => {
  const casa = { startDate: '2026-07-20', endDate: '2026-07-30', city: 'Casablanca' }

  it('laisse tout passer quand rien n’est posé', () => {
    expect(matchesRefine(card('a'), emptyRefineForm(), AT)).toBe(true)
  })

  it('retient sur la fenêtre de présence', () => {
    const stayer = card('a', { presencePeriods: [casa] })

    expect(matchesRefine(stayer, form({ presence: '30' }), AT)).toBe(true)
    expect(matchesRefine(stayer, form({ presence: '15' }), AT)).toBe(false)
  })

  it('« maintenant » demande un séjour couvrant le jour même', () => {
    const stayer = card('a', { presencePeriods: [casa] })

    expect(matchesRefine(stayer, form({ presence: 'now' }), AT)).toBe(false)
    expect(matchesRefine(stayer, form({ presence: 'now' }), new Date('2026-07-25T12:00:00Z'))).toBe(
      true,
    )
  })

  // Une ville seule ne se referme pas sur aujourd'hui : la question devient « y va-t-il un jour ? ».
  it('accepte une ville posée sans fenêtre', () => {
    expect(
      matchesRefine(
        card('a', { presencePeriods: [casa] }),
        form({ presenceCity: 'Casablanca' }),
        AT,
      ),
    ).toBe(true)
  })

  it('exige le même séjour pour la fenêtre et la ville', () => {
    const twoStays = card('a', {
      presencePeriods: [casa, { startDate: '2026-12-01', endDate: '2026-12-10', city: 'Rabat' }],
    })

    expect(matchesRefine(twoStays, form({ presence: '30', presenceCity: 'Casablanca' }), AT)).toBe(
      true,
    )
    expect(matchesRefine(twoStays, form({ presence: '30', presenceCity: 'Rabat' }), AT)).toBe(false)
  })

  // Un interrupteur décoché ne filtre pas sur « ceux qui n'en ont pas » — personne ne cherche ça.
  it('ne filtre rien quand un interrupteur est décoché', () => {
    expect(
      matchesRefine(card('a', { hasPiedATerre: false }), form({ hasPiedATerre: false }), AT),
    ).toBe(true)
  })

  it('retient sur le pied-à-terre et la mobilité quand ils sont cochés', () => {
    expect(
      matchesRefine(card('a', { hasPiedATerre: true }), form({ hasPiedATerre: true }), AT),
    ).toBe(true)
    expect(matchesRefine(card('a'), form({ hasPiedATerre: true }), AT)).toBe(false)
    expect(matchesRefine(card('a'), form({ geoMobility: true }), AT)).toBe(false)
  })

  it('retient sur l’horizon et la disponibilité', () => {
    const soon = card('a', { returnHorizon: 'M6', availability: 'IMMEDIATE' })

    expect(matchesRefine(soon, form({ returnHorizon: 'M6' }), AT)).toBe(true)
    expect(matchesRefine(soon, form({ returnHorizon: 'M3' }), AT)).toBe(false)
    expect(matchesRefine(soon, form({ availability: 'IMMEDIATE' }), AT)).toBe(true)
    expect(matchesRefine(soon, form({ availability: 'NOTICE_3M' }), AT)).toBe(false)
  })

  it('combine les facettes en ET', () => {
    const both = card('a', { returnHorizon: 'M6', availability: 'IMMEDIATE' })

    expect(matchesRefine(both, form({ returnHorizon: 'M6', availability: 'IMMEDIATE' }), AT)).toBe(
      true,
    )
    expect(matchesRefine(both, form({ returnHorizon: 'M6', availability: 'NOTICE_1M' }), AT)).toBe(
      false,
    )
  })
})

describe('refine', () => {
  const pile = [
    card('a', { returnHorizon: 'M3' }),
    card('b', { returnHorizon: 'M6' }),
    card('c', { returnHorizon: 'M3' }),
  ]

  it('sélectionne sans réordonner', () => {
    expect(ids(refine(pile, form({ returnHorizon: 'M3' }), AT))).toEqual(['a', 'c'])
  })

  it('rend le jeu entier quand rien n’est posé', () => {
    expect(ids(refine(pile, emptyRefineForm(), AT))).toEqual(['a', 'b', 'c'])
  })

  it('peut ne rien rendre', () => {
    expect(refine(pile, form({ returnHorizon: 'THINKING' }), AT)).toEqual([])
  })
})

describe('countFor', () => {
  const pile = [
    card('a', { returnHorizon: 'M3', availability: 'IMMEDIATE' }),
    card('b', { returnHorizon: 'M6', availability: 'IMMEDIATE' }),
    card('c', { returnHorizon: 'M6', availability: 'NOTICE_1M' }),
  ]

  it('compte ce que l’option laisserait', () => {
    expect(countFor(pile, emptyRefineForm(), 'returnHorizon', 'M6', AT)).toBe(2)
  })

  // LA propriété du comptage facetté : l'option remplace la valeur de SA facette, les autres
  // filtres tiennent. Compter sur le jeu déjà resserré par cette facette mettrait toutes les
  // autres options à zéro, et le compteur ne dirait plus que ce qu'on vient de choisir.
  it('ignore la valeur déjà posée sur sa propre facette', () => {
    const posed = form({ returnHorizon: 'M3' })

    expect(countFor(pile, posed, 'returnHorizon', 'M6', AT)).toBe(2)
  })

  it('applique les AUTRES facettes posées', () => {
    const posed = form({ availability: 'IMMEDIATE' })

    expect(countFor(pile, posed, 'returnHorizon', 'M6', AT)).toBe(1)
  })

  it('compte aussi un interrupteur', () => {
    const withPied = [...pile, card('d', { hasPiedATerre: true })]

    expect(countFor(withPied, emptyRefineForm(), 'hasPiedATerre', true, AT)).toBe(1)
  })
})

describe('refinableCities', () => {
  it('n’offre que les villes du jeu', () => {
    const pile = [
      card('a', {
        presencePeriods: [{ startDate: '2026-07-10', endDate: '2026-07-20', city: 'Rabat' }],
      }),
      card('b', {
        presencePeriods: [{ startDate: '2026-07-10', endDate: '2026-07-20', city: 'Agadir' }],
      }),
      card('c'),
    ]

    expect(refinableCities(pile)).toEqual(['Agadir', 'Rabat'])
  })

  it('rend une liste vide sur un jeu sans séjour', () => {
    expect(refinableCities([card('a'), card('b')])).toEqual([])
  })
})

describe('affinage sur le pays de résidence', () => {
  const pile = [
    card('a', { country: 'FR' }),
    card('b', { country: 'BE' }),
    card('c', { country: 'FR' }),
    card('d'),
  ]

  it('retient les profils du pays posé', () => {
    expect(ids(refine(pile, form({ country: 'FR' }), AT))).toEqual(['a', 'c'])
  })

  it('écarte un profil sans pays connu', () => {
    expect(matchesRefine(card('x'), form({ country: 'FR' }), AT)).toBe(false)
  })

  it('compte ce que chaque pays laisserait', () => {
    expect(countFor(pile, emptyRefineForm(), 'country', 'BE', AT)).toBe(1)
  })
})

describe('affinage sur le poste du moment', () => {
  const current = card('a', { matches: jobMatch('CURRENT') })
  const past = card('b', { matches: jobMatch('PAST') })
  const unknown = card('c', { matches: jobMatch() })

  it('ne garde que les correspondances confirmées comme actuelles', () => {
    expect(matchesRefine(current, form({ currentJob: true }), AT)).toBe(true)
    expect(matchesRefine(past, form({ currentJob: true }), AT)).toBe(false)
  })

  // LA règle qui coûte, et celle que le libellé doit dire : le silence du serveur n'est pas un
  // « non », mais on l'écarte quand même — faute de pouvoir affirmer l'inverse.
  it('écarte aussi les profils que le serveur n’a pas su classer', () => {
    expect(matchesRefine(unknown, form({ currentJob: true }), AT)).toBe(false)
  })

  it('ne filtre rien quand l’interrupteur est décoché', () => {
    expect(ids(refine([current, past, unknown], emptyRefineForm(), AT))).toEqual(['a', 'b', 'c'])
  })

  it('écarte un profil sans aucune correspondance de métier', () => {
    expect(matchesRefine(card('x'), form({ currentJob: true }), AT)).toBe(false)
  })

  it('compte ce que l’interrupteur laisserait', () => {
    expect(countFor([current, past, unknown], emptyRefineForm(), 'currentJob', true, AT)).toBe(1)
  })
})

describe('refinableCountries', () => {
  it('dédoublonne et range les pays du jeu, le plus porté d’abord', () => {
    expect(
      refinableCountries([
        card('a', { country: 'FR' }),
        card('b', { country: 'BE' }),
        card('c', { country: 'FR' }),
      ]),
    ).toEqual(['FR', 'BE'])
  })

  it('ignore les pays absents ou vides', () => {
    expect(refinableCountries([card('a'), card('b', { country: '  ' })])).toEqual([])
  })
})

describe('carriesJobCriterion', () => {
  // Sans critère de métier, aucun profil ne porte de correspondance à interroger : l'écran n'offre
  // pas l'interrupteur plutôt que de vider le jeu sans raison lisible.
  it('est vrai dès qu’une carte porte une correspondance de métier', () => {
    expect(carriesJobCriterion([card('a'), card('b', { matches: jobMatch('PAST') })])).toBe(true)
  })

  it('est faux sur une recherche qui ne nomme aucun métier', () => {
    const keywordOnly = [{ facet: 'q', value: 'sap', matched: true }]

    expect(carriesJobCriterion([card('a'), card('b', { matches: keywordOnly })])).toBe(false)
  })

  it('est faux sur un jeu vide', () => {
    expect(carriesJobCriterion([])).toBe(false)
  })
})

describe('affinage sur la langue parlée', () => {
  const arabe = { language: 'ar', proficiency: 'NATIVE_OR_BILINGUAL' }
  const anglaisSansNiveau = { language: 'en', proficiency: null }

  const pile = [
    card('a', { languages: [arabe, anglaisSansNiveau] }),
    card('b', { languages: [anglaisSansNiveau] }),
    card('c'),
  ]

  it('retient les profils qui parlent la langue posée', () => {
    expect(ids(refine(pile, form({ language: 'en' }), AT))).toEqual(['a', 'b'])
  })

  // LE PIÈGE : la langue maternelle arrive souvent sans niveau. Un affinage qui exigerait une
  // maîtrise connue écarterait précisément ceux qui la maîtrisent le mieux.
  it('ne demande aucun niveau — un profil sans maîtrise connue passe', () => {
    expect(
      matchesRefine(card('x', { languages: [anglaisSansNiveau] }), form({ language: 'en' }), AT),
    ).toBe(true)
  })

  it('écarte un profil sans langue connue', () => {
    expect(matchesRefine(card('x'), form({ language: 'fr' }), AT)).toBe(false)
  })

  it('compte ce que chaque langue laisserait', () => {
    expect(countFor(pile, emptyRefineForm(), 'language', 'ar', AT)).toBe(1)
  })
})

describe('affinage sur le secteur d’expérience', () => {
  // Une bifurcation : longtemps dans la santé, plus récemment dans la banque.
  const bifurque = card('a', { experienceSectors: ['SANTE_PHARMA', 'BANQUE_FINANCE'] })
  const banquier = card('b', { experienceSectors: ['BANQUE_FINANCE'] })
  const pile = [bifurque, banquier, card('c')]

  // LA propriété qui justifie de descendre TOUS les secteurs : un profil qui a changé de branche
  // doit rester trouvable sur chacune, pas seulement sur la plus ancienne.
  it('retient un profil sur CHACUN de ses secteurs', () => {
    expect(ids(refine(pile, form({ experienceSector: 'SANTE_PHARMA' }), AT))).toEqual(['a'])
    expect(ids(refine(pile, form({ experienceSector: 'BANQUE_FINANCE' }), AT))).toEqual(['a', 'b'])
  })

  it('écarte un profil sans secteur connu', () => {
    expect(matchesRefine(card('x'), form({ experienceSector: 'INDUSTRIE' }), AT)).toBe(false)
  })

  it('compte ce que chaque secteur laisserait', () => {
    expect(countFor(pile, emptyRefineForm(), 'experienceSector', 'BANQUE_FINANCE', AT)).toBe(2)
  })
})

describe('refinableLanguages et refinableSectors', () => {
  const pile = [
    card('a', {
      languages: [{ language: 'fr', proficiency: null }],
      experienceSectors: ['INDUSTRIE', 'BTP'],
    }),
    card('b', {
      languages: [
        { language: 'ar', proficiency: 'ELEMENTARY' },
        { language: 'fr', proficiency: null },
      ],
      experienceSectors: ['BTP'],
    }),
    card('c'),
  ]

  it('n’offrent que ce que le jeu porte, dédoublonné et rangé par poids', () => {
    expect(refinableLanguages(pile)).toEqual(['fr', 'ar'])
    expect(refinableSectors(pile)).toEqual(['BTP', 'INDUSTRIE'])
  })

  it('rendent une liste vide sur un jeu qui n’en porte aucun', () => {
    expect(refinableLanguages([card('a')])).toEqual([])
    expect(refinableSectors([card('a')])).toEqual([])
  })
})

describe('une carte à laquelle les champs récents manquent', () => {
  /**
   * Le cas qui a cassé l'écran : le type donne `languages` et `experienceSectors` pour obligatoires,
   * mais le miroir est écrit à la main et rien ne le confronte au serveur. Une carte arrivée d'une
   * API sans le déploiement — ou déjà en mémoire depuis avant — n'en porte aucun, le calcul des
   * options jetait, et le panneau entier cessait de rendre : ses quatre volets s'ouvraient sur du
   * vide, y compris ceux qui n'ont rien à voir avec ces champs.
   */
  const ancienne = { id: 'z', country: 'FR', presencePeriods: [] } as unknown as CandidateCard

  it('n’empêche pas de lister les options', () => {
    expect(() => refinableLanguages([ancienne])).not.toThrow()
    expect(() => refinableSectors([ancienne])).not.toThrow()
    expect(refinableLanguages([ancienne])).toEqual([])
    expect(refinableSectors([ancienne])).toEqual([])
  })

  /**
   * LA MÊME ABSENCE SUR LE PAYS, et elle a cassé l'écran de la même façon : le contrat annonce
   * `string | null`, la carte arrive parfois SANS la clé, et une garde sur le seul `null` laissait
   * passer `undefined` jusqu'à `undefined.trim()`. Vue en rendu serveur — 500, page blanche — le
   * jour où la liste des pays est sortie de son volet et s'est mise à se calculer à chaque rendu.
   */
  it('n’empêche pas de lister les pays', () => {
    const sansPays = { id: 'y', presencePeriods: [] } as unknown as CandidateCard
    const pile = [sansPays, card('a', { country: 'FR' }), card('b', { country: 'BE' })]

    expect(() => refinableCountries(pile)).not.toThrow()
    expect(refinableCountries(pile)).toEqual(['BE', 'FR'])
  })

  it('n’empêche pas de compter, ni de filtrer', () => {
    expect(() => countFor([ancienne], emptyRefineForm(), 'language', 'fr', AT)).not.toThrow()
    expect(matchesRefine(ancienne, form({ language: 'fr' }), AT)).toBe(false)
    expect(matchesRefine(ancienne, form({ experienceSector: 'BTP' }), AT)).toBe(false)
  })

  it('reste retenue par les facettes qui, elles, ont leur donnée', () => {
    expect(matchesRefine(ancienne, form({ country: 'FR' }), AT)).toBe(true)
  })
})

describe('un choix unique ne s’offre pas', () => {
  /**
   * Une liste à un seul bouton ne resserre rien : tous les profils du jeu la satisfont déjà. Elle
   * occupe pourtant son intitulé et sa ligne, et l'écran masque déjà les listes vides pour la raison
   * voisine. Mesuré sur le vivier : sur la plupart des recherches, le pays n'offre que « FR ».
   *
   * LE SEUIL PORTE SUR LE NOMBRE DE CHOIX, jamais sur le nombre de profils — c'est ce que le second
   * test de chaque paire protège : deux valeurs très inégales restent deux choix, et c'est
   * précisément là que l'affinage sert.
   */
  const sejour = (city: string) => [{ startDate: '2026-07-10', endDate: '2026-07-20', city }]

  it('masque le pays, la langue, le secteur et la ville sur une valeur unique', () => {
    const pile = [
      card('a', {
        country: 'FR',
        languages: [{ language: 'fr', proficiency: null }],
        experienceSectors: ['BTP'],
        presencePeriods: sejour('Rabat'),
      }),
      card('b', {
        country: 'FR',
        languages: [{ language: 'fr', proficiency: null }],
        experienceSectors: ['BTP'],
        presencePeriods: sejour('Rabat'),
      }),
    ]

    expect(refinableCountries(pile)).toEqual([])
    expect(refinableLanguages(pile)).toEqual([])
    expect(refinableSectors(pile)).toEqual([])
    expect(refinableCities(pile)).toEqual([])
  })

  it('les offre dès la deuxième valeur, même portée par un seul profil', () => {
    const pile = [
      card('a', {
        country: 'FR',
        languages: [{ language: 'fr', proficiency: null }],
        experienceSectors: ['BTP'],
        presencePeriods: sejour('Rabat'),
      }),
      card('b', {
        country: 'FR',
        languages: [{ language: 'fr', proficiency: null }],
        experienceSectors: ['BTP'],
        presencePeriods: sejour('Rabat'),
      }),
      card('c', {
        country: 'BE',
        languages: [{ language: 'ar', proficiency: null }],
        experienceSectors: ['INDUSTRIE'],
        presencePeriods: sejour('Agadir'),
      }),
    ]

    expect(refinableCountries(pile)).toEqual(['FR', 'BE'])
    expect(refinableLanguages(pile)).toEqual(['fr', 'ar'])
    expect(refinableSectors(pile)).toEqual(['BTP', 'INDUSTRIE'])
    expect(refinableCities(pile)).toEqual(['Rabat', 'Agadir'])
  })

  it('rend toujours une liste vide sur un jeu qui ne porte rien', () => {
    expect(refinableCountries([card('a'), card('b')])).toEqual([])
    expect(refinableLanguages([card('a'), card('b')])).toEqual([])
    expect(refinableSectors([card('a'), card('b')])).toEqual([])
    expect(refinableCities([card('a'), card('b')])).toEqual([])
  })
})

describe('le projet du candidat et l’entretien', () => {
  const pile = [
    card('a', { desiredSector: 'Finance', desiredFunction: 'Comptable', isStar: true }),
    card('b', { desiredSector: 'Informatique', desiredFunction: 'Développeur' }),
    card('c'),
  ]

  it('garde ceux qui visent le secteur posé', () => {
    expect(ids(refine(pile, form({ desiredSector: 'Finance' }), AT))).toEqual(['a'])
  })

  it('garde ceux qui visent la fonction posée', () => {
    expect(ids(refine(pile, form({ desiredFunction: 'Développeur' }), AT))).toEqual(['b'])
  })

  it('garde les seuls profils vérifiés quand l’interrupteur est coché', () => {
    expect(ids(refine(pile, form({ isStar: true }), AT))).toEqual(['a'])
  })

  it('ne retire rien quand l’interrupteur est décoché', () => {
    // Décoché veut dire « peu importe », jamais « ceux qui ne sont pas vérifiés ».
    expect(ids(refine(pile, form({ isStar: false }), AT))).toEqual(['a', 'b', 'c'])
  })

  it('combine les deux rangées par ET', () => {
    expect(
      ids(refine(pile, form({ desiredSector: 'Finance', desiredFunction: 'Comptable' }), AT)),
    ).toEqual(['a'])
    expect(
      refine(pile, form({ desiredSector: 'Finance', desiredFunction: 'Développeur' }), AT),
    ).toEqual([])
  })

  it('n’offre que ce que le jeu porte, le plus porté d’abord', () => {
    const jeu = [
      card('x', { desiredSector: 'Informatique', desiredFunction: 'Développeur' }),
      card('y', { desiredSector: 'Informatique', desiredFunction: 'Développeur' }),
      card('z', { desiredSector: 'Finance', desiredFunction: 'Comptable' }),
    ]

    expect(refinableDesiredSectors(jeu)).toEqual(['Informatique', 'Finance'])
    expect(refinableDesiredFunctions(jeu)).toEqual(['Développeur', 'Comptable'])
  })

  it('n’offre rien quand un seul projet est représenté, ou aucun', () => {
    // Un choix unique ne resserre rien : tous les profils du jeu le satisfont déjà.
    expect(refinableDesiredSectors([card('x', { desiredSector: 'Finance' })])).toEqual([])
    expect(refinableDesiredFunctions([card('x'), card('y')])).toEqual([])
  })

  it('compte l’affinage comme posé', () => {
    expect(isRefined(form({ desiredSector: 'Finance' }))).toBe(true)
    expect(isRefined(form({ desiredFunction: 'Comptable' }))).toBe(true)
    expect(isRefined(form({ isStar: true }))).toBe(true)
  })
})

describe('le niveau de langue', () => {
  const parle = (language: string, proficiency: string | null) => ({ language, proficiency })

  const pile = [
    card('bilingue', { languages: [parle('en', 'NATIVE_OR_BILINGUAL')] }),
    card('courant', { languages: [parle('en', 'FULL_PROFESSIONAL')] }),
    card('notions', { languages: [parle('en', 'ELEMENTARY')] }),
    card('sans-niveau', { languages: [parle('en', null)] }),
    card('autre-langue', { languages: [parle('ar', 'NATIVE_OR_BILINGUAL')] }),
  ]

  it('garde les paliers SUPÉRIEURS, le seuil n’étant pas une égalité', () => {
    expect(
      ids(refine(pile, form({ language: 'en', languageLevel: 'FULL_PROFESSIONAL' }), AT)),
    ).toEqual(['bilingue', 'courant'])
  })

  it('écarte l’entrée sans niveau déclaré dès qu’un palier est posé', () => {
    // Un tiers du vivier ne porte pas de niveau : le silence ne se compte pas comme un « oui ».
    expect(ids(refine(pile, form({ language: 'en', languageLevel: 'ELEMENTARY' }), AT))).toEqual([
      'bilingue',
      'courant',
      'notions',
    ])
  })

  it('garde tout le monde, niveau compris, quand seule la langue est posée', () => {
    expect(ids(refine(pile, form({ language: 'en' }), AT))).toEqual([
      'bilingue',
      'courant',
      'notions',
      'sans-niveau',
    ])
  })

  it('ne resserre rien quand le palier est posé SANS langue', () => {
    // Le seuil n'a aucune entrée à qualifier : il se tait plutôt que de vider le jeu.
    expect(refine(pile, form({ languageLevel: 'NATIVE_OR_BILINGUAL' }), AT)).toHaveLength(
      pile.length,
    )
  })

  it('exige la MÊME entrée pour la langue et le niveau', () => {
    // Le piège que ce test protège : évalués séparément, « anglais » et « au moins bilingue » sont
    // tous deux satisfaits par ce profil — qui ne répond pourtant pas à la demande.
    const arabophone = card('arabophone', {
      languages: [parle('ar', 'NATIVE_OR_BILINGUAL'), parle('en', 'ELEMENTARY')],
    })

    expect(
      refine([arabophone], form({ language: 'en', languageLevel: 'NATIVE_OR_BILINGUAL' }), AT),
    ).toEqual([])
    expect(
      ids(refine([arabophone], form({ language: 'en', languageLevel: 'ELEMENTARY' }), AT)),
    ).toEqual(['arabophone'])
  })

  it('se contente d’UNE entrée satisfaisante quand la langue est portée deux fois', () => {
    // Deux sources peuvent déclarer la même langue à des niveaux différents.
    const deuxFois = card('deux-fois', {
      languages: [parle('en', null), parle('en', 'FULL_PROFESSIONAL')],
    })

    expect(
      ids(refine([deuxFois], form({ language: 'en', languageLevel: 'FULL_PROFESSIONAL' }), AT)),
    ).toEqual(['deux-fois'])
  })

  it('écarte un niveau inconnu de l’échelle, comme une absence de niveau', () => {
    const inconnu = card('inconnu', { languages: [parle('en', 'PARFAIT')] })

    expect(refine([inconnu], form({ language: 'en', languageLevel: 'ELEMENTARY' }), AT)).toEqual([])
  })
})
