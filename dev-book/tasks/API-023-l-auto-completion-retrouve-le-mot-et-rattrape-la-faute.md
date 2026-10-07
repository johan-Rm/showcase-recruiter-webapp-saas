# TASK API-023 — api : l'auto-complétion retrouve le mot au milieu et rattrape la faute

- **Créée le** : 2026-08-04.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-04 — « logiciel » et « developeur » proposent enfin quelque chose,
  et « dev » propose exactement ce qu'il proposait avant. 6 tests ajoutés (11 / 63 assertions).
- **Type** : Backend API — recherche, changement de comportement sans changement de contrat.
- **Tests** : fonctionnels, dans `SuggestionTest`, qui traverse déjà toute la pile jusqu'à Postgres —
  mot trouvé au milieu d'un libellé, faute rattrapée en secours, mot trop court non tenté, et
  **dégradation sans les extensions**. Le test pose lui-même `unaccent` et `pg_trgm`, comme celui
  d'`API-016` : elles ne font pas partie du schéma Doctrine.
- **Réf** : `API-009` (la route et le préfixe seul) · `API-013` (l'extension et les index, qui rend
  cette tâche possible et l'annonce nommément) · `API-016` (les accents, qui a écarté le seuil de
  similarité en disant pourquoi) · `ENT-004` (le formulaire qui consomme la route, où le défaut se
  voit) · `SuggestionFinder` ·
  [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md)
