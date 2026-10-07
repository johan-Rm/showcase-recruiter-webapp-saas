# TASK ADM-016 — l'espace admin cesse de répéter son nom

- **Créée le** : 2026-08-04.
- **Session** : `a57b4ab0-a355-4f38-a6da-42858884e758`.
- **Statut** : ✅ fait le 2026-08-04 — l'espace admin ne dit plus deux fois son nom : cinq composants
  et neuf composables renommés, marqueurs et noms d'export compris, et la dette ouverte le matin même
  par `WEB-012` retirée en entier. Deux cases de la définition de terminé ont été **reformulées**
  plutôt que cochées de force, et deux points du cadrage corrigés — la carte décrivait un état du
  dépôt qui avait changé depuis.
- **Type** : Admin — renommage, sans changement de comportement.
- **Tests** : `sans objet — un renommage ne crée aucun comportement`. La preuve est ailleurs, et elle
  est double : le **type-check par espace** (`bun run quality-type-check`, une passe par espace)
  refuse tout appel resté sur un ancien nom — un composant inconnu dans un template comme un
  composable non résolu sont des erreurs de type ; et la gate `app-no-space-prefix` de `WEB-012`
  passe au vert une fois les quatorze lignes de dette retirées. Le test existant
  `spaces/admin/app/utils/navRoute.vitest.ts` n'est pas touché.
- **Réf** : **amont bloquant `WEB-012`**, qui écrit la règle et ouvre la dette que cette carte retire
  · [webapp/base/app/components/README.md](../../webapp/base/app/components/README.md) —
  « Marqueur de composant sur la racine » ·
  [arborescence-admin.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md) ·
  [webapp/scripts/ci/rules.yaml](../../webapp/scripts/ci/rules.yaml) · **les vingt-cinq cartes closes
  qui citent les anciens noms** — `ADM-002`, `ADM-004`, `ADM-005`, `ADM-006`, `ADM-009` à `ADM-013`,
  `WEB-005`, `WEB-006`, `WEB-009`, `097`, `098`, `099`, `127`, `130`, `133`, `134`, `136`, `137`,
  `142`, `143`, `055b` — **qu'on ne réécrit pas**, cf. « Décisions de cadrage ».

## Amont bloquant

**Cette carte ne peut pas démarrer avant que `WEB-012` soit mergée sur `develop` et que
`espace/admin` soit rebasée dessus.** Deux raisons, et aucune n'est contournable :

- les quatorze lignes de dette à retirer **n'existent pas** sur cette branche tant que `WEB-012` n'y
  est pas ;
- la gate `app-no-space-prefix`, qui est la preuve que le renommage est complet, n'existe pas non
  plus.

`webapp/scripts/ci/rules.yaml` est un fichier partagé par toutes les lanes : le toucher depuis deux
branches en même temps produit un conflit garanti. `WEB-011` a rencontré exactement cette situation
avec `ADM-015` et l'a écrite dans son statut plutôt que de la subir.

## Contexte

**L'espace admin répète son propre nom dans quatorze fichiers, et il est le seul à le faire.**

| | Espace admin | Espace entreprise |
| --- | --- | --- |
| Composants de coque | `AdminTopbar.vue` → `<AdminTopbar>` | `Topbar.vue` → `<Topbar>` |
| Composables | `useAdminNav`, `useAdminVivier`… | `useSearch`, `useCockpit`… |

Le préfixe ne protège de rien : chaque espace est **compilé séparément** (`extends: ['./spaces/<espace>',
'./base']`, une build par `SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE`), donc `<Topbar>` de l'entreprise et `<AdminTopbar>` de
l'admin ne se rencontrent jamais. Vérifié en dérivant les noms d'appel des vingt-six composants du
socle et des trente-deux des espaces : **aucun doublon dans aucune des quatre builds**, et aucun
composable d'espace ne porte le nom d'un composable du socle.

Et l'admin ne se l'applique pas à lui-même. Trois de ses couches nomment déjà sans préfixe :

