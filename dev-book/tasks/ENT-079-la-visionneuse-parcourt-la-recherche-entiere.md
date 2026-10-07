# TASK ENT-079 — entreprise : la Visionneuse parcourt la recherche entière

- **Créée le** : 2026-08-10.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-10 — la Visionneuse charge la suite du jeu à l'approche du bout et
  va jusqu'au dernier profil ; les trois compteurs portent sur la recherche entière ; le libellé
  « sur N profils chargés » a disparu. **Un défaut connu est livré avec** : au-delà d'environ six
  cents fiches, le défilement se dégrade — mesuré, arbitré, décrit en *Décisions de cadrage*.
- **Type** : feat — le défilement charge la suite, et les compteurs changent de source.
- **Tests** : **deux fonctions pures, en service testé**, au niveau des 24 suites existantes du
  dépôt : « faut-il demander la page suivante ? » et le regroupement des arrivants dans la pile. Ce
  sont les deux endroits où se logent les erreurs de bord — dernière page, chargement déjà en cours,
  jeu plus court qu'une page, profil écarté qui décale le rang. Aucun test de composant : le dépôt
  n'en outille pas, et rien ici ne l'exige.
- **Réf** : `ENT-049` — **amont**, qui a nommé le plafond et l'a explicitement reporté : *« elle
  demande une page de trente et n'en redemande jamais […] À rouvrir avec la question du chargement,
  pas seule. »* Cette carte est cette réouverture · `ENT-058` — **amont**, qui a posé les trois
  compteurs et laissé le libellé en l'état · `api-conventions.md` (§ enveloppe de pagination) ·
  `data-contracts.md` (§ constantes exposées par l'API).

## Contexte

La Visionneuse demande **une page de trente profils, et n'en redemande jamais**.

Sur une recherche de 135 profils, **105 sont inatteignables** : on défile jusqu'au trentième et la
pile s'arrête, pendant que la barre haute continue d'annoncer « / 135 ». Sur la plus grosse
recherche du vivier — 3 052 profils — ce sont 3 022 profils qui n'existent nulle part dans l'écran.

Et les trois compteurs de la colonne de gauche comptent sur ce qui est **chargé**, pas sur la
recherche. Mesuré pendant le cadrage sur la recherche `019fd61b-bdd5…` :

| | à l'écran | au serveur |
| --- | --- | --- |
| Vus | 30 | **74** |
| Favoris | 0 | **14** |
| Profils | 30 chargés | **135** |

Un libellé gris — « sur 30 profils chargés » — explique cet écart au lieu de le corriger. Il est posé
sous les trois cases alors qu'il n'est vrai que pour l'une d'elles (`ENT-058`, *Risques*).

**Rien de tout cela n'attend le serveur.** `ENT-058` concluait qu'il fallait « une tâche amont, dans
une autre lane : que le serveur rende le nombre de profils vus de la recherche entière ». Vérifié au
cadrage : **cette tâche n'a pas lieu d'être**, la fiche d'une recherche rend déjà
`resultCount`, `seenCount` et `favoriteCount` sur le jeu entier, et `?scope=dismissed` rend son
`total`. Les quatre nombres existent ; l'écran ne les lit pas.

## Périmètre

- **Le chargement de la page suivante**, déclenché quand le profil regardé approche la fin de ce qui
  est chargé.
- **Le regroupement des arrivants** : les nouveaux non vus se placent devant la queue déjà vue,
  jamais devant un profil non encore franchi.
- **Les trois compteurs**, branchés sur les nombres du serveur — avec, pour « Vus », le delta de ce
  que la session vient de marquer.
- **Un bloc d'échec en pied de pile**, avec reprise, quand une page n'arrive pas.
- **La suppression du libellé** « sur N profils chargés » de la colonne — **sa clé reste**, voir
  *Décisions de cadrage*.
- **Trois clés nouvelles** pour le pied de pile : chargement en cours, échec, reprise.

## Hors périmètre

- **La Table (« Ma sélection »).** Son onglet « Tous » lit la même pile de trente et souffre du même
  plafond. **Carte à part** : un tableau ne se charge pas comme une pile de fiches — pagination ou
  défilement de lignes, et un geste différent. Nommée ici, non traitée.
- **Le recyclage d'affichage** (ne garder dans la page que les fiches proches de l'écran). Écarté
  pour cette carte, et **conditionné à la mesure** — voir *Risques*.
