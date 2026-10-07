# TASK API-040 — un mot-clé cherche aussi dans les postes occupés, et dit où il a trouvé

- **Créée le** : 2026-08-13.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Cadrée depuis la lane entreprise, implémentée dans la lane `espace/api`.** Le besoin est né sur
  l'écran de recherche ; le manque est entièrement côté serveur.
- **Statut** : ✅ fait le 2026-08-13 — un mot-clé cherche aussi dans les intitulés de poste écrits,
  l'explication a suivi le filtre champ pour champ, et la correspondance dit désormais lequel des
  trois endroits a répondu. Le texte de présentation est ressorti du périmètre sur mesure.
- **Type** : Backend — élargissement d'un filtre de recherche, mise en accord de l'explication qui
  l'accompagne, et enrichissement du contrat de sortie.
- **Tests** : **fonctionnels sur la recherche** (phpunit). Un profil ne portant le terme que dans un
  intitulé de poste écrit est trouvé ; un profil qui ne le porte nulle part ne l'est pas. **Et surtout** : tout profil trouvé par
  le filtre doit voir son mot-clé annoncé comme satisfait — c'est l'invariant que cette tâche risque
  le plus de casser, cf. *Règles et cas limites*. La provenance rendue est vérifiée champ par champ.
- **Réf** : `api/src/Repository/CandidateRepository.php` (`applyFilters`) ·
  `api/src/Domain/Search/CriterionMatcher.php` (`keyword`) ·
  `api/src/Domain/Search/CriterionMatch.php` · `api/src/State/SearchResultsBuilder.php` ·
  `api/src/Entity/CandidateJobTitle.php` · aval côté webapp : la colonne du verdict, qui affiche déjà
  l'extrait et attend le nom du champ.

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, pose un ou plusieurs mots-clés sur
« Nouvelle recherche », puis lit les profils rendus.

**Ce qu'un mot-clé fouille : deux champs.** La requête compare le terme à la **ligne de titre** du
profil et au **texte des compétences**, et à rien d'autre. Le motif écrit dans le code est que le
texte de présentation est vide sur 98,5 % des profils, si bien que « l'ensemble du profil » se
réduisait à ces deux-là.

**Cet argument ne couvre pas les intitulés de poste écrits.** Un recruteur qui cherche un terme
métier figurant dans un poste passé ne trouve rien, alors que la donnée est là — et déjà rangée : à
l'ingestion, les intitulés écrits dans les parcours sont **projetés dans une table dédiée**, celle
qui sert l'auto-complétion du champ métier. Les chercher ne demande aucune nouvelle extraction.

**L'explication existe déjà, elle est seulement muette sur le lieu.** Quand un profil est rendu, la
carte porte déjà, pour chaque critère, s'il est satisfait et **par quel texte du profil** — c'est ce
que l'info-bulle de la colonne du verdict affiche. Pour un mot-clé, ce texte est la ligne de titre ou
la compétence qui correspond. Ce qui manque n'est donc pas l'extrait : c'est **le nom du champ** où
il a été trouvé.

## Périmètre

- **Un mot-clé cherche aussi dans les intitulés de poste écrits**, en plus de la ligne de titre et
  des compétences.
- **L'explication suit le filtre**, champ pour champ : ce qui rend un profil doit pouvoir dire
  pourquoi.
- **La correspondance dit dans quel champ** le terme a été vu, en plus du texte déjà rendu.
- **Le contrat de sortie s'enrichit** de ce nom de champ, et `api/openapi.json` est régénéré.
- **La documentation du contrat suit** — cf. *Documentation impactée*.

## Hors périmètre

- **Les employeurs et les descriptions d'expérience**, écartés le 2026-08-13 après analyse — cf.
  *Décisions de cadrage*. Ils demandent une extraction qui n'existe pas, et posent une question de
  dévoilement que le périmètre retenu ne pose pas. **Tâche à part, non ouverte.**
- **Le texte de présentation**, écarté le même jour **sur mesure** : il est renseigné sur 2 % du
  vivier et coûtait à lui seul autant que tout le reste, faute d'index de recherche floue sur sa
  colonne. Le rattraper demanderait une migration pour un champ presque vide. **Non ouvert.**
