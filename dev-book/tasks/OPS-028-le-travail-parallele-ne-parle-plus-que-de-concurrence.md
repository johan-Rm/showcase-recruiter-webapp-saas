# TASK OPS-028 — le travail parallèle ne parle plus que de concurrence

- **Créée le** : 2026-08-02.
- **Session** : `65acb369-82b3-411a-a75f-31de108a5cd1`.
- **Statut** : ✅ fait le 2026-08-02 — `parallel-work.md` ne redéfinit plus l'espace et ne redit plus
  les règles de commit ; elle garde la concurrence, et le risque qu'elle seule porte.
- **Type** : Documentation — recentrage d'une page sur son sujet propre.
- **Tests** : `sans objet` — aucun code exécutable, aucune gate CI sur `docs/`. La preuve est la
  lecture de la page seule : elle doit se tenir sans rien redéfinir.
- **Réf** : `OPS-027` **amont** — `ai-workflow.md` doit porter la définition de l'espace et le champ
  `Session` avant que cette page puisse y renvoyer · `OPS-029` — le routeur suit ·
  [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — destinataire réel
  du renvoi sur la discipline de commit, cf. les décisions de cadrage.

## Contexte

`parallel-work.md` a un sujet propre et solide : **ce qui casse quand plusieurs agents écrivent en
même temps**. Worktrees, stacks Docker, ressources partagées, base de test — c'est de la concurrence,
et personne d'autre ne le porte.

Mais la page commence par redéfinir ce qu'elle n'a pas à définir, et finit par redire ce qu'une autre
possède :

- §7-15 « Le principe » et §17-25 « Ce que l'espace partitionne » **rappellent le découpage en
  espaces**, que `OPS-027` va installer dans `ai-workflow.md` ;
- §173-182 « Discipline de commit » — staging par chemins explicites, relire l'index, ne jamais
  écraser du travail non commité — **redit ce que porte l'étape de commit** du cycle de delivery ;
- §111-120, le champ `Session` et la propriété d'une carte, part vers `ai-workflow.md` avec
  `OPS-027`.

Une page de 192 lignes dont trente ne lui appartiennent pas.

## Périmètre

`parallel-work.md` se recentre sur **la concurrence, et rien d'autre**.

Ce qu'elle garde, parce qu'elle seule le porte :

- le `git worktree` comme dossier de travail, et `make worktree-add` / `worktree-list` /
  `worktree-remove` ;
- la **stack Docker par worktree** — décalage de ports, domaine `wt-<espace>.localhost`, base par
  stack, un seul serveur Nuxt par stack ;
- les **ressources partagées** : `webapp/base/` et `webapp/shared/`, `dev-book/tasks/` comme dossier
  commun, `api/openapi.json` régénéré donc en conflit garanti ;
- la **base de test** et `TEST_TOKEN` ;
- le conteneur API qui lit l'arbre depuis lequel il a été lancé.

Ce qui devient un renvoi :

- l'ouverture (§7-25) ne redéfinit plus l'espace : elle **pose le problème de la page** — le
  découpage réduit les collisions sans les supprimer — et renvoie vers `ai-workflow.md` pour ce
  qu'est un espace ;
- la « discipline de commit » (§173-182) renvoie vers l'étape du cycle qui applique la règle ;
- ce qui reste du champ `Session` après `OPS-027` se limite au constat que `dev-book/tasks/` est
  partagé, avec un renvoi.

## Hors périmètre

- **`ai-workflow.md` et `git-workflow.md`** : `OPS-027` les traite. Cette carte ne fait qu'y pointer.
- **Toute règle nouvelle sur la concurrence.** Les six réflexes existent, ils ne changent pas.
- **La procédure de worktree elle-même** : elle est juste, elle reste telle quelle.
- **`CLAUDE.md`**, qui reprend les six réflexes en version courte : `OPS-029`.

## Décisions de cadrage

### Une page peut poser un problème sans définir ses termes

Supprimer l'ouverture entièrement laisserait la page démarrer sur `git worktree` sans que le lecteur
sache pourquoi il en faudrait plusieurs. L'ouverture reste donc, mais change de nature : elle
n'explique plus **ce qu'est** un espace, elle explique **ce qui casse malgré** le découpage.

C'est la même information vue depuis l'autre bout, et c'est ce qui en fait le sujet propre de la
page.

### La discipline de commit renvoie plutôt que de disparaître

Les trois règles de §173-182 sont vraies et utiles ici — c'est en contexte concurrent qu'un
`git add -A` fait des dégâts. Mais elles appartiennent au cycle de delivery. La page garde donc **le
risque propre à la concurrence** — « un fichier écrit par un autre agent peut apparaître entre le
`git status` et le `git add` » — et renvoie pour la règle.

Supprimer sans renvoyer aurait été pire : le lecteur de cette page est précisément celui qui a besoin
de ces trois gestes.

### Écart au cadrage — le renvoi ne va pas vers `git-workflow.md`

**Le cadrage désignait le mauvais propriétaire.** Il annonçait que la discipline de staging « redit
`git-workflow.md` §147 et suivants ». Vérification faite avant de supprimer quoi que ce soit :
`git-workflow.md` ne contient **ni** `git add`, **ni** la notion de staging — cette page traite du
message de commit, du merge et des PR, pas de l'index.

La règle vit à quatre endroits : `CLAUDE.md` (énoncé court), `ai-workflow.md` étape 10 depuis
`OPS-027`, `/commit-tache` (opérationnel), et les définitions d'agents (rappel). Le propriétaire
documentaire est donc **`ai-workflow.md`**, à l'étape qui l'applique — c'est là que pointe le renvoi.

Renvoyer vers `git-workflow.md` comme prévu aurait produit un lien vers une section inexistante, et
fait disparaître la règle du corpus.

### Écart au cadrage — « ne jamais écraser du travail non commité » reste ici

Cette troisième règle n'existe **que** dans `CLAUDE.md` et dans cette page. Elle n'a pas de
propriétaire documentaire ailleurs, et elle est purement un risque de concurrence : sur un dépôt à
un seul auteur, il n'y a pas de travail d'autrui à écraser. Elle reste donc, et c'est le renvoi
d'origine qui rétrécit — pas la règle.

## Règles et cas limites

- **`OPS-027` doit être commitée avant.** Renvoyer vers une section d'`ai-workflow.md` qui n'existe
  pas encore produirait un lien mort.
- **Aucune règle ne se perd** dans l'opération : chaque paragraphe retiré est soit déjà écrit
  ailleurs, soit remplacé par un renvoi vers l'endroit où il l'est.
- **La page reste lisible seule.** Un agent qui n'ouvrirait que celle-là doit toujours savoir quoi
  faire — les renvois complètent, ils ne remplacent pas.

## Documentation impactée

- [parallel-work.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md) **(M)** — seule
  page touchée par cette carte.

## Définition de terminé

- [x] L'ouverture pose le problème de la concurrence et **ne redéfinit plus** l'espace ; elle renvoie
      vers `ai-workflow.md`.
- [x] La « discipline de commit » renvoie vers le propriétaire réel de la règle et ne garde que le
      risque propre à la concurrence.
- [x] Le champ `Session` n'est plus décrit ici ; seul subsiste le fait que `dev-book/tasks/` est un
      dossier partagé, avec un renvoi.
- [x] Worktrees, stacks, ressources partagées et base de test sont inchangés sur le fond.
- [x] Aucune règle n'a disparu sans destination.
- [x] Tous les renvois pointent vers une section qui existe.

## Vérification

**Page servie en local, HTTP 200.** Les onze titres se rendent dans l'ordre attendu, la nouvelle
section finale comprise :

```
Le principe · Ce que l'espace partitionne · Un dossier de travail par espace — git worktree ·
Une stack Docker par worktree · Ce qui ne se partitionne pas · webapp/base/ et webapp/shared/ ·
dev-book/tasks/ · api/openapi.json · Le conteneur API · La base de test ·
Ce que la concurrence change au moment de commiter
```

**Les trois renvois vers `ai-workflow.md` résolvent contre les identifiants réellement générés par
le site** — pas contre une règle de slug supposée :

```
OK  les-espaces
OK  à-qui-appartient-une-carte
OK  cadrer-coder-figer-à-chaque-carte
```

Ce contrôle-là est neuf. Le précédent comparait à la règle GitHub, qui rend un tiret cadratin en
**double** tiret là où Docus n'en met qu'**un** : il validait des ancres cassées. La méthode est
désormais de lire les `id` du HTML rendu.

**Aucune règle n'a disparu sans destination.** Le staging et la relecture de l'index sont dans
`ai-workflow.md` étape 10 ; « ne jamais écraser du travail non commité » reste ici, faute d'autre
propriétaire et parce que c'est un risque purement concurrent.

**Aucune gate ne s'applique** : le diff est du Markdown seul, il ne touche ni `webapp/` ni `api/`.

## Livré

- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md` **(M)** — l'ouverture pose le problème
  de la page au lieu de définir l'espace ; la table « ce que l'espace partitionne » ne garde que les
  deux séparations dont cette page est propriétaire ; la « discipline de commit » devient « ce que la
  concurrence change au moment de commiter », renvoie pour la règle et garde le risque.

## Reste à faire (hors périmètre)

- **`OPS-029`** — `CLAUDE.md` réexplique encore le cycle de delivery. La carte est écrite.
- **Cinq ancres cassées dans `OPS-027`** (commit `0b42dbd`) — mêmes tirets cadratins, mêmes
  identifiants doublés. Laissées en l'état sur décision explicite : seule la sixième, qui
  appartenait à cette tâche, a été corrigée.
- **Rien ne vérifie les ancres** dans ce dépôt — c'est ce qui a laissé passer l'erreur. Un contrôle
  en CI serait une décision nouvelle, donc une carte à part.