- **L'API.** Aucun endpoint, aucun contrat, aucune migration : tout est déjà servi. Vérifié au
  cadrage, `?page=2` traverse le BFF et rend la deuxième trentaine.
- **Les archives `recherches-v1/` et `recherches-v2/`**, qui gèlent un état et ne suivent pas la
  ligne vivante (`ENT-077`).
- **Le rang de la barre haute**, déjà compté sur le jeu entier — il n'a jamais menti.

## Décisions de cadrage

### Tout garder dans la page, et le mesurer

Arbitré. Le recyclage d'affichage tiendrait n'importe quel volume, mais c'est un chantier à part : il
doit cohabiter avec le défilement par cran, qui a besoin de connaître la hauteur des fiches. On
livre le comportement demandé — un défilement infini **complet** — et on le mesure sur la recherche
à 3 052 profils. Si ça ne tient pas, le recyclage devient une carte aval, pas un ajout en cours de
route.

### Le compteur « Vus » : le nombre du serveur, plus ce que la session vient de marquer

Arbitré. Le serveur donne le vrai nombre mais ne le remet à jour qu'à la relecture de la fiche : le
compteur resterait figé pendant qu'on défile, ce qui se lit comme une panne. On y ajoute donc les
profils que cette session vient de déclarer vus — exact et immédiat, sans aller-retour
supplémentaire.

Les deux autres n'ont pas ce problème : « Favoris » et « Écartés » sont déjà tenus à jour localement
par les gestes qui les modifient, et leur source serveur porte sur le jeu entier.

### Le déclencheur : l'approche de la fin, pas une sentinelle

Arbitré. Le repérage du profil regardé existe déjà (`useVisibleProfile`) et sert le rang comme le
verdict. S'en servir évite une seconde mécanique d'observation, et **déclenche plus tôt** qu'une
sentinelle en bas de liste : la page suivante a le temps d'arriver avant qu'on atteigne le vide.

### Les arrivants passent devant la queue vue, jamais devant le curseur

Arbitré, et c'est la décision la plus délicate.

`applyFrozenOrder` place aujourd'hui les cartes arrivées **à la fin**. Conservé tel quel, « non vus
d'abord » ne tiendrait que sur la première trentaine : on retraverserait les dix vus de la page 1
avant d'atteindre les trente non vus de la page 2.

À chaque page reçue, seule la **queue déjà vue** est donc recomposée : les arrivants non vus se
glissent avant elle. Rien ne bouge devant le profil qu'on regarde — c'est l'invariant qui a fait
écrire le gel d'ordre, et il n'est pas levé.

### Un échec de chargement se dit, il ne se devine pas

Arbitré. Une pile qui s'arrête sans explication est exactement le défaut qu'on corrige, sous une
autre forme : on croit être au bout de la recherche. Un bloc en pied de pile annonce que la suite n'a
pas pu être chargée et propose de réessayer.

### ⚠️ Écart — « tout garder » ne tient pas, et la mesure l'a tranché

Le cadrage pariait sur le volume et exigeait de le mesurer. **Mesuré, et le pari est perdu** — sur
la recherche à 3 052 profils, défilement réel à la molette :

| Fiches | Nœuds DOM | Mémoire | Durée d'un tour de molette |
| --- | --- | --- | --- |
| 210 | 14 154 | 192 Mo | 2,6 s |
| 390 | 26 240 | 288 Mo | 5,3 s |
| 600 | 40 440 | 320 Mo | 12,8 s |
| 780 | 52 781 | 693 Mo | **28,8 s** |

Le même geste passe de 2,6 à 28,8 secondes. À 780 fiches — un quart du jeu — l'écran ne répond plus
utilement.

**Livré quand même, sur décision de l'humain**, pour deux raisons tenues pour plus fortes que le
défaut : il remplace un défaut PIRE — aujourd'hui 3 022 profils sont hors d'atteinte —, et il est
RARE : sur les 64 recherches du vivier, **une seule dépasse 300 profils** et six dépassent 200.

Le recyclage d'affichage devient la suite immédiate. **Nommé, pas ouvert** : c'est une décision
d'ordonnancement, elle appartient à l'humain.

### ⚠️ Écart — la clé du libellé survit, parce que les archives s'en servent

