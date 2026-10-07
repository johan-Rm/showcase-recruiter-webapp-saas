# TASK ENT-137 — le survol annonce ce qu'on va ouvrir

- **Créée le** : 2026-08-18.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-18 — survoler, focaliser ou faire entrer une carte à l'écran met sa
  recherche en tête du chargement de fond, sans lancer d'appel propre.
- **Type** : Frontend espace entreprise — trois signaux d'intention qui réordonnent une file.
- **Tests** : vitest sur la couche pure — la file réordonnée par une intention : la recherche
  désignée passe en tête, celles déjà chargées ne bougent rien, une intention répétée n'empile pas.
  Le délai d'intention et l'écoute des évènements se prouvent au navigateur.
- **Réf** : **amont bloquant** `ENT-136` (le moteur de file) · `showcase-recruiter-webapp-saas-vueuse-over-dom`.

## Contexte

**Le chargement de fond suit un ordre fixe, et cet ordre ignore ce que le recruteur s'apprête à
faire.** Sur l'écran de collection, cinquante-cinq cartes sont en file : celle qu'on vise peut être
la dernière, et cliquer dessus fait attendre alors que le geste était annonçable une demi-seconde
plus tôt.

Le survol, le focus au clavier et l'entrée d'une carte à l'écran disent tous la même chose — « c'est
celle-là qui va être ouverte ». Aucun n'est écouté aujourd'hui.

## Périmètre

- **Le survol d'une carte de recherche**, maintenu ~120 ms, fait passer sa recherche en tête de file.
- **Le focus clavier** sur la même carte fait exactement la même chose.
- **L'entrée d'une carte dans l'écran**, sur un écran tactile, fait la même chose.
- Ces trois signaux **réordonnent la file, et rien d'autre** : ils ne lancent aucun appel qui leur
  soit propre.

## Hors périmètre

- **Le moteur de chargement lui-même** — la file, les paliers, les bornes : `ENT-136`.
- **Toute autre surface que la carte de recherche.** La fiche d'un profil, les onglets, les liens de
  navigation ne portent aucun signal.
- **Un survol qui préchargerait une page entière** au sens du routeur : on ne parle que de l'ordre
  d'une file déjà en marche.

## Décisions de cadrage

### En plus du moteur, jamais à sa place

Ces signaux **réordonnent** la file ; ils ne créent pas de chemin de chargement parallèle. La règle
du **seul appel en vol** posée par `ENT-136` ne bouge pas : le palier en cours finit, puis la
recherche désignée passe avant toutes les autres.

Un appel immédiat en plus du fond aurait été plus réactif, et aurait remis en cause la seule
protection qui empêche le préchargement de passer devant un geste du recruteur.

### Cent vingt millisecondes, et pas zéro

Sans délai, traverser une grille de six cartes réordonne la file six fois — et c'est la dernière
carte traversée qui gagne, jamais celle qu'on visait. 120 ms suffisent à écarter un balayage tout en
restant bien en deçà des ~300 ms qu'il faut pour viser et cliquer : l'intention est acquise avant le
clic. Le délai s'annule si le pointeur repart.

### Le clavier et le doigt ont le même bénéfice

Le focus coûte le même code que le survol ; s'en passer réserverait le gain à ceux qui ont une
souris, dont sont exclues les personnes qui naviguent au lecteur d'écran.

Au doigt, il n'y a pas de survol : c'est l'entrée de la carte dans l'écran qui en tient lieu. Sur
téléphone l'accueil n'affiche que deux cartes à la fois, le signal suit donc naturellement le
défilement.

### L'écran de collection ne chargeait RIEN — corrigé ici, et c'est un manquement d'`ENT-136`

Découvert en voulant prouver le survol : sur `/recherches`, aucune recherche ne se chargeait. Le
registre ne déclarait que trois routes — l'accueil, la Visionneuse, la Table — et une route absente
reçoit un plan vide. Ni état de travail, ni remplissage.

C'est l'écran où le survol sert le plus, et la tâche ne pouvait pas s'y prouver. La route y est donc
ajoutée **dans cette tâche** : `ENT-136` est déjà commitée, et sa case « actif quel que soit l'écran »
avait été cochée après n'avoir mesuré que l'accueil et l'écran d'une recherche. Le commit mélange
donc deux périmètres, assumé et arbitré avec l'humain plutôt que découvert au commit.

### Les évènements passent par VueUse

`useEventListener`, `useTimeoutFn` et l'observateur d'intersection plutôt que les API brutes :
SSR-safe et nettoyés tout seuls. C'est une règle du dépôt, verrouillée en intégration continue.

## Règles et cas limites

- **Une recherche déjà chargée ne réordonne rien** : la survoler est sans effet, et sans appel.
- **Une intention répétée n'empile pas** : survoler deux fois la même carte laisse la file inchangée
  la seconde fois.
