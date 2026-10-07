import { useCandidateStore } from '../stores/candidate'
import { useCandidatesStore } from '../stores/candidates'
import { useDashboardStore } from '../stores/dashboard'
import { useJarviStore } from '../stores/jarvi'
import { useOrganizationsStore } from '../stores/organizations'
import { useActivityStore } from '../stores/activity'
import { useOrganizationStore } from '../stores/organization'
import { useOverflowingSearchesStore } from '../stores/overflowingSearches'
import { useReferentialsStore } from '../stores/referentials'
import { useUsersStore } from '../stores/users'
import { useVivierGapStore, vivierGapKey } from '../stores/vivierGap'
import { useVivierStore } from '../stores/vivier'

import type { BootstrapLoader } from '#shared/types/bootstrap'
import type { AdminActivityCollection } from '#shared/types/admin-activity'
import type { AdminOrganizationDetail } from '#shared/types/admin-organization'
import type { AdminOverflowingSearchCollection } from '#shared/types/admin-search'
import type { components } from '#shared/types/api'
import type { AdminCandidateDetail, AdminCandidateQueue } from '#shared/types/candidate'
import type { AdminDashboard } from '#shared/types/dashboard'
import type { AdminJarvi } from '#shared/types/jarvi'
import type { OrganizationReviewQueue, OrganizationReviewStatus } from '#shared/types/organization'
import type { AdminUserQueue } from '#shared/types/user'

type AdminVivier = components['schemas']['AdminVivier']
type AdminVivierGap = components['schemas']['AdminVivierGap']
type AdminReferential = components['schemas']['AdminReferential']

// `useRequestFetch` et non `$fetch` brut : au SSR il transmet les en-têtes de la requête entrante
// (dont le cookie de session), sans quoi le BFF ne trouve pas la session (`requireUserSession`) et
// répond 401. Côté client, c'est un `$fetch` normal (le navigateur envoie le cookie lui-même).

/** Snapshot du vivier : hydrate le store depuis le BFF, no-op s'il est déjà chargé. */
export const loadVivier = (): BootstrapLoader => {
  const store = useVivierStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/vivier',
    isLoaded: () => store.getVivier !== null,
    load: async () => {
      store.setVivier(await request<AdminVivier>('/api/admin/vivier'))
    },
    // Lourd (~29 agrégats côté API, dont des unnest JSON plein-table) et rarement la page
    // d'atterrissage : hors preload au repos. Reste critique sur /vivier et /candidats (nav).
    eager: false,
  }
}

/** File des candidats (vivier cross-tenant) : hydrate le store depuis le BFF, no-op si déjà chargée. */
export const loadCandidates = (): BootstrapLoader => {
  const store = useCandidatesStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/candidates',
    isLoaded: () => store.getQueue !== null,
    load: async () => {
      store.setQueue(await request<AdminCandidateQueue>('/api/admin/candidates'))
    },
  }
}

/** Fiche d'un candidat (route paramétrée) : hydrate le store pour CET id, no-op s'il est déjà là. */
export const loadCandidate = (id: string): BootstrapLoader => {
  const store = useCandidateStore()
  const request = useRequestFetch()

  return {
    key: `/api/admin/candidates/${id}`,
    isLoaded: () => store.getLoadedId === id,
    load: async () => {
      store.setCandidate(await request<AdminCandidateDetail>(`/api/admin/candidates/${id}`), id)
    },
  }
}

/** Compteurs de la home admin : hydrate le store depuis le BFF, no-op s'ils sont déjà chargés. */
export const loadDashboard = (): BootstrapLoader => {
  const store = useDashboardStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/dashboard',
    isLoaded: () => store.getDashboard !== null,
    load: async () => {
      store.setDashboard(await request<AdminDashboard>('/api/admin/dashboard'))
    },
  }
}

/** Comptes tous espaces confondus : hydrate le store depuis le BFF, no-op si déjà chargés. */
export const loadUsers = (): BootstrapLoader => {
  const store = useUsersStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/users',
    isLoaded: () => store.getQueue !== null,
    load: async () => {
      store.setQueue(await request<AdminUserQueue>('/api/admin/users'))
    },
  }
}

/** Supervision Jarvi : hydrate le store depuis le BFF, no-op si déjà chargée. */
export const loadJarvi = (): BootstrapLoader => {
  const store = useJarviStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/jarvi',
    isLoaded: () => store.getJarvi !== null,
    load: async () => {
      store.setJarvi(await request<AdminJarvi>('/api/admin/jarvi'))
    },
  }
}

