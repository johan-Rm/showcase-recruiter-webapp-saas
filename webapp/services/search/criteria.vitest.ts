import { describe, expect, it } from 'vitest'

import {
  autoName,
  confrontCriteria,
  emptySecondaryForm,
  fromSecondaryForm,
  mergeWithSecondary,
  toSecondaryForm,
  criterionText,
  describeCriterion,
  fromAiCriteria,
  fromPrimaryForm,
  mergeWithPrimary,
  orderCriteria,
  scoreOf,
  toPrimaryForm,
  withoutCriterion,
} from './criteria'

import type { AiSearchCriteria } from './criteria'
import type { CandidateCard } from '#shared/types/candidate-card'

/**
 * Un traducteur de test qui rend la CLÉ, et son paramètre de pluriel quand il y en a un.
 *
 * On vérifie ainsi quelle clé a été choisie, ce qui est la seule chose que cette couche décide —
 * la traduction elle-même appartient au catalogue.
 */
const translate = (key: string, params?: Record<string, unknown>): string =>
  undefined === params?.count ? key : `${key}(${String(params.count)})`

const EMPTY_AI: AiSearchCriteria = {
  keywords: [],
  country: null,
  city: null,
  languages: [],
  availability: null,
  returnHorizon: null,
  geoMobility: null,
}

describe('fromPrimaryForm', () => {
  it('pose les critères dans l’ordre de lecture de l’écran', () => {
    expect(
      fromPrimaryForm({
        jobTitle: 'Comptable',
        keywords: ['Sage', 'Consolidation'],
        experienceMin: 5,
        educationLevel: 'MASTER',
      }),
    ).toEqual([
      { facet: 'job', value: 'Comptable', group: 'metier' },
      { facet: 'experienceMin', value: '5', group: 'experience' },
      { facet: 'educationLevel', value: 'MASTER', group: 'etudes' },
      { facet: 'q', value: 'Sage', group: 'motsCles' },
      { facet: 'q', value: 'Consolidation', group: 'motsCles' },
    ])
  })

  it('ignore les champs vides plutôt que de filtrer sur rien', () => {
    expect(
      fromPrimaryForm({ jobTitle: '  ', keywords: [' '], experienceMin: 0, educationLevel: '' }),
    ).toEqual([])
  })

  // `0` porte le « peu importe » : poser le critère quand même l'afficherait comme un filtre actif
  // dans la barre haute, alors que le moteur l'ignore.
  it('ne pose aucun critère d’expérience à zéro', () => {
    const criteria = fromPrimaryForm({
      jobTitle: 'Comptable',
      keywords: [],
      experienceMin: 0,
      educationLevel: '',
    })

    expect(criteria.some((item) => 'experienceMin' === item.facet)).toBe(false)
  })
})

describe('fromAiCriteria', () => {
  // Un critère PAR terme : le moteur les accumule et les relie en OU. Joints en une chaîne, ils se
  // chercheraient comme une expression et ne trouveraient personne.
  it('pose un mot-clé par terme proposé', () => {
    const criteria: AiSearchCriteria = {
      ...EMPTY_AI,
      keywords: [{ terms: ['développeur', 'developer'] }, { terms: ['PHP'] }],
    }

    expect(fromAiCriteria(criteria)).toEqual([
      { facet: 'q', value: 'développeur', group: 'motsCles' },
      { facet: 'q', value: 'developer', group: 'motsCles' },
      { facet: 'q', value: 'PHP', group: 'motsCles' },
    ])
  })

  it('ne pose que la PREMIÈRE langue, avec son niveau', () => {
    const criteria: AiSearchCriteria = {
      ...EMPTY_AI,
      languages: [
        { language: 'nl', minProficiency: 'PROFESSIONAL_WORKING' },
        { language: 'en', minProficiency: null },
      ],
    }

    expect(fromAiCriteria(criteria)).toEqual([
      { facet: 'language', value: 'nl', group: 'langues' },
      { facet: 'languageLevel', value: 'PROFESSIONAL_WORKING', group: 'langues' },
    ])
  })

  it('n’envoie pas ce que le recruteur a écarté à l’écran', () => {
    const criteria: AiSearchCriteria = {
      ...EMPTY_AI,
      keywords: [{ terms: ['PHP'] }, { terms: ['Symfony'] }],
      country: 'BE',
      availability: 'IMMEDIATE',
    }

    expect(fromAiCriteria(criteria, new Set(['kw:0', 'country']))).toEqual([
      { facet: 'q', value: 'Symfony', group: 'motsCles' },
      { facet: 'availability', value: 'IMMEDIATE', group: 'dispo' },
    ])
  })

  it('ne pose la mobilité que quand elle est demandée', () => {
    expect(fromAiCriteria({ ...EMPTY_AI, geoMobility: false })).toEqual([])
    expect(fromAiCriteria({ ...EMPTY_AI, geoMobility: true })).toEqual([
      { facet: 'geoMobility', value: 'true', group: 'mobilite' },
    ])
  })

  it('rend une liste vide sans critère compris', () => {
    expect(fromAiCriteria(null)).toEqual([])
    expect(fromAiCriteria(EMPTY_AI)).toEqual([])
  })
})

