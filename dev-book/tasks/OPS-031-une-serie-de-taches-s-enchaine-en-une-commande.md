# TASK OPS-031 — une série de tâches s'enchaîne en une commande

- **Créée le** : 2026-08-05.
- **Session** : `f2d21f06-a78d-474a-abca-b2fd7a28848e`.
- **Statut** : ✅ fait le 2026-08-05 — `/demarrer-taches` existe, avec sa phase 0 de contrôles
  groupés, sa boucle qui repasse par `/commit-tache` à chaque tour, ses cinq motifs d'arrêt net, et
  les six renvois de documentation qu'elle rendait faux.
- **Type** : outillage.
- **Tests** : sans objet — la tâche ne produit aucun code exécutable, seulement une commande
  `.claude/commands/` et les renvois de documentation qu'elle rend nécessaires.
- **Réf** : `/demarrer-tache`, `/creer-tache`, `/commit-tache` ; `OPS-024` (l'ouverture de tâche
  porte l'implémentation), `OPS-025` (une carte dit de quelle session elle vient), `OPS-026`
  (l'état des lanes) ; `1.getting-started/git-workflow.md`, `ai-workflow.md`.

## Contexte

Le cycle outillé mène **une** tâche à la fois : `/demarrer-tache` puis `/commit-tache`, et la
suivante rouvre par `/demarrer-tache`. Ce retour est ce qui empêche deux tâches de finir dans les
mêmes fichiers — l'incident du 2026-07-30, trois cartes implémentées d'une traite et un découpage
d'index reconstruit à la main, a écrit cette règle.

Le manque n'est pas la règle, c'est le **coût du retour**. Quand un lot de trois à cinq cartes est
déjà cadré et que rien ne demande d'arbitrage, l'humain retape deux commandes par tâche et rend un
verdict à chaque preuve, alors qu'il n'a rien à décider. La pression pour tout implémenter d'un bloc
vient de là, et c'est elle qui a produit l'incident : la discipline coûte plus cher que sa
transgression.

## Périmètre

- Une commande `.claude/commands/demarrer-taches.md` qui mène une **série** de tâches, en refermant
  le cycle complet — implémentation, preuve, tests, doc, **commit** — sur chacune avant d'ouvrir la
  suivante.
- Trois formes d'entrée : une plage `<PRÉFIXE>-NNN..<PRÉFIXE>-NNN`, une liste de références, ou un
  besoin en une phrase qu'elle fait d'abord cadrer en N cartes.
- Les contrôles bloquants **tous rassemblés avant la première ligne de code** — existence,
  appartenance de session, espace unique, ordre des dépendances — pour que la série puisse ensuite
  tourner sans reprise de main.
- Cinq motifs d'arrêt net, et le comportement associé : arrêt sur place, jamais de saut.
- Les renvois de documentation que l'ajout d'une septième commande rend faux.

## Hors périmètre

- **Toute forme de commit groupé.** L'invariant « un commit = une tâche » n'est pas touché : une
  série de cinq tâches produit cinq commits. La commande accélère le rythme des commits, elle n'en
  réduit pas le nombre.
- Le push et la PR — ils restent à `/creer-pr`, et les quality gates y restent aussi.
- Une série qui traverse deux espaces : deux branches, donc deux séries.
- Le parallélisme réel entre tâches. La série est **séquentielle**, c'est ce qui garantit un arbre
  propre entre deux cartes.

## Décisions de cadrage

- **« Bulk » désigne l'enchaînement des commits, pas leur fusion.** La demande initiale parlait de
  « travailler en bulk commit » ; l'invariant du dépôt et le squash désactivé ne laissent qu'une
  lecture possible, et la commande la dit dès son ouverture pour qu'elle ne soit jamais retournée.
- **Le verdict humain est déplacé, pas supprimé.** `/demarrer-tache` s'arrête sur une preuve fraîche
  et attend. Ici l'agent continue **tant que sa preuve est nette**, et s'arrête net dès qu'elle ne
  l'est pas. Le verdict se rattrape en une passe à la relecture de la série, avant `/creer-pr` —
  c'est le prix explicite du rythme, et il est acceptable parce que les commits ne sont pas encore
  poussés. Les deux autres cadences envisagées ont été écartées : un verdict par tâche ne
  supprimait pas le coût qui motive la commande, aucune pause du tout laissait une décision mal
  prise en tâche 2 se découvrir au commit 5.
- **L'arbre propre entre deux tâches est le garde-fou central.** C'est la seule vérification
  mécanique qui reproduit ce que le retour manuel par `/demarrer-tache` garantissait : un reste dans
  l'arbre signifie que la tâche précédente n'est pas close, et la série s'arrête.
- **Arrêt net plutôt que saut.** Sauter une carte bloquée obligerait à défaire son travail pour ne
  pas polluer la suivante — un `checkout --` sur un arbre qui peut porter du travail d'un autre, ce
  que le dépôt interdit. L'arrêt garde le travail en cours intact.
- **Sans plage fournie, elle cadre avant d'implémenter.** Faire proposer à l'agent les cartes `⏳ à
  faire` de la lane a été écarté : `OPS-025` interdit qu'un agent présente une file d'attente, et
  cette commande serait le pire endroit pour en faire l'exception.
- **Elle ne duplique aucune procédure.** Elle appelle la phase 1 de `/demarrer-tache` pour la
  branche, sa phase 2 pour l'implémentation, `/creer-tache` pour le cadrage et `/commit-tache` en
  entier pour le commit. Elle n'ajoute qu'une boucle et ses conditions d'arrêt — une copie
  divergerait, comme la table des espaces avait divergé avant `OPS-024`.

## Règles et cas limites

- **Plage à trous** — un numéro sans carte est nommé et écarté, jamais inventé. La numérotation par
  espace saute des numéros quand une lane est doublée.
- **Carte déjà `✅ fait`** — écartée et nommée, la série continue.
- **Carte d'une autre session** — le contrôle de `/demarrer-tache` s'applique, mais **en phase 0
  pour toute la plage** : la question est posée une fois, avant que quoi que ce soit démarre.
- **Plage traversant deux espaces** — refus immédiat, avec le découpage en deux séries.
- **Dépendance amont dans la plage** — l'ordre donné est respecté ; une plage numérique s'étend en
  ordre croissant, et une carte qui déclare en amont une carte plus haute dans la même plage arrête
  la phase 0.
- **Au-delà de cinq tâches** — la série est découpée. Le rapport final doit rester relisable, et
  c'est ce rapport qui porte le verdict différé.
- **Une tâche de la série rend fausse une carte suivante** — arrêt : la carte suivante n'est plus
  une spec, elle se recadre.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md` — l'encadré d'outillage annonce « cinq
  commandes » et décrit le retour par `/demarrer-tache`.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md` — § *Les commandes qui outillent le
  cycle*.
- `CLAUDE.md` racine — l'encadré de fin du § *Comment chacun livre*.
- `.claude/commands/demarrer-tache.md` — le § *L'invariant du cycle* interdit d'implémenter N cartes
  à la suite ; sans renvoi, la nouvelle commande le contredit en apparence.
- `.claude/commands/creer-tache.md` — l'avertissement final, même raison.
- `.claude/commands/commit-tache.md` — la ligne de clôture qui renvoie vers `/demarrer-tache`.

## Définition de terminé

- [x] `.claude/commands/demarrer-taches.md` existe et couvre les trois formes d'entrée.
- [x] Les contrôles bloquants sont rassemblés avant la première ligne de code.
- [x] Les cinq motifs d'arrêt sont énumérés, et l'arrêt est net.
- [x] L'invariant « un commit = une tâche » est réaffirmé, pas contourné.
- [x] Aucune procédure de `/demarrer-tache`, `/creer-tache` ou `/commit-tache` n'est recopiée.
- [x] Les six renvois de documentation listés ci-dessus sont à jour.

## Vérification

- **La commande est en place et son en-tête se lit.** `ls .claude/commands/` rend huit fichiers,
  `demarrer-taches.md` parmi eux ; `head -6` sur ce fichier rend un frontmatter complet et
  syntaxiquement clos — `description`, `argument-hint`, `allowed-tools`.
- **Son seul lien relatif résout.** `ls .claude/commands/../../CLAUDE.md` rend le `CLAUDE.md` racine
  (10 257 octets). C'est la seule référence de fichier que la commande porte hors de son dossier.
- **Rien n'a été écrit hors du worktree.** `git status --porcelain` sur `/home/johan/www/lab/showcase-recruiter-webapp-saas-mvp`
  rend une sortie vide, alors que le worktree porte les huit fichiers de la tâche.
- **Les renvois croisés ont été audités, pas supposés.** Un `grep -n -i 'session'` sur les sept
  commandes a servi à vérifier la cohérence de l'ensemble ; c'est lui qui a mis au jour le manque
  reporté ci-dessous.

> **Limite assumée, et elle compte** : la commande vit dans le `.claude/commands/` de ce worktree.
> Une session lancée depuis l'arbre principal ne la voit pas encore — la découverte réelle par
> Claude Code n'est observable qu'après le merge de la branche dans `develop`. La preuve ci-dessus
> porte sur le fichier et sa forme, pas sur son invocation.

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `.claude/commands/demarrer-taches.md` (+) | La commande : trois formes d'entrée, six contrôles bloquants en phase 0, la boucle, les cinq motifs d'arrêt, le rappel de relecture avant `/creer-pr` |
| `.claude/commands/demarrer-tache.md` (M) | Encadré après l'invariant « une tâche à la fois » : sans lui, la nouvelle commande semblait le contredire |
| `.claude/commands/creer-tache.md` (M) | L'avertissement final pointe vers la série, pour le cas où le cadrage vient de produire N cartes |
| `.claude/commands/commit-tache.md` (M) | Dit que la série repasse **par cette commande** à chaque tour, sans rien raccourcir |
| `CLAUDE.md` (M) | L'encadré d'outillage du § *Comment chacun livre* : cinq commandes de cycle, deux à côté |
| `docs/…/git-workflow.md` (M) | L'encadré d'outillage : la septième commande et ce qu'elle déplace du verdict humain |
| `docs/…/ai-workflow.md` (M) | § *Les commandes qui outillent le cycle* |

Deux déclencheurs de documentation ont été examinés et écartés, plutôt que passés sous silence :
`parallel-work.md`, qui ne nomme aucune commande et dont les règles de concurrence ne bougent pas —
la série reste séquentielle et mono-lane ; et `api/openapi.json`, aucune surface d'API n'étant
touchée.

## Reste à faire (hors périmètre)

**`/commit-tache` peut encore fabriquer une carte sans identifiant de session.** Son *cas dégradé*
— code écrit sans passer par `/creer-tache` — renvoie à la règle de **numérotation** de
`/creer-tache`, et à elle seule : le champ `**Session**` n'y est jamais nommé. Or `/demarrer-tache`
traite un champ absent comme « carte antérieure à `OPS-025`, continuer sans rien dire ». Une carte
née par ce chemin est donc lue comme *ancienne* et non comme *sans propriétaire*, et passe le
contrôle en silence.

C'est le dernier chemin qui produit des cartes anonymes ; tant qu'il est ouvert, la règle « champ
absent = carte ancienne » se dégrade. Le correctif tient en deux points — faire porter l'identifiant
de la session qui rédige à une carte tardive, et retrancher ce que « champ absent » doit signifier
désormais — mais il relève du mécanisme d'`OPS-025`, pas de l'enchaînement de séries : **il demande
sa propre carte.**

Point mineur relevé au passage, non corrigé : dans `/demarrer-tache`, le contrôle de propriété est
le **dernier** pas de la phase 1, donc la branche a déjà été basculée — voire réinitialisée sur
`origin/develop` — avant qu'on sache à qui appartient la carte. Rien ne se perd, la réinitialisation
ne s'appliquant qu'à une branche sans commits d'avance.
