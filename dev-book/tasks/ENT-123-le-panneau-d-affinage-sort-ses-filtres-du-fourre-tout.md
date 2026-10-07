# TASK ENT-123 — le panneau d'affinage sort ses filtres du fourre-tout

- **Créée le** : 2026-08-14.
- **Session** : `b5fe5724-1c7f-4c06-ad0b-9ce84888cfe0`.
- **Statut** : ✅ fait le 2026-08-16 — l'interrupteur ouvre la colonne, le secteur a son volet, pays
  et langue se lisent à plat, et une liste à un seul choix ne s'affiche plus.
- **Type** : refactor — réorganisation d'un panneau existant, plus une règle d'affichage sur les
  listes calculées. Aucun filtre nouveau, aucun filtre retiré.
- **Tests** : `webapp/services/search/refine.vitest.ts` — chacune des quatre listes calculées rend
  vide à un seul choix et reste offerte à deux. **Aucun test de rendu** : le dépôt n'a aucun test de
  composant, et l'ordre des blocs comme la disparition d'un volet se prouvent à l'écran.
- **Réf** : `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` ·
  `webapp/services/search/refine.ts` ·
  `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` (la page qui décrit les affinages) ·
  `webapp/base/app/components/README.md` (conventions de composant).

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, parcourt les profils d'une recherche
enregistrée. À **gauche** de la fiche, la colonne d'affinage : quatre volets repliés — « Présence au
Maroc », « Projet de retour », « Disponibilité », « Métier et lieu ». Ces filtres resserrent ce qu'on
regarde ; ils ne touchent pas à la recherche enregistrée et disparaissent en quittant l'écran.

**Le dernier volet est un fourre-tout.** Il s'appelle « Métier et lieu » parce qu'on y a rangé ce qui
restait : le pays de résidence, le secteur d'expérience, la langue parlée, et l'interrupteur du poste
encore exercé. Quatre filtres sans rapport entre eux derrière un même clic.

**L'interrupteur le plus décisif est le plus enfoui.** Ne garder que les gens encore en poste sur le
métier cherché demande de déplier le volet, puis de descendre sous trois listes. C'est une ligne, et
elle coûte le plus de gestes du panneau.

**Deux de ces listes n'offrent souvent qu'un seul choix.** Mesuré sur deux recherches réelles, sur
les 30 profils chargés : **1 à 2 pays**, **5 langues**, **13 à 15 secteurs**. Une liste à un seul
bouton occupe deux lignes de colonne et ne filtre rien — et le problème devient visible une fois la
liste sortie du volet qui la cachait.

## Périmètre

- **L'interrupteur monte en tête du panneau, hors de tout volet.** Son libellé devient
  « Poste actuel » et **perd son compte entre parenthèses**. Sa phrase d'avertissement reste,
  mot pour mot.
- **Le volet « Métier et lieu » disparaît.** Son contenu se redistribue :
  - le **secteur** devient un volet à lui seul, nommé « Secteur », **après** les trois volets
    existants ;
  - le **pays** et la **langue** se posent **à plat sous les volets**, dans cet ordre, chacun avec
    son intitulé.
- **Une liste calculée sur le jeu ne s'affiche qu'à partir de deux choix.** Règle unique, portée par
  le service, et qui vaut aussi pour la **ville de présence** — la quatrième liste de ce type.
- **Trois langues** : `fr`, `en`, `ar`.

## Hors périmètre

- **Le contenu des trois volets existants** — présence, projet de retour, disponibilité. Seule la
  ville de présence est touchée, et seulement par la règle des deux choix.
- **Les interrupteurs « pied-à-terre » et « mobilité »**, qui restent dans « Projet de retour » : ils
  disent tous deux comment le retour se prépare, et suivent donc l'horizon.
- **La règle qui masque l'interrupteur quand la recherche ne nomme aucun métier** — conservée telle
  quelle, cf. *Décisions de cadrage*.
- **L'avertissement sous l'interrupteur** — conservé mot pour mot. Le raccourcir sort du périmètre,
  et le cadrage produit l'exige déjà.
- **Le niveau de langue**, absent de l'affinage par décision antérieure (un tiers du vivier porte une
  langue sans niveau). Rien ne change ici.
- **Toute retouche serveur** : ces listes se calculent sur les cartes déjà chargées.

## Décisions de cadrage

**Le secteur passe après les trois volets existants, pas avant.** La lecture littérale de « secteur
en premier » aurait ouvert la pile dessus — écartée : la **présence au Maroc est la promesse du
produit**, et la reléguer en deuxième position n'est pas un choix neutre de mise en page. Le secteur
prend donc simplement la place qu'occupait « Métier et lieu ».

