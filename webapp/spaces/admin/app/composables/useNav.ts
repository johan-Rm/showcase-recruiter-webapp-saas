import type { NavigationMenuItem } from '@nuxt/ui'

import { resolveNavRoute } from '../utils/navRoute'

// Source UNIQUE de la navigation admin : la sidebar, le tiroir mobile et le fil d'ariane lisent
// tous cette déclaration. Elle a longtemps été écrite deux fois — les items dans la sidebar, une
// table de libellés dans la topbar — et les deux avaient divergé : `referentiels` et `vivier`
// n'avaient plus de fil d'ariane du tout.
//
// Chaque entrée porte son NOM DE ROUTE, et c'est lui qui relie une URL à son libellé. Les clés
// i18n suivent ce nom de route et non le texte affiché : `nav.admin.vivier` nomme l'entrée de la
// route `vivier`, quel que soit le libellé qu'on y met.

interface NavEntry {
  /** Nom de route Nuxt, sans suffixe de locale. */
  route: string
  /** Chemin non localisé, passé à `localePath`. */
  path: string
  labelKey: string
  icon: string
  /** Actif seulement sur la route exacte — réservé à l'accueil, préfixe de toutes les autres. */
  exact?: boolean
}

interface NavGroup {
  labelKey: string
  entries: readonly NavEntry[]
}

const NAV_GROUPS: readonly NavGroup[] = [
  {
    labelKey: 'nav.admin.pilotage',
    entries: [
      {
        route: 'index',
        path: '/',
        labelKey: 'nav.admin.dashboard',
        icon: 'i-lucide-layout-dashboard',
        exact: true,
      },
    ],
  },
  {
    labelKey: 'nav.admin.comptes',
    entries: [
      {
        route: 'entreprises',
        path: '/entreprises',
        labelKey: 'nav.admin.entreprises',
        icon: 'i-lucide-building-2',
      },
      {
        route: 'candidats',
        path: '/candidats',
        labelKey: 'nav.admin.candidats',
        icon: 'i-lucide-users',
      },
      {
        route: 'utilisateurs',
        path: '/utilisateurs',
        labelKey: 'nav.admin.utilisateurs',
        icon: 'i-lucide-user-cog',
      },
    ],
  },
  {
    labelKey: 'nav.admin.supervision',
    entries: [
      { route: 'jarvi', path: '/jarvi', labelKey: 'nav.admin.jarvi', icon: 'i-lucide-database' },
      {
        route: 'referentiels',
        path: '/referentiels',
        labelKey: 'nav.admin.referentiels',
        icon: 'i-lucide-book-check',
      },
      {
        route: 'vivier',
        path: '/vivier',
        labelKey: 'nav.admin.vivier',
        icon: 'i-lucide-heart-pulse',
      },
      {
        route: 'recherches',
        path: '/recherches',
        labelKey: 'nav.admin.recherches',
        icon: 'i-lucide-list-filter',
      },
      {
        route: 'activite',
        path: '/activite',
        labelKey: 'nav.admin.activite',
        icon: 'i-lucide-eye',
      },
    ],
  },
]

// Écrans joignables sans passer par la sidebar : le compte vit dans le menu de l'avatar. Ils n'ont
// pas d'entrée de navigation, mais le fil d'ariane doit savoir les nommer.
const OFF_NAV_LABELS: Readonly<Record<string, string>> = {
  compte: 'nav.admin.compte',
}

const NAV_ENTRIES = NAV_GROUPS.flatMap((group) => group.entries)
const KNOWN_ROUTES = [...NAV_ENTRIES.map((entry) => entry.route), ...Object.keys(OFF_NAV_LABELS)]

/**
 * Navigation de l'espace admin : les groupes prêts pour `UNavigationMenu`, et le libellé de la
 * page courante pour le fil d'ariane. Ajouter une entrée à `NAV_GROUPS` la fait apparaître dans
 * les deux, sans toucher à un autre fichier.
 */
export const useNav = () => {
  const { localePath } = useLocale()
  const { $i18n } = useNuxtApp()
  const route = useRoute()

  const navGroups = computed<NavigationMenuItem[][]>(() =>
    NAV_GROUPS.map((group): NavigationMenuItem[] => [
      { label: $i18n.t(group.labelKey), type: 'label' },
      ...group.entries.map((entry): NavigationMenuItem => ({
        label: $i18n.t(entry.labelKey),
        icon: entry.icon,
        to: localePath(entry.path),
        exact: entry.exact ?? false,
      })),
    ]),
  )

  // Une fiche hérite du libellé de sa section (`candidats-id` → « Le vivier ») ; une route hors
  // navigation laisse le fil d'ariane à son seul premier niveau.
  const currentLabel = computed<string | null>(() => {
    const resolved = resolveNavRoute(route.name, KNOWN_ROUTES)
    if (!resolved) return null

    const labelKey =
      OFF_NAV_LABELS[resolved] ?? NAV_ENTRIES.find((entry) => entry.route === resolved)?.labelKey

    return labelKey ? $i18n.t(labelKey) : null
  })

  return { navGroups, currentLabel }
}
