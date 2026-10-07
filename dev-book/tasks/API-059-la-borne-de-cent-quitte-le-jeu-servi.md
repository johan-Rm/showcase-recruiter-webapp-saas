# TASK API-059 — la borne de cent quitte le jeu servi

- **Créée le** : 2026-08-17.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-17 — une recherche se parcourt jusqu'à son dernier profil ; cent
  devient un seuil de recommandation. Trois écarts au cadrage, consignés plus bas.
- **Type** : Correction de contrat — une borne dure devient une recommandation, la pagination
  revient.
- **Tests** : fonctionnels phpunit — une recherche de plus de cent profils se parcourt jusqu'au
  dernier, page par page ; le total annoncé reste le nombre réel ; une page au-delà de la fin rend
  une collection vide et non une erreur ; l'ordre du jeu est identique d'un appel à l'autre.
- **Réf** : `API-048` (qui a posé cette borne) · **aval** `ENT-131` (la recommandation affichée) ·
  `API-051` (l'inventaire d'administration, qui lit ce même nombre) · `api-conventions.md`.

## Contexte

**Une recherche de 352 profils s'arrête au centième, et rien ne permet d'aller plus loin.** La borne
posée par `API-048` ne limite pas seulement ce qu'un écran affiche : elle limite ce que le serveur
sert. Les 252 profils restants sont classés, comptés, annoncés — et inatteignables.

L'arbitrage produit a changé. **Cent devient un conseil**, affiché au recruteur quand sa recherche
est large, et non plus un couperet. Le jeu se parcourt jusqu'à son dernier profil.

## Périmètre

- **Retirer la borne du jeu servi.** Une recherche rend tous ses profils, par pages.
- **Rétablir la pagination** sur les résultats : page et taille de page redeviennent signifiantes,
  et le total continue d'annoncer le nombre réel.
- **Conserver le nombre cent comme seuil de recommandation**, exposé dans la configuration publique
  que l'écran lit déjà — c'est la même valeur, elle change seulement de rôle.
- **Reporter ce changement de rôle sur l'inventaire d'administration**, qui lit ce nombre pour
  décider ce qui « déborde ». Le seuil de l'écran de supervision doit rester celui de la
  recommandation, sans quoi les deux diraient des choses différentes de la même recherche.

## Hors périmètre

- **Le rythme de chargement côté écran** — anticipé plutôt que déclenché en fin de liste. Il fera sa
  propre tâche, après discussion.
- **Le texte et le placement de la recommandation** : `ENT-131`.
- **L'ordre du jeu**, qui ne change pas d'une virgule.

## Décisions de cadrage

### Retirer, plutôt que relever

Relever la borne à cinq cents ou mille déplacerait le problème sans le résoudre : il resterait une
recherche pour la dépasser, et le recruteur retrouverait le même mur sans comprendre pourquoi. Une
garde haute subsiste néanmoins par la taille de page maximale, qui protège d'un appel demandant tout
d'un coup.

### ÉCART — le nombre est renommé, contrairement à ce que le cadrage prévoyait

Le cadrage disait de garder le nom et de le corriger plus tard. À l'implémentation, c'est
intenable : une constante appelée « limite de chargement » qui ne limite plus aucun chargement ment
à la première lecture, et elle est lue à trois endroits dont un qui s'en servait comme d'un plafond.
Elle devient donc le **seuil recommandé**, ce qu'elle est. Le changement coûte trois lignes.

### ÉCART — la borne quitte AUSSI les listes du plan de travail

Le cadrage réservait ce point à la tâche suivante. Mais la même constante bornait les listes du plan
de travail, et la renommer sans la retirer de là aurait laissé un « seuil recommandé » servir de
plafond — exactement le mensonge qu'on vient d'éviter. La borne disparaît donc partout où elle
tronquait ce qui est servi, ce qui est le sujet de cette carte.

## Règles et cas limites

- **Une page au-delà de la fin rend une collection vide**, jamais une erreur : un client qui déroule
  ne doit pas rencontrer d'échec là où il n'y a plus rien.
