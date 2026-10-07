# TASK ENT-115 — entreprise : le tableau montre pourquoi la ligne est là

- **Créée le** : 2026-08-13.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff`.
- **Statut** : ✅ fait le 2026-08-13 — la quatrième colonne s'appelle « Retenir », la cellule du poste
  porte trois compétences dont celles qui ont fait entrer le profil, et l'ancienneté a retrouvé son
  unité.
- **Type** : feat — les colonnes du tableau réalignées sur la maquette validée, et la colonne
  d'action renommée par le geste qu'elle porte.
- **Tests** : le choix des trois compétences affichées devient une **fonction pure** de
  `webapp/services/search/`, où la suite front la ramasse — profil sans compétence, profil sans
  critère satisfait, profil dont plus de trois compétences correspondent, correspondance portant sur
  une facette qui n'est pas une compétence, doublon entre une correspondance et une compétence.
  Aucun test de rendu : ce qui se voit se prouve à l'écran, ce qui se calcule se prouve ici —
  précédent `ENT-110`.
- **Réf** : `ENT-116` — la barre de sélection qui descend au contact des lignes · `ENT-117` — le
  tableau qui devient l'étape « contacter », et dont l'onglet « Tous » **exige** que cette colonne
  reste · `ENT-050` — l'arbitrage « retenus » plutôt que « favoris » ·
  [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) §3 — le tableau des douze colonnes ·
  `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-v23.html` — la cellule de poste à deux
  lignes, et la justification de la quatrième place ·
  [services/README.md](../../webapp/services/README.md) ·
  [components/README.md](../../webapp/base/app/components/README.md)

## Contexte

Un recruteur ouvre le tableau d'une recherche et voit onze colonnes de profils masqués. Rien, sur la
ligne, ne lui dit **pourquoi ce profil est là**. Il a cherché « développeur Java parlant anglais » ;
la ligne affiche un intitulé de poste, une ancienneté, une ville — et les mots qui ont fait entrer le
profil ne sont écrits nulle part. Sur une correspondance partielle — le profil satisfait deux
critères sur quatre — il n'a aucun moyen de savoir lesquels sans ouvrir la fiche, une par une.

La maquette validée avait prévu la réponse : la cellule du poste tient **deux lignes**, l'intitulé
puis trois étiquettes de compétences. L'implémentation n'a gardé que la première.

Dans le même temps, la ligne porte une **étoile intitulée « Favoris »**. Le mot est le mauvais : il
dit un goût là où le produit décrit un **geste**, et l'arbitrage a déjà été rendu en `ENT-050` — « le
mot dit le GESTE du recruteur là où *favori* dit un goût » — en laissant écrit noir sur blanc que
« la Table et la Visionneuse disent encore *Favoris* jusqu'à ce qu'on les touche ». On les touche.

Enfin, l'ancienneté s'affiche `7`. Un nombre nu dans une colonne intitulée « Exp. » : le lecteur
reconstitue l'unité, et la maquette écrivait « 7 ans ».

## Pourquoi la colonne reste, alors que l'écran change de métier

`ENT-117` fait du tableau l'étape où l'on **contacte** des profils déjà choisis : retenir est le
geste de l'étape d'avant, dans la Visionneuse. Le raisonnement mène naturellement à retirer la
colonne — et il est faux, pour une raison qui tient à une autre décision du même cadrage.

**L'onglet « Tous » reste accessible.** L'entonnoir guide sans enfermer : un recruteur doit pouvoir
se rattraper, revoir la recherche entière, y repérer un profil que son parcours avait laissé passer.
Sans étoile, cet onglet devient un cul-de-sac : il y voit un bon profil et n'a **rien** à en faire,
sinon lui dévoiler ses coordonnées directement — c'est-à-dire dépenser un crédit sur un profil qu'il
n'a même pas pu mettre de côté. L'échappatoire pousserait exactement au geste que tout le reste du
cadrage cherche à éviter.

**Et un écran où l'on peut défaire sans pouvoir faire n'a pas d'excuse.** L'étoile bascule dans les
deux sens ; c'est déjà, sur cet écran, le seul moyen de retirer un profil de sa sélection. Retirer la
pose sans retirer la dépose produirait une asymétrie que personne n'a décidée.

Le geste reste donc **secondaire** ici : le geste principal est celui de la Visionneuse, une fiche à
la fois avec tout son détail sous les yeux. Sur le tableau, c'est un rattrapage — et un rattrapage a
besoin d'être possible, pas d'être mis en avant.

## Périmètre

- **La colonne « Favoris » devient « Retenir »**, dans les trois langues. Elle garde sa quatrième
  place — juste après l'expérience —, son étoile et sa bascule dans les deux sens.
- **La cellule du poste passe à deux lignes** : l'intitulé et sa puce « Poste actuel / Poste passé »
  sur la première, jusqu'à **trois étiquettes `#…`** sur la seconde.
