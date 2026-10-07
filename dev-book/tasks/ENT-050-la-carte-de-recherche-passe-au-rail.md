# TASK ENT-050 — entreprise : la carte de recherche passe au rail

- **Créée le** : 2026-08-05.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-05 — la carte porte un rail qui dit ce que la recherche contient,
  ce qu'elle a produit et où en est son lecteur ; une close s'y affiche éteinte. 10 tests.
- **Type** : Frontend entreprise — refonte d'un composant d'affichage, aucun contrat touché.
- **Tests** : unitaires (vitest) sur le service qui calcule le contenu du rail — la nouveauté
  annoncée seulement quand elle existe, l'avancement borné à 100 %, l'absence de division par zéro,
  la recherche d'un collègue amputée de son avancement, l'état de clôture lu même quand la clé est
  absente, et le filtrage des critères déjà dits par le nom. Le rendu, lui, se prouve en capture : le
  README de `components/` interdit à un composant de porter de la donnée dérivée, donc tout ce qui se
  teste vit dans le service.
- **Réf** : **`API-027` et `API-028` — amont, mergées** · **`ENT-051` — aval** (menu d'actions,
  bouton de clôture, pli des closes) · `webapp/spaces/entreprise/app/components/search/Card.vue` ·
  `webapp/shared/types/search.ts` · `webapp/services/search/` ·
  [components/README.md](../../webapp/base/app/components/README.md) ·
  [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md)

## Contexte

Le serveur sert désormais ce qu'une recherche a **produit** et où en est son **lecteur** :
`seenCount`, `favoriteCount` et `closedAt` sont arrivés avec `API-027` et `API-028`. La carte n'en
montre rien.

Conséquence à l'écran : douze recherches se présentent comme douze objets identiques — un titre, des
critères, un volume. Rien ne distingue celle qu'on a dépouillée jusqu'au bout de celle qu'on n'a
jamais ouverte, ni celle qui a donné huit profils de celle qui n'a rien donné. Le compteur de
nouveautés lui-même, calculé par l'API depuis des mois, n'est **affiché nulle part**.

La carte porte en outre un défaut visible : le nom d'une recherche est déjà le résumé de ses
critères, et les étiquettes en dessous les répètent mot pour mot.

Le dessin a été arbitré sur maquette — `ui-ux-design/draft/carte-recherche-rail-v2.html`, hors dépôt
puisque `draft/` est ignoré.

## Périmètre

- **`components/search/Card.vue` réécrit** : structure plate sans bordure ni ombre, rayon **8 px**
  (le défaut de la charte), **rail en pleine hauteur** à gauche, corps à droite, ligne d'actions
  resserrée. Étoile pour « Ma sélection », pile pour « Parcourir les profils ». Nouveauté en **vert
  signature**. Survol à 5 % avec un pixel de montée. Titre porteur du lien, calque étendant le clic
  à toute la carte.
- **L'état close est dessiné** : rail éteint, plus d'aplat accent, ligne de bilan, actions réduites.
- **`shared/types/search.ts`** gagne `seenCount`, `favoriteCount` et `closedAt` — le type est écrit
  à la main, en miroir de la réponse de l'API.
- **Un service `services/search/card.ts`** calcule ce que le rail montre, avec ses tests.
- **Deux lignes de commentaire** dans `pages/index.vue` : cf. *Décisions de cadrage*.

## Hors périmètre

- **Le menu « ⋯ », le bouton de clôture et le pli qui range les closes** — c'est `ENT-051`.
- **Le nombre de recherches affichées sur l'accueil** et l'équilibre de ses aplats accent : ce sont
  des décisions d'écran, pas de composant, et elles ne sont pas tranchées.
- **Le renommage de « Favoris » sur les autres écrans** — cf. *Décisions de cadrage*.
- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)**,
  qui énumère les gestes d'une recherche sans la clôture : elle part avec `ENT-051`, qui apporte le
  geste.
- **Toute modification du contrat** : cette tâche ne fait que consommer ce qui est déjà servi.

## Décisions de cadrage

**La carte seule, pas les écrans qui la portent.** Le composant est le même sur « Mes recherches » et
sur l'accueil : le refaire change les deux, mécaniquement. Mais combien de recherches l'accueil
montre, et comment ses deux boutons de moteur restent dominants face à des cartes qui portent
chacune un aplat, sont des décisions **d'écran**. Les traiter ici mettrait deux sujets dans un
commit, et elles ne bloquent pas le dessin arrêté.

**`UCard` est abandonné, et l'exception se démontre.** Le README de `components/` impose de partir de
Nuxt UI et exige que l'exception nomme ce que la structure a d'impossible. Elle l'est ici :
`UCard` **empile** en-tête, corps et pied les uns sous les autres, alors que le rail est une
**colonne à côté** du corps. Aucune valeur de `:ui` ne transforme un empilement en deux colonnes —
c'est le même motif que `UiTagPicker`, dont les chips ne sortaient pas du champ.

