# showcase-recruiter-webapp-saas — Standard des services

> Ce qu'est un service dans le `webapp/`, comment la couche est organisée, et quand créer un service
> plutôt qu'un composable ou un util. Couche métier du triptyque. Complète
> [responsibility-boundaries.md](./responsibility-boundaries.md),
> [composables-standard.md](./composables-standard.md) et le README co-localisé
> [`services/README.md`](../../../../webapp/services/README.md).

## Intent

Un service porte la **logique métier pure**, réutilisable, indépendante du framework de rendu. Il
**reçoit ses entrées en paramètres et retourne des données** — il ne connaît ni le cycle de vie des
composants, ni l'état réactif, ni le store. Il est consommé par les composables, les pages et le
serveur, et reste testable hors runtime Nuxt.

Racine `webapp/services/` (comme `shared/`), **hors layers** : partagée par toutes les builds
d'espace.

## Règle fondamentale : framework-agnostic

Un fichier de `services/` **n'importe ni `vue`, ni `nuxt`, ni `#app`, ni `#imports`, ni `pinia`**.
Cette règle est **verrouillée en CI** (`services-no-framework-imports`,
[rules.yaml](../../../../webapp/scripts/ci/rules.yaml)).

Imports autorisés dans un service :

- bibliothèques tierces **pures** (ex. `zod`) ;
- `#shared/...` (ou chemin relatif) pour les types et utils partagés ;
- d'autres services de `services/`.

La logique réactive, l'accès au store et les primitives Nuxt appartiennent aux `composables/` ou
aux `pages/`, qui consomment les services.

## Organisation : par domaine

La couche est organisée **par domaine métier**, un sous-dossier par domaine — pas par type de
fonction. État actuel :

```txt
services/
├── auth/loginFailure.ts        # motif d'un refus de connexion, lu côté BFF puis côté navigateur
├── organization/review.ts      # DTO entreprise → carte de revue prête à rendre
└── validation/violations.ts    # violations 422 Symfony → forme affichable champ par champ
```

Ajouter un domaine = ajouter un sous-dossier (`services/<domaine>/<fichier>.ts`). Pas de barrel
`index.ts` tant que le dossier n'expose pas une API publique stabilisée.

> Divergence assumée avec le socle blue-bay, qui découpe `services/` par **type de traitement**
> (`mapper/`, `content/`, `seo/`, `api/`…) parce que c'est un site de contenu à build statique.
> showcase-recruiter-webapp-saas est une app pilotée par l'API : pas de couche `services/api/` cliente (le réseau passe
> par le **BFF**), pas de `content/`/`seo/` de génération. On découpe par **domaine**.

## Pattern : la transformation vit à la frontière

Un service résout **à la frontière** le flou que l'UI ne doit pas porter : champs optionnels du DTO,
valeurs par défaut contractualisées, agrégations d'affichage. Le template reçoit un contrat déjà
propre.

```ts
// services/organization/review.ts — DTO API (tout optionnel) → contrat prêt à rendre
export const toReviewCard = (organization: Organization): OrganizationReviewCard => ({
  id: organization.id ?? '',
  name: organization.name ?? '',
  // Ville et pays lus d'un bloc : « Casablanca, MA », ou l'un, ou rien.
  location: [organization.city, organization.country].filter(Boolean).join(', ') || null,
  // Le contrat garantit le statut : pas de valeur inventée hors énumération.
  validationStatus: organization.validationStatus ?? 'EN_REVUE',
})
```

Règles :

- **ne jamais `throw` sur une donnée manquante** : fallback contractualisé (`?? null`, valeur
  d'énumération valide) ;
- préférer des **helpers purs et nommés** à une fonction monolithique ;
- **mêmes entrées → mêmes sorties**, sans effet observable.

## Quand créer un service

| Situation | Couche |
| --- | --- |
| Transformation DTO → contrat prêt à rendre | `services/<domaine>/` |
| Normalisation d'une erreur API en forme affichable | `services/<domaine>/` |
| Logique réactive liée à Vue (état, `computed`, cycle de vie) | `app/composables/` |
| Accès aux données (API Symfony) | `server/` (BFF), appelé par un composable |
| Helper pur sans domaine, réutilisé hors `services/` | `shared/utils/` |
| Type partagé par plusieurs couches | `shared/types/` |

## Placement des types

Un type partagé par plusieurs fichiers va dans `shared/types/` ; un type local non exporté reste
dans le fichier qui l'utilise.

## Ce qu'un service ne fait pas

- lire l'état global (`useXxxStore`) ou en dépendre ;
- appeler `useRoute`, `useRuntimeConfig` ou tout composable Nuxt ;
- manipuler le DOM ou lire `window` ;
- appeler le réseau (rôle du BFF, déclenché par le composable) ;
- porter de la logique d'orchestration UI (transitions, ancres, état d'affichage).
