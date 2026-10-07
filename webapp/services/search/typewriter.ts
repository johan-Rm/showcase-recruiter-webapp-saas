/**
 * Réécrire un texte à la machine — les étapes, et rien d'autre.
 *
 * Le nom d'une recherche se recompose à chaque critère changé. Le remplacer d'un coup ne dit pas
 * qu'il a changé ; le retaper en entier fait repartir de zéro un nom qui n'a bougé que d'un mot. On
 * efface donc jusqu'au **préfixe commun**, puis on tape la suite : ce qui est resté identique n'est
 * jamais retouché, et le mouvement désigne exactement ce qui a changé.
 *
 * Module PUR — aucune minuterie, aucun DOM. Il rend une suite d'états ; l'appelant décide s'il les
 * joue, à quelle cadence, et s'il les joue tout court.
 */

/**
 * Durée totale de la réécriture, quel que soit l'écart.
 *
 * Bornée et non proportionnelle : un nom long mettrait plusieurs secondes à s'écrire, et le
 * recruteur aurait fini de remplir sa phrase avant que le nom ait fini de se composer. C'est la
 * cadence qui s'adapte au nombre d'étapes, jamais l'inverse.
 */
export const TYPING_TOTAL_MS = 520

/** En deçà, l'œil ne suit plus ; au-delà, on attend. Bornes de la cadence, en millisecondes. */
const MIN_STEP_MS = 10
const MAX_STEP_MS = 30

/**
 * Les états successifs menant de `current` à `target`, le premier retour arrière inclus.
 *
 * Vide quand les deux textes sont identiques : il n'y a alors rien à jouer, et l'appelant n'a pas à
 * distinguer ce cas lui-même.
 *
 * Le dernier état rendu est toujours `target` exactement — un appelant qui joue toute la suite
 * arrive à destination sans avoir à la reposer.
 */
export const typingSteps = (current: string, target: string): string[] => {
  if (current === target) {
    return []
  }

  let common = 0
  while (common < current.length && common < target.length && current[common] === target[common]) {
    common += 1
  }

  const steps: string[] = []

  // Retour arrière jusqu'au préfixe commun…
  for (let length = current.length; length > common; length -= 1) {
    steps.push(current.slice(0, length - 1))
  }

  // …puis frappe de ce qui diffère.
  for (let length = common; length < target.length; length += 1) {
    steps.push(target.slice(0, length + 1))
  }

  return steps
}

/**
 * La cadence à tenir pour que la suite entière rentre dans le budget de temps.
 *
 * Bornée des deux côtés : une réécriture de deux caractères ne doit pas s'étirer sur un quart de
 * seconde par étape, et une réécriture de cent caractères ne doit pas devenir un clignotement.
 * Le budget est donc une cible, pas une garantie — c'est la lisibilité qui tranche aux extrêmes.
 */
export const typingDelay = (steps: number, total: number = TYPING_TOTAL_MS): number => {
  if (steps <= 0) {
    return MAX_STEP_MS
  }

  return Math.max(MIN_STEP_MS, Math.min(MAX_STEP_MS, Math.round(total / steps)))
}
