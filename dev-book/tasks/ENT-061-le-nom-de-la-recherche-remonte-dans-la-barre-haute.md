# TASK ENT-061 — entreprise : le nom de la recherche remonte dans la barre haute

- **Créée le** : 2026-08-06.
- **Session** : `9a89d25e-8c4a-45f8-beda-24511fc94e99`.
- **Statut** : ✅ fait le 2026-08-06 — la barre haute est rétablie sur la Visionneuse, le nom de la
  recherche s'y lit en toutes lettres et les deux entrées vers les filtres l'accompagnent,
  désactivées. La colonne de gauche a perdu ses onglets et son entête.
- **Type** : Frontend entreprise — une bande d'écran rétablie, un titre et deux commandes qui
  changent de place. Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Rien ne se calcule ici : un titre déjà
  résolu change d'emplacement, deux onglets deviennent deux boutons inertes. Le webapp n'a aucun
  harnais de test de composant (`webapp/vitest.config.ts` ne couvre que le code pur, sans DOM), et
  jsdom ne mesure ni hauteur ni allure. La preuve attendue est une **capture de la page connectée**,
  avant et après, sur grand écran et en largeur téléphone.
- **Réf** : **`ENT-041` — amont** (elle a créé la console à trois onglets et supprimé la barre haute
  de cet écran, que celle-ci rétablit) · `ENT-047`, `ENT-049`, `ENT-057`, `ENT-058` (l'histoire de la
  colonne) · **aval : brancher les deux boutons**, carte à écrire ·
  `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` · `components/ActionBar.vue` ·
  `components/search/{Console,ControlPanel,ProgressHeader}.vue`

## Contexte

**Sur téléphone, on ne sait plus sur quelle recherche on travaille.** L'écran « Parcourir les
profils » n'écrit le nom de la recherche qu'à un seul endroit : en tête de la colonne de gauche,
sous l'intitulé « RECHERCHE EN COURS ». Or cette colonne est **masquée en dessous de 1024 pixels**
— c'est une décision de mise en page assumée, la fiche du candidat prenant tout le cadre. En
dessous de cette largeur, l'information n'existe donc nulle part : ni titre, ni rappel, ni retour
visible vers ce qu'on a demandé.

**Et sur grand écran, ce nom est mal logé.** Il partage une colonne de deux cent quatre-vingts
pixels avec trois compteurs, une barre d'avancement et la liste des critères confrontés au profil
affiché. Il y est tronqué dès qu'une recherche porte un intitulé un peu long — « Ingénieur DevOps ·
terraf… » à l'écran aujourd'hui — alors que c'est le seul élément de la page qui dise **de quoi on
parle**.

**Les deux entrées vers les filtres sont au même endroit, et au même rang.** « Classiques » et
« Secondaires » sont deux onglets de cette même colonne étroite, à égalité typographique avec
« En cours ». Deux formulaires de douze champs s'ouvrent donc dans une gouttière de deux cent
quatre-vingts pixels, sous le compteur qui les a fait descendre.

**La bande large existe déjà, elle a simplement été retirée de cet écran.** `ENT-041` l'a supprimée
en même temps qu'elle créait la console : le raisonnement était qu'une console porterait tout, barre
haute comprise. La brique, elle, n'a jamais disparu — la Table l'utilise toujours (`ActionBar.vue`),
et le document d'architecture décrit encore la Visionneuse avec sa barre haute.

## Périmètre

- **La barre haute est rétablie** sur `/recherches/:uuid`, entre la gouttière et la grille, avec la
  brique existante `ActionBar` — la même que la Table, aucune création.
- **Le nom de la recherche s'y installe à gauche**, en toutes lettres et visible de tous. Le titre
  de premier niveau de la page, jusqu'ici réservé aux lecteurs d'écran, devient ce titre-là : le nom
  se lit, la mention « Parcourir les profils » qui le précède reste réservée à l'écoute.
- **Deux boutons « Classiques » et « Secondaires » se posent à droite**, dans l'emplacement d'action
  de la bande. Ils sont **désactivés** : ils annoncent où iront les filtres, ils n'ouvrent rien.
- **La colonne de gauche perd ses onglets** et son entête « RECHERCHE EN COURS » + nom. Elle ne
  porte plus que ce qui reste : les trois compteurs, l'avancement, et le profil courant face aux
  critères.
- **La chaîne de relais qui portait l'affinage disparaît de la page et de la console** — les deux
  formulaires n'ayant plus rien qui les ouvre, ce qui leur passait les critères et remontait leur
  résultat n'a plus d'objet.

## Hors périmètre

- **Brancher les boutons.** Ce que « Classiques » et « Secondaires » ouvriront — un panneau latéral,
  un tiroir, une fenêtre modale, ou le retour des onglets — n'est pas tranché ici et fera l'objet
  d'une carte à part. C'est la demande, formulée telle quelle : *« les boutons pour le moment
  n'ouvriront rien »*.
- **Les deux formulaires eux-mêmes** (`FiltersPrimary.vue`, `FiltersSecondary.vue`), qui ne sont pas
  supprimés, pas déplacés, pas modifiés d'une ligne. Ils attendent d'être rebranchés.