```
stores/      users.ts → useUsersStore · vivier.ts → useVivierStore · jarvi.ts · candidates.ts   ✅
utils/       navRoute.ts · vivierMetrics.ts                                                     ✅
bootstrap/   loadUsers · loadVivier · loadCandidates · loadJarvi                                 ✅
components/  5 fichiers Admin*                                                                   ❌
composables/ 9 fichiers useAdmin*                                                                ❌
```

Ce renommage ne change donc pas de convention : il **finit** celle que l'espace applique partout
ailleurs.

## Périmètre

### Les cinq composants, marqueur de racine compris

| Fichier | Devient | Marqueur |
| --- | --- | --- |
| `AdminTopbar.vue` | `Topbar.vue` | `admin-topbar` → `topbar` |
| `AdminSidebarNav.vue` | `SidebarNav.vue` | `admin-sidebar-nav` → `sidebar-nav` |
| `AdminMobileDrawer.vue` | `MobileDrawer.vue` | `admin-mobile-drawer` → `mobile-drawer` |
| `AdminCandidateTable.vue` | `CandidateTable.vue` | `admin-candidate-table` → `candidate-table` |
| `AdminFillRateSection.vue` | `FillRateSection.vue` | `admin-fill-rate-section` → `fill-rate-section` |

`DashboardBody.vue` et les trois composants de `review/` ne bougent pas : ils n'ont jamais porté le
préfixe.

### Les neuf composables — fichier **et** nom d'export

`useAdminCandidate` · `useAdminCandidates` · `useAdminDashboard` · `useAdminDrawer` ·
`useAdminJarvi` · `useAdminNav` · `useAdminReferentials` · `useAdminUsers` · `useAdminVivier`
→ chacun amputé de son `Admin`. `useOrganizationReview` est déjà conforme.

### Le reste

- **Les appelants** : une vingtaine de fichiers, tous dans `spaces/admin/` — pages, layout
  `dashboard.vue`, `stores/vivier.ts`, `bootstrap/loaders.ts`, et les composants entre eux.
- **La ligne de dette** `spaces/admin/app/components/AdminFillRateSection.vue # admin` dans
  l'exception de `components-no-page-region` ([rules.yaml](../../webapp/scripts/ci/rules.yaml)), plus
  les quatorze lignes ouvertes par `WEB-012`.
- **`arborescence-admin.md`** : deux mentions de `useAdminNav`, dont une dans l'encadré qui dit que
  les libellés de la barre latérale vivent là « et nulle part ailleurs ».
- **L'identifiant `admin-drawer`** (`id` dans le tiroir, `aria-controls` dans la barre haute) devient
  `mobile-drawer`.

## Hors périmètre

- **Les deux phrases de README** qui fondent la règle — c'est `WEB-012`, lane socle.
- **Toute réorganisation en dossiers.** Les composants de coque restent à la racine : c'est la
  décision, pas un compromis. Aucun dossier `layout/` n'est créé.
- **Les vingt-cinq cartes closes** qui citent les anciens noms.
- **Tout changement de comportement, de rendu ou de style.** Un diff qui touche autre chose que des
  noms est un diff hors périmètre.

## Décisions de cadrage

**Les noms nus, sans préfixe ni dossier.** Le contexte `Layout` a été explicitement écarté : il
n'apporte rien sur une barre haute, et la layer porte déjà le domaine.

**Y compris pour les trois noms qui deviennent génériques** — `useNav`, `useDrawer`, `useUsers`.
L'objection « trop génériques » a été levée par le dépôt lui-même : le store voisin s'appelle déjà
`users`, le chargeur déjà `loadUsers`. Aucun ne percute le socle aujourd'hui, vérifié sur les
dix-sept composables de `base/`.

