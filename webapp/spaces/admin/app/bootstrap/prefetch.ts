import { loadCandidate } from './loaders'
import { useCandidatesStore } from '../stores/candidates'

import type { PrefetchRegistry } from '#shared/types/bootstrap'

/**
 * Règles de préchargement prédictif de l'espace admin.
 *
 * L'admin précharge déjà tous ses domaines au repos (`139`) — sauf les fiches paramétrées. Le seul
 * gain prédictif réel est donc : sur la liste des candidats, anticiper la fiche de chaque candidat
 * visible pour que le clic soit instantané. Dédupe par id (via `isLoaded` du loader).
 */
export const ADMIN_PREFETCH_RULES: PrefetchRegistry = {
  candidats: () =>
    useCandidatesStore().getCandidates.map((candidate) => loadCandidate(candidate.id)),
}