Le cadrage prévoyait de supprimer `search.viewer.panel.scope` avec le libellé. Impossible :
`search-v1/ProgressHeader.vue` et `search-v2/ProgressHeader.vue` — les deux archives d'`ENT-077` — la
rendent encore. La retirer y afficherait la clé brute.

Elle rejoint donc la **procédure de retrait des archives**, où `ENT-077` l'avait déjà inscrite.

Conséquence visible, et assumée : les archives héritent des nouveaux compteurs — ils viennent du
composable partagé — et affichent donc « 74 vus » sous leur propre libellé « sur 30 profils
chargés ». Contradiction interne à l'échafaudage, qui disparaît avec lui.

### Écart mineur — les compteurs viennent des listes d'identifiants, pas des nombres de la fiche

« Favoris » et « Écartés » se lisent sur `favoriteIds` et `dismissedIds` du store plutôt que sur les
nombres de la fiche. Même vérité — ces listes arrivent du serveur sans pagination et portent sur le
jeu entier — mais elles vivent dans le store, donc **traversent le rendu serveur**. C'est ce qui
corrige au passage le « 0 Favoris » sur une recherche qui en porte 14 : le compteur lisait une
variable locale du composable, que le navigateur ne reçoit jamais.

### La taille de page ne se code jamais en dur

Contrainte trouvée dans le dépôt, pas arbitrée : `GET /config` expose `perPage`, le store des
référentiels a déjà son accesseur, et l'enveloppe de réponse (`items` / `total` / `page` / `perPage`)
le porte à chaque appel. Écrire `30` quelque part serait une dérive de constante, que
`data-contracts.md` interdit.

## Règles et cas limites

- **Dernière page atteinte** : plus rien à demander, aucun bloc, aucune tentative. La condition se
  lit sur le jeu (`resultIds`) et non sur un compteur de pages.
- **Un seul chargement à la fois** : une demande en cours interdit la suivante, sinon un défilement
  rapide en lance dix.
- **Jeu plus court qu'une page** : rien ne se déclenche, et le libellé ayant disparu, l'écran ne dit
  rien de particulier — c'est le cas nominal.
- **Profil écarté** : il quitte la pile, ce qui rapproche le curseur de la fin. Le déclencheur doit
  donc se relire sur la pile courante, pas sur un rang mémorisé.
- **Changement d'ordre** en cours de parcours : le regroupement se recalcule sur **tout** ce qui est
  chargé, y compris les pages arrivées depuis.
- **Rendu serveur** : seule la première page est rendue au SSR ; les suivantes n'arrivent que dans le
  navigateur, au geste. C'est le comportement attendu, et il ne change rien à l'indexation — l'écran
  est derrière une authentification.
- **Recherche d'un collègue** (`editable` faux) : le chargement fonctionne à l'identique, on lit
  toute la recherche sans y rien trancher.
- **Aucun rôle n'est concerné** : ni permission, ni contrôle d'accès ne varient. Dimension écartée.

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/`.** Vérifié au cadrage : aucune ne décrit le plafond de
  chargement ni la portée des compteurs. `api-conventions.md` documente l'enveloppe de pagination,
  qui ne change pas.
- **Les commentaires de tête qui deviendront faux** : `useSearch.ts` (« ⚠️ Comptés sur la PILE
  CHARGÉE, pas sur le jeu »), `ProgressHeader.vue` (ce que couvrent les trois chiffres),
  `stack.ts` (la réconciliation des arrivants).
- **`ENT-049`**, dont la section *Risques et reports assumés* pointe cette carte comme sa suite.

## Définition de terminé

- [x] On défile jusqu'au dernier profil d'une recherche de 135, sans butée.
- [x] Les trois compteurs affichent les nombres de la recherche entière — 74 vus et 14 favoris là où
      l'écran montrait 30 et 0.
- [x] « Vus » monte pendant qu'on défile, sans attendre un rechargement.
- [x] Un profil chargé après coup et non vu se place devant les déjà-vus, et rien ne bouge devant le
      profil regardé.
- [x] Le libellé « sur N profils chargés » a disparu de la colonne — sa clé reste tant que les
      archives d'`ENT-077` la rendent.
- [x] Une page qui échoue laisse un bloc explicite, avec reprise qui fonctionne.
- [x] Aucune occurrence de `30` en dur : le rang de page se compte, la taille vient du serveur.
- [x] Mesure du volume sur la recherche à 3 052 profils, chiffres reportés dans la carte.

## Vérification

### Le parcours complet — 135 profils, du premier au dernier

Geste réel à la molette, session `atlas.owner`, recherche `019fd61b-bdd5…` (Développeur logiciel ·
java · sql).

```
départ  :  30 fiches · rang   1 / 135 · vus 74
 tour  4:  60 fiches · rang  29 / 135
 tour  5:  90 fiches · rang  61 / 135 · vus 75
 tour  9: 120 fiches · rang  89 / 135
 tour 13: 135 fiches · rang 117 / 135
