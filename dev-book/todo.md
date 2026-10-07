# showcase-recruiter-webapp-saas — TODO / Backlog

> Liste centrale de ce qu'il reste à faire (décisions ouvertes, cadrage à compléter, socle
> technique, V2). Mise à jour au fil de l'avancement. Les sujets pointent vers leur doc
> propriétaire dans `docs/showcase-recruiter-webapp-saas/content/`.
>
> **État (2026-06-23).** Socle API **largement livré** : scaffolding `api/`, modèle de données,
> auth (JWT + refresh + voters), batterie de tests fonctionnels, endpoint `config`/`pricing`, CI
> `api-quality` + `commit-check`. Espace Entreprise (P2) livré côté API (tâches 004→009).
> **Chantier courant : pivot Jarvi (tâches `013` + `020` + `014`)** — **`013` ✅ implémenté (2026-06-23)**
> (Candidate = projection ; révise 002/005/006/008, complétude à vérifier) ; **`020` 🚧 amorcé** (scaffold
> du pipeline d'**ingestion du périmètre** : Jarvi → réplica locale lecture seule ; polling 2×/j incrémental
> `updatedAt` + réconciliation ; sortie de périmètre = **désactivation**) — pull/incrémental/client Jarvi
> **à finir** ; **`014` 🚧 amorcé** (recherche **sur la réplica locale Postgres** : pour l'instant exclusion
> hors-périmètre, FTS+filtres à étoffer). **Décision 2026-06-23 : ingestion complète du périmètre** →
> l'« index local » abandonné le 2026-06-22 est **réhabilité** (la réplica EST l'index ; quota 500 req/j
> dégagé). MCP / recherche IA = V2.
> **P4 (Candidat) : socle `015` ✅ + présence Maroc `017` ✅ implémentés (2026-06-23)** ; restent `016`
> (édition profil + sync), `018` (visibilité/RGPD), `019` (activité/notifs). **P3 (Admin) reste à cadrer.**
> Gaps socle restants : **finir ingestion + client Jarvi (`020`)**, **scaffolding `webapp/`** (vide),
> workflows de déploiement, `webapp-quality`, lefthook, release-please. ⚠️ commits feat du 23/06 **non poussés**.

## 🔵 Décisions ouvertes (à trancher)

- [x] **Prestataire de paiement** ✅ (2026-07-02) : **Stripe** pour les paiements **Europe**, **CMI**
      pour le **Maroc**. Reste à détailler plans/packs/webhooks/facturation → cf. § Cadrage à compléter (billing).
- [x] **Moteur d'index local** : ~~abandonné (2026-06-22)~~ → **réhabilité (2026-06-23)** par
      l'ingestion complète : la **réplica locale EST l'index**. **Tranché (2026-07-06)** par benchmark
      (dump réel ×20 ≈ 32,5 k profils) : **Postgres FTS (`tsvector` GIN + `pg_trgm`) en V1**, recherche
      **serveur** (jamais le dump côté client → poids/RAM/PWA + masquage) ; **Meilisearch/Typesense =
      escape hatch** si besoin fuzzy/instantané. UI (barre/filtres/tri) côté front Nuxt, résultats
      **paginés servis par l'API**. → cadrage `entreprise/api/recherche_et_masquage.md`
      §Moteur & passage à l'échelle ; tâches `014` / `035` / `043`,
      `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`.
