import { describe, expect, it } from 'vitest'

import { actionsFor, announceBroad, criteriaToShow, isBroadSearch, railOf } from './card'

import type { RailSource } from './card'
import type { SearchCriterion } from '../../shared/types/search'

/**
 * Le rail d'une carte de recherche.
 *
 * Ce que ces tests protègent tient en une phrase : **l'avancement et les retenus n'obéissent pas à
 * la même règle**, et l'écran ne doit jamais laisser croire le contraire.
 */

/** Traducteur de test : il rend la clé, ce qui suffit à comparer des textes entre eux. */
const translate = (key: string): string => key

const criterion = (facet: string, value: string, group: string): SearchCriterion => ({
  facet,
  value,
  group,
})

const source = (overrides: Partial<RailSource> = {}): RailSource => ({
  name: '',
  criteria: [],
  isOwn: true,
  newCount: 0,
  resultCount: 100,
  seenCount: 0,
  favoriteCount: 0,
  ...overrides,
})

describe('railOf', () => {
  it('rapporte le compte de nouveautés tel quel', () => {
    // LE COMPTE PASSE TEL QUEL, zéro compris : `null` à la place d'un zéro se lit « la carte n'a
    // pas fini de charger », pas « j'ai regardé, il n'y a rien ». Le détail est plus bas.
    expect(railOf(source({ newCount: 12 })).newCount).toBe(12)
  })

  it('rapporte l’avancement au jeu de résultats', () => {
    expect(railOf(source({ resultCount: 200, seenCount: 50 })).progress).toEqual({
      seen: 50,
      ratio: 0.25,
    })
  })

  it('borne l’avancement à 100 %, quoi qu’il arrive', () => {
    // L'API borne déjà `seenCount` au jeu courant ; si elle cessait de le faire, une barre qui
    // déborde de sa piste serait un défaut VISIBLE, pas une donnée fausse en silence.
    expect(railOf(source({ resultCount: 10, seenCount: 40 })).progress?.ratio).toBe(1)
  })

  it('ne divise pas par zéro sur une recherche qui ne ramène personne', () => {
    expect(railOf(source({ resultCount: 0, seenCount: 0 })).progress).toEqual({ seen: 0, ratio: 0 })
  })

  it('n’expose aucun avancement sur la recherche d’un collègue', () => {
    // L'avancement est PERSONNEL, les retenus appartiennent à la recherche : les poser côte à côte
    // sur la carte d'un autre ferait lire deux sujets dans une seule ligne.
    const rail = railOf(source({ isOwn: false, seenCount: 40, favoriteCount: 8 }))

    expect(rail.progress).toBeNull()
    expect(rail.retained).toBe(8)
  })

  it('lit l’état de clôture, y compris quand la clé est absente', () => {
    // L'API ne sérialise pas les valeurs nulles : sur une recherche active, `closedAt` n'est PAS
    // dans la réponse — l'absence vaut donc « active », au même titre que `null`.
    expect(railOf(source()).isClosed).toBe(false)
    expect(railOf(source({ closedAt: null })).isClosed).toBe(false)
    expect(railOf(source({ closedAt: '2026-03-12T09:14:00+00:00' })).isClosed).toBe(true)
  })
})

describe('railOf — la nouveauté', () => {
  /**
   * Ce que ces tests protègent : **zéro est une réponse**. Remplacé par `null`, il ne laisserait
   * rien du tout à cet endroit du rail — ce qui se lit « la carte n'a pas fini de charger », pas
   * « j'ai regardé, il n'y a rien ».
   */
  it('garde zéro au lieu de le remplacer par un vide', () => {
    expect(railOf(source({ newCount: 0 })).newCount).toBe(0)
  })

  it('rend le compte tel quel quand il y a du neuf', () => {
    expect(railOf(source({ newCount: 12 })).newCount).toBe(12)
  })

  it('garde zéro sur une recherche close, dont l’écran dira la clôture', () => {
    // L'API remet le compte à zéro quand la recherche est close ; c'est l'ÉCRAN qui décide de dire
    // « close » plutôt que « pas de nouveaux » à cet emplacement, pas le service.
    const rail = railOf(source({ newCount: 0, closedAt: '2026-03-12T09:00:00+00:00' }))

    expect(rail.newCount).toBe(0)
    expect(rail.isClosed).toBe(true)
  })
})