- **Le retrait d'un critère d'un clic**, qui vit dans la liste « ce profil face à vos critères » et
  continue de fonctionner : c'est la seule modification de recherche qui survit à cette tâche.
- **La Table**, sa barre haute et ses onglets de vues. Elle n'est pas touchée.
- **La fiche du candidat**, la pile de profils, la gouttière et son ordre de parcours (`ENT-057`).
- **Les fichiers de langue**, qu'une autre session modifie en ce moment même — voir *Risques*.

## Décisions de cadrage

### Le titre à gauche, les commandes à droite

C'est le découpage que la barre haute de la Table applique déjà, et que la gouttière applique aussi
(guidage à gauche, action à droite). Un recruteur qui bascule d'un outil à l'autre retrouve donc les
commandes au même endroit. Les deux autres dispositions envisagées — boutons collés au titre, ou
titre rejeté à droite — cassaient cette symétrie sans rien gagner.

### Les boutons sont désactivés, pas seulement inertes

Un bouton d'apparence normale qui ne fait rien au clic se lit comme une panne, et il n'y a aucun
moyen pour le recruteur de savoir que c'en est une. Désactivés, ils disent la même chose sans
mentir : ça arrive, ce n'est pas encore là. Le curseur ne change pas au survol, et les lecteurs
d'écran annoncent « indisponible » au lieu d'inviter à un geste sans effet.

### Le nom quitte la colonne, il n'y reste pas en double

C'est un déplacement, pas une copie. Le même nom à deux endroits d'un même écran fait douter qu'il
s'agisse de la même chose, et la colonne n'a pas de place à donner.

### Titre et boutons s'affichent aussi sur téléphone

C'est même le premier gain de la tâche : en dessous de 1024 pixels, la barre haute devient le
**seul** endroit qui nomme la recherche. Faire disparaître les boutons sous cette largeur aurait
fait apparaître et disparaître deux commandes au redimensionnement, sans bénéfice.

### Le titre cède le passage sous 640 pixels — largeur MESURÉE, ajoutée à l'implémentation

Le cadrage prévoyait que le nom se coupe « plutôt que de pousser les deux boutons hors de l'écran ».
La première capture sur téléphone a montré le contraire : le second bouton arrivait coupé en deux.
Mesure dans le navigateur, à 390 pixels de large — les deux boutons réclament 175 pixels, la bande
en offre 342, et le titre en prenait 203, ne laissant que 131 à la zone d'action. La zone déborde
alors en défilement horizontal, ce qui se lit comme un bouton cassé.

Le titre est donc borné à 136 pixels en dessous de 640, et libre au-dessus. Le chiffre vient de la
mesure et non d'un choix : il est le complément exact de ce que les deux boutons réclament. Nouvelle
mesure après correction : plus aucun débordement à 390, 430, 768 ni 1600 pixels.

### On assume de ne plus pouvoir affiner depuis cet écran

C'est la contrepartie explicite du découpage : entre cette carte et celle qui branchera les boutons,
ajuster ses critères en lisant ses résultats n'est plus possible. Deux gestes restent ouverts —
retirer un critère d'un clic dans la liste de la colonne, ou repartir d'une nouvelle recherche. La
perte est bornée dans le temps et ne détruit rien : aucun formulaire n'est supprimé.

## Règles et cas limites

- **Recherche d'un collègue, lue sans droit d'écriture** : les deux boutons **ne s'affichent pas**.
  C'est la règle qui gouvernait déjà les deux onglets — une commande qu'on n'a pas le droit
  d'utiliser n'occupe pas la bande. La barre haute ne garde alors que le nom.
- **Pendant le chargement, et sur une recherche sans intitulé** : la bande reste, avec le repli
  « Recherche sans nom » déjà employé par les cartes de recherche. La page ne se réorganise pas
  verticalement à l'arrivée des données.
- **Nom très long** : il se coupe proprement en fin de ligne plutôt que de pousser les deux boutons
  hors de l'écran — la bande a une hauteur bornée, elle ne se replie pas sur deux lignes.
- **Un seul titre de premier niveau** sur la page, et il arrive avant la grille dans l'ordre de
  lecture : on n'entre pas dans un document par un chapitre. C'est déjà l'état actuel, il change
  seulement de lieu et devient visible.
- **Colonne sans onglets** : la bande d'onglets disparaît entièrement plutôt que de rester avec un
  seul onglet actif, ce qui n'aurait plus rien d'un choix.

## Documentation impactée

