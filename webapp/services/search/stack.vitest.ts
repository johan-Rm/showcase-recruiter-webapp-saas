import { describe, expect, it } from 'vitest'

import { applyFrozenOrder, orderStack, overflowsLoaded } from './stack'

import type { CandidateCard } from '../../shared/types/candidate-card'

/** Une carte réduite à ce que l'ordre regarde : son identité, et si elle a été vue. */
const card = (id: string, isSeen: boolean): CandidateCard => ({ id, isSeen }) as CandidateCard

const ids = (cards: readonly CandidateCard[]): string[] => cards.map((candidate) => candidate.id)

describe('orderStack', () => {
  // Le serveur classe déjà : critères satisfaits d'abord, rencontrés en départage, entrées récentes.
  // « Pertinence » DÉSIGNE cet ordre, il ne le recalcule pas — le front n'en a pas les moyens.
  it('rend l’ordre du serveur intact sous « pertinence »', () => {
    const pile = [card('a', true), card('b', false), card('c', true)]

    expect(ids(orderStack(pile, 'relevance'))).toEqual(['a', 'b', 'c'])
  })

  it('remonte les non vus devant', () => {
    const pile = [card('a', true), card('b', false), card('c', true), card('d', false)]

    expect(ids(orderStack(pile, 'unseen'))).toEqual(['b', 'd', 'a', 'c'])
  })

  // LA propriété qui rend cet ordre sûr : il REGROUPE, il ne mélange pas. Un tri qui perdrait la
  // pertinence à l'intérieur des groupes serait un tri qui dégrade — on aurait remonté les non vus
  // en payant l'ordre pour lequel le serveur a travaillé.
  it('conserve l’ordre du serveur À L’INTÉRIEUR de chaque groupe', () => {
    const pile = [
      card('a', true),
      card('b', false),
      card('c', true),
      card('d', false),
      card('e', false),
    ]

    expect(ids(orderStack(pile, 'unseen'))).toEqual(['b', 'd', 'e', 'a', 'c'])
  })

  it('ne change rien quand tout a été vu, ou quand rien ne l’a été', () => {
    const tout = [card('a', true), card('b', true)]
    const rien = [card('a', false), card('b', false)]

    expect(ids(orderStack(tout, 'unseen'))).toEqual(['a', 'b'])
    expect(ids(orderStack(rien, 'unseen'))).toEqual(['a', 'b'])
  })

  it('ne mute pas la pile reçue', () => {
    const pile = [card('a', true), card('b', false)]

    orderStack(pile, 'unseen')

    expect(ids(pile)).toEqual(['a', 'b'])
  })

  it('accepte une pile vide', () => {
    expect(orderStack([], 'unseen')).toEqual([])
    expect(orderStack([], 'relevance')).toEqual([])
  })
})

describe('applyFrozenOrder', () => {
  // C'est ce gel qui empêche la pile de se réorganiser SOUS LE CURSEUR : marquer un profil vu ne
  // doit pas le faire basculer derrière à l'instant où on le lit.
  it('range la pile selon l’ordre figé, même si l’état « vu » a changé depuis', () => {
    const gel = ['b', 'd', 'a', 'c']
    // `b` vient d'être marqué vu : il RESTE en tête, là où on le lit.
    const pile = [card('a', true), card('b', true), card('c', true), card('d', false)]

    expect(ids(applyFrozenOrder(pile, gel))).toEqual(['b', 'd', 'a', 'c'])
  })

  // Un profil ÉCARTÉ quitte la pile : son identifiant est ignoré, sans trouer l'ordre.
  it('ignore un identifiant dont la carte a quitté la pile', () => {
    expect(ids(applyFrozenOrder([card('a', false), card('c', false)], ['a', 'b', 'c']))).toEqual([
      'a',
      'c',
    ])
  })

  // Une carte ARRIVÉE depuis — page suivante chargée — suit à la fin plutôt que d'être perdue en
  // attendant le prochain regroupement.
  it('place à la fin les cartes que l’ordre figé ne connaît pas', () => {
    const pile = [card('a', false), card('b', false), card('z', false)]

    expect(ids(applyFrozenOrder(pile, ['b', 'a']))).toEqual(['b', 'a', 'z'])
  })

  // Au tout premier rendu le gel n'existe pas encore : la pile doit sortir entière, dans l'ordre
  // reçu, plutôt que vide.
  it('rend la pile entière quand l’ordre figé est vide', () => {
    const pile = [card('a', false), card('b', true)]

    expect(ids(applyFrozenOrder(pile, []))).toEqual(['a', 'b'])
  })

  it('accepte une pile vide sans perdre l’ordre figé', () => {
    expect(applyFrozenOrder([], ['a', 'b'])).toEqual([])
  })
})

describe('overflowsLoaded', () => {
  it('se tait quand la recherche tient dans ce qui est chargé', () => {
    expect(overflowsLoaded(32, 32)).toBe(false)
  })

  it("parle dès qu'un profil correspond sans être chargé", () => {
    expect(overflowsLoaded(100, 101)).toBe(true)
  })

  it('reste muet sur une recherche vide — rien à dire de plus que zéro', () => {
    expect(overflowsLoaded(0, 0)).toBe(false)
  })

  /**
   * Le compte réel ne peut pas être inférieur au chargé, mais il peut arriver EN RETARD : il vient
   * de la fiche de recherche, le jeu de son propre appel. Entre les deux, l'écran ne doit pas
   * annoncer un débordement négatif.
   */
  it("se tait quand le compte réel n'est pas encore connu", () => {
    expect(overflowsLoaded(100, 0)).toBe(false)
  })
})
