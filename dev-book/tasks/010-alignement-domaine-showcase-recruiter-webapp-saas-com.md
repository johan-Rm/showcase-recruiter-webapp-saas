# TASK 010 — Alignement du domaine sur `showcase-recruiter-webapp-saas.example`

- **Créée le** : 2026-06-19.
- **Statut** : ✅ réalisée le 2026-06-19 (CORS `*.showcase-recruiter-webapp-saas.example` autorisé / origine étrangère refusée ;
  fixtures + tests sur `@demo.showcase-recruiter-webapp-saas.example` ; `make api-quality` vert).
- **Périmètre** : harmoniser **toutes** les références de domaine sur **`showcase-recruiter-webapp-saas.example`** (le
  `BASE_DOMAIN` racine est déjà passé à `showcase-recruiter-webapp-saas.example`). Aujourd'hui des références **`showcase-recruiter-webapp-saas.io`**
  subsistent — notamment la **regex CORS**, ce qui **bloquerait** les requêtes depuis le vrai
  domaine. Chantier transverse, à faire **avant** de scaffolder le webapp.
- **Type** : `chore` (pas de nouvelle fonctionnalité ; cohérence + déblocage CORS).

## Pourquoi (impact réel)

- **CORS** : `CORS_ALLOW_ORIGIN` autorise `showcase-recruiter-webapp-saas.io` → un front servi sur `*.showcase-recruiter-webapp-saas.example` serait
  **rejeté** (pré-flight KO). C'est le point bloquant fonctionnel.
- Cohérence : sous-domaines (`api.` / `candidat.` / `entreprise.` / `admin.`), docs, templates env,
  données de démo doivent parler du même domaine.

## Portée du remplacement `showcase-recruiter-webapp-saas.io` → `showcase-recruiter-webapp-saas.example`

À vérifier exhaustivement (`grep -rIl 'showcase-recruiter-webapp-saas\.io'`), au moins :

- **Config / CORS** : `api/.env.dev`(+`.example`), `api/.env.preprod.example`, `api/.env.prod.example`
  — `CORS_ALLOW_ORIGIN` et `DEFAULT_URI`. ⚠️ La regex CORS contient des points échappés
  (`cap\-nour\.com` → attention au `-` dans la classe `[a-z0-9-]` et au `.`).
- **Données de démo** : emails `@demo.showcase-recruiter-webapp-saas.io` → `@demo.showcase-recruiter-webapp-saas.example` dans
  `api/src/DataFixtures/AppFixtures.php` **et** les tests fonctionnels (`ApiTestCase`, `OrganizationTest`,
  `RevealTest`, `MeTest`, `CandidateTest`) — sinon le login de démo casse les tests.
- **Docs** : `docs/showcase-recruiter-webapp-saas/content/2.architecture/*` (sous-domaines, exemples d'origines), `dev-book/` si pertinent.
- **Mémoire projet / gouvernance** : vérifier `CLAUDE.md` / `AGENTS.md` (sous-domaines cités).

> Remplacement mécanique mais **relire la regex CORS** à la main (un mauvais échappement casse tout
> le pré-flight). Ne pas toucher `node_modules`, `vendor`, `.git`, `docs` (vendored).

## Étapes

1. `grep -rIl 'showcase-recruiter-webapp-saas\.io'` → liste exhaustive.
2. Remplacer par `showcase-recruiter-webapp-saas.example` (+ `@demo.showcase-recruiter-webapp-saas.example` pour les emails de démo, de façon cohérente
   fixtures ↔ tests).
3. Recharger les fixtures, relancer `make api-quality`.
4. Vérifier le **pré-flight CORS** : `OPTIONS` avec `Origin: https://entreprise.showcase-recruiter-webapp-saas.example` →
   en-têtes `Access-Control-Allow-Origin` présents ; et un `Origin` hors domaine → rejeté.

## Critères d'acceptation

- [ ] Plus aucune occurrence de `showcase-recruiter-webapp-saas.io` (hors dépendances vendored).
- [ ] CORS : origine `*.showcase-recruiter-webapp-saas.example` **autorisée**, origine étrangère **refusée**.
- [ ] Fixtures + tests cohérents (`@demo.showcase-recruiter-webapp-saas.example`) ; `make api-quality` vert.
