# TASK API-002 — API : écrire une recherche rend son jeu, et le jeu dit tout ce qu'il contient

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — 27 tests, 216 assertions.
- **Type** : API — surface de contrat.
- **Réf** : task 072 (les recherches, leur jeu matérialisé, le badge de nouveautés) · task 154
  (l'export sur sélection) · task ENT-001 (le chargement du front qui consommera ces réponses).

## Contexte

Lancer une recherche coûtait **trois** allers-retours au client : `POST /v1/searches`, puis
`GET /v1/searches/{id}` pour la fiche, puis `GET /v1/searches/{id}/results` pour le jeu. Les deux
derniers redemandaient ce que le premier venait de produire — le processeur d'écriture **recalcule
déjà** le jeu (`SearchMembershipRecalculator`), il ne le disait simplement pas.

Le jeu lui-même, ensuite, ne se laissait connaître qu'en entier : une page de cartes, et rien pour
savoir ce que contenait le reste. Un client qui veut compter, ou retenir une sélection qui survit au
filtrage, devait tout télécharger.

## Décisions

### `POST` et `PATCH` renvoient la recherche ET son jeu

`SavedSearchWithResultsOutput` : deux sous-objets, `search` et `results`. Poser un critère et voir ce
qu'il ramène est **un** geste, pas deux.

Cela ferme aussi un piège d'ordonnancement que l'appelant devait connaître : servir les résultats
remet à jour le repère de lecture, donc lire la fiche **après** eux rapportait un `newCount` déjà
consommé. L'ordre était à la charge du client ; il est maintenant dans la réponse.

Deux sous-objets et non un aplatissement : ce sont deux ressources distinctes, que le client range à
deux endroits distincts. Les fondre l'obligerait à les redécouper à l'arrivée.

**`DELETE` ne change pas** — il ne renvoie toujours rien. Et la **duplication**
(`POST /searches/{id}/duplicate`) garde sa forme : elle ne recalcule rien, elle copie.

### Une ressource imbriquée devient un IRI si on ne le dit pas

Premier jet : la réponse portait `"search": "/v1/searches/019fa5…"`. `SavedSearchOutput` **est** une
ressource, et le sérialiseur remplace par défaut une ressource imbriquée par son lien — soit
exactement l'aller-retour qu'on voulait supprimer. `#[ApiProperty(readableLink: true)]` sur la
propriété rétablit l'objet complet.

C'est le premier `ApiProperty` du projet : la note est dans le code, elle se reposera au prochain
output composite.

### Le jeu porte la liste COMPLÈTE de ses identifiants

`SearchResultsOutput` remplace `CollectionOutput` sur `/results` et ajoute `resultIds` : **tous** les
candidats du jeu, dans l'ordre, pagination comprise.

Deux besoins différents, deux poids différents. Les cartes peignent un écran — on n'en affiche jamais
plus d'une page, et l'une pèse ~700 octets. Les identifiants servent à raisonner sur le jeu entier :
compter, cocher, savoir ce qu'il reste à charger — et l'un pèse 36 octets. La liste complète d'une
recherche à 5 000 profils tient ainsi dans quelques dizaines de Ko.

Enveloppe propre et non un champ ajouté à `CollectionOutput` : celle-ci est partagée par une quinzaine
de collections que cette liste ne concerne pas.

### Le même ordre des deux côtés, garanti par un seul `ORDER BY`

`page()` et `orderedCandidateUuidsFor()` partagent désormais un `ordered()` privé. Sans ordre
identique, la page 2 du client ne serait pas la page 2 du serveur, et un rang dans la liste ne
désignerait pas la personne de la carte du même rang. Deux copies de ce tri auraient divergé au
premier ajustement.

### Le calcul du jeu quitte le provider

`SearchResultsBuilder` : il a maintenant deux appelants — la lecture du jeu et l'écriture d'une
recherche. Il **lit** le repère de lecture sans y toucher, et laisse l'appelant décider s'il y a eu
consultation : lire un jeu n'est pas toujours le regarder, et l'écriture a déjà posé le sien.

Le provider se réduit à ce qui est sa vraie responsabilité : autoriser, lire la requête, marquer vu.

### L'écriture ne renvoie que la première page

Une création n'a pas à peser tout le jeu. Le client a `resultIds` pour savoir ce qui lui reste, et
`total` pour l'annoncer tout de suite.

## Vérification

`SearchTest`, `SearchListsTest`, `SearchExportTest` : **27 tests, 216 assertions**, verts. Dont deux
neufs :

- l'écriture rend un jeu dont `total` est celui de la fiche, dont `resultIds` porte tout le jeu, et
  dont aucune carte n'est « nouvelle » — on ne découvre pas ce qu'on vient de composer ;
- `?perPage=1` sur un jeu plus grand : **une** carte servie, `resultIds` complet, et le premier
  identifiant de la liste est celui de la première carte.

## Reste à faire (hors périmètre de cette tâche)

- **`items` n'est pas typé dans le contrat** : `SearchResultsOutput.items` sort en
  `(string | null)[]`, la générique `list<TItem>` n'étant qu'un phpdoc. Ce n'est pas une régression —
  les quinze `CollectionOutput` du contrat ont exactement le même défaut depuis toujours, et le front
  retype à la main. À traiter pour toutes en même temps ou pas du tout.
- **Le front appelle encore trois fois** : il ne consommera ces réponses qu'avec ses stores
  normalisés et son orchestrateur (lane entreprise).
- **Compteurs facettés** — toujours absents, cf. task 164.