- **Le choix des trois étiquettes** part de ce qui a fait entrer le profil : les correspondances de
  critères portant sur une compétence d'abord, dans leur ordre, complétées par les premières
  compétences du profil jusqu'à trois.
- **L'ancienneté retrouve son unité** — « 7 ans » et non « 7 ».

## Hors périmètre

- **La colonne « Ouvert aux opportunités »**, douzième de la maquette : le signal n'existe ni dans
  l'API, ni dans le contrat de la carte candidat, ni dans les données. Elle demande d'abord une tâche
  côté `espace/api`, et une carte `ENT` qui la branchera ensuite.
- **La photo en colonne propre.** La maquette lui donne une colonne ; le tableau la colle au nom.
  Onze colonnes débordent déjà en largeur, et la vignette accolée ne coûte rien à la lecture — la
  fusion reste.
- **Les villes ciblées de la colonne « Mobilité ».** Le contrat n'expose qu'un booléen ; la maquette
  affiche une liste de villes. Écart de données, pas de rendu.
- **Le tri du tableau**, qui reste celui qu'il est : les profils vérifiés en tête, rien de
  négociable par colonne.
- **Le tiroir d'aperçu**, qui reste une surface de **lecture seule**. L'étoile bascule dans les deux
  sens : ajouter un second chemin vers le même geste ne ferait que dédoubler.
- **Le geste en lot**, qui n'existe pas et ne revient pas : voir `ENT-116`.
- **La barre de sélection** et **le métier de l'écran** : `ENT-116` et `ENT-117`.

## Décisions de cadrage

- **Les étiquettes disent la correspondance, pas le profil.** Afficher les trois premières
  compétences telles que Jarvi les a rangées — ce que fait la maquette — donne trois mots dont
  l'ordre n'a aucun rapport avec ce qu'on cherche : sur une recherche précise, les trois peuvent être
  hors sujet, et la seconde ligne devient du bruit qui a coûté une hauteur de ligne. Le serveur dit
  déjà, profil par profil, quels critères sont satisfaits et **par quel mot du profil** ; c'est cette
  information qu'on montre.
- **Le complément existe parce que la correspondance peut être vide.** Un profil entre parfois sur un
  critère qui n'est pas une compétence — un pays, une langue, une fenêtre de présence. Sans
  complément, sa seconde ligne serait vide alors que le profil a des compétences à montrer.
- **Mesuré à l'implémentation : le complément est le cas COURANT, pas le cas de bord.** Sur les 149
  profils de la recherche « Développeur logiciel » + mots-clés `backend` et `php`, la promotion ne
  s'active que sur **39 profils, soit 26 %**. Ce que le serveur reconnaît sur un mot-clé est le plus
  souvent l'**accroche** du candidat — « Software engineer | Backend & DevOps | … » — et non une de
  ses compétences. Le cadrage supposait l'inverse ; l'ordre reste juste, mais il faut lire la
  fonctionnalité comme « les trois premières compétences, sauf quand on sait mieux ».
- **Égalité stricte, et c'est mesuré aussi** : une comparaison insensible à la casse et aux espaces
  n'ajoute **aucun** profil (39 dans les deux cas). La sous-chaîne en ajouterait six — 30 % au lieu
  de 26 —, au prix d'une correspondance approximative sur des noms de technologies. Le code garde
  l'égalité stricte, comme le portrait de la Visionneuse.
- **Seule la facette du mot-clé est écoutée.** Les autres nomment un pays, une langue ou un poste :
  un profil entré sur « Développeur logiciel » ne doit pas voir ce libellé promu en étiquette de
  compétence. C'est aussi la règle que le portrait de la Visionneuse applique déjà.