const criterion = (facet: string, value: string, group = 'g') => ({ facet, value, group })

describe('describeCriterion', () => {
  // Le défaut qui a ouvert la task : la puce affichait `LICENCE`, la valeur d'énumération, alors
  // que sa traduction existait depuis toujours.
  it('renvoie une énumération vers son catalogue', () => {
    expect(describeCriterion(criterion('educationLevel', 'LICENCE'))).toEqual({
      labelKey: 'search.criterion.facet.educationLevel',
      wanted: { key: 'search.education.LICENCE', text: 'LICENCE', count: null },
    })

    expect(criterionText(criterion('availability', 'IMMEDIATE'), translate)).toBe(
      'search.availability.IMMEDIATE',
    )
    expect(criterionText(criterion('returnHorizon', 'M6'), translate)).toBe('search.horizon.M6')
    expect(criterionText(criterion('languageLevel', 'FULL_PROFESSIONAL'), translate)).toBe(
      'search.ai.proficiency.FULL_PROFESSIONAL',
    )
  })

  // « ✓ 1 » ne portait ni son unité ni son sens : c'est une BORNE BASSE, pas une durée.
  it('donne son unité et son sens à la borne d’expérience', () => {
    expect(criterionText(criterion('experienceMin', '3'), translate)).toBe(
      'search.criterion.value.experienceMin(3)',
    )
  })

  it('affiche telle quelle une borne d’expérience illisible plutôt que de compter sur rien', () => {
    expect(criterionText(criterion('experienceMin', 'beaucoup'), translate)).toBe('beaucoup')
  })

  // Une puce « Oui » ne dit pas de quoi elle parle. Un booléen posé se lit par son LIBELLÉ — c'est
  // le seul cas où la valeur lisible n'est pas la valeur du critère.
  it('nomme un booléen par son libellé plutôt que par « oui »', () => {
    expect(criterionText(criterion('star', 'true'), translate)).toBe('search.criterion.facet.star')
    expect(criterionText(criterion('geoMobility', 'true'), translate)).toBe(
      'search.criterion.facet.geoMobility',
    )
  })

  // Le bloc de confrontation, lui, garde le couple : il a une colonne pour le libellé et une pour
  // la valeur.
  it('garde le couple libellé + valeur pour la confrontation', () => {
    expect(describeCriterion(criterion('star', 'true'))).toEqual({
      labelKey: 'search.criterion.facet.star',
      wanted: { key: 'search.criterion.value.yes', text: 'true', count: null },
    })
  })

  it('affiche telle quelle une valeur saisie à la main', () => {
    expect(criterionText(criterion('job', 'Comptable'), translate)).toBe('Comptable')
    expect(criterionText(criterion('presenceCity', 'Casablanca'), translate)).toBe('Casablanca')
  })

  // Le libellé nomme la NATURE du critère, sur sa propre ligne sous la valeur : un mot-clé en a
  // une. Une facette inconnue n'en a pas — elle afficherait sa clé de traduction en clair.
  it("libelle un mot-clé, mais pas une facette qu'il ne connaît pas", () => {
    expect(describeCriterion(criterion('q', 'vue')).labelKey).toBe('search.criterion.facet.q')
    expect(describeCriterion(criterion('inventée', 'x')).labelKey).toBeNull()
  })
})

