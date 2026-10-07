# TASK ENT-089 — entreprise : le thème des boutons survit au démarrage à froid

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — le réglage quitte le fichier de layer pour le `nuxt.config`
  racine, et l'application redémarre à froid sans tomber.
- **Type** : correctif. Aucune modification visible : le curseur reste ce qu'il était, il cesse
  seulement d'emporter l'application avec lui.
- **⚠️ Découvert en implémentant `ENT-088`**, sur le premier redémarrage du serveur de développement
  depuis que le fichier existe. Il ne s'était jamais manifesté.
- **Tests** : `sans objet` — il n'y a pas de code à couvrir, le correctif DÉPLACE une déclaration.
  La preuve est un **redémarrage à froid réel**, avant et après.
- **Réf** : `ENT-082` — qui a posé ce fichier.

## Contexte

`ENT-082` avait besoin de rendre le curseur en main sur les boutons Nuxt UI, que Tailwind v4 ne pose
plus. Le réglage a été écrit dans un fichier neuf, `webapp/base/app/app.config.ts`, à la racine de la
layer socle.

**Il fait tomber toute l'application au premier démarrage à froid.** Pas la Visionneuse, pas l'espace
entreprise : **toutes les pages des quatre espaces**, en erreur 500 :

```
message : "defineAppConfig is not defined"
stack   : at /app/base/app/app.config.ts:14:15
```

`defineAppConfig` est une fonction que Nuxt injecte à la compilation. Dans un `app.config.ts` de
**layer**, la transformation ne s'applique pas : le fichier part alors comme un module ordinaire, et
l'appel échoue.

**Rien ne l'avait signalé, et c'est le point important.** Le fichier a été créé pendant que le
serveur de développement tournait ; le rechargement à chaud l'a pris en charge et tout a fonctionné
pendant des heures. Le défaut n'existe qu'au **démarrage**, et il n'y avait pas eu de démarrage
depuis. Il serait apparu en préproduction, sur un déploiement, en 500 sur toutes les pages.

## Périmètre

- **Le réglage passe dans le `nuxt.config` racine**, sous la clé `appConfig`.
- **Le fichier `base/app/app.config.ts` disparaît.**

## Hors périmètre

- **Le réglage lui-même**, qui ne change pas d'un caractère.
- **Les autres options de module** : `ui.fonts`, `icon.serverBundle` et `colorMode` vivent déjà dans
  le `nuxt.config` racine, pour une raison voisine.

## Décisions de cadrage

### Le racine, et pas un `app.config.ts` ailleurs

Le `nuxt.config` racine porte déjà les options de modules, et son commentaire dit pourquoi : le
projet TypeScript de la layer socle ne charge pas les augmentations de types de `NuxtConfig`, si bien
qu'une clé de module y serait signalée inconnue. `appConfig` rejoint donc `ui` et `icon`, au même
endroit et pour le même motif.

L'autre issue — un `app.config.ts` à la racine de `webapp/` — aurait supposé de créer un dossier
`app/` racine que l'architecture en layers n'a pas : le projet racine n'est qu'une coque qui étend
`spaces/<espace>` et `base`.

### Le commentaire part avec le réglage, et gagne une ligne

Celui de `ENT-082` explique pourquoi le curseur est nécessaire et pourquoi il se pose sur le slot
`base` — il reste vrai et il suit. S'y ajoute la raison de l'emplacement, sans quoi la prochaine
carte recréera un `app.config.ts` de layer en croyant bien faire.

## Règles et cas limites

- **Le rechargement à chaud masque ce type de défaut.** Un fichier que Nuxt traite spécialement —
  `app.config.ts`, `nuxt.config.ts`, un plugin — ne se valide qu'après un redémarrage.
- **Les quatre espaces** partagent ce `nuxt.config` racine : le correctif vaut pour tous.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/`, aucun README : rien ne documentait cet emplacement.

## Définition de terminé

- [x] L'application répond à un démarrage à froid.
- [x] Le curseur en main est toujours posé sur les boutons.
- [x] Le fichier de layer a disparu.

## Vérification

### Avant — sur un conteneur recréé, cache Nuxt neuf

```
GET /                    → 500  defineAppConfig is not defined
GET /recherches/{uuid}   → 500  defineAppConfig is not defined
```

### Le fichier écarté, à titre de contre-épreuve

```
GET /                    → 200
```

### Après — réglage déplacé, redémarrage à froid

```
GET /recherches/{uuid}   → 200
grep cursor-pointer sur le HTML servi → présent
```

## Livré

| Fichier | Geste |
|---|---|
| `webapp/nuxt.config.ts` | (M) le thème des boutons rejoint les options de modules |
| `webapp/base/app/app.config.ts` | (D) supprimé — non transformé dans une layer |

## Reste à faire (hors périmètre)

- **Rien.**