**Ce que le rail calcule vit dans un service, pas dans le composant.** Le rail décide s'il y a une
nouveauté à annoncer, rapporte l'avancement au total et lit l'état de clôture. C'est
de la **donnée dérivée**, que le README interdit explicitement à un composant de construire. Et ce
dépôt teste ses services (`services/search/` en compte quatre couverts) sans tester ses composants :
c'est donc le seul endroit où cette tâche peut être couverte. Un composable serait un faux ami — il
n'y a aucune réactivité en jeu, seulement une fonction pure sur un objet reçu en props.

**`ENT-050` dessine l'état close, `ENT-051` apporte le geste.** L'allure d'une recherche terminée
fait partie du dessin de la carte, et le serveur sert déjà `closedAt`. Repousser l'affichage à
`ENT-051` ferait qu'entre les deux tâches, une recherche close **mentirait sur son état**.

**Le rail dit « retenus », pas « favoris ».** Le mot dit le **geste** du recruteur — il a retenu ce
profil — là où « favori » dit un goût. C'est un arbitrage de vocabulaire produit, assumé ici :
l'étoile de la Table et le panneau de la Visionneuse continueront de dire « Favoris » jusqu'à ce
qu'on les touche. **Deux mots coexisteront donc temporairement**, et c'est le prix de ne pas ouvrir
un chantier de renommage transverse dans une tâche d'affichage.

**Toute la carte est cliquable, mais le lien est porté par le titre.** Un `::after` sur le lien du
titre étend la zone de clic au rectangle entier. Faire de la carte elle-même un lien la ferait
réciter tout son contenu à voix haute par un lecteur d'écran. **Contrainte léguée à `ENT-051`** : son
menu devra passer au-dessus de ce calque et arrêter la propagation du clic.

**Zéro profil s'affiche tel quel.** Une recherche dont les critères ne ramènent rien est un
**résultat**, pas une anomalie : le rail affiche `0`, la barre reste vide, aucun dessin particulier.
Le cas « aucun critère » n'existe pas — une recherche sans critère est une recherche abandonnée, elle
ne figure pas dans la collection.

**Le commentaire périmé de l'accueil est corrigé.** `pages/index.vue` affirme « un seul aplat accent
par écran (guide de marque) » — or la charte ne dit **nulle part** cela, et cette tâche fait porter un
aplat à chaque carte. Laisser l'affirmation garantirait qu'une prochaine session relise la règle
périmée et refasse le débat. Deux lignes de commentaire, aucun changement de comportement.

### Écart : rien ne « prime » dans le rail

Le cadrage annonçait que la nouveauté **primerait** sur les retenus — un seul des deux s'afficherait.
Le dessin final n'en a pas besoin : le rail les porte à deux endroits différents, la nouveauté sous
le compte et les retenus dans la ligne du bas, avec l'avancement. Il n'y a donc aucun arbitrage à
faire entre eux, et la ligne `**Tests**` a été corrigée en conséquence.

## Règles et cas limites

- **Recherche d'un collègue** — ni « vus » ni barre d'avancement : l'avancement est **personnel**, et
  le poser à côté de retenus qui ne le sont pas ferait lire deux sujets dans une seule ligne. La
  carte porte « partagée par X · lecture seule » et une seule action permise.
- **Recherche close** — n'annonce jamais de nouveauté : le serveur l'éteint déjà, l'écran n'a rien à
  filtrer.
- **Le rail montre la nouveauté quand il y en a**, sinon les retenus. Un rail dont l'élément
  dominant disparaît la moitié du temps perdrait son rôle d'ancre alignée, d'où le nombre de profils
  toujours présent en tête.
- **Chiffres à chasse fixe** (`font-variant-numeric: tabular-nums`) et **rail à largeur fixe** :
  sinon « 7 » et « 1 248 » tordent la colonne d'une carte à l'autre, et c'est cet alignement qui fait
  tout l'intérêt du rail.
- **En étroit**, le rail bascule en **bandeau horizontal** : une colonne verticale mangerait la
  moitié de la largeur d'un téléphone.
- **Survol** — 5 % de teinte et un pixel de montée, **supprimés** quand le système demande moins de
  mouvement. Les 8 % du kit sont calibrés pour de petits objets ; sur une carte entière, la même
  valeur devient un aplat.
- **Focus clavier** — l'anneau se pose sur la carte, pas sur le titre seul, sinon il souligne un mot
  au milieu d'un rectangle cliquable.
- **Toute chaîne passe par une clé i18n**, y compris les unités du rail et le pluriel des comptes.

