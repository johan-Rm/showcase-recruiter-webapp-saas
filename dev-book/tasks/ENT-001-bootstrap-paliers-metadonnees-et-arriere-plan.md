# TASK ENT-001 — socle : le palier métadonnées, et l'arrière-plan enfin branché

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — vérifié sur le dev, admin et entreprise.
- **Type** : Frontend — socle webapp (orchestration du chargement).
- **Réf** : task 055 (les trois paliers, jamais terminée) · task 055b (l'orchestrateur) ·
  task 139 (l'anticipation admin) · task 056 / 146 (le préchargement N+1).
- **Rattachement** : `webapp/base/` travaillé **pour la refonte du chargement de l'espace
  entreprise** — donc lane entreprise, cf. task OPS-001 (le transverse suit l'espace consommateur).

## Contexte

La refonte du chargement de l'espace entreprise — tout charger au boot, ne plus rien charger à la
navigation — suppose un orchestrateur à trois paliers. L'inventaire du socle en trouve **deux**, et
un défaut.

### `loadBackgroundData` n'était appelé nulle part

Le palier 3 existait dans `useAppBootstrap` depuis la task 055b et **personne ne l'appelait** : ni
`app.vue`, ni le middleware. Le défaut est resté invisible huit tâches durant parce que les huit
routes du registre admin déclarent toutes `background: []` — un palier vide qu'on n'exécute pas
ressemble exactement à un palier vide qu'on exécute.

### Le palier 1 n'a jamais été écrit

La task 055 le décrivait en toutes lettres (« charger au plus tôt les données critiques — en
général des **métadonnées** ») et n'a livré que le volet jalons. Le front recopie donc à la main les
nomenclatures qu'il affiche.

## Décisions

### Les métadonnées vivent HORS du registre de routes

`setBootstrapMetadata(() => [...])`, à côté de `setBootstrapRegistry(...)`. Un référentiel ne dépend
pas de la page affichée : le déclarer dans chaque entrée du registre ferait d'une donnée
d'application une donnée d'écran, et huit recopies dériveraient à la première qui manque.

Une **fonction** et non un tableau : un loader capture son store et `useRequestFetch()`, il doit
naître dans un contexte Nuxt — un tableau posé au module-level s'évaluerait trop tôt.

### Séquentiel entre paliers, parallèle à l'intérieur

`runCritical` devient `runBlocking(stages)`. L'ordre porte une règle et pas une commodité : les
métadonnées sont les **relations** des données critiques, un critique servi avant elles s'afficherait
avec des libellés vides le temps qu'elles arrivent.

Un seul statut pour l'ensemble des paliers bloquants, et non un par palier : sinon le rideau de boot
repasserait par `ready` entre les deux, et clignoterait.

### L'arrière-plan de la page affichée passe avant l'anticipation des autres

À `runtimeReady`, `loadBackgroundData` **puis** `preloadAll` — séquentiels. Les deux se partagent le
même réseau, et ce qui complète l'écran sous les yeux vaut mieux que ce qui prépare l'écran suivant.

`runtimeReady` retombe à chaque navigation (cf. `deferred-runtime`), donc chaque page rejoue son
propre palier 3, avec un résolveur évalué **au déclenchement** — il peut donc lire un store déjà
hydraté par le critique pour décider quoi charger.

### Additif

Un espace qui ne déclare pas de métadonnées garde exactement le comportement d'avant : le repli est
une liste vide, et `runBlocking` d'un palier vide est un `no-op`. L'admin n'est pas touché.

## Vérification

Comportementale — Vitest est volontairement limité au code **pur** (`shared/`, `services/`), sans
runtime Nuxt : un composable d'orchestration n'y entre pas.

| Vérification | Résultat |
| --- | --- |
| HMR sur `base/` | changements repris (`page reload base/app/app.vue`) |
| admin `/candidats` authentifié | **200**, 261 Ko de HTML, **21 `<tr>` rendus au SSR** — le critique se résout toujours côté serveur après le reséquencement |
| entreprise `/recherches` authentifié | **200**, écran rendu, rideau de boot présent, aucune erreur dans les logs du conteneur |

## Reste à faire (hors périmètre de cette tâche)

- **Le palier 1 n'a pas encore de consommateur** — aucun espace n'appelle `setBootstrapMetadata`, et
  aucun ne déclare de `background`. C'est exactement l'état dans lequel `loadBackgroundData` a pourri
  huit tâches durant. Le premier consommateur est le store `referentials` de l'espace entreprise,
  nourri par `/v1/config` (task API-001) ; si cette série devait s'arrêter avant, ce palier doit être
  replié plutôt que laissé déclaré.
- **Rechargement des métadonnées au changement de locale** — prévu par la task 055. Sans objet tant
  que la réponse est indépendante de la locale (les libellés vivent dans l'i18n du front), à
  reprendre le jour où une métadonnée localisée apparaît.
