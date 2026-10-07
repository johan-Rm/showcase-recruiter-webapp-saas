import {
  loadActivity,
  loadCandidate,
  loadCandidates,
  loadDashboard,
  loadJarvi,
  loadOrganization,
  loadOrganizations,
  loadOverflowingSearches,
  loadReferentials,
  loadUsers,
  loadVivier,
  loadVivierGap,
} from './loaders'
import { gapMetricLabel } from '../utils/vivierMetrics'

import type { BootstrapRegistry } from '#shared/types/bootstrap'

/**
 * Registre de bootstrap de l'espace admin : par route, le critique (bloquant) et l'arrière-plan.
 *
 * Migration incrémentale — seules les routes déjà servies par un store y figurent ; les autres
 * gardent le fetch de leur composable jusqu'à leur propre store. Le
 * vivier est critique sur `/vivier` (c'est la vue) ET sur `/candidats` (il y alimente le taux de
 * remplissage, rendu au SSR — le mettre en arrière-plan le ferait apparaître après coup côté
 * client). Sur `/candidats`, la liste et le vivier sont tous deux critiques.
 */
export const ADMIN_BOOTSTRAP_REGISTRY: BootstrapRegistry = {
  index: () => ({ critical: [loadDashboard()], background: [] }),
  vivier: () => ({ critical: [loadVivier()], background: [] }),
  // Route paramétrée dont le filtre vit dans l'adresse : le motif entre dans la clé du loader, sinon
  // sélectionner un motif changerait l'URL sans rien recharger.
  //
  // Une métrique que le front ne connaît pas n'est pas chargée du tout : l'appel partirait pour
  // revenir en 404, et la page afficherait un bandeau d'erreur technique là où elle doit
  // simplement dire qu'elle n'existe pas.
  'vivier-metric': (ctx) =>
    ctx.params.metric && gapMetricLabel(ctx.params.metric)
      ? { critical: [loadVivierGap(ctx.params.metric, ctx.query.reason ?? '')], background: [] }
      : { critical: [], background: [] },
  candidats: () => ({ critical: [loadCandidates(), loadVivier()], background: [] }),
  // Route paramétrée : chargée à la nav (via le middleware), jamais par le preload (pas d'id au repos).
  'candidats-id': (ctx) =>
    ctx.params.id
      ? { critical: [loadCandidate(ctx.params.id)], background: [] }
      : { critical: [], background: [] },
  utilisateurs: () => ({ critical: [loadUsers()], background: [] }),
  jarvi: () => ({ critical: [loadJarvi()], background: [] }),
  entreprises: () => ({ critical: [loadOrganizations()], background: [] }),
  // Route paramétrée : chargée à la nav (via le middleware), jamais par le preload (pas d'id au repos).
  'entreprises-id': (ctx) =>
    ctx.params.id
      ? { critical: [loadOrganization(ctx.params.id)], background: [] }
      : { critical: [], background: [] },
  referentiels: () => ({ critical: [loadReferentials()], background: [] }),
  recherches: () => ({ critical: [loadOverflowingSearches()], background: [] }),
  activite: () => ({ critical: [loadActivity()], background: [] }),
}
