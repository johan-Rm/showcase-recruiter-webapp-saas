# TASK ENT-051 — entreprise : clôturer une recherche, et ranger celles qui le sont

- **Créée le** : 2026-08-05.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-05 — une recherche se clôture et se rouvre depuis sa carte, les
  closes descendent sous un pli replié, et l'accueil ne montre plus qu'elles sont actives.
  6 tests.
- **Type** : Frontend entreprise — un geste s'ajoute à l'écran, aucun contrat touché.
- **Tests** : unitaires (vitest) sur la logique pure — la partition entre recherches actives et
  closes, et le filtre de l'accueil qui s'applique **avant** le tri par date. Le rendu du pli et du
  bouton se prouve en capture : le README de `components/` interdit à un composant de porter de la
  donnée dérivée, donc tout ce qui se teste vit hors du rendu.
- **Réf** : **`API-028` — amont, mergée** (le serveur sait clôturer et rouvrir) · **`ENT-050` —
  amont, prérequis réel** (elle dessine la carte close) · **aval à cadrer** — renommer, dupliquer, partager,
  supprimer, et le menu « ⋯ » ; son numéro sera attribué à son cadrage, `ENT-052` ayant été pris
  entre-temps par une autre session ·
  `webapp/spaces/entreprise/app/pages/recherches/index.vue` ·
  `webapp/spaces/entreprise/app/pages/index.vue` ·
  [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)

## Contexte

Le serveur sait clôturer et rouvrir une recherche depuis `API-028` — **aucun écran ne le propose**.
La route d'ajustement porte déjà le champ, elle n'attend qu'un appel.

Et rien ne range les recherches terminées. C'est le point qui compte : **le bénéfice de la clôture
n'est pas l'état, c'est le rangement**. Une recherche déclarée finie qui reste au milieu des actives
encombre autant qu'avant, et le geste ne sert alors qu'à changer une teinte.

L'accueil aggrave la chose : il sert à **reprendre** un travail en cours — son propre code le dit —
et il montre les recherches les plus récemment touchées. Une recherche qu'on vient de clôturer y
serait donc la plus visible de toutes, à la place d'une recherche vivante.

## Périmètre

- **« Clôturer »** sur la carte d'une recherche active dont on est l'auteur, **« Rouvrir »** sur la
  carte close — action fine dans la ligne du bas, pas dans un menu.
- **L'écriture** : une action de store qui appelle la route d'ajustement existante avec l'état, et
  met la collection à jour.
- **Le relais serveur doit être élargi** — constaté en implémentant `ENT-050` :
  `server/api/searches/[id].patch.ts` **filtre** la charge utile et ne transmet que `name`,
  `criteria` et `isShared`. Un `isClosed` y est **jeté en silence**, et le bouton n'aurait aucun
  effet. La route répond pourtant `200`, ce qui rend le défaut invisible sans vérification.
- **Le pli des closes** sur « Mes recherches » : replié, annonçant leur nombre, absent s'il n'y en a
  aucune.
- **L'accueil ne montre plus que les actives.**
- **Le refus serveur** (`409`) porte son propre message et recharge la carte.
- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)** :
  la ligne des gestes d'une recherche gagne « clôturer ».

## Hors périmètre

- **Renommer, dupliquer, partager, supprimer** — et les **deux routes serveur intermédiaires** qui
  leur manquent (`DELETE` et la duplication) : c'est la tâche de **gestion**, encore à cadrer.
- **Le menu « ⋯ »** : cf. *Décisions de cadrage*.
- **Le nombre de recherches affichées sur l'accueil** — « deux rangées pleines » a été proposé,
  jamais tranché. Cette tâche change **qui** s'affiche, pas **combien**.
- **Toute clôture automatique ou suggérée** — « exportée depuis deux mois, la clôturer ? ».
- **Tout contrat, toute migration** : cette tâche consomme ce qui est déjà servi.

## Décisions de cadrage

**Le besoin se coupe en deux, par l'intention.** Cinq actions, deux routes serveur manquantes et un
pli, ça ne tient pas dans une carte : le commit serait trop large pour être relu et la preuve
couvrirait cinq gestes sans rapport. Le partage retenu suit ce que l'utilisateur **veut faire** :
ici le **cycle de vie** d'une recherche — la finir, la reprendre, la ranger —, et dans une seconde
tâche sa **gestion** — la renommer, la copier, la partager, l'effacer. Chaque carte est entière et se vérifie
seule.

**« Clôturer » est un bouton, pas une entrée de menu.** Cette tâche n'apporte qu'une action : la
mettre dans un « ⋯ » reviendrait à cacher un lien derrière un clic de plus — exactement ce qu'on
vient d'écarter pour la carte en lecture seule, qui ne porte qu'un « Dupliquer ». Le menu naîtra avec la tâche de
gestion, quand il aura cinq entrées à porter, et l'action y migrera. **Coût assumé** : l'action
change de place entre les deux tâches.

