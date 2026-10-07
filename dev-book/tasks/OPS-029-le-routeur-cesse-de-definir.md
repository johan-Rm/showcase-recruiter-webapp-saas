# TASK OPS-029 — le routeur cesse de définir

- **Créée le** : 2026-08-02.
- **Session** : `65acb369-82b3-411a-a75f-31de108a5cd1`.
- **Statut** : ✅ fait le 2026-08-02 — `CLAUDE.md` route et renvoie ; il ne réexplique plus le cycle
  de delivery ni l'incident qui a écrit la discipline de session.
- **Type** : Gouvernance IA — le `CLAUDE.md` racine réaligné après la redistribution.
- **Tests** : `sans objet` — aucun code exécutable. La preuve est qu'un agent démarrant une session
  trouve toujours, dans le fichier auto-chargé, tout ce qu'il doit savoir **avant** sa première
  écriture — sans avoir à ouvrir une page de documentation pour agir correctement.
- **Réf** : `OPS-027` et `OPS-028` **amont** — les pages destinataires des renvois doivent exister ·
  `OPS-011` — la gouvernance en un seul fichier · `OPS-019` — un sub-agent par lane · `OPS-025` — la
  discipline de session, dont l'énoncé reste ici.

## Contexte

`CLAUDE.md` s'annonce comme un **routeur** : « il dit quel agent prend quelle demande, et ce qu'ils
partagent tous. Il ne contient ni architecture, ni conventions de code, ni règles métier, ni état
d'avancement. »

Il tient cette promesse à une nuance près : plusieurs de ses sections **redisent** ce que les pages
de `1.getting-started/` possèdent — la procédure de worktree, les six réflexes de travail parallèle,
la boucle de delivery. Tant que ces pages étaient elles-mêmes en désordre, la redondance servait de
filet. Après `OPS-027` et `OPS-028`, elle n'a plus d'excuse : elle devient un second exemplaire à
faire vieillir en parallèle.

Le risque est concret. Le nom des branches a changé quatre fois en trois jours ; à chaque fois, une
copie oubliée a fait travailler un agent sur une convention périmée.

## Périmètre

`CLAUDE.md` garde ce qui doit s'appliquer **sans lecture préalable** :

- la **table de routage** — ce que la demande touche → quel agent, quelle branche, quel préfixe. Elle
  reste ici : choisir une lane ne doit pas demander d'ouvrir une autre page ;
- les **deux règles de routage** — l'espace consommateur, et le découpage d'une demande qui traverse
  plusieurs lanes ;
- l'**énoncé court de la discipline de session** — pas d'initiative, clôture par inventaire. C'est le
  fichier réellement chargé à chaque session, donc le seul dont la règle s'applique ;
- les **interdits** du § *Ce qu'aucun agent ne fait* ;
- la **procédure de dossier de travail**, qui doit être suivie avant la première écriture — donc
  avant toute lecture de documentation.

Ce qui devient un renvoi :

- le § *Ce que chacun lit avant d'écrire* pointe désormais vers `ai-workflow.md` **en premier** :
  c'est elle qui porte le process et la définition de l'espace ;
- le § *Comment chacun livre* garde ses règles courtes mais renvoie vers `ai-workflow.md` pour le
  cycle et vers `git-workflow.md` pour les commits, au lieu de les réexpliquer ;
- les **six réflexes** de travail parallèle restent en liste courte, avec un renvoi vers
  `parallel-work.md` pour le pourquoi — la répartition actuelle, qu'il s'agit de confirmer plutôt que
  de refaire.

## Hors périmètre

- **Les définitions d'agents** `.claude/agents/espace-*.md` : elles pointent déjà plutôt qu'elles ne
  recopient. Rien à y faire.
- **Les commandes** `.claude/commands/` : source opérationnelle, intouchée.
- **Le socle global** `~/.claude/CLAUDE.md`, hors dépôt.
- **Toute règle nouvelle.** Cette carte réaligne des renvois ; elle n'ajoute ni ne retire d'obligation.
- **Faire déménager la discipline de session vers `ai-workflow.md`** — écarté au cadrage, voir
  ci-dessous.

## Décisions de cadrage

### Un routeur garde ce qui doit s'appliquer avant toute lecture

La règle « il pointe, il ne définit pas » a une exception nécessaire : ce qui doit être respecté
**avant** que l'agent ait lu quoi que ce soit. La procédure de dossier de travail en fait partie —
elle protège du geste qui a déjà détruit du travail non commité, et un renvoi arriverait trop tard.

Le critère retenu n'est donc pas « est-ce défini ailleurs ? » mais **« que se passe-t-il si l'agent
ne lit pas la page pointée ? »**. Si la réponse est « il casse quelque chose », ça reste dans
`CLAUDE.md`.

### La discipline de session reste ici

Un déménagement complet vers `ai-workflow.md` aurait été plus cohérent avec le principe du routeur.
Écarté : `CLAUDE.md` est chargé automatiquement, une page de doc ne l'est pas. Une règle déplacée
devient une règle qu'il faut penser à lire — et celle-ci existe précisément parce qu'une session a
agi sans avoir lu.

`ai-workflow.md` en portera le **pourquoi** (`OPS-027`), `CLAUDE.md` l'**énoncé**. Deux registres.

### Écart au cadrage — le fichier n'a pas rétréci

