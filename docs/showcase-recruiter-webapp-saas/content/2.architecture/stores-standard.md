# showcase-recruiter-webapp-saas — Standard des stores (Pinia)

> Comment les stores Pinia du `webapp/` sont organisés : **état brut sérialisable**, getters de
> lecture et de dérivation, setters explicites. Couche état du triptyque. Complète
> [responsibility-boundaries.md](./responsibility-boundaries.md),
> [composables-standard.md](./composables-standard.md), [services-standard.md](./services-standard.md),
> [data-contracts.md](./data-contracts.md) et le README co-localisé
> [`stores/README.md`](../../../../webapp/base/app/stores/README.md).

## Rôle

Un store centralise **l'état global** d'un domaine, partagé entre plusieurs composants ou
composables et exposé via un contrat typé (state, getters, actions). Il **ne porte pas la logique
métier** (déléguée aux `services/`), **n'appelle pas le réseau** (rôle du composable via le BFF)
et **ne connaît pas l'UI**. Un état sans vocation globale reste local à son composable (`ref`), il
ne devient pas un store.

## Frontière avec les autres couches

En showcase-recruiter-webapp-saas, le réseau ne vit pas dans le store. La chaîne est :

```
Page / Composant
  └─ Composable            useFetch('/api/<domaine>')  ← appel réseau (BFF Nitro → API Symfony)
        │                  puis hydrate le store via setX(...)
        ▼
     Store (Pinia)         état brut (DTO) · getters (lecture + dérivation) · setX · reset
        │                  aucun réseau, aucune UI
        ▼
     services/             enrichissement / transformation métier pure (si nécessaire)
```

- Le **composable** déclenche l'appel BFF (`useFetch` vers `/api/*`) et **hydrate** le store par un
  setter. Il porte le `pending`/`error` du fetch et expose au rendu des données déjà prêtes.
- Le **store** conserve le DTO brut et l'expose en lecture. Il ne fait **jamais** d'appel réseau et
  ne dépend pas du runtime Nuxt au-delà de Pinia.
- L'**enrichissement** (mapping, calcul dérivé) est délégué à [`webapp/services/`](../../../../webapp/services/README.md),
  couche pure sans dépendance framework. Un getter *orchestre* l'appel au service ; il ne porte pas
  la transformation lui-même.

> Divergence assumée avec le socle blue-bay dont ce standard est porté : là-bas le store transient
> délègue le réseau à une couche cliente `services/api/`, et l'hydratation des data-stores passe par
> un bootstrap global. En showcase-recruiter-webapp-saas, l'appel réseau vit dans le composable (`useFetch` sur le BFF)
> qui hydrate le store — pas de couche `services/api/` cliente. Le principe reste identique : **le
> store ne touche pas le réseau.**

## Deux styles de store

### Options API — store de données (défaut)

Pour un domaine qui conserve une donnée stable chargée depuis l'API (entité, liste, snapshot). Le
state stocke le **DTO brut** ; les getters exposent la lecture et l'état dérivé ; les setters
mutent. Le `pending`/`error` du chargement reste dans le composable, **pas** dans le store.

```ts
import type { components } from '#shared/types/api'

type AdminVivier = components['schemas']['AdminVivier']

type VivierState = {
  vivier: AdminVivier | null
}

export const useVivierStore = defineStore('vivier', {
  state: (): VivierState => ({
    vivier: null,
  }),

  getters: {
    getVivier(state: VivierState): AdminVivier | null {
      return state.vivier
    },
  },

  actions: {
    setVivier(payload: AdminVivier | null): void {
      this.vivier = payload && typeof payload === 'object' ? payload : null
    },

    reset(): void {
      this.vivier = null
    },
  },
})
```

Utiliser ce style quand : le store maintient une entité ou une liste persistante ; les getters
exposent (et éventuellement enrichissent) cette donnée brute.

### Composition API — store transient

Pour une opération asynchrone unique portée côté client, avec état de chargement propre. Rare en
showcase-recruiter-webapp-saas : l'orchestration async passe d'ordinaire par un composable (`useFetch`). Réserver ce
style aux mutations client-only qui doivent partager un `loading`/`error` entre plusieurs écrans.

