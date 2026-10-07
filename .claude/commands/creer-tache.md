---
description: Cadrer un besoin par questions interactives jusqu'à épuisement des inconnues et écrire la carte de tâche — la branche est positionnée par /demarrer-tache, qu'elle appelle
argument-hint: [le besoin en une phrase — ex. « export CSV de la sélection »]
allowed-tools: Bash(git:*), Read, Write, Glob, Grep, AskUserQuestion
---

Cadrer puis ouvrir la tâche : **$ARGUMENTS**

## Invariant

**Rien ne se code avant que la carte existe.** La carte `dev-book/tasks/<PRÉFIXE>-NNN-<slug>.md` est
la **spec** : elle est écrite ici, avant la première ligne de code, et `/commit-tache` ne fera plus
que la clôturer (statut, vérification, livré).

Cette commande **ne code rien**. Elle produit deux choses et s'arrête : une carte de tâche et une
branche prête.

## Phase 1 — S'instruire avant d'interroger

**Ne jamais poser une question dont la réponse est dans le dépôt.** Chaque question posée à tort
coûte la crédibilité des suivantes. Avant le premier échange, lire le minimum utile — et rien de
plus :

- les pages de `docs/showcase-recruiter-webapp-saas/content/2.architecture/` que le périmètre touche ;
- les **cartes de tâches voisines** (`dev-book/tasks/`), qui portent les décisions déjà prises et
  les renvois « aval » ;
- le code réellement concerné, pas son voisinage ;
- le `README.md` du dossier visé, qui porte ses conventions opérationnelles.

Annoncer en trois lignes ce que cette lecture a déjà tranché. Le cadrage ne porte plus que sur ce
qui reste ouvert.

## Phase 2 — Cadrage interactif, jusqu'à épuisement

