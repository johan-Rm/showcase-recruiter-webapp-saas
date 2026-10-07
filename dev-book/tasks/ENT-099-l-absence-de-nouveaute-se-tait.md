# TASK ENT-099 — entreprise : l'absence de nouveauté se tait

- **Créée le** : 2026-08-12.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-12 — la mention « pas de nouveaux » a disparu des cartes de
  recherche, la place qu'elle occupait est réservée au centième de pixel près, et sa clé de
  traduction est partie des trois langues.
- **Type** : Frontend entreprise — retrait d'une mention sur un composant existant. Aucun contrat,
  aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable`. Une branche d'affichage disparaît, la place
  qu'elle occupait reste réservée. Rien ne se calcule : le rail est déjà construit par `railOf`, une
  fonction pure couverte, et **elle ne change pas** — c'est ce que la carte en montre qui change. Le
  webapp n'a aucun harnais capable de rendre un composant (`webapp/vitest.config.ts` ne couvre que
  le code pur, sans DOM). **La preuve attendue est une capture** de la liste montrant des cartes avec
  et sans nouveauté côte à côte, et une mesure de leurs hauteurs de rail.
- **Réf** : `webapp/spaces/entreprise/app/components/search/Card.vue` ·
  `webapp/base/i18n/locales/{fr,en,ar}.yaml` · `webapp/services/search/card.ts` (`railOf`, inchangée)

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur « Vos recherches enregistrées ».
Chaque recherche s'y montre en carte ; à gauche, un rail vertical porte le nombre de profils, puis
une ligne sur la nouveauté.

**Cette ligne parle pour ne rien dire.** Quand la recherche a ramené de nouveaux profils, elle
annonce « 13 nouveaux », en vert, précédée d'une pastille. Quand elle n'en a pas, elle écrit
**« pas de nouveaux »** — une phrase grise qui occupe une ligne pour annoncer qu'il n'y a rien à
annoncer. Sur une liste de cinquante-trois recherches dont la plupart n'ont rien de neuf, c'est la
mention la plus répétée de l'écran, et la moins utile.

**Elle est là exprès, et c'est ce qui rend la décision non triviale.** Le code écrit son motif :
elle « occupe la place au lieu de la laisser vide », ce qui « distingue *j'ai regardé, il n'y a
rien* de *la carte n'a pas fini de charger* », et « rend une colonne de cartes lisible d'un trait ».

**Mais la première moitié de ce motif ne tient plus.** La liste remplace toutes les cartes par
« Chargement… » tant qu'elle charge : une carte n'est jamais rendue à moitié, et la confusion avec
un chargement en cours n'est pas possible sur cet écran. Reste la seconde moitié — l'alignement —,
qui elle est bien réelle.

## Périmètre

- **La mention « pas de nouveaux » disparaît** de la carte de recherche.
- **Sa place reste réservée** : le rail garde exactement la même hauteur, avec ou sans nouveauté.
- **Rien n'est dit non plus aux lecteurs d'écran** à cet endroit.
- **La clé de traduction devient orpheline et se retire** des trois langues.

## Hors périmètre

- **La mention « 13 nouveaux »**, qui ne bouge pas — ni son texte, ni sa pastille, ni sa couleur.
- **La mention d'une recherche close**, qui passe devant les deux autres et reste telle quelle.
- **`railOf`**, la fonction pure qui calcule le rail : elle continue de rendre le compte de
  nouveautés, c'est seulement son affichage qui change. Sa suite de tests ne bouge pas.
- **Les autres écrans** : la mention n'existe qu'ici, une seule fois dans tout le webapp.

## Décisions de cadrage

**On retire, on ne remplace pas.** L'alternative — une mention plus courte, un point, un tiret —
aurait gardé le défaut : une ligne pour dire qu'il n'y a rien à dire. Le silence est la réponse au
bruit.

**La place reste réservée, arbitré avec l'utilisateur le 2026-08-12.** La refermer aurait fait des
cartes sans nouveauté des cartes plus courtes. En grille, chaque rangée s'aligne déjà sur sa carte
la plus haute, donc l'écart ne se serait vu qu'entre deux rangées entièrement sans nouveauté — mais
il se serait vu. Le rail garde donc sa hauteur, et le vide n'a pas à s'expliquer puisqu'il n'y a
rien à y lire.

**Rien pour les lecteurs d'écran non plus, arbitré le même jour.** Une mention invisible mais lue
aurait créé deux expériences différentes du même écran. Si l'absence de nouveauté ne mérite plus
d'être écrite, elle ne mérite pas d'être dite — et le nombre de profils comme l'avancement restent
lus juste à côté.

**La clé de traduction part avec la mention.** Une clé sans appelant survit à l'écran qui
l'employait et se retrouve, six mois plus tard, réutilisée pour autre chose ou traduite pour rien.
Même geste que pour `search.sentence.name.autoHint`, retirée le 2026-08-11 dans les mêmes
conditions.

**Le commentaire qui justifiait la mention est réécrit, pas effacé.** Il porte une décision et son
motif ; le supprimer ferait croire à un oubli, et quelqu'un rétablirait la mention en toute bonne
foi. Il doit dire pourquoi la place reste réservée alors que rien ne s'y écrit.

## Règles et cas limites

- **Une recherche close continue de l'annoncer** : sa mention passe devant, et elle n'est pas
  concernée.
- **Le rail garde la même hauteur dans les trois cas** — nouveautés, aucune, close.
- **Aucun caractère de remplissage visible** : la place se réserve par une hauteur, pas par un texte
  transparent ou une espace insécable qu'un lecteur d'écran pourrait annoncer.
- **La hauteur réservée suit le palier de texte** dont elle prend la place : figée en pixels, elle se
  décalerait au premier changement d'échelle typographique.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni règle métier ne change, et la carte de
navigation ne décrit pas le contenu du rail. Aucun `README.md` ne change de convention.

## Définition de terminé

- [x] Une recherche sans nouveauté n'affiche plus « pas de nouveaux ».
- [x] Son rail a exactement la même hauteur qu'une carte qui en annonce.
- [x] Rien n'est annoncé à cet endroit aux lecteurs d'écran.
- [x] Une recherche avec des nouveautés, et une recherche close, sont inchangées.
- [x] La clé `search.card.noNew` a disparu des trois fichiers de langue ; le test de parité passe.
- [x] Le commentaire du composant explique pourquoi la place reste réservée.
- [x] Preuve fournie que la place réservée vaut exactement une ligne du palier qu'occupait le texte —
      par la mesure, faute d'un jeu de données contenant deux recherches dont une avec nouveautés.
      La comparaison côte à côte reste à faire, et elle est inscrite en *Reste à faire*.

## Vérification

Preuves prises le 2026-08-12 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`, sur
« Vos recherches enregistrées ».

