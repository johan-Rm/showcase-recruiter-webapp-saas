import { describe, expect, it } from 'vitest'

import {
  VIVIER_METRIC_META,
  formatCount,
  gapMetricLabel,
  gapRouteOf,
  vivierRatioDenominator,
} from './vivierMetrics'

// Un rapport réduit à ce que les deux fonctions lisent : les trois populations de référence, et
// une section quelconque pour vérifier qu'on ne va pas y chercher un dénominateur.
const SNAPSHOT = {
  Vivier: { 'Profils actifs': 2561, 'Postes du parcours': 14943 },
  'Tables dérivées': { 'Expérience métier — postes rattachés': 9911 },
  'Poste actuel': { CERTAIN: 1484 },
}

describe('formatCount', () => {
  it('écrit le nombre dans la langue reçue', () => {
    // L'anglais est le repère stable : virgule des milliers, quelle que soit la version d'ICU.
    expect(formatCount(2561, 'en')).toBe('2,561')
    expect(formatCount(14943, 'en')).toBe('14,943')
  })

  it('n’écrit jamais la virgule anglaise en français', () => {
    // Le défaut corrigé : `toLocaleString()` sans langue rendait « 2,561 » à un lecteur français.
    // On n'affirme pas QUEL séparateur ICU choisit — espace fine, insécable — mais qu'il groupe
    // sans virgule, ce qui est la règle française et ne dépend d'aucune version.
    const rendu = formatCount(2561, 'fr')

    expect(rendu).not.toContain(',')
    expect(rendu.replace(/\D/g, '')).toBe('2561')
    expect(rendu).not.toBe(formatCount(2561, 'en'))
  })

  it('laisse les petits nombres intacts, dans les deux langues', () => {
    expect(formatCount(940, 'fr')).toBe('940')
    expect(formatCount(940, 'en')).toBe('940')
    expect(formatCount(0, 'fr')).toBe('0')
  })
})

describe('vivierRatioDenominator', () => {
  it('lit chaque population de référence là où elle vit', () => {
    expect(vivierRatioDenominator(SNAPSHOT, 'actifs')).toBe(2561)
    expect(vivierRatioDenominator(SNAPSHOT, 'postes')).toBe(14943)
    expect(vivierRatioDenominator(SNAPSHOT, 'rattaches')).toBe(9911)
  })

  it('rend null plutôt que d’échouer quand la métrique manque', () => {
    // Dégradation propre : le back peut cesser d'émettre une section ou renommer une métrique.
    // L'écran perd alors son ratio, il n'affiche pas une erreur.
    expect(vivierRatioDenominator({}, 'actifs')).toBeNull()
    expect(vivierRatioDenominator({ Vivier: {} }, 'actifs')).toBeNull()
    expect(vivierRatioDenominator({ Autre: { 'Profils actifs': 12 } }, 'actifs')).toBeNull()
  })
})

describe('VIVIER_METRIC_META', () => {
  it('ne déclare que des ratios dont la population est atteignable', () => {
    // Le registre est écrit à la main : une clé de ratio mal orthographiée y passerait inaperçue
    // et ferait disparaître le ratio à l'écran, en silence.
    for (const [metric, meta] of Object.entries(VIVIER_METRIC_META)) {
      if (!meta.ratioOf) continue

      expect(vivierRatioDenominator(SNAPSHOT, meta.ratioOf), metric).not.toBeNull()
    }
  })

  it('donne une bulle d’aide à chaque métrique devenue cliquable', () => {
    // Les deux tables sont indexées sur la même chaîne : si l'une reconnaît un libellé que l'autre
    // ignore, c'est qu'un renommage n'a été répercuté que d'un côté.
    for (const [metric, meta] of Object.entries(VIVIER_METRIC_META)) {
      if (gapRouteOf(metric)) expect(meta.hint, metric).toBeTruthy()
    }
  })
})

// La table qui relie un libellé de métrique à l'adresse de son écran de détail est indexée sur les
// CHAÎNES EXACTES que le serveur émet. Rien ne signale une clé devenue fausse : la carte cesse
// simplement d'être cliquable, sans erreur. C'est le point de rupture assumé au cadrage, et le
// piège porte un nom — l'apostrophe courbe de « Niveau d’études dérivé ».
describe('gapRouteOf', () => {
  it('rend le code d’adresse des quatre métriques qui savent expliquer leur absence', () => {
    expect(gapRouteOf('Ancienneté renseignée')).toBe('experienceYears')
    expect(gapRouteOf('Durée sur le poste actuel')).toBe('currentRoleMonths')
    expect(gapRouteOf('Niveau d’études dérivé')).toBe('educationLevel')
    expect(gapRouteOf('Date de modification source')).toBe('jarviUpdatedAt')
  })

  it('distingue l’apostrophe courbe de la droite', () => {
    // Le serveur émet l'apostrophe typographique. La même phrase avec une apostrophe droite est un
    // AUTRE libellé : si ce test tombe, c'est que la table a été retapée à la main.
    expect(gapRouteOf('Niveau d’études dérivé')).not.toBeNull()
    expect(gapRouteOf("Niveau d'études dérivé")).toBeNull()
  })

  it('rend null sur une métrique de répartition, qui n’a pas d’inverse à montrer', () => {
    expect(gapRouteOf('Séniorité SENIOR')).toBeNull()
    expect(gapRouteOf('CERTAIN')).toBeNull()
    expect(gapRouteOf('Profils actifs')).toBeNull()
  })

  it('rend null sur un libellé inconnu plutôt que de fabriquer une adresse', () => {
    expect(gapRouteOf('')).toBeNull()
    expect(gapRouteOf('Une métrique qui n’existe pas')).toBeNull()
  })
})

describe('gapMetricLabel', () => {
  it('relit le libellé à partir du code de l’adresse', () => {
    expect(gapMetricLabel('experienceYears')).toBe('Ancienneté renseignée')
    expect(gapMetricLabel('educationLevel')).toBe('Niveau d’études dérivé')
  })

  it('rend null sur un code inconnu — ce qui vaut « cette page n’existe pas »', () => {
    expect(gapMetricLabel('nimporte')).toBeNull()
    expect(gapMetricLabel('')).toBeNull()
  })

  it('fait l’aller-retour sans perte pour chaque métrique adressable', () => {
    const addressable = [
      'Ancienneté renseignée',
      'Durée sur le poste actuel',
      'Niveau d’études dérivé',
      'Date de modification source',
    ]

    for (const label of addressable) {
      expect(gapMetricLabel(gapRouteOf(label) as string), label).toBe(label)
    }
  })
})
