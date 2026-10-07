# TASK ENT-124 — entreprise : la cellule du poste se hiérarchise et se borne

- **Créée le** : 2026-08-14.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff`.
- **Statut** : ✅ fait le 2026-08-14 — la colonne passe de 48 % à 23 % du tableau, le statut file à
  droite en « actuel » / « passé », et les compétences sont des badges neutres à contour.
- **Type** : refactor — la cellule du poste change de hiérarchie, reçoit une largeur, et ses
  compétences passent en badges de la bibliothèque.
- **Tests** : **aucun test nouveau**, et ce n'est pas un oubli. `rowSkillsOf` — le choix des
  compétences et leur ordre — est déjà une fonction pure couverte par `candidate.vitest.ts`
  (`ENT-115`) et **ne change pas** : le « 3 » cesse d'être un compte pour devenir un plafond, ce qui
  se joue à la largeur au rendu et non dans le code. Le reste est du gabarit et des classes, et le
  dépôt n'a aucun harnais pour monter un composant — les suites front ne portent que du code pur.
- **Réf** : `ENT-115` — les trois compétences et leur ordre, qui restent · `ENT-122` — la grammaire
  des deux bandes, en amont · [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) §2, §3
  et §3.0 · [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md) ·
  [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) — la règle
  « icône et libellé, jamais la teinte » ·
  [components/README.md](../../webapp/base/app/components/README.md) — « un écran neuf part de Nuxt UI »

## Contexte

**La colonne du poste occupe 48 % du tableau.** Mesuré au rendu, sur la recherche « Développeur
logiciel · backend » en 1600 px de large :

```
largeur du tableau : 1581 px

    754 px  48 %   POSTE          ← presque la moitié
    198 px  13 %   VILLE ACTUELLE
    192 px  12 %   NOM & PRÉNOM
     84 px   5 %   FENÊTRE MAROC
     78 px   5 %   MOBILITÉ
     73 px   5 %   RETENIR
     63 px   4 %   EXP.
     54 px   3 %   E-MAIL
     47 px   3 %   TÉL.
     38 px   2 %   (sélection)
