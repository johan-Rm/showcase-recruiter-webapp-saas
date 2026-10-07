import { describe, expect, it } from 'vitest'

import { isRetained, statesOf } from './states'

import type { CandidateCard } from '#shared/types/candidate-card'

const card = (over: Partial<CandidateCard> = {}): CandidateCard =>
  ({
    isStar: false,
    isFavorite: false,
    isNew: false,
    isInMorocco: false,
    isSeen: false,
    isDismissed: false,
    availability: null,
    ...over,
  }) as CandidateCard

const keys = (profile: CandidateCard) => statesOf(profile).map((state) => state.key)

describe('statesOf', () => {
  // Un badge nomme un attribut ACQUIS, jamais son absence : « Non rencontré » n'existe pas, et
  // c'est au conteneur de disparaître plutôt qu'au badge de dire non.
  it('ne rend rien quand le profil ne porte aucun état', () => {
    expect(statesOf(card())).toEqual([])
  })

  it('rend un état par attribut vrai', () => {
    expect(keys(card({ isStar: true }))).toEqual(['met'])
    expect(keys(card({ isFavorite: true }))).toEqual(['favorite'])
    expect(keys(card({ isNew: true }))).toEqual(['new'])
    expect(keys(card({ isInMorocco: true }))).toEqual(['morocco'])
    expect(keys(card({ isSeen: true }))).toEqual(['seen'])
    expect(keys(card({ isDismissed: true }))).toEqual(['dismissed'])
  })

  // Hors d'une recherche, l'avancement vaut `null` — pas `false`. Une simple véracité confondrait
  // « je n'ai pas vu ce profil » avec « la question ne se pose pas », et collerait un badge à un
  // profil consulté depuis le vivier.
  it("ne dit rien de l'avancement quand la question ne se pose pas", () => {
    expect(keys(card({ isSeen: null, isDismissed: null }))).toEqual([])
    expect(keys(card({ isSeen: false, isDismissed: false }))).toEqual([])
  })

  // Écarter suppose d'avoir vu : les deux faits coexistent, et le profil porte les deux badges.
  it('cumule vu et écarté', () => {
    expect(keys(card({ isSeen: true, isDismissed: true }))).toEqual(['seen', 'dismissed'])
  })

  // Ils parlent du parcours, pas du profil : ils ferment la marche derrière les cinq attributs.
  it("place l'avancement APRÈS ce que le profil est", () => {
    expect(keys(card({ isStar: true, isFavorite: true, isSeen: true, isDismissed: true }))).toEqual(
      ['met', 'favorite', 'seen', 'dismissed'],
    )
  })

  // La seule condition DÉRIVÉE du lot : le serveur envoie une disponibilité, pas un booléen. Un
  // profil en préavis affichait « Disponible » si la comparaison partait — c'est l'inversion que ce
  // test attrape, et qu'aucune capture ne montrerait sans le bon profil sous la main.
  it('ne dit « disponible » que sur une disponibilité immédiate', () => {
    expect(keys(card({ availability: 'IMMEDIATE' }))).toEqual(['available'])
    expect(keys(card({ availability: 'NOTICE_3M' }))).toEqual([])
    expect(keys(card({ availability: null }))).toEqual([])
  })

  /**
   * L'ORDRE est fixe, et il ne suit pas les états présents.
   *
   * Un ordre qui varierait ferait sauter la lecture d'une carte à l'autre — sur un écran où l'on
   * tranche cent profils, la place d'un signe compte autant que le signe.
   */
  it('garde le même ordre quels que soient les états portés', () => {
    const complet = card({
      isStar: true,
      isFavorite: true,
      isNew: true,
      isInMorocco: true,
      availability: 'IMMEDIATE',
    })

    expect(keys(complet)).toEqual(['met', 'favorite', 'available', 'new', 'morocco'])
    // Le sous-ensemble garde l'ordre du tout : « Au Maroc » reste après « Rencontré ».
    expect(keys(card({ isInMorocco: true, isStar: true }))).toEqual(['met', 'morocco'])
  })

  // Le favori est le seul état que le recruteur PRODUIT : il porte la signature de la marque, là où
  // les autres constatent un fait et portent leur propre teinte.
  it('donne l’accent au seul favori, et une teinte propre aux autres', () => {
    const teintes = Object.fromEntries(
      statesOf(
        card({
          isStar: true,
          isFavorite: true,
          isNew: true,
          isInMorocco: true,
          availability: 'IMMEDIATE',
        }),
      ).map((state) => [state.key, state.tint]),
    )

    expect(teintes.favorite).toBe('--accent')
    expect(teintes.met).toBe('--state-met')
    expect(teintes.available).toBe('--state-available')
    expect(teintes.new).toBe('--state-new')
    expect(teintes.morocco).toBe('--state-morocco')
  })

  // Une teinte nomme une variable CSS et jamais une couleur : la valeur vit dans `themes.css`, seul
  // endroit où une couleur du kit doit exister.
  it('ne rend aucune couleur en dur', () => {
    const complet = card({ isStar: true, isFavorite: true, isNew: true, isInMorocco: true })

    expect(statesOf(complet).every((state) => state.tint.startsWith('--'))).toBe(true)
  })

  it('rend un libellé traduisible et une icône pour chaque état', () => {
    const complet = card({ isStar: true, isNew: true })

    expect(statesOf(complet).every((state) => state.labelKey.startsWith('search.'))).toBe(true)
    expect(statesOf(complet).every((state) => state.icon.startsWith('i-lucide-'))).toBe(true)
  })

  // `isNew` et `isFavorite` valent `null` hors d'une recherche — la question n'y a pas d'objet, et
  // `null` ne doit pas se comporter comme `true`.
  it('ne prend pas une question sans objet pour un oui', () => {
    expect(keys(card({ isNew: null, isFavorite: null }))).toEqual([])
  })
})

describe('isRetained', () => {
  it('ne vaut que pour un profil retenu dans cette recherche', () => {
    expect(isRetained(card({ isFavorite: true }))).toBe(true)
    expect(isRetained(card({ isFavorite: false }))).toBe(false)
    expect(isRetained(card({ isFavorite: null }))).toBe(false)
  })
})
