import { loadHomeBackground, loadSearchBackground, loadSearchScreen } from './loaders'

import type { BootstrapRegistry, BootstrapRouteData } from '#shared/types/bootstrap'

/**
 * L'écran d'une recherche, quel que soit l'outil qui l'affiche — la Visionneuse ou la Table lisent
 * exactement le même jeu.
 *
 * Sans identifiant, rien de critique n'est déclaré : c'est le cas du préchargement au repos, qui
 * résout les routes sans paramètres, et un appel partirait pour revenir en erreur.
 */
const searchScreen = (uuid: string | undefined): BootstrapRouteData =>
  uuid
    ? { critical: [loadSearchScreen(uuid)], background: [loadSearchBackground()] }
    : { critical: [], background: [loadSearchBackground()] }

/**
 * Registre de bootstrap de l'espace entreprise : par route, le critique (bloquant) et
 * l'arrière-plan.
 *
 * Deux familles d'écrans seulement — l'accueil, et les deux outils d'une recherche. Les autres
 * (crédits, équipe, entreprise, profil) gardent le chargement de leur composable : ils n'ont ni jeu
 * à partager ni écran suivant à anticiper, et les faire passer ici ne servirait rien.
 *
 * **Le préchargement est en arrière-plan sur les trois routes**, et c'est le cœur de ce registre.
 * Sur l'accueil il charge l'état de toutes les recherches ouvertes, puis les trente premières fiches
 * de celles qu'il affiche, puis remplit le reste ; sur un écran de recherche il charge l'état et
 * enchaîne sur ce même remplissage, la recherche ouverte en tête.
 *
 * L'identifiant entre dans la clé des chargeurs d'une route paramétrée : sans lui, changer de
 * recherche changerait l'adresse sans rien recharger.
 */
export const ENTREPRISE_BOOTSTRAP_REGISTRY: BootstrapRegistry = {
  /**
   * L'ACCUEIL NE DÉCLARE RIEN DE CRITIQUE, et c'est délibéré.
   *
   * Sa collection est déjà chargée de façon bloquante par l'écran, et elle doit l'être À CHAQUE
   * VISITE : ses compteurs — nouveautés, vus, retenus — bougent dès qu'on ouvre une recherche.
   * Déclarée ici, sa garde la tairait au retour, et l'accueil afficherait des compteurs d'avant.
   */
  index: () => ({ critical: [], background: [loadHomeBackground()] }),
  /**
   * L'ÉCRAN QUI LISTE TOUTES LES RECHERCHES — la seule surface où la file entière se voit.
   *
   * Sans cette entrée, une route absente du registre reçoit un plan vide : ni état de travail, ni
   * remplissage. Le recruteur pouvait donc rester sur cet écran sans qu'une seule recherche se
   * charge, et y viser une carte ne réordonnait rien puisqu'aucun jeu n'était connu.
   *
   * Rien de bloquant : la collection charge la sienne, comme l'accueil.
   */
  recherches: () => ({ critical: [], background: [loadHomeBackground()] }),
  'recherches-uuid': (ctx) => searchScreen(ctx.params.uuid),
  'recherches-uuid-table': (ctx) => searchScreen(ctx.params.uuid),
}
