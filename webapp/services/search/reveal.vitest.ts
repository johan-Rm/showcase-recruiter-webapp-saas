import { describe, expect, it } from 'vitest'

import { channelState, hasAccess, hasDataOn, isRevealed, payableCount } from './reveal'

import type { CandidateCard } from '#shared/types/candidate-card'

/** Un profil qui a de quoi servir sur les quatre canaux, et n'a rien payé — le cas courant. */
const card = (over: Partial<CandidateCard> = {}): CandidateCard =>
  ({
    revealedEmail: false,
    revealedPhone: false,
    revealedResume: false,
    revealedReport: false,
    hasEmail: true,
    hasPhone: true,
    hasResume: true,
    hasReport: true,
    ...over,
  }) as CandidateCard

describe('hasAccess', () => {
  it('ne rend rien quand rien n’est payé', () => {
    const nothing = card()

    expect(hasAccess(nothing, 'EMAIL')).toBe(false)
    expect(hasAccess(nothing, 'PHONE')).toBe(false)
    expect(hasAccess(nothing, 'RESUME')).toBe(false)
    expect(hasAccess(nothing, 'INTERVIEW_REPORT')).toBe(false)
  })

  // L’inversion qu’aucune capture ne montrerait : les deux coordonnées s’affichent au même endroit,
  // dans la même colonne, et rien à l’écran ne dirait qu’on sert la mauvaise.
  it('n’ouvre pas le téléphone quand l’e-mail est payé', () => {
    const emailOnly = card({ revealedEmail: true })

    expect(hasAccess(emailOnly, 'EMAIL')).toBe(true)
    expect(hasAccess(emailOnly, 'PHONE')).toBe(false)
  })

  it('n’ouvre pas l’e-mail quand le téléphone est payé', () => {
    const phoneOnly = card({ revealedPhone: true })

    expect(hasAccess(phoneOnly, 'PHONE')).toBe(true)
    expect(hasAccess(phoneOnly, 'EMAIL')).toBe(false)
  })

  it('garde les documents indépendants des canaux', () => {
    const resumeOnly = card({ revealedResume: true })

    expect(hasAccess(resumeOnly, 'RESUME')).toBe(true)
    expect(hasAccess(resumeOnly, 'INTERVIEW_REPORT')).toBe(false)
    expect(hasAccess(resumeOnly, 'EMAIL')).toBe(false)
  })
})

describe('payableCount', () => {
  it('compte le lot entier quand rien n’est payé', () => {
    expect(payableCount([card(), card(), card()], 'EMAIL')).toBe(3)
  })

  // Le cas qui a motivé l'affichage du coût sur le bouton : trois profils sélectionnés dont un a
  // déjà son e-mail — le bouton doit annoncer 2, pas 3. Annoncer le compte de la sélection ferait
  // croire à une double facturation et ferait renoncer à un lot qui tenait dans le solde.
  it('exclut les profils déjà payés SUR CE CANAL', () => {
    const lot = [card(), card(), card({ revealedEmail: true })]

    expect(payableCount(lot, 'EMAIL')).toBe(2)
    expect(payableCount(lot, 'PHONE')).toBe(3)
  })

  // Zéro n'est pas un détail d'affichage : c'est ce qui éteint le bouton. Un geste proposé qui ne
  // ferait rien est pire que pas de geste du tout.
  it('rend zéro quand tout le lot est déjà payé sur ce canal', () => {
    expect(
      payableCount([card({ revealedResume: true }), card({ revealedResume: true })], 'RESUME'),
    ).toBe(0)
  })

  it('rend zéro sur un lot vide', () => {
    expect(payableCount([], 'EMAIL')).toBe(0)
  })

  // Chaque canal commande le sien : payer le CV ne réduit aucun des trois autres comptes.
  it('garde les quatre canaux indépendants', () => {
    const lot = [card({ revealedResume: true })]

    expect(payableCount(lot, 'RESUME')).toBe(0)
    expect(payableCount(lot, 'EMAIL')).toBe(1)
    expect(payableCount(lot, 'PHONE')).toBe(1)
    expect(payableCount(lot, 'INTERVIEW_REPORT')).toBe(1)
  })
})

