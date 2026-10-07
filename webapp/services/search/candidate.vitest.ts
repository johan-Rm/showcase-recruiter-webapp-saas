import { describe, expect, it } from 'vitest'

import { describeDecision, jobTenureOf, roleOf, rowSkillsOf } from './candidate'

import type { CandidateCard } from '#shared/types/candidate-card'

const card = (over: Partial<CandidateCard>): CandidateCard =>
  ({ headline: null, experienceYears: 0, city: null, ...over }) as CandidateCard

describe('describeDecision', () => {
  it('assemble poste, ancienneté et ville', () => {
    expect(
      describeDecision(
        card({ headline: 'Directeur technique', experienceYears: 11, city: 'Lyon' }),
      ),
    ).toBe('Directeur technique · 11 ans · Lyon')
  })

  it('omet une ancienneté nulle plutôt que d’écrire « 0 ans »', () => {
    expect(describeDecision(card({ headline: 'Chef de projet', experienceYears: 0 }))).toBe(
      'Chef de projet',
    )
  })

  it('tient avec les seuls champs disponibles', () => {
    expect(describeDecision(card({ city: 'Casablanca' }))).toBe('Casablanca')
  })

  it('rend une chaîne vide quand le profil ne dit rien — jamais des séparateurs orphelins', () => {
    expect(describeDecision(card({}))).toBe('')
  })
})

describe('roleOf', () => {
  it('rend le poste, et le donne pour tel', () => {
    expect(
      roleOf(card({ currentTitle: 'Comptable senior', headline: 'Senior Accountant' })),
    ).toEqual({ text: 'Comptable senior', isFallback: false })
  })

  // 27 % du vivier n'a aucun poste dérivable. Un tiret aurait vidé un quart du tableau de toute
  // indication de métier : on se replie sur l'accroche, et on dit que c'en est une.
  it('se replie sur l’accroche quand le poste manque, et le signale', () => {
    expect(roleOf(card({ headline: 'Maths et Tech' }))).toEqual({
      text: 'Maths et Tech',
      isFallback: true,
    })
  })

  // Le sérialiseur de la carte OMET la clé plutôt que de l'envoyer à `null` : une garde
  // `null !== currentTitle` laisserait passer l'absence et afficherait du vide.
  it('traite la clé ABSENTE comme un poste manquant, pas comme une valeur', () => {
    const sansCle = card({ headline: 'Quality assurance (QA) engineer' })
    expect('currentTitle' in sansCle).toBe(false)
    expect(roleOf(sansCle).isFallback).toBe(true)
  })

  it('ne rend rien quand le profil ne porte ni poste ni accroche', () => {
    expect(roleOf(card({}))).toEqual({ text: null, isFallback: false })
  })

  // Jarvi sert des chaînes que le candidat a saisies : un espace seul y passe pour une valeur.
  it('ignore un poste ou une accroche qui ne contient que des espaces', () => {
    expect(roleOf(card({ currentTitle: '   ', headline: 'Data Analyst' }))).toEqual({
      text: 'Data Analyst',
      isFallback: true,
    })
    expect(roleOf(card({ currentTitle: '  ', headline: '  ' }))).toEqual({
      text: null,
      isFallback: false,
    })
  })

  it('rogne les bords sans toucher au reste de l’intitulé', () => {
    expect(roleOf(card({ currentTitle: '  Tech Lead | Java, Angular  ' })).text).toBe(
      'Tech Lead | Java, Angular',
    )
  })
})

describe('jobTenureOf', () => {
  const withMatches = (matches: CandidateCard['matches']) => card({ matches })

  it('rapporte que le métier cherché est encore exercé', () => {
    expect(
      jobTenureOf(
        withMatches([{ facet: 'job', value: 'dev', matched: true, matchedWhen: 'CURRENT' }]),
      ),
    ).toBe('CURRENT')
  })

  it('rapporte qu’il a été quitté', () => {
    expect(
      jobTenureOf(
        withMatches([{ facet: 'job', value: 'dev', matched: true, matchedWhen: 'PAST' }]),
      ),
    ).toBe('PAST')
  })

  // Le serveur OMET le champ quand le poste du moment est inconnu ou ambigu — le cas le plus
  // fréquent. Son silence ne veut pas dire « passé », et la Table doit se taire avec lui.
  it('ne dit rien quand le serveur ne dit rien', () => {
    expect(jobTenureOf(withMatches([{ facet: 'job', value: 'dev', matched: true }]))).toBeNull()
  })

  it('ne dit rien sur un métier que le profil n’exerce pas', () => {
    expect(
      jobTenureOf(withMatches([{ facet: 'job', value: 'cordiste', matched: false }])),
    ).toBeNull()
  })

  it('ignore les autres facettes, et une carte sans correspondance', () => {
    expect(
      jobTenureOf(withMatches([{ facet: 'q', value: 'vue', matched: true, matchedOn: 'Vue.js' }])),
    ).toBeNull()
    expect(jobTenureOf(card({}))).toBeNull()
  })
})

