# TASK API-046 — la recherche accepte de nouveau plusieurs mots-clés

- **Créée le** : 2026-08-13.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-13 — une recherche à plusieurs mots-clés fonctionne de nouveau, un
  test de non-régression à deux mots la retient, et les deux remises en conformité sont faites.
- **Type** : Backend — **correction d'un défaut**, trouvé au point de contrôle et jamais parti de la
  lane. Deux remises en conformité l'accompagnent.
- **Tests** : **un test de non-régression à DEUX mots-clés** (phpunit). C'est le point exact que la
  couverture d'`API-040` manquait : tous ses cas n'en posaient qu'un, et le défaut n'apparaît qu'à
  partir de deux. La preuve d'ensemble est la suite complète, verte après correction.
- **Réf** : `API-040`, dont le commit porte le défaut · `api/src/Repository/CandidateRepository.php`
  · `api/tests/Functional/SearchKeywordFieldsTest.php` · précédents de même nature : `ENT-103`,
  `ENT-106`.

## Contexte

**La branche était prête, et sa propre suite de tests la refusait.** Relevé au point de contrôle
d'ouverture de PR, en une passe : **511 tests, 6 erreurs et 1 échec** — répartis sur quatre classes,
mais **une seule cause**.

**Le défaut.** `API-040` a fait chercher les mots-clés dans les intitulés de poste écrits, par une
sous-requête. Cette sous-requête nomme la table qu'elle interroge, et ce nom était **écrit en dur**.
Or les mots-clés se relient en « ou » **dans une seule et même requête** : deux mots-clés produisent
deux sous-requêtes, donc deux fois le même nom, et la base rejette **la requête entière** — pas
seulement la seconde.

**Conséquence si c'était parti :** toute recherche à plus d'un mot-clé aurait échoué. Or les
mots-clés servent justement à s'accumuler — c'est la seule facette multivaluée du formulaire, et
`ENT-107` vient d'ajouter un geste qui en pose huit d'un coup.

**Pourquoi la couverture d'`API-040` ne l'a pas vu.** Ses quatre cas de test posaient **un seul**
mot-clé chacun. Le défaut n'existe qu'à partir de deux. Ce sont des tests **antérieurs** — ceux qui
vérifient que les mots-clés élargissent la recherche, et le jeu de données de démonstration — qui
l'ont attrapé.

**Rien n'est parti.** Le défaut vit dans un commit non mergé : aucun recruteur ne l'a rencontré, et
il n'y a rien à rattraper en production.

**Deux remises en conformité s'y ajoutent**, relevées par la même passe : deux conversions de type
inutiles, et une méthode appelée sur un type trop général dans un test — elle appartient à la
version paresseuse d'une collection, pas à son interface.

## Périmètre

- **Le nom de la sous-requête porte le rang du mot-clé**, si bien que deux mots-clés ne se
  chevauchent plus.
- **Un test de non-régression à deux mots-clés** entre dans la suite.
- **Les deux conversions de type inutiles** sont retirées.
- **L'assertion de type manquante** est posée dans le test du préchargement.
- **Un bloc de commentaire est réaligné** par le formateur.

## Hors périmètre

- **Tout autre comportement.** Cette tâche ne fait que rétablir ce qu'`API-040` décrivait déjà ;
  aucune règle, aucun contrat, aucune donnée ne change.
- **Le retard d'un commit sur l'intégration** pris pendant ce travail : il se rattrape au prochain
  positionnement de branche, pas ici.
- **La fiche `API-042`**, posée dans ce dossier par une autre session pendant ce travail. Elle n'est
  ni à moi, ni dans ce commit.

## Décisions de cadrage

**La tâche est nommée par le défaut qu'elle corrige, arbitré avec l'utilisateur le 2026-08-13.**
Trois tâches voisines du dépôt s'appellent « la lane repasse ses gates avant la PR », et la
cohérence plaidait pour ce nom. Mais ces trois-là ne corrigeaient que du style : aucune ne cachait
une recherche cassée. Un titre d'entretien courant aurait enterré un vrai bug dans l'historique, là
où un relecteur doit pouvoir lire « il y avait un défaut, il est corrigé, un test le retient ».

**Les deux remises en conformité voyagent avec.** Elles tiennent en une ligne chacune, viennent de
la même passe de contrôle et du même code : les séparer produirait un commit de plus sans rien
clarifier.