**L'interrupteur garde son avertissement, il ne se réduit pas à deux mots.** La lecture stricte de
« le label c'est *poste actuel* only » aurait aussi supprimé la phrase en dessous. Écartée : le poste
du moment n'est résolu que sur une part du vivier — mesuré ailleurs, **3 profils classés « actuel »
sur une recherche de 41 logisticiens**. Sans cette phrase, cocher se lit comme « filtrer sur du
certain », alors que le geste écarte aussi tous les profils dont on ne sait rien. Le compte entre
parenthèses, lui, part : il double une information que les listes ne portent plus à cet endroit.

**Une liste à un seul choix se masque, comme une liste vide.** Le panneau masque déjà ce qui est vide
— « un intitulé suivi du vide se lit comme un chargement qui n'a pas abouti ». Un seul choix pose le
même problème d'un cran plus haut : la ligne existe, elle attire l'œil, et elle ne resserre rien.
Écarté : afficher toujours, qui aurait gardé une forme de panneau constante d'une recherche à
l'autre — au prix d'un « Pays : FR » permanent sur la majorité des recherches.

**La règle vit dans le service, pas dans le gabarit.** Les quatre fonctions qui produisent ces listes
ne servent qu'à ce panneau et ont déjà leurs tests ; leur métier est « les choix qu'on offre », et un
choix unique n'en est pas un. Écrite dans le gabarit, la règle aurait été répétée quatre fois et
couverte par rien.

**Le volet s'appelle « Secteur », pas « Secteur d'expérience ».** Dans une colonne étroite, un titre
de volet tient sur une ligne à côté de sa pastille et de son chevron. Le libellé long reste celui de
la barre des critères, où la distinction avec le secteur *visé* compte ; ici, aucun secteur visé n'est
offert, l'ambiguïté n'existe pas.

## Règles et cas limites

- **Sans critère de métier dans la recherche, l'interrupteur n'existe pas** et le panneau commence
  directement par « Présence au Maroc ». Règle existante : sans critère de poste, aucun profil ne
  porte de correspondance à interroger, et l'affinage viderait le jeu sans raison lisible.
- **Un jeu à un seul pays** — le cas courant — n'affiche aucune ligne « Pays ». Idem pour la langue,
  le secteur et la ville de présence.
- **Le seuil est sur le nombre de choix offerts, pas sur le nombre de profils.** Deux pays dont l'un
  ne compte qu'un profil restent deux choix : c'est justement là que l'affinage sert.
- **La pastille qui signale un volet fermé porteur d'un filtre** reste sur les volets. Les filtres à
  plat n'en ont pas besoin — ce qu'ils portent est visible sans déplier.
- ~~**Déplier un volet demande le jeu entier**~~ — **caduc**, cf. *Ce que l'implémentation a trouvé
  autrement*. Le jeu arrive en un seul appel à l'ouverture : il n'y a plus rien à déclencher, et les
  listes à plat sont justes dès le premier rendu.
- **Colonne étroite** : les intitulés « Pays » et « Langue » restent au-dessus de leurs boutons, pas
  à côté — 280 pixels ne tiennent pas deux colonnes.
- **Aucun texte en dur** : tout passe par une clé de traduction, dans les trois langues.

## Documentation impactée

- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` — la page devient fausse sur **deux
  points** : elle décrit la colonne comme étant « en volets repliés » alors que trois affinages n'y
  seront plus, et elle nomme l'interrupteur « métier exercé aujourd'hui ». Le reste de sa section
  reste juste, y compris la réserve sur ce que l'interrupteur écarte.
- **Aucune page d'architecture n'est déclenchée** : ni contrat d'API, ni modèle de données, ni
  convention de dossier, ni tokens de design. Le panneau ne change ni de responsabilités ni de
  couche.
- **`webapp/services/README.md`** : à relire, corrigé seulement si la règle des deux choix relève
  d'une convention de couche — a priori non, c'est une règle métier d'affichage.

## Ce que l'implémentation a trouvé autrement

**Le seul risque de la carte n'existait plus.** Entre le cadrage et l'implémentation, `ENT-126` a
supprimé la pagination et, avec elle, le « parcourir tout » de l'affinage : une recherche se charge
désormais en un seul appel. L'événement qui tirait le reste du jeu quand on dépliait un volet, la
fonction qui bouclait sur les pages et l'indicateur de chargement ont quitté le dépôt. Sortir des
filtres de leur volet ne prive donc plus personne de rien — le panneau reçoit le jeu complet dès
l'ouverture. Le point est devenu sans objet plutôt que résolu.

**Une panne dormait dans la liste des pays, et la sortir du volet l'a réveillée.** Le rendu serveur
tombait en erreur 500, page blanche : `undefined is not an object (evaluating 'country.trim')`. Le
contrat annonce `string | null`, la carte arrive parfois **sans la clé**, et la garde ne testait que
`null`. Le défaut était antérieur à cette tâche mais inatteignable : la liste n'était calculée qu'à
l'ouverture du volet qui la contenait. Corrigé en testant le type — exactement le repli que les
langues et les secteurs portaient déjà deux fonctions plus bas, avec son commentaire d'origine.

**Une phrase de la page de cadrage a été corrigée en plus des deux annoncées** : elle disait que le
jeu se charge « d'un coup dès qu'un volet est posé ». C'est la dette d'`ENT-126`, mais elle vit dans
le paragraphe même que cette tâche réécrit, et l'y laisser aurait fait se contredire deux phrases
voisines.

## Vérification

Stack du worktree entreprise, navigateur piloté, connecté en `owner@demo.showcase-recruiter-webapp-saas.example`.

**La colonne, sur une recherche à neuf critères** (80 résultats) — texte relevé dans le panneau :

```
Poste actuel
  Écarte aussi les profils dont le poste du moment n'a pas pu être établi.