- **Le total reste le nombre réel de correspondances**, indépendant de la pagination.
- **Les listes d'état** — retenus, vus, écartés — continuent de porter sur la recherche **entière**
  et non sur la page servie. C'est ce qui fait que les compteurs ne mentent pas.
- **L'ordre est stable entre deux appels** : sans quoi un profil pourrait apparaître deux fois, ou
  jamais, en changeant de page.

### CE QUI SORT DE CETTE LANE — l'écran de supervision dit maintenant quelque chose de faux

L'inventaire d'administration affiche une colonne « Non montrées », calculée comme l'écart entre le
nombre réel et ce qui est servi. **Plus rien n'est caché** : la colonne ne mesure plus un manque mais
un dépassement du conseil.

Le champ servi garde son nom — le renommer casserait un écran livré, qui vit dans la lane
d'administration. Son sens est documenté côté serveur, et **le libellé de la colonne doit être repris
par une tâche de cette lane-là**. Nommé ici, pas traité ici.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — la route de résultats redevient paginée.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md` — la borne y est décrite comme une
  limite servie ; elle devient un seuil de recommandation.
- `api/openapi.json` régénéré, la surface changeant.

## Vérification

**Appels réels** sur la pile du worktree, recherche à 218 correspondances :

```
page 1, perPage=30   items=30  total=218  resultIds=218      ← la liste s'arrêtait à 100
page 3 (rangs  61-90)   30 profils
page 4 (rangs  91-120)  30 profils      ← traverse l'ancien mur
page 8 (rangs 211-240)   8 profils      ← la queue des 218
page 20                  0 profils      ← vide, pas d'erreur
```

**Les deux lecteurs du seuil**, inchangés dans leur réponse :

```
/v1/config          searchLoadLimit = 100
/v1/admin/searches  19 recherches au-dessus du seuil
/v1/searches/workspace  56 recherches · plus grand jeu = 218 identifiants (100 avant)
```

**Tests impactés** — la classe qui protégeait la borne protège maintenant le parcours :

```
phpunit --filter 'SearchPaginationTest|SearchWorkspaceTest
                  |AdminOverflowingSearchTest|SearchWorkStateTest'
OK (25 tests, 213 assertions)
```

Un test a échoué au premier passage, et c'est lui qui avait tort : il attendait une page pleine là
où le jeu s'arrête à 105 profils. Réécrit pour comparer les **identités** des profils de la page et
non leur seul nombre — une coupe compensée par un décalage serait sinon passée inaperçue.

## Livré

- `api/src/State/SearchResultsBuilder.php` — la constante devient `RECOMMENDED_LIMIT`, la coupe
  disparaît, la liste ordonnée est servie entière.
- `api/src/State/SearchWorkspaceBuilder.php` — les listes du plan de travail ne sont plus tronquées.
- `api/src/State/Provider/ConfigProvider.php` et `AdminOverflowingSearchProvider.php` — les deux
  lecteurs du seuil suivent le nouveau nom.
- `api/src/ApiResource/Output/` — les deux sorties disent ce qu'elles portent désormais.
- `api/tests/Functional/SearchLoadLimitTest.php` → `SearchPaginationTest.php`, réécrite.
- `api/openapi.json` et `webapp/shared/types/api.ts` régénérés.

## Reste à faire (hors périmètre)

- **Le libellé « Non montrées » de l'écran de supervision** ne veut plus rien dire — rien n'est
  caché. À reprendre par une tâche de la lane d'administration ; le champ servi garde son nom pour
  ne pas casser un écran livré.
- **Le rythme de chargement côté écran**, et le conseil affiché au recruteur : leurs propres tâches.

## Définition de terminé

- [x] Une recherche de plus de cent profils se parcourt jusqu'au dernier, page par page.
- [x] Le total annoncé reste le nombre réel de correspondances.
- [x] Une page au-delà de la fin rend une collection vide.
- [x] La configuration publique annonce toujours cent, désormais comme seuil de recommandation.
- [x] L'inventaire d'administration s'appuie sur ce même seuil.
- [x] Tests fonctionnels sur les quatre points ci-dessus.
- [x] Documentation et contrat régénéré à jour.