- **L'affichage du nom du champ** dans l'info-bulle du webapp : lane `espace/entreprise`, une fois ce
  contrat livré.
- **Les formations, les langues et les contacts**, qui restent hors du champ des mots-clés.
- **Le CV**, qui n'existe pas dans notre base : seules ses métadonnées sont copiées, le binaire reste
  chez Jarvi. Il n'y a aucun texte de CV à fouiller.
- **L'écart entre la façon de chercher et celle de proposer** — sous-chaîne d'un côté, début de mot
  de l'autre —, qui mérite son propre examen.

## Décisions de cadrage

**Les employeurs et les descriptions sont écartés, et ce n'est pas de la prudence de principe.** Le
contrat de la carte de profil porte déjà la règle, avec son motif : l'employeur **n'est jamais
transmis** tant que le profil n'est pas dévoilé, tandis que l'intitulé de poste l'est même masqué,
parce qu'« un intitulé qualifie sans nommer… c'est l'employeur qui ré-identifie ». Une description en
texte libre est pire : elle peut nommer l'employeur sans qu'aucune règle ne puisse le prévoir. Les
inclure aurait donc imposé de masquer un extrait **sans pouvoir garantir le masquage**.

**Le périmètre retenu rend l'extrait sûr PAR CONSTRUCTION.** Ligne de titre, compétences, intitulé
de poste : ces trois champs sont **déjà servis** au recruteur, dévoilé ou non, si bien que l'extrait
ne montre rien que l'écran ne montre déjà. Il n'y a donc aucune règle de masquage à écrire, et aucune
à oublier. C'est ce qui a fait préférer ce
découpage à un périmètre complet assorti d'un filtre de sécurité.

**Aucune nouvelle extraction à l'ingestion.** Le cadrage initial laissait ouverte la façon de rendre
les expériences cherchables — colonne dédiée alimentée à l'ingestion, ou interrogation de la
structure au vol. La question ne se pose plus : la projection des intitulés écrits **existe déjà** et
sert l'auto-complétion. La lecture de la structure brute, elle, est écartée pour la raison décrite
dans les règles ci-dessous.

**Le texte de présentation devait entrer, et la mesure l'a fait ressortir.** Le cadrage l'incluait
au motif qu'une colonne de texte de plus ne coûte rien. C'est faux ici : la base porte des index de
recherche floue sur l'accroche, les compétences et les intitulés, **mais aucun sur cette colonne** —
et une seule branche non indexée fait abandonner les index de toutes les autres. Mesuré sur
3 898 profils : 3 ms sans lui, 53 à 74 ms avec, pour un champ renseigné sur 2 % du vivier. Écarté.

**Le coût restant est assumé, parce que le gain est mesuré.** L'élargissement aux postes fait passer
la recherche de 3 à 65 ms, et c'est vingt fois plus. Mais sur les termes de métier — ceux qu'un
recruteur tape en premier — il trouve jusqu'à près de trois fois plus de profils : « chef de
projet » passe de 96 à 273, « comptable » de 92 à 200, « architecte » de 32 à 58. Soixante-cinq
millisecondes de SQL ne bloquent rien à cette échelle ; l'écart se rediscutera avec la refonte du
moteur que le code annonce déjà.

**La provenance porte le champ ET l'extrait, arbitré le même jour.** L'extrait existe déjà ; seul le
nom du champ manque. Sans lui, « trouvé » ne permet pas de juger si la correspondance est pertinente
ou fortuite.

## Règles et cas limites

- **LE FILTRE ET L'EXPLICATION NE DOIVENT JAMAIS DIVERGER.** Ce sont deux codes séparés qui décident
  de la même chose : l'un ramène le profil, l'autre dit pourquoi. Élargir l'un sans l'autre produit
  l'écran le plus déroutant qui soit — un profil rendu par une recherche, dont la colonne du verdict
  annonce que le critère n'est **pas** satisfait. C'est le risque principal de cette tâche, et il se
  couvre par un test.