arrivée : 135 fiches · rang 135 / 135 · vus 76        en 23 s
pages demandées : page=2 · page=3 · page=4 · page=5
```

Les 105 profils hors d'atteinte le sont devenus ; les pages partent dans l'ordre, une à la fois ;
« Vus » monte pendant le parcours.

### Les compteurs, à l'ouverture

```
à l’écran : 74 vus · 14 favoris · 0 écartés        libellé « sur N profils chargés » : absent
au serveur: seenCount 74 · favoriteCount 14 · resultCount 135
```

Avant cette carte, le même écran affichait **30 vus et 0 favoris**.

### Le volume, sur la plus grosse recherche

Chiffres et arbitrage dans *Décisions de cadrage* — le défilement passe de 2,6 s à 28,8 s par tour
entre 210 et 780 fiches.

### Les suites impactées

```
$ bunx vitest run services/search/stack.vitest.ts base/i18n/locales/parity.vitest.ts \
                  services/search/criteria.vitest.ts
 Test Files  3 passed (3)
      Tests  94 passed (94)
```

Dont **28 sur les deux règles nouvelles**, bords compris : dernière page, demande déjà en vol, échec
en attente, jeu plus court qu'une page, pile qui rétrécit sous les écartés, curseur sorti de la pile,
arrivant déjà placé, arrivant déjà vu.

### Les écrans voisins, qui partagent le composable

```
Table        rendu:true   aucune erreur
archive v1   rendu:true   compteurs 74/14/0
archive v2   rendu:true   compteurs 74/14/0
liste        rendu:true   64 cartes, aucune erreur
```

## Livré

| Fichier | Geste |
|---|---|
| `services/search/stack.ts` | (M) `needsNextPage` et `growFrozenOrder`, les deux règles pures |
| `services/search/stack.vitest.ts` | (M) 28 tests sur ces deux règles |
| `spaces/entreprise/app/composables/useSearch.ts` | (M) le chargement page par page, ses deux états, et les trois compteurs sur le jeu entier |
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) le déclenchement, le placement des arrivants, les nouveaux compteurs |
| `spaces/entreprise/app/components/search/Stack.vue` | (M) le pied de pile : chargement, échec, reprise |
| `spaces/entreprise/app/components/search/ProgressHeader.vue` | (M) perd le libellé et deux propriétés |
| `spaces/entreprise/app/components/search/Console.vue` | (M) relaie les compteurs au lieu des listes |
| `spaces/entreprise/app/components/search/ControlPanel.vue` | (M) idem |
| `base/i18n/locales/{fr,en,ar}.yaml` | (M) trois clés pour le pied de pile |

## Risques et reports assumés

**Le volume — mesuré, et le pari est perdu.** Les chiffres et la décision sont dans *Décisions de
cadrage*. Ce qu'il en reste pour la suite : **le recyclage d'affichage est la prochaine carte de cet
écran**, et elle n'est pas ouverte. Tant qu'elle ne l'est pas, une recherche de plus de six cents
profils se parcourt mal au-delà de ce seuil.

**`useSearch` rend vingt-quatre valeurs**, là où le README de la couche en autorise huit — seuil
délibérément non verrouillé en CI. Le composable était déjà largement au-dessus avant cette carte,
qui y ajoute cinq entrées. Constat, pas action : le découper est un refactor à part.

**La Table garde son plafond** tant que sa propre carte n'est pas écrite. Une recherche parcourue
en entier dans la Visionneuse remplira le réservoir partagé, et la Table en profitera — mais y
arriver directement continuera de ne montrer que trente lignes sur son onglet « Tous ». Écart connu,
assumé le temps d'une carte.