Interroger par tours successifs (`AskUserQuestion`, jusqu'à 4 questions par tour), et **relancer
tant qu'une dimension reste floue**. Un tour unique n'est pas un cadrage.

Les douze dimensions à couvrir — celles qui ne s'appliquent pas sont **écartées à voix haute**, pas
oubliées en silence :

1. **Problème réel** — qui, quand, quel manque aujourd'hui. Pas la solution, le manque.
2. **Résultat observable** — à quoi on verra que c'est fait, sans lire le code.
3. **Périmètre**, et surtout **hors périmètre** : ce qui aurait pu entrer et n'entre pas.
4. **Espace de rattachement** → il détermine la branche et le préfixe (phase 3).
5. **Surface touchée** : contrat d'API, UI, modèle de données, migration, infra.
6. **Règles métier, rôles et permissions**, cas limites.
7. **États et données** : vide, erreur, chargement, volume, concurrence.
8. **Contraintes transverses** : mobile-first, SSR-safe, accessibilité, SEO, performance, i18n.
9. **Tests attendus** — nature et niveau ; ou exemption motivée si aucun code exécutable n'est
   produit. Cette réponse alimente la ligne `**Tests** :` que `/commit-tache` lira.
10. **Documentation impactée** — quelles pages de `docs/showcase-recruiter-webapp-saas/` et quels `README.md`.
11. **Dépendances** : tâches amont qui doivent exister, tâches aval que celle-ci ouvre.
12. **Risques et reports assumés** — ce qui est délibérément renvoyé à plus tard, et pourquoi.

Conduite des questions :

- une seule idée par question, options **concrètes et mutuellement exclusives**, recommandation en
  premier avec son motif ;
- proposer un défaut raisonnable plutôt qu'une question ouverte quand le dépôt penche déjà d'un
  côté ;
- une réponse qui en ouvre une autre se traite **dans le tour suivant**, pas plus tard ;
- ne jamais convertir une incertitude en hypothèse silencieuse : soit elle est posée, soit elle est
  écrite dans la carte comme hypothèse assumée.

**Clore par une restitution** : problème, périmètre, hors périmètre, critères de terminé, tests,
docs. L'utilisateur la valide — ou la corrige — **avant** toute écriture.

## Phase 3 — Positionner la branche

L'espace est connu (dimension 4) : la branche suit. **Exécuter la seule phase 1 de
`/demarrer-tache`** — *Positionner la branche* —, qui possède cette procédure : le positionnement
n'est écrit qu'à un seul endroit, et ce n'est pas ici. L'ancienne répartition le dupliquait, et la
copie avait fini par rater l'ajout d'un espace.

**S'arrêter à la sortie de cette phase.** La phase 2 de cette commande-là implémente la carte ; elle
n'a pas sa place dans un cadrage, où la carte n'est pas encore écrite ni validée.

Elle rend la branche, son état, et les tâches déjà empilées sur la lane.

## Phase 4 — Écrire la carte

**Numéro** — le dernier pris pour cet espace, incrémenté sur trois chiffres. Un
`ls dev-book/tasks/` ne suffit **pas** : il ne voit que l'arbre courant, qui peut être en retard sur
`develop` ou sur un worktree. `--all` couvre branches locales et remotes.

```bash
git log --all --diff-filter=A --name-only --format= -- 'dev-book/tasks/<PRÉFIXE>-*' \
  | grep -oE '<PRÉFIXE>-[0-9]{3}' | sort -u | tail -1
```

Ne jamais reprendre la séquence nue `001`-`164`, **gelée** (tâches d'avant le 2026-07-27). Le slug
ne répète pas le préfixe : `ENT-018-export-de-la-selection.md`.

**Session** — l'UUID de la session qui cadre, lu dans le chemin de son dossier de travail temporaire :

```
/tmp/claude-<uid>/<projet>/<UUID-de-session>/scratchpad
```

Le compteur par espace protège les **noms** — deux agents ne créent pas `ENT-030` en même temps. Ce
champ protège les **intentions** : une carte vit en fichier non suivi jusqu'au commit de sa tâche,
visible de tous et attribuée à personne. Sans lui, un agent reprend la carte d'un autre en croyant
continuer son propre travail, avec des décisions de cadrage qu'il n'a jamais prises — c'est arrivé
sur trois cartes le 2026-08-02. Ne jamais écrire « inconnue » : sans identifiant, le champ ne sert
plus à rien.

Forme de la carte — suivre les voisines, cette ossature est le minimum :

```markdown
# TASK <PRÉFIXE>-NNN — <titre en langage courant>

- **Créée le** : <AAAA-MM-JJ>.
- **Session** : `<UUID de la session qui cadre>`.
- **Statut** : ⏳ à faire.
- **Type** : <nature du travail — feat, fix, refactor, docs, outillage…>.
- **Tests** : <ce qui sera couvert et à quel niveau> — ou `sans objet — <raison>`.
- **Réf** : <tâches amont / aval, pages de doc, fichiers>.

## Contexte
<le manque, pas la solution>

## Périmètre
## Hors périmètre
## Décisions de cadrage
<ce qui a été tranché pendant l'échange, et pourquoi ce choix plutôt que l'autre>

## Règles et cas limites
## Documentation impactée
<pages de docs/showcase-recruiter-webapp-saas et README de dossier — la carte les liste, elle ne les remplace pas>

## Définition de terminé
- [ ] …
```

La ligne `**Type** :` est, avec le type Conventional Commit, le **seul** endroit où la nature du
travail est écrite — le nom de branche ne dit que l'espace.

La carte **ne devient pas la documentation durable** : elle pointe vers `docs/showcase-recruiter-webapp-saas/`, elle ne
recopie pas son contenu (règle de non-duplication,
[ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#règle-de-non-duplication)).

## Sortie

Quatre lignes : la branche et son état (créée / reprise / réinitialisée), le chemin de la carte, les
inconnues restées ouvertes s'il y en a, et la première étape d'implémentation.

Ne rien committer, ne rien pousser, ne rien coder. L'implémentation vient avec la phase 2 de
`/demarrer-tache`, le commit avec `/commit-tache`.

> ⚠️ **Un cadrage peut légitimement produire PLUSIEURS cartes** — un besoin se découpe souvent en
> trois. Les **écrire** toutes est utile ; les **implémenter** à la suite ne l'est pas. L'ordre est
> imposé, carte par carte : `/demarrer-tache` (qui branche **et** implémente) → `/commit-tache` →
> carte suivante. Le contrat, et l'incident qui l'a écrit, sont dans `/demarrer-tache`.
>
> Ce va-et-vient s'outille : `/demarrer-taches` prend la plage de cartes qui vient d'être écrite et
> enchaîne les tours sans reprise de main — **toujours un commit par carte**. Elle sert le cas où
> rien ne reste à arbitrer ; une carte qui laisse une décision ouverte se mène seule.
