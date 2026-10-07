# TASK ENT-071 — entreprise : la barre haute porte le rang et l'avancement

- **Créée le** : 2026-08-07.
- **Session** : `73d0110f-a5ee-41fa-b3e5-959796b4171d`.
- **Statut** : ✅ fait le 2026-08-09 — la barre haute porte les deux entrées vers les filtres au
  centre exact, le rang à droite et le fil d'avancement sur son bord bas ; la colonne de gauche ne
  garde que le tri accompli. Implémentée **deux fois** : une première sur le gabarit `recherche` et
  ses projections, jetée sans être commitée quand la revue de code du 2026-08-09 les a supprimés,
  puis refaite en composition directe — plus courte, et rendue par le serveur.
- **Type** : feat — trois éléments changent de place, et deux d'entre eux deviennent visibles là où
  ils ne l'étaient pas du tout. Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Rien ne se calcule ici : trois valeurs
  déjà résolues changent d'emplacement, et une zone s'ajoute à un gabarit. Le webapp n'a aucun
  harnais de test de composant (`webapp/vitest.config.ts` ne couvre que le code pur, sans DOM), et
  jsdom ne mesure ni largeur ni centrage. La preuve attendue est une **capture de la page
  connectée**, avant et après, sur grand écran **et** en largeur téléphone.
