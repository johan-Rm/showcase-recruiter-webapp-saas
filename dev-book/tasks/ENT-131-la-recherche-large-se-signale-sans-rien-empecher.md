# TASK ENT-131 — une recherche large se signale, sans rien empêcher

- **Créée le** : 2026-08-17.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-17 — une recherche au-dessus du seuil porte un avertissement sur sa
  carte et à son ouverture ; rien n'est empêché, et le seuil vient de la configuration servie.
- **Type** : Frontend espace entreprise — un conseil affiché, jamais bloquant.
- **Tests** : vitest sur la règle du seuil, dans la couche pure : au-delà du seuil le conseil est dû,
  en deçà il ne l'est pas, et le seuil vient de la configuration servie plutôt que d'un nombre écrit
  dans l'écran.
- **Réf** : **amont** `API-059` (le nombre devient un seuil de recommandation) · `ADM-025` (le même
  seuil, côté supervision) · `i18n.md`.

## Contexte

**Cent profils cesse d'être une limite pour devenir un conseil.** Une recherche qui en ramène 352
reste entièrement parcourable — mais trier 352 profils un par un est un travail que personne ne mène
jusqu'au bout, et le recruteur n'a aujourd'hui aucun signal le lui disant.

Côté showcase-recruiter-webapp-saas, l'écran de supervision livré par `ADM-025` liste déjà ces recherches. **Le recruteur,
lui, ne sait rien.** Le conseil lui donne l'information et le geste qui la résout : ajouter un
critère.

## Périmètre

- **Un avertissement affiché sur une recherche qui dépasse le seuil**, portant le nombre réel et
  l'action qui le réduit.
- **Sur la carte de la recherche**, là où le nombre s'affiche déjà, et **à l'ouverture** de la
  recherche.
- **Trois langues**, par clés — aucun texte écrit en dur.
- **Le seuil vient de la configuration publique**, celle que l'écran lit déjà : il ne s'écrit pas
  dans le code de l'écran.

## Hors périmètre

- **Empêcher quoi que ce soit.** La recherche s'enregistre, s'ouvre et se parcourt comme les autres.
- **Le conseil pendant la composition des critères** — au moment où l'on tape, avec une estimation
  du nombre. C'est un autre écran et un autre moment ; il pourra suivre.
- **Toute action proposée automatiquement** — suggérer quel critère ajouter suppose de savoir
  lequel ; ce n'est pas cette tâche.

## Décisions de cadrage

### Le constat, puis le geste

Le texte dit ce qui se passe et ce qu'on peut y faire, sans reproche et sans jargon. Le nombre exact
y figure : c'est lui qui rend le conseil crédible.

> ⚠ Recherche large — 352 profils
> Au-delà d'une centaine, le tri devient difficile à mener jusqu'au bout.
> Ajoutez un critère pour resserrer.

Une mention sèche sans explication n'aide pas qui ignore pourquoi c'est un problème ; une formule
tournée vers le bénéfice, sans nombre, rend incompréhensible qu'elle apparaisse ici et pas ailleurs.

### Jamais bloquant

Agir sur le travail d'un client — refuser d'enregistrer, forcer un critère — est une décision produit
qui n'a pas été prise. Le conseil informe.

### Un seul seuil dans tout le produit

Le même nombre gouverne le conseil au recruteur et l'inventaire de supervision. Deux réglages
finiraient par signaler à l'un ce qu'ils taisent à l'autre.

### Deux formes pour un seul texte — arbitré à l'implémentation

Le cadrage demandait le conseil « sur la carte » et « à l'ouverture », sans dire qu'il y prendrait la
même forme. Il n'y en prend pas : la carte de collection est un objet dense où le rail, les critères
et deux boutons se disputent déjà la hauteur, et une phrase de trois lignes y aurait repoussé les
actions hors de vue sur téléphone — c'est la règle de cadrage sur le mobile qui l'interdisait. La
carte porte donc le constat seul, sur une ligne ; l'ouverture déplie le conseil entier, et c'est
aussi le moment où l'on peut y donner suite.

### Le conseil se tait tant que le seuil est inconnu

Le seuil vaut zéro le temps que la configuration arrive. Sans garde explicite, **toute** recherche
non vide serait signalée pendant ces quelques centaines de millisecondes. Un seuil à zéro ne
conseille donc rien : mieux vaut se taire qu'avertir sur un nombre inventé.

### Le seuil exact ne déclenche rien