- `dev-book/cadrage/entreprise/ui/vue_la_visionneuse.md` — le tableau des zones (§3) annonce une
  « console à trois onglets », le chapitre 5 écrit qu'elle « remplace la barre haute **et** le
  tiroir d'affinage », et la liste de vérification finale reprend les deux affirmations. Les trois
  passages deviennent faux. Le texte se lit **côté client** : il décrira la barre haute et la
  colonne telles qu'elles sont, sans jamais mentionner que les boutons attendent d'être branchés.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` — deux passages décrivent la
  barre haute de la Visionneuse comme portant « les 4 critères, et de quoi les affiner ». Ils
  n'étaient déjà plus vrais avant cette tâche ; ils doivent dire ce que la bande porte réellement.

## Risques et reports assumés

- **Deux clés de traduction deviennent inutilisées** (l'intitulé de l'onglet « En cours » et celui
  de l'encart « RECHERCHE EN COURS »). Elles sont **laissées en place** : les trois fichiers de
  langue sont modifiés en ce moment par une autre session, et y toucher provoquerait un conflit
  pour un gain nul. À nettoyer quand les boutons seront branchés.
- **Le travail en cours d'autres sessions occupe le même dossier de travail** — deux tâches non
  commitées y vivent au moment où cette carte est écrite. Le commit de celle-ci devra désigner ses
  fichiers un par un, sans jamais prendre un dossier entier.
- **La fenêtre sans affinage** est la seule régression fonctionnelle, tracée ci-dessus et bornée par
  la carte aval.

## Vérification

Preuves observées le 2026-08-06 sur la pile de développement du worktree entreprise, connecté en
propriétaire d'entreprise (`atlas.owner`), navigateur piloté, thème sombre.

**Captures de la page connectée** — `/recherches/019fd61b-d8f9-710e-841d-7d092d1b105a` :

- **1600 × 900** — la bande porte « Ingénieur DevOps · terraform » à gauche et les deux boutons à
  droite ; la colonne commence directement par les trois compteurs, sans onglets ni entête.
- **390 × 844** — la même bande, les deux boutons entiers, le titre coupé à « Ingénieur De… ». Avant
  cette tâche, aucune ligne de cet écran ne nommait la recherche à cette largeur.
- **Recherche d'un collègue** (`019fd61b-9be5-…`, `isOwn: false`) — la bande ne porte que le nom, les
  deux boutons sont absents.

**Mesures dans la page servie**, aux quatre largeurs :

| Largeur | Bande | Titre | Zone d'action | Contenu réclamé | Déborde |
| --- | --- | --- | --- | --- | --- |
| 390 | 374 | 136 | 175 | 175 | non |
| 430 | 414 | 136 | 175 | 175 | non |
| 768 | 751 | 282 | 175 | 175 | non |
| 1600 | 1581 | 306 | 175 | 175 | non |

Les deux boutons y sont lus comme **désactivés** (`disabled` vrai dans le document servi, pas
supposé d'après le code source).

**Journal du serveur de développement** : aucun avertissement de propriété manquante ni erreur de
rendu après le changement — seules subsistent les lignes de l'outil de développement de Nuxt, sans
rapport.

## Livré

- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` (M) — la barre haute est posée
  entre la gouttière et la grille ; le titre de premier niveau y devient visible et porte le nom de
  la recherche, résolu une fois ; les deux boutons désactivés occupent la zone d'action, absents
  sans droit d'écriture ; les deux relais d'affinage disparaissent.
- `webapp/spaces/entreprise/app/components/search/Console.vue` (M) — la barre d'onglets et les deux
  formulaires quittent la console, avec les propriétés et les intentions qui les servaient.
- `webapp/spaces/entreprise/app/components/search/ControlPanel.vue` (M) — ne transmet plus le nom.
- `webapp/spaces/entreprise/app/components/search/ProgressHeader.vue` (M) — l'entête « RECHERCHE EN
  COURS » et le nom disparaissent ; compteurs, portée et progression sont intacts.
- `dev-book/cadrage/entreprise/ui/vue_la_visionneuse.md` (M) — schéma, tableau des zones, chapitre 5
  et décisions validées décrivent la barre haute et la colonne d'un seul tenant.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` (M) — les deux passages qui
  décrivaient une barre haute portant « les 4 critères » disent ce qu'elle porte réellement.

## Reste à faire (hors périmètre)

- **Brancher les deux boutons** : carte à écrire, elle refermera la fenêtre pendant laquelle on ne
  peut plus affiner une recherche depuis cet écran.
- **Deux clés de traduction orphelines** — l'intitulé de l'onglet « En cours » et celui de l'encart
  « RECHERCHE EN COURS ». Laissées en place : les trois fichiers de langue étaient ouverts par une
  autre session au moment du commit, et les toucher aurait provoqué un conflit pour un gain nul.

## Définition de terminé

- [x] La barre haute est rétablie sur `/recherches/:uuid`, entre la gouttière et la grille.
- [x] Le nom de la recherche s'y lit à gauche, en toutes lettres, sur grand écran **et** en largeur
      téléphone.
- [x] Les boutons « Classiques » et « Secondaires » sont à droite, désactivés, et absents sur une
      recherche qu'on n'a pas le droit de modifier.
- [x] La colonne de gauche n'a plus d'onglets ni d'entête « RECHERCHE EN COURS » ; ses compteurs,
      son avancement et sa liste de critères sont intacts.
- [x] La page ne porte qu'un seul titre de premier niveau, placé avant la grille.
- [x] Captures fournies, sur grand écran et en largeur téléphone — plus une mesure des largeurs, qui
      a révélé et fait corriger un bouton coupé sur téléphone.
- [x] Les deux documents listés ci-dessus sont réalignés dans le même commit.