```ts
export const useXxxStore = defineStore('xxx', () => {
  const result = ref<XxxDto | null>(null)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)

  const submit = async (payload: XxxRequest): Promise<XxxDto | null> => {
    loading.value = true
    error.value = null
    try {
      result.value = await postXxx(payload)
      return result.value
    } catch (err) {
      error.value = normalizeApiError(err)
      return null
    } finally {
      loading.value = false
    }
  }

  return { result, loading, error, submit }
})
```

## Pattern central : état brut, getter enrichi

Le **state stocke le DTO brut** (sérialisable pour le SSR). Les **getters exposent une lecture** de
ce DTO et **l'état dérivé** de la donnée — calculé **à la demande** (lazy, réactif), **jamais
persisté** dans le state ni muté.

**Responsabilités d'un getter :**

1. **Exposer le DTO en lecture** — l'UI lit par le getter, pas le state brut. Un getter *passe-plat*
   typé (`getVivier → state.vivier`) est légitime : c'est le contrat de lecture, même sans
   enrichissement. C'est le cas majoritaire quand le DTO est déjà prêt à l'affichage.
2. **Exposer l'état dérivé** — filtrage, indexation (`Map`), lookup paramétré, enrichissement via un
   service. La transformation métier est **déléguée** à `services/` ; le getter l'orchestre.

Deux formes normées de getter dérivé :

- **getter de liste** `getXxx` → renvoie un tableau (éventuellement filtré / enrichi) ;
- **getter de lookup** `getXxxBySlug` / `getXxxById` → renvoie une **fonction** paramétrée.

Un getter ne mute jamais l'état, n'appelle pas le réseau et ne connaît pas l'UI.

## Conventions d'actions

### Setters explicites, défensifs

Toute mutation passe par une action `setX` qui **valide son entrée** avant d'assigner (guard de
type, `Array.isArray`), pour absorber une source hétérogène sans planter.

```ts
setVivier(payload: AdminVivier | null): void {
  this.vivier = payload && typeof payload === 'object' ? payload : null
}
```

### `reset()` systématique

Chaque store expose `reset()` qui ramène l'état à sa valeur initiale (nettoyage à la déconnexion,
au changement de contexte, ou en test).

### Merge stable pour les listes

Pour une liste rechargée partiellement, préférer un merge par clé à un remplacement brutal, afin de
préserver la réactivité sans re-rendu global.

## Dépendances entre stores

Un getter peut lire un autre store pour composer ses données de référence. **Uniquement dans les
getters, jamais dans les actions** : une action ne doit pas dépendre du résultat d'un getter d'un
autre store.

## Règles SSR

- Le state ne contient que des valeurs **sérialisables** (pas de `ref`, pas d'objet DOM).
- `defineStore` est **auto-importé** par `@pinia/nuxt` (branché dans la layer `base`) : ne pas
  l'importer manuellement.
- Une action à effet de bord client (dépendante de `window`) reste gardée côté client.

## Ce qu'un store ne fait pas

- appeler directement le réseau — l'appel vit dans le composable (`useFetch` vers `/api/*`) ;
- porter la transformation métier — rôle de `services/` ;
- connaître les composants qui le consomment, ni piloter navigation ou affichage ;
- exposer des valeurs non typées.

## Naming

| Élément                 | Convention                                                   |
| ----------------------- | ------------------------------------------------------------ |
| Fichier                 | `camelCase.ts`, nom de domaine — ex. `vivier.ts`             |
| Export                  | `useXxxStore` — ex. `useVivierStore`                         |
| Clé Pinia               | `'camelCase'` identique au domaine — ex. `'vivier'`          |
| Type d'état             | `XxxState` local au fichier — ex. `VivierState`             |
| Getter de lecture/liste | `getXxx` — renvoie le DTO ou un tableau                      |
| Getter de lookup        | `getXxxBySlug`, `getXxxById` — renvoie une fonction          |
| Action setter           | `setX` défensif — mutation d'un champ ou d'une liste         |
| Action réinitialisation | `reset` — toujours présente                                  |

> Le préfixe `use…` porte sur **l'export** (`useVivierStore`), jamais sur le **fichier** : un
> fichier `useXxx.ts` est la convention des `composables/`, pas des stores.
