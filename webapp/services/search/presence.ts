import type { CandidatePresencePeriod } from '../../shared/types/candidate-card'

/**
 * Le candidat est-il physiquement au Maroc ★ à cette date ?
 *
 * Métier pur : l'API descend les fenêtres de présence, pas le verdict — c'est ici qu'on le calcule.
 * La date est un PARAMÈTRE et non `new Date()` interne : sans cela la fonction ne serait pas
 * testable, et « aujourd'hui » changerait entre le rendu serveur et l'hydratation.
 *
 * Bornes INCLUSES : une fenêtre « 08–24 juillet » couvre le 8 et le 24. Les dates arrivent en
 * `YYYY-MM-DD` (ou ISO), comparées en chaîne sur les 10 premiers caractères — l'ordre lexicographique
 * de ce format EST l'ordre chronologique, et ça évite de fabriquer des `Date` dans deux fuseaux.
 */
export const isPresentInMorocco = (
  periods: readonly CandidatePresencePeriod[],
  at: Date,
): boolean => {
  const day = at.toISOString().slice(0, 10)

  return periods.some(
    ({ startDate, endDate }) => startDate.slice(0, 10) <= day && day <= endDate.slice(0, 10),
  )
}

/** Le jour, décalé de N jours, au même format `YYYY-MM-DD` — en UTC, comme toutes les dates d'ici. */
const dayAfter = (at: Date, days: number): string => {
  const shifted = new Date(at)
  shifted.setUTCDate(shifted.getUTCDate() + days)

  return shifted.toISOString().slice(0, 10)
}

/**
 * Un séjour au Maroc ★ dans la fenêtre `[aujourd'hui, aujourd'hui + N jours]`, et dans la ville
 * demandée quand elle l'est.
 *
 * ⚠️ LES DEUX CONDITIONS PORTENT SUR LA MÊME PÉRIODE, et c'est la règle du serveur, qui les résout
 * en un `EXISTS` unique. Les séparer répondrait « oui » à « Casablanca sous 30 jours » pour un
 * candidat qui passe à Casablanca en décembre et à Rabat la semaine prochaine.
 *
 * `days` à zéro dit *présent aujourd'hui* : la fenêtre se referme sur le jour même. C'est la même
 * question que le drapeau `isInMorocco` de la carte, posée ici parce qu'elle doit se combiner à la
 * ville — le drapeau, lui, ne connaît que le verdict.
 *
 * Bornes INCLUSES et comparaison en chaîne, comme au-dessus : l'ordre lexicographique de
 * `YYYY-MM-DD` EST l'ordre chronologique, sans fabriquer de `Date` dans deux fuseaux.
 */
export const hasStayWithin = (
  periods: readonly CandidatePresencePeriod[],
  days: number,
  city: string | null,
  at: Date,
): boolean => {
  const from = at.toISOString().slice(0, 10)
  const until = dayAfter(at, days)
  const wanted = city?.toLowerCase() ?? null

  return periods.some(
    ({ startDate, endDate, city: stayCity }) =>
      endDate.slice(0, 10) >= from &&
      startDate.slice(0, 10) <= until &&
      (null === wanted || stayCity?.toLowerCase() === wanted),
  )
}

/** Les villes de séjour réellement présentes dans un jeu, dédoublonnées et rangées. */
export const stayCities = (stays: readonly (readonly CandidatePresencePeriod[])[]): string[] =>
  [
    ...new Set(
      stays.flatMap((periods) =>
        periods
          .map(({ city }) => city)
          .filter((city): city is string => null !== city && '' !== city.trim()),
      ),
    ),
  ].sort((left, right) => left.localeCompare(right))

/** `YYYY-MM-DD…` → `DD/MM`. Découpage et non `Intl` : le libellé doit être le même des deux côtés du rendu. */
const shortDay = (date: string): string => `${date.slice(8, 10)}/${date.slice(5, 7)}`

/**
 * Fenêtre de présence à afficher : celle qui couvre la date, sinon la prochaine à venir — `null` si
 * le candidat n'annonce aucun séjour.
 *
 * C'est LA colonne différenciante de la Table : « présent du 8 au 24 juillet » est le moment où l'on
 * peut organiser un entretien sur place.
 */
export const presenceWindowLabel = (
  periods: readonly CandidatePresencePeriod[],
  at: Date,
): string | null => {
  const day = at.toISOString().slice(0, 10)

  const upcoming = [...periods]
    .sort((left, right) => left.startDate.localeCompare(right.startDate))
    .find(({ startDate, endDate }) => day <= endDate.slice(0, 10) || startDate.slice(0, 10) >= day)

  return upcoming ? `${shortDay(upcoming.startDate)} – ${shortDay(upcoming.endDate)}` : null
}