/**
 * File de revue des entreprises pour un filtre (défaut `EN_REVUE`, la file de travail) : hydrate le
 * store depuis le BFF, no-op si une file est déjà chargée. Le changement de filtre et le refetch
 * sur décision sont portés par useOrganizationReview, pas par le preload.
 */
export const loadOrganizations = (
  status: OrganizationReviewStatus = 'EN_REVUE',
): BootstrapLoader => {
  const store = useOrganizationsStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/organizations',
    isLoaded: () => store.getQueue !== null,
    load: async () => {
      const queue = await request<OrganizationReviewQueue>('/api/admin/organizations', {
        query: { status },
      })
      store.setQueue(queue, status)
    },
  }
}

/**
 * Détail d'un manque de qualification (route paramétrée, filtre dans l'adresse) : hydrate le store
 * pour CETTE métrique et CE motif, no-op si la vue est déjà là. Choisir un motif change l'adresse
 * sans changer de route — d'où la clé composite, sans quoi la vue précédente resterait affichée.
 */
export const loadVivierGap = (metric: string, reason: string): BootstrapLoader => {
  const store = useVivierGapStore()
  const request = useRequestFetch()
  const key = vivierGapKey(metric, reason)

  return {
    key: `/api/admin/vivier/gaps/${key}`,
    isLoaded: () => store.getLoadedKey === key,
    load: async () => {
      const gap = await request<AdminVivierGap>(`/api/admin/vivier/gaps/${metric}`, {
        query: reason ? { reason } : {},
      })
      store.setGap(gap, key)
    },
  }
}

/**
 * Référentiel qualifié pour un type (défaut `METIER`), page 1 : hydrate le store depuis le BFF,
 * no-op si un référentiel est déjà chargé. Le changement de type/page/statut et le refetch sur
 * relecture sont portés par useReferentials, pas par le preload.
 */
export const loadReferentials = (type: 'METIER' | 'COMPETENCE' = 'METIER'): BootstrapLoader => {
  const store = useReferentialsStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/referentials',
    isLoaded: () => store.getReferential !== null,
    load: async () => {
      const data = await request<AdminReferential>(`/api/admin/referentials/${type}`, {
        query: { page: 1, perPage: 25 },
      })
      store.setReferential(data, `${type}|1|`)
    },
  }
}

/** Taille de page de l'inventaire, partagée par le chargeur et le composable qui pagine. */
export const OVERFLOW_PER_PAGE = 25

/**
 * L'inventaire des recherches qui débordent : première page, triée par l'API du plus large au plus
 * étroit.
 *
 * Le seuil n'est pas un paramètre : c'est la borne de chargement des recherches, et l'API la
 * connaît. La demander d'ici en ferait un second réglage à tenir d'accord avec le premier.
 */
export const loadOverflowingSearches = (): BootstrapLoader => {
  const store = useOverflowingSearchesStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/searches',
    isLoaded: () => store.getCollection !== null,
    load: async () => {
      store.setCollection(
        await request<AdminOverflowingSearchCollection>('/api/admin/searches', {
          query: { page: 1, perPage: OVERFLOW_PER_PAGE },
        }),
        1,
      )
    },
  }
}

/** Taille de page du fil, partagée par le chargeur et le composable qui pagine. */
export const ACTIVITY_PER_PAGE = 25

/**
 * Le fil des visites de profil : première page, triée par l'API du plus récent au plus ancien.
 */
export const loadActivity = (): BootstrapLoader => {
  const store = useActivityStore()
  const request = useRequestFetch()

  return {
    key: '/api/admin/activity',
    isLoaded: () => store.getCollection !== null,
    load: async () => {
      store.setCollection(
        await request<AdminActivityCollection>('/api/admin/activity', {
          query: { page: 1, perPage: ACTIVITY_PER_PAGE },
        }),
        1,
      )
    },
  }
}

/**
 * La fiche d'une entreprise : solde, abonnement, membres et journal de crédits.
 *
 * Un identifiant inconnu est mémorisé comme **fiche vide pour cet id**, et non laissé remonter
 * comme une panne : l'écran doit pouvoir dire « cette entreprise n'existe pas » plutôt que proposer
 * de réessayer indéfiniment.
 */
export const loadOrganization = (id: string): BootstrapLoader => {
  const store = useOrganizationStore()
  const request = useRequestFetch()

  return {
    key: `/api/admin/organizations/${id}`,
    isLoaded: () => store.getLoadedId === id,
    load: async () => {
      try {
        store.setOrganization(
          await request<AdminOrganizationDetail>(`/api/admin/organizations/${id}`),
          id,
        )
      } catch (error) {
        if (404 === (error as { statusCode?: number }).statusCode) {
          store.setOrganization(null, id)

          return
        }

        throw error
      }
    },
  }
}
