# TASK API-055 — la collection Insomnia suit les routes livrées

- **Créée le** : 2026-08-16.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-16 — deux requêtes ajoutées et **rejouées pour de vrai** contre la
  stack du worktree, quatre requêtes mortes retirées, fichier d'environnement inchangé.
- **Type** : Outillage de la lane API — la collection d'essai rattrape le contrat, aucun code
  applicatif touché.
- **Tests** : `sans objet` — aucun code exécutable n'est produit. La preuve est l'**exécution réelle
  des requêtes ajoutées** contre la stack du worktree, et la relecture du fichier par un analyseur
  YAML.
- **Réf** : `API-050` (le plan de travail) · `API-051` (l'inventaire des débordements) · `API-047`
  (qui a posé les cinq premières requêtes de recherche) · `dev-book/resources/insomnia/`.

## Contexte

**La collection Insomnia est la porte d'entrée pour essayer l'API à la main**, et elle a pris du
retard sur ce que la lane livre. Deux manques distincts :

- **Les routes de cette PR n'y sont pas** : le plan de travail et l'inventaire des recherches qui
  débordent viennent d'être livrés sans que rien ne permette de les appeler ;
- **quatre requêtes y pointent sur une route morte** — `/v1/organizations/{uuid}/saved-searches`,
  qui n'existe plus. Vérifié sur le routeur : aucune route de ce chemin n'est déclarée. Elles ne
  rendent pas une erreur explicable, elles rendent 404 sur un chemin qui n'a jamais été supprimé de
  la collection.

Une collection qui ment coûte plus qu'une collection vide : elle envoie chercher un défaut là où il
n'y a qu'un chemin périmé. `api-routes.md` a été remis à jour sur ce périmètre en `API-050` ; la
collection ne l'a pas été.

## Périmètre

- **Deux requêtes ajoutées** — `GET /v1/searches/workspace` et `GET /v1/admin/searches` —, chacune
  avec sa description : ce que la réponse contient, et ce qu'elle sert à décider.
- **Les quatre requêtes mortes retirées.**
- **Le fichier d'environnement** reste inchangé : ses valeurs sont celles de l'arbre principal, et
  c'est ce qui doit être versionné.

## Hors périmètre

- **L'inventaire complet de la collection.** D'autres dossiers ont probablement le même retard ; les
  relire est un travail à part, pas un à-côté de cette PR.
- **Les URL locales d'un worktree** : elles se posent dans Insomnia, elles ne se committent pas —
  versionnées, elles enverraient toute l'équipe sur une stack qui n'existe que sur une machine.

## Décisions de cadrage

### Retirer plutôt que corriger les quatre requêtes mortes

Elles ne visent pas une route renommée mais un **modèle abandonné** : les recherches ne pendent plus
à une organisation, elles appartiennent à un recruteur. Les rebrancher sur `/v1/searches` reviendrait
à recréer, en double, les requêtes qu'`API-047` a déjà posées.

### Une carte pour un fichier d'outillage

Le dépôt tient « un commit = une tâche » sans exception, et cette mise à jour n'appartient à aucune
des trois tâches déjà commitées : elle les suit toutes. Elle prend donc sa carte, écrite **après**
coup et non par `/creer-tache` — la demande est arrivée au point de contrôle, pas au cadrage.

## Règles et cas limites

- **Le fichier reste lisible par Insomnia** : YAML valide, identifiants de requête uniques, jeton
  chaîné sur la réponse du login comme ses voisines.
- **Aucune valeur d'environnement n'est modifiée** : les variables existantes suffisent, et
  `search_id` est déjà là depuis `API-047`.
- **Les descriptions restent sans accent typographique exotique** : elles voyagent dans un YAML que
  d'autres outils relisent.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : la collection **est** l'outillage, elle ne documente pas une
convention. `api-routes.md` porte déjà les deux routes, remis à jour en `API-050` et `API-051`.

## Vérification

Les deux requêtes ont été **rejouées** contre la stack du worktree (`localhost:18182`), avec les
comptes de recette que porte le fichier d'environnement.

```
GET /v1/searches/workspace          HTTP 200   2 385 657 octets   11,4 s
  56 recherches ouvertes · pool dédoublonné : 1 099 candidats
  somme des resultIds : 1 794 → dédoublonné à 1 099
  clés d'une entrée : id, resultIds, favoriteIds, dismissedIds, seenIds, matches

GET /v1/admin/searches              HTTP 200   8 874 octets
  total débordantes : 19 · page 1 · perPage 20
  515 réels / 100 servis — showcase-recruiter-webapp-saas Demo SARL
GET /v1/admin/searches (recruteur)  HTTP 403
```

Le fichier relu par un analyseur YAML : **valide**, 83 entrées, aucun identifiant de requête en
double, plus aucune URL `saved-searches`.

## Livré

- `dev-book/resources/insomnia/showcase-recruiter-webapp-saas.insomnia.yaml` — +45 / −101 lignes :
  - **`Search Workspace - Everything At Once`** dans « Recherche & Dévoilement » ;
  - **`Admin Searches - Overflowing`** dans « Administration », jeton chaîné sur `req_auth_login_admin`
    comme ses voisines et non sur le login recruteur ;
  - les quatre requêtes `saved-searches` supprimées.

## Écart au cadrage

**La description du plan de travail a dû être réécrite après l'avoir appelé.** Écrite de mémoire,
elle annonçait un champ `newIds` et un pool nommé `profiles` : la réponse ne porte ni l'un ni
l'autre — le pool s'appelle `candidates`, et les entrées portent `matches` là où j'attendais
`newIds`. C'est exactement ce que la carte affirmait vouloir éviter, une collection qui ment ; elle
a menti pendant vingt minutes.

## Reste à faire (hors périmètre)

- **Le poids du plan de travail sur un gros compte** : 2,4 Mo et 11,4 secondes pour 56 recherches
  ouvertes. La mesure d'`API-050` portait sur un compte plus léger. Ce n'est ni une régression ni un
  défaut de cette carte, mais **le front ne peut pas charger ça à l'ouverture** sans stratégie —
  c'est à trancher là où le préchargement se branche, pas ici.

## Définition de terminé

- [x] Les deux nouvelles requêtes existent et **répondent** contre la stack du worktree.
- [x] Les quatre requêtes mortes ont disparu.
- [x] Le fichier reste un YAML valide, relu par un analyseur.
- [x] Le fichier d'environnement versionné n'a pas bougé.
