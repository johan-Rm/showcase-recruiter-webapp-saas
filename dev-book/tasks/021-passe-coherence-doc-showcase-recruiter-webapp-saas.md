# TASK 021 — Passe de cohérence de la doc showcase-recruiter-webapp-saas

- **Créée le** : 2026-06-24.
- **Statut** : ⏳ **à faire**.
- **Périmètre** : audit de cohérence éditoriale et technique de la documentation du site `showcase-recruiter-webapp-saas`
  (`docs/showcase-recruiter-webapp-saas/content/`), après les déménagements récents (réorg `dev-book/cadrage/`, sortie de
  `docs/` → `docs/showcase-recruiter-webapp-saas/content/`, renommage `docs-sites/` → `docs/`) et le pivot Jarvi.
- **Hors périmètre** : refonte du contenu métier, ajout de nouveaux docs, doc API générée
  (`docs/api/content/`, voir tâche 007) et dev-book.

## Contexte

La structure a beaucoup bougé. La casse (`architecture.md`) et l'asymétrie d'index
(`2.architecture/index.md` + index racine allégé) ont **déjà été corrigées** — ne pas les refaire.
Reste à passer le contenu au peigne fin pour traquer les incohérences résiduelles.

## À vérifier

1. **Liens morts / chemins périmés** : balayer tous les liens internes et relatifs sortants. Cibler
   les vestiges des anciens emplacements (`docs/2.architecture/…` sans préfixe, noms cadrage en
   MAJUSCULES type `INITIAL_BRIEF.md`, `ROADMAP.md`, chemins `docs-sites/…`).
2. **Cohérence avec l'architecture actuelle** : confronter chaque doc au **pivot Jarvi** (Jarvi maître
   du profil pro, showcase-recruiter-webapp-saas maître des différenciants, réplica locale = index, ingestion `020`,
   recherche `014`). Certains passages peuvent encore décrire l'ancien modèle (« proxy recherche
   Jarvi », « index local abandonné »).
3. **Quasi-doublon §5** : la table « Sous-domaines » d'[architecture.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md)
   §5 fait redondance avec le résumé du §2. Décider : amincir l'une des deux (router vers l'autre).
4. **Frontières de responsabilité entre docs** : appliquer le **principe de non-duplication** —
   `architecture.md` doit *résumer et router*, pas recopier `data-model.md` / `jarvi-integration.md` /
   `api-conventions.md`. Repérer les blocs recopiés.
5. **Frontmatter & titres** : présence et cohérence des `title` / descriptions (le rendu Docus s'en
   sert), niveaux de titres homogènes.
6. **Dates & statuts** : harmoniser les « État (date) » et marqueurs ✅/🚧 entre docs et avec le
   `dev-book/cadrage/todo.md`.

## Définition de terminé

- Aucun lien interne/relatif cassé dans `docs/showcase-recruiter-webapp-saas/content/`.
- Aucun chemin ou nom de fichier périmé.
- Pas de bloc de contenu dupliqué entre `architecture.md` et les docs détaillés (routage à la place).
- Contenu aligné sur la frontière Jarvi en vigueur.
