import { describe, expect, it } from 'vitest'

import { pendingTags } from './tags'

describe('pendingTags', () => {
  it('ne rend rien quand il n y a rien à proposer', () => {
    expect(pendingTags([], [])).toEqual([])
    expect(pendingTags([], ['vuejs'])).toEqual([])
  })

  it('rend toute la liste quand rien n est encore retenu', () => {
    expect(pendingTags(['vuejs', 'vuex', 'pc vue'], [])).toEqual(['vuejs', 'vuex', 'pc vue'])
  })

  it('écarte ce qui est déjà retenu', () => {
    expect(pendingTags(['vuejs', 'vuex', 'pc vue'], ['vuex'])).toEqual(['vuejs', 'pc vue'])
  })

  it('ne rend rien quand tout est déjà retenu', () => {
    expect(pendingTags(['vuejs', 'vuex'], ['vuex', 'vuejs'])).toEqual([])
  })

  it('ne propose pas deux fois la même valeur', () => {
    expect(pendingTags(['vuejs', 'vuex', 'vuejs'], [])).toEqual(['vuejs', 'vuex'])
  })

  it('conserve l ordre d affichage plutôt que de trier', () => {
    expect(pendingTags(['vuex', 'angular', 'react'], [])).toEqual(['vuex', 'angular', 'react'])
  })

  it('ignore ce qui est retenu sans être proposé', () => {
    expect(pendingTags(['vuejs'], ['symfony', 'php'])).toEqual(['vuejs'])
  })

  it('distingue deux valeurs que seule la casse sépare', () => {
    // Le vocabulaire arrive en minuscules du serveur ; deux écritures distinctes restent deux
    // critères distincts, et rien ici ne décide qu'elles n'en font qu'un.
    expect(pendingTags(['React', 'react'], [])).toEqual(['React', 'react'])
  })
})