- **Le survol pendant qu'un palier est en vol attend sa fin** — il change l'ordre, pas le rythme.
- **Le plafond de `ENT-136` s'applique quand même** : une intention ne rouvre pas un budget épuisé,
  elle réordonne ce qui reste à charger. Cliquer, lui, charge la recherche entière comme toujours.
- **Rien ne se voit** : la carte ne change pas d'apparence au survol au-delà de ce qu'elle fait déjà.
- **Accessibilité** : le focus ne déclenche aucun mouvement ni annonce — il réordonne une file
  invisible, un lecteur d'écran n'a rien à entendre.
- **Mouvement réduit** : sans objet, aucun mouvement n'est introduit.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : le chapitre « qui charge quoi, et quand » est mis à jour par
`ENT-136`, et ces trois signaux n'y ajoutent pas de couche — ils désignent un rang dans une file
qu'il décrit déjà.

## Vérification

Parcours rejoué au navigateur sur l'écran de collection, compte `owner@demo`, huit recherches — la
seule surface où la file entière se voit.

**L'état des recherches pendant que le fond travaille**, pour situer ce qui suit :

```
019ffff9  jeu=   80  manquants=   48
019ffb99  jeu=   41  manquants=   11
019ffb27  jeu=   96  manquants=   66
019ffc55  jeu=  641  manquants=  599
019ffb27  jeu= 3239  manquants= 3138   ← écartée par le plafond
```

**Un balayage ne désigne personne** — sept cartes traversées sans s'arrêter :

```
désignées : []      appels déclenchés : 0
```

**Un survol maintenu met en tête, sans appel propre :**

```
désignées : ["019ffb27…"]
appels déclenchés par le survol : 0
```

**Le fond suit.** Les trois paliers qui suivent portent sur la visée avant de repartir ailleurs — le
premier palier après un survol ne prouve rien, il était déjà décidé :

```
019ffb27 → 019ffb27 → 019ffb99      la visée est servie : true
```

**Le focus clavier fait la même chose**, et la précédente désignée garde son rang :

```
focus sur 019ffff9   avant : ["019ffb27"]
                     après : ["019ffff9", "019ffb27"]
```

**Un survol répété n'empile pas** : la liste reste sans doublon. **Zéro chevauchement** d'appels sur
toute la session.

Tests : `bun run vitest run services/search/preload.vitest.ts` → **38 tests verts** (31 d'`ENT-136`,
7 ajoutés ici).

## Livré

- `webapp/services/search/preload.ts` — `withIntent` : la file réordonnée, dédoublonnée.
- `webapp/services/search/preload.vitest.ts` — sept cas de plus, dont le plafond qu'une intention ne
  rouvre pas.
- `webapp/spaces/entreprise/app/composables/useSearchIntent.ts` **(+)** — recueille l'intention et
  refuse celle d'une recherche déjà chargée.
- `webapp/spaces/entreprise/app/components/search/Card.vue` — les trois signaux, annoncés par un
  évènement : la carte reste passive.
- `webapp/spaces/entreprise/app/pages/index.vue`, `pages/recherches/index.vue` — les quatre poses de
  carte relaient l'annonce.
- `webapp/spaces/entreprise/app/bootstrap/loaders.ts` — le fond consulte les désignées avant les
  affichées.
- `webapp/spaces/entreprise/app/bootstrap/registry.ts` — **l'écran de collection entre au registre**,
  cf. *Décisions de cadrage*.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/composables-standard.md` — les signaux, et l'avertissement
  sur les routes absentes du registre.

## Reste à faire (hors périmètre)

- **Le signal tactile n'est pas éprouvé sur un vrai appareil.** L'entrée d'une carte à l'écran est
  câblée et réservée aux écrans sans survol ; seul son code a été vérifié.
- **Le survol n'a aucun effet sur une recherche hors plafond** — c'est la règle voulue, mais elle
  mérite d'être connue : ces recherches ne se chargent qu'en les ouvrant.

## Définition de terminé

- [x] Survoler une carte de recherche ~120 ms met sa recherche en tête de file.
- [x] Un balayage rapide sur plusieurs cartes ne réordonne rien.
- [x] Le focus clavier produit le même effet que le survol.
- [x] Sur écran tactile, une carte qui entre à l'écran produit le même effet — **câblé, pas éprouvé
      sur un appareil réel**, cf. *Reste à faire*.
- [x] Une recherche déjà chargée ne déclenche ni réordonnancement ni appel.
- [x] Aucun appel propre n'est lancé : un seul palier reste en vol à tout moment.
- [x] Le gain est mesuré au navigateur — **autrement que prévu** : le délai d'ouverture n'est pas
      comparable d'un essai à l'autre sur un serveur de développement où un palier prend une
      quinzaine de secondes. Ce qui est mesuré est le fait observable qui le produit : les trois
      paliers suivant le survol portent sur la recherche visée.
- [x] Tests vitest sur le réordonnancement de la file.
