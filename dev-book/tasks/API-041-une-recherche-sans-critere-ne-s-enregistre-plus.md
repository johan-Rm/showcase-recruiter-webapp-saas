# TASK API-041 — une recherche sans aucun critère ne s'enregistre plus

- **Créée le** : 2026-08-13.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-13 — le serveur refuse une recherche sans aucun critère, à la
  création comme à la modification, et le refus arrive nommé sur le champ des critères. Une
  modification qui ne parle pas des critères passe toujours.
- **Type** : Backend — contrainte de validation sur une entrée d'API. Aucune entité, aucune
  migration, aucune règle métier nouvelle : c'est une règle **déjà appliquée** par l'écran qui
  descend là où elle tient vraiment.
- **Tests** : **fonctionnels** (phpunit). Une création sans critère est refusée en 422 ; une
  modification qui vide explicitement les critères l'est aussi ; une modification qui ne parle pas
  des critères passe, puisque « absent » veut dire « inchangé » ; une recherche normale s'enregistre
  comme avant. Le refus doit porter sur le **champ** des critères, pas sur le formulaire entier.
- **Réf** : `api/src/ApiResource/Input/SavedSearchInput.php` ·
  `api/src/State/Provider/ValidationRulesProvider.php` (qui publie les règles au webapp) ·
  `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` (le garde-fou d'écran, inchangé).

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, enregistre une recherche depuis
« Nouvelle recherche » ou la retouche depuis « Modifier la recherche ».

**Le garde-fou existe, mais au mauvais endroit.** L'écran éteint son bouton tant qu'aucun critère
n'est posé, et refuse d'enregistrer avec un message. Le **serveur, lui, n'exige rien** : le champ des
critères est facultatif et ne porte aucune contrainte de nombre. Un appel direct à l'API enregistre
donc une recherche vide.

**Ce qu'une recherche vide veut dire.** Aucun critère, c'est aucun filtre : le jeu de résultats est
le vivier entier. Elle s'affiche comme une recherche normale, elle compte pour une recherche
normale, et rien n'indique qu'elle n'a jamais rien demandé.

**Rien n'est à rattraper.** Sur les **71 recherches enregistrées** de la base peuplée, **aucune** n'a
zéro critère. La contrainte peut donc entrer sans rendre quoi que ce soit d'existant immodifiable.

## Périmètre

- **L'entrée d'une recherche enregistrée exige au moins un critère** quand elle en parle.
- **La règle vaut à la création comme à la modification**, les deux passant par la même entrée.
- **Le refus porte sur le champ des critères**, pas sur le formulaire entier.
- **Le message dit le geste** : « Ajoutez au moins un critère à votre recherche. »
- **`api/openapi.json` est régénéré** si le contrat généré s'en trouve changé.

## Hors périmètre

- **L'écran, qui garde sa règle écrite en dur.** Arbitré le 2026-08-13 : la duplication est minime,
  elle refuse à la frappe sans aller-retour serveur, et la faire dériver de la règle publiée
  demanderait une seconde tâche dans une autre lane pour un bénéfice faible sur une règle aussi
  simple.
- **Les autres chemins de création** — duplication d'une recherche, jeu de démonstration, données de
  développement. Aucun ne passe par cette entrée, et tous portent déjà des critères : une
  duplication copie ceux de sa source.
- **Le sort d'une recherche déjà enregistrée sans critère.** Il n'y en a aucune ; le cas ne se pose
  pas et rien n'est écrit pour lui.
- **Le nombre minimal de critères au-delà de un.** Exiger un poste, ou un critère « qui restreint
  vraiment », est une autre décision — elle change le parcours du recruteur, celle-ci non.

## Décisions de cadrage

**Une contrainte déclarative, pas un contrôle dans le processeur, tranché à l'implémentation près.**
L'écart entre les deux n'est pas stylistique. Un point d'accès **publie les contraintes de cette
entrée au webapp**, précisément pour qu'il n'ait pas à les recopier ; et le webapp sait déjà
transformer un refus de validation en message par champ. Une contrainte déclarative se propage donc
toute seule et arrive déjà rangée en face du bon champ, là où un contrôle écrit à la main ne
produirait qu'un refus opaque.

**Le message dit le geste plutôt que la règle, arbitré avec l'utilisateur le 2026-08-13.**
« Ajoutez au moins un critère à votre recherche » suit la tournure des messages voisins de l'API —
« Sélectionnez au moins un profil à exporter. » — et se lit sans avoir à traduire une règle en
action.

**L'écran garde sa règle en dur, arbitré le même jour.** Faire dériver le bouton de la règle publiée
serait plus pur, mais c'est beaucoup de mécanique pour « au moins un », dans une autre lane, et on
perdrait le refus immédiat qui évite un aller-retour.

**On ne demande pas plus qu'un critère.** L'idée d'exiger un poste — ou un mot-clé — a été pesée et
écartée le même jour : un mot-clé **élargit** la recherche au lieu de la restreindre, l'obliger
serait une contradiction, et il passe par le vocabulaire le moins normalisé et le plus coûteux du
formulaire. Exiger un **poste** se défendrait mieux, mais c'est un changement de parcours et non un
garde-fou : cette tâche ne fait que descendre au serveur la règle que l'écran applique déjà.

## Règles et cas limites

- **« Absent » ne veut pas dire « vide ».** Sur une modification, ne pas parler des critères signifie
  « n'y touche pas » et doit continuer de passer. Seule une liste **explicitement vide** est refusée.
  C'est exactement le comportement d'une contrainte de nombre sur un champ facultatif, et c'est ce
  qui la rend juste ici.
- **Le refus est une 422**, comme les autres refus de validation de l'API, et il nomme le champ.
- **Aucune recherche existante ne devient immodifiable** : vérifié, aucune n'est vide.
- **La duplication reste possible** : elle copie les critères de sa source, qui en a par
  construction.
- **Le jeu de démonstration et les données de développement ne passent pas par cette entrée** et ne
  sont donc pas contraints — ils fabriquent leurs recherches directement, avec leurs critères.

## Documentation impactée

- **`api/openapi.json`** — régénéré, et **il bouge** : la contrainte se traduit par une borne
  `minItems: 1` dans le schéma généré, aux deux endroits où l'entrée est décrite. Vérifié, pas
  supposé.
- **Aucune page de `docs/showcase-recruiter-webapp-saas/`.** `api-conventions.md` a été relue : elle n'énonce nulle part
  les règles de validation des entrées, elle n'a donc rien à corriger. Ni entité, ni colonne, ni
  migration, donc aucune page de modèle de données non plus.

## Définition de terminé

- [x] Une création sans aucun critère est refusée en 422.
- [x] Une modification qui vide explicitement les critères est refusée de la même façon.
- [x] Une modification qui ne parle pas des critères passe toujours.
- [x] Une recherche normale s'enregistre et se modifie comme avant.
- [x] Le refus nomme le **champ** des critères, et porte le message convenu.
- [x] La règle apparaît dans les contraintes publiées au webapp.
- [x] `api/openapi.json` est régénéré, et la fiche dit s'il a bougé ou non.
- [x] Les suites de recherche existantes passent toujours.

## Vérification

Preuves prises le 2026-08-13 sur la stack de la lane (`showcase-recruiter-webapp-saas-api-*`), par de **vrais appels
HTTP** traversant le noyau et la base.

| Ce qui est demandé au serveur | Réponse observée |
| --- | --- |
| Créer une recherche avec zéro critère | **422**, violation sur `criteria`, message « Ajoutez au moins un critère à votre recherche. » |
| Créer une recherche avec un critère | **201**, comme avant |
| Vider les critères d'une recherche existante | **422** |
| Renommer sans parler des critères | **succès** — « absent » veut bien dire « inchangé » |

**Le corps du refus, lu tel quel** :
`{"status":422,"violations":[{"propertyPath":"criteria","message":"Ajoutez au moins un critère à
votre recherche."}]}` — c'est cette forme que le webapp sait déjà ranger en face du bon champ.

**La règle est publiée au webapp** : la contrainte apparaît bien dans les métadonnées de validation
du champ (`debug:validator`), et le point d'accès qui sert les règles parcourt les contraintes de
chaque champ.

**Le contrat généré a bougé** : `minItems: 1` apparaît aux deux endroits où cette entrée est
décrite. Régénéré et commité.

**Tests** : 4 nouveaux (14 assertions), et les suites de recherche existantes restent vertes —
`SearchTest` + `SearchKeywordFieldsTest`, **23 tests, 188 assertions**.

Aucune gate complète n'a tourné : elles appartiennent à l'ouverture de la PR.

## Livré

- `api/src/ApiResource/Input/SavedSearchInput.php` — une contrainte de nombre sur les critères, avec
  le motif écrit : pourquoi une recherche vide n'en est pas une, et pourquoi `null` doit continuer de
  passer.
- `api/tests/Functional/SavedSearchRequiresCriteriaTest.php` *(nouveau)* — les quatre cas, dont
  celui qui compte le plus : une modification qui ne parle pas des critères ne doit rien exiger
  d'eux.
- `api/openapi.json` — la borne `minItems: 1`, régénérée.

## Reste à faire (hors périmètre)

- **L'écran garde sa règle écrite en dur**, ce qui est assumé : il refuse à la frappe sans
  aller-retour serveur. La faire dériver de la règle publiée serait une seconde tâche, lane
  `espace/entreprise`, pour un bénéfice faible sur « au moins un ».
- **Le retour arrière après enregistrement** n'annule rien : la recherche est créée dès le
  lancement, et revenir en arrière laisse une recherche derrière soi sans le dire. Nommé le même
  jour, **non tranché** — la question est de savoir si une recherche doit se créer au lancement ou
  seulement quand le recruteur la garde.
- **Exiger un poste** — plutôt qu'un critère quelconque — reste une décision ouverte. Ce serait un
  changement de parcours, là où cette tâche n'a fait que descendre au serveur la règle que l'écran
  applique déjà.
