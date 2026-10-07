# TASK ADM-011 — webapp admin : la navigation n'a plus qu'une source

- **Créée le** : 2026-08-01.
- **Statut** : ✅ fait le 2026-08-02 — `useAdminNav` déclare seule les sept entrées et leurs trois
  groupes ; sidebar, tiroir mobile et fil d'ariane la lisent. Les cinq écrans sans libellé de fil
  l'ont retrouvé, le repère du fil porte son propre nom, « vivier » ne désigne plus qu'un écran en
  fr / en / ar. Vérifié au DOM vivant et en capture, dans les deux thèmes et en RTL ; gates au
  passage PR.
- **Type** : refactor — la navigation passe à une source unique ; le correctif du fil d'ariane, le
  renommage des libellés et le regroupement des entrées en découlent.
- **Tests** : un `*.vitest.ts` colocalisé dans `spaces/admin/app/utils/`, sur la résolution d'un nom
  de route en libellé — suffixe de locale retiré, route de premier niveau, fiche qui hérite de son
  parent, route inconnue. Le rendu (sidebar, fil d'ariane, tiroir mobile) se prouve en capture,
  comme `ADM-010`.
- **Réf** : `ADM-010` (états actifs, et son reste-à-faire sur le tiroir mobile) · `ADM-005` (la nav
  en `UNavigationMenu`) · `ADM-004` (le tiroir mobile) · `ADM-009` (le pied de sidebar retiré) ·
  [README de `composables/`](../../webapp/base/app/composables/README.md) ·
  [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)
  (le modèle de la page de doc).

## Contexte

La navigation de l'espace admin est décrite **deux fois**. `AdminSidebarNav` porte la liste des
items ; `AdminTopbar` porte, pour le fil d'ariane, une table `ROUTE_LABELS` qui associe un nom de
route à un libellé. Rien ne relie les deux, et elles ont divergé : la sidebar déclare sept
destinations, la table n'en connaît que six et il manque justement `referentiels` et `vivier`. Sur
ces deux pages, `currentLabel` retombe à `null` et l'en-tête n'affiche plus que « Espace admin »,
sans chevron ni nom de page. Même trou sur la fiche candidat, dont la route `candidats-id` n'est pas
dans la table.

Deux autres défauts vivent dans les mêmes fichiers. Le `<nav>` du fil d'ariane s'annonce
« Navigation principale », **exactement comme** le `UNavigationMenu` de la sidebar : deux repères de
navigation homonymes dans la même page, dont un qui n'est pas la navigation principale. Et le mot
« vivier » désigne deux écrans selon l'endroit où on le lit — la sidebar appelle `/vivier` « Santé du
vivier », pendant que le tableau de bord intitule « Voir le vivier » un lien qui mène à
`/candidats`.

## Périmètre

- **`useAdminNav()`**, nouveau composable de `spaces/admin/app/composables/` : l'unique déclaration
  des sept entrées — libellé, icône, `to`, nom de route, groupe. Il a besoin de `localePath` et de la
  réactivité i18n, ce qui est le test d'admission de la couche.
- **`AdminSidebarNav`** rend ses groupes depuis cette source, et ne déclare plus rien.
- **`AdminTopbar`** y lit le libellé de la route courante ; `ROUTE_LABELS` disparaît. Les sept
  entrées sont couvertes, et `/candidats/[id]` affiche le libellé de sa section parente.
- **Le landmark du fil d'ariane** prend une clé à lui, `nav.breadcrumb`.
- **Renommages, en fr / en / ar** : l'entrée `/candidats` devient « Le vivier » ; `/vivier` et son
  titre de page deviennent « État de la base » ; le lien du tableau de bord suit.
- **Trois groupes** : *Pilotage* (Tableau de bord) · *Comptes* (Entreprises, Le vivier,
  Utilisateurs) · *Supervision* (Jarvi, Référentiels, État de la base). Une clé de plus,
  `nav.admin.comptes`, dans les trois langues.
- **Le titre du tiroir mobile** passe en libellé invisible : toujours annoncé, plus affiché.
- **La résolution nom de route → libellé** part en fonction pure dans
  `spaces/admin/app/utils/`, avec son test colocalisé.
- **Le commentaire de périmètre de `webapp/vitest.config.ts`** : il annonce « shared/, services/ »,
  et un test d'espace l'élargit. La règle se corrige là où elle est écrite, pas en silence.
- **`docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md`**, mise à jour.
- **Le sous-titre de `/vivier`** : il ouvrait sur « État de la base au {at} », qui devient le titre
  de la page juste au-dessus. Il commence désormais à la date.

## Hors périmètre

- **Le bloc de marque, son alignement et le badge d'espace** — `WEB-005` et `WEB-001`, sur la branche
  du socle. Déjà écartés par `ADM-010`.
- **Le tiers vide en bas de colonne** : résultat voulu de `ADM-009`.
- **Le seuil `lg` (1024 px)** qui masque la sidebar sur tablette portrait.
- **Les URL** : `/candidats` et `/vivier` gardent leur chemin. Seuls les libellés bougent — renommer
  une route casserait les liens en circulation pour un gain nul.
- **Un fil d'ariane à trois niveaux sur la fiche candidat** : elle hérite du libellé parent. Afficher
  l'identité du candidat demanderait de trancher le chargement, l'erreur et le cas d'un profil masqué
  dont le prénom est réduit à une initiale.
- **Les trois autres espaces** : seul l'admin a un fil d'ariane, vérifié par `grep` sur les quatre.

## Décisions de cadrage

- **Une seule carte pour les cinq points.** Ils vivent dans les trois mêmes fichiers et les mêmes
  clés i18n ; les découper ferait écrire trois fois dans le même endroit, pour trois relectures du
  même diff.
- **« Le vivier » est rangé sous « Comptes »** — arbitrage assumé après objection : un vivier de
  profils n'est pas un compte, mais le groupe réunit ce que l'admin administre, et l'alternative
  (rattacher le vivier à ses sources de données) éloignait Entreprises de Candidats.
- **« État de la base » plutôt que « Complétude des profils »** : c'est mot pour mot ce que dit déjà
  le sous-titre de la page, et ça couvre ses cinq sections — la complétude n'en est qu'une, les top
  secteurs, métiers et langues n'y entreraient pas.
- **Le renommage aligne l'admin sur l'entreprise**, qui appelle déjà « Le vivier » sa page de profils
  (`page.entreprise.vivier`). Ce n'est pas un vocabulaire inventé ici.
- **La fonction pure vit dans la lane, pas dans `shared/`.** `shared/` est l'une des trois ressources
  partagées par toutes les lanes ; cette résolution ne sert qu'à l'admin. Le `include` de vitest la
  ramène déjà dans la suite sans toucher à la config.
- **Pas de test de composant.** Monter les composants avec un routeur simulé introduirait une
  pratique que le dépôt n'a nulle part ; le seul morceau vraiment testable sans runtime est la
  fonction pure, et c'est exactement le défaut corrigé.
- **Les libellés arabes sont proposés, pas validés** : `قاعدة المواهب` (Le vivier),
  `حالة القاعدة` (État de la base), `الحسابات` (Comptes). À relire par l'utilisateur. Les deux
  premiers étaient déjà dans le fichier — `page.entreprise.vivier` et le sous-titre de `/vivier`
  les employaient : le renommage reprend le vocabulaire arabe en place, il n'en invente pas.

**Écarts constatés à l'implémentation** — la carte les corrige plutôt que de mentir :

- **`arborescence-admin.md` EXISTE déjà**, et l'index de la section la référençait. Le cadrage l'a
  posée comme une page à créer : c'était faux, la question a été mal posée. Elle est donc **mise à
  jour**, pas écrite : datée du 2026-07-04, elle affirmait qu'aucune route `/v1/admin/*` n'existait
  (il y en a dix), plaçait `/referentiels` en V2 différée alors qu'il est livré, et ignorait
  `/vivier`. Corrigés : l'état API vérifié, l'arborescence en trois groupes, et la mention explicite
  que les LIBELLÉS vivent dans le composable et pas dans la doc.
- **Le hamburger reste affiché sur desktop** : `lg:hidden` ne mord pas, le `display: grid` du
  `<style scoped>` de `.icon-btn` le bat en spécificité (mesuré : `grid` à 1440 et 1024 px). Défaut
  **pré-existant**, découvert en mesurant cette tâche, laissé à une carte ADM dédiée — le corriger
  ici aurait élargi un périmètre déjà validé.

## Règles et cas limites

- **Le critère qui prouve la source unique** : ajouter une entrée au composable doit la faire
  apparaître dans la sidebar, dans le tiroir mobile **et** dans le fil d'ariane, sans toucher à un
  autre fichier.
- **Route inconnue** : le fil d'ariane s'arrête au premier niveau, sans chevron — comportement
  actuel, conservé. Jamais d'exception levée sur un nom de route inattendu.
- **Le suffixe de locale** (`___fr`, `___en`, `___ar`) est retiré avant résolution, et une route
  enfant retombe sur sa section parente : `candidats-id___ar` → `candidats`.
- **Le chevron du fil d'ariane est figé à droite** (`lucide:chevron-right`) : en arabe il pointe à
  contresens de la lecture. Le fil étant réécrit ici, la correction y entre — c'est un ajout au
  périmètre, assumé pendant le cadrage et non demandé.
- **La nav est un seul composant pour deux surfaces** (`ADM-004`) : tout changement se voit aussi
  dans le tiroir mobile, à vérifier en 375 px.
- **Le panneau du tiroir doit garder un nom accessible** : le titre devient invisible, il ne
  disparaît pas.
- **Trois langues obligatoires.** Une clé ajoutée en fr sans son pendant en / ar est une régression
  d'i18n, pas un reste à faire.
- **Les états actifs d'`ADM-010` ne bougent pas** : pilule et liseré sont pilotés par `navUi`, que ce
  refactor déplace mais ne réécrit pas.

## Documentation impactée

- **Mise à jour** :
  [`docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md`](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md)
  — les sept écrans, leurs groupes, leurs routes. La page décrit la structure ; elle **ne recopie pas
  la liste des libellés**, qui vit dans le composable, sous peine de rouvrir la deuxième source que
  cette tâche supprime.
- **Aucun README de dossier ne change** : `spaces/admin/app/utils/` existait déjà
  (`vivierMetrics.ts`), et les conventions de la couche restent celles de
  [`base/app/utils/README.md`](../../webapp/base/app/utils/README.md).
- Aucune autre page ne cite la nav admin ni « Santé du vivier » — vérifié par `grep` sur tout
  `docs/showcase-recruiter-webapp-saas/content`.

## Livré

- **`useAdminNav.ts`** (nouveau) : `NAV_GROUPS` déclare les sept entrées — nom de route, chemin,
  clé i18n, icône — en trois groupes ; `OFF_NAV_LABELS` couvre `/compte`, joignable par l'avatar et
  absent de la barre. Deux valeurs en sortie : `navGroups` pour `UNavigationMenu`, `currentLabel`
  pour le fil d'ariane.
- **`navRoute.ts`** (nouveau) : `stripLocaleSuffix` et `resolveNavRoute`, la remontée d'un nom de
  route vers l'entrée qui le porte. `null` sur une route hors navigation — pas une exception.
- **`AdminSidebarNav.vue`** : les trente-huit lignes d'`items` disparaissent, `navUi` reste tel quel
  — les états actifs d'`ADM-010` sont déplacés, pas réécrits.
- **`AdminTopbar.vue`** : `ROUTE_LABELS` et son `useRoute` disparaissent au profit de
  `currentLabel` ; le `<nav>` du fil prend `nav.breadcrumb` ; son chevron gagne `rtl:rotate-180`.
- **`AdminMobileDrawer.vue`** : `:ui` ajoute `title: 'sr-only'`.
- **i18n (fr / en / ar)** : `nav.breadcrumb` et `nav.admin.comptes` ajoutées ; `candidats` et
  `vivier` renommés dans `nav.admin` **et** `page.admin` ; le sous-titre de `/vivier` ne répète plus
  le titre.
- **`vitest.config.ts`** : le commentaire de périmètre dit désormais le vrai critère — l'absence de
  runtime Nuxt, pas le dossier.
- **`arborescence-admin.md`** : arborescence en trois groupes, état API revérifié, `/referentiels`
  sorti de la section V2, et la règle « les libellés vivent dans le composable » écrite noir sur
  blanc.

## Vérification

Mesures prises dans Chromium sur la stack `:8480`, session admin réelle, **au DOM vivant** — le HTML
servi ne rend que le rideau de boot et ne prouve rien.

| Écran | Fil d'ariane | Avant |
|---|---|---|
| `/referentiels` | Espace admin › **Référentiels** | « Espace admin » seul |
| `/vivier` | Espace admin › **État de la base** | « Espace admin » seul |
| `/candidats/<uuid>` | Espace admin › **Le vivier** | « Espace admin » seul |
| `/candidats` | Espace admin › **Le vivier** | › Candidats |
| `/compte` | Espace admin › **Compte** | inchangé — l'écran hors barre est préservé |

- **Les deux repères ne portent plus le même nom** : `Navigation principale` pour la barre,
  `Fil d'Ariane` pour le fil ; en arabe `التنقل الرئيسي` et `مسار التنقل`.
- **Trois groupes** rendus en fr et en ar : Pilotage · Comptes (Entreprises, Le vivier,
  Utilisateurs) · Supervision (Jarvi, Référentiels, État de la base).
- **Tiroir mobile à 375 px** : le titre mesure `1 × 1 px` avec la classe `sr-only` et reste la cible
  de l'`aria-labelledby` du panneau. Un seul repère de navigation exposé tiroir ouvert — le dialogue
  modal masque le reste de la page.
- **RTL** : barre à droite, chevron du fil retourné, liseré de l'item actif côté début de ligne.
- **Tests** : `bunx vitest run spaces/admin/app/utils/navRoute.vitest.ts` → **7 tests verts**,
  0,7 s.
- **Non vérifié** : le rendu en anglais, dont seuls les libellés changent — même structure que le
  français, lui vérifié.

## Reste à faire (hors périmètre)

- **Le hamburger reste affiché sur desktop** : `lg:hidden` est battu en spécificité par le
  `display: grid` du `<style scoped>` de `.icon-btn` (mesuré `grid` à 1440 et 1024 px, sidebar
  `block`). Pré-existant, à prendre par une carte ADM dédiée.
- **`api/config/reference.php`** dérive à chaque démarrage de la stack du worktree : le conteneur
  API le régénère sans le bloc `dama_doctrine_test`. Hors de ce commit, et à traiter côté
  `espace/api` ou outillage.
- **Les libellés arabes attendent une relecture** humaine.

## Définition de terminé

- [x] Les sept entrées sont déclarées **une seule fois**, dans `useAdminNav()`.
- [x] Le fil d'ariane nomme la page courante sur les sept écrans, y compris `/referentiels` et
      `/vivier`, et affiche la section parente sur `/candidats/[id]`.
- [x] Le fil d'ariane ne s'annonce plus « Navigation principale » ; son chevron suit le sens de
      lecture en arabe.
- [x] « Vivier » ne désigne plus qu'un seul écran dans toute l'interface admin.
- [x] La sidebar montre trois groupes ; « Utilisateurs » n'est plus rangé avec Jarvi et les
      référentiels.
- [x] Le tiroir mobile n'affiche plus son titre en double avec la marque, et reste annoncé aux
      lecteurs d'écran.
- [x] fr, en et ar sont complets ; aucun libellé en dur.
- [x] Le test de résolution passe ; le commentaire de périmètre de `vitest.config.ts` dit la vérité.
- [x] `arborescence-admin.md` remise en phase avec les écrans réellement livrés.
- [x] Captures à l'appui : desktop et 375 px, thème clair et thème sombre.
- [ ] Conventions, lint, format, type-check des 4 espaces verts — **au passage PR**, en une passe
      pour toute la branche.