- **NE JAMAIS CHERCHER DANS LA STRUCTURE BRUTE D'UNE EXPÉRIENCE.** Les compétences sont une liste de
  textes : les lire d'un bloc est sans danger. Les expériences sont une liste d'**objets**, dont la
  sérialisation contient les **noms des champs**. Les lire ainsi ferait remonter **tout le vivier**
  dès qu'un recruteur tape « poste », « entreprise » ou « description ». La projection dédiée est la
  seule voie.
- **Aucune requête par ligne.** La carte est servie par une requête qui peint une page entière ; les
  intitulés de poste doivent donc être chargés **pour toute la page en une fois**, jamais profil par
  profil au moment d'expliquer.
- **Le premier champ qui répond gagne**, et l'ordre est celui de la lecture d'un profil : ligne de
  titre, compétences, intitulés de poste. Un profil qui correspond par plusieurs champs n'en annonce
  qu'un — celui-là.
- **Les mots-clés restent reliés en OU entre eux, et en ET avec le reste** des critères.
- **La visibilité et le consentement continuent de filtrer en amont** : un profil invisible, inactif
  ou sans consentement ne remonte jamais, quel que soit le champ qui correspond.
- **Élargir élargit** : les décomptes annoncés au recruteur bougeront pour les mêmes critères. Ce
  n'est pas un défaut, mais c'est attendu.
- **Le coût se mesure.** La recherche par mots-clés lit déjà une colonne structurée par parcours
  complet, ce que le code signale comme tenable maintenant et pas durablement. Mesurer l'effet de
  cette tâche fait partie du travail ; corriger la dette de fond n'en fait pas partie.
- **`api/openapi.json` est régénéré dans le même commit** : la CI compare le fichier au contrat et
  échoue sur l'écart, là où le contrôle local ne le voit pas.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` — **la seule page qui
  décrive la portée d'un mot-clé**, et elle était déjà fausse : elle annonçait « `q` = LIKE headline
  seul » alors que les compétences étaient cherchées aussi. Corrigée.
- **Ni `api-routes.md`, ni `api-conventions.md`, ni `data-contracts.md`** : le cadrage les avait
  désignées de mémoire, à tort — aucune ne décrit la structure des correspondances.
- **Pas de `data-model.md`** : aucune entité, aucune colonne, aucune migration — la projection
  utilisée existe déjà.
- **`api/openapi.json` régénéré, et il ne bouge pas.** La structure des correspondances n'est pas
  décrite dans le contrat généré : elle voyage dans une enveloppe de collection dont API Platform ne
  type pas les éléments. C'est pourquoi le webapp écrit ce type À LA MAIN
  (`webapp/shared/types/candidate-card.ts`) — un écart de contrat préexistant, nommé ici, non
  traité.
- Le libellé d'aide affiché au recruteur, « Cherchés dans l'intitulé et les compétences », **a déjà
  été retiré** du webapp (`ENT-108`) : rien à corriger de ce côté.

## Définition de terminé

- [x] Un profil qui ne porte le terme que dans un intitulé de poste écrit est trouvé.
- [x] Un profil qui ne le porte nulle part n'est pas trouvé.
- [x] **Tout profil trouvé voit son mot-clé annoncé comme satisfait** — prouvé par un test.
- [x] La correspondance dit dans quel champ le terme a été vu, et l'extrait reste celui du profil.
- [x] Un terme qui est un nom de rubrique de structure ne ramène pas tout le vivier.
- [x] Les intitulés de poste sont chargés en une fois pour la page, pas profil par profil.
- [x] Les profils invisibles, inactifs ou sans consentement restent exclus.
- [x] Le coût de la requête est mesuré et consigné, avant et après.
- [x] `api/openapi.json` est régénéré (diff vide, et la fiche dit pourquoi).
- [x] Les pages de documentation du contrat sont à jour.

## Vérification

Preuves prises le 2026-08-13. Les tests tournent sur la stack de la lane
(`showcase-recruiter-webapp-saas-api-*`, base propre à ce worktree) ; les mesures de coût sont lues sur la base peuplée du
worktree entreprise (3 898 profils visibles et consentants), en lecture seule.

