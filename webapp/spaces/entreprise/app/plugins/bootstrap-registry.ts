import { ENTREPRISE_BOOTSTRAP_REGISTRY } from '../bootstrap/registry'
import { loadConfig } from '../bootstrap/loaders'

/**
 * Ce que l'espace entreprise déclare au bootstrap.
 *
 * Le palier MÉTADONNÉES est posé hors du registre de routes parce que la configuration ne dépend
 * d'aucun écran — la recopier dans chaque entrée ferait d'une donnée d'application une donnée de
 * page. Il passe en tête : les écrans en dérivent des libellés et des bornes, et un critique servi
 * avant elle s'afficherait le temps qu'elle arrive.
 *
 * Le registre de routes, lui, couvre l'accueil et les deux outils d'une recherche : le critique de
 * chacun, et le plan de travail en arrière-plan. Les autres écrans chargent encore les leurs.
 *
 * Plugin universel — le registre est résolu au rendu serveur comme côté client.
 */
export default defineNuxtPlugin(() => {
  setBootstrapMetadata(() => [loadConfig()])
  setBootstrapRegistry(ENTREPRISE_BOOTSTRAP_REGISTRY)
})
