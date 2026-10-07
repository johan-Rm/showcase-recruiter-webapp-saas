/**
 * Violations Symfony Validator renvoyées en 422 par l'API.
 *
 * Métier pur : aucune dépendance framework — la règle de validation appartient à l'API, le front
 * ne fait que la rendre affichable champ par champ.
 */
interface Violation {
  propertyPath: string
  message: string
}

interface ViolationBody {
  violations?: Violation[]
}

/**
 * Deux formes selon le chemin d'appel :
 *  - appel direct de l'API : `error.data` EST le corps 422 → `error.data.violations`.
 *  - appel via le BFF (relais Nitro) : l'erreur h3 emballe le corps de l'API sous `data`, donc
 *    `error.data.data.violations`. Presque tous les appels du front passent par le BFF.
 */
interface ViolationPayload {
  data?: ViolationBody & { data?: ViolationBody }
}

/** Erreur d'appel → dictionnaire `champ → message`. Vide si l'erreur n'est pas une 422 de validation. */
export const fieldViolations = (error: unknown): Record<string, string> => {
  const payload = (error as ViolationPayload)?.data
  const violations = payload?.violations ?? payload?.data?.violations ?? []

  return Object.fromEntries(violations.map(({ propertyPath, message }) => [propertyPath, message]))
}