**Ce que le filtre ramène, et ce que l'explication en dit**

| Ce qui est prouvé | Résultat |
| --- | --- |
| Les trois endroits sont cherchés — accroche, compétences, poste écrit | vert |
| Un profil sans le terme ne remonte pas | vert |
| **Tout profil ramené est expliqué comme satisfait**, pour les trois provenances | vert |
| La provenance annoncée est la bonne, et l'extrait est celui du profil | vert |
| Un nom de rubrique (`companyName`, `description`, `startDate`) ne ramène personne | vert |
| Les postes sont chargés pour la page, pas profil par profil | vert — collections **non initialisées** avant `warmUp`, **initialisées** après |
| Suite ciblée | **4 tests, 26 assertions** |
| Suites de recherche existantes (`SearchTest`, `SearchCountsTest`) | **24 tests, 230 assertions** |

**Le coût, mesuré trois fois de suite sur cache chaud**

| Ce qui est cherché | Temps | Profils sur « vue » |
| --- | --- | --- |
| Accroche + compétences (avant) | **2,5 – 4,6 ms** | 43 |
| + intitulés de poste (livré) | **62 – 68 ms** | 44 |
| + texte de présentation (écarté) | 53 – 74 ms de plus | — |

**Le gain, sur les termes de métier** — c'est lui qui justifie le coût :

| Terme | Avant | Après |
| --- | --- | --- |
| « chef de projet » | 96 | **273** |
| « comptable » | 92 | **200** |
| « architecte » | 32 | **58** |
| « commercial » | 634 | **802** |
| « vue » | 43 | 44 |

**L'index manquant a été testé, puis abandonné avec le champ.** Créé dans une transaction annulée
sur `lower(summary)`, il ramenait le coût de 78 à 57 ms — utile mais insuffisant, pour un champ
renseigné sur 2 % du vivier. Rien n'a été laissé dans la base.

Aucune gate complète n'a tourné : elles appartiennent à l'ouverture de la PR.

## Livré

- `api/src/Repository/CandidateRepository.php` — le filtre des mots-clés cherche aussi dans la
  projection des intitulés de poste ; le commentaire dit pourquoi le texte de présentation en est
  absent, chiffres à l'appui.
- `api/src/Domain/Search/CriterionMatcher.php` — l'explication suit le filtre endroit pour endroit,
  rapporte lequel a répondu, et gagne `warmUp()` qui charge les postes d'une page en une requête.
- `api/src/Enum/MatchField.php` *(nouveau)* — les trois endroits possibles, et la raison pour
  laquelle l'employeur n'en fera jamais partie.
- `api/src/Domain/Search/CriterionMatch.php` et
  `api/src/ApiResource/Output/CriterionMatchOutput.php` — la correspondance porte le champ trouvé.
- `api/src/State/CandidateMapper.php` — le transmet au contrat.
- `api/src/State/SearchResultsBuilder.php` — déclenche le préchargement avant de peindre la page.
- `api/tests/Functional/SearchKeywordFieldsTest.php` *(nouveau)* — les quatre cas ci-dessus.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` — la portée d'un mot-clé,
  qui y était décrite à tort comme « headline seul ».

## Reste à faire (hors périmètre)

- **Le webapp doit ajouter le nouveau champ à la main.** La structure des correspondances n'est pas
  décrite dans le contrat généré — elle voyage dans une enveloppe dont les éléments ne sont pas
  typés —, si bien que `webapp/shared/types/candidate-card.ts` l'écrit à la main. Lane
  `espace/entreprise`, avec l'affichage de la provenance.
- **Les employeurs et les descriptions d'expérience** restent hors de la recherche, avec leur
  question de dévoilement. Non ouvert.
- **Le texte de présentation** attendra un index de recherche floue, donc une migration, pour un
  champ rempli sur 2 % du vivier. Non ouvert.
- **Le coût de fond ne baisse pas.** Le filtre reste une recherche par sous-chaîne sur des colonnes
  lues en entier, ce que le code signale comme « tenable maintenant et pas durablement ». Cette
  tâche l'a mesuré, elle ne l'a pas corrigé.