describe('criteriaToShow', () => {
  const job = criterion('job', 'Développeur logiciel', 'metier')
  const city = criterion('presenceCity', 'Casablanca', 'presence')
  const keyword = criterion('q', 'kubernetes', 'motsCles')

  it('retire les critères que le nom automatique dit déjà', () => {
    // Le nom auto EST composé des critères : les réafficher ferait dire deux fois la même chose.
    const shown = criteriaToShow({ name: '', criteria: [job, keyword] }, translate, 3)

    expect(shown.shown).toEqual([])
    expect(shown.hidden).toBe(0)
  })

  it('ne retire rien quand le nom a été saisi à la main', () => {
    // L'auteur a nommé sa recherche comme il l'entend : le nom ne renseigne plus sur les critères.
    const shown = criteriaToShow(
      { name: 'Ma chasse du moment', criteria: [job, city] },
      translate,
      3,
    )

    expect(shown.shown).toHaveLength(2)
    expect(shown.hidden).toBe(0)
  })

  it('compte ce qui dépasse au lieu de l’afficher', () => {
    const shown = criteriaToShow(
      { name: 'Nom écrit à la main', criteria: [job, city, keyword] },
      translate,
      2,
    )

    expect(shown.shown).toHaveLength(2)
    expect(shown.hidden).toBe(1)
  })

  it('ignore la casse en comparant le critère au nom', () => {
    const shown = criteriaToShow({ name: 'CHASSE Casablanca', criteria: [city] }, translate, 3)

    // Le nom est saisi à la main : rien n'est retiré, même si le mot s'y trouve.
    expect(shown.shown).toHaveLength(1)
  })
})

describe('actionsFor', () => {
  /**
   * Ce que ces tests protègent : **l'écran ne propose jamais ce que le serveur
   * refuserait**. Le contraire apprend à l'utilisateur que l'interface ment — et le refus n'arrive
   * qu'après le clic, quand il est trop tard pour prévenir.
   */
  const own = { isOwn: true, isShared: false }

  it('donne les six gestes sur ma recherche active, modifier en tête', () => {
    expect(actionsFor(own)).toEqual(['modify', 'rename', 'duplicate', 'share', 'close', 'delete'])
  })

  it('ne propose pas de modifier la recherche d’un collègue', () => {
    // L'écriture est réservée à l'auteur : le geste prévu pour la retoucher est la duplication.
    expect(actionsFor({ isOwn: false, isShared: true })).not.toContain('modify')
  })

  it('ne propose pas de modifier une recherche close', () => {
    // La rouvrir est un geste distinct, et c'est lui qui la rend modifiable à nouveau.
    expect(actionsFor({ ...own, closedAt: '2026-03-12T09:00:00+00:00' })).not.toContain('modify')
  })

  it('dit ce que le clic va faire, pas l’état courant', () => {
    // Une recherche déjà partagée propose de ne PLUS l'être : le libellé annonce le geste.
    expect(actionsFor({ ...own, isShared: true })).toContain('unshare')
    expect(actionsFor({ ...own, isShared: true })).not.toContain('share')
  })

  it('réduit une recherche close à rouvrir et supprimer', () => {
    // Le serveur refuse en 409 tout ce qui la modifie ; supprimer reste permis, supprimer n'est
    // pas modifier.
    expect(actionsFor({ ...own, closedAt: '2026-03-12T09:00:00+00:00' })).toEqual([
      'reopen',
      'delete',
    ])
  })

  it('traite une clé absente comme une recherche active', () => {
    // L'API ne sérialise pas les valeurs nulles : sur une recherche active, `closedAt` n'est PAS
    // dans la réponse. Lire l'absence comme « close » viderait le menu de quatre entrées.
    expect(actionsFor({ ...own, closedAt: null })).toHaveLength(6)
    expect(actionsFor(own)).toHaveLength(6)
  })

  it('ne laisse qu’un geste sur la recherche d’un collègue', () => {
    // Un seul geste ne fait pas un menu : l'écran le rend en lien direct. C'est cette longueur de 1
    // qui le lui dit.
    expect(actionsFor({ isOwn: false, isShared: true })).toEqual(['duplicate'])
  })

  it('ne propose rien d’autre sur la recherche close d’un collègue', () => {
    // L'appartenance tranche AVANT l'état : je ne rouvre pas, et je ne supprime pas, ce qui n'est
    // pas à moi.
    expect(
      actionsFor({ isOwn: false, isShared: true, closedAt: '2026-03-12T09:00:00+00:00' }),
    ).toEqual(['duplicate'])
  })
})

