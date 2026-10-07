# TASK 057 — webapp : couche de transformation DTO → modèles de store

- **Créée le** : 2026-07-09.
- **Statut** : ✅ clôturée le 2026-07-27 — **remplacée** par le pattern « transformation en service à
  la demande » (audit du 2026-07-27 en fin de fiche). Pas de couche `services/mapper/` générale.
- **Type** : Front — Webapp Nuxt (architecture, contrats de données).
- **Dépend de** : tâche `050` (types TS générés depuis l'OpenAPI — les entrées des mappers).
- **Débloque** : tâche `055` (bootstrap — les stores consomment les modèles, jamais les DTO) ·
  tâche `043` (vivier).
- **Réf** : projet de référence `blue-bay-mogador` (hors repo) : `services/mapper/*` ·
  conventions API showcase-recruiter-webapp-saas (réponses plates, relations par uuid, value objects en sous-objet).

## Contexte

La tâche `050` donne des types TS fidèles aux DTO de l'API — mais un DTO n'est pas un modèle
de store. Le bootstrap (tâche `055`) veut des **métadonnées à plat** et des entités prêtes à
l'affichage ; sans couche dédiée, la transformation se ferait dans les stores ou les
composants, dispersée et intestables.

Le projet de référence isole cette responsabilité dans `services/mapper/` : fonctions **pures,
framework-agnostic** (zéro import `vue`/`nuxt`/`pinia`), tests Vitest colocalisés.

## Objectif

Une frontière nette : côté API des DTO générés, côté app des modèles de store — et une seule
couche autorisée à passer de l'un à l'autre.

## Architecture

- **`webapp/services/mapper/`** — fonctions pures de transformation :
  - entrée : types générés de la tâche `050` (jamais de `as Type` sur une donnée externe) ;
  - sortie : modèles définis dans `shared/types/` (partagés app + server) ;
  - responsabilités : mise à plat des relations (uuid), normalisation des value objects,
    valeurs par défaut, parsing des dates, préparation des libellés i18n (la locale est un
    **paramètre**, le mapper ne lit pas l'état global) ;
  - un fichier par entité (`candidate.ts`, `referentials.ts`…), test colocalisé
    (`candidate.vitest.ts`) avec des fixtures factices.
- **Règle de consommation** : les routes BFF (`server/api/*`) et les stores Pinia ne
  manipulent que des modèles ; aucun DTO brut ne franchit la couche. Le point de
  transformation naturel est côté serveur (la route BFF mappe avant de répondre), le payload
  SSR transporte déjà des modèles.
- Cette tâche **introduit la couche `webapp/services/`** : c'est le déclencheur de reprise de
  la tâche `048` — activer au minimum `verify-no-framework-imports` sur `services/` dans la
  foulée (ou l'inscrire immédiatement à la suite).

## Invariants

- `services/` n'importe jamais `vue`, `nuxt`, `#app`, `#imports`, `pinia` ni un auto-import
  Nuxt : la couche est testable hors runtime.
- Fonctions pures : entrées en paramètres, sorties en données, aucun effet, aucun accès à
  l'état global ou au runtime config.
- Pas de `any` ; pas de `as Type` sur les données externes — les types générés (ou zod aux
  frontières incertaines) font foi.
- Fixtures de test factices — aucune donnée candidat réelle.
- Modèles à plat : pas de graphe imbriqué recomposé côté front (les relations restent des
  uuid + stores dédiés).

## Critères d'acceptation

- [ ] Chaque entité consommée par la webapp a son mapper et son test colocalisé.
- [ ] `grep` : aucun import framework dans `webapp/services/` ; aucun type DTO généré
      référencé dans un store ou un composant.
- [ ] Un DTO de fixture malformé (champ manquant/null inattendu) produit un modèle aux
      défauts documentés ou une erreur explicite — jamais un `undefined` silencieux.
- [ ] Tests Vitest verts ; `bun run quality:check` vert.
- [ ] Tâche `048` : garde `verify-no-framework-imports` activée ou planifiée immédiatement.

## Hors périmètre

- La génération des types DTO eux-mêmes → tâche `050`.
- Le chargement/séquencement des données → tâche `055`.
- Mappers d'écriture (modèle → payload de mutation) : à ajouter quand les premières mutations
  front arriveront (hors lecture, rien en V1 vivier).

## Mise à jour 2026-07-12 — différée, à raison

Audit : **aucune entité de l'API ne traverse vers l'app**. `/api/me` (la seule route BFF de
lecture) n'est **jamais appelée** — c'est une route de démo. Il n'y a donc **rien à mapper** :
écrire des mappers candidat maintenant serait spéculatif, d'autant que le cadrage du Vivier
**bouge** (Favoris/Shortlist repassés sur la recherche, task `008` à refondre).

En revanche, cette tâche disait elle-même : « c'est le déclencheur de reprise de la `048` —
activer `verify-no-framework-imports` dans la foulée ». **C'est fait** : la frontière de
`services/` est **verrouillée en CI** pendant que la couche est encore vide — le meilleur moment.

**Déclencheur de reprise** : la première entité réellement consommée par un écran, c'est-à-dire
le Vivier (tâche `043`). Les mappers naîtront avec elle, pas avant.

## Clôture 2026-07-27 — remplacée par le pattern service à la demande

Audit du flux réel **API → BFF → store → écran** sur les huit domaines admin (routes livrées) :

- **Le BFF ne mappe rien** : chaque route `base/server/api/admin/*` est un **relais typé pur**
  (`apiFetchForUser<T>(event, '/v1/...')` — le `<T>` est un cast, pas une transformation). Choix
  assumé : BFF fin, l'API reste juge de l'accès.
- **Les types générés (task `050`) sont en place** (`shared/types/api.ts`, openapi-typescript) et
  déjà consommés directement par certaines routes (`vivier`, `referentials`).
- **L'API renvoie déjà des read-models plats** : les classes `*Output` PHP (`AdminDashboardOutput`,
  `CandidateCardOutput`…) **sont** le mapping, côté serveur. Rien à re-transformer côté webapp pour
  les DTO plats (compteurs, files paginées).
- **La seule vraie transformation existe déjà et est au bon endroit** : `services/organization/`
  `toReviewCard` résout l'optionalité et compose (DTO tout-optionnel → carte ferme). C'est
  exactement l'esprit de cette fiche, appliqué **là où un écran en a besoin**, à la carte.

**Décision** : ne pas introduire de couche `services/mapper/` en bloc — spéculatif (KISS/YAGNI). La
transformation vit dans `services/<domaine>/`, **née avec le besoin** (patron `toReviewCard`), pas
par anticipation. Cette fiche est close ; son intention survit dans ce patron.

**Risque latent à surveiller (≠ mappers)** : plusieurs `#shared/types/*` admin sont des **miroirs
écrits main** qui *durcissent* l'optionalité du généré (`: number` là où l'OpenAPI dit `number?`).
Le relais ne fait que **caster** : si l'API omet un champ, le store détient `undefined` sous un type
qui jure `number`. Pas de bug actif (ces champs sont toujours servis), mais **déclencheur futur** —
le jour où un écran casse sur un champ manquant, ajouter un mini-mapper `services/<domaine>/` (comme
`toReviewCard`) qui rend un modèle ferme. Alternative : aliaser le type sur `components['schemas']`
et absorber l'optionalité au point d'usage.

