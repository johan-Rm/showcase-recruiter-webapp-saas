# TASK OPS-026 — l'état des lanes se lit en une commande

- **Créée le** : 2026-08-02.
- **Session** : `bc77f304-4621-4ca8-a850-38c421b122f5`.
- **Statut** : ✅ fait le 2026-08-02 — `/etat-des-lanes` rapporte l'état des huit lanes en lecture
  seule, le backlog en est exclu, et les trois documents qui annonçaient « cinq commandes » en
  comptent six.
- **Type** : Gouvernance IA — commande de lecture, sixième moment du cycle.
- **Tests** : `sans objet` — la carte ne produit aucun code exécutable, elle ajoute un fichier
  Markdown de commande. La preuve est l'exécution de sa procédure sur l'état réel du dépôt, dont la
  sortie est reproduite dans les *Décisions de cadrage*.
- **Réf** : `OPS-025` — la règle « un agent n'ouvre aucune tâche de sa propre initiative », que
  cette commande doit servir sans l'enfreindre · `OPS-003` — une stack Docker par worktree, qui a
  rendu les lanes réellement simultanées ·
  [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) — les cinq
  commandes du cycle · [apres-pr-mergee.md](../../.claude/commands/apres-pr-mergee.md) — dont
  l'énumération des branches et la lecture d'état sont reprises ici en lecture seule

## Contexte

**Personne ne peut dire, en un geste, ce qui est en train d'avancer.** Le dépôt porte huit lanes,
sept worktrees, et plusieurs sessions écrivent en même temps. L'information existe — elle est
éparpillée sur cinq commandes git et sept dossiers, et personne ne la reconstitue.

Les cinq commandes du cycle sont toutes **transactionnelles** : chacune ouvre, mène, clôt ou livre
**une** tâche sur **une** lane. Aucune ne répond à la question qui précède le cycle — *où en
est-on ?* `/apres-pr-mergee` s'en approche le plus, mais elle énumère les branches pour les
**réaligner**, exige un arbre propre, et refuse donc de s'exécuter exactement quand du travail est
en cours, c'est-à-dire quand la question se pose.

Le coût est double. Une session qui démarre ne sait pas qu'une lane porte déjà une tâche non
commitée, et le contrat « une tâche à la fois » se rompt sans que personne ne le voie. Une lane
prend du retard sur `origin/develop` en silence, et le conflit se manifeste plusieurs tâches plus
tard.

**`OPS-025` rend cette commande à la fois plus nécessaire et plus délicate.** Plus nécessaire :
depuis que les cartes portent une session, savoir *à qui* appartient une carte non suivie a un sens.
Plus délicate : une commande qui liste les cartes ouvertes est, par construction, à un pas de
proposer d'en reprendre une — et c'est précisément la brèche qu'`OPS-025` a fermée.

## Périmètre

- Une commande `.claude/commands/etat-des-lanes.md`, **en lecture seule**, qui rapporte pour chaque
  branche `espace/*` : son worktree, son état d'avancement, ses tâches non mergées, ses cartes non
  suivies et son retard sur `origin/develop`.
- Un argument optionnel restreignant l'**affichage** à une lane, sans restreindre la collecte.
- La lecture des seuls champs `**Statut**` et `**Session**` des cartes non suivies.
- Un format de sortie fermé : un tableau, trois listes conditionnelles, et rien après.

## Hors périmètre

- **Le backlog.** Les cartes déjà commitées et restées `⏳ à faire` se comptent par dizaines ; elles
  ne disent rien de ce qui avance, et les afficher ferait de l'état une file d'attente.
- **Toute action corrective** : rebase, réalignement, création de worktree, ouverture de tâche. La
  commande nomme ce qui cloche, elle ne le répare pas.
- **L'état réel des Pull Requests.** `gh` n'est pas authentifié dans cet environnement ; il est
  déduit de la présence de la branche sur `origin`, et rapporté comme une probabilité.
- Les branches `hotfix/*` et `main`, dont le cycle ne part pas de `develop`.
- Toute modification des cinq commandes existantes.

## Décisions de cadrage

**« En cours » désigne ce qui est engagé, pas ce qui est envisagé.** Trois signaux le prouvent —
arbre sale, commits d'avance, carte non suivie. Le critère alternatif (« toute carte au statut
ouvert ») a été écarté : il aurait produit une liste de plus de trente entrées, illisible, et surtout
une liste de *choses à prendre* là où on demande une liste de *choses qui bougent*.

**La commande ne conclut pas.** Pas de synthèse, pas de « prochaine étape », pas de priorisation. Le
format de sortie l'impose plutôt que de le recommander, parce qu'une consigne de style ne tient pas
face à la pente naturelle d'un agent qui vient de lister sept lanes. C'est la contrepartie directe
d'`OPS-025` : la commande sert à décider, la décision reste humaine.

**Les cartes non suivies se lisent par leur en-tête, jamais par leur corps.** Deux champs suffisent
à situer une carte ; lire ses *Décisions de cadrage* revient à s'approprier un cadrage pris ailleurs
— le mécanisme exact de l'incident du 2026-08-02.

**Le retard se cumule à l'état au lieu de le remplacer.** Une lane au repos avec quatre commits de
retard sera rebasée sans douleur par `/demarrer-tache` ; une lane **en cours** avec le même retard
est un conflit qui mûrit. Les deux méritent d'être distingués.

**Preuve d'exécution** — la procédure jouée sur l'état réel du dépôt le 2026-08-02 :

