/**
 * LE NOM D'UN CODE, dans la langue qu'on lit — « MA » → « Maroc », « nl » → « Néerlandais ».
 *
 * L'exécution porte déjà ces deux nomenclatures dans toutes les langues de l'interface : il n'y a
 * aucune table à tenir, et une valeur que le vivier n'avait jamais portée est nommée sans qu'on ait
 * eu à la prévoir. C'est ce qui sépare ces deux facettes des secteurs, qui arrivent bruts de la
 * source et restent tels quels faute d'un référentiel comparable.
 *
 * SANS FRAMEWORK, la langue étant un paramètre : l'appelant la connaît, cette couche ne la cherche
 * pas. Le cache est la mémoïsation d'une fonction déterministe — construire un formateur par
 * étiquette et par rendu coûterait pour rien sur une rangée de seize.
 */

const formatters = new Map<string, Intl.DisplayNames>()

const formatterFor = (type: 'region' | 'language', locale: string): Intl.DisplayNames | null => {
  const key = `${type}:${locale}`
  const known = formatters.get(key)

  if (known) return known

  try {
    const built = new Intl.DisplayNames([locale], { type })
    formatters.set(key, built)

    return built
  } catch {
    return null
  }
}

/**
 * L'INITIALE EN CAPITALE, et c'est une règle de panneau, pas de langue.
 *
 * Un nom de langue s'écrit en minuscule en français — « français », « anglais » —, quand un nom de
 * pays est un nom propre. Laissé tel quel, « français » serait le seul libellé bas d'un panneau dont
 * toutes les options commencent par une capitale : « Maintenant », « Sous 15 j », « Immédiate ». La
 * rangée se lirait comme un défaut d'alignement, ce que ce nommage vient précisément corriger.
 *
 * La capitale est demandée DANS LA LANGUE affichée, et le découpage passe par les points de code :
 * une écriture sans casse — l'arabe — traverse sans rien changer.
 */
const capitalized = (name: string, locale: string): string => {
  const [first, ...rest] = [...name]

  return undefined === first ? name : first.toLocaleUpperCase(locale) + rest.join('')
}

/**
 * Le repli est le code LUI-MÊME, jamais une chaîne vide : une valeur que l'exécution ne sait pas
 * nommer s'affiche comme avant ce nommage, elle ne disparaît pas de la rangée.
 *
 * `of()` JETTE sur un code mal formé — deux lettres attendues pour un pays — au lieu de rendre
 * `undefined`. Sans cette garde, une valeur inattendue ne raterait pas une étiquette : elle
 * viderait le panneau entier, comme l'ont fait les langues et les secteurs absents d'une carte.
 */
const nameOf = (type: 'region' | 'language', code: string, locale: string): string => {
  const trimmed = code.trim()

  if ('' === trimmed) return code

  try {
    const named = formatterFor(type, locale)?.of(trimmed)

    // LA CAPITALE NE S'APPLIQUE QU'À UN NOM. Faute de nomenclature, `of()` rend le code lui-même :
    // le capitaliser produirait un « Zz » qui n'est plus le code et n'est pas davantage un nom.
    return undefined === named || named.toLowerCase() === trimmed.toLowerCase()
      ? trimmed
      : capitalized(named, locale)
  } catch {
    return trimmed
  }
}

/** « BE » → « Belgique ». Le code reste ce qu'on envoie au filtre ; seul l'affichage change. */
export const countryLabel = (code: string, locale: string): string => nameOf('region', code, locale)

/** « nl » → « Néerlandais ». */
export const languageLabel = (code: string, locale: string): string =>
  nameOf('language', code, locale)