**Le correctif porte le rang du mot-clé plutôt qu'un identifiant tiré au hasard.** Le rang existe
déjà dans la boucle — il nomme le paramètre de chaque mot-clé — et il rend le nom lisible dans une
requête qu'on relit à la main.

## Règles et cas limites

- **UN TEST DE MOTS-CLÉS SE POSE À DEUX MOTS AU MOINS.** C'est la leçon de ce défaut, et elle
  dépasse cette tâche : la requête construit **un fragment par mot-clé**, si bien qu'un cas à un
  seul mot ne traverse jamais le chemin où les fragments se rencontrent. Toute couverture future du
  filtre par mots-clés doit en poser plusieurs.
- **Le comportement attendu ne change pas d'un pouce** : un mot-clé cherche toujours dans l'accroche,
  les compétences et les intitulés de poste écrits, et la correspondance dit toujours lequel a
  répondu.
- **Les mots-clés restent reliés en OU entre eux**, et en ET avec le reste des critères.
- **La preuve est la suite COMPLÈTE**, pas les seules classes touchées : c'est précisément parce que
  la couverture avait été choisie trop étroitement que le défaut a survécu jusqu'au point de
  contrôle.

## Documentation impactée

**Aucune.** `API-040` a déjà décrit le comportement dans la page d'inventaire des critères ; le
défaut empêchait de l'obtenir, le correctif le rétablit — rien de ce qui est écrit ne devient faux
ni ne le redevient. Ni contrat, ni entité, ni migration : `api/openapi.json` ne bouge pas non plus.

## Définition de terminé

- [x] Une recherche à deux mots-clés ne lève plus d'erreur et ramène les profils attendus.
- [x] Un test de non-régression à deux mots-clés existe et passe.
- [x] Les quatre classes qui échouaient passent : recherche par mots-clés, jeu de démonstration,
      candidats, et la suite de cette tâche.
- [x] L'analyse statique ne signale plus les conversions inutiles ni la méthode inconnue.
- [x] Le formateur ne trouve plus rien à corriger.
- [x] La suite **complète** est verte, et son résultat est lu — pas son code de sortie seul.

## Vérification

Preuves prises le 2026-08-13 sur la stack de la lane (`showcase-recruiter-webapp-saas-api-*`).

| Ce qui est prouvé | Résultat lu |
| --- | --- |
| Les quatre classes qui échouaient | **43 tests, 333 assertions — OK** |
| Conteneur de services, analyse statique, style, frontières de couches | tous verts, code de sortie **0** |
| Formateur | **0 fichier à corriger** sur 510 |
| Suite **complète** | **511 tests, 3 228 assertions — OK** |

**UNE SUITE COMPLÈTE SE LANCE SEULE, et cette tâche en porte la démonstration.** Une exécution
concurrente — la suite entière en arrière-plan pendant qu'une suite ciblée tournait dans le même
conteneur — a rendu **298 erreurs** `no connection to the server` sur ce même code. Ce ne sont pas
des échecs de test : les deux exécutions se disputaient la connexion transactionnelle partagée du
banc. Relancée seule, la suite est verte. Ne jamais conclure d'un rouge obtenu dans ces conditions.

**Le code de sortie ne suffit pas à conclure**, et c'est la seconde leçon de cette tâche : une
première exécution avait rapporté « 0 » alors que la suite comptait 6 erreurs et 1 échec — le zéro
venait de la commande d'enrobage, pas de phpunit. Le résumé se lit.

## Livré

- `api/src/Repository/CandidateRepository.php` — le nom de la sous-requête des intitulés de poste
  porte le rang du mot-clé, si bien que plusieurs mots-clés cohabitent dans la même requête ; le
  commentaire dit pourquoi un nom fixe ne peut pas marcher.
- `api/tests/Functional/SearchKeywordFieldsTest.php` — un cas à **deux** mots-clés, plus deux
  assertions de type sur le test du préchargement et un bloc de commentaire réaligné.
- `api/src/Domain/Search/CriterionMatcher.php` — deux conversions de type inutiles retirées.

## Reste à faire (hors périmètre)

- **La branche a un commit de retard** sur l'intégration, pris pendant ce travail. Il se rattrape au
  prochain positionnement de branche.
- **Deux fiches d'une autre session** vivent dans ce dossier — `API-042` et `API-044`. Elles ne sont
  ni à moi, ni dans ce commit. Le numéro de cette tâche a d'ailleurs dû être décalé **deux fois**
  pour les éviter : `045` est laissé libre exprès.
