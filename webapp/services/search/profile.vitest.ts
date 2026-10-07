import { describe, expect, it } from 'vitest'

import { mergeProfile } from './profile'

import type { CandidateCard } from '#shared/types/candidate-card'
import type { CandidateProfile } from '#shared/types/candidate-profile'

const card = (overrides: Partial<CandidateCard> = {}): CandidateCard =>
  ({
    id: 'c1',
    givenName: 'Y.',
    familyName: null,
    photoUrl: null,
    headline: 'Développeur backend PHP',
    experienceYears: 8,
    educationLevel: null,
    city: 'Paris',
    country: 'FR',
    contactEmail: null,
    phone: null,
    hasEmail: true,
    hasPhone: true,
    lastInteractionAt: null,
    lastInteractionType: null,
    desiredSector: 'Informatique',
    desiredFunction: 'Développeur',
    availability: 'NOTICE_1M',
    returnHorizon: 'M6',
    returnProject: 'Rejoindre un groupe marocain.',
    geoMobility: true,
    hasPiedATerre: true,
    piedATerreCity: 'Casablanca',
    presencePeriods: [{ startDate: '2026-09-12', endDate: '2026-09-26', city: 'Casablanca' }],
    isInMorocco: false,
    skills: ['PHP'],
    isStar: false,
    revealedEmail: false,
    revealedPhone: false,
    revealedResume: false,
    revealedReport: false,
    createdAt: '2026-08-12T10:00:00+00:00',
    updatedAt: '2026-08-12T10:00:00+00:00',
    ...overrides,
  }) as CandidateCard

describe('mergeProfile — la fiche n’est pas encore arrivée', () => {
  it('rend un profil utilisable avec la seule vignette', () => {
    const merged = mergeProfile(card(), null)

    expect(merged.isComplete).toBe(false)
    expect(merged.identity.headline).toBe('Développeur backend PHP')
    expect(merged.morocco.returnProject).toBe('Rejoindre un groupe marocain.')
    expect(merged.expertise.skills).toEqual(['PHP'])
  })

  it('laisse la partie parcours vide, sans la déclarer remplie', () => {
    const merged = mergeProfile(card({ skills: [] }), null)

    expect(merged.career.isEmpty).toBe(true)
    expect(merged.career.workExperiences).toEqual([])
  })

  it('porte déjà les états du profil, dont « rencontré » et « au Maroc »', () => {
    const merged = mergeProfile(card({ isStar: true, isInMorocco: true }), null)

    expect(merged.states.map((state) => state.key)).toEqual(['met', 'morocco'])
  })

  it('n’annonce ni métier ni secteur avant la fiche, qui seule les porte', () => {
    const merged = mergeProfile(card(), null)

    expect(merged.expertise.jobs).toEqual([])
    expect(merged.expertise.sectors).toEqual([])
  })
})

describe('mergeProfile — les deux sources', () => {
  it('garde les quatre différenciants Maroc, que la fiche ne porte pas', () => {
    const profile: CandidateProfile = { id: 'c1', summary: 'Un résumé.' }

    const merged = mergeProfile(card(), profile)

    expect(merged.morocco.returnProject).toBe('Rejoindre un groupe marocain.')
    expect(merged.morocco.hasPiedATerre).toBe(true)
    expect(merged.morocco.piedATerreCity).toBe('Casablanca')
    expect(merged.morocco.isInMorocco).toBe(false)
    expect(merged.identity.summary).toBe('Un résumé.')
    expect(merged.isComplete).toBe(true)
  })

  it('donne le dernier mot à la fiche quand les deux portent la donnée', () => {
    const merged = mergeProfile(card({ city: 'Paris' }), { id: 'c1', city: 'Lyon' })

    expect(merged.identity.city).toBe('Lyon')
  })

  it('retombe sur la vignette quand la fiche omet la clé', () => {
    const merged = mergeProfile(card({ city: 'Paris' }), { id: 'c1' })

    expect(merged.identity.city).toBe('Paris')
  })
})

describe('mergeProfile — le parcours se range', () => {
  it('met le poste en cours en tête, puis du plus récent au plus ancien', () => {
    const merged = mergeProfile(card(), {
      id: 'c1',
      workExperiences: [
        {
          title: 'Ancien',
          companyName: null,
          startDate: '2014-01-01',
          endDate: '2017-01-01',
          source: null,
        },
        {
          title: 'Récent',
          companyName: null,
          startDate: '2017-01-01',
          endDate: '2020-01-01',
          source: null,
        },
        {
          title: 'En cours',
          companyName: null,
          startDate: '2020-01-01',
          endDate: null,
          source: null,
        },
      ],
    })

    expect(merged.career.workExperiences.map((x) => x.title)).toEqual([
      'En cours',
      'Récent',
      'Ancien',
    ])
  })

  it('ne modifie pas le tableau reçu', () => {
    const workExperiences = [
      {
        title: 'A',
        companyName: null,
        startDate: '2014-01-01',
        endDate: '2017-01-01',
        source: null,
      },
      { title: 'B', companyName: null, startDate: '2020-01-01', endDate: null, source: null },
    ]

    mergeProfile(card(), { id: 'c1', workExperiences })

    expect(workExperiences.map((x) => x.title)).toEqual(['A', 'B'])
  })
})