**Aucune confirmation avant de clôturer.** Le geste se défait d'un clic — rien ne se perd,
contrairement à supprimer. Une boîte sur un geste réversible apprend surtout à cliquer « oui » sans
lire, et **banaliserait celle de la suppression**, qui elle est définitive : deux boîtes qui se
ressemblent, et l'une des deux perd son poids. En revanche l'écran dit, **au moment du geste**, que
la recherche ne sera plus modifiable — la conséquence est annoncée avant, et défaisable après.

**Le pli est replié à l'arrivée.** Ouvert, il ne rangerait rien : la page redeviendrait aussi
chargée qu'avant et la clôture n'apporterait plus qu'une teinte. Replié, il tient en une ligne qui
annonce combien de recherches sont closes, et elles restent à un clic.

**L'accueil filtre les closes, côté écran.** Son code pose déjà que le tri des recherches y est
« une règle d'affichage de CET écran » et non une demande au serveur : le filtre suit le même
chemin. Aucun paramètre nouveau sur la collection, qui continue de tout servir.

**Le refus serveur a son propre message.** Le `409` arrive quand deux onglets sont ouverts, ou qu'on
a clôturé ailleurs entre-temps : l'écran croit la recherche modifiable, le serveur la sait close. Le
message générique laisserait l'écran continuer d'afficher une recherche active — et le clic suivant
échouerait pareil. On dit ce qui s'est passé **et on recharge la carte**, pour que l'écran cesse
immédiatement de mentir.

**Enregistré pour la tâche de gestion, décidé ici** : sur la recherche d'un collègue, le menu se réduirait à
« Dupliquer » — ce sera donc un **lien direct**, pas un « ⋯ » qu'il faut ouvrir pour y trouver une
seule ligne.

### Écart : le refus serveur est tendu pour un besoin qui n'est pas encore là

Le cadrage disait que le `409` arriverait « quand deux onglets sont ouverts ». **Mesuré, c'est
faux** : le seul appel que cet écran émet est `isClosed`, et le serveur y répond `200` dans tous les
cas — re-clôturer une recherche déjà close est idempotent, la rouvrir aussi. Vérifié appel par
appel :

| Ce que l'écran envoie | Réponse |
| --- | --- |
| `isClosed: true` sur une close | **200** |
| `isClosed: false` sur une close | **200** |
| `name` sur une close — ce que l'écran **n'envoie pas encore** | **409** |

Le traitement du refus est donc **écrit et inatteignable depuis cet écran**. Il le deviendra avec la
tâche de gestion, qui apportera le renommage. Gardé plutôt que retiré : le code est en place au
moment où le geste qui le déclenche arrivera, et le retirer pour le réécrire ensuite n'aurait aucun
sens.

### Écart : le lien de l'accueil compte ce qu'il n'affiche plus

« Voir mes N recherches » compte la collection **entière**, closes comprises, sous une liste qui les
exclut désormais : deux cartes affichées, « voir mes 3 recherches » en dessous. Le nombre est vrai,
mais il se lit comme un décalage. Sa condition d'apparition a aussi changé de sens — elle voulait
dire « il y en a de plus anciennes », elle peut maintenant dire « il y en a des closes ».

**Laissé tel quel** : c'est une décision d'écran sur l'accueil, et cette tâche a délibérément laissé
ces décisions à la tâche qui traitera cet écran. Nommé pour qu'il ne devienne pas invisible.

## Règles et cas limites

- **« Clôturer » n'apparaît que sur ses propres recherches.** Le serveur refuse l'écriture aux
  autres ; l'écran ne propose pas ce qui serait refusé. Idem pour « Rouvrir ».
- **Une recherche close reste ouvrable** : on la parcourt, on relit sa sélection. C'est `ENT-050`
  qui dessine cette carte ; cette tâche n'y ajoute que le geste de réouverture.
- **Pas de close, pas de pli** : la ligne ne s'affiche pas du tout.
- **Le compte du pli s'accorde en nombre** par une clé i18n — « 1 recherche close » et non
  « 1 recherche(s) ».
- **Le pli se referme à chaque visite** — son état n'est pas mémorisé. **Hypothèse assumée** : le
  rangement reste effectif de toute façon, et retenir l'ouverture demanderait un stockage pour un
  confort mince.
- **Sur l'accueil, le filtre s'applique AVANT le tri** par date de modification. Dans l'autre ordre,
  une recherche close fraîchement touchée évincerait une active des places servies.
