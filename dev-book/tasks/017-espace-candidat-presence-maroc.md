# TASK 017 — Espace Candidat : présence au Maroc (`PresencePeriod`)

- **Créée le** : 2026-06-23.
- **Statut** : ✅ implémenté (2026-06-23) — CRUD `PresencePeriod` self-service + test. _Push Jarvi
  abandonné (décision 2026-07-01 : aucune sync montante showcase-recruiter-webapp-saas→Jarvi ; donnée maître locale)._
- **Type** : fonctionnalité P4 — **la donnée différenciante du produit**.
- **Dépend de** : tâche `013` (projection). Suit `015`/`016`.
- **Réf cadrage** : [`ESPACE_CANDIDAT.md`](../cadrage/ESPACE_CANDIDAT.md) §2, [`PARCOURS_UTILISATEUR_CANDIDAT.md`](../cadrage/PARCOURS_UTILISATEUR_CANDIDAT.md) §6, data-model §171.

## Contexte

« **Quand le candidat sera physiquement présent au Maroc** » est **la** donnée différenciante de
showcase-recruiter-webapp-saas (entretiens physiques pendant un séjour déjà prévu). Modélisée par `PresencePeriod`
(`startDate`, `endDate`, `city`) — **dispo entretien, pas un voyage**. Différenciant **maître showcase-recruiter-webapp-saas**,
stocké localement et consommé par la recherche showcase-recruiter-webapp-saas.

## Périmètre

- **CRUD `PresencePeriod`** (1-N sur la projection `Candidate`) : déclarer / modifier / supprimer une
  période de présence (`startDate`, `endDate`, `city`). Self-only (voter task 015).
- **Validation** : `endDate ≥ startDate` ; ville cohérente ; pas de période passée incohérente
  (règles à confirmer). Intégrité en base (CHECK), pas seulement en code.
- **Index local** : exposer les périodes à la recherche recruteur showcase-recruiter-webapp-saas (« présent à Casa en
  septembre », cf. task 014), sans push Jarvi.
- **Exposition recruteur** : la présence est un attribut **affiché masqué/visible** selon dévoilement
  (cohérent avec le masquage task 006/014) — câblage côté carte candidat.

## Hors périmètre

- Recherche par présence côté recruteur → task 014 (consomme la donnée locale).
- Notifications « entreprise intéressée pendant ta présence » → task 019 / V2.

## Invariants

- **Self-only** ; écriture via API Symfony.
- **Intégrité garantie en base** (CHECK `endDate ≥ startDate`).
- **Aucune sync montante vers Jarvi** pour cette donnée maître showcase-recruiter-webapp-saas.

## Critères d'acceptation

- [ ] CRUD `PresencePeriod` self-only ; contrainte `endDate ≥ startDate` en base.
- [ ] Périodes consommables par la recherche locale (vérifié avec 014).
- [ ] Affichage respectant le masquage (visible après dévoilement).
- [ ] `make api-quality` vert ; tests (validation dates, masquage).

## Docs à tenir à jour (même scope)

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-integrity.md` : contrainte `PresencePeriod`.
- [`ESPACE_CANDIDAT.md`](../cadrage/ESPACE_CANDIDAT.md) : statut fonctionnalité.
