import { describe, expect, it } from 'vitest'

import { filterByView, toSearchView, viewSlug } from './views'

import type { CandidateCard } from '../../shared/types/candidate-card'

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

describe('filterByView', () => {
  it('remonte les profils vérifiés en tête, en préservant l’ordre du reste', () => {
    const list = [candidate('a'), candidate('b', { isStar: true }), candidate('c')]

    expect(filterByView(list, 'all').map(({ id }) => id)).toEqual(['b', 'a', 'c'])
  })

  it('réduit aux profils retenus', () => {
    const list = [candidate('favori', { isFavorite: true }), candidate('autre')]

    expect(filterByView(list, 'favorites').map(({ id }) => id)).toEqual(['favori'])
  })

  // « Tous » garde une porte sur ce qui n'a pas été retenu : retenir n'engage à rien, et on doit
  // pouvoir se rattraper.
  it('garde le jeu entier sous « Tous »', () => {
    const list = [candidate('favori', { isFavorite: true }), candidate('autre')]

    expect(filterByView(list, 'all')).toHaveLength(2)
  })

  it('ne retient aucun favori hors d’une recherche, où l’état de travail est `null`', () => {
    const list = [candidate('moteur', { isFavorite: null })]

    expect(filterByView(list, 'favorites')).toEqual([])
  })

  it('ne mute pas le jeu reçu', () => {
    const list = [candidate('a'), candidate('b', { isStar: true })]

    filterByView(list, 'all')

    expect(list.map(({ id }) => id)).toEqual(['a', 'b'])
  })
})

describe('toSearchView', () => {
  // « Ma sélection » ouvre la Table sur un onglet donné, ce qui suppose que la vue vive dans
  // l'adresse. Tout ce qui suit vient d'une adresse écrite par quelqu'un d'autre —
  // recopiée, tronquée, mise en signet — et aucun de ces cas ne doit produire un écran vide.
  it('reconnaît le nom de la vue dans l’adresse', () => {
    expect(toSearchView('tous')).toBe('all')
  })

  // Le tableau est l'étape où l'on CONTACTE : il s'ouvre sur la récolte, pas sur le vivier de la
  // recherche. Un écran de contact ouvert sur tout invite à payer pour un profil jamais regardé.
  it('ouvre les retenus quand le paramètre est absent', () => {
    expect(toSearchView(undefined)).toBe('favorites')
  })

  it('ouvre les retenus sur un nom inconnu, plutôt qu’un écran vide', () => {
    expect(toSearchView('nimportequoi')).toBe('favorites')
    expect(toSearchView('')).toBe('favorites')
  })

  // UNE ADRESSE ANCIENNE reste juste sans rien migrer : `?vue=favoris` a été écrite quand les
  // retenus portaient un nom ; ce nom n'est plus reconnu, elle retombe sur le défaut — qui EST la
  // vue qu'elle demandait. Un signet posé avant l'inversion ouvre donc toujours ce qu'il visait.
  it('rend les retenus sur une adresse écrite avant l’inversion', () => {
    expect(toSearchView('favoris')).toBe('favorites')
  })

  // `?vue=favoris&vue=tous` arrive en TABLEAU côté routeur : sans ce cas la comparaison porterait
  // sur un tableau, qui n'égale jamais une chaîne, et la vue retomberait sur « Tous » par accident
  // plutôt que par décision.
  it('retient la première valeur quand le paramètre est répété', () => {
    expect(toSearchView(['tous', 'favoris'])).toBe('all')
    expect(toSearchView([])).toBe('favorites')
  })

  // Le nom interne n'est PAS le nom public : écrire `?vue=favorites` ne doit pas marcher par
  // hasard, sinon deux adresses désignent le même onglet et l'une d'elles n'est documentée nulle
  // part.
  it('n’accepte pas le nom interne de la vue', () => {
    expect(toSearchView('all')).toBe('favorites')
  })
})

describe('viewSlug', () => {
  it('nomme « Tous » et laisse la vue par défaut sans nom', () => {
    expect(viewSlug('all')).toBe('tous')
    // `undefined` retire le paramètre de l'adresse : revenir sur le défaut rend exactement
    // l'adresse d'où l'on venait, au lieu d'y laisser un paramètre.
    expect(viewSlug('favorites')).toBeUndefined()
  })
})