describe('hasDataOn', () => {
  it('lit le signal du canal demandé', () => {
    const injoignable = card({ hasEmail: false, hasPhone: false })

    expect(hasDataOn(injoignable, 'EMAIL')).toBe(false)
    expect(hasDataOn(injoignable, 'PHONE')).toBe(false)
    expect(hasDataOn(injoignable, 'RESUME')).toBe(true)
  })

  // La règle qui évite une régression le jour d'un déploiement en deux temps : un serveur qui ne
  // renvoie pas encore ces deux signaux ne dit pas qu'il n'y a rien, il ne dit rien. Éteindre
  // dessus rendrait un contenu payant inatteignable partout où l'API a du retard.
  it('traite un signal ABSENT comme « on ne sait pas », donc présent', () => {
    const ancienContrat = card({ hasResume: undefined, hasReport: undefined })

    expect(hasDataOn(ancienContrat, 'RESUME')).toBe(true)
    expect(hasDataOn(ancienContrat, 'INTERVIEW_REPORT')).toBe(true)
  })
})

describe('payableCount — ce qui n’a rien à servir', () => {
  // Le défaut d'origine : sur un vivier où 1 % des profils ont un e-mail, le compte annonçait un
  // prix pour des adresses inexistantes, alors que la carte portait déjà de quoi le savoir.
  it('exclut les profils sans donnée sur le canal', () => {
    const lot = [card(), card({ hasEmail: false })]

    expect(payableCount(lot, 'EMAIL')).toBe(1)
    expect(payableCount(lot, 'PHONE')).toBe(2)
  })

  it('rend zéro quand aucun profil du lot n’a la donnée', () => {
    expect(payableCount([card({ hasPhone: false }), card({ hasPhone: false })], 'PHONE')).toBe(0)
  })

  // Les deux exclusions se cumulent sans se gêner.
  it('exclut aussi bien le déjà payé que le vide, dans le même lot', () => {
    const lot = [card(), card({ revealedEmail: true }), card({ hasEmail: false })]

    expect(payableCount(lot, 'EMAIL')).toBe(1)
  })
})

describe('channelState', () => {
  it('reste payable dès qu’un profil du lot reste à payer', () => {
    expect(channelState([card(), card({ revealedEmail: true })], 'EMAIL')).toBe('payable')
    expect(channelState([card(), card({ hasEmail: false })], 'EMAIL')).toBe('payable')
  })

  it('dit « acquis » quand tout le lot a déjà payé', () => {
    expect(
      channelState([card({ revealedEmail: true }), card({ revealedEmail: true })], 'EMAIL'),
    ).toBe('owned')
  })

  it('dit « rien » quand aucun profil n’a la donnée', () => {
    expect(channelState([card({ hasEmail: false }), card({ hasEmail: false })], 'EMAIL')).toBe(
      'empty',
    )
  })

  // Le cas des dévoilements fautifs déjà payés : l'accès est acquis, mais il n'ouvrira jamais rien.
  // « Rien » plutôt qu'« acquis », parce que c'est l'information utile pour la suite.
  it('dit « rien » quand un canal est à la fois vide et déjà payé', () => {
    const lot = [card({ hasEmail: false, revealedEmail: true }), card({ hasEmail: false })]

    expect(channelState(lot, 'EMAIL')).toBe('empty')
  })
})

describe('isRevealed', () => {
  it('tient dès qu’un canal de contact est payé', () => {
    expect(isRevealed(card({ revealedEmail: true }))).toBe(true)
    expect(isRevealed(card({ revealedPhone: true }))).toBe(true)
  })

  // Un document acheté ne rend joignable personne : le compteur compte des personnes engagées.
  it('ne tient pas sur les seuls documents', () => {
    expect(isRevealed(card({ revealedResume: true, revealedReport: true }))).toBe(false)
  })

  it('est faux quand rien n’est payé', () => {
    expect(isRevealed(card())).toBe(false)
  })
})