describe('autoName', () => {
  it('suit un ordre FIXE, pas celui de sélection', () => {
    // Les critères sont posés à l'envers : le nom les remet dans son ordre à lui. C'est ce qui
    // permet de comparer deux titres du regard.
    expect(
      autoName(
        [
          criterion('educationLevel', 'Bac+2', 'etudes'),
          criterion('experienceMin', '2 ans et plus', 'experience'),
          criterion('job', 'Développeur logiciel', 'metier'),
        ],
        translate,
      ),
      // Le traducteur de test rend la clé : ce qui compte ici est l'ORDRE et la présence, pas le
      // libellé, qui est couvert ailleurs.
    ).toBe(
      'Développeur logiciel · search.criterion.value.experienceMin(2) · search.education.Bac+2',
    )
  })

  it('ne retient que le PREMIER mot-clé', () => {
    // Le groupe des mots-clés est le seul sans taille naturelle : dix-neuf d'entre eux donnaient un
    // titre de vingt mots. C'est lui, et lui seul, qui borne le titre.
    expect(
      autoName(
        [
          criterion('job', 'Développeur logiciel', 'metier'),
          criterion('q', 'react', 'motsCles'),
          criterion('q', 'vue', 'motsCles'),
          criterion('q', 'kubernetes', 'motsCles'),
        ],
        translate,
      ),
    ).toBe('Développeur logiciel · react')
  })

  it('ne laisse plus tomber les études quand quatre groupes sont posés', () => {
    // L'ancien plafond gardait les trois PREMIERS groupes rencontrés : les études, posées en
    // quatrième, disparaissaient.
    expect(
      autoName(
        [
          criterion('job', 'Développeur logiciel', 'metier'),
          criterion('q', 'react', 'motsCles'),
          criterion('experienceMin', '2 ans et plus', 'experience'),
          criterion('educationLevel', 'Bac+2', 'etudes'),
        ],
        translate,
      ),
      // Le mot-clé ferme la marche : il vient APRÈS ce qu'on exige de la personne. Ce que ce test
      // protège est ailleurs — les études survivent à un quatrième groupe.
    ).toBe(
      'Développeur logiciel · search.criterion.value.experienceMin(2) · search.education.Bac+2 · react',
    )
  })

  it('écarte les groupes qui ne sont pas nommés', () => {
    expect(
      autoName(
        [criterion('sector', 'Tech', 'metier'), criterion('country', 'France', 'localisation')],
        translate,
      ),
    ).toBe('Tech')
  })

  it('retombe sur ce qui reste quand aucun groupe nommé n’est posé', () => {
    // « des gens au Maroc qui parlent anglais » n'a ni métier ni mot-clé : la règle stricte
    // rendrait la carte muette alors que ses critères disent quelque chose.
    expect(
      autoName(
        [
          criterion('country', 'Maroc', 'localisation'),
          criterion('language', 'Anglais', 'langues'),
        ],
        translate,
      ),
    ).toBe('Maroc · Anglais')
  })

  it('joint les valeurs d’un même groupe par une virgule', () => {
    expect(
      autoName(
        [criterion('job', 'Comptable', 'metier'), criterion('q', 'Sage', 'metier')],
        translate,
      ),
    ).toBe('Comptable, Sage')
  })

  it('nomme le séjour au Maroc, une seule fois en tête de segment', () => {
    expect(
      autoName(
        [
          criterion('presenceWithinDays', 'Maintenant', 'presence'),
          criterion('presenceCity', 'Casablanca', 'presence'),
        ],
        translate,
      ),
    ).toBe('Maroc Maintenant, Casablanca')
  })

  it('s’arrête à trois segments et compte le reste', () => {
    expect(
      autoName(
        [
          criterion('a', 'Un', 'g1'),
          criterion('b', 'Deux', 'g2'),
          criterion('c', 'Trois', 'g3'),
          criterion('d', 'Quatre', 'g4'),
          criterion('e', 'Cinq', 'g5'),
        ],
        translate,
      ),
    ).toBe('Un · Deux · Trois +2')
  })

  // Le nom se lit juste au-dessus des puces qu'il résume : deux graphies du même critère à dix
  // pixels d'écart se lisent comme deux critères.
  it('nomme avec les mêmes libellés que la barre haute', () => {
    expect(
      autoName(
        [
          criterion('experienceMin', '1', 'experience'),
          criterion('educationLevel', 'LICENCE', 'etudes'),
        ],
        translate,
      ),
    ).toBe('search.criterion.value.experienceMin(1) · search.education.LICENCE')
  })

  it('ne nomme rien tant qu’aucun critère n’est posé', () => {
    expect(autoName([], translate)).toBe('')
    expect(autoName([criterion('job', '  ', 'metier')], translate)).toBe('')
  })
})