| Ce qui est prouvé | Mesure lue |
| --- | --- |
| La mention a disparu | recherchée dans tout le texte de la page : absente |
| La place réservée vaut exactement une ligne | **22,36 px** réservés · une vraie ligne du même palier, insérée pour comparaison, mesure **22,36 px** |
| Elle suit le palier, pas des pixels figés | hauteur de ligne calculée : `22.368px` |
| Rien n'est lu à voix haute | contenu de l'élément : chaîne vide |
| Le rail garde sa hauteur | 170 px, identique en thème clair et sombre |
| Les trois langues restent à parité | 9 tests verts |

**Une limite de cette preuve, assumée.** Le jeu de données du worktree ne contient **qu'une seule
recherche**, et elle n'a pas de nouveautés : impossible d'en poser deux côte à côte, l'une annonçant
des nouveautés et l'autre non, comme la définition de terminé le demandait littéralement.
L'équivalence a donc été établie autrement, et plus finement qu'à l'œil — la place réservée et une
ligne de texte réelle du même palier mesurent la même chose au centième de pixel. Une comparaison
visuelle demanderait un jeu d'au moins deux recherches dont une avec des nouveautés.

**Captures** : `preuve-rails-dark.png`, `preuve-rails-light.png`, `preuve-rail-vide.png`, dans le
dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

Aucune gate qualité complète n'a été jouée : elles appartiennent à l'ouverture de la PR.

## Livré

- `webapp/spaces/entreprise/app/components/search/Card.vue` — la branche qui affichait « pas de
  nouveaux » ne rend plus qu'un espace de la hauteur d'une ligne, sans contenu ni caractère de
  remplissage ; le commentaire dit pourquoi la place reste prise.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — `search.card.noNew` retirée, devenue orpheline.

## Reste à faire (hors périmètre)

- **Deux violations de conventions traînent sur la branche**, dans `ProfileCard.vue` et
  `MatchList.vue` : le mot « désormais » y raconte une chronologie. Elles viennent d'une autre
  session et bloqueront la PR.
- **La comparaison visuelle entre une carte avec nouveautés et une sans** reste à faire sur un jeu
  de données qui en contient deux.