```

**Et ce n'est pas le poste qui l'élargit, c'est sa seconde ligne.** Sur vingt-cinq lignes mesurées,
l'intitulé — déjà plafonné à vingt-huit caractères — ne dépasse jamais **241 px**, quand la ligne de
compétences monte à **731 px**. Rien ne la borne.

Ça se paie ailleurs : l'e-mail vit sur 54 px et le téléphone sur 47. Ces colonnes affichent « — »
tant que le profil est masqué, mais elles porteront de vraies adresses dès le dévoilement, et des
fenêtres comme « 08–24 juil ».

**La première ligne, elle, mêle trois registres sur un même plan typographique** : l'intitulé du
poste, le statut (« Poste actuel » / « Poste passé ») et, quand un collègue est déjà passé, un badge
« Rencontré ». Tous du même corps, collés les uns aux autres. L'œil ne lit pas « voici le poste, et
voici ce qu'on en sait » — il lit un titre à rallonge, dont la longueur change à chaque ligne. La
colonne cesse d'être balayable, ce qui est pourtant sa raison d'être : *« à cette étape, le recruteur
balaie des colonnes, il ne lit pas des profils »*.

## Périmètre

- **Le titre tient la gauche** et tronque s'il déborde ; le **statut** et le badge **« Rencontré »**
  filent au bout de la ligne, dans cet ordre.
- **Le statut se raccourcit** en « actuel » / « passé » : la colonne s'appelle POSTE et l'intitulé
  est juste à côté, le mot « Poste » y est redondant. Nouvelles clés, dans les trois langues.
- **Les compétences deviennent des badges à contour fin**, neutres, sans fond, et **le dièse
  disparaît** — le contour le remplace.
- **Le badge vient de Nuxt UI** — `UBadge`, en `color="neutral"` et `variant="outline"` —, et le
  composant maison `UiBadge` n'est **pas** touché.
- **La colonne est bornée à vingt-huit caractères**, la largeur que l'intitulé a déjà le droit
  d'occuper. Il tient **autant de badges que cette largeur permet** ; les autres ne s'affichent pas.

## Hors périmètre

- **La Visionneuse.** Son portrait montre les mêmes compétences en texte préfixé d'un dièse, et
  garde ce dessin. Divergence **assumée et nommée** : elle en affiche huit et accentue celles qui ont
  été trouvées, ce qui est un autre arbitrage visuel — huit badges à contour dans une colonne étroite
  n'est pas trois dans un tableau.
- **L'ordre des compétences**, décidé en `ENT-115` — les trouvées d'abord — et inchangé.
- **Les autres colonnes**, qui récupèrent la place sans être redessinées ni élargies à la main.
- **Le tri du tableau**, les profils vérifiés restant en tête.
- **La colonne « Ouvert aux opportunités »**, toujours absente faute de donnée côté serveur.

## Décisions de cadrage

### Le statut reste — c'est sa forme qui était fautive, pas son existence

La demande initiale était de le **supprimer**. La mesure l'a renversée : sur deux recherches réelles
du vivier,

```
352 profils :  rien = 53 %   ·   actuel = 26 %   ·   passé = 20 %
149 profils :  rien = 45 %   ·   actuel = 30 %   ·   passé = 23 %
```

**Un profil sur cinq exerce aujourd'hui un autre métier** que celui qui l'a fait entrer dans la
recherche, et cette puce est le seul endroit du tableau qui le dit. La capture qui a lancé le sujet
montrait deux lignes, toutes deux « Poste actuel » — c'était l'onglet des retenus, qui n'en contenait
que deux.

Ce qu'on corrige est donc le **plan de lecture** : le statut cesse de ressembler à une rallonge du
titre. Supprimer l'information pour régler un défaut de présentation aurait coûté ces 20 %.

> ⚠️ **L'absence ne veut pas dire « passé »**, elle veut dire qu'on ne sait pas — le serveur se tait
> quand le poste du moment est inconnu ou ambigu, ce qui arrive une fois sur deux. Rien ne s'affiche
> alors, et surtout pas « actuel » par défaut.

### La largeur cède sur le NOMBRE de badges, jamais sur la longueur d'un mot

Trois issues étaient possibles quand les compétences débordent.

**Tronquer les libellés** pour en garder trois coûte que coûte : écarté — « Artificial Intelligence
(AI) » devient « Artificial Intell… », et une compétence tronquée se reconnaît mal, alors que c'est
précisément ce qu'on vient y lire.

**Les faire passer à la ligne** : écarté, et le cadrage de la cellule le dit déjà en toutes lettres —
*« c'est la hauteur de ligne qui coûte, pas la largeur de colonne »*, parce qu'une ligne gagnée est
une comparaison de plus faite sans défiler.

**Réduire le nombre** : retenu. Aucun mot n'est jamais coupé, la colonne ne bouge plus d'une ligne à
l'autre, et le nombre de badges varie — ce qui se remarque en balayant, et c'est le prix.

> Ce choix **renforce** l'ordre décidé en `ENT-115` au lieu de l'affaiblir : quand deux badges
> seulement tiennent, ce sont les deux premiers qui survivent, donc les compétences qui ont fait
> entrer le profil quand il y en a.

### Le badge vient de Nuxt UI, pas du kit maison — corrigé à l'implémentation

**Le cadrage avait tranché d'ajouter une troisième forme à `UiBadge`**, en réécrivant au passage le
commentaire qui la refuse. La lecture du README des composants, faite avant d'écrire la première
ligne, a renversé la décision : *« un écran neuf part des composants Nuxt UI ; un `Ui*` existant
n'est remplacé que lorsqu'on touche l'écran qui le porte »*, et surtout *« l'exception se démontre,
elle ne se décrète pas — un `Ui*` neuf ne se justifie que lorsque Nuxt UI ne sait pas produire la
STRUCTURE voulue, pas quand son style ne plaît pas »*.

Or `UBadge` sait produire exactement cette structure : `color="neutral"` et `variant="outline"`
donnent un badge neutre à contour, sans fond. Il est déjà employé dans quatre espaces du dépôt —
`variant="soft"` sur la carte de profil, `variant="subtle"` sur l'écran d'équipe.

**Conséquences** : `UiBadge` n'est pas touché, son commentaire non plus, et le design system ne
gagne aucune convention nouvelle. Le diff se réduit d'autant.

**Ce qui reste vrai du raisonnement d'origine** : le dessin ne vit pas en classes locales à la
cellule. Il vit dans un composant partagé — simplement celui de la bibliothèque plutôt que celui du
kit maison, ce qui sert le même but quand la Visionneuse voudra s'y aligner.

**La charte encadre toujours le dessin** : *« la distinction passe par l'icône et le libellé, pas par
la teinte ; une information répétée sur chaque ligne se distingue par l'icône et le libellé »*. D'où
`neutral` et non une teinte par compétence.

**Le fond plein est écarté** : sur une ligne survolée ou sélectionnée, le fond de la pastille
rejoint celui de la ligne et l'effet s'annule. Un contour tient dans les deux cas.

### Le statut ne descend pas sur la ligne des badges

Il y aurait rejoint les compétences en seconde ligne, laissant la première au seul titre. Écarté : il
serait entré en concurrence de largeur avec elles, et en aurait fait tomber une — sur une colonne
qu'on rétrécit précisément pour que les compétences tiennent.

### 23 % et non 18 % — l'estimation du cadrage ignorait la redistribution

Le cadrage annonçait « environ 18 % ». **C'est 23 %**, et l'écart n'est pas une dérive : borner la
cellule à 241 px libère de la place, et le tableau la **redistribue entre toutes les colonnes**, la
sienne comprise. Le contenu est bien borné comme prévu — la ligne de compétences passe de 731 px à
241 px — mais la colonne reçoit ensuite sa part du reste.

C'est le bon comportement : la fenêtre Maroc double (84 → 171 px), l'e-mail passe de 54 à 90 px et
le téléphone de 47 à 65, ce qui était le but.

### Vingt-huit caractères, et pas trente-six

La colonne prend la largeur que l'intitulé a **déjà** le droit d'occuper : environ 280 px, soit 18 %
du tableau au lieu de 48 %, et près de 470 px rendus aux autres colonnes. Trente-six caractères
auraient laissé tenir trois badges courts plus souvent, mais n'auraient rendu que 390 px — et la
borne n'aurait plus été celle du titre, donc plus rien n'aurait justifié sa valeur.

## Règles et cas limites

- **Aucune compétence** → pas de seconde ligne du tout, comme aujourd'hui : une ligne vide sous
  l'intitulé se lit comme une donnée manquante alors qu'il n'y a rien à manquer.
- **Aucun statut connu** (une ligne sur deux) → rien ne s'affiche à droite du titre, et le titre
  dispose alors de toute la largeur.
- **Un seul badge trop long pour la colonne** → il s'affiche seul et tronque à l'intérieur de son
  contour, faute de quoi la ligne resterait vide alors qu'une compétence existe.
- **Profil « Rencontré »** → le badge d'état teinté et les badges de compétence neutres coexistent
  sur deux lignes différentes : registres distincts, aucune confusion de teinte.
- **Intitulé de repli** — quand le poste manque et que c'est l'accroche qui bouche le trou, elle
  reste en italique atténué, ce que `ENT-115` a posé et qui ne change pas.
- **Arabe** : les noms de technologies ne se traduisent pas et restent latins dans une page en
  écriture droite-à-gauche. L'isolation du sens de lecture, aujourd'hui portée par `bdi`, doit
  survivre au passage en badge.
- **Accessibilité** : le badge neutre ne code rien par la couleur, il porte son libellé. L'ordre de
  lecture dans le document reste titre → statut, quelle que soit la place visuelle du statut.
- **Trois langues** : les deux libellés raccourcis sont posés dans `fr`, `en` et `ar`, sous peine
  d'échec du test de parité.

## Documentation impactée

- [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) — §2, la figure d'anatomie ; §3, la
  ligne de la colonne « Poste / compétences » ; §3.0, l'ordre des compétences, qui devient un plafond
  et non un compte.
> Le design system et le commentaire d'`UiBadge` **ne sont plus impactés** : le badge vient de la
> bibliothèque, aucune convention du kit maison ne bouge. Voir *Décisions de cadrage*.

## Vérification

**Mesuré au rendu**, recherche « Développeur logiciel · backend », fenêtre de 1600 px :

```
                avant      après