describe('rowSkillsOf', () => {
  const found = (matchedOn: string) => ({ facet: 'q', value: 'x', matched: true, matchedOn })

  it('remonte en tête la compétence qui a fait entrer le profil', () => {
    expect(
      rowSkillsOf(
        card({ skills: ['AngularJS', 'PostgreSQL', 'Vagrant'], matches: [found('Vagrant')] }),
      ),
    ).toEqual(['Vagrant', 'AngularJS', 'PostgreSQL'])
  })

  it('respecte l’ordre du serveur quand plusieurs compétences ont été trouvées', () => {
    expect(
      rowSkillsOf(
        card({
          skills: ['Java', 'PHP', 'Go', 'Rust'],
          matches: [found('Go'), found('Java')],
        }),
      ),
    ).toEqual(['Go', 'Java', 'PHP'])
  })

  // Mesuré sur le vivier : ce que le serveur reconnaît sur un mot-clé est le plus souvent l'ACCROCHE
  // du candidat — « Software engineer | Backend & DevOps | … » —, et non une de ses compétences. La
  // promotion ne s'active que sur 26 % des profils ; les autres tombent sur ce repli, qui n'est donc
  // pas un cas de bord.
  it('se replie sur les premières compétences quand la correspondance n’en est pas une', () => {
    expect(
      rowSkillsOf(
        card({
          skills: ['Programmation', 'Architecture logicielle', 'DevOps', 'Agile'],
          matches: [found('Software engineer | Backend & DevOps'), found('PHP')],
        }),
      ),
    ).toEqual(['Programmation', 'Architecture logicielle', 'DevOps'])
  })

  it('ne compte une compétence qu’une fois, même trouvée deux fois', () => {
    expect(
      rowSkillsOf(card({ skills: ['Go', 'Rust'], matches: [found('Go'), found('Go')] })),
    ).toEqual(['Go', 'Rust'])
  })

  it('s’arrête à trois, même quand tout correspond', () => {
    expect(
      rowSkillsOf(
        card({
          skills: ['A', 'B', 'C', 'D'],
          matches: [found('A'), found('B'), found('C'), found('D')],
        }),
      ),
    ).toEqual(['A', 'B', 'C'])
  })

  // Seule la facette du mot-clé désigne une compétence : les autres nomment un pays, une langue ou
  // un poste. Un profil entré sur « Développeur logiciel » ne doit pas voir ce libellé promu en
  // étiquette — ce n'en est pas une, même s'il figurait par hasard dans ses compétences.
  it('n’écoute que la facette du mot-clé', () => {
    expect(
      rowSkillsOf(
        card({
          skills: ['Java', 'Go'],
          matches: [{ facet: 'job', value: 'go', matched: true, matchedOn: 'Go' }],
        }),
      ),
    ).toEqual(['Java', 'Go'])
  })

  it('ignore une correspondance non satisfaite', () => {
    expect(
      rowSkillsOf(
        card({
          skills: ['Java', 'Go'],
          matches: [{ facet: 'q', value: 'go', matched: false, matchedOn: 'Go' }],
        }),
      ),
    ).toEqual(['Java', 'Go'])
  })

  // La cellule ne pose PAS de seconde ligne sur un tableau vide : une ligne vide sous l'intitulé se
  // lit comme une donnée manquante, alors qu'il n'y a rien à manquer.
  it('ne rend rien quand le profil n’a aucune compétence', () => {
    expect(rowSkillsOf(card({ skills: [], matches: [found('Go')] }))).toEqual([])
    expect(rowSkillsOf(card({}))).toEqual([])
  })
})