describe('orderCriteria', () => {
  it("range les critères dans l'ordre canonique, quel que soit celui d'enregistrement", () => {
    // Une recherche créée à l'écran pose ses critères dans l'ordre où le recruteur les saisit ; le
    // catalogue de démonstration, lui, les pose déjà rangés. L'affichage ne doit dépendre ni de
    // l'un ni de l'autre.
    const range = orderCriteria([
      criterion('q', 'prince2', 'motsCles'),
      criterion('educationLevel', 'Master', 'etudes'),
      criterion('job', 'Chef de projet', 'metier'),
      criterion('experienceMin', '5 ans et plus', 'experience'),
    ])

    expect(range.map((item) => item.group)).toStrictEqual([
      'metier',
      'experience',
      'etudes',
      'motsCles',
    ])
  })

  it("garde l'ordre de saisie entre critères d'un même groupe", () => {
    // Le tri est stable : cinq mots-clés restent dans l'ordre où ils ont été posés, et c'est ce qui
    // permet au premier de nommer la recherche.
    const range = orderCriteria([
      criterion('q', 'prince2', 'motsCles'),
      criterion('q', 'kpi', 'motsCles'),
      criterion('job', 'Chef de projet', 'metier'),
      criterion('q', 'gestion de projet', 'motsCles'),
    ])

    expect(range.map((item) => item.value)).toStrictEqual([
      'Chef de projet',
      'prince2',
      'kpi',
      'gestion de projet',
    ])
  })

  it('ne perd aucun critère et relègue les groupes inconnus en fin', () => {
    // Un séjour au Maroc ou une langue n'appartient à aucun des quatre groupes nommés : il reste
    // affiché, après eux, plutôt que d'être écarté.
    const melange = [
      criterion('presenceCity', 'Casablanca', 'presence'),
      criterion('job', 'Juriste', 'metier'),
      criterion('language', 'fr', 'langues'),
      criterion('q', 'droit des affaires', 'motsCles'),
    ]

    const range = orderCriteria(melange)

    expect(range).toHaveLength(melange.length)
    expect(range.map((item) => item.group)).toStrictEqual([
      'metier',
      'motsCles',
      'presence',
      'langues',
    ])
  })

  it('ne modifie pas la liste reçue', () => {
    // La couche est pure : trier ne réordonne pas les critères de l'appelant, qui les envoie au
    // serveur dans leur ordre à lui.
    const origine = [
      criterion('q', 'scrum', 'motsCles'),
      criterion('job', 'Product Owner', 'metier'),
    ]

    orderCriteria(origine)

    expect(origine.map((item) => item.group)).toStrictEqual(['motsCles', 'metier'])
  })
})