- **Aval, nées du même cadrage** : `ENT-045` (le champ dit qu'un mot n'est pas un métier connu — a
  cette carte pour amont **bloquant**) · `API-024` (garder trace des métiers que le dictionnaire
  ignore) · `ENT-044` puis `API-025` (le nombre de profils quitte l'écran, puis la réponse).
  Indépendantes de celle-ci, sauf `ENT-045`.

## Contexte

L'auto-complétion des deux champs de recherche ne propose quelque chose que si le recruteur tape **le
début exact du libellé, sans faute**. Constaté sur la stack réelle, à travers le BFF, avec un compte
recruteur :

```
JOB q=dev           → 5 métiers      Développeur logiciel (293), Business Developer (156)…
JOB q=développeur   → 3 métiers
JOB q=developeur    → []             une lettre manque, la liste se vide
JOB q=logiciel      → []             « Développeur logiciel » existe, et pèse 293 profils
```

**Les deux manques n'ont pas le même poids.** La faute de frappe se répare toute seule quand on la
remarque. Le mot au milieu, non : rien à l'écran ne dit qu'il faut commencer par « développeur » pour
trouver « Développeur logiciel ». Le recruteur voit une liste vide et en conclut que le vivier ne
contient personne — le même contresens que les accents produisaient avant `API-016`.

C'est une dette annoncée deux fois. `API-009` promettait une saisie « tolérante aux fautes et ouverte
à la similarité » et a livré le préfixe seul. `API-013`, qui a posé `pg_trgm` et les index
trigrammes, la désigne comme sa suite directe : « les index trigrammes rendent possible la
similarité […] c'est la tolérance aux fautes qu'`API-009` promettait et n'a pas livrée. Elle devient
une tâche courte, à condition de garder le repli sur préfixe quand l'extension est absente. »
`API-016` l'a écartée en nommant précisément ce qui manquait : « il demande d'arbitrer un seuil, donc
de décider ce qu'est une proposition raisonnable ». **Cette carte est cet arbitrage.**

## Périmètre

- **`SuggestionFinder::matches`** — la comparaison passe du préfixe du libellé au **début de
  n'importe quel mot** du libellé. Sur les intitulés de poste comme sur les compétences.
- **Un second passage, en secours seulement** — si la comparaison exacte ne ramène rien, la même
  requête est rejouée en tolérant les fautes (similarité trigramme).
- **Le classement du secours** — par proximité décroissante, le nombre de profils ne départageant que
  les ex æquo.
- **Le regroupement des compétences** sur la valeur débarrassée de ses espaces de début et de fin.
- **La ligne de `/v1/suggestions` dans `api-routes.md`**, absente depuis `API-009`.

## Hors périmètre

- **Le contrat d'API.** Rien dans la réponse ne dira qu'une proposition est approchée : pas de champ
  ajouté, `api/openapi.json` inchangé, aucune tâche `espace/entreprise` ouverte. Voir *Décisions*.
- **Les qualificatifs entre parenthèses** — « python (langage de programmation) », « python
  (programming language) » et « python » restent trois propositions. C'est une normalisation à
  l'**ingestion**, pas à l'affichage, et elle changerait aussi ce que la recherche par mot-clé trouve.
- **La fusion bilingue des compétences** et **l'index matérialisé des compétences distinctes** — les
  deux autres restes d'`API-009` et `API-013`, intacts.
- **Le filtre de recherche lui-même** (`GET /v1/search/candidates`), qui garde son égalité stricte —
  examiné puis écarté, voir *Décisions de cadrage*.
- **Les index et la commande `app:search:index`**, qui n'ont rien à recevoir : `pg_trgm` est déjà
  posée et sert déjà la comparaison trigramme.

## Décisions de cadrage

### Le début d'un mot, pas une sous-chaîne

Chercher `%terme%` aurait été le geste évident. Il crée des faux positifs **au milieu des mots**, et
il casserait un test existant qui vérifie exactement ça : `testSkillSuggestionsComeFromThePoolAndCarryTheirCount`
affirme que le préfixe cherché « n'est le début d'aucune compétence », en tapant une chaîne qui est
pourtant contenue dans la compétence posée.

La comparaison au **début de n'importe quel mot** — `\m` en expression régulière Postgres — donne le
résultat voulu sans le défaut. Vérifié :

```
« logiciel »                          → Développeur logiciel · Architecte (IT/logiciel)
« k123abc » dans « kubernetes sk123abc » → refusé par le mot, accepté par la sous-chaîne
```

La parenthèse et la barre oblique comptent comme des frontières de mot, ce qui fait entrer
« Architecte (IT/logiciel) » sans règle supplémentaire.

### La tolérance aux fautes est un SECOURS, jamais un mélange

C'est la décision qui désamorce l'arbitrage qu'`API-016` redoutait. On cherche d'abord exactement ;
**si et seulement si** rien ne vient, on rejoue en tolérant.

Trois conséquences, et c'est ce qui rend le choix économique :

- **aucun bruit ne pollue jamais une recherche qui marchait** — le seuil n'a plus à protéger le cas
  nominal, puisqu'il ne s'y applique pas ;
- **le coût n'est payé que quand le recruteur est bloqué** — mesuré à 350 ms sur les compétences,
  contre 120 ms pour la recherche exacte ;
- **le seuil peut être généreux**, puisqu'à ce moment-là il n'y a rien de mieux à montrer.

### 0,5 de proximité, et pas en dessous de cinq lettres

Mesuré sur des fautes réalistes (`word_similarity`, dans les deux sens de la comparaison) :

| Ce qu'on tape | Ce qu'on visait | Proximité |
| --- | --- | --- |
| `javascrip` | javascript | 0,90 |
| `kubernets` | kubernetes | 0,80 |
| `comunication` | communication | 0,80 |
| `developeur` | développeur logiciel | 0,77 |
| `managment` | management | 0,615 |
| `pyton` | python | 0,50 |
| `exel` | excel | **0,40** |

**Le plancher de longueur n'est pas un ornement.** Un mot court porte trop peu de trigrammes pour
être comparé : `exel` ne ramène que du bruit — quatre métiers sans rapport, tous à 0,60. En deçà de
cinq lettres, on ne tente donc rien, et on continue de ne rien proposer plutôt que de proposer
n'importe quoi.

**Un seuil plus sévère aurait raté la moitié des fautes réelles.** À 0,7, `managment` et `pyton`
restent muets. À 0,6, le bruit revient par un autre chemin : « développement » ressemble à
« developeur » à 0,636, ce qui fait entrer *Ingénieur d'études*, *Technicien*, *Commercial terrain*
et *Assistant de recherche* par leurs variantes. Ce bruit-là est écarté par le mode secours, pas par
le seuil — mais il confirme que 0,6 était une frontière instable.

### En secours, c'est la ressemblance qui commande

Le classement par nombre de profils est juste dans le mode normal : les propositions y matchent
toutes également bien, seul le volume les distingue. En secours il devient faux. Mesuré sur
`developeur` :

```
classé par volume       Ingénieur d'études (493, proximité 0,64) en tête,
                        Développeur logiciel (293, proximité 0,82) en deuxième
classé par proximité    Développeur logiciel · Développeur front-end · Développeur mobile
```

Le nombre de profils reste servi et reste le départage des ex æquo — il n'a pas cessé d'être utile,
il a cessé d'être le critère principal.

### La réponse ne dit pas qu'une proposition est approchée

Un champ supplémentaire permettrait au formulaire d'écrire « aucun résultat exact, voici les plus
proches ». Il changerait le contrat, donc `api/openapi.json`, et ouvrirait une tâche
`espace/entreprise` pour l'afficher : **deux lanes pour un bénéfice que la liste porte déjà**. Qui
tape « developeur » et voit « Développeur logiciel » n'a besoin d'aucune explication.

La distinction est nommée ici pour qu'elle reste disponible si l'usage la réclame, pas pour être
construite maintenant.

### Proposer tolérant, filtrer strict — et c'est volontaire

Cette carte rend la **proposition** tolérante. Le **filtre**, lui, exige toujours une correspondance
exacte : le terme doit être, mot pour mot, le libellé canonique d'un métier ou l'un de ses libellés
vivants (`CandidateRepository`, résolution du critère `job`). L'écart a été examiné, et il est
maintenu.

Trois raisons, dans l'ordre de leur poids :

- **Ce ne sont pas les mêmes gestes.** Proposer un terme approchant est une aide : le recruteur voit,
  choisit, garde la main. Filtrer approximativement fait entrer dans les résultats des profils
  **qu'il n'a pas demandés**, sans qu'il l'ait décidé ni qu'il puisse le constater.
- **Une recherche enregistrée est une promesse.** Assouplir le filtre changerait rétroactivement ce
  que ramènent les recherches déjà créées.
- **Ce serait deux endroits à tenir d'accord** — `CandidateRepository` filtre, `CriterionMatcher::job`
  explique pourquoi un profil est là et recalcule les appartenances. S'ils divergent, un profil
  s'affiche pendant que l'encadré « pourquoi ce profil » affirme qu'il ne correspond pas.

**Conséquence assumée, et elle est visible** : un terme libre non reconnu ramènera toujours zéro
profil. Ce n'est pas un défaut du moteur, c'est un défaut de **dialogue** — l'écran ne dit pas qu'il
n'a pas compris la question. Il se répare côté formulaire, et c'est `ENT-045`.

### Les espaces parasites, oui ; les parenthèses, non

Le champ « Mots-clés » propose aujourd'hui cinq fois « python » : `python (langage de programmation)`
263, `python (programming language)` 169, `python` 141, `python ` **avec une espace finale** 5, et
deux variantes à qualificatif.

L'espace finale se règle ici, sur la ligne du regroupement : c'est le **même mot**, et le laisser
dédoubler la proposition n'a aucune justification. Les qualificatifs entre parenthèses sont un autre
problème — ce sont des chaînes différentes, écrites différemment à la source, et les fondre relève de
la normalisation à l'ingestion. Les traiter ici changerait aussi ce que la recherche par mot-clé
trouve, ce qui déborde largement l'auto-complétion.

## Règles et cas limites

- **La dégradation est conservée, et c'est une propriété, pas une politesse.** Sans `pg_trgm`, le
  secours ne se déclenche pas ; sans `unaccent`, la comparaison reste accentuée. Une base de
  production qui n'a pas reçu `app:search:index` sert **moins** de propositions, elle n'échoue pas —
  même contrat qu'`API-013` et `API-016`.
- **La disponibilité des extensions se demande une fois** et se mémorise, comme `unaccent`
  aujourd'hui : une requête de catalogue à chaque frappe serait une de trop.
- **Le terme reste échappé.** Il passe désormais dans une expression régulière : les métacaractères
  d'expression régulière tapés au clavier doivent être neutralisés au même titre que `%` et `_`
  l'étaient pour `LIKE`. Une saisie n'est pas un opérateur — `testWildcardsTypedByTheUserAreNotOperators`
  tient déjà cette règle et doit rester vert.
- **Le plancher de deux caractères ne bouge pas** : en deçà, la route ne propose toujours rien
  (`SuggestionProvider::MIN_TERM_LENGTH`). Le plancher de cinq caractères est **celui du secours**,
  il s'y ajoute et ne le remplace pas.
- **Un concept que personne n'exerce n'est toujours jamais proposé**, y compris en secours : une
  suggestion qui ramène zéro profil reste le pire échec d'une auto-complétion.
- **La normalisation reste dans le SQL, des deux côtés.** Replier les accents en PHP donnerait une
  autre table de correspondance que celle de la base.
- **La proposition rendue reste la valeur telle qu'elle est écrite** : on normalise pour comparer,
  pas pour afficher.
- **Limite assumée — les mots courts.** `pyton` ne remontera pas `python` en tête : la proximité y
  est de 0,50, sous celle de `pytorch` (0,67). Le secours vise la faute d'une ou deux lettres dans un
  mot long, qui est le cas réellement constaté. Il n'est pas un correcteur orthographique.

## Documentation impactée

- **`api-routes.md`** — la ligne `GET /v1/suggestions` **manque** : la route n'est mentionnée nulle
  part dans `docs/`, omission d'`API-009`. Elle est ajoutée ici, puisque c'est le comportement de
  cette route que cette tâche change.
- **Le doc-block de `SuggestionFinder`** — il annonce aujourd'hui « PRÉFIXE SEUL » et « Pas de
  tolérance aux FAUTES ». Les deux deviennent faux.
- **Vérifié, non impacté** : `api/openapi.json` (aucun champ ni paramètre ajouté), `data-model.md`
  (aucune colonne, aucun index nouveau), `api-conventions.md` (la forme de la réponse est inchangée),
  `app:search:index` (rien à poser de plus). Aucun `README.md` de dossier ne couvre
  `api/src/Domain/Search/` — il n'en existe aucun sous `api/`.

## Définition de terminé

- [x] `logiciel` propose `Développeur logiciel` et `Architecte (IT/logiciel)`.
- [x] `developeur` propose les trois métiers `Développeur`, le plus ressemblant en tête.
- [x] `dev` et `développeur` proposent **exactement** ce qu'ils proposent aujourd'hui : le secours ne
      se déclenche pas quand la recherche exacte a trouvé. **Il a fallu une règle de plus pour le
      tenir** — cf. *Écart au cadrage*.
- [x] `exel` ne propose toujours rien — sous cinq lettres, aucune tolérance n'est tentée.
- [x] Le champ « Mots-clés » ne propose plus `python` et `python ` comme deux entrées : 141 + 5
      deviennent 145 porteurs sur une seule ligne.
- [x] Sans `pg_trgm` ni `unaccent`, les suggestions continuent de fonctionner : exactes et
      accentuées, comme aujourd'hui.
- [x] `testWildcardsTypedByTheUserAreNotOperators` et
      `testSkillSuggestionsComeFromThePoolAndCarryTheirCount` passent **sans retouche** — le second
      est précisément celui qui interdit la sous-chaîne brute.
- [x] Le coût est mesuré dans les deux modes, et la décision de ne rien indexer de plus est prise sur
      ce chiffre.

## Vérification

**La vraie classe, exercée sur le vivier réel** (3 052 profils visibles, 158 métiers, 3 358
libellés), à travers une connexion Doctrine ordinaire. Sortie lue le 2026-08-04 :

```
--- ce qui marchait doit continuer de marcher
JOB q="dev"           36 ms  Développeur logiciel (293) · Business Developer (156) · Ingénieur DevOps (17)
                             · Développeur front-end (9) · Développeur mobile (5)
JOB q="développeur"   10 ms  Développeur logiciel (293) · Développeur front-end (9) · Développeur mobile (5)

--- LE MOT AU MILIEU (nouveau)
JOB q="logiciel"      11 ms  Développeur logiciel (293) · Architecte (IT/logiciel) (37)

--- LA FAUTE RATTRAPÉE, en secours (nouveau)
JOB q="developeur"   108 ms  Développeur logiciel (293) · Développeur front-end (9) · Développeur mobile (5)
                             · Business Developer (156) · Ingénieur d'études (493)
JOB q="comunication"  30 ms  Assistant marketing (80) · Community Manager (63) · Responsable marketing (22)
                             · Responsable communication (14) · Ingénieur réseaux (2)

--- le plancher de 5 lettres : aucun rattrapage tenté
JOB q="exel"           6 ms  — rien

--- les compétences suivent la même règle
SKILL q="python"     104 ms  python (langage de programmation) (263) · python (programming language) (170)
                             · python (145) · …
SKILL q="kubernets"  475 ms  kubernetes (77) · azure kubernetes service (aks) (3) · …

--- une saisie n'est pas un motif
JOB q="dev("           6 ms  — rien
JOB q="a<b"            6 ms  — rien
```

Les cinq propositions de `dev` sont **exactement** celles de la capture d'origine, dans le même
ordre. C'est le point le plus important de cette sortie : élargir ne devait rien déplacer.

**Le coût, mesuré des deux côtés.** Le passage exact reste au niveau d'avant (8-36 ms sur les
métiers, ~104 ms sur les compétences). Le secours coûte 108 ms sur les métiers et **475 ms** sur les
compétences — et c'est précisément pourquoi il ne se déclenche qu'à défaut. Aucun index nouveau n'est
posé : ceux d'`API-013` suffisent, et `unaccent` reste inutilisable dans un index.