- [ ] **Pivot modèle candidat** (Jarvi maître profil / showcase-recruiter-webapp-saas maître différenciants+interactions,
      `Candidate` = projection sur `jarviProfileId` + **réplica pro lecture seule + snapshot + drapeau
      d'activité**). → tâches `013` (modèle) / `020` (ingestion) / `014` (recherche). Révise
      002 / 005 / 006 / 008. **Phase dev : base recréée à vide, pas de migration.**
- [ ] **Ingestion du périmètre Jarvi** (décidé 2026-06-23) : pull initial + incrémental `updatedAt`,
      polling 2×/j, refresh on-demand au dévoilement, réconciliation quotidienne, sortie de périmètre
      = **désactivation** (pas de suppression), liste blanche de champs. → tâche `020`.
- [ ] **Critères d'un profil candidat « complet »** (déblocage des fonctions après onboarding).
      → déplacé dans le backlog candidat [candidat/todo.md](candidat/todo.md).
- [ ] **Paiement candidat** (options premium) : candidat = Stripe customer, ou paiement one-shot ?
      → `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` (CandidateOption)
- [ ] **Coût d'un dévoilé** : reste fixe (1 crédit) ou variable selon profil/option à terme ?
- [ ] 🔭 **Diffusion payante ?** (**décision reportée en V2**) : l'envoi d'une offre consomme-t-il des
      crédits / une option, ou est-il inclus ? Conditionne l'UX de confirmation (comme le dévoilement
      groupé). → V2 « Diffusion d'offres ».
- [ ] 🔭 **Statique vs dynamique (audience de diffusion)** (**décision reportée en V2**) : confirmer le
      **snapshot à l'envoi** (segment figé) vs ré-évaluation continue. → V2 « Diffusion d'offres ».

## 🟠 Cadrage à compléter

- [x] **Questions ouvertes à Jarvi** ✅ clôturées (2026-07-02) : quota & droit de stockage RGPD
      tranchés (ingestion actée 2026-06-23), **CV download résolu** (URL storage Jarvi `…/v1/files/<fileId>`),
      custom fields → **filtrage côté front** (sans objet). Doc `questions-jarvi.md` supprimé ; le volet
      RGPD prod reste suivi ci-dessous. → `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`.
- [ ] **Détail Stripe / modèle commercial** : plans d'abonnement, options ponctuelles, packs de
      crédits, webhooks, facturation entreprise marocaine, et arbitrage entre offre commerciale,
      commande, facture et fournisseur. → `dev-book/cadrage/draft/modele_commercial_futur.md`, puis
      nouveau doc `docs/showcase-recruiter-webapp-saas/content/2.architecture/billing.md` (à créer).
- [ ] **Dashboard admin** : cadrer les vues (connexions, soldes crédits, droits, historique des
      profils visités, supervision). Surtout des **vues** sur les entités existantes.
- [ ] **RGPD** : consentement, droit à l'oubli, durées de rétention, données candidats européens.
      → impacte le hard delete (procédure dédiée). **⚠️ Renforcé (2026-06-23)** : l'ingestion de masse
      (`020`) stocke d'emblée les données perso de **tout le périmètre** → base légale + rétention +
      opposition deviennent un **prérequis avant mise en prod de l'ingestion** (pas la V1 dev).
- [ ] **Système de notification multi-canal** : une brique unique à **3 canaux** (in-app / email /
      push PWA) avec **préférences par utilisateur**, servant **tous** les usages (alertes recruteur,
      dévoilement/crédits/équipe, notifications candidat, **et diffusion d'offres** comme simple
      émetteur). Inclut les **emails transactionnels** + config SMTP. Principe : ne pas construire
      deux tuyaux — la diffusion passe par ce système (cible = candidats au lieu de recruteurs).
      → canaux déjà esquissés dans `dev-book/cadrage/draft/notes_en_vrac.md`.
- [ ] **Dévoilement groupé (sélection multiple)** : étendre le cœur sensible (task 005) à un lot de
      candidats. Décisions retenues à formaliser : (a) **coût recalculé serveur** (les déjà-dévoilés
      sont **gratuits**, idempotence) ; (b) **tout-ou-rien** si solde insuffisant → 402, aucun débit ;
      (c) **atomicité du lot** (une transaction, verrou pessimiste sur le wallet partagé) ;
      (d) endpoint **batch** (`{candidateIds:[…]}`), jamais N appels client ; (e) UX de confirmation
      avec décomposition du coût (recalcul au clic car **wallet partagé** entreprise).
      → `dev-book/tasks/005-…md`, `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md`.