describe('criteriaToShow — la rangée vide', () => {
  /**
   * Ce que ces tests protègent : la règle est celle de l'**écran**, pas de la donnée.
   * Ce que le lecteur voit, c'est un espace vide sous le titre, et c'est lui qu'il faut expliquer —
   * y compris quand la recherche a bel et bien des critères, tous déjà dits par le titre.
   */
  const job = criterion('jobTitle', 'Développeur', 'metier')
  // `presence` n'entre PAS dans le nom automatique : c'est ce qui en fait une étiquette qui reste.
  // Un mot-clé aurait été absorbé par le titre au même titre que le métier — la première version de
  // ce test s'y est trompée, et les deux critères disparaissaient ensemble.
  const city = criterion('presenceCity', 'Casablanca', 'presence')

  it('se dit vide quand le titre a tout absorbé', () => {
    // Le cas le PLUS FRÉQUENT : la recherche a des critères, le titre les dit tous, et la rangée
    // n'a plus rien à montrer. Se fier à l'absence de critères le raterait.
    const shown = criteriaToShow({ name: '', criteria: [job] }, translate, 2)

    expect(shown.shown).toHaveLength(0)
    expect(shown.hidden).toBe(0)
    expect(shown.isEmpty).toBe(true)
  })

  it('se dit vide sur une recherche sans aucun critère', () => {
    expect(criteriaToShow({ name: '', criteria: [] }, translate, 2).isEmpty).toBe(true)
  })

  it('ne se dit pas vide dès qu’une étiquette reste', () => {
    const shown = criteriaToShow({ name: '', criteria: [job, city] }, translate, 2)

    expect(shown.shown.length + shown.hidden).toBeGreaterThan(0)
    expect(shown.isEmpty).toBe(false)
  })

  it('ne se dit pas vide quand tout est compté sans être affiché', () => {
    // `max` à zéro : rien n'est montré, mais « +N critères » l'est. La rangée existe donc, et le
    // badge n'a pas à prendre sa place.
    const shown = criteriaToShow(
      { name: 'Nom écrit à la main', criteria: [job, city] },
      translate,
      0,
    )

    expect(shown.shown).toHaveLength(0)
    expect(shown.hidden).toBe(2)
    expect(shown.isEmpty).toBe(false)
  })
})

