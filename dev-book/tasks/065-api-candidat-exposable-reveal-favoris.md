# TASK 065 — API : centraliser la règle "candidat exposable" pour reveal, favoris et actions entreprise

- **Créée le** : 2026-07-11.
- **Statut** : ⬜ à faire.
- **Type** : correction sécurité / cohérence métier.
- **Dépend de** : tâche `064` (consentement), tâches `005` (dévoilement), `008` (favoris),
  `029` (assignation).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md` § Atomicité
  - `dev-book/cadrage/entreprise/api/credits_et_devoilement.md`

## Contexte

`RevealProcessor` et `FavoriteProcessor` résolvent un candidat directement par UUID public avec
`CandidateRepository::findOneByUuid()`. Ils ne réappliquent pas la règle d'exposition du vivier.

Risque : un recruteur peut dévoiler ou mettre en favori un profil qui ne devrait pas être
actionnable : profil inactif, masqué, supprimé logiquement ou sans consentement explicite. L'assignation
hérite ensuite du problème si le reveal a été autorisé à tort.

## Objectif

Toutes les actions entreprise portant sur un candidat doivent passer par une règle unique :
le candidat doit être exposable, sauf cas explicitement historique déjà payé.

## Périmètre

- Ajouter une méthode dédiée côté repository ou service applicatif :
  - exemple : `CandidateRepository::findOneExposableByUuid(string $uuid): ?Candidate` ;
  - conditions alignées avec la tâche `064`.
- Remplacer les résolutions directes dans :
  - `RevealProcessor` ;
  - `FavoriteProcessor` ;
  - routes de notes / assignations si elles créent de nouvelles interactions sur un candidat non déjà dévoilé.
- Clarifier le cas historique :
  - un profil déjà dévoilé doit rester lisible dans "déjà dévoilés" même s'il sort ensuite du périmètre ;
  - un nouveau reveal/favori ne doit pas être possible si le profil n'est plus exposable.
- Tests fonctionnels :
  - impossible de révéler un candidat `HIDDEN` ;
  - impossible de révéler un candidat inactif ;
  - impossible de révéler un candidat sans consentement ;
  - impossible d'ajouter en favori ces mêmes cas ;
  - un profil déjà dévoilé reste présent dans l'historique.

## Invariants

- Le reveal reste atomique et idempotent.
- L'historique payé est conservé.
- Aucune logique d'exposition côté client : le serveur tranche.

## Critères d'acceptation

- [ ] Une méthode ou un service unique porte la règle "candidat exposable".
- [ ] Reveal et favoris l'utilisent.
- [ ] Les tests couvrent les candidats masqués, inactifs et sans consentement.
- [ ] Les tests de non-régression reveal idempotent / solde insuffisant restent verts.
- [ ] `make api-quality` vert.

## Hors périmètre

- Snapshot figé complet au dévoilement si non encore implémenté.
- Refonte des DTO candidats.