```
Lane           État                  En cours                                  Retard
admin          tâche en cours        ADM-012 cadrée + 1 commitée, arbre sale   4 commits
api            prête pour /creer-pr  2 tâches non mergées                      —
candidat       au repos              — (aucun worktree)                        —
design         au repos              — (aucun worktree)                        —
entreprise     tâche en cours        ENT-032/034/035/037 cadrées, arbre sale   —
outillage      tâche en cours        OPS-026 cadrée + 1 commitée, arbre sale   —
public         au repos              —                                         —
socle-webapp   prête pour /creer-pr  1 tâche non mergée                        —

— cartes non suivies portant une AUTRE session —
  entreprise · ENT-037 → session 57a6103a-e57e-429e-82e9-aed454da48f3
  admin · ADM-012, entreprise · ENT-032/034/035 → champ absent (antérieures à OPS-025)
```

La lane `admin` illustre à elle seule ce que la commande apporte : une tâche en cours **et** quatre
commits de retard sur l'intégration, deux faits qu'aucune commande existante ne met côte à côte.

Deux exécutions à dix minutes d'intervalle ont par ailleurs donné des états différents sur `api` et
`socle-webapp` — leurs cartes non suivies étaient devenues des commits entre-temps. C'est la
confirmation que la commande lit un état **vivant**, écrit par plusieurs sessions simultanées, et la
raison pour laquelle elle ne conclut pas : sa photo peut être périmée avant d'être lue.

## Règles et cas limites

- **Lane sans worktree** — les mesures « arbre sale » et « cartes non suivies » sont *sans objet*,
  et se rapportent ainsi. Si la branche porte malgré tout des commits d'avance, le travail existe
  sans dossier pour le reprendre : le signaler avec `make worktree-add ESPACE=<espace>`.
- **Carte sans champ `Session`** — carte antérieure à `OPS-025`, pas carte orpheline. Ne rien en
  déduire, ne pas l'attribuer.
- **Carte portant une autre session** — la nommer comme telle, sans jamais suggérer de la reprendre.
- **Arbre sale sans carte non suivie** — rapporté tel quel : soit du travail sur une carte déjà
  commitée, soit du travail hors cycle. La commande n'arbitre pas.
- **Les lanes se découvrent**, jamais en dur : `git for-each-ref 'refs/heads/espace/*'`. Un espace
  ajouté serait manqué par une liste écrite à la main — le piège qui avait déjà touché
  `/demarrer-tache`.
- **`git fetch origin --prune` est le seul écrit toléré**, et il ne touche aucun arbre de travail.

## Documentation impactée

- [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) — l'encadré
  « Outillage : cinq commandes projet » en compte désormais **six**, et la nouvelle est la seule qui
  ne modifie rien.
- [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — la même
  énumération, côté cycle de travail : la commande y est posée **à côté** du cycle, pas dedans.
- [CLAUDE.md](../../CLAUDE.md) — l'encadré « Cinq commandes projet outillent le cycle » du socle
  commun aux agents, troisième et dernier endroit où le compte était écrit.

## Vérification

**Procédure jouée en entier sur le dépôt réel, deux fois** (sortie de la seconde passe reproduite en
*Décisions de cadrage*). Les huit branches `espace/*` ont été découvertes par `for-each-ref`,
mesurées, classées, et les cartes non suivies attribuées à leur session.

- **Le classement discrimine** : les six états prévus sont sortis sur les huit lanes — trois « tâche
  en cours », deux « prête pour `/creer-pr` », trois « au repos » dont deux sans worktree.
- **Le retard remonte séparément de l'état** : `admin` sort *tâche en cours* **et** `4 commits` de
  retard, le cas qu'aucune commande existante ne met côte à côte.
- **L'attribution par session fonctionne** : `ENT-037` est ressortie sur la session
  `57a6103a-e57e-429e-82e9-aed454da48f3`, distincte de la session courante ; quatre cartes sans le
  champ ont été rapportées comme antérieures à `OPS-025`, pas comme orphelines.
- **La lecture seule tient** : `git status --porcelain` de l'arbre principal vide après les deux
  passes, aucun `switch`, `checkout` ni `reset` exécuté.
- **L'état lu est vivant** : `api` et `socle-webapp` ont changé d'état entre les deux passes, une
  autre session ayant commité leurs cartes dans l'intervalle.

## Livré

- `.claude/commands/etat-des-lanes.md` **(+)** — la commande : périmètre, définition de « en
  cours », procédure en cinq étapes, table de classement, format de sortie fermé, garde-fous de
  lecture seule.
- `dev-book/tasks/OPS-026-*.md` **(+)** — cette carte.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md` **(M)** — la sixième commande, son
  positionnement hors cycle et l'exclusion du backlog.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md` **(M)** — la même mention, côté cycle.
- `CLAUDE.md` **(M)** — l'encadré du socle commun aux agents.

## Reste à faire (hors périmètre)

- **Rien pour cette tâche.** Deux cartes `OPS-027` et `OPS-028`, apparues dans ce worktree pendant
  l'implémentation, appartiennent à une autre session et touchent `ai-workflow.md` et
  `parallel-work.md` : le premier fichier est modifié ici, un conflit d'édition est possible à leur
  commit. Signalé, pas traité.

## Définition de terminé

- [x] `.claude/commands/etat-des-lanes.md` existe, avec son frontmatter et ses garde-fous.
- [x] La procédure a été jouée sur l'état réel du dépôt, et sa sortie figure dans cette carte.
- [x] `git-workflow.md` mentionne la sixième commande.
- [x] La commande ne propose aucune tâche, ne classe rien par priorité et ne conclut pas.