- **La puce « Poste actuel / Poste passé » reste, et la maquette ne l'avait pas.** Elle est
  postérieure, et elle répond à une question que la maquette ne savait pas encore poser : « ce
  métier, il l'exerce encore, ou il en a changé ? ». Les deux tiennent parce que la cellule était
  déjà prévue sur deux lignes — la puce reste en ligne avec l'intitulé, les étiquettes vont dessous.
- **La colonne garde sa quatrième place, et l'argument de la maquette tient mot pour mot** : *« c'est
  une action qu'on pose en balayant la table, pas une donnée qu'on lit en fin de ligne — elle vit là
  où la décision se prend »*, c'est-à-dire à portée du poste et de l'expérience. La reléguer en fin
  de ligne obligerait à traverser onze colonnes pour un geste qui se décide en deux.
- **Un entête qui nomme un verbe, et c'est délibéré.** Les autres colonnes portent des noms — Poste,
  Exp., Nom & prénom — parce qu'elles montrent une donnée. Celle-ci n'en montre aucune : elle offre
  un geste, et son entête le dit.
- **Le libellé du geste existe déjà** — `search.action.retain` / `release`, utilisés par la
  Visionneuse. Deux surfaces qui portent le même geste le nomment pareil ; seul l'entête de colonne
  est une clé nouvelle.

## Règles et cas limites

- **Profil sans aucune compétence** → pas de seconde ligne du tout. Une ligne vide sous l'intitulé
  se lit comme une donnée manquante alors qu'il n'y a rien à manquer.
- **Profil hors recherche** — le tableau vit toujours dans une recherche, mais le champ des
  correspondances est nullable au contrat : absence traitée comme « aucune correspondance », donc
  repli sur les premières compétences.
- **Correspondance non satisfaite** : le serveur **omet** la clé du mot correspondant, il ne l'envoie
  pas à vide. Une garde sur la nullité laisse passer l'absence — piège déjà constaté sur cet écran.
- **Doublon** entre un mot de correspondance et une compétence du profil : une seule étiquette.
- **Plus de trois correspondances** : les trois premières, dans l'ordre où le serveur les rend.
- **Recherche partagée en lecture seule** : l'étoile est présente mais inactive, comme les autres
  gestes d'écriture de l'écran.
- **Retirer un profil depuis l'onglet des retenus** le fait quitter la vue aussitôt. C'est le
  comportement attendu — mais le geste doit rester annulable sans changer d'onglet : la ligne sort,
  le message de guidage dit ce qui vient de se passer.
- **L'étoile et la case à cocher sont deux gestes distincts sur la même ligne**, et il n'y a aucun
  mode à activer : cliquer l'étoile ne sélectionne pas, cliquer la ligne ouvre le tiroir.
- **Les profils retenus sont ceux de la recherche**, pas du recruteur : deux membres d'une même
  équipe voient et posent les mêmes étoiles.
- **Accessibilité** : les étiquettes sont du texte, pas des images ; l'étoile est un bouton dont
  l'état — retenu ou non — est exposé, pas seulement colorié.
- **Trois langues** : l'entête « Retenir » et, si l'unité de l'ancienneté en demande une, sa clé sont
  posés dans les trois fichiers, sous peine d'échec du test de parité.

## Documentation impactée

- [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) — §3, le tableau des colonnes : la
  quatrième change de nom, la deuxième gagne sa seconde ligne et la règle de choix des étiquettes ;
  §8, la décision « douze colonnes » et le vocabulaire de « Favoriser », qui devient « Retenir ».
- `webapp/services/README.md` — si la fonction pure ajoutée demande une entrée dans son inventaire.

## Vérification

**Capture authentifiée**, stack du worktree entreprise, thème sombre, recherche « Développeur
logiciel · backend » — 149 profils, critères `job: Développeur logiciel` + mots-clés `backend` et
`php`. L'entête de la quatrième colonne lit **RETENIR** ; l'ancienneté lit « 7 ans », « 3 ans »,
« 0 ans », « 14 ans » ; les compétences sont sur une seconde ligne préfixée d'un dièse.

**La promotion se vérifie ligne par ligne, sur des rangs connus d'avance** :

