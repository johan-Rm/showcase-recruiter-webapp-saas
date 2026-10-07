# TASK 062 — Filtres conditionnés à la couverture des données (jamais 0 résultat garanti)

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Phase 2 — Espace entreprise / recherche vivier (API + front).
- **Dépend de** : `035` (endpoint recherche), `043` (branchement front), complémentaire de `061`.
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` (constat n°1)
  - `docs/showcase-recruiter-webapp-saas/content/2.architecture/filtres-vivier-tableau-partage.md` (§ À retenir, reco n°3)
  - `dev-book/cadrage/entreprise/ui/console_contextuelle.md` (widget Recherche, compteurs)

## Contexte

Sur le vivier importé, les critères « projet Maroc » ⭐ (horizon de retour, séjours prévus,
disponibilité, mobilité, pied-à-terre, secteur/fonction) sont à **~0 % de couverture** : un
recruteur qui coche un de ces filtres obtient **0 résultat garanti** → frustration et perte de
confiance dans le produit. Ces données se rempliront progressivement (magic link, onboarding
candidat — tasks 033/016) : l'UI doit s'adapter à la couverture réelle, pas au cadrage cible.

## Objectif

Ne jamais proposer un filtre qui ne peut rien renvoyer : **masquer (ou griser avec explication)
tout filtre dont la donnée est vide dans le vivier**, et l'activer automatiquement quand la
donnée apparaît.

## Périmètre

- **API — compteurs de couverture** : exposer, par critère filtrable, le nombre de candidats
  ayant la donnée renseignée (endpoint léger dédié ou enrichissement de la réponse de
  recherche / du endpoint `config`). Portée : critères différenciants + présence Maroc ;
  cache court (la couverture évolue lentement).
- **Front — règle d'affichage** (au branchement 043) :
  - couverture 0 → filtre **masqué** ;
  - couverture faible (seuil à définir, ex. < 5 %) → filtre visible mais **annoté**
    (ex. « 12 candidats renseignés ») ;
  - couverture normale → filtre standard.
- Cohérence avec les **compteurs cliquables** de la console (disponibles / dévoilés / nouveaux /
  bientôt au Maroc) : un compteur à 0 ne doit pas être un raccourci mort.

## Décisions ouvertes

- Masquer vs griser+expliquer (le griser « vend » la fonctionnalité à venir — arbitrage produit).
- Seuil « couverture faible » et wording de l'annotation.
- Où exposer les compteurs (endpoint dédié vs `config` vs facettes de la recherche).

## Invariants

- Les compteurs de couverture ne fuient **aucune donnée individuelle** (agrégats seulement,
  calculés sur les profils visibles/actifs).
- Aucun filtrage côté client sur dump complet (invariant 043).

## Critères d'acceptation

- [ ] L'API expose la couverture par critère (testée, agrégats seulement).
- [ ] Un filtre à couverture 0 n'apparaît pas dans l'UI ; il apparaît dès que la donnée existe.
- [ ] Les compteurs console à 0 ne proposent pas de raccourci mort.
- [ ] `make api-quality` + `webapp-quality` verts.

## Hors périmètre

- Construction des filtres eux-mêmes → tasks `035`/`061`.
- Campagne d'invitation candidats (remplissage des données) → task `033` (phases suivantes).
