# TASK API-010 — API : filtrer sur les années d'expérience

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 5 tests, 19 assertions.
- **Type** : API — moteur de recherche.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28 · task 164 (le manque, déjà relevé).

## Contexte

Le formulaire propose un champ « Années d'expérience », et **il ne filtre rien**. Le moteur connaît
`experienceSector` (un secteur) et `educationLevel`, pas de borne d'expérience.

Les plages du formulaire — `0_2`, `3_5`, `6_10`, `10_PLUS` — sont une **invention du front**, sans
équivalent serveur : leurs codes ne partent nulle part. Le champ donne l'illusion d'un critère.

La donnée existe pourtant : `candidate_jarvi.experience_years`, dérivée du parcours à l'ingestion, et
déjà servie sur la carte.

## Décision

Un critère de **borne basse**, exprimé en années, aligné sur ce que l'écran devient : un curseur où
**`0` signifie « toutes les expériences »**. Pas d'état vide à construire — la borne basse porte le
« peu importe ».

Une borne haute n'est pas retenue : on cherche « au moins tant d'années », jamais « pas plus de ».

### La facette s'appelle `experienceMin`

Elle traverse les trois portes d'entrée : la recherche enregistrée (traducteur de critères), le
moteur direct (`?experienceMin=`) et l'explication de correspondance. Le nom dit la borne, pas la
plage — il n'y a pas de borne haute, et il n'y en aura pas tant que personne ne cherche « pas plus
de tant d'années ».

### Zéro veut dire « peu importe », partout

`null` et `0` sont traités pareil : aucun filtre. C'est ce qui permet au curseur de n'avoir pas
d'état vide à construire — sa borne basse porte le « peu importe ».

## Vérification

- Un minimum de 5 ans retient le profil à 11 ans, écarte celui à 2 ans, **et écarte celui dont
  l'ancienneté n'a pas pu être dérivée** (elle vaut `0`) : on ne prête pas une expérience qu'on
  ignore, même règle que le niveau d'études.
- `0` ne retire personne.
- `CandidateKeywordSearchTest` : **5 tests, 19 assertions**.

## Hors périmètre

Le curseur et ses bornes d'affichage → espace entreprise.