| Profil | Étiquettes rendues | Ce que ça prouve |
| --- | --- | --- |
| Ayoub Rachid | `#Applications PHP` puis ses deux premières | promue depuis le **7ᵉ** rang par le mot-clé `php` |
| Khawla Mabchour | `#PhpMyAdmin` puis ses deux premières | promue depuis le **4ᵉ** rang |
| Martin Dohou | ses trois premières, inchangées | sa correspondance portait sur son **accroche**, écartée |
| Semi Gökol | ses trois premières, inchangées | idem — le filtre ne promeut pas n'importe quoi |

**Mesure sur les 149 profils** : la promotion s'active sur **39 d'entre eux, soit 26 %**. Une
comparaison insensible à la casse et aux espaces en ajoute **zéro** ; la sous-chaîne en ajouterait
six (30 %), au prix d'une correspondance approximative. L'égalité stricte est donc gardée.

**L'étoile pose et retire, vérifié par interaction réelle** dans le navigateur, sur la première
ligne du tableau :

```
onglets AVANT : Tous 149 · Favoris 0      étoile AVANT : « Retenir »
onglets APRÈS : Tous 149 · Favoris 1      étoile APRÈS : « Retenu »
onglets REMIS : Tous 149 · Favoris 0      (donnée reposée comme elle a été trouvée)
```

> ⚠️ **La lecture seule n'a pas été observée.** Elle repose sur la liaison `:disabled="!editable"`,
> que ce diff ne touche pas — c'est une non-régression déduite du diff, pas un fait constaté.

**Tests** : `vitest run services/search/candidate.vitest.ts base/i18n/locales/parity.vitest.ts` →
**2 fichiers, 32 tests verts en 1,15 s**.

## Livré

- `webapp/services/search/candidate.ts` — `rowSkillsOf`, fonction pure : les compétences trouvées
  d'abord, dans l'ordre du serveur, complétées par les premières du profil, plafonnées à trois.
- `webapp/services/search/candidate.vitest.ts` — huit cas : promotion simple, ordre multiple, repli
  quand la correspondance n'est pas une compétence, doublon, plafond, facette ignorée,
  correspondance non satisfaite, profil sans compétence.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` — l'entête de la colonne d'étoile,
  la cellule du poste sur deux lignes, la cellule d'ancienneté avec son unité.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — l'entête `search.column.retain`, aligné sur le
  vocabulaire déjà posé par `search.action.retain`.
- `dev-book/cadrage/entreprise/ui/vue_la_table.md` — la colonne renommée, un § 3.0 sur l'ordre des
  compétences et sa mesure, deux décisions validées.

## Reste à faire (hors périmètre)

- **L'onglet dit encore « Favoris »** face à une colonne « Retenir » — `ENT-117` les réconcilie.
- **« 0 ans » devrait s'écrire « 0 an »** en français. La règle de pluriel vient de la clé partagée
  `search.profile.years`, qu'utilisent aussi la Visionneuse et le tiroir de profil : la corriger
  déborde cette tâche.
- **La colonne « Ouvert aux opportunités »** reste absente — le signal n'existe pas côté serveur.

## Définition de terminé

- [x] L'entête de la quatrième colonne lit « Retenir » dans les trois langues, et plus « Favoris ».
- [x] Depuis l'onglet « Tous », une étoile pose un profil dans les retenus ; il apparaît dans
      l'autre onglet sans rechargement.
- [x] Un second clic sur l'étoile le retire, et la ligne quitte la vue des retenus.
- [x] Une recherche sur « Java » fait apparaître `#Java` en première étiquette des profils entrés
      par ce mot.
- [x] Un profil entré par un critère qui n'est pas une compétence affiche quand même ses trois
      premières compétences.
- [x] Un profil sans compétence n'affiche aucune seconde ligne, et sa hauteur de ligne ne change pas.
- [x] La colonne « Exp. » lit « 7 ans ».
- [x] Le clic sur une ligne ouvre le tiroir d'aperçu, qui ne porte aucun geste.
- [x] Sur une recherche partagée en lecture seule, l'étoile est présente et inactive — *déduit du
      diff, qui ne touche pas la liaison ; non observé*.
- [x] La fonction pure de choix des étiquettes est couverte par les cas listés en tête de carte.
- [x] `vue_la_table.md` dit ce que l'écran fait, pas ce qu'il faisait.