- [ ] **Statut candidat (entreprise)** : action recruteur = poser un **statut** sur un candidat.
      Vocabulaire à trancher : **liste globale gérée par showcase-recruiter-webapp-saas** (commune à toutes les
      entreprises, **pas** de création libre par entreprise) — le référentiel `CategoryCode` ayant été
      supprimé, le support reste à choisir (enum si la liste est fermée et stable, table dédiée si elle
      doit s'administrer). Entité d'affectation **`OrganizationStatus`** `(organization, candidate,
      status)`, **partagée** entreprise, **exclusive** (unicité `(organization, candidate)` = 1 statut
      actif), posée **sans dévoiler**, en **action groupée** sur sélection. **Local, non poussé
      Jarvi**. Liste **plate ordonnée** (garde-fou « pas d'ATS complet »).
      → `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md`.
- [ ] **Recherches enregistrées (recruteur)** : entité métier `SavedSearch` pour mémoriser une
      recherche et la rejouer. Cibler en priorité les **recherches « classiques »** (mots-clés /
      filtres servis par l'index local) car **déterministes et reproductibles** ; les recherches IA
      (MCP `advancedSearch`) sont plus délicates à rejouer à l'identique (à cadrer séparément).
      Base des futures **alertes** (notif in-app / email / push PWA).
      → `dev-book/cadrage/draft/notes_en_vrac.md` (§ Recherches enregistrées et alertes recruteurs)

## 🎨 Design / identité visuelle

> Direction artistique **actée** (palette terre & nature, Cormorant Garamond / DM Sans, ambiance
> épuré & éditorial). Recueil d'intentions : `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md`.

- [ ] **Design system technique** : établir tokens, échelles, variables CSS et config Tailwind à
      partir de `brand-guidelines.md` (confié à l'expert design). → `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md`
- [ ] **Palette clair + sombre** : décliner terre & nature en mode clair et dark mode (V1) via des
      tokens sémantiques (attention aux chauds qui virent au boueux). → `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md`
- [ ] **Accessibilité (WCAG AA)** : valider le contraste de tous les couples texte/fond + éléments
      interactifs (boutons argile, liens, focus). → `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md`
- [ ] **Polices** : intégrer Cormorant Garamond (titres) / DM Sans (corps) via `@nuxt/fonts`
      (SSR-safe, `font-display: swap`) au scaffold webapp.
- [ ] **Police arabe** : Cormorant Garamond & DM Sans **ne couvrent pas l'arabe** → choisir une
      police arabe d'accompagnement (titres + corps) et la brancher sur la locale `ar`.
      → `docs/showcase-recruiter-webapp-saas/content/2.architecture/i18n.md`
- [ ] **Support RTL** : adapter le design system et les composants au sens droite-à-gauche pour
      `ar` (mise en miroir des layouts, paddings logiques, icônes directionnelles).
      → `docs/showcase-recruiter-webapp-saas/content/2.architecture/i18n.md`
- [ ] **Composants de base + preview** : boutons, cartes profils « stars », formulaires, états —
      page de démonstration du style guide.
- [ ] **Relancer le client** : logo définitif (+ charte), moodboard / références, styles ou couleurs
      à éviter. → `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md`
- [ ] **Logo définitif** : remplacer le placeholder texte « showcase-recruiter-webapp-saas » dès réception.

## 🟢 Socle technique (étape 2 — scaffolding)

> **Statut (2026-06-23)** : la moitié **API** du socle est livrée ; la moitié **webapp** n'a pas
> démarré (`webapp/` vide). Gap structurant restant côté API : **le client Jarvi d'import descendant**
> (GraphQL prioritaire + curseur `updatedAt`) — prérequis de l'exploitation du vivier en phase 2.

- [x] **Socle infra Docker/Makefile** : compose `{dev,preprod,prod}`, Makefiles, Caddy, env.
      → `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md`
- [x] **Dockerfile `api/` (FrankenPHP)** livré · [ ] **Dockerfile `webapp/` (Nuxt)** au scaffold.
- [x] **Scaffolding `api/`** (Symfony + API Platform + FrankenPHP) livré (`dev-book/tasks/001`).
      · [ ] **Scaffolding `webapp/`** (Nuxt) — **pas commencé**.
- [ ] **i18n** : installer `@nuxtjs/i18n` (FR/AR/EN, fallback FR, **AR=RTL**) dès l'init + **dark mode**.
      → `docs/showcase-recruiter-webapp-saas/content/2.architecture/i18n.md`
- [ ] **Contrats de données** : [x] endpoint `config`/`pricing` (`ConfigProvider`) livré · [ ] génération
      des types TS webapp depuis l'OpenAPI + gate CI anti-dérive (au scaffold webapp).
      → `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md`
- [x] **Modèle de données → code** : entités Doctrine / ressources API Platform dérivées (IDs hybrides,
      soft delete, contraintes BDD). ⚠️ **`Candidate` à réaligner par `013`** (projection).
      → `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md` + `data-integrity.md`
- [ ] **Auth** : [x] API JWT (LexikJWT + refresh) + voters livrés · [ ] webapp **BFF `nuxt-auth-utils`**
      (cookie httpOnly sur `.showcase-recruiter-webapp-saas.example`, SSO inter-sous-domaines) — au scaffold webapp.
- [ ] **Sous-domaines** : Nuxt mono-app routée par host (apex public, candidat./entreprise./admin.) ;
      CORS API ; TLS wildcard `*.showcase-recruiter-webapp-saas.example`. → `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` §5
- [ ] **Intégration Jarvi** ⚠️ **GAP STRUCTURANT** (clé privée côté `api/`) :
      **ingestion descendante** → tâches `020`/`034` : remplit la **réplica locale** depuis Jarvi
      (GraphQL prioritaire, pagination 500, curseur `updatedAt`) pour alimenter le vivier exploité
      en phase 2 Espace entreprise. **Aucune sync montante** des données maîtres showcase-recruiter-webapp-saas vers Jarvi ;
      seul un `POST /profiles` ponctuel reste autorisé si un candidat s'inscrit sans profil Jarvi
      existant. **MCP** `advancedSearch` = **V2**. *(showcase-recruiter-webapp-saas **consomme** Jarvi ; il n'expose pas de
      GraphQL — contrats REST/OpenAPI.)* → `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- [ ] **CI GitHub Actions** : [x] `api-quality.yml` + `commit-check.yml` livrés (tâche 012) ·
      [ ] `webapp-quality.yml` (au scaffold webapp). → `docs/showcase-recruiter-webapp-saas/content/3.quality/testing-and-ci.md`
- [ ] **lefthook** (pre-commit) + [x] **commitlint** (`.commitlintrc.json`) livré.
- [ ] **CD** : [x] cibles `Makefile` (`preprod-deploy` / `prod-deploy`) livrées · [ ] workflows
      `deploy-preprod.yml` (auto depuis `develop`) + `deploy-prod.yml` (sur tag + approbation).
- [ ] **Checks CI transverses (webapp ET api)** : `commit-check`, `whitespace` (git diff --check),
      `secret-scan` (gitleaks, non bloquant) [repo-global] ; `no-debug` (webapp: console.log/warn ;
      api: dump/dd/var_dump) + `dead-code` (webapp: knip/ts-prune ; api: unused) [par contexte].
      Différés : SAST/CodeQL, lint rapide, diagnostic pipeline. → `docs/showcase-recruiter-webapp-saas/content/3.quality/testing-and-ci.md`
- [ ] **Versioning / releases** : `release-please` (versioning unifié lockstep, tag `vX.Y.Z`),
      `CHANGELOG.md` racine, endpoint API exposant la version. → `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md`
- [ ] **Garde-fous déploiement** : branch protection + required checks ; deploy sur tag `v*` only ;
      environnement `production` GitHub avec approbation manuelle ; rollback auto sur échec
      healthcheck ; vérif version post-deploy. → `docs/showcase-recruiter-webapp-saas/content/3.quality/testing-and-ci.md`
- [x] **Tests API** : `ApiTestCase` + batterie fonctionnelle livrés (reveal, me, invitations,
      organization, collaboration, candidate, référentiels…). [ ] JSON Schema par ressource +
      query-count anti-N+1 à compléter. → `docs/showcase-recruiter-webapp-saas/content/3.quality/api-testing.md`
- [ ] **Load-test k6** : budgets de latence p95 sur endpoints clés, en gate séparé.
- [ ] **README de dossier** (`webapp/…`, `api/…`) — contrat à 2 chapitres.
- [ ] **Moteur de validation des conventions** (`scripts/ci/`) : règles YAML + scripts `verify-*`.
      → `scripts/ci/README.md`

## 🟣 Runbooks à écrire

- [ ] **Sauvegarde / restauration PostgreSQL** (dumps planifiés, rétention, restauration testée).
- [ ] **Hard delete RGPD** (droit à l'oubli — procédure contrôlée).
- [ ] **Spec du moteur de conventions** (tâche `dev-book/tasks/`, au scaffolding).

## ⚪ V2 / différé (structure parfois préparée)

- [ ] **Recherche IA (langage naturel)** : MCP `advancedSearch` (client MCP serveur côté `api/`).
      **Reportée en V2** — la V1 ne livre que la **recherche classique** (sur la réplica locale Postgres).
      → tâche `014`, `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`.
- [ ] **Alertes & recherches enregistrées** : moteur de matching (structure `SavedSearch` prête).
- [ ] **Diffusion d'offres / missions** (schema.org `JobPosting`, diffusion ciblée Europe).
      **Sélection de l'audience** = réutiliser la recherche existante, deux modes :
      (1) **par filtres → segment dynamique** (recommandé, recalculé à l'envoi, = recherche
      enregistrée comme cible) ; (2) **bulk → liste statique** (même multi-sélection que le
      dévoilement). + « groupe privé » = segment prédéfini (communauté showcase-recruiter-webapp-saas).
      **Contraintes** : (a) **pas besoin de dévoiler** — showcase-recruiter-webapp-saas route l'envoi vers l'adresse réelle
      que l'entreprise **ne voit pas** (modèle « coordonnées payantes » préservé, on peut toucher des
      candidats masqués) ; (b) **filtre opt-in / consentement** toujours appliqué côté serveur (RGPD,
      cf. visibilité candidat) ; (c) **segment figé à l'instant de l'envoi** (snapshot) + aperçu du
      volume avant confirmation. Émission via le **système de notification multi-canal**.
- [ ] **Candidature assistée**.
- [ ] **Assignation / suivi** (pipeline candidat→recruteur, collaboration avancée).
- [ ] **PWA** (install, offline, **push notifications**).
- [ ] **Vivier Jarvi élargi** (profils non inscrits showcase-recruiter-webapp-saas).
- [ ] **Social login** Google / LinkedIn (onboarding 2 clics).
- [ ] **Auto-traduction** : activer la clé API de traduction (mode stub en V1).
- [ ] **Multi-appartenance recruteur** (un recruteur dans plusieurs entreprises).

## 📌 Jalons (rappel)

P1 Socle (10 juil.) → P2 Espace Entreprise (10 août) → P3 Dashboard Admin (20 août) →
P4 Espace Candidat (31 août) → P5 Public Website (7 sept.) → P6 Mise en prod (15 sept.).
→ `dev-book/roadmap.md`
