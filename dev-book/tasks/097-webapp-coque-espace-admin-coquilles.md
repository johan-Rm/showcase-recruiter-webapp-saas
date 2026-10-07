# TASK 097 — webapp : coque de l'espace admin (dashboard, sidebar, topbar) + coquilles UI

- **Créée le** : 2026-07-20.
- **Statut** : ✅ livré (base : PR #83 ; refonte layout standard + `DashboardBody` + `AdminTopbar`
  sur `feature/espace-admin`). **Coquilles UI** en place — leur back est cadré en 099–102.
- **Type** : Front — Webapp Nuxt (espace admin).
- **Dépend de** : tâche `083` (file de revue entreprises, seul écran avec back vivant) · `025a`
  (rôles) · tokens de taille du socle (`base/app/assets/css/sizing.css`).
- **Réf cadrage** : `dev-book/cadrage/admin/ui/arborescence.md` (carte P3).
- **Réf code** : `spaces/admin/app/layouts/dashboard.vue`, `components/AdminTopbar.vue`,
  `components/DashboardBody.vue`, `pages/*`.

## Contexte

L'espace admin (`admin.showcase-recruiter-webapp-saas.example`, P3) n'avait qu'un écran (`/entreprises`, tâche 083) sur
l'ancien layout. Cette tâche pose la **coque** de tout l'espace et les **coquilles UI** des écrans
à venir, pour rendre l'admin navigable de bout en bout avant de câbler les API une à une.

## Livré

- **Coque plein écran** (`h-[100dvh]`, `overflow-hidden`, aucun scroll de page ; bornée
  `max-w-shell` puis centrée). Zones scrollables **définies** : topbar / sidebar / gouttière /
  section haute fixes ; seules la zone principale et la secondaire défilent (desktop).
- **Layout Nuxt standard** (`definePageMeta({ layout: 'dashboard' })`) + composant
  `DashboardBody` portant le gabarit A/B/C/D (slots nommés). **Pas** de `<NuxtLayout>` imbriqué
  dans les pages (async + transition `out-in` → écran noir : piège écarté).
- **Sidebar** : navigation réelle **Pilotage** (Tableau de bord · Entreprises · Candidats) /
  **Supervision** (Jarvi · Utilisateurs) + **Compte**, icônes lucide, état actif accent.
- **`AdminTopbar`** : breadcrumb (gauche) ; thème · notifications (cloche + badge + drawer,
  contenu en coquille) · menu compte (Mon compte, langue, déconnexion) (droite). **Pas de chip
  crédits** (solde recruteur, sans objet pour un opérateur admin).
- **6 écrans** : `/entreprises` **fonctionnel** (porté dans la coque) ; **coquilles** pour la home,
  `/candidats`, `/utilisateurs`, `/jarvi`, `/compte`.
- **i18n** fr/en/ar (`nav.admin.*`, `page.admin.*`, `admin.notifications.*`) ; primitive `.skeleton`
  partagée dans le socle.

## Règles

- Modèle **plein écran commun à tous les espaces** ; **lecture seule par défaut** (invariant P3).
- Aucun texte en dur (i18n 3 langues), mobile-first, accessibilité clavier.
- Écrans réservés à la build `admin` (404 natif ailleurs) et aux rôles admin.

## Reste

- **Tiroir mobile** : < lg la sidebar est masquée → aucune nav sur petit écran → tâche `098`.
- **Câblage back des coquilles** : `/candidats` (`099`), `/utilisateurs` (`100`), `/jarvi` (`101`),
  home KPI (`102`).
- Notifications : contenu réel (API notifications non cadrée).

## Hors périmètre

- Tout le back des écrans coquilles (→ 099–102).
- `/invitations`, `/activite`, `/notifications` de la carte P3 (non démarrés).