**`SuggestionTest` : 11 tests, 63 assertions, vert.** Les 5 d'origine passent sans retouche.

**`api/openapi.json` régénéré sur cache vidé : identique.** Aucun champ, aucun paramètre n'a bougé.

## Livré

- `api/src/Domain/Search/SuggestionFinder.php` **(M)** — deux passages au lieu d'un ; comparaison au
  début de n'importe quel mot (`\m`) ; secours par `word_similarity` sous conditions de longueur et
  de seuil ; classement par proximité ; `btrim` sur les compétences ; échappement d'expression
  régulière.
- `api/tests/Functional/SuggestionTest.php` **(M)** — 6 tests : le mot au milieu et le refus de la
  sous-chaîne, l'ordre « commence par » devant « contient », la faute rattrapée en secours seulement,
  le plancher de cinq lettres, la dégradation sans extension, la fusion des espaces de bord.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` **(M)** — la ligne `GET /v1/suggestions`,
  absente depuis `API-009`.

## Écart au cadrage

### Une règle de classement que la carte n'avait pas vue

Le cadrage tenait le « mot au milieu » pour un pur élargissement. Il déplaçait en réalité l'ordre :
mesuré après coup, `dev` remontait **« Ingénieur d'études » (493 profils) en tête**, devant
« Développeur logiciel » (293). Le concept y entre parce qu'une de ses variantes contient
« **dév**eloppement » — la correspondance est juste, mais lointaine, et le volume la rachetait.

Le remède tient en une règle : **ce qui commence par le terme passe devant ce qui le contient**, le
volume ne départageant qu'à l'intérieur de chaque groupe. Le préfixe, qui filtrait avant cette tâche,
devient donc un critère de **classement** — il ne disparaît pas, il change de rôle.

Sans elle, la promesse « `dev` propose exactement ce qu'il propose aujourd'hui » était fausse, et
elle l'aurait été en silence : aucune gate ne voit un ordre qui bouge. C'est la sortie réelle qui l'a
montrée, pas un test.

### `preg_quote` était le mauvais outil

Écrit d'abord avec `preg_quote`, qui échappe pour PCRE. Il protège `<` et `>` — or `\<` et `\>` sont
des **frontières de mot** dans les expressions régulières de PostgreSQL : il les rendait spéciaux au
lieu de les rendre littéraux, l'inverse exact de ce qu'on lui demandait. Remplacé par un échappement
de la liste POSIX, vérifié sur `a<b` et `dev(`.