describe('mergeProfile — ce que le serveur envoie n’est pas ce que l’écran affiche', () => {
  it('ne garde des métiers et des secteurs que leur nom, pas leurs compteurs', () => {
    const merged = mergeProfile(card(), {
      id: 'c1',
      jobExperiences: [
        { job: 'Développeur logiciel', months: 43, positions: 4, exactPositions: 3 },
        { job: null, months: 2, positions: 1, exactPositions: 0 },
      ],
      sectorExperiences: [
        { sector: 'INFORMATIQUE', months: 50 },
        { sector: 'CONSEIL_AUDIT', months: 1 },
      ],
    })

    expect(merged.expertise.jobs).toEqual(['Développeur logiciel'])
    expect(merged.expertise.sectors).toEqual(['INFORMATIQUE', 'CONSEIL_AUDIT'])
  })

  it('tire le nom de l’école, qu’elle arrive en texte ou en objet', () => {
    const merged = mergeProfile(card(), {
      id: 'c1',
      educations: [
        {
          school: { id: '73485153', name: 'Université Paris-Saclay' },
          title: 'Master 2',
          startDate: '2021-01-01',
          endDate: '2022-01-01',
        },
        {
          school: 'Université de démonstration',
          title: 'Master Management et SI',
          startDate: '2014-09-01',
          endDate: '2016-06-30',
        },
      ],
    })

    expect(merged.career.educations.map((school) => school.school)).toEqual([
      'Université Paris-Saclay',
      'Université de démonstration',
    ])
  })

  it('ne déclare rempli que ce qui est daté — les mots vivent dans l’autre bloc', () => {
    const merged = mergeProfile(card({ skills: [] }), {
      id: 'c1',
      skills: ['PHP'],
      jobExperiences: [
        { job: 'Développeur logiciel', months: 43, positions: 4, exactPositions: 3 },
      ],
    })

    expect(merged.career.isEmpty).toBe(true)
    expect(merged.expertise.isEmpty).toBe(false)
  })
})

describe('mergeProfile — trois états, pas deux', () => {
  it('distingue une coordonnée verrouillée d’une coordonnée inexistante', () => {
    const merged = mergeProfile(card({ hasEmail: true, hasPhone: false }), { id: 'c1' })

    expect(merged.locked.email.state).toBe('locked')
    expect(merged.locked.phone.state).toBe('absent')
  })

  it('rend la coordonnée une fois dévoilée', () => {
    const merged = mergeProfile(card(), { id: 'c1', hasEmail: true, contactEmail: 'y@example.com' })

    expect(merged.locked.email).toEqual({ state: 'revealed', value: 'y@example.com' })
  })

  it('déclare le CV verrouillé tant qu’il n’est pas payé, puis absent s’il n’y en avait pas', () => {
    expect(mergeProfile(card(), { id: 'c1', revealedResume: false }).locked.resume.state).toBe(
      'locked',
    )
    expect(
      mergeProfile(card(), { id: 'c1', revealedResume: true, resumes: [] }).locked.resume.state,
    ).toBe('absent')
  })

  it('ne propose jamais le compte rendu d’un profil jamais rencontré', () => {
    const merged = mergeProfile(card({ isStar: false }), { id: 'c1', isStar: false })

    expect(merged.locked.report.state).toBe('absent')
  })

  it('propose le compte rendu d’un profil rencontré, tant qu’il n’est pas payé', () => {
    const merged = mergeProfile(card({ isStar: true }), {
      id: 'c1',
      isStar: true,
      revealedReport: false,
    })

    expect(merged.locked.report.state).toBe('locked')
  })
})

describe('mergeProfile — ce qui est gratuit le reste', () => {
  it('rend l’employeur et le profil public sans condition d’achat', () => {
    const merged = mergeProfile(card(), {
      id: 'c1',
      currentCompanyName: 'Société Exemple',
      linkedinUrl: 'https://example.test/in/yassine',
      revealedEmail: false,
    })

    expect(merged.identity.company).toBe('Société Exemple')
    expect(merged.identity.linkedinUrl).toBe('https://example.test/in/yassine')
  })
})