describe('confrontCriteria', () => {
  it("rend les critères dans l'ordre canonique, pas dans celui d'enregistrement", () => {
    // Le panneau « Ce profil face à vos critères » affiche ce que rend cette fonction : c'est ici
    // que l'ordre se joue, sans quoi l'expérience se lisait derrière cinq mots-clés.
    const outcomes = confrontCriteria(
      [
        criterion('q', 'prince2', 'motsCles'),
        criterion('experienceMin', '5 ans et plus', 'experience'),
        criterion('job', 'Chef de projet', 'metier'),
      ],
      null,
    )

    expect(outcomes.map((outcome) => outcome.criterion.group)).toStrictEqual([
      'metier',
      'experience',
      'motsCles',
    ])
  })

  const card = (over: Partial<CandidateCard> = {}): CandidateCard =>
    ({
      experienceYears: 3,
      educationLevel: 'MASTER',
      availability: null,
      returnHorizon: null,
      geoMobility: false,
      hasPiedATerre: false,
      isStar: false,
      country: 'FR',
      desiredSector: null,
      desiredFunction: null,
      matches: [],
      ...over,
    }) as CandidateCard

  // Le serveur OMET `matchedOn` quand le critère échoue : le fixture le reproduit, sans quoi le test
  // vérifierait une charge utile qui n'existe pas.
  const match = (facet: string, value: string, matched: boolean, matchedOn?: string) =>
    undefined === matchedOn ? { facet, value, matched } : { facet, value, matched, matchedOn }

  // RIEN D'AUTRE NE PORTE LES CRITÈRES à l'écran. Sans ce cas, une recherche qui ne ramène personne
  // n'en afficherait plus aucun — donc plus aucun moyen de retirer celui qui est de trop, au moment
  // précis où l'on veut le faire.
  it('rend les critères SANS verdict quand il n’y a pas de profil à confronter', () => {
    const outcomes = confrontCriteria(
      [criterion('job', 'developpeur'), criterion('experienceMin', '5')],
      null,
    )

    expect(outcomes).toHaveLength(2)
    expect(outcomes.map((outcome) => outcome.matched)).toEqual([null, null])
    expect(outcomes.map((outcome) => outcome.found)).toEqual([null, null])
    // Le critère reste entier : c'est lui qu'on renvoie au retrait.
    expect(outcomes[0]?.criterion.value).toBe('developpeur')
  })

  // `matches` à `null` — le serveur n'a rien confronté — se traite comme l'absence de profil : il n'y
  // a pas de verdict, ce qui ne veut pas dire que les critères ont échoué.
  it('traite une carte sans correspondances comme un profil absent', () => {
    const outcomes = confrontCriteria([criterion('job', 'developpeur')], card({ matches: null }))

    expect(outcomes).toHaveLength(1)
    expect(outcomes[0]?.matched).toBeNull()
  })

  /** Le métier reconnu, avec le « quand » que le serveur y attache. */
  const jobMatch = (value: string, on: string, when?: 'CURRENT' | 'PAST') =>
    undefined === when
      ? { facet: 'job', value, matched: true, matchedOn: on }
      : { facet: 'job', value, matched: true, matchedOn: on, matchedWhen: when }

  it('rapporte qu’un métier reconnu est encore exercé', () => {
    const [outcome] = confrontCriteria(
      [criterion('job', 'developpeur')],
      card({ matches: [jobMatch('developpeur', 'Développeur logiciel', 'CURRENT')] }),
    )

    expect(outcome?.tenure).toBe('CURRENT')
  })

  it('rapporte qu’il l’a quitté pour un autre', () => {
    const [outcome] = confrontCriteria(
      [criterion('job', 'developpeur')],
      card({ matches: [jobMatch('developpeur', 'Développeur logiciel', 'PAST')] }),
    )

    expect(outcome?.tenure).toBe('PAST')
  })

  // Le poste du moment n'est connu que sur la moitié du vivier, et il est parfois ambigu : le serveur
  // OMET alors le champ. Son absence ne veut pas dire « passé », elle veut dire qu'on ne sait pas —
  // et l'écran doit se taire avec lui.
  it('ne dit rien quand le serveur ne dit rien', () => {
    const [outcome] = confrontCriteria(
      [criterion('job', 'developpeur')],
      card({ matches: [jobMatch('developpeur', 'Développeur logiciel')] }),
    )

    expect(outcome?.tenure).toBeNull()
  })

  // Un métier qu'on n'exerce pas n'a ni présent ni passé. Le serveur l'omet déjà ; on ne s'en remet
  // pas à lui pour autant, les deux règles doivent tomber d'accord.
  it('ne dit rien sur un critère non satisfait', () => {
    const [outcome] = confrontCriteria(
      [criterion('job', 'cordiste')],
      card({ matches: [{ facet: 'job', value: 'cordiste', matched: false }] }),
    )

    expect(outcome?.matched).toBe(false)
    expect(outcome?.tenure).toBeNull()
  })

  /** Un mot-clé satisfait, avec l'endroit du profil où le serveur l'a vu. */
  const keywordMatch = (value: string, on: string, at?: 'HEADLINE' | 'SKILL' | 'JOB_TITLE') =>
    undefined === at
      ? { facet: 'q', value, matched: true, matchedOn: on }
      : { facet: 'q', value, matched: true, matchedOn: on, matchedIn: at }

  // Un terme lu dans une compétence déclarée ne vaut pas celui lu dans l'accroche que le candidat
  // s'écrit : c'est la seule facette à fouiller plusieurs endroits, donc la seule dont la
  // correspondance se juge.
  it.each([
    ['HEADLINE' as const, 'Développeur backend PHP'],
    ['SKILL' as const, 'PHP'],
    ['JOB_TITLE' as const, 'Développeur PHP senior'],
  ])('rapporte que le mot-clé a été vu dans %s', (source, on) => {
    const [outcome] = confrontCriteria(
      [criterion('q', 'php', 'motsCles')],
      card({ matches: [keywordMatch('php', on, source)] }),
    )

    expect(outcome?.source).toBe(source)
  })

  // Toutes les autres facettes n'ont qu'un endroit possible : le serveur n'envoie rien, et
  // l'annoncer n'apprendrait rien.
  it('ne rapporte aucun endroit sur une facette qui n’en a qu’un', () => {
    const [outcome] = confrontCriteria(
      [criterion('educationLevel', 'LICENCE')],
      card({ matches: [match('educationLevel', 'LICENCE', true, 'MASTER')] }),
    )

    expect(outcome?.source).toBeNull()
  })

  // Le sérialiseur OMET la clé, il ne l'envoie pas vide : une garde sur le seul `null` laisserait
  // passer `undefined` jusqu'à la clé de traduction, et la bulle afficherait son chemin brut.
  it('survit à un mot-clé dont le serveur n’a pas envoyé l’endroit', () => {
    const [outcome] = confrontCriteria(
      [criterion('q', 'php', 'motsCles')],
      card({ matches: [keywordMatch('php', 'PHP')] }),
    )

    expect(outcome?.source).toBeNull()
  })

  // Un mot qu'on n'a pas trouvé n'a été vu nulle part. Même prudence que sur le « quand » : le
  // serveur l'omet déjà, les deux règles doivent tomber d'accord.
  it('ne rapporte aucun endroit sur un mot-clé non satisfait', () => {
    const [outcome] = confrontCriteria(
      [criterion('q', 'terraform', 'motsCles')],
      card({ matches: [{ facet: 'q', value: 'terraform', matched: false }] }),
    )

    expect(outcome?.source).toBeNull()
  })

  it('ne rapporte aucun endroit quand il n’y a pas de profil à confronter', () => {
    const [outcome] = confrontCriteria([criterion('q', 'php', 'motsCles')], null)

    expect(outcome?.source).toBeNull()
  })

  // La règle de la task : « ✓ LICENCE » sur un profil Master cochait le critère sans jamais dire ce
  // que le profil valait — et la règle serveur est « au moins ce niveau ».
  it('oppose au critère la valeur que le profil porte', () => {
    const [outcome] = confrontCriteria(
      [criterion('educationLevel', 'LICENCE')],
      card({ matches: [match('educationLevel', 'LICENCE', true, 'MASTER')] }),
    )

    expect(outcome?.matched).toBe(true)
    expect(outcome?.wanted.key).toBe('search.education.LICENCE')
    expect(outcome?.found).toEqual({ key: 'search.education.MASTER', text: 'MASTER', count: null })
  })

  // Le serveur met `matchedOn` à `null` dès que le critère échoue, et c'est précisément là qu'on
  // veut la valeur du profil : « demandé cinq ans, profil trois ans » est ce qui fait trancher.
  it('lit la valeur du profil sur la carte même quand le critère échoue', () => {
    const [outcome] = confrontCriteria(
      [criterion('experienceMin', '5')],
      card({ experienceYears: 3, matches: [match('experienceMin', '5', false)] }),
    )

    expect(outcome?.matched).toBe(false)
    expect(outcome?.found).toEqual({ key: 'search.profile.years', text: '3', count: 3 })
  })

  // Le libellé DU PROFIL, jamais celui qui a été cherché : « vue » a trouvé « Vue.js », et la chaîne
  // cherchée n'est nulle part dans la fiche.
  it('rapporte ce qu’un mot-clé a trouvé, et rien quand il n’a rien trouvé', () => {
    const outcomes = confrontCriteria(
      [criterion('q', 'vue'), criterion('q', 'react')],
      card({
        matches: [match('q', 'vue', true, 'Vue.js'), match('q', 'react', false)],
      }),
    )

    expect(outcomes.map((outcome) => outcome.found?.text ?? null)).toEqual(['Vue.js', null])
  })

  // Le piège du contrat : le champ est ABSENT, pas à `null`. Une garde sur le seul `null` laissait
  // passer l'absence, et la ligne affichait une valeur blanche au lieu de son verdict — vu à l'écran
  // sur le mot-clé « postgres ».
  it('traite un matchedOn absent comme une absence, pas comme une valeur', () => {
    const [outcome] = confrontCriteria(
      [criterion('q', 'postgres')],
      card({ matches: [{ facet: 'q', value: 'postgres', matched: false }] }),
    )

    expect(outcome?.found).toBeNull()
  })

  // On ne montre pas ce qu'on n'a pas : la langue, le secteur d'expérience et les fenêtres de
  // présence n'ont aucune contrepartie sur la carte. La ligne dit alors le verdict, et c'est tout.
  it('ne rend aucune valeur de profil sur une facette que la carte ne porte pas', () => {
    const outcomes = confrontCriteria(
      [criterion('language', 'ar'), criterion('experienceSector', 'banque')],
      card({
        matches: [match('language', 'ar', false), match('experienceSector', 'banque', false)],
      }),
    )

    expect(outcomes).toHaveLength(2)
    expect(outcomes.every((outcome) => null === outcome.found)).toBe(true)
  })

  // « Disponibilité · Disponible immédiatement · Disponible immédiatement » remplit la colonne pour
  // n'apprendre rien. Constaté à l'écran sur la recherche « Finance France vers Casablanca ».
  it('ne répète pas une valeur de profil identique à celle demandée', () => {
    const [outcome] = confrontCriteria(
      [criterion('availability', 'IMMEDIATE')],
      card({
        availability: 'IMMEDIATE',
        matches: [match('availability', 'IMMEDIATE', true, 'IMMEDIATE')],
      }),
    )

    expect(outcome?.matched).toBe(true)
    expect(outcome?.found).toBeNull()
  })

  it('garde l’ordre des critères, celui de la barre haute', () => {
    const outcomes = confrontCriteria(
      [criterion('job', 'dev'), criterion('experienceMin', '1'), criterion('q', 'vue')],
      card({
        matches: [
          match('job', 'dev', true, 'Frontend developer'),
          match('experienceMin', '1', true),
          match('q', 'vue', true, 'Vue.js'),
        ],
      }),
    )

    expect(outcomes.map((outcome) => outcome.criterion.facet)).toEqual([
      'job',
      'experienceMin',
      'q',
    ])
  })

  // `languageLevel` précise `language`, qui porte déjà le niveau atteint : le serveur ne l'évalue
  // pas. L'annoncer non trouvé serait un verdict qu'on n'a pas.
  it('écarte un critère que le serveur n’a pas évalué', () => {
    const outcomes = confrontCriteria(
      [criterion('language', 'nl'), criterion('languageLevel', 'PROFESSIONAL_WORKING')],
      card({ matches: [match('language', 'nl', true)] }),
    )

    expect(outcomes.map((outcome) => outcome.criterion.facet)).toEqual(['language'])
  })

  // ELLE REND LES CRITÈRES MÊME SANS PROFIL, et c'est le contraire de ce qu'on attendrait d'une
  // confrontation : se taire ici rend le critère irretirable sur une recherche qui ne ramène
  // personne, puisque rien d'autre ne les porte.
  it('rend les critères sans profil ni hors d’une recherche, au lieu de se taire', () => {
    expect(confrontCriteria([criterion('q', 'vue')], null)).toHaveLength(1)
    expect(confrontCriteria([criterion('q', 'vue')], card({ matches: null }))).toHaveLength(1)
  })
})

