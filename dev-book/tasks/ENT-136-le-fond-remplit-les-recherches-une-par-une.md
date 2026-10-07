# TASK ENT-136 — le fond remplit les recherches, une par une

- **Créée le** : 2026-08-18.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-18 — un chargement de fond remplit les recherches une par une,
  un appel à la fois, borné à vingt recherches ou 1 500 profils.
- **Type** : Frontend espace entreprise — un chargement de fond continu, borné et muet.
- **Tests** : vitest sur la couche pure — la planification de la file : ce qui reste à charger pour
  une recherche, l'ordre des recherches, où le plafond s'arrête, ce que la file devient quand une
  recherche passe devant. Les chargeurs eux-mêmes se prouvent au navigateur : ils touchent l'état
  partagé dès leur construction, et l'outillage du dépôt ne les couvre pas.
- **Réf** : **amont** `ENT-130` (l'accueil charge état puis fiches) · `API-060` (l'état allégé) ·
  **aval** `ENT-137` (les signaux d'intention) · `composables-standard.md`.

## Contexte

**Une recherche s'ouvre sur trente profils et s'arrête là.** Le compteur en annonce 525, la pile en
montre trente, et arriver au bout ne charge rien : `ENT-130` a séparé l'état des fiches sans que
personne ne reprenne le fil du chargement. Observé au navigateur : 33 cartes affichées, compteur
« 1 / 93 ».

Les explications ont disparu du même coup — elles ne voyagent plus avec l'état, et aucun écran ne les
demande. Une fiche s'ouvre donc sans son « pourquoi ce profil ».

## Périmètre

- **Un chargement de fond continu**, actif quel que soit l'écran, qui remplit les recherches
  **une par une et entièrement**.
- **Par paliers de cent profils**, un appel en vol à la fois.
- **La pile de travail d'abord, les profils écartés ensuite**, pour chaque recherche.
- **Fiches et explications ensemble** : la page du jeu les rapporte d'un coup.
- **L'ordre** : ce qui est affiché à l'écran, puis les recherches les plus récemment touchées.
- **Le routage passe devant** : ouvrir une recherche la place en tête de file.
- **Deux bornes**, au premier atteint : vingt recherches, ou 1 500 profils chargés.
- **Retirer les deux appels de l'ouverture** — `?scope=favorites` et `?scope=dismissed`.

## Hors périmètre

- **Le survol, le focus et l'entrée à l'écran** comme signaux de priorité : `ENT-137`.
- **La libération de la mémoire.** Le magasin commun ne se vide toujours pas, et c'est précisément
  ce qui rend le plafond nécessaire plutôt que confortable. Dette assumée, à mesurer plutôt qu'à
  corriger ici.
- **Toute jauge ou mention à l'écran** : le fond est muet, cf. *Décisions de cadrage*.
- **La Table**, qui ne change pas : elle lit le même magasin, ses lignes apparaissent à mesure.

## Décisions de cadrage

### Tout charger, et non « juste devant »

La première intention était d'anticiper le bout de la pile. Elle a été écartée au profit du
remplissage complet : le recruteur qui ouvre une recherche va la parcourir en entier, et un
chargement qui court derrière lui reste un chargement qu'on attend.

### Fiches et explications par le même appel

Puisqu'on charge tout de toute façon, séparer les deux ne sert plus à doser — ça double le nombre
d'appels. La page du jeu rapporte les deux, et aucune fiche ne s'affiche sans son « pourquoi ».

**Conséquence assumée, et elle porte sur du travail livré la veille** : la route d'explications par
page (`API-062`) n'a **aucun consommateur** par ce chemin. Elle reste au contrat, prête pour un usage
qui la dosera séparément ; si aucun ne vient, c'est une dette à retirer et il faudra le décider.

### Un seul appel en vol

Le palier suivant ne part que quand le précédent est arrivé. Ce n'est pas de la prudence : c'est ce
qui garantit qu'un geste du recruteur — dévoiler un profil, ouvrir une recherche — ne passe jamais
derrière une file de préchargement.

### Cent par palier

Cinq fois moins d'allers-retours qu'avec trente, pour des réponses d'environ 180 kilo-octets. Le
serveur plafonne à deux cents ; s'en approcher ferait arriver la première moitié de la pile plus
tard, pour un gain qui ne se voit pas.

### Rien n'est bloqué, jamais

Une recherche non encore chargée reste **cliquable**, et son écran affiche l'attente qu'il affiche
déjà. Une carte inerte aurait figé quarante-neuf des cinquante-cinq cartes de l'écran de collection
pendant près d'une minute, pour un mécanisme que le recruteur n'a pas demandé.

Le fond n'est donc **pas une garantie mais une accélération** — c'est ce qui permet de le laisser
échouer en silence.

### Deux bornes, et elles ne se rechargent pas

Vingt recherches OU 1 500 profils, au premier atteint. Une seule des deux ne borne rien : vingt
recherches pèsent de 0,3 à 8 mégaoctets selon leur taille, et un budget en profils seul serait
consommé entièrement par une recherche énorme.

Le budget ne se recharge **pas** à chaque ouverture : il ne bornerait alors plus rien sur une longue
session. Une recherche ouverte au-delà du plafond se charge entièrement, comme avant.

### LE PLAFOND BORNE UN POIDS, PAS UN RANG — corrigé à l'implémentation

Première version : la recherche qui « franchissait » le seuil était prise en entier, pour ne pas
laisser un jeu à moitié chargé. Mesuré au navigateur, elle chargeait **4 172 profils et 7,92 Mo** là
où le plafond en annonçait 1 500 — un plafond qui ne plafonne pas.

Une recherche qui ne tient pas dans ce qui reste est donc **sautée**, et l'examen continue : les
suivantes, plus petites, y tiennent. S'arrêter net sur elle aurait laissé le budget inemployé et
privé de préchargement des recherches qui n'y étaient pour rien.

### Une page tentée ne se retente jamais

Une page peut revenir sans tout ce qu'on en attendait : un profil supprimé entre l'état et l'appel,
ou un profil qu'aucun critère ne sait énoncer — le serveur n'a alors rien à dire de lui,
légitimement. Elle reste incomplète, donc éligible, et le fond la redemanderait **sans fin**. La
page est écartée plutôt que la boucle rompue : les recherches suivantes doivent se charger quand
même.

### Muet, et silencieux jusque dans l'échec

Rien ne s'affiche : les compteurs viennent de l'état et sont déjà justes, la pile s'allonge toute
seule. Un palier qui échoue arrête le fond sans message — le recruteur n'a pas demandé ce
chargement, lui montrer son échec parlerait d'un rouage. Rouvrir la recherche relance le
remplissage.

## Règles et cas limites

- **Une recherche est « chargée » quand chacun des identifiants de son jeu a sa fiche dans le
  magasin commun**, écartés compris. Être dans la liste des recherches ne suffit pas : la liste
  porte le nom et les compteurs, le jeu porte des identifiants, les fiches vivent ailleurs.
- **Le magasin commun est partagé** : un profil qui sert quatre recherches n'y existe qu'une fois,
  et charger une recherche avance les autres. La planification doit compter ce qui **manque**, pas
  ce qui appartient.
- **Une recherche déjà complète est sautée sans appel.**
- **Une recherche au jeu vide ne déclenche rien.**
- **Les recherches closes ne sont pas préchargées** : elles ne se travaillent plus.
- **Rendu serveur** : le fond est un geste d'après-affichage, jamais dans la page servie.
- **Changer d'écran ne l'arrête pas** — le magasin est global, ce qui est arrivé resservira.
- **Les explications appartiennent au couple recherche-profil** et se rangent dans le jeu de la
  recherche, jamais sur la fiche : le magasin est commun, une fiche qui les porterait prêterait
  celles de la dernière recherche ouverte à toutes les autres.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/composables-standard.md` — le chapitre « qui charge quoi, et
  quand » décrit le préchargement d'`ENT-130` ; il devient incomplet.

## Vérification

Parcours rejoué au navigateur sur la pile du worktree entreprise, compte `owner@demo` : **huit
recherches ouvertes, 4 172 appartenances**, dont une seule de 3 239 profils.

**Le fond à l'accueil :**

```
appels /api : 15  ·  dont paliers de fond : 13
poids total : 2,39 Mo  ·  dernier appel à +30 s
APPELS SIMULTANÉS AU PLUS : 1

+15,0 s  405 044 o  /api/searches/workspace
+16,2 s  230 273 o  /api/searches/profiles?ids=…
+16,5 s    3 871 o  /api/searches/019ffb0c…/results?page=1&perPage=100
   …
+29,9 s   63 498 o  /api/searches/019ffb27…/results?page=1&perPage=100
```

Le plafond écarte bien la recherche de 3 239 profils. **Avant la correction du plafond**, la même
mesure donnait **46 paliers et 7,92 Mo**.

**Ouvrir une recherche remplie**, atteinte par un clic — navigation cliente, donc les appels sont
observables :

```
── OUVERTURE d'une recherche PRÉCHARGÉE
   appels : 0 (AUCUN)
   à l'écran : {"cartes":2,"compteur":"1 / 2","criteres":true}
```

Aucun appel : ni le jeu, ni les deux périmètres qui partaient à chaque ouverture. Les explications
sont présentes.

**Ouvrir la recherche écartée du plafond** — elle échappe aux bornes et se remplit entièrement :

```
paliers reçus : 29 — de 2 à 30, sans trou
(la page 1 vient du rendu serveur, invisible au navigateur)
durée moyenne d'un palier : 19,1 s
à l'écran : « 3 000 / 3 239 » · explications présentes
```

Le dénominateur ne bouge pas pendant le remplissage. La mesure s'arrête à la page 30 sur 33 parce
que le chronomètre du script expirait, pas le chargement.

**Le seul chevauchement observé** est celui qu'on veut :

```
+47 s  …/results?page=3  ||  …/reviews
```

Un palier de fond et un **geste du recruteur** en même temps — le geste n'a pas attendu derrière la
file.

Tests : `bun run vitest run services/search/preload.vitest.ts` → **31 tests verts**.

## Livré

- `webapp/services/search/preload.ts` **(+)** — la couche pure : l'ordre, les deux plafonds, les
  pages manquantes, le prochain pas.
- `webapp/services/search/preload.vitest.ts` **(+)** — 31 cas, dont le plafond qui ne plafonnait pas
  et la page qu'on ne retente pas.
- `webapp/spaces/entreprise/app/bootstrap/loaders.ts` — le moteur `loadPreloadQueue`, l'arrière-plan
  d'un écran de recherche, et les deux appels d'ouverture retirés.
- `webapp/spaces/entreprise/app/bootstrap/registry.ts` — l'écran d'une recherche prend le fond.
- `webapp/spaces/entreprise/app/stores/searches.ts` — `mergeMatches` : une page de plus sans toucher
  à l'état, que l'API ne sert plus au-delà de la première.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/composables-standard.md` — le chapitre du remplissage.

## Reste à faire (hors périmètre)

- **Le temps de remplissage en production n'est pas mesuré.** Dix-neuf secondes par palier sur le
  serveur de développement — sans cache d'opcode et en mode debug — n'annoncent rien de la
  production, où ce sera bien plus rapide.
- **Le magasin commun ne se vide toujours pas** : les 1 500 fiches du plafond y restent jusqu'au
  rechargement de la page. C'est ce qui rend le plafond nécessaire plutôt que confortable.
- **La route d'explications par page (`API-062`) n'a aucun consommateur**, puisque fiches et
  explications voyagent par le même appel. La garder en réserve ou la retirer appartient à la lane
  serveur.
- **Le compte `entreprise@showcase-recruiter-webapp-saas.example` n'authentifie plus** sur cette pile — constaté en passant,
  sans rapport avec cette tâche.

## Définition de terminé

- [x] Le fond remplit les recherches une par une et entièrement, quel que soit l'écran.
      ⚠ **Cochée à tort ici** : seuls l'accueil et l'écran d'une recherche avaient été mesurés, et le
      registre ne déclarait pas l'écran de collection — une route absente reçoit un plan vide, donc
      rien ne s'y chargeait. Réparé par `ENT-137`, qui l'a découvert.
- [x] Un seul appel de préchargement est en vol à tout moment.
- [x] Chaque recherche charge sa pile par paliers de cent, puis ses profils écartés.
- [x] Les fiches arrivent avec leurs explications, et l'écran d'une recherche les affiche.
- [x] L'ordre suit ce qui est affiché, puis les plus récentes ; ouvrir une recherche la met en tête.
- [x] Le fond s'arrête à vingt recherches ou 1 500 profils, et ne repart pas.
- [x] Une recherche ouverte au-delà du plafond se charge entièrement.
- [x] Les deux appels `?scope=` de l'ouverture ont disparu, et retenus comme écartés s'affichent.
      Ils subsistent pour un geste qui change la donnée côté serveur — remettre un profil écarté —,
      où le jeu en mémoire ne décrit plus la vérité.
- [x] Rien ne se voit à l'écran, et un échec n'affiche aucun message.
- [x] Le poids transféré et le temps de remplissage sont mesurés et rapportés — en développement
      seulement, cf. *Reste à faire*.
- [x] Tests vitest sur la planification de la file.
- [x] `composables-standard.md` à jour.
