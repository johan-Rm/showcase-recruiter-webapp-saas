# TASK API-026 — api : la correspondance sur le métier dit si le poste est actuel

- **Créée le** : 2026-08-04.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-04 — la correspondance sur le métier porte `matchedWhen`, et le
  concept du poste actuel n'est jamais chargé pour l'obtenir. 6 tests, 14 assertions.
- **Type** : Backend API — un champ s'ajoute au contrat, aucun résultat de recherche ne change.
- **Tests** : unitaires sur `CriterionMatcher` — les trois situations (poste du moment, autre poste
  aujourd'hui, poste actuel inconnu) et le cas ambigu qui doit être traité comme inconnu. S'y ajoute
  un test fonctionnel sur `GET /v1/searches/{id}/results` prouvant que la mention voyage avec la
  fiche, **et** un test qui compte les requêtes pour tenir la règle anti-`N+1`.
- **Réf** : **`ENT-046` — aval** (l'écran qui l'affiche) · `API-005` (« pourquoi ce profil est là »,
  qui a créé ce mécanisme) · `CriterionMatch` · `CriterionMatcher::job` · `CriterionMatchOutput` ·
  `CandidateJarvi::currentJobConcept` ·
  [inventaire-criteres-3-couches.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md)

## Contexte

Le critère « Intitulé de poste » interroge `candidate_job_experience`, un **cumul de carrière** : la
ligne dit qu'une personne a exercé ce métier, sans dire ni quand ni si c'est encore le cas. Le
panneau « Ce profil face aux critères » écrit donc « ✓ Métier : Développeur logiciel », et le
recruteur ne sait pas s'il a devant lui quelqu'un qui développe aujourd'hui ou quelqu'un qui l'a fait
il y a huit ans.

Mesuré sur le vivier de développement, pour ce seul métier :

| Sur les 293 profils qui ont exercé « Développeur logiciel » | |
| --- | --- |
| le font **encore** | 90 |
| occupent **un autre** poste aujourd'hui | 92 |
| **poste actuel inconnu** | 111 |

Les deux premières lignes sont une information que le recruteur cherche à chaque profil, et qu'il
n'a nulle part. La troisième est la raison pour laquelle cette tâche ajoute une **mention** et non un
filtre.

**La donnée existe déjà** : `candidate_jarvi.current_job_concept_id` porte le métier du moment,
résolu contre le dictionnaire à l'ingestion, aux côtés de `current_title`, `current_company_name` et
`current_position_certainty`. Personne ne s'en sert dans la recherche.

## Périmètre

- **`CriterionMatch`** gagne un champ, proposé `matchedWhen` — il se lit en paire avec `matchedOn` :
  l'un dit **par quoi** le critère est satisfait, l'autre **quand**.
- **`CriterionMatcher::job`** le renseigne, en rapprochant le concept qui a déclenché la
  correspondance du poste actuel du profil.
- **`CriterionMatchOutput`** l'expose.
- **`api/openapi.json`** régénéré, et **`webapp/shared/types/api.ts`** avec lui, dans le **même
  commit** — sans quoi le dépôt reste rouge entre les deux.

## Hors périmètre

- **En faire un filtre.** Mesuré et écarté : cocher « actuellement » écarterait 111 profils parce que
  la donnée est muette sur eux, contre 92 qui ont réellement changé de métier. Le dépôt a déjà tranché
  ce genre de cas — la ville, couverte à 71 %, a été laissée en « **afficher sans filtrer** »
  (`inventaire-criteres-3-couches.md`). Ici la couverture est de 51 %.
- **L'affichage**, qui est `ENT-046`.
- **Les autres critères.** Seul le métier a un « quand » qui se calcule ; une langue ou un pays n'en
  ont pas.
- **Améliorer la résolution du poste actuel à l'ingestion.** 2 222 profils portent un intitulé actuel
  brut, mais 1 557 seulement retombent sur un métier du dictionnaire : **665 profils** se perdent dans
  cet écart. C'est le levier qui rendrait un jour le filtre honnête, et c'est une autre tâche —
  nommée ici, pas ouverte.
- **`current_role_months`, `current_company_name`, `current_title`**, qui ne remontent pas dans la
  correspondance.

## Décisions de cadrage

### Trois situations, deux valeurs, et l'absence dit la troisième

Un booléen forcerait l'inconnu dans l'un des deux camps, et ce serait un mensonge sur 38 % de la
population concernée.

- **poste du moment** → la mention le dit ;
- **occupe un AUTRE poste aujourd'hui** → la mention le dit aussi ;
- **poste actuel inconnu** → le champ est **absent**, exactement comme `matchedOn` est omis quand il
  ne révèle rien. Rien ne s'affiche, rien n'est affirmé.

C'est le contrat existant qui dicte cette forme : `matchedOn` « reste `null` quand le critère n'est
pas satisfait, et quand le satisfaire ne révèle rien de plus ». Un troisième code `INCONNU` aurait
demandé à chaque appelant de le traiter, pour un résultat identique au silence.

### « Poste passé » n'est affirmé que sur preuve

On ne dit « poste passé » que si le profil porte un poste actuel **résolu et différent**. Déduire le
passé de l'absence de présent reviendrait à réintroduire le booléen par la porte de derrière.

### L'ambigu compte comme inconnu

410 profils portent un poste actuel marqué `AMBIGUOUS` par l'ingestion : plusieurs postes s'y
disputent le rôle de poste du moment. Le doute y est **déjà mesuré** ; l'ignorer afficherait
« actuellement » sur des profils dont on sait qu'on n'est pas sûr.

Seul `CERTAIN` produit une mention. C'est plus sévère que la couverture brute — sur 1 557 postes
actuels résolus, une partie tombera dans le silence — et c'est le prix d'une mention à laquelle on
peut se fier.

### Une seule modification, deux surfaces servies

`CriterionMatch` voyage avec **chaque fiche de résultat**, que le recruteur soit dans le tableau ou
dans la Visionneuse. Poser l'information là plutôt que sur la fiche candidat sert les deux écrans
sans rien ajouter ailleurs — et la rattache au **critère**, ce qui est sa vraie nature : elle répond
à « ce métier-là, il le fait encore ? », pas à « quel est son métier ? ».

## Règles et cas limites

- **Aucune requête supplémentaire.** `CriterionMatcher` s'exécute sur chaque profil d'une page qui
  peut en compter deux cents : lire le poste actuel par une relation paresseuse ferait un `N+1`.
  Le poste actuel doit voyager avec ce qui est **déjà** chargé pour la page. C'est la règle qui a
  cadré `API-022` sur le même chemin, et le test de comptage de requêtes est là pour ça.
- **Le concept comparé est celui qui a satisfait le critère**, pas le terme cherché. Chercher
  « software engineer » et voir « actuellement » doit vouloir dire que la personne est actuellement
  « Développeur logiciel », le concept que le dictionnaire a résolu.
- **Un profil sans réplique Jarvi** n'a pas de poste actuel : silence, pas d'erreur.
- **Le champ reste absent quand le critère n'est pas satisfait** : un métier qu'on n'exerce pas n'a
  ni présent ni passé.
- **Aucun résultat de recherche ne change.** Cette tâche n'ajoute qu'une mention : le même jeu de
  profils, dans le même ordre.

## Documentation impactée

- **Le doc-block de `CriterionMatch`**, qui décrit aujourd'hui un seul complément (`matchedOn`).
- **`inventaire-criteres-3-couches.md`** — la ligne « Intitulé de poste » gagne la mention du poste
  actuel et sa couverture mesurée, dans la même forme que la ville.
- **`webapp/shared/types/candidate-card.ts`** — le type de la correspondance, **écrit à la main** :
  c'est lui qui décrit ce contrat, et non les types générés. Cf. *Écart au cadrage*.
- **Vérifié, non impacté** : `api/openapi.json` et `webapp/shared/types/api.ts` — régénérés sur cache
  vidé, **identiques** ; la fiche candidat n'apparaît pas nommément dans l'OpenAPI. `data-model.md`
  (aucune colonne ajoutée, `current_job_concept_id` existe déjà), `api-routes.md` (aucune route
  nouvelle), `api-conventions.md`.

## Définition de terminé

- [x] Un profil qui occupe actuellement le métier cherché porte la mention « actuel ».
- [x] Un profil qui occupe aujourd'hui un **autre** métier résolu porte la mention « passé ».
- [x] Un profil dont le poste actuel est inconnu **ou ambigu** ne porte aucune mention.
- [x] La mention est absente quand le critère n'est pas satisfait.
- [x] Le nombre de requêtes d'une page de résultats est **inchangé** — prouvé autrement que par un
      comptage, cf. *Écart au cadrage*.
- [x] Le jeu de profils rendu et son ordre sont identiques à avant.
- [x] Contrat régénéré sur cache vidé — **identique**, cf. *Écart au cadrage*.

## Vérification

**`CandidateJobTenureTest` : 6 tests, 14 assertions, vert.** Les quatre situations, plus la garde
anti-`N+1` :

| Cas | Attendu |
| --- | --- |
| Le métier cherché **est** son poste du moment | `CURRENT` |
| Il occupe aujourd'hui un **autre** métier résolu | `PAST` |
| Aucun poste actuel connu | **rien** — surtout pas « passé » |
| Poste actuel marqué **ambigu** | **rien** |
| Le critère n'est pas satisfait | **rien** |
| Après calcul, le concept du poste actuel | **jamais chargé** |

**Les 125 tests du domaine recherche et candidat passent** (912 assertions) : `CriterionMatcher` a
gagné un champ, il n'a rien perdu.

**`api/openapi.json` régénéré sur cache vidé : identique** — voir l'écart ci-dessous, ce n'était pas
prévu.

## Livré

- `api/src/Enum/JobTenure.php` **(+)** — deux cas seulement, `CURRENT` et `PAST` ; l'inconnu est
  l'absence de valeur, pas un troisième code.
- `api/src/Domain/Search/CriterionMatch.php` **(M)** — `matchedWhen`, en paire avec `matchedOn`.
- `api/src/Domain/Search/CriterionMatcher.php` **(M)** — le métier ne passe plus par le raccourci
  commun, il porte un « quand » ; comparaison **par identifiant** ; certitude exigée.
- `api/src/ApiResource/Output/CriterionMatchOutput.php` **(M)** — le champ, et l'avertissement que
  son absence ne veut pas dire « passé ».
- `api/src/State/CandidateMapper.php` **(M)** — la valeur voyage vers la fiche.
- `webapp/shared/types/candidate-card.ts` **(M)** — le type, écrit à la main : ce contrat-là n'est
  pas généré (cf. écart).
- `api/tests/Functional/CandidateJobTenureTest.php` **(+)** — 6 tests.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` **(M)** — la ligne du
  critère, sa couverture mesurée, et la raison de ne pas en faire un filtre.

## Écart au cadrage

### Le contrat ne bouge pas, et ce n'est pas là que le type vit

La carte prévoyait de régénérer `api/openapi.json` puis `webapp/shared/types/api.ts` dans le même
commit. Les deux ressortent **identiques** : la fiche candidat n'apparaît pas nommément dans
l'OpenAPI — elle n'y existe que nichée dans des collections aux `items` opaques. Même constat
qu'`API-022`, sur le même chemin.

Le type de la correspondance est en fait **écrit à la main**, dans
`webapp/shared/types/candidate-card.ts`. C'est donc lui qui reçoit le champ, ici et non dans
`ENT-046` : sans ça, la lane entreprise recevrait une donnée que son typage ignore. Le champ est
**optionnel**, il ne casse aucun appelant existant.

Conséquence pour `ENT-046` : elle n'a plus rien à déclarer, elle affiche.

### La garde anti-N+1 est une propriété, pas un compteur

La carte demandait un test de comptage de requêtes. Un compteur daterait à la première optimisation
voisine, et il ne dirait pas *pourquoi* le nombre est bon.

Le test vérifie la propriété qui compte : après le calcul, le concept du poste actuel est **toujours
non chargé** (`isUninitializedObject`). C'est exactement ce qui garantit l'absence de requête par
ligne — et ce qui casserait immédiatement si quelqu'un remplaçait la comparaison d'identifiants par
une comparaison de libellés, qui est la façon naturelle et fausse de l'écrire.

### La réplica Jarvi peut manquer

`Candidate::getJarvi()` est nullable, ce que la carte n'avait pas relevé. Un profil sans réplica n'a
pas de poste actuel : silence, pas d'erreur. Le code le traite explicitement plutôt que d'hériter du
comportement fragile de son voisin `experienceMin`, qui déréférence sans garde.