**L'identifiant `admin-drawer` voyage avec — mais pas celui que cette carte annonçait.** Le cadrage
décrivait un `id` dans le tiroir et un `aria-controls` sur le hamburger, lus dans `ADM-002`. Ils
**n'existent plus** : `ADM-004` a converti le tiroir en `USlideover`, qui pose lui-même la relation
entre le bouton et le panneau. Ce qui subsistait est une **clé d'état partagé**,
`useState('admin-drawer-open')`, devenue `mobile-drawer-open`. Même intention — ne pas laisser une
trace orpheline derrière un composant renommé —, autre objet. La leçon est générale : une carte
close décrit l'état du jour où elle a été écrite, pas l'état courant.

**Les `Admin*` du contrat d'API restent.** `AdminVivier`, `AdminDashboard`, `AdminCandidateDetail`
et leurs voisins sont des types générés depuis `openapi.json` et importés de `#shared/types`. Ce ne
sont pas des noms de fichiers, la règle ne les vise pas, et les renommer sortirait de cette lane.

**Les cartes closes ne se réécrivent pas.** Une carte close est le procès-verbal de ce qui a été fait
ce jour-là ; la réécrire pour qu'elle emploie les nouveaux noms falsifie la trace — on ne saurait plus
que le composant s'appelait autrement quand `ADM-004` l'a converti en `USlideover`. C'est **cette**
carte qui porte la table de correspondance, pour que le lien se refasse dans les deux sens.

## Règles et cas limites

- **Pour un composable, le nom qui compte est celui de l'export, pas celui du fichier.** Nuxt scanne
  les exports. Renommer `useAdminJarvi.ts` en `useJarvi.ts` en laissant
  `export const useAdminJarvi = …` ne change **rien** à l'auto-import et ne produit aucune erreur :
  juste un fichier dont le nom ment. Les deux changent ensemble.
- **Le marqueur de racine est vérifié en CI.** Un fichier renommé dont la première classe reste
  `admin-topbar` fait rougir `components-root-marker`.
- **La ligne d'exception de `rules.yaml` est le piège de cette tâche.** Renommer
  `AdminFillRateSection.vue` sans mettre la ligne à jour, et l'exception ne correspond plus à rien :
  la règle `components-no-page-region` s'applique alors au fichier renommé, qui la viole toujours, et
  la CI passe au rouge sans que le diff n'ait l'air fautif.
- **Aucune référence ne sort de l'espace admin** — vérifié sur tout le dépôt. Les trois autres
  espaces, `base/`, `shared/` et `services/` ne portent aucun fichier à préfixe d'espace.

## Documentation impactée

- [arborescence-admin.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md) — les deux
  mentions de `useAdminNav`.

Aucun README de dossier n'est touché : les conventions sont écrites par `WEB-012`, cette carte les
applique.

## Définition de terminé

- [x] `WEB-012` est mergée et `espace/admin` rebasée sur `develop` **avant** la première écriture.
- [x] Les cinq composants sont renommés, avec leur marqueur de racine.
- [x] Les neuf composables sont renommés, **fichier et nom d'export**.
- [x] Tous les appelants suivent. **Case reformulée** : elle exigeait « aucune occurrence de
      `Admin[A-Z]` ni `useAdmin` » dans `spaces/admin/app/`, ce qui est intenable et n'a jamais été
      l'objectif — neuf **types du contrat d'API** (`AdminVivier`, `AdminDashboard`,
      `AdminCandidateDetail`, `AdminCandidateQueue`, `AdminCandidate`, `AdminUserQueue`,
      `AdminUser`, `AdminJarvi`, `AdminReferential`) sont importés de `#shared/types` et générés
      depuis `openapi.json`. Ce sont les seuls survivants, et ils appartiennent à la lane API. Les
      deux types **locaux** qui répétaient l'espace, eux, sont partis (`AdminNavEntry` → `NavEntry`,
      `AdminNavGroup` → `NavGroup`).
- [x] Les quatorze lignes de dette de `app-no-space-prefix` sont retirées de `rules.yaml`, et la
      ligne d'exception de `components-no-page-region` pointe le nouveau nom.
