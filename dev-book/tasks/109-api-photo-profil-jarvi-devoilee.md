# TASK 109 — API : photo de profil Jarvi, ingérée par URL et dévoilée

- **Créée le** : 2026-07-21.
- **Statut** : 🟢 code livré — reste la ré-ingestion `--full` (run Jarvi) et l'affichage front.
- **Type** : API — ingestion Jarvi / réplica `candidate_jarvi` + exposition masquée.
- **Lié à** : `020` (ingestion), `006` (masquage), `086` (ré-identification), `108` (WhatsApp).
- **Réf** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
  - `dev-book/cadrage/entreprise/api/recherche_et_masquage.md`
  - `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md`

## Contexte

La photo est le champ le mieux rempli du vivier après l'identité : **86,2 % des profils** (1467 /
1701) en ont une chez Jarvi. Elle n'entrait pas en base — la requête GraphQL ne la demandait pas —
et le cadrage la déclarait « jamais transmise, dévoilement compris ».

Une carte sans visage se lit mal : le recruteur qui a **payé** un dévoilement obtient un nom, un
e-mail et un CV, mais pas le visage qui va avec. La photo rejoint donc les coordonnées : masquée
par défaut, libérée par le crédit.

## Objectif

Ingérer la photo **par référence** (URL, jamais le binaire) et la servir au seul profil dévoilé.

## Livré (code)

- [x] Requête GraphQL `JarviClient` étendue : `image { publicUrl }` (les 3 variantes).
- [x] `JarviProfile::$photoUrl` → `candidate_jarvi.photo_url` (colonne `text` nullable) via
  `ProfileIngestor`.
- [x] Exposition masquée dans `CandidateMapper` : `photoUrl` sur la carte et le détail, `null` tant
  que `revealed` est faux.
- [x] Tests : mapping GraphQL (`JarviClientMappingTest`), masquage / dévoilement (`CandidateTest`).
- [x] Cadrage et référence Jarvi réalignés (la photo n'est plus « jamais transmise »).
- [x] Exposition admin (non masquée) : `AdminCandidateOutput` et `AdminCandidateDetailOutput`.
- [x] Front admin : `UiAvatar` (photo dimensionnée, repli initiales) sur la table du vivier et
  l'en-tête de la fiche candidat ; helper pur `shared/utils/photo.ts`.

## Reste à faire

- [ ] **Ré-ingestion `--full`** pour peupler l'existant (run Jarvi, clé + rate limit 500/j).
- [ ] **Front entreprise** : vignette de la Table, en-tête de l'inspecteur et de la visionneuse —
  quand ces surfaces existeront. `UiAvatar` est déjà partagé (`base/`), rien à réécrire.

## Contrat pour le front

- L'URL est servie **telle quelle** ; c'est le front qui dimensionne, via les paramètres du storage
  Jarvi : `?w=96&h=96&q=80` ramène une vignette de **3 Ko** là où l'original pèse ~111 Ko.
- `photoUrl` est `null` dans deux cas indiscernables — profil masqué, ou profil sans photo (14 %).
  Le repli est le même : initiales sur aplat, jamais un visage générique.
- Réponse mesurée sur le storage Jarvi : `200`, `image/jpeg`, `cache-control: max-age=2630000`,
  servi par Cloudflare.

## Invariants

- **Aucun binaire copié** : ni téléchargement, ni proxy, ni miniature stockée. La réplica ne porte
  qu'une chaîne.
- Réplica `candidate_jarvi` = lecture seule, opaque, Jarvi maître.
- Le masquage reste **serveur** : l'URL ne doit jamais figurer dans une réponse non dévoilée, même
  « non affichée » par le front.

## Points de vigilance

- **L'URL est publique et non authentifiée.** Elle vaut identité au même titre qu'un e-mail :
  quiconque la détient voit le visage, sans compte ni jeton. D'où le masquage strict — et le fait
  qu'un export ou un log qui la recopie la diffuse durablement.
- **`imageId` n'est pas l'identifiant du fichier de l'URL** (deux UUID distincts) : sans
  `publicUrl`, l'URL est irreconstructible. Ne pas « optimiser » en ne stockant que `imageId`.
- **Dépendance externe à l'affichage** : chaque vignette tape le CDN de Jarvi. Si Jarvi purge ou
  fait tourner le fichier, l'image casse côté recruteur ; la ré-ingestion rafraîchit l'URL. C'est
  le prix du « pas de copie », acceptable tant que la photo est un confort d'affichage.

## Docs à tenir à jour

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
- `dev-book/cadrage/entreprise/api/recherche_et_masquage.md`
- `dev-book/cadrage/entreprise/api/credits_et_devoilement.md`
- `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`
- `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md`