## Documentation impactée

Aucune. Aucune page de `docs/showcase-recruiter-webapp-saas/` n'est rendue fausse par ce diff, et **aucun `README.md` ne
change de convention** : l'exception à « un écran neuf part de Nuxt UI » se **démontre** dans cette
carte, comme le README l'exige, elle ne modifie pas la règle.

Le référencement est sans objet — écran authentifié. Aucune migration, aucun contrat touché.

## Définition de terminé

- [x] La carte n'a ni bordure, ni ombre, ni séparateur, et son rayon est celui de la charte.
- [x] Le rail occupe toute la hauteur et aligne : nombre de profils, nouveauté, avancement, retenus.
- [x] Les rails de plusieurs cartes forment une colonne alignée, quels que soient les nombres.
- [x] Une recherche close s'affiche éteinte, sans aplat accent, avec sa ligne de bilan.
- [x] Sur la recherche d'un collègue, l'avancement et la barre sont absents ; les retenus restent.
- [x] Un clic n'importe où sur la carte ouvre la recherche, et un lecteur d'écran annonce son nom.
- [x] Au clavier, la carte prend un focus visible ; le mouvement disparaît si le système le demande.
- [x] En étroit, le rail devient un bandeau et les actions s'empilent.
- [x] Le service du rail est couvert par des tests unitaires ; aucune donnée dérivée ne reste dans
      le composant.
- [x] Aucun texte en dur : toutes les chaînes passent par une clé i18n.
- [x] Le commentaire de `pages/index.vue` n'affirme plus une règle que l'écran contredit.

## Vérification

**Capture authentifiée de `/recherches`, dans les DEUX thèmes**, sur la stack de la lane — quatre
recherches d'un même compte :

| Carte | Ce qui a été observé |
| --- | --- |
| 218 profils | « • 218 nouveaux » en vert signature, aplat accent sur l'action |
| 82 profils, **close** | rail éteint, titre grisé, « close », « close le 24/07/2026 par Omar Alaoui », **aucun aplat** |
| 1 profil | singulier tenu — « 1 PROFIL », « 1 vu · 2 retenus » |
| 1 profil, rien retenu | « 0 vus · 0 retenus » |

**Les rails s'alignent en colonne** d'une carte à l'autre — c'était l'enjeu du dessin, et il tient.

**Le filtrage des critères se voit à l'œil** : la recherche au nom AUTOMATIQUE n'affiche aucune
étiquette (tout est déjà dans son titre), celle nommée à la main les affiche toutes.

**Un défaut corrigé pendant la preuve** : la première capture montrait une carte close portant
encore son aplat vert. Aucune gate ne l'aurait vu — c'est exactement pourquoi le verdict est humain.

**`card.vitest.ts` : 10 tests, vert.** Les **140 tests** de `services/search/` passent, fixture
d'`API-027` comprise. Les **13 règles de convention CI** ne relèvent aucune violation. Type-check
vert sur les quatre espaces.

## Livré

- `webapp/services/search/card.ts` **(+)** — le rail (compte, nouveauté, avancement, retenus, état)
  et le filtrage des critères déjà dits par le nom. Couche pure, sans dépendance framework.
- `webapp/services/search/card.vitest.ts` **(+)** — 10 tests.
- `webapp/spaces/entreprise/app/components/search/Card.vue` **(M)** — la carte réécrite **sans
  `UCard`**, exception nommée dans le fichier.
- `webapp/shared/types/search.ts` **(M)** — les trois champs servis par l'API, `closedAt` optionnel
  parce qu'une valeur nulle n'est pas sérialisée.
- `webapp/base/i18n/locales/fr.yaml` **(M)** — les clés du rail, avec le pluriel ; `results`
  disparaît, le rail le remplace.
- `webapp/services/search/normalize.vitest.ts` **(M)** — fixture complétée par le type élargi.
- `webapp/spaces/entreprise/app/pages/index.vue` **(M)** — le commentaire qui affirmait une règle
  absente de la charte.

## Reste à faire (hors périmètre)

- **Le relais serveur d'ajustement jette `isClosed` en silence** — `server/api/searches/[id].patch.ts`
  ne transmet que `name`, `criteria` et `isShared`, et répond quand même `200`. Constaté ici,
  consigné dans le périmètre de la tâche de clôture.
- **Le corps de la carte reste très vide** sur une recherche au nom automatique : le rail ne prend
  que 104 px d'une carte qui en fait 620. Nommé à la relecture, laissé tel quel.
- **« close » apparaît deux fois** sur une carte close — dans le rail et dans la ligne de bilan.
  C'était le dessin validé ; à revoir si ça gêne à l'usage.
- **L'équilibre des aplats sur l'accueil** n'est toujours pas tranché : il en porte désormais
  plusieurs.