- [x] `arborescence-admin.md` cite `useNav`.
- [x] La dernière trace `admin-` du tiroir est partie. **Case reformulée** : elle visait un `id` et
      un `aria-controls` qui n'existent plus depuis qu'`ADM-004` a converti le tiroir en
      `USlideover`. Ce qui subsistait est la clé d'état `useState('admin-drawer-open')`, devenue
      `mobile-drawer-open`.
- [x] `bun run quality-type-check` est vert sur les quatre espaces — c'est lui qui prouve qu'aucun
      appelant n'a été oublié.
- [x] `bun run quality-conventions-check` est vert, dette comprise.
- [x] L'espace admin rend à l'identique — barre haute, barre latérale, tiroir mobile, écran candidats
      — vérifié dans le navigateur, pas déduit.
- [x] La table de correspondance ancien → nouveau est dans cette carte, puisque les vingt-cinq cartes
      closes gardent les anciens noms.

## Vérification

**Le type-check des quatre espaces** — la preuve qui compte pour un renommage : un composant inconnu
dans un gabarit et un composable non résolu sont tous deux des erreurs de type. Rejoué après la
dernière retouche, pas seulement avant.

```
→ type-check : espace public · candidat · entreprise · admin      exit 0, aucune sortie
```

**La gate des conventions, dette comprise** :

```
✔ conventions — 13 règles, aucune violation
```

`app-no-space-prefix` n'a plus **aucune** ligne d'exception : la dette ouverte le matin par `WEB-012`
est intégralement retirée, et la règle mord désormais partout.

**Le rendu, regardé et non déduit.** Stack de la lane montée (`make dev-up-space SPACE=admin`),
session ouverte avec le compte de recette `admin@demo.showcase-recruiter-webapp-saas.example`, trois captures :

- le tableau de bord et l'écran candidats, inchangés ;
- le tiroir mobile, ouvert au clic sur le hamburger à 420 px : ses trois groupes et ses sept entrées
  sont là, l'entrée courante marquée.

Dans le HTML servi, les **nouveaux** marqueurs sont présents (`topbar`, `sidebar-nav`,
`dashboard-body`, et `mobile-drawer` une fois le tiroir ouvert) et les anciens sont à **zéro**.

**Les sept tests de l'espace** (`utils/navRoute.vitest.ts`) passent, inchangés.

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `components/{AdminCandidateTable,AdminFillRateSection,AdminMobileDrawer,AdminSidebarNav,AdminTopbar}.vue` **(R)** | Renommés sans le préfixe, marqueur de racine suivi. |
| `composables/useAdmin*.ts` ×9 **(R)** | Renommés, **et leur nom d'export avec** — c'est lui que Nuxt auto-importe. |
| `composables/useNav.ts` **(M)** | Ses deux types locaux perdent aussi le préfixe (`NavEntry`, `NavGroup`). |
| `composables/useDrawer.ts` **(M)** | Clé d'état `admin-drawer-open` → `mobile-drawer-open`. |
| `layouts/dashboard.vue`, 7 pages, `stores/vivier.ts`, `bootstrap/loaders.ts` **(M)** | Les appelants suivent. |
| `webapp/scripts/ci/rules.yaml` **(M)** | Les quatorze lignes de dette retirées ; l'exception de `components-no-page-region` repointée sur `FillRateSection.vue`. |
| `docs/…/arborescence-admin.md` **(M)** | Cite `useNav`. |
| `dev-book/tasks/ADM-016-*.md` **(+)** | Cette carte. |

## Reste à faire (hors périmètre)

- **Les neuf types `Admin*` du contrat d'API** restent, et c'est voulu : ils sont générés depuis
  `openapi.json` et appartiennent à la lane API. Les renommer serait un changement de contrat, pas
  un renommage de fichiers.
- **Le conteneur `worker` échoue au démarrage sur les quatre lanes** —
  `network 27a85b2f… not found`. Sans effet sur cette tâche, constaté en montant la stack. Sujet
  d'infra, lane outillage.