- **Réf** : **`ENT-061` — amont** (elle a rétabli la barre haute, posé le nom à gauche et les deux
  boutons à droite) · **revue de code du 2026-08-09** (elle supprime le gabarit `recherche` et
  toutes les projections `Teleport` : chaque page compose désormais sa propre barre haute) ·
  `ENT-057` (l'ordre du parcours dans la gouttière) · `ENT-058`, `ENT-049`, `ENT-047` (l'histoire
  de la colonne) · **aval : brancher les deux boutons de filtres**, carte toujours à écrire ·
  `webapp/spaces/entreprise/app/components/ActionBar.vue` ·
  `app/components/search/{Position,Progress,Console,ControlPanel,ProgressHeader}.vue` ·
  `app/pages/recherches/[uuid]/index.vue` ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md`

## Contexte

**Sur téléphone, on parcourt des centaines de profils sans savoir où l'on en est.** Le rang du
profil affiché — « 1 / 28 » — et le fil qui montre la proportion déjà vue vivent tous deux en tête
de la colonne de gauche. Or cette colonne est **masquée en dessous de 1024 pixels** : c'est une
décision de mise en page assumée depuis `ENT-041`, la fiche du candidat prenant tout le cadre. En
dessous de cette largeur, ces deux repères n'existent donc nulle part. C'est le même manque que
`ENT-061` a corrigé pour le nom de la recherche, sur les deux informations voisines.

**Et la barre haute est à moitié vide.** Depuis `ENT-061` elle porte un nom à gauche et deux boutons
à droite, avec un large vide entre les deux — sur grand écran, la moitié de sa largeur ne dit rien.
Elle est pourtant la seule bande qui survive au rétrécissement de l'écran.

**Le rang et le fil se lisent ensemble, et c'est délibéré.** `ProgressHeader.vue` les a posés côte à
côte : « la barre montre où l'on en est, le nombre le dit ». Les séparer casserait ce couple ; les
faire monter tous les deux le préserve, et vide la colonne de gauche d'un bloc qu'elle n'a plus de
raison de porter.

## Périmètre

- **Une troisième zone au centre de la barre haute.** `ActionBar.vue` n'a aujourd'hui que deux
  emplacements, gauche et droite. Le centre s'y ajoute, et il n'existe **que pour la page qui le
  déclare** : les autres n'écrivent pas cet emplacement, et la bande reprend alors sa forme à deux
  colonnes.
- **Les deux boutons « Classiques » et « Secondaires » y déménagent**, au centre exact de la bande.
  Ils restent **désactivés** : cette tâche les déplace, elle ne les branche pas.
- **Le rang s'installe à droite de la bande**, avec le chiffre courant nettement plus gros que le
  total — « **8** / 234 ». Il **quitte** la colonne de gauche.
- **Le fil d'avancement le suit**, collé au bord bas de la barre haute, sur toute sa largeur, à son
  épaisseur actuelle.
- **La colonne de gauche perd la rangée du bas de son entête** — le fil et le nombre. Elle garde les
  trois compteurs « Vus / Favoris / Écartés », la mention de portée quand la pile chargée est plus
  courte que le jeu, puis les critères confrontés au profil.
- **La documentation d'arborescence suit** : la barre haute de cet écran ne porte plus seulement
  « le nom de la recherche, et l'accès aux filtres ».

## Hors périmètre

- **La Table et l'écran de création**, qui partagent cette barre. Ils ne reçoivent ni rang ni fil :
  la Table n'a pas de parcours séquentiel, et sa zone de droite est déjà prise par ses onglets de
  vues. Leur bord bas reste net et leur centre n'existe pas.
- **Brancher les deux boutons de filtres.** Ils n'ouvrent toujours rien depuis `ENT-061`. Les
  centrer les rend plus visibles sans les rendre cliquables — la dette reste ouverte et entière.
- **Le sélecteur d'ordre du parcours** (« Non vus d'abord »), qui reste dans la gouttière au-dessus.
- **Les trois compteurs « Vus / Favoris / Écartés »**, qui restent dans la colonne de gauche : ils
  décrivent le tri accompli, pas la position courante.
- **Ce que le fil mesure.** Il compte les profils **vus** et non le défilement — décision d'`ENT-049`
  qu'on ne rouvre pas ici.
- **Le panneau « Ce profil face à vos critères »**, qui fait l'objet de deux cartes distinctes issues
  du même échange : le verdict toujours écrit en toutes lettres avec le détail au survol, et le titre
  « Mots-clés » au-dessus de son bloc.

## Décisions de cadrage

### Le nom cède, jamais les boutons

Les deux boutons restent au **centre exact de la bande** à toute largeur ; c'est le nom de la
recherche qui se coupe quand la place manque. L'alternative — un nom toujours entier et des boutons
qui glissent vers la droite — a été écartée : leur position changerait d'une recherche à l'autre,
et un geste qu'on cherche des yeux à chaque écran n'est plus un repère. Le nom, lui, reste lisible
en entier dans le titre du document et se relit en le survolant.

Conséquence de mise en page : un centre réellement centré ne peut pas être obtenu par une simple
répartition entre trois blocs, où la largeur du nom décale le milieu. Il faut une grille dont la
colonne centrale ne dépend pas de ses voisines.

### Le compteur déménage, il ne se duplique pas

Un même nombre écrit deux fois sur le même écran, à trente centimètres d'écart, est une répétition
que le reste de cet écran s'interdit déjà. Et le déplacer est ce qui règle le problème d'origine :
la colonne de gauche disparaît sur petit écran, la barre haute non.

### Le fil suit le compteur

Ils ont été mis côte à côte exprès. Les séparer aurait laissé un fil sans son nombre dans une
colonne, et un nombre sans son fil dans une bande — chacun amputé de ce qui l'explique.

### Sur téléphone, tout tient et c'est le nom qui se réduit

Les boutons et le rang gardent leur place à toute largeur. C'est la suite directe de la première
décision, et surtout : le rang sur petit écran est la **raison d'être** de cette tâche. Le sacrifier
là où il manque le plus la viderait de son objet. Replier les filtres sous un bouton unique a été
écarté — ce serait un geste de plus pour atteindre des boutons qui n'ouvrent encore rien.

### Sans aucun profil, ni rang ni fil — mais les boutons restent

Un « 0 / 0 » et une jauge à zéro sur un écran vide n'apprennent rien et se lisent comme une panne.
C'est déjà le comportement du compteur dans la colonne aujourd'hui. Les deux boutons de filtres, eux,
restent : une recherche sans résultat est exactement le moment où l'on veut élargir ses critères.

### Le gabarit de colonnes suit la présence du centre — écart inscrit après coup

La carte annonçait un centre « vide et sans effet » partout. L'implémentation a montré que ce n'est
pas tenable : centrer **exactement** demande deux colonnes latérales de largeur égale, et une bande
à trois colonnes égales sur la Table y tronquerait le nom de la recherche à mi-largeur, alors qu'il
y dispose aujourd'hui de toute la bande moins les onglets de vues. Le gabarit dépend donc de la
présence du centre : deux colonnes sans lui, trois avec.

Une première version, écrite avant la revue de code du 2026-08-09, avait besoin d'un drapeau posé
sur la page pour distinguer les trois écrans — la barre était alors portée par un gabarit commun, et
chaque page y projetait ses commandes à distance. La revue ayant supprimé ce gabarit et interdit les
projections, chaque page compose sa propre barre : elle déclare le centre, ou ne le déclare pas. Le
drapeau n'a plus d'objet, et le rendu serveur vient en prime.

### Le fil garde son épaisseur

Il courait sur deux cent quatre-vingts pixels, il courra sur toute la largeur de l'écran : à
épaisseur égale il gagne déjà beaucoup en présence. L'épaissir en ferait un élément de l'interface
disputant l'attention à la fiche du candidat, alors que c'est un repère.

## Règles et cas limites

- **Recherche d'un collègue (lecture seule).** Les deux boutons de filtres n'apparaissent pas —
  comportement existant —, le centre reste donc vide. Le rang et le fil, eux, **s'affichent** : le
  serveur calcule « vu » et « écarté » **par recruteur** (`SearchResultsBuilder`, `statesFor($search,
  $recruiter)`), l'avancement affiché est bien celui de qui regarde.
- **Aucun profil dans le jeu.** Ni rang ni fil, bord bas net. Les boutons restent.
- **Le rang et le fil ne racontent pas la même chose, et peuvent diverger.** Rang 8 sur 234 avec un
  fil au tiers n'est pas une contradiction : on a pu sauter des profils. Les deux étaient déjà
  voisins dans la colonne ; les remonter ensemble rend l'écart plus visible sans le créer. Le texte
  destiné aux lecteurs d'écran continue de les nommer séparément — position d'un côté, avancement de
  l'autre.
- **Accessibilité.** Le fil garde son rôle de barre de progression **et son nom accessible** : un
  pourcentage nu ne s'annonce pas. Le rang garde la phrase complète qui l'accompagne, le découpage
  visuel gros/petit ne devant rien changer à ce qui est lu à voix haute.
- **Rendu serveur, et sans clignotement.** La page compose sa barre haute directement : le rang et
  le fil sont rendus par le serveur comme le reste de l'écran. C'était l'inverse du temps des
  projections, où ils n'apparaissaient qu'une fois la page vivante — la revue de code qui les a
  supprimées fait disparaître ce défaut au passage.
- **La bande ne grandit pas.** Sa hauteur bornée est inchangée ; le fil se pose **sur** son bord bas,
  il ne s'ajoute pas sous elle. Les coins arrondis de la bande doivent le rogner, sans quoi il
  déborde aux extrémités.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` — la ligne qui décrit la
  Visionneuse (« barre haute : le nom de la recherche, et l'accès aux filtres ») et le paragraphe sur
  la coque partagée par les trois écrans — ⚠️ ce paragraphe décrit un gabarit `recherche` que la
  revue de code a supprimé, il est faux **indépendamment de cette tâche** et n'est pas corrigé ici.
- Les commentaires d'en-tête de `ActionBar.vue`, `ProgressHeader.vue` et `ControlPanel.vue`, qui
  énoncent chacun ce que leur bloc porte — et qui deviennent faux sans mise à jour.

## Vérification

Mesures lues dans la page rendue, sur la recherche « Développeur logiciel · 1 an et plus · Bac ·
front-end de… » — nom volontairement long, et avancement partiel.

| Ce qui est vérifié | Ce qui a été observé |
| --- | --- |
| Centrage exact, grand écran | colonnes latérales `592,828px` et `592,844px` ; milieu du bloc de boutons à **0 px** du milieu de la bande |
| Centrage exact, largeur téléphone (390 px) | **0 px** d'écart également |
| Le fil prend toute la largeur | fil `1422 px` pour une bande de `1422 px` |
| Le fil suit sa valeur | remplissage mesuré à 0 %, 4 %, 5 %, 9 %, 65 %, 70 %, 80 % et 100 % sur huit recherches |
| Le rang pèse plus que le total | `20,32 px` contre `14,91 px` |
| Le rang reste annonçable | à l'œil « 1 / 10 », à l'écoute « Profil 1 sur 10 » |
| Le fil reste annonçable | `role="progressbar"`, nom accessible « Profils vus », valeur exposée |
| Le rang a quitté la colonne | plus aucun `search-position` sous `search-progress-header` |
| Aucune projection résiduelle | zéro élément `#search-shell-*` dans la page |
| Jeu vide | ni rang ni fil, bande à trois colonnes, les deux boutons de filtres toujours là |
| Recherche d'un collègue | aucun bouton au centre, rang « 1 / 28 » et fil à 4 % présents |
| Table | bande à deux colonnes, aucun fil — allure inchangée |
| Écran de création | bande à deux colonnes, aucun fil — allure inchangée |

Captures de la page connectée, grand écran et largeur téléphone, prises sur la stack de la lane avec
le compte `atlas.owner` puis `atlas.member`. La recherche impossible créée pour observer le jeu vide
a été supprimée derrière la vérification.

## Livré

- `components/ActionBar.vue` (M) — la bande passe en grille ; une zone centrale et un emplacement de
  fil s'ajoutent, et le gabarit de colonnes suit la présence du centre.
- `components/search/Position.vue` (+) — le rang, chiffre courant en gros, total en petit, phrase
  entière pour les lecteurs d'écran.
- `components/search/Progress.vue` (+) — le fil, extrait de l'entête de la colonne.
- `pages/recherches/[uuid]/index.vue` (M) — la page déclare le centre, le rang et le fil, et cesse de
  passer le rang et l'avancement à la colonne.
- `components/search/{Console,ControlPanel,ProgressHeader}.vue` (M) — la chaîne de props perd
  `position` et `progress` ; l'entête de la colonne perd sa rangée du bas.
- `docs/…/arborescence-entreprise.md` (M) — la barre haute de la Visionneuse décrite telle qu'elle
  est désormais.

## Reste à faire (hors périmètre)

- **Brancher les deux boutons de filtres**, toujours inertes depuis `ENT-061`. Les centrer les rend
  plus visibles sans les rendre cliquables. Carte à écrire.
- **Le paragraphe « Les trois écrans d'une recherche partagent une même coque »** de
  `arborescence-entreprise.md` décrit un gabarit `recherche` supprimé par la revue de code. Il est
  faux indépendamment de cette tâche et n'a pas été réécrit ici.

## Définition de terminé

- [x] La barre haute a trois zones ; la Table et l'écran de création, qui ne déclarent pas de
      centre, gardent exactement leur allure.
- [x] Les deux boutons de filtres sont au centre exact, à toute largeur d'écran, et restent
      désactivés.
- [x] Le nom de la recherche se coupe quand la place manque ; les boutons ne se décalent jamais.
- [x] Le rang s'affiche à droite de la bande, chiffre courant nettement plus gros que le total.
- [x] Le rang a disparu de la colonne de gauche — il n'est plus écrit qu'à un seul endroit.
- [x] Le fil d'avancement est collé au bord bas de la bande, sur toute sa largeur, à son épaisseur
      d'origine, et rogné par les coins arrondis.
- [x] Le fil a disparu de la colonne de gauche, qui garde ses trois compteurs et ses critères.
- [x] Sur un jeu vide : ni rang ni fil, bord bas net, boutons toujours là.
- [x] Sur la recherche d'un collègue : aucun bouton au centre, rang et fil présents.
- [x] Le fil garde son rôle et son nom accessible ; le rang garde sa phrase lue à voix haute.
- [x] Capture de la page connectée, avant et après, sur grand écran et en largeur téléphone.
- [x] La page d'arborescence de l'espace entreprise et les trois commentaires d'en-tête sont à jour.