describe('scoreOf', () => {
  let rang = 0

  // Une valeur DIFFÉRENTE à chaque appel : les critères servent de clé dans le gabarit, et deux
  // identiques dans une même liste rendraient le cas de test ambigu.
  const outcome = (matched: boolean | null, tenure: 'CURRENT' | 'PAST' | null = null) =>
    ({
      criterion: criterion('q', `mot-${(rang += 1)}`, 'motsCles'),
      labelKey: null,
      wanted: { key: null, text: 'x', count: null },
      matched,
      found: null,
      tenure,
    }) as ReturnType<typeof confrontCriteria>[number]

  it("ne rend rien quand il n'y a aucun critère", () => {
    // Un anneau « 0 sur 0 » ne dit rien, et un palier posé dessus serait un jugement sur le vide.
    expect(scoreOf([])).toBeNull()
  })

  it("ne rend rien quand il n'y a aucun profil à confronter", () => {
    // `matched` nul n'est pas « non satisfait » : c'est l'absence de profil. « 0 sur 6 » se lirait
    // comme un mauvais score là où il n'y a rien à compter.
    expect(scoreOf([outcome(null), outcome(null)])).toBeNull()
  })

  it('compte un poste PASSÉ comme satisfait', () => {
    // Le candidat a exercé ce métier et le moteur a retenu le profil dessus : un score qui
    // l'ignorerait contredirait le tri qui a ramené ce profil. La ligne le nuance, pas le score.
    expect(scoreOf([outcome(true, 'PAST'), outcome(false), outcome(false)])).toStrictEqual({
      met: 1,
      total: 3,
      tier: 'medium',
    })
  })

  it('bascule de faible à moyenne au tiers, et de moyenne à forte aux deux tiers', () => {
    const surSix = (met: number) =>
      scoreOf([
        ...Array.from({ length: met }, () => outcome(true)),
        ...Array.from({ length: 6 - met }, () => outcome(false)),
      ])

    expect(surSix(0)?.tier).toBe('low')
    expect(surSix(1)?.tier).toBe('low')
    expect(surSix(2)?.tier).toBe('medium')
    expect(surSix(3)?.tier).toBe('medium')
    expect(surSix(4)?.tier).toBe('high')
    expect(surSix(6)?.tier).toBe('high')
  })

  it('rend les deux nombres tels quels, sans arrondi ni plafond', () => {
    // Ce sont eux qui s'écrivent dans l'anneau et dans la phrase : les dériver deux fois les ferait
    // diverger le jour où l'un des deux changerait.
    expect(scoreOf([outcome(true), outcome(false), outcome(true)])).toStrictEqual({
      met: 2,
      total: 3,
      tier: 'high',
    })
  })
})