POSTE           754 px     366 px      48 % → 23 %
VILLE ACTUELLE  198 px     274 px
NOM & PRÉNOM    192 px     267 px
FENÊTRE MAROC    84 px     171 px      ← doublée
E-MAIL           54 px      90 px
TÉL.             47 px      65 px

ligne de compétences la plus large : 731 px → 241 px   (= la borne du titre)
```

**Le nombre cède, jamais un mot** — compté ligne par ligne sur les huit premières :

```
ligne 1 : 2 visibles sur 3      ligne 5 : 2 sur 3
ligne 2 : 2 visibles sur 3      ligne 6 : 1 sur 3
ligne 3 : 3 visibles sur 3      ligne 7 : 2 sur 3
ligne 4 : 3 visibles sur 3      ligne 8 : 3 sur 3
```

`Python (langage de programmation)` s'affiche en entier ; c'est le badge suivant qui sort du champ.

**Le statut se comporte comme la mesure du cadrage l'annonçait** : la première ligne n'en porte
aucun — le serveur ne sait pas —, les suivantes lisent « actuel », et un profil lit « passé ».

**En arabe**, page en `rtl`, entête `المنصب`, statut `حالي` : les noms de technologies restent
lisibles dans le bon sens **à l'intérieur** de leur badge (`Java 11`, `Spring Boot`, `PostgreSQL`),
et la troncature d'un intitulé pose ses points de suspension à gauche. Le badge promu est le plus à
droite, c'est-à-dire le premier lu.

**Tests** : `vitest run base/i18n/locales/parity.vitest.ts services/search/candidate.vitest.ts` →
**2 fichiers, 32 tests verts en 2,74 s**. Aucun test ajouté, comme la ligne `**Tests**` l'annonce.

## Livré

- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` — la cellule bornée à 28
  caractères, le titre en `flex-1 truncate`, le statut et les badges d'état poussés à droite, et les
  compétences en `UBadge` neutres à contour dans une liste d'une seule rangée visible.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — `search.column.tenure.CURRENT` et `PAST`, les libellés
  courts. Les clés longues du tiroir de profil restent, trois autres écrans les utilisent.
