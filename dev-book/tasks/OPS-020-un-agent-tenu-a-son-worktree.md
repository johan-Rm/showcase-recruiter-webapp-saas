# TASK OPS-020 — Un agent de lane est tenu à son worktree, et ce qui déborde remonte

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-07-31 — procédure de dossier de travail dans les huit agents et dans
  le socle commun, protocole de passage de relais entre lanes écrit.
- **Type** : Outillage — gouvernance IA.
- **Tests** : sans objet — aucun code exécutable. La vérification est la relecture des huit fichiers
  et du socle commun.
- **Réf** : `OPS-019` (un sub-agent par lane) · `OPS-003` (une stack Docker par worktree) ·
  [parallel-work.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md)

## Contexte

`OPS-019` a déclaré huit agents de lane en indiquant à chacun son dossier de travail — « `worktree/api/`
s'il existe, sinon l'arbre principal ». Cette phrase décrit **où** travailler ; elle ne dit ni
**comment** s'y tenir, ni **ce qui arrive** si on ne s'y tient pas.

Or rien, techniquement, ne met un agent sur sa branche :

- **un sub-agent démarre dans le dossier de son appelant**, pas dans celui de sa lane. Il n'a ni
  dépôt ni branche à lui — seulement son contexte et son prompt ;
- **le shell repart de la racine du dépôt entre deux commandes**. Un chemin relatif, un `git commit`
  sans `-C`, un `sed -i` : tout cela édite l'arbre principal, silencieusement ;
- **deux agents lancés dans le même dossier sont sur la même branche**, et le `checkout` de l'un
  l'arrache sous les pieds de l'autre.

Le mode de panne n'est pas théorique : une série d'éditions en chemins relatifs a déjà écrit dans
l'arbre principal au lieu du worktree visé, et une copie de dossier suivie d'un `git checkout --` a
détruit du travail non commité.

La même carte traite une seconde question restée implicite : **que fait un agent quand sa tâche
déborde sur une autre lane ?** `OPS-019` disait qu'une demande transverse « se découpe en autant de
tâches », sans dire qui écrit quoi. Un agent laissé sans consigne écrit la carte de l'autre lane sur
**sa** branche — et cette carte atterrit sur `develop`, dans un commit qui n'est pas le sien, sans le
code qu'elle décrit.

## Périmètre

- Ajouter à chaque agent une section **Ton dossier de travail** : établir le chemin, vérifier la
  branche, refuser d'écrire en cas d'écart.
- Porter le mécanisme et son pourquoi dans le socle commun de `CLAUDE.md`.
- Écrire le protocole de passage de relais entre lanes.

## Hors périmètre

- **L'isolation automatique par worktree temporaire.** Elle existe côté outil, mais crée un dossier
  au nom variable, hors de `worktree/`, donc **sans bloc de ports ni stack Docker** — un espace qu'on
  veut voir tourner ne peut pas s'en contenter. Les worktrees de `make worktree-add` restent la
  référence.
- **Le verrou technique de périmètre**, toujours écarté (cf. `OPS-019`).

## Décisions de cadrage

### La vérification de branche est bloquante

L'agent ne se contente pas de connaître son dossier : il exécute
`git -C <ton dossier> branch --show-current` et **s'arrête** si la réponse n'est pas sa branche. Un
avertissement ne suffirait pas — le coût d'un faux départ est un commit sur la mauvaise branche, ou
pire, sur `develop`.

C'est le pendant, côté agent, de la règle qui veut qu'on vérifie la branche **dès la première
modification** et non au moment de committer : un `gitStatus` d'ouverture de session est une photo,
il périme dès qu'une PR est mergée.

### Le chemin se réécrit en entier, à chaque commande

La consigne aurait pu se contenter de « travaille en chemins absolus ». Elle précise **pourquoi la
variable de shell ne sauve pas** : l'état du shell ne survit pas d'une invocation à l'autre, donc un
`WT=…` posé une fois est vide au coup suivant, et le chemin redevient relatif sans que rien ne le
signale. C'est exactement le mode de panne qu'on cherche à fermer.

### Ce qui sort d'une lane remonte

Un agent qui découvre un besoin hors de son périmètre **nomme ce qui manque et s'arrête**. Il n'écrit
ni le code ni la carte de l'autre lane.

La raison est le workflow lui-même : une carte voyage dans le commit de **sa propre** tâche, sur la
branche de sa lane, et se crée au début du cycle de l'agent qui va l'implémenter. Une carte `API-`
écrite depuis `espace/entreprise` arriverait sur `develop` dans un commit `feat(entreprise)`, sans le
code qu'elle décrit — le contraire de « un commit = une tâche ».

Corollaire assumé : **les agents ne s'appellent pas entre eux**, et n'en ont pas l'outil. Une
bifurcation remonte toujours à l'agent principal, seul endroit où le périmètre de la demande reste
visible d'un coup d'œil.

## Règles et cas limites

- Le mécanisme (procédure complète, pourquoi) vit dans `CLAUDE.md` § *Ton dossier de travail* ; chaque
  agent n'en porte que la version paramétrée par sa branche. Un fichier d'agent pointe, il ne recopie
  pas.
- Deux lanes n'ont pas encore de worktree — `candidat` et `design`. Leur agent le crée par
  `make worktree-add ESPACE=<espace>` plutôt que de se rabattre sur l'arbre principal, qui est sur
  `develop`.

## Documentation impactée

Aucune : `parallel-work.md` porte déjà le worktree par espace et le piège des chemins relatifs. Cette
carte traduit ces règles en procédure d'agent, elle ne les redéfinit pas.

## Vérification

Aucune gate à jouer — la tâche ne produit pas de code exécutable. Les preuves sont deux relevés sur
l'arbre de travail :

- les huit fichiers de `.claude/agents/` portent une section **Ton dossier de travail** citant leur
  propre branche, et leur ligne de tableau ne promet plus « sinon l'arbre principal » ;
- `CLAUDE.md` porte la procédure complète une seule fois, et le protocole de relais dans
  § *Comment chacun livre*.

## Livré

- `.claude/agents/espace-api.md` **(M)**, `espace-entreprise.md` **(M)**, `espace-candidat.md`
  **(M)**, `espace-admin.md` **(M)**, `espace-public.md` **(M)**, `espace-socle-webapp.md` **(M)**,
  `espace-design.md` **(M)**, `espace-outillage.md` **(M)** — section *Ton dossier de travail* en
  trois temps (établir, vérifier, absolu partout) ; la ligne de tableau renvoie à la section.
- `CLAUDE.md` **(M)** — § *Ton dossier de travail* dans le socle commun (un agent ne possède pas une
  branche, c'est le dossier qui la porte) ; protocole de relais entre lanes dans § *Comment chacun
  livre* ; le réflexe « chemins absolus » de la liste renvoie à la procédure.

## Définition de terminé

- [x] Chaque agent établit son dossier et vérifie sa branche avant d'écrire.
- [x] La procédure interdit les chemins relatifs et explique pourquoi une variable de shell ne sauve
      pas.
- [x] Un agent sait quoi faire quand sa tâche déborde : nommer, s'arrêter, remonter.
- [x] Le mécanisme n'est écrit qu'à un seul endroit.