describe('filtres secondaires', () => {
  const filled = () => ({
    ...emptySecondaryForm(),
    presenceWithinDays: 30,
    presenceCity: 'Casablanca',
    geoMobility: true,
    hasPiedATerre: true,
    returnHorizon: 'M6',
    availability: 'IMMEDIATE',
    sector: 'Informatique',
    experienceSector: 'BANQUE_FINANCE',
    jobFunction: 'Développeur',
    language: 'ar',
    languageLevel: 'FULL_PROFESSIONAL',
    star: true,
    country: 'BE',
  })

  it('ne pose aucun critère tant que rien n’est demandé', () => {
    expect(fromSecondaryForm(emptySecondaryForm())).toEqual([])
  })

  // Les douze filtres du moteur, et EUX SEULS : un champ de plus donnerait une case sans effet.
  it('pose une facette par filtre renseigné', () => {
    expect(fromSecondaryForm(filled()).map((item) => item.facet)).toEqual([
      'presenceWithinDays',
      'presenceCity',
      'geoMobility',
      'hasPiedATerre',
      'returnHorizon',
      'availability',
      'sector',
      'experienceSector',
      'function',
      'language',
      'languageLevel',
      'star',
      'country',
    ])
  })

  // Un interrupteur décoché veut dire « peu importe », pas « je veux les immobiles ». Posé, le
  // critère filtrerait — et s'afficherait en puce comme un filtre actif.
  it('ne pose rien pour un interrupteur décoché', () => {
    const off = { ...emptySecondaryForm(), geoMobility: false, hasPiedATerre: false, star: false }

    expect(fromSecondaryForm(off)).toEqual([])
  })

  // Même règle qu'`experienceMin` à zéro : un seuil à zéro n'est pas un filtre.
  it('ne pose aucune fenêtre de présence à zéro', () => {
    expect(fromSecondaryForm({ ...emptySecondaryForm(), presenceWithinDays: 0 })).toEqual([])
  })

  /**
   * LE VA-ET-VIENT, et c'est le test qui compte.
   *
   * Une facette perdue entre la lecture et la réécriture efface un filtre en silence : la recherche
   * change, et rien à l'écran ne le dit. Aucune capture ne montrerait ça.
   */
  it('relit puis réécrit à l’identique', () => {
    const criteria = fromSecondaryForm(filled())

    expect(fromSecondaryForm(toSecondaryForm(criteria))).toEqual(criteria)
    expect(toSecondaryForm(criteria)).toEqual(filled())
  })

  it('repart de zéro sur une recherche qui ne porte aucun filtre secondaire', () => {
    expect(toSecondaryForm([criterion('job', 'Comptable', 'metier')])).toEqual(emptySecondaryForm())
  })

  // Le pendant de `mergeWithPrimary` : ajuster un pays ne doit pas effacer un intitulé de poste, ni
  // un mot-clé posé par la recherche en langage naturel.
  it('préserve les facettes que le formulaire ne possède pas', () => {
    const existing = [
      criterion('job', 'Comptable', 'metier'),
      criterion('q', 'Sage', 'motsCles'),
      criterion('country', 'FR', 'localisation'),
    ]

    const merged = mergeWithSecondary(existing, { ...emptySecondaryForm(), country: 'BE' })

    expect(merged).toEqual([
      criterion('job', 'Comptable', 'metier'),
      criterion('q', 'Sage', 'motsCles'),
      criterion('country', 'BE', 'localisation'),
    ])
  })

  // Les groupes composent le nom automatique : deux libellés pour la même famille le couperaient en
  // deux segments.
  it('range chaque filtre dans le groupe que les autres moteurs utilisent déjà', () => {
    const groups = Object.fromEntries(
      fromSecondaryForm(filled()).map((item) => [item.facet, item.group]),
    )

    expect(groups.presenceCity).toBe('presence')
    expect(groups.hasPiedATerre).toBe('presence')
    expect(groups.geoMobility).toBe('mobilite')
    expect(groups.availability).toBe('dispo')
    expect(groups.returnHorizon).toBe('retour')
    expect(groups.country).toBe('localisation')
    expect(groups.language).toBe('langues')
    expect(groups.function).toBe('metier')
  })
})

