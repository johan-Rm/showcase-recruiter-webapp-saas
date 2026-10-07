# TASK API-060 — la route d'état ne porte plus que l'état

- **Créée le** : 2026-08-17.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-17 — la route ne porte plus que cinq listes d'identifiants, et pèse
  23 fois moins. Un écart au cadrage, consigné plus bas.
- **Type** : Correction de contrat — une route qui portait deux responsabilités n'en garde qu'une.
- **Tests** : fonctionnels phpunit — la réponse ne contient ni fiche ni explication ; elle porte les
  listes de toutes les recherches ouvertes, sans borne ; une recherche close en est absente ; un
  recruteur ne voit que ce qui lui est visible.
- **Réf** : `API-050` (qui a créé cette route) · **aval** `API-061` (les fiches, qui en sortent) ·
  `API-062` (les explications, qui en sortent) · `ENT-130` (l'écran qui la consomme).

## Contexte

**La route du plan de travail transporte 3 méga-octets pour livrer 90 kilo-octets d'information de
travail.** Mesuré sur un compte réel à 55 recherches ouvertes : les fiches pèsent 52 % de la
réponse, les explications 43 %, et les listes qui disent qui appartient à quoi, 4 %.

Or ces trois natures n'ont ni le même coût ni le même rythme. Un identifiant coûte 39 octets, une
explication 601, une fiche 1 191. **Les mélanger force à payer le plus cher pour obtenir le moins
cher.**

## Périmètre

- **La route ne rend plus que l'état** : pour chaque recherche ouverte, la liste ordonnée de ses
  profils, et ce que le recruteur en a fait — retenus, écartés, vus, **et nouveaux**.
- **Les fiches et les explications en sortent**, et n'y reviennent pas.
- **Aucune borne sur les listes** : elles décrivent la recherche entière, cohérentes avec `API-059`.
- **Les retenus sont servis dans l'ordre du classement de la recherche** — les mieux appariés
  d'abord —, et non dans celui de leur enregistrement.
- **Les nouveaux profils sont servis en liste**, comme les trois autres. Ils sont aujourd'hui la
  seule des cinq listes qui n'a pas d'équivalent servi.

## Hors périmètre

- **Les routes qui prennent le relais** : elles ont leurs propres tâches.
- **Le choix des recherches** : la route continue de servir toutes les recherches ouvertes du
  recruteur, sans en sélectionner.

## Décisions de cadrage

### L'ordre des retenus vient d'ici, et c'est ce qui supprime deux appels

**Constaté au navigateur** : à l'ouverture d'une recherche, deux appels partent — les fiches des
retenus et celles des écartés — et n'apportent rien. Sur une recherche à quinze retenus, les quinze
identifiants sont déjà connus et les quinze fiches déjà en mémoire. Le seul apport de ces appels est
un **ordre** : celui du classement de la recherche, qui n'est pas celui que le plan de travail sert.

Servir les retenus dans le bon ordre depuis cette route rend l'appel des retenus inutile, et celui
des écartés conditionnel à la présence d'écartés. Sans ce détail, les supprimer changerait l'ordre
de l'onglet des retenus en silence.

### Les cinq listes décrivent la recherche ENTIÈRE, sans exception

C'est la raison d'être de cette route : **l'écran ne doit jamais déduire un compte de ce qu'il a
chargé**. Quatre des cinq listes le respectent déjà ; la cinquième, celle des nouveaux profils, est
reconstituée côté écran à partir des fiches reçues. Elle vaut donc « les nouveaux parmi ce qui est
chargé » — presque juste avec cent fiches, franchement faux avec trente.

La servir ici est ce qui rend la règle tenable : après cette tâche, **aucune liste d'état ne se
déduit plus d'un chargement partiel**.

### Toutes les recherches ouvertes, et non les seules affichées

L'accueil n'en montre que six, et ses compteurs viennent de la collection, pas d'ici : on aurait donc
pu restreindre cette route aux recherches visibles, et passer d'environ cent kilo-octets à dix.

Écarté. Depuis la page qui liste les recherches, **n'importe laquelle des soixante-quatre peut être
ouverte** : restreindre l'état obligerait à le redemander avant d'afficher, et introduirait un cas
particulier — l'ouverture rapide ici, l'ouverture lente là. Cent kilo-octets d'identifiants, rapportés
aux trois méga-octets que ce chantier supprime, ne valent pas cette complication.

### Alléger sur place plutôt que créer une route neuve

L'adresse ne change pas : deux façons de servir la même donnée finiraient par diverger, et le
contrat généré pour l'écran suit tout seul.

### Les listes ne sont plus bornées

Mesuré : toutes les listes de 55 recherches ouvertes pèsent environ 90 kilo-octets sans borne,
contre 90 avec. Les recherches de ce compte font 51 profils en moyenne, la plus grosse 352 — la
borne ne changeait presque rien au poids, et la retirer rend la réponse cohérente avec un jeu qui se
parcourt entièrement.

## Règles et cas limites

- **Les recherches closes sont absentes** : le plan de travail décrit ce sur quoi on travaille.
- **Un recruteur ne voit que ses recherches et celles que son équipe a partagées** — inchangé.
- **Un jeu vide reste une entrée**, avec ses listes vides : distinguer « aucun profil » de
  « recherche inconnue » compte pour l'écran.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — le contenu de la route change.
- `api/openapi.json` régénéré.

## ÉCART — la borne était déjà tombée

Le cadrage prévoyait ici le retrait de la borne sur les listes. `API-059` l'a fait : la même
constante bornait le plan de travail, et la renommer sans la retirer de là aurait laissé un « seuil
recommandé » servir de plafond. Cette carte n'a donc eu qu'à retirer les fiches et les explications,
et à ajouter la cinquième liste.

## Vérification

**La route, sur la pile du worktree**, compte à 56 recherches ouvertes :

```
avant   2 648 105 octets   (fiches + explications + listes)
après     114 421 octets   ← 23 fois moins

clés racine : searches                       ← le pool de fiches a disparu
une entrée  : id, resultIds, favoriteIds, dismissedIds, seenIds, newIds
la plus grande recherche : 218 identifiants  ← entière
```

**Les retenus suivent l'ordre du jeu** : sur la recherche qui en compte quatorze, leurs rangs dans le
jeu sortent croissants.

**Les nouveaux profils sortent vides sur cette base, et c'est juste** : le profil le plus récent y
date du 2 août, quinze jours, quand la règle en retient sept. Le mécanisme est prouvé par un test qui
vieillit un profil à la main.

```
phpunit --filter 'SearchWorkspaceTest'     OK (7 tests, 60 assertions)
phpunit --filter 'Search|Workspace|Favorite'   OK (143 tests, 1 712 assertions)
```

Trois tests ont échoué avant de passer, et chacun disait quelque chose :

- la **métadonnée de sérialisation** survivait au vidage de cache et au redémarrage du conteneur —
  il a fallu supprimer le dossier à la main, en développement puis en test ;
- la base de test **naît avec ses fixtures**, donc tout y est récent : prouver la règle des sept
  jours demande de dater un profil dans le passé ;
- une entité **détachée** entre deux requêtes HTTP : la recherche doit être relue dans le
  gestionnaire courant avant d'y rattacher un favori.

## Livré

- `api/src/ApiResource/Output/SearchWorkspaceOutput.php` — le pool de fiches quitte l'enveloppe.
- `api/src/ApiResource/Output/SearchWorkspaceEntryOutput.php` — les explications sortent, les
  nouveaux profils entrent : cinq listes.
- `api/src/State/SearchWorkspaceBuilder.php` — trois dépendances en moins, et les sous-listes rangées
  dans l'ordre du jeu.
- `api/src/Repository/SearchResultMembershipRepository.php` — une lecture groupée des profils récents.
- `api/tests/Functional/SearchWorkspaceTest.php` — réécrit sur la nouvelle forme.
- `api/openapi.json` et `webapp/shared/types/api.ts` régénérés.

## Reste à faire (hors périmètre)

- **L'écran consomme encore l'ancienne forme** : le préchargement d'`ENT-127` lit un pool de fiches
  qui n'existe plus. `ENT-130` le remplace, et la lane entreprise doit passer avant que cette branche
  ne soit livrée avec l'écran.

## Définition de terminé

- [x] La réponse ne contient plus aucune fiche ni aucune explication.
- [x] Elle porte, par recherche ouverte, la liste ordonnée complète et les **quatre** listes d'état :
      retenus, écartés, vus, nouveaux.
- [x] Aucune de ces listes n'est bornée : chacune décrit la recherche entière.
- [x] Les retenus y sont dans l'ordre du classement de la recherche, identique à celui que servait
      l'appel dédié.
- [x] Une recherche close n'y figure pas ; une recherche au jeu vide y figure avec des listes vides.
- [x] Tests fonctionnels sur ces cinq points.
- [x] Documentation et contrat régénéré à jour.