Le contexte de cette carte parle de redondance « à faire vieillir en parallèle », ce qui laissait
attendre un allègement. **Le fichier passe de 147 à 154 lignes.**

Les justifications retirées étaient plus courtes que les trois paragraphes de renvoi qui les
remplacent. Ce que la tâche gagne n'est donc pas de la concision mais de la **propriété** : plus rien
n'y est défini deux fois, et une convention qui change ne se corrige plus qu'à un seul endroit.

C'est le bon arbitrage — un routeur peut être long s'il ne définit rien — mais il fallait le dire :
mesurer le succès de cette carte au nombre de lignes conduirait à la juger ratée.

### Écart au cadrage — une clause a été gardée faute de destination

« Une carte écrite ailleurs atterrirait sur `develop` sans le code qu'elle décrit » est le seul
*pourquoi* de la règle « ni le code, ni la carte de l'autre lane ». Aucune page ne le porte —
`ai-workflow.md` décrit la carte comme spec, pas ce qui arrive quand elle voyage seule.

La supprimer aurait laissé une règle sans raison. Elle reste donc, ramenée à une ligne : le principe
« l'énoncé reste, le pourquoi part » suppose que le pourquoi ait où aller.

### Le cycle n'a pas à être réexpliqué deux fois

Le § *Comment chacun livre* décrit aujourd'hui la boucle, le contenu d'un commit, le moment des
gates. C'est exactement le sujet d'`ai-workflow.md` après `OPS-027`. Les règles courtes restent —
elles s'appliquent sans lecture —, mais leur justification part.

## Règles et cas limites

- **`OPS-027` et `OPS-028` doivent être commitées avant.** Un renvoi vers une section inexistante est
  pire que la duplication qu'il remplace.
- **Aucune obligation ne disparaît.** Chaque règle retirée est soit conservée en forme courte, soit
  renvoyée vers la page qui la porte.
- **Le fichier reste lisible d'un bout à l'autre en une fois.** Un routeur qu'on ne lit pas
  entièrement ne route rien.
- **Vérifier les liens relatifs** : `CLAUDE.md` est à la racine, ses chemins vers `docs/` ne
  commencent pas comme ceux d'une page de `1.getting-started/`.

## Documentation impactée

- `CLAUDE.md` **(M)** — racine du dépôt.
- Aucun autre fichier : `OPS-027` et `OPS-028` ont déjà traité les pages de `1.getting-started/`.

## Définition de terminé

- [x] Le § *Ce que chacun lit avant d'écrire* cite `ai-workflow.md` en premier.
- [x] Le § *Comment chacun livre* garde ses règles courtes et renvoie pour le reste, sans réexpliquer
      le cycle.
- [x] La procédure de dossier de travail reste intégralement dans `CLAUDE.md`.
- [x] L'énoncé de la discipline de session reste ; son *pourquoi* n'est plus ici mais dans
      `ai-workflow.md`.
- [x] La table de routage et les deux règles de routage sont inchangées.
- [x] Aucune obligation n'a disparu sans destination.
- [x] Les liens **ajoutés par cette carte** pointent vers une section qui existe, vérifié contre les
      identifiants rendus. Le critère d'origine disait « tous les liens » : trop large, il rendait
      cette carte responsable d'une ancre morte depuis `af349a4`, nommée en *Reste à faire*.

## Vérification

**Aucune obligation n'a disparu.** Inventaire automatique des puces normatives entre
`HEAD:CLAUDE.md` et l'arbre :

```
règles avant 15 / après 15 — disparues : aucune
lignes : 147 → 154
```

**Les trois renvois ajoutés résolvent** contre les identifiants réellement générés par le site servi
en local — pas contre une règle de slug supposée :

```
OK  ai-workflow.md#les-espaces
OK  ai-workflow.md#le-chemin-dune-demande
OK  ai-workflow.md#la-discipline-de-session
```

**Aucune page de documentation n'est rendue fausse.** Les sept mentions de `CLAUDE.md` dans
`ai-workflow.md` restent exactes, dont la symétrie « il route, celle-ci définit », qui se lit
désormais des deux côtés.

**Aucune gate ne s'applique** : le diff est du Markdown de gouvernance et ne touche ni `webapp/` ni
`api/`.

## Livré

- `CLAUDE.md` **(M)** — la table de routage dit qu'elle route et renvoie vers la table qui définit ;
  l'ordre de lecture place `ai-workflow.md` en deuxième position ; le cycle de delivery garde ses
  règles courtes et renvoie pour leurs raisons ; l'incident de la discipline de session part chez son
  propriétaire.

## Reste à faire (hors périmètre)

- **`architecture.md#invariants` est une ancre morte**, et ce depuis le commit `af349a4` — bien
  avant cette carte. Cause : la page écrit `## Invariants non négociables (garde-fous) {#invariants}`
  et Docus n'interprète pas le marqueur `{#…}`, il slugifie la ligne entière. `CLAUDE.md` porte ce
  lien, mais le réparer suppose de corriger `architecture.md`. Nommé, pas repris.
- **Rien ne vérifie les ancres** dans ce dépôt. Un contrôle en CI serait une décision nouvelle.
- **Cinq ancres cassées dans `OPS-027`** (commit `0b42dbd`), laissées en l'état sur décision
  explicite.
