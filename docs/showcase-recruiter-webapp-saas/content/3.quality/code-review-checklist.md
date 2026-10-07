# showcase-recruiter-webapp-saas — Checklist de review & Definition of Done

> Checklist centrée sur les risques spécifiques showcase-recruiter-webapp-saas (crédits, ACL multi-comptes, sync
> Jarvi). À passer avant tout merge vers `develop`.

## Checklist de review

### Périmètre & sécurité

- [ ] Le scope du diff correspond à la tâche (`dev-book/tasks/`), pas de refactor hors périmètre.
- [ ] Aucun secret committé ; `.env` non versionné (webapp et api).
- [ ] **Clé privée Jarvi** utilisée uniquement côté `api/`, jamais exposée au webapp.

### Métier sensible (cœur monétisable)

- [ ] **Crédits** : toute consommation est tracée (`CreditTransaction`), idempotente, et ne peut
      pas rendre le solde négatif. Tests à l'appui.
- [ ] **Déblocage profil** : l'accès reste conservé après débit ; pas de double débit.
- [ ] **ACL multi-comptes** : un recruteur n'accède qu'aux données de son entreprise
      (isolation inter-entreprises vérifiée par test).

### Données & Jarvi

- [ ] Les migrations Doctrine préservent les données existantes si nécessaire.
- [ ] **Sync Jarvi** : upsert idempotent (`externalId`), résiliente (retry, statut de sync), ne
      bloque pas le parcours utilisateur si Jarvi est indisponible.
- [ ] Champs traduisibles (i18n FR/AR/EN) gérés dans le modèle de données, pas en dur.

### Contrats & qualité

- [ ] Les payloads API restent compatibles, ou le breaking change est documenté.
- [ ] Les tests couvrent les comportements critiques touchés (cf. TDD ciblé).
- [ ] Frontières d'architecture respectées (Deptrac côté api ; `services/` webapp sans import
      Vue/Nuxt/Pinia).
- [ ] La documentation propriétaire est mise à jour, sans duplication.

## Definition of Done

Une tâche est « done » quand :

1. Le comportement attendu est implémenté et couvert par des tests au niveau de risque adéquat
   (test-first sur le cœur sensible).
2. Les **gates de pré-merge passent** : phpstan + deptrac + cs + phpunit functional (api) ;
   lint + format + type-check + vitest + conventions (webapp).
3. Aucune nouvelle erreur hors baseline (PHPStan / Deptrac).
4. La doc impactée (`docs/`, `README.md` de dossier, tâche `dev-book/`) est à jour.
5. La revue (cette checklist) est passée et la PR ciblée sur `develop`.
