# TASK 075 — API : référentiel de postes (autocomplétion) et agrégats de facettes

- **Créée le** : 2026-07-12.
- **Statut** : ⬜ à faire.
- **Type** : moteur de recherche.
- **Dépend de** : tâche `014` / `035` (recherche FTS réplica), `061` (filtres FTS multichamps).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` §1 et § Moteur
  - `dev-book/cadrage/entreprise/ui/console_contextuelle.md` §2.3

## Contexte

Le cockpit **n'a plus de champ de recherche libre générique**. Le point d'entrée est la combobox
**« Poste recherché »**, dont l'input **fait office de recherche libre sur les intitulés de poste**
(multi-sélection, autocomplétion).

Par ailleurs, chaque option de filtre affiche **le nombre de profils qu'elle donnerait** — un
**comptage facetté** que l'API ne sait pas produire aujourd'hui : elle ne renvoie qu'une liste.

## Objectif

Alimenter le point d'entrée de la recherche (postes) et les **compteurs par option** (facettes).

## Périmètre

- **Référentiel de postes** :
  - endpoint d'**autocomplétion** sur les intitulés (FTS / `pg_trgm`, tolérance aux fautes) ;
  - chaque suggestion porte **son nombre de candidats** dans le périmètre courant ;
  - source : intitulés du vivier (dérivés de la réplica) — **stratégie à trancher** : liste dérivée à
    la volée, ou table de référence normalisée alimentée à l'ingestion ?
- **Agrégats de facettes** : la réponse de recherche renvoie, à côté des résultats, un **compteur par
  option de filtre**, calculé **tous les autres filtres appliqués sauf le sien** (comptage facetté
  classique). Une option à `0` est grisée côté UI.
- **Périmètre des compteurs** : les **tabs d'état** (Vérifiés / Favoris / Dévoilés / Non dévoilés) ne
  sont **pas des critères** — leurs compteurs se calculent dans le **périmètre des critères seuls**,
  indépendamment du tab actif.
- Pagination des résultats (≈ 20/page, scroll infini côté front).

## Invariants

- Les compteurs sont **calculés serveur** : le front ne descend jamais le vivier pour compter.
- Les filtres serveur implicites (visibilité, actif, soft delete) s'appliquent **aussi** aux agrégats —
  un compteur ne doit jamais révéler l'existence d'un profil non exposable.

## Critères d'acceptation

- [ ] L'autocomplétion des postes répond en quelques ms sur ~30 k profils, avec compteur par
      suggestion.
- [ ] La recherche renvoie les agrégats par facette, corrects au comptage facetté (une facette ne se
      compte pas elle-même).
- [ ] Les compteurs des tabs d'état sont **stables quel que soit le tab actif**.
- [ ] Un profil non exposable n'apparaît dans **aucun** compteur.
- [ ] `make api-quality` vert.

## Hors périmètre

- Recherche assistée par IA (POC front sur la Smart Home).
- Tri par colonne : **révoqué** — seul « vérifiés en tête » existe.
