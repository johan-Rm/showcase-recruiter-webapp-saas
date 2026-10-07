import { describe, expect, it } from 'vitest'

import {
  activeCount,
  groupSearches,
  HOME_COLUMNS,
  HOME_ROWS,
  homeCapacity,
  preloadedSearchIds,
  recentToResume,
} from './collection'

import type { StoredSearch } from '../../shared/types/search'

/**
 * Comment une collection de recherches se présente à l'écran.
 *
 * Ces tests protègent deux règles, et elles se cassent en silence toutes les deux.
 *
 * **L'ordre des opérations sur l'accueil.** Filtrer après avoir trié laisserait une recherche
 * écartée occuper une des places servies — et clôturer une recherche la « touche », donc la rend la
 * plus récente de toutes.
 *
 * **La priorité entre clôture et appartenance.** Une recherche partagée ET close appartient au pli
 * des closes, jamais à la section des partagées : rien à l'écran ne dirait qu'elle s'est rangée au
 * mauvais endroit, les deux groupes se ressemblant.
 */

const search = (id: string, overrides: Partial<StoredSearch> = {}): StoredSearch => ({
  id,
  name: id,
  criteria: [],
  isAutoNamed: true,
  isShared: false,
  ownerId: 'owner-1',
  isOwn: true,
  newCount: 0,
  resultCount: 10,
  seenCount: 0,
  favoriteCount: 0,
  createdAt: '2026-01-01T00:00:00+00:00',
  updatedAt: '2026-01-01T00:00:00+00:00',
  ...overrides,
})

describe('groupSearches', () => {
  it('sépare les miennes, celles d’un collègue et les closes', () => {
    const { mine, shared, closed } = groupSearches([
      search('à moi'),
      search('close', { closedAt: '2026-03-12T09:00:00+00:00' }),
      search('de Leila', { isOwn: false }),
    ])

    expect(mine.map((s) => s.id)).toEqual(['à moi'])
    expect(shared.map((s) => s.id)).toEqual(['de Leila'])
    expect(closed.map((s) => s.id)).toEqual(['close'])
  })

  it('range une recherche partagée ET close chez les closes', () => {
    // La clôture l'emporte sur l'appartenance : le pli veut dire « plus rien à y faire », et c'est
    // vrai quel qu'en soit l'auteur. Rangée dans la section des partagées, elle rouvrirait un
    // travail que son auteur a déclaré fini.
    const { shared, closed } = groupSearches([
      search('close de Leila', { isOwn: false, closedAt: '2026-03-12T09:00:00+00:00' }),
    ])

    expect(shared).toHaveLength(0)
    expect(closed.map((s) => s.id)).toEqual(['close de Leila'])
  })

  it('traite une clé absente comme une recherche active', () => {
    // L'API ne sérialise pas les valeurs nulles : sur une recherche active, `closedAt` n'est PAS
    // dans la réponse. L'absence doit valoir « active », au même titre que `null`.
    const { mine } = groupSearches([search('a'), search('b', { closedAt: null })])

    expect(mine).toHaveLength(2)
  })

  it('rend trois listes vides sur une collection vide', () => {
    expect(groupSearches([])).toEqual({ mine: [], shared: [], closed: [] })
  })
})

describe('activeCount', () => {
  it('compte les miennes ET celles d’un collègue, sans les closes', () => {
    // C'est ce que le lien de l'accueil promet : ce qu'on verra en arrivant sur la collection,
    // hors du pli. Compter mes seules recherches annoncerait moins que ce que l'écran montre.
    expect(
      activeCount([
        search('à moi'),
        search('de Leila', { isOwn: false }),
        search('close', { closedAt: '2026-03-12T09:00:00+00:00' }),
      ]),
    ).toBe(2)
  })

  it('rend zéro sur une collection vide', () => {
    expect(activeCount([])).toBe(0)
  })
})