describe('isBroadSearch', () => {
  const search = (resultCount: number, closedAt: string | null = null) => ({
    resultCount,
    closedAt,
  })

  /**
   * CENT EST UN CONSEIL, PAS UNE LIMITE : la recherche à 352 se parcourt entièrement, on signale
   * seulement que la trier d'un bout à l'autre est un travail que personne ne mène au bout.
   */
  it('conseille au-dessus du seuil, et pas en dessous', () => {
    expect(isBroadSearch(search(352), 100)).toBe(true)
    expect(isBroadSearch(search(99), 100)).toBe(false)
  })

  /**
   * Le seuil lui-même ne déclenche rien : « au-delà de cent » commence à cent un. Un `>=` ferait
   * apparaître le conseil sur une recherche exactement conforme à ce qu'on recommande.
   */
  it('se tait sur une recherche exactement au seuil', () => {
    expect(isBroadSearch(search(100), 100)).toBe(false)
  })

  /** Une recherche close ne se travaille plus : conseiller d'y ajouter un critère n'a plus d'objet. */
  it('se tait sur une recherche close, quel que soit son volume', () => {
    expect(isBroadSearch(search(352, '2026-07-31T09:00:00+00:00'), 100)).toBe(false)
  })

  /**
   * LE SEUIL VIENT DU SERVEUR, et vaut zéro tant que la configuration n'est pas arrivée. Mieux vaut
   * se taire qu'avertir sur un nombre inventé — sans cette garde, TOUTE recherche non vide serait
   * signalée le temps du chargement.
   */
  it("ne conseille rien tant que le seuil n'est pas connu", () => {
    expect(isBroadSearch(search(352), 0)).toBe(false)
    expect(isBroadSearch(search(1), 0)).toBe(false)
  })

  /**
   * Le seuil est un ARGUMENT et non une constante d'ici : le même nombre gouverne le conseil au
   * recruteur et l'inventaire de supervision, et il change côté serveur sans toucher à ce code.
   */
  it('suit le seuil qu’on lui donne', () => {
    expect(isBroadSearch(search(150), 200)).toBe(false)
    expect(isBroadSearch(search(150), 120)).toBe(true)
  })

  /** L'API ne sérialise pas les valeurs nulles : sur une recherche active, la clé est ABSENTE. */
  it('traite une clé de clôture absente comme une recherche active', () => {
    expect(isBroadSearch({ resultCount: 352 }, 100)).toBe(true)
  })

  it('se tait sur une recherche vide', () => {
    expect(isBroadSearch(search(0), 100)).toBe(false)
  })
})

/**
 * Le conseil « recherche large » ne se dit qu'une fois.
 *
 * Ce que ces tests protègent est une MÉMOIRE, et une mémoire qui se trompe ne se voit pas à l'œil :
 * elle se tait quand elle devrait parler, et rien à l'écran ne le signale. Trois comportements en
 * dépendent — ne pas répéter, réannoncer un nouveau franchissement, et ne rien décider tant que le
 * seuil est inconnu.
 */
describe('announceBroad', () => {
  it('annonce une recherche large jamais vue, et la retient', () => {
    const resultat = announceBroad([], 'r-1', 'broad')

    expect(resultat.announce).toBe(true)
    expect(resultat.announced).toEqual(['r-1'])
  })

  it('se tait sur une recherche déjà annoncée', () => {
    const resultat = announceBroad(['r-1'], 'r-1', 'broad')

    expect(resultat.announce).toBe(false)
    expect(resultat.announced).toEqual(['r-1'])
  })

  it("n'annonce rien sur une recherche sous le seuil, et l'oublie", () => {
    const resultat = announceBroad(['r-1'], 'r-1', 'narrow')

    expect(resultat.announce).toBe(false)
    expect(resultat.announced).toEqual([])
  })

  // Le recruteur a resserré ses critères, puis les a rouverts : c'est un fait nouveau.
  it('réannonce un nouveau franchissement du seuil', () => {
    const resserree = announceBroad(['r-1'], 'r-1', 'narrow')
    const rouverte = announceBroad(resserree.announced, 'r-1', 'broad')

    expect(rouverte.announce).toBe(true)
    expect(rouverte.announced).toEqual(['r-1'])
  })

  // Le seuil n'est pas encore servi : ne pas savoir n'est pas savoir que non.
  it('ne touche à rien tant que le seuil est inconnu', () => {
    const jamaisVue = announceBroad([], 'r-1', 'unknown')

    expect(jamaisVue.announce).toBe(false)
    expect(jamaisVue.announced).toEqual([])

    const dejaAnnoncee = announceBroad(['r-1'], 'r-1', 'unknown')

    expect(dejaAnnoncee.announce).toBe(false)
    expect(dejaAnnoncee.announced).toEqual(['r-1'])
  })

  it("n'annonce pas une recherche à la place d'une autre", () => {
    const resultat = announceBroad(['r-1'], 'r-2', 'broad')

    expect(resultat.announce).toBe(true)
    expect(resultat.announced).toEqual(['r-1', 'r-2'])
  })
})