- `dev-book/cadrage/entreprise/ui/vue_la_table.md` — figure d'anatomie, ligne de la colonne, un
  paragraphe sur les deux registres et la largeur, deux décisions validées.

## Reste à faire (hors périmètre)

- **Le statut n'est pas aligné d'une ligne à l'autre** : il colle au titre tronqué, donc sa position
  varie. L'aligner au bord droit de la cellule les mettrait en colonne, au prix d'un blanc après les
  intitulés courts. Vu à l'écran, laissé tel quel.
- **Les intitulés tronqués sont plus fréquents** qu'avant, la colonne étant plus étroite. C'est le
  prix de la largeur rendue aux autres colonnes.
- **La Visionneuse** garde ses compétences en texte préfixé d'un dièse : deux écrans décrivent le
  même profil avec deux dessins tant qu'elle n'est pas touchée.

## Définition de terminé

- [x] La colonne du poste occupe environ **23 %** de la largeur du tableau au lieu de 48 %, mesuré
      au rendu — et le contenu de sa cellule est borné à 241 px, la limite du titre.
- [x] Les intitulés s'alignent à gauche ; le statut est au bout de la ligne — observé. *Le badge
      « Rencontré » suit le même flux, mais aucun profil n'en portait dans le jeu observé.*
- [x] Le statut lit « actuel » ou « passé », et **rien** quand le serveur ne sait pas.
- [x] Les compétences sont des badges à contour fin, neutres, sans fond ni dièse.
- [x] Aucun libellé de compétence n'est coupé en plein mot — sauf le cas d'un badge seul trop long.
- [x] Sur un profil aux compétences longues, il en tient moins — observé (1 à 3 selon les lignes).
      *Que ce soient les promues qui restent découle de `rowSkillsOf`, inchangée et déjà couverte.*
- [x] Un profil sans compétence n'a pas de seconde ligne — *la garde est inchangée depuis
      `ENT-115` et le diff ne la touche pas ; non ré-observée, aucun profil sans compétence dans le
      jeu affiché*.
- [x] En arabe, un nom de technologie reste lisible dans le bon sens à l'intérieur de son badge.
- [x] `UiBadge` n'est pas modifié : le badge vient de `UBadge`, en neutre et à contour.
- [x] `vue_la_table.md` décrit la cellule et le badge tels qu'ils sont.
