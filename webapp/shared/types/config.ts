/**
 * Configuration publique de l'application (GET /v1/config), en miroir de `ConfigOutput` côté API.
 *
 * Ce sont les MÉTADONNÉES du webapp : elles ne dépendent d'aucun écran, ne changent qu'au rythme
 * d'un déploiement, et tout le reste s'y adosse. D'où un seul appel, au tout début du chargement.
 *
 * Les nomenclatures arrivent en VALEURS seulement — les libellés vivent dans l'i18n du front
 * (`search.education.*`), ce qui garde cette réponse indépendante de la locale.
 */

/** Nomenclatures fermées, dans l'ordre des rangs côté API. Le front les affiche telles quelles. */
export interface AppReferentials {
  educationLevels: string[]
  availabilities: string[]
  returnHorizons: string[]
  languageProficiencies: string[]
}

export interface AppConfig {
  /** Coût en crédits d'un accès dévoilé — un par type d'accès. */
  revealCost: number
  welcomeCredits: number
  locales: string[]
  defaultLocale: string
  perPage: number
  /**
   * Combien de profils une recherche donne à travailler, quelle que soit sa taille réelle.
   *
   * C'est la taille que les écrans demandent : une recherche tient alors dans un seul appel. Le
   * nombre vient d'ici et non d'une constante recopiée — c'est exactement ce que cette réponse
   * existe pour éviter.
   */
  searchLoadLimit: number
  referentials: AppReferentials
}
