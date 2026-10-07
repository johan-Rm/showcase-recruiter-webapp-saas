# TASK API-015 — la suite de tests cesse de reconstruire la base

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Outillage de test API.

## Contexte

La suite complète prenait **11 min 29 s** pour 376 tests. À ce prix, elle cesse d'être une boucle de
retour : on ne la lance plus pendant qu'on code, on l'évite avant de committer, et on finit par
découvrir en CI ce qu'elle aurait dit en local.

## Ce que la mesure a montré

Le temps ne partait ni dans les tests, ni dans le hachage des mots de passe — l'hypothèse de départ,
fausse : `when@test` posait déjà `cost: 4` et `memory_cost: 10`.

Une classe de test réduite à trois assertions vides, en héritant simplement de la base fonctionnelle :

```
88 tests unitaires (aucune base)          0,38 s au total
setUp seul, sans fixtures ni assertion    1,8 s PAR TEST
  dont ResetDatabase (schéma recréé)      1,6 s
  dont fixtures applicatives              0,2 s
```

`ResetDatabase` de Foundry rejoue un `DROP` puis un `CREATE` du schéma **entier avant chaque test**.
Sur 242 tests fonctionnels, cela fait près de six minutes pendant lesquelles la suite ne teste rien :
elle reconstruit une base.

## Décision

**`dama/doctrine-test-bundle`**, en dépendance de développement.

Il rend la connexion Doctrine statique et ouvre une transaction par test, annulée à la fin. Foundry
le détecte et cesse alors de recréer le schéma : il ne le fait plus qu'**une fois par exécution**.
L'isolation entre tests est préservée — c'est le même contrat, obtenu par `ROLLBACK` plutôt que par
`DROP`/`CREATE`.

Une dépendance ajoutée demande une justification : celle-ci est la réponse standard de l'écosystème
Symfony à ce problème précis, elle ne sert qu'en environnement de test, et son gain est mesuré, pas
supposé.

Les fixtures continuent d'être chargées à chaque test (0,2 s) : leur coût est réel mais marginal, et
les charger une fois par classe demanderait de les sortir de la transaction — de la complexité pour
un dixième du gain déjà obtenu.

## Résultat

```
avant   Time: 11:28.913   OK (376 tests, 1870 assertions)
après   Time: 00:45.420   OK (376 tests, 1870 assertions)
```

Quinze fois plus rapide, à périmètre et verdict identiques. Aucun test n'a eu besoin d'être adapté à
la transaction.

Le gain vaut aussi pour la CI : `api-quality` exécute le même `composer quality` dans le même
conteneur.

## Critères d'acceptation

- [x] La suite complète passe sous la minute.
- [x] Les 376 tests restent verts, sans modification d'un seul d'entre eux.
- [x] L'isolation entre tests est conservée.