describe('édition des critères', () => {
  const aiBorn = [
    criterion('q', 'PHP', 'motsCles'),
    criterion('country', 'BE', 'localisation'),
    criterion('language', 'nl', 'langues'),
  ]

  it('pré-remplit le formulaire avec ce qu’il sait porter', () => {
    expect(
      toPrimaryForm([
        ...aiBorn,
        criterion('q', 'Symfony', 'motsCles'),
        criterion('experienceMin', '5', 'experience'),
        criterion('educationLevel', 'MASTER', 'etudes'),
      ]),
    ).toEqual({
      jobTitle: '',
      // TOUS les mots-clés : n'en relire qu'un en perdrait à chaque ajustement.
      keywords: ['PHP', 'Symfony'],
      experienceMin: 5,
      educationLevel: 'MASTER',
    })
  })

  it('repart de zéro quand aucune ancienneté n’est posée', () => {
    expect(toPrimaryForm(aiBorn).experienceMin).toBe(0)
  })

  it('préserve les facettes que le formulaire ne possède pas', () => {
    const merged = mergeWithPrimary(aiBorn, {
      jobTitle: 'Développeur',
      keywords: ['Symfony'],
      experienceMin: 0,
      educationLevel: '',
    })

    // Le pays et la langue survivent : le recruteur n'a ajusté qu'un poste.
    expect(merged).toEqual([
      criterion('country', 'BE', 'localisation'),
      criterion('language', 'nl', 'langues'),
      criterion('job', 'Développeur', 'metier'),
      criterion('q', 'Symfony', 'motsCles'),
    ])
  })

  it('retire un critère par sa facette et sa valeur', () => {
    expect(withoutCriterion(aiBorn, 'country', 'BE')).toEqual([
      criterion('q', 'PHP', 'motsCles'),
      criterion('language', 'nl', 'langues'),
    ])
    expect(withoutCriterion(aiBorn, 'country', 'FR')).toHaveLength(3)
  })
})
