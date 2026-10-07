# TASK API-057 — le plan de travail se range du bon côté de la frontière

- **Créée le** : 2026-08-16.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-16 — classe déplacée, correspondances traduites, style rattrapé ;
  la réponse servie est **octet pour octet la même**.
- **Type** : Correction — une fuite de couche livrée par `API-050`, prise au point de contrôle.
- **Tests** : les tests fonctionnels d'`API-050` (`SearchWorkspaceTest`) suffisent et **doivent
  passer sans être retouchés** : ils décrivent la réponse, et la réponse ne change pas. Un test qui
  aurait à bouger signalerait que le déplacement a changé le contrat, ce qui n'est pas le but.
- **Réf** : `API-050` (la route déplacée) · `responsibility-boundaries.md` · `api-structure.md`.

## Contexte

**`API-050` a rangé le constructeur du plan de travail dans le domaine, alors qu'il fabrique de la
sortie d'API.** Le vérificateur de frontières compte **sept violations** : une classe de
`src/Domain/` y importe trois classes de `src/ApiResource/Output/`.

Ce n'est pas une question de goût, c'est la règle que le dépôt fait respecter en machine : `State`
peut dépendre de `ApiResource`, `Domain` **ne peut pas**. Et le voisin immédiat de cette classe —
`SearchResultsBuilder`, qui fait exactement le même travail pour une recherche — vit dans `State`
depuis toujours.

Un second défaut sortait au même moment, du même endroit : **le plan de travail sert l'objet du
domaine là où l'API expose sa version de sortie**. Les deux formes coïncident aujourd'hui sur le fil,
par chance — l'énumération de l'une se sérialise comme la chaîne de l'autre —, mais rien ne garantit
qu'elles continuent : ajouter un champ au domaine le publierait aussitôt, sans passer par le
contrat.

### Pourquoi rien de tout ça n'est sorti au moment de livrer

**La suite qualité s'arrête au premier rouge.** Elle a buté sur l'analyse statique, et le
vérificateur de frontières, qui vient après, n'a jamais tourné. Les trois erreurs signalées par
l'analyse — deux appels sans effet, un type de retour qui ment — **désignaient déjà** la fuite : le
type qui ment est exactement l'objet du domaine servi à la place de sa version de sortie.

## Périmètre

- **Déplacer** `SearchWorkspaceBuilder` de `App\Domain\Search` vers `App\State`, aux côtés de
  `SearchResultsBuilder`.
- **Convertir** les correspondances de critères en objets de sortie, par le même chemin que la fiche
  candidat — la conversion existe déjà, elle est en ligne dans `CandidateMapper` : elle devient une
  fonction nommée que les deux appellent.
- **Retirer** les deux appels sans effet signalés par l'analyse.
- **Passer le correcteur de style** sur les fichiers des trois tâches de la branche : il n'a jamais
  tourné dessus, pour la raison décrite plus haut — sept fichiers, tous issus de ces trois commits.

## Hors périmètre

- **La forme de la réponse.** Rien de ce qui part sur le fil ne change ; c'est le critère qui dit que
  le déplacement est réussi.
- **Les autres classes de `Domain/Search/`.** Le vérificateur ne leur reproche rien.
- **Le fait que la suite s'arrête au premier rouge.** C'est une propriété de l'outil, discutable
  ailleurs — pas ici.

## Décisions de cadrage

### Déplacer, plutôt qu'ouvrir la règle de frontière

Assouplir le vérificateur pour laisser passer ces sept violations ferait sauter le garde-fou pour
tout le domaine, au bénéfice d'une seule classe mal rangée. Le déplacement coûte un import à changer.

### La conversion devient une fonction nommée plutôt qu'une copie

Elle existe déjà, écrite en ligne dans le constructeur de fiche. La recopier dans le plan de travail
créerait deux vérités sur la même traduction, et la prochaine évolution n'en corrigerait qu'une.

## Règles et cas limites

- **Aucun test n'est retouché.** Ils décrivent la réponse ; si le déplacement était correct, ils
  passent tels quels.
- Le service est câblé par découverte automatique : le déplacement n'implique aucune déclaration.

## Documentation impactée

Aucune. `api-structure.md` énonce déjà la règle, `responsibility-boundaries.md` aussi ; c'est leur
application qui a été manquée, pas leur énoncé.

## Vérification

**La réponse est octet pour octet la même**, appelée sur le compte le plus chargé (56 recherches
ouvertes) avant le déplacement puis après, cache vidé entre les deux :

```
AVANT  9b816dbd32fb41d1   2 503 912 octets
APRES  9b816dbd32fb41d1   2 503 912 octets   -> identique
```

Les trois gates que la suite n'avait jamais atteintes :

```
composer analyse    527 fichiers   [OK] No errors
composer cs:check   527 fichiers   aucun fichier a corriger
composer deptrac    Violations 0   (7 avant)
```

Et les tests des trois tâches de la branche, joués ensemble :

```
phpunit --filter 'SearchWorkspaceTest|SearchInactivityClosureTest|AdminOverflowingSearchTest
                  |SearchLoadLimitTest|SearchWorkStateTest'
OK (30 tests, 227 assertions)
```

`api/openapi.json` régénéré : **aucun diff**. Le déplacement est interne, il ne touche pas le
contrat — c'est précisément ce qu'on attendait de lui.

## Livré

- `api/src/State/SearchWorkspaceBuilder.php` — déplacé depuis `Domain/Search/`, imports recâblés.
- `api/src/State/CandidateMapper.php` — la traduction d'une correspondance devient
  `toCriterionMatch()`, appelée par la fiche **et** par le plan de travail.
- `api/src/State/Provider/SearchWorkspaceProvider.php` — l'import suit.
- Quatre fichiers touchés **par le seul correcteur de style**, sans changement de comportement :
  `SearchWorkspaceEntryOutput`, `SearchProfileReviewRepository`, `SavedSearchProcessor`, et les deux
  classes de test.

## Définition de terminé

- [x] Le vérificateur de frontières ne rapporte **aucune** violation.
- [x] L'analyse statique ne rapporte **aucune** erreur.
- [x] Les tests fonctionnels de la route passent — **leur logique est intacte** ; deux d'entre eux
  portent une ponctuation ajoutée par le correcteur de style, et rien d'autre. La formulation
  d'origine de ce critère disait « sans avoir été modifiés » : elle est corrigée ici plutôt que
  cochée de travers.
- [x] La réponse servie est **identique**, comparée avant / après sur le même compte.