describe('recentToResume', () => {
  it('écarte les closes avant de trier, pas après', () => {
    // La close est la PLUS RÉCENTE — clôturer une recherche la touche. Triée d'abord, elle
    // occuperait la première place et évincerait une recherche vivante.
    const result = recentToResume(
      [
        search('ancienne', { updatedAt: '2026-01-01T00:00:00+00:00' }),
        search('close', {
          updatedAt: '2026-12-31T00:00:00+00:00',
          closedAt: '2026-12-31T00:00:00+00:00',
        }),
        search('récente', { updatedAt: '2026-06-01T00:00:00+00:00' }),
      ],
      2,
    )

    expect(result.map((s) => s.id)).toEqual(['récente', 'ancienne'])
  })

  it('écarte les recherches d’un collègue avant de trier, pas après', () => {
    // Même piège que la close, et il coûte plus cher : une recherche partagée fraîchement touchée
    // par son auteur prendrait la première place de MON écran d'arrivée, alors que je ne peux
    // rien en faire.
    const result = recentToResume(
      [
        search('la mienne', { updatedAt: '2026-01-01T00:00:00+00:00' }),
        search('de Leila', { isOwn: false, updatedAt: '2026-12-31T00:00:00+00:00' }),
      ],
      2,
    )

    expect(result.map((s) => s.id)).toEqual(['la mienne'])
  })

  it('rend une liste vide quand aucune recherche n’est à moi', () => {
    // C'est le cas qui déclenche la redirection vers l'écran de création : un membre à qui l'on a
    // partagé des recherches, mais qui n'en a créé aucune.
    expect(recentToResume([search('de Leila', { isOwn: false })], 6)).toEqual([])
  })

  it('sert au plus le nombre demandé', () => {
    const searches = ['a', 'b', 'c', 'd'].map((id, index) =>
      search(id, { updatedAt: `2026-0${index + 1}-01T00:00:00+00:00` }),
    )

    expect(recentToResume(searches, 2).map((s) => s.id)).toEqual(['d', 'c'])
  })

  it('ne modifie pas la collection reçue', () => {
    // Le tri de `Array.prototype.sort` est en place : sans copie préalable, il réordonnerait la
    // liste du store — un effet de bord invisible depuis l'écran qui appelle.
    const searches = [
      search('a', { updatedAt: '2026-01-01T00:00:00+00:00' }),
      search('b', { updatedAt: '2026-09-01T00:00:00+00:00' }),
    ]

    recentToResume(searches, 2)

    expect(searches.map((s) => s.id)).toEqual(['a', 'b'])
  })
})

describe('homeCapacity', () => {
  it('donne deux rangées pleines à chaque palier de la grille', () => {
    // Un nombre FIXE laisserait une rangée bancale : quatre cartes sur trois colonnes donnent une
    // rangée pleine et une orpheline entre deux cases vides.
    expect(homeCapacity(HOME_COLUMNS.base)).toBe(2)
    expect(homeCapacity(HOME_COLUMNS.sm)).toBe(4)
    expect(homeCapacity(HOME_COLUMNS.xwide)).toBe(6)
  })

  it('rend toujours un multiple du nombre de rangées', () => {
    // C'est l'invariant : jamais de rangée incomplète, à aucune largeur.
    for (const colonnes of Object.values(HOME_COLUMNS)) {
      expect(homeCapacity(colonnes) % HOME_ROWS).toBe(0)
    }
  })

  it('ne rend jamais un nombre négatif', () => {
    expect(homeCapacity(-1)).toBe(0)
  })

  it('suit les paliers du gabarit, qui montent par un', () => {
    // Ces valeurs DOIVENT rester celles de `sm:grid-cols-2 xwide:grid-cols-3`. Si elles divergent,
    // le nombre de cartes et la grille basculent à des largeurs différentes et la dernière rangée
    // se casse — ce test est le garde-fou de cette correspondance.
    expect([HOME_COLUMNS.base, HOME_COLUMNS.sm, HOME_COLUMNS.xwide]).toEqual([1, 2, 3])
  })
})

describe('preloadedSearchIds', () => {
  const dated = (id: string, jour: string): StoredSearch =>
    search(id, { updatedAt: `2026-${jour}T00:00:00+00:00` })

  it('ne nomme que les recherches que l’accueil affiche', () => {
    // C'est L'ÉCRAN qui nomme, pas le serveur : un serveur qui prendrait « les plus récentes »
    // finirait par précharger une recherche que l'accueil n'affiche pas.
    const searches = [
      dated('a', '01-01'),
      search('close', {
        updatedAt: '2026-12-31T00:00:00+00:00',
        closedAt: '2026-12-31T00:00:00+00:00',
      }),
      search('de Leila', { isOwn: false, updatedAt: '2026-12-30T00:00:00+00:00' }),
    ]

    expect(preloadedSearchIds(searches)).toEqual(['a'])
  })

  it('s’arrête à la capacité de la grille la plus large', () => {
    // Six, jamais deux ni quatre : la largeur n'est pas connue au chargement, et précharger pour
    // un écran étroit laisserait les dernières cartes d'un grand écran sans fiches.
    const searches = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'].map((id, index) =>
      dated(id, `0${index + 1}-01`),
    )

    expect(preloadedSearchIds(searches)).toHaveLength(homeCapacity(HOME_COLUMNS.xwide))
  })

  it('rend les identifiants triés, quel que soit l’ordre d’affichage', () => {
    // L'ADRESSE DE L'APPEL EN DÉPEND : deux demandes du même lot doivent produire la même chaîne,
    // sans quoi rien ne permet de reconnaître un appel déjà parti et le lot repartirait à chaque
    // navigation.
    const recentes = [dated('zoé', '09-01'), dated('adam', '08-01')]
    const inversees = [dated('adam', '08-01'), dated('zoé', '09-01')]

    expect(preloadedSearchIds(recentes)).toEqual(['adam', 'zoé'])
    expect(preloadedSearchIds(inversees)).toEqual(preloadedSearchIds(recentes))
  })

  it('ne nomme personne quand aucune recherche n’est affichable', () => {
    // Le chargeur s'en sert pour ne PAS partir : un appel sans recherche reviendrait vide.
    expect(preloadedSearchIds([])).toEqual([])
    expect(preloadedSearchIds([search('de Leila', { isOwn: false })])).toEqual([])
  })
})
