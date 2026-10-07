# TASK OPS-002 — workflow : une branche par espace et travail parallèle multi-agents

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — convention écrite, trois commandes projet alignées, page
  neuve sur le travail parallèle.
- **Type** : Process / documentation — aucun changement de comportement.
- **Réf** :
  - task `OPS-001-numerotation-des-taches-par-lane.md` (le compteur par espace, mergé la veille —
    cette tâche en corrige la règle de tirage et remplace les lanes qu'elle décrivait)
  - task 126 (les lanes d'espace), task 157 (la renumérotation de 151-153)
  - `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md`,
    `docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md`

## Contexte

`OPS-001` a donné à chaque espace son préfixe de tâche et son compteur, en gardant les lanes
`feature/espace-*` héritées de la task 126. Deux manques sont apparus dès le lendemain.

**Le nom de branche portait une information redondante.** `feature/espace-entreprise` répétait un
`feature/` qui ne distinguait rien — les sept lanes le portaient — tout en interdisant, pour une
raison purement technique, d'ajouter quoi que ce soit sous l'espace.

**Le tirage du numéro était faux.** `OPS-001` prescrivait `ls dev-book/tasks/<PRÉFIXE>-*.md | tail
-1`, qui ne voit que l'arbre courant. Le défaut s'est manifesté en écrivant cette tâche : rédigée
depuis un arbre en retard sur `develop`, elle a d'abord été numérotée `OPS-001` — le numéro que
`develop` portait déjà. La convention a été prise en défaut par son propre auteur, dans la semaine
de son écriture.

## Décisions

### Les branches deviennent `espace/<espace>`

Une branche par espace, et rien d'autre dans le nom : `espace/api`, `espace/entreprise`,
`espace/candidat`, `espace/admin`, `espace/public`, `espace/socle-webapp`, `espace/outillage`.
Elles sont longues, réutilisées tâche après tâche, réalignées sur `develop` entre deux PR.

Les espaces sont inchangés depuis `OPS-001` ; seule la forme du nom bouge.

**La nature du travail ne figure pas dans le nom de branche.** Une forme intermédiaire
`<espace>/<type>` — `entreprise/fix`, `admin/design` — a été écrite puis abandonnée : elle
dupliquait une information qui vit déjà à deux endroits mieux placés, la ligne `**Type** :` de la
carte de tâche et le type Conventional Commit que `commit-check` valide. Une branche `…/fix` qui
porte un `feat` ne déclenche aucune alerte, là où un commit mal typé en déclenche une. Et une
branche accumule plusieurs tâches de natures différentes : elle n'a pas de nature propre à annoncer.

### Le préfixe `feature/` disparaît, parce que Git l'exige

Le premier essai gardait les lanes `feature/espace-*` et ajoutait un niveau :
`feature/espace-public/chore`. Git le refuse, dans les deux sens :

```
$ git branch feature/espace-public
$ git branch feature/espace-public/chore
fatal: cannot lock ref 'refs/heads/feature/espace-public/chore':
       'refs/heads/feature/espace-public' exists
```

Une réf ne peut pas être à la fois un fichier et un dossier sous `.git/refs/heads/`. C'est cette
contrainte qui a écarté le troisième segment, puis le préfixe `feature/` avec lui.

Elle vaut toujours pour la forme retenue : `espace/api` étant une branche, `espace/api/<quoi que ce
soit>` est impossible, et une branche nommée `espace` tout court interdirait les sept. La porte à un
segment supplémentaire est donc **fermée définitivement** — c'est le prix de cette forme, assumé.

Vérifié avant d'écrire : les trois workflows CI filtrent sur la branche **cible**
(`branches: [develop, main]`), jamais sur la source ; aucun `.yml`, `.json` ou `Makefile` du dépôt
ne mentionne `feature/`. La convention ne vivait que dans la documentation.

### Le numéro se tire sur toutes les branches, pas sur l'arbre courant

Correction directe de `OPS-001`, qui prescrivait `ls dev-book/tasks/<PRÉFIXE>-*.md`. Cette commande
ne voit que l'arbre courant — pas ce qui est arrivé sur `develop` depuis le dernier réalignement, ni
ce qu'un worktree porte :

```bash
git log --all --diff-filter=A --name-only --format= -- 'dev-book/tasks/ENT-*' \
  | grep -oE 'ENT-[0-9]{3}' | sort -u | tail -1
```

`--all` couvre les branches locales **et** les remotes. Borné à un espace, le geste reste exact et
tient en une ligne — donc il sera fait, là où la règle de la task 157 (« regarder ce que `develop`
porte, et ce que les autres worktrees ont commité ») ne l'était jamais.

### Le travail parallèle a sa page

Le découpage en espaces **réduit** les conflits, il ne les supprime pas — et c'est la question
réelle, puisque le travail se mène avec plusieurs agents en parallèle. `parallel-work.md` liste ce
que l'espace partitionne, et surtout ce qui traverse : `webapp/base/` et `webapp/shared/` (par
conception, règle de l'espace consommateur), `dev-book/tasks/`, `api/openapi.json` (conflit garanti
dès que deux espaces touchent la surface), le conteneur API et la base de test.

Elle documente au passage les **`git worktree`**, utilisés depuis des semaines sans être écrits
nulle part : `worktree/api` existe, exclu du suivi par `.git/info/exclude`.

## Deux pièges du travail parallèle, vérifiés en écrivant

- **Le conteneur API ne voit pas les worktrees.** `docker-compose.dev.yml:80` monte `./api:/app`,
  soit l'`api/` de l'arbre **principal**. Un agent qui développe l'API depuis `worktree/api/` teste
  contre le code de l'arbre principal — symptôme : « mon changement n'a aucun effet ».
- **La base de test sait déjà s'isoler.** `api/config/packages/doctrine.yaml:35` porte
  `dbname_suffix: '_test%env(default::TEST_TOKEN)%'` : un `TEST_TOKEN=<n>` par agent évite que deux
  suites se recréent mutuellement `showcase-recruiter-webapp-saas_test`. Le mécanisme existait, il n'était pas documenté —
  avec sa réserve : le jeton change le **nom** de la base, il ne la crée pas.

## Livré

- `git-workflow.md` : modèle `espace/<espace>`, encadré « en un coup d'œil », section de
  numérotation corrigée, règles de nettoyage revues.
- `parallel-work.md` (+) : la page décrite ci-dessus, référencée depuis l'index de la section.
- `/demarrer-tache` : l'espace suffit à déterminer la branche ; numéro annoncé dès le
  positionnement.
- `/commit-tache` : commande du compteur, mention `— task <PRÉFIXE>-NNN` dans le titre, et rappel
  que la nature du travail vit dans la carte et le commit.
- `/apres-pr` : les branches sont **découvertes** par `git for-each-ref 'refs/heads/espace/*'` au
  lieu d'être listées en dur.

## Migration des noms de branches — faite

Les anciennes lanes et les nouvelles branches **coexistent sans se gêner** : `feature/api` et
`espace/api` sont deux noms sans rapport pour Git. La bascule n'avait donc pas à être atomique.

Au 2026-07-28, les six lanes étaient **toutes mergées** (`git cherry origin/develop <lane>` sans
aucun commit d'avance) : le renommage n'a mis aucun travail en jeu. Vérifié également — renommer une
branche checked-out dans un worktree fonctionne, et le worktree suit automatiquement.

| Ancienne | Nouvelle |
| --- | --- |
| `feature/api` | `espace/api` |
| `feature/espace-entreprise` | `espace/entreprise` |
| `feature/espace-candidat` | `espace/candidat` |
| `feature/espace-admin` | `espace/admin` |
| `feature/espace-public` | `espace/public` |
| `feature/outillage` | `espace/outillage` |

`espace/socle-webapp` n'existe pas encore : elle se créera au premier travail de socle qui ne se
rattache à aucun espace consommateur. Côté `origin`, aucune lane ne subsistait — toutes avaient été
supprimées après merge.

## Reste à faire (hors périmètre de cette tâche)

- **Branch protection** — si des règles GitHub ciblent `feature/*`, les réaccorder sur les nouveaux
  noms. Aucune n'a été trouvée côté dépôt (les workflows filtrent sur `develop` / `main`), mais les
  réglages d'organisation n'ont pas été inspectés faute d'accès.
- **Doublons `035`, `081`, `146`** — hérités de l'ancienne numérotation, jamais réparés
  contrairement à 151-153. À traiter comme la task 157 l'a fait, mais seulement si une référence
  ambiguë gêne réellement la lecture d'un fichier source.
