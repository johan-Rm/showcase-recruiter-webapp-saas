# showcase-recruiter-webapp-saas — Frontières de responsabilité (webapp)

> Qui fait quoi entre les couches du `webapp/` et, surtout, **ce que chaque couche ne fait pas**.
> Chapeau du triptyque **composables / stores / services**. Complète
> [architecture.md §2 (Couches & flux)](./architecture.md) et détaille par couche dans
> [composables-standard.md](./composables-standard.md), [stores-standard.md](./stores-standard.md),
> [services-standard.md](./services-standard.md).

## Principe

Une donnée traverse toujours les couches dans le même sens : **rendu → orchestration → état /
métier → données**. Chaque couche a une responsabilité unique et non superposable ; une couche ne
court-circuite pas la suivante. Le respect de ces frontières est ce qui rend l'app testable,
SSR-safe et lisible.

## Frontières de code

| Couche | Rôle | Ne fait **pas** |
| --- | --- | --- |
| `app/pages/` | Orchestre la route : compose composables + composants, porte les métadonnées de page | Transformation métier, appel réseau brut inline |
| `app/components/` | Affiche des données déjà prêtes reçues par props | **Récupérer ses données** (règle CI `app-no-fetch-in-components`) ; **s'attribuer une région du modèle de page** — `<section>`/`<aside>`/`<main>` et les classes de placement appartiennent à celui qui place (règle CI `components-no-page-region`) |
| `app/composables/` | Passerelle réactive UI ↔ données : état, `computed`, sync URL / cycle de vie ; déclenche le BFF | Porter le métier pur ; muter un store en douce |
| `app/stores/` | État global brut (DTO) + lecture/dérivation par getters + setters explicites | Appeler le réseau ; connaître l'UI ; porter le métier |
| `services/` | Métier pur, framework-agnostic : transformation DTO → contrat prêt à rendre | Importer Vue/Nuxt/Pinia (règle CI `services-no-framework-imports`) ; lire un store ou le runtime |
| `server/` (BFF) | Routes Nitro `/api/*` : attachent le JWT de session, relaient vers l'API Symfony, normalisent | Exposer un secret au client |
| `shared/` | Types (`shared/types/`) et utils purs partagés par toutes les builds d'espace | Dépendre d'une couche `app/` d'un espace |

Le détail des conventions de chaque dossier vit dans son **README co-localisé** ; ces docs
d'architecture fixent les **frontières** et le **pourquoi**.

## Frontières réseau

C'est la frontière la plus stricte, et deux règles CI la verrouillent :

- **Un composant n'appelle jamais le réseau** — ni API interne, ni externe (règle CI
  `app-no-fetch-in-components` : `$fetch`/`useFetch`/`useLazyFetch` interdits dans
  `components/**`). Il reçoit ses données par props ou via un composable.
- **Un composable ou une page appelle le BFF** via `useFetch('/api/*')` : c'est la couche serveur
  de la même app (en SSR, l'appel exécute le handler Nitro sans requête HTTP réelle).
- **L'API Symfony externe n'est jamais appelée en direct depuis le client.** Le BFF
  ([`server/utils/api.ts`](../../../../webapp/base/server/utils/api.ts) — `apiFetchForUser`) lit le
  JWT de session, l'attache en `Bearer` et gère le refresh. Le client ne voit jamais le token.
- **Le store ne touche pas le réseau** : le composable fait le `useFetch` et hydrate le store via un
  setter (cf. [stores-standard.md](./stores-standard.md)).

> Divergence assumée avec le socle blue-bay : là-bas les appels client passent par une couche
> `services/api/` (fetcher injecté). En showcase-recruiter-webapp-saas, **il n'y a pas de `services/api/` client** — tout
> l'accès aux données passe par le **BFF Nitro**, et `services/` reste du métier pur sans réseau.

## Frontières de documentation

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/` — invariants et frontières globales (ce doc et ses voisins).
- `README.md` co-localisés — conventions techniques d'un dossier précis.
- En cas de conflit, l'ordre normatif est celui du [CLAUDE.md racine](../../../../CLAUDE.md).
