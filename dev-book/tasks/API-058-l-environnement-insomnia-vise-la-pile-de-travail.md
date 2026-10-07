# TASK API-058 — l'environnement Insomnia vise la pile de travail

- **Créée le** : 2026-08-16.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-16 — les trois adresses visent la pile du worktree entreprise.
- **Type** : Outillage — trois valeurs d'un fichier d'environnement, aucun code touché.
- **Tests** : `sans objet` — aucun code exécutable n'est produit, et aucune gate ne se déclenche sur
  ce fichier. Demande explicite de ne pas relancer la suite : elle a tourné verte quelques minutes
  plus tôt sur la même branche, et ce fichier n'entre dans aucun de ses contrôles.
- **Réf** : `API-055` (la collection, dont ce fichier est l'environnement).

## Contexte

L'environnement de la collection d'essai pointait sur la pile de développement de l'arbre principal.
Il vise désormais la pile du worktree entreprise — le port de son API, et son domaine de site.

**C'est une décision de l'utilisateur, prise contre l'avis porté par `API-055`**, qui gardait ce
fichier hors périmètre au motif que des URL de worktree n'ont de sens que sur la machine qui l'a
créé. L'objection reste vraie et n'a pas été levée : elle a été **arbitrée**. Cette carte la trace
plutôt que de la faire disparaître.

## Périmètre

- Trois valeurs : l'adresse de base, l'adresse de l'API, l'adresse du site.

## Hors périmètre

- **Le reste du fichier** — comptes de recette, identifiants, langue : inchangés.
- **La collection elle-même**, livrée par `API-055`.

## Décisions de cadrage

### Ce que ça implique pour qui récupère la branche

Ces adresses ne répondent que sur une machine où le worktree entreprise tourne, avec ses ports.
Ailleurs, les requêtes de la collection tomberont sur une connexion refusée — ce n'est pas un
défaut de la collection, c'est l'environnement qu'il faudra repointer.

## Règles et cas limites

- Le fichier reste un JSON valide — relu par un analyseur.
- Le fichier jumeau dans le worktree entreprise porte **exactement** le même contenu : une fois cette
  branche mergée et les lanes réalignées, son écart local disparaîtra de lui-même. Rien à défaire
  là-bas, et rien n'y a été touché.

## Documentation impactée

Aucune.

## Vérification

```
- "base_url": "http://localhost:18082"          + "base_url": "http://localhost:18282"
- "url_api":  "http://localhost:18082/v1"       + "url_api":  "http://localhost:18282/v1"
- "site_url": "http://localhost:3000"           + "site_url": "http://entreprise.wt-entreprise.localhost:8280"
```

Fichier relu par un analyseur JSON : **valide**. Contenu comparé au fichier du worktree
entreprise : **identique**.

## Livré

- `dev-book/resources/insomnia/showcase-recruiter-webapp-saas.environment.json` — trois valeurs, rien d'autre.

## Définition de terminé

- [x] Les trois adresses visent la pile du worktree entreprise.
- [x] Le fichier reste un JSON valide.