Présence au Maroc · Projet de retour · Disponibilité · Secteur
Pays     BE 6 · FR 72 · MA 1
Langue   ar 22 · de 2 · en 44 · es 3 · fr 56 · ru 1
```

L'interrupteur est hors volet, sans compte, avec sa réserve ; le secteur ferme la pile des volets ;
pays puis langue se lisent à plat en dessous.

**La règle du choix unique, sur une recherche dont le jeu ne porte qu'un pays et aucune langue** :

```
Présence au Maroc · Projet de retour · Disponibilité · Secteur
```

Ni « Pays » ni « Langue ». L'interrupteur est absent lui aussi, cette recherche ne nommant aucun
métier — la règle existante tient.

**La panne du rendu serveur**, avant correction, lue dans les journaux du conteneur :

```
TypeError: undefined is not an object (evaluating 'country.trim')
  at refinableCountries (./services/search/refine.ts)
  at _sfc_ssrRender (spaces/entreprise/components/search/RefinePanel.vue)
```

Après correction, la page rend en entier — les deux captures ci-dessus le montrent.

**Tests** :

```
services/search/refine.vitest.ts   Test Files 1 passed · Tests 49 passed (434 ms)
```

## Livré

- `webapp/services/search/refine.ts` **(M)** — les quatre listes calculées passent par un garde-fou
  commun qui rend vide en dessous de deux choix ; la garde des pays porte désormais sur le type.
- `webapp/services/search/refine.vitest.ts` **(M)** — la règle des deux choix sur les quatre listes,
  la deuxième valeur portée par un seul profil, le jeu qui ne porte rien, et la carte sans pays.
- `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` **(M)** — l'interrupteur en tête
  hors volet et sans compte, le volet « Secteur » en dernier, pays et langue à plat sous la pile.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — « Métier et lieu » devient « Secteur »,
  l'interrupteur devient « Poste actuel ».
- `dev-book/cadrage/entreprise/ui/../criteres_de_filtrage_profils.md` **(M)** — la forme de la
  colonne, le nom de l'affinage, la règle du choix unique, et la phrase de chargement héritée.

## Reste à faire (hors périmètre)

- **`webapp/services/README.md`** : relu, aucune convention touchée — la règle des deux choix est un
  arbitrage d'affichage, pas une règle de couche. Vérifié.
- **Aucune page d'architecture déclenchée** : ni contrat d'API, ni modèle de données, ni convention
  de dossier, ni jetons de design.
- **Le compte des interrupteurs « pied-à-terre » et « mobilité »** reste affiché entre parenthèses,
  alors que celui du poste actuel est parti. Cohérence à trancher ailleurs : ces deux-là vivent dans
  un volet, et rien dans cette tâche ne justifiait d'y toucher.

## Définition de terminé

- [x] Le panneau s'ouvre sur l'interrupteur « Poste actuel », hors volet, avec son avertissement et
      sans compte.
- [x] Les volets se lisent : Présence au Maroc, Projet de retour, Disponibilité, Secteur.
- [x] « Métier et lieu » n'existe plus, ni à l'écran ni dans les fichiers de langue.
- [x] Pays puis Langue se lisent à plat sous les volets, chacun avec son intitulé.
- [x] Sur une recherche dont tous les profils partagent le même pays, aucune ligne « Pays ».
- [x] La même règle vaut pour la langue, le secteur et la ville de présence.
- [x] Sur une recherche sans critère de métier, l'interrupteur n'apparaît pas.
- [x] ~~Le chargement du jeu entier se déclenche encore quand il doit~~ — **sans objet** : il n'y a
      plus de déclenchement, le jeu arrive en un seul appel.
- [x] `refine.vitest.ts` couvre la règle des deux choix sur les quatre listes.
- [x] Les libellés existent en `fr`, `en` et `ar`, et le test de parité passe.
- [x] La page de cadrage est à jour sur ses deux points.
- [x] La carte est clôturée et le commit ne porte que cette tâche.
