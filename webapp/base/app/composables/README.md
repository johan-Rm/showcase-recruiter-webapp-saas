# base/app/composables/

Passerelle réactive entre l'UI et la logique applicative.

## Rôle et responsabilités

**Rôle Nuxt :** un composable est une fonction de composition auto-importée qui encapsule une
logique **réactive** réutilisable — état, valeurs dérivées (`computed`), watchers, cycle de vie —
exposée via une API nommée explicite. Réf. : [doc Nuxt — `composables/`](https://nuxt.com/docs/4.x/directory-structure/app/composables).

**Rôle attendu :** la couche composable est la passerelle réactive entre l'affichage (pages,
composants) et les données (stores, services). Elle orchestre la réactivité — état, valeurs
dérivées, synchronisation UI / URL / cycle de vie — et expose des données déjà prêtes à rendre,
pour que les composants restent passifs. **La logique métier pure ne lui appartient pas** : elle
est déléguée aux services. Elle distingue explicitement l'état local qu'elle crée de l'état
global qu'elle partage.

Le test d'admission : _a-t-elle besoin de la réactivité Vue ou du contexte runtime Nuxt
(`useRequestURL`, `useRoute`, session…) ?_ Sinon, ce n'est pas un composable — c'est un `util`
ou un `service`.

Le rôle dans le triptyque et les frontières réseau sont fixés dans
[docs/2.architecture/composables-standard.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/composables-standard.md).

## Conventions techniques

### Responsabilité unique

Une responsabilité claire et identifiable (`useSearch`, `useUserProfile`) — pas de fourre-tout
(`useGlobalStuff`, `useHelpers`).

**Le nom dit la responsabilité, jamais l'espace.** Dans `spaces/admin/`, le composable du vivier
s'appelle `useVivier` et non `useAdminVivier` : la layer porte déjà le domaine, le répéter n'ajoute
rien. Les stores et les chargeurs de cette même couche le font déjà (`useVivierStore`, `loadVivier`).

Le nom qui compte est celui de l'**export**, pas celui du fichier : Nuxt scanne les exports, et
renommer le fichier seul laisse l'auto-import inchangé sans qu'aucune erreur ne le signale. Règle CI
`app-no-space-prefix`.

### API explicite

Les noms du `return {}` reflètent le métier (`users`, `isLoading`, `refreshUsers`), pas la
mécanique (`data`, `loading`, `execute`).

### Side-effects explicites

Aucun appel API ni comportement critique déclenché implicitement à l'invocation. L'appelant
déclenche l'effet lui-même :

```ts
const { loadUser } = useUser()
await loadUser()
```

### Séparer orchestration et logique métier

Le composable orchestre la réactivité Vue. La transformation métier est externalisée dans
`services/`, `utils/` ou des fonctions pures.

### Watchers limités

Les `watch()` restent rares et justifiés (sync URL, déclenchement API). Préférer `computed` et
une architecture déclarative.

### Pas de composable « magique »

Pas de mutation de store silencieuse, d'analytics caché ni d'appel réseau invisible. Les effets
sont lisibles et prévisibles, documentés en tête de composable.

### État local vs partagé

Exprimer clairement s'il crée un état local (`ref`) ou partage un état global (`useState('key')`).

### Taille

Pas plus de **8 valeurs** dans le `return {}`. Un composable volumineux cache plusieurs
responsabilités : découper en composables orientés feature (`usePropertyFilters`,
`useBookingCalendar`).

Seuil **non verrouillé en CI**, et c'est délibéré : aucun composable du projet ne l'a jamais
approché, et une règle sans défaut observé est une prédiction, pas un garde-fou. À verrouiller
le jour où un composable obèse apparaît — pas avant.

Les règles transverses de la couche `app/` s'appliquent (voir [../README.md](../README.md)).