- **Toute chaîne passe par une clé i18n**, message de refus compris.
- **Le pli porte un chevron** qui pivote à l'ouverture : sans lui il se lit comme un simple filet
  de séparation, et rien n'indique qu'on peut l'ouvrir. Il ne bouge pas si le système demande
  moins d'animation.

## Documentation impactée

- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)** —
  la ligne « créer · reprendre · dupliquer · partager · supprimer » ignore la clôture. Le fichier
  n'est plus contendu : la session qui le modifiait a mergé.

Aucun `README.md` ne change de convention. Aucun autre document n'est rendu faux par ce diff.

## Définition de terminé

- [x] L'auteur clôture sa recherche depuis sa carte, et la carte s'affiche close aussitôt.
- [x] Il la rouvre depuis la carte close, et l'édition redevient possible.
- [x] Le geste n'est proposé sur aucune recherche dont on n'est pas l'auteur.
- [x] La conséquence — « plus modifiable » — est lisible au moment du geste, pas après.
- [x] Les recherches closes sont rangées sous un pli replié qui annonce leur nombre.
- [x] Le pli disparaît quand il n'y a aucune recherche close.
- [x] L'accueil ne montre plus aucune recherche close.
- [x] Un refus du serveur affiche un message qui dit ce qui s'est passé, et la carte se met à jour.
- [x] La logique de partition et de filtrage est couverte par des tests unitaires.
- [x] Aucun texte en dur : toutes les chaînes passent par une clé i18n.
- [x] L'arborescence de l'espace entreprise mentionne la clôture.

## Vérification

**Le geste, à travers le relais élargi** — c'est ce qui bloquait :

```
PATCH /api/searches/{id}  { "isClosed": true }   →  closedAt = 2026-08-05T18:39:25+00:00
```

Avant cette tâche, la même requête répondait `200` et **ne changeait rien**.

**Ce que l'écran peut réellement provoquer**, vérifié appel par appel :

| Envoyé | Réponse |
| --- | --- |
| `isClosed: true` sur une close | **200** — l'état s'affirme |
| `isClosed: false` | **200** |
| `name` sur une close | **409** — mais l'écran ne l'envoie pas encore |

**Captures authentifiées de « Mes recherches » et de l'accueil** :

| Écran | Ce qui a été observé |
| --- | --- |
| Collection, pli **replié** | deux actives, puis « › 1 recherche close » ; la carte close **n'est pas visible** |
| Collection, pli **déplié** | la close apparaît : rail éteint, **« Rouvrir »**, aucun aplat accent |
| Accueil | **deux** cartes là où la collection en compte trois — la close en est absente |

Le singulier est tenu : « 1 recherche close », pas « 1 recherche(s) ».

**Un défaut corrigé pendant la preuve** : le pli n'avait **aucune affordance** — sans chevron il se
lisait comme un simple filet, et rien n'indiquait qu'on pouvait l'ouvrir.

**`collection.vitest.ts` : 6 tests, vert**, dont celui qui fige l'ordre des opérations. Les **146
tests** de `services/search/` passent, les **13 règles de convention** ne relèvent rien, le
type-check est vert sur les quatre espaces, et le contrôle de format aussi.

## Livré

- `webapp/services/search/collection.ts` **(+)** — la partition actives / closes et le filtre de
  l'accueil, qui **filtre avant de trier**.
- `webapp/services/search/collection.vitest.ts` **(+)** — 6 tests.
- `webapp/base/server/api/searches/[id].patch.ts` **(M)** — le relais transmet enfin l'état, et son
  commentaire dit désormais que ce filtrage jette en silence.
- `webapp/spaces/entreprise/app/composables/useSearches.ts` **(M)** — `setClosed`, qui rend une clé
  d'écran plutôt qu'un code HTTP, et recharge la collection sur un refus.
- `webapp/spaces/entreprise/app/components/search/Card.vue` **(M)** — le bouton, **en événement** :
  le composant annonce l'intention, la page l'exécute.
- `webapp/spaces/entreprise/app/pages/recherches/index.vue` **(M)** — le pli des closes, avec son
  chevron.
- `webapp/spaces/entreprise/app/pages/index.vue` **(M)** — les actives seulement.
- `webapp/base/i18n/locales/fr.yaml` **(M)** — le geste, sa conséquence, ses refus, le pli.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` **(M)** — la clôture et son pli.

## Reste à faire (hors périmètre)

- **Le lien « Voir mes N recherches » de l'accueil** compte les closes sous une liste qui les
  exclut. Décision d'écran, laissée à la tâche qui traitera l'accueil.
- **Le traitement du `409`** attend le renommage pour devenir atteignable — cf. *Écart*.
- **La tâche de gestion** — renommer, dupliquer, partager, supprimer, et le menu « ⋯ » — reste à
  cadrer, avec les deux routes serveur qui lui manquent.