« Au-delà d'une centaine » commence à cent un. Une comparaison large aurait fait apparaître
l'avertissement sur une recherche exactement conforme à ce qu'on recommande.

## Règles et cas limites

- **Le conseil ne s'affiche pas sur une recherche close** : elle ne se travaille plus.
- **Le nombre affiché est le nombre réel de correspondances**, jamais un nombre servi.
- **Accessibilité** : l'avertissement est annoncé comme tel aux lecteurs d'écran, et ne repose pas
  sur la seule couleur.
- **Mobile** : sur la carte, le message ne doit pas repousser hors de vue le nombre ni les actions.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : le seuil est déjà décrit côté serveur par `API-059`, et la liste
des clés de langue ne se documente pas.

## Vérification

Parcours rejoué au navigateur sur la pile du worktree entreprise, compte de recette, 64 recherches.

**Le compte, confronté à la source.** L'API sert **8 recherches au-dessus de 100 profils, dont 1
close** (113 profils, close le 2026-07-31). L'écran de collection en signale **7** — exactement les
ouvertes :

```
── COLLECTION — /recherches
   avertissements : 7 sur 64 carte(s)
   mention lue à voix haute : « Avertissement · »
   « Avertissement · Recherche large — 352 profils »
   « Avertissement · Recherche large — 149 profils »
   « Avertissement · Recherche large — 183 profils »
   « Avertissement · Recherche large — 266 profils »
```

**À l'ouverture**, sur les deux outils, au même endroit — sous la bande de titre, sans rien
recouvrir :

```
── VISIONNEUSE — une recherche de 352 profils
   « Avertissement · Recherche large — 352 profils Au-delà d'une centaine, le tri devient
     difficile à mener jusqu'au bout. Ajoutez un critère pour resserrer. »
── TABLE — la même recherche
   (texte identique)
visionneuse : avertissement visible à y=180, 1422×76 px
table       : avertissement visible à y=180, 1422×76 px
```

**La carte ne grandit pas** et n'expulse rien :

```
carte AVEC conseil : 702×180 px
carte SANS conseil : 702×180 px
carte étroite (390 px de large) : 374×190 px
   actions dans la carte : 3/3 · débordement horizontal : non
```

Captures relues à l'œil — collection, Visionneuse, Table, et la carte seule en large et en étroit.
Le conseil est ambre, précédé d'un pictogramme d'alerte, et se lit sans l'un ni l'autre.

Tests joués : `bun run vitest run services/search/card.vitest.ts` → **32 tests verts**.
`bun run quality-conventions-check` → **19 règles, aucune violation**. Type-check sur l'hôte → **vert
sur les quatre espaces**.

## Livré

- `webapp/services/search/card.ts` — `isBroadSearch` : la règle du seuil, pure, le seuil en argument.
- `webapp/services/search/card.vitest.ts` — sept cas, dont le seuil exact et le seuil inconnu.
- `webapp/spaces/entreprise/app/components/search/BroadNotice.vue` **(+)** — le conseil, en forme
  courte et en forme complète.
- `webapp/spaces/entreprise/app/composables/useSearches.ts` — `isBroad(search)` pour la collection.
- `webapp/spaces/entreprise/app/composables/useSearch.ts` — `isBroad` pour l'écran d'une recherche.
- `webapp/spaces/entreprise/app/components/search/Card.vue` — la forme courte, sous les critères.
- `webapp/spaces/entreprise/app/pages/index.vue`, `pages/recherches/index.vue`,
  `pages/recherches/[uuid]/index.vue`, `pages/recherches/[uuid]/table.vue` — les quatre poses.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — quatre clés, trois langues.

## Reste à faire (hors périmètre)

- **Le champ de configuration s'appelle encore `searchLoadLimit`** alors qu'il ne borne plus rien :
  c'est un seuil de recommandation depuis `API-059`. Le renommer touche le contrat d'API — autre
  lane, à nommer là-bas.

## Définition de terminé

- [x] Une recherche au-dessus du seuil affiche l'avertissement sur sa carte et à son ouverture.
- [x] Une recherche en dessous n'affiche rien.
- [x] Une recherche close n'affiche rien.
- [x] Rien n'est empêché : enregistrement, ouverture et parcours restent identiques — le conseil n'a
      aucune action, et la pile se parcourt comme avant.
- [x] Le seuil vient de la configuration servie, et le texte des clés de langue, en trois langues.
- [x] L'avertissement est annoncé aux lecteurs d'écran et lisible sur écran étroit.
- [x] Tests vitest sur la règle du seuil.
