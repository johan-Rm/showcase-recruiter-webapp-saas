# TASK OPS-034 — un sujet s'évoque avant de devenir une tâche

- **Créée le** : 2026-08-12.
- **Session** : `065767ad-ebd0-42e2-9689-262dcab79ba3`.
- **Statut** : ✅ fait le 2026-08-12 — `/evoquer-un-sujet` existe en lecture seule, avec ses quatre
  sorties et sa frontière avec `OPS-025` écrites dans le fichier ; les deux phrases devenues fausses
  sont corrigées, et le rythme « Qualifier » n'est plus le seul sans commande.
- **Type** : Gouvernance IA — commande de discussion, en amont du cycle.
- **Tests** : `sans objet` — la carte ne produit aucun code exécutable, elle ajoute un fichier
  Markdown de commande et corrige trois documents. Aucune gate ne couvre ce périmètre : la CI ne
  porte que `api-quality`, `webapp-quality` et `commit-check`, et `scripts/ci/` ne contient qu'un
  `README.md`. La preuve est l'exécution de la commande sur un sujet réel, dont la conduite sera
  reproduite dans la *Vérification*.
- **Réf** : `OPS-026` — `/etat-des-lanes`, le précédent d'une commande hors cycle en lecture seule,
  dont la table des moments et les garde-fous sont repris ici · `OPS-025` — la règle « un agent
  n'ouvre aucune tâche de sa propre initiative », que la passerelle de fin doit servir sans
  l'enfreindre · `OPS-007` — le cycle outillé de bout en bout, dont cette commande complète l'amont ·
  [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#le-chemin-dune-demande)
  — les quatre rythmes, dont le premier est aujourd'hui sans outil

## Contexte

**Il n'existe aucun endroit pour parler d'un sujet qui n'est pas encore une décision.** La première
marche disponible est `/creer-tache`, et elle est déjà trop haute : sa quatrième dimension de cadrage
demande *quel espace de rattachement*, c'est-à-dire qu'elle présuppose deux choses jamais vérifiées —
que le sujet vaut la peine d'être fait, et qu'il tient dans une seule lane. Une conversation
exploratoire n'a donc que deux issues : un cadrage prématuré qui fige des choix avant qu'ils soient
mûrs, ou du code écrit sur une intention qui n'a pas été examinée.

Le manque est déjà décrit dans la documentation, ce qui est le meilleur signe qu'il est réel.
[ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#quatre-rythmes-pas-un-seul)
distingue quatre rythmes ; le premier — *« Qualifier — une fois par demande »*, étapes 1 et 2 : la
demande arrive en langage courant, puis elle est routée vers une ou plusieurs lanes — est **le seul
qu'aucune commande n'outille**. Les cinq commandes du cycle couvrent les étapes 3 à 14, et
`/etat-des-lanes` répond à *où en est-on*, jamais à *est-ce qu'on le fait*.

Deux conséquences se paient déjà. Le **découpage multi-lanes**, que le routage impose (« une demande
qui traverse plusieurs lanes se découpe en autant de tâches »), se fait aujourd'hui de tête, au
moment d'ouvrir la première carte — donc mal, et sans trace du raisonnement. Et le **refus** n'a
aucun lieu : la seule façon de conclure qu'un sujet ne vaut pas le coup est de ne pas l'ouvrir, ce
qui ne se distingue pas d'un oubli.

## Périmètre

- Une commande `.claude/commands/evoquer-un-sujet.md`, **en lecture seule**, qui ouvre un échange sur
  un sujet non tranché et le conduit jusqu'à une conclusion.
- Une **instruction préalable minimale** : vérifier si le sujet est déjà tranché — cadrage, cartes
  voisines, doc d'architecture — et l'annoncer en une ligne avant de discuter.
- **Quatre sorties possibles**, toutes légitimes : à faire · à ne pas faire · pas maintenant · aucun
  verdict, mais l'énoncé de ce qui manque pour trancher.
- Une **passerelle de fin** : quand le sujet est mûr, nommer les tâches qui en découlent — une par
  lane, avec son espace de rattachement — puis s'arrêter.
- La mise à jour des **quatre documents** que l'existence de cette commande rend faux.

## Hors périmètre

- **Toute trace écrite.** Ni dossier `dev-book/sujets/`, ni ligne ajoutée à `dev-book/todo.md`, ni
  note de quelque forme que ce soit. Décision explicite, motivée plus bas.
- **L'appel à `/creer-tache`.** La commande annonce les tâches à ouvrir ; les ouvrir reste un geste
  humain, et la frontière entre *on en parle* et *on l'ouvre* doit rester visible.
- **La migration de la section « Décisions ouvertes » de `dev-book/todo.md`**, qui recouvre pourtant
  le même terrain — un sujet non tranché. Chantier distinct, plus large que cette tâche.
- **Toute écriture** : code, carte, branche, commit, maquette. La liste d'outils l'interdit, elle ne
  le déconseille pas.
- Toute modification des six commandes existantes, hors la ligne de table de `/etat-des-lanes`.

## Décisions de cadrage

**Rien ne s'écrit — c'est une discussion libre.** Une note de sujet par conversation avait été
envisagée puis écartée : elle aurait créé un second backlog à côté de `dev-book/todo.md`, avec sa
convention de nommage à arbitrer et son cycle de vie à tenir, pour un contenu dont l'essentiel est
consommé dans l'échange lui-même. Le report est assumé et inscrit ici pour qu'on sache dans six mois
qu'il était délibéré : **un sujet évoqué et non tranché disparaît à la fermeture de la session.** Si
ce coût se révèle réel à l'usage, la trace écrite se rouvrira comme une tâche à part entière.

**La lecture seule est un verrou, pas une consigne.** Le frontmatter `allowed-tools` ne porte que
`Read`, `Glob`, `Grep`, `Bash(git:*)` et `AskUserQuestion` — ni `Write`, ni `Edit`. C'est le même
mécanisme qu'`/etat-des-lanes`, et pour la même raison : une consigne de style ne tient pas face à la
pente naturelle d'un agent qui vient d'explorer un sujet et voit quoi coder. Le dépôt compte
désormais **deux** commandes qui ne modifient rien.

**La conduite se fait en prose, le questionnaire à choix étant réservé aux bifurcations.** La méthode
de `/creer-tache` — tours de questions fermées jusqu'à épuisement — a été écartée pour ce moment
précis : elle est faite pour *fermer* les portes, alors qu'explorer un sujet consiste à les garder
ouvertes. Le questionnaire ressort dès qu'une vraie alternative apparaît, et à ce moment-là
seulement.

**« Pas maintenant » est une vraie réponse.** Les trois verdicts ne sont pas hiérarchisés, et le
troisième n'est pas un refus poli : il dit que le sujet est légitime et que son moment n'est pas
venu. La quatrième sortie — pas de verdict, mais l'énoncé de ce qui manque pour trancher — est tout
aussi légitime : forcer une conclusion absente fabrique de la fausse certitude.

**La passerelle nomme, elle ne propose pas — et la nuance est celle d'`OPS-025`.** Cette commande se
termine en citant des tâches, ce qui la met à un pas de la brèche du 2026-08-02 : présenter une file
d'attente comme « la suite ». La frontière tient en une phrase, à écrire dans la commande : elle
nomme ce qui découle **du sujet apporté par l'humain dans cette conversation**, et ne va jamais
chercher dans `dev-book/tasks/` ce qui traîne. Ce qu'elle produit n'est pas un choix orienté, c'est
la conséquence de ce qui vient d'être décidé ensemble.

**Écart au cadrage — le renumérotage des commandes.** La carte prévoyait quatre corrections de
documentation ; l'implémentation en a produit une cinquième, non anticipée. `git-workflow.md`
désigne les commandes hors cycle par un ordinal — « une sixième », « une septième » —, si bien
qu'insérer `/evoquer-un-sujet` à sa place logique, auprès de l'autre commande en lecture seule,
décale `/demarrer-taches` de **septième à huitième**. Corrigé dans le même diff : laisser deux
« septième » dans le même encadré aurait été une incohérence introduite par cette tâche.

**Écart au cadrage — ce que la preuve pouvait prouver.** La *Définition de terminé* exigeait que la
commande soit « jouée sur un sujet réel ». Une commande de conversation ne peut pas l'être seule :
ses étapes *explorer* et *conclure* attendent un interlocuteur. La ligne a été ramenée à ce qui se
vérifie sans humain — l'instruction préalable, la reformulation du manque et le découpage par lane —,
et la limite est inscrite dans la *Vérification* plutôt que masquée par une case cochée à tort.

**Deux phrases de la documentation deviennent littéralement fausses**, et c'est ce qui justifie de
toucher quatre fichiers plutôt qu'un :
[git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) affirme
d'`/etat-des-lanes` qu'elle est « la seule commande du dépôt qui **ne modifie rien** », et le
[CLAUDE.md](../../CLAUDE.md) racine annonce « deux [commandes] s'ajoutent sans y entrer ». Laisser
l'une des deux en l'état créerait exactement la copie périmée que la doc dit avoir déjà payée quatre
fois.

## Règles et cas limites

- **Sujet déjà tranché** — la commande le dit et s'arrête avant de discuter : rouvrir un arbitrage
  écrit demande de le vouloir, pas de l'ignorer. Elle cite où la décision vit.
- **Sujet qui est en fait une tâche** — un besoin déjà clair, tenant dans une lane, n'a rien à faire
  ici : renvoyer vers `/creer-tache` sans dérouler la conversation.
- **Sujet qui traverse plusieurs lanes** — le cas nominal, et la raison d'être de la commande. Le
  découpage se fait ici, avant qu'une seule carte existe.
- **Sujet hors produit** (méthode, outillage, infrastructure) — recevable : le routage désigne alors
  `espace/outillage`, comme pour cette carte elle-même.
- **La conversation s'arrête sans conclure** — issue normale. Aucun verdict forcé, aucune tâche
  nommée, et rien n'est perdu qui aurait dû être gardé : c'est le report assumé ci-dessus.
- **Aucune tâche n'est jamais ouverte par la commande**, même quand le verdict est « à faire » et que
  la lane est évidente.

## Documentation impactée

- [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — le rythme
  « Qualifier — une fois par demande » cesse d'être orphelin, et le § *Les commandes qui outillent le
  cycle* compte une commande de plus à côté du cycle.
- [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) — l'affirmation
  « la seule commande du dépôt qui ne modifie rien » devient fausse ; la nouvelle commande y est
  décrite comme **précédant** le cycle.
- [CLAUDE.md](../../CLAUDE.md) — l'encadré du socle commun : « deux s'ajoutent sans y entrer » passe
  à trois.
- [.claude/commands/etat-des-lanes.md](../../.claude/commands/etat-des-lanes.md) — une ligne dans la
  table *Moment / Commande / Ce qu'elle possède*, au-dessus de « Se situer ».

## Vérification

**La procédure de la commande jouée à la main sur un sujet réel** — les textes intégrés de Nuxt UI
affichés en anglais —, le fichier n'étant pas encore chargé au moment de l'écrire.

- **Étape 1, s'instruire au minimum** : `webapp/base/app/app.vue:66` monte `<UApp>` **sans attribut
  `:locale`** ; aucune des sept cartes contenant *locale*, *i18n* ou *nuxt-ui* ne porte le sujet ; il
  est absent de `dev-book/cadrage/`. Sortie exploitable : le sujet n'est pas tranché, la commande
  continue au lieu de renvoyer vers une décision existante.
- **Étape 2, reformuler le manque** : « dans les quatre espaces, dès qu'un composant Nuxt UI affiche
  son texte intégré — un tableau vide annonce *No data*, la pagination s'écrit en anglais — alors que
  le produit est en français ; et le contrôle du texte en dur ne les voit pas, ces chaînes vivant
  dans `node_modules` ». Le manque est énoncé avec son adresse, sans présupposer la solution.
- **Étape 5, le découpage par lane** : le fichier à corriger appartient au socle partagé, et les
  **quatre** espaces le consomment. Aucun consommateur unique ne se dégageant, la règle de routage
  tranche vers `espace/socle-webapp`, préfixe `WEB`, **une seule tâche**. Le découpage a produit un
  résultat non trivial alors qu'aucune carte n'existait — la fonction même de l'étape.
- **Le verrou de lecture seule tient** : `allowed-tools: Bash(git:*), Read, Glob, Grep,
  AskUserQuestion`, sans `Write` ni `Edit`. Aucun fichier n'a été écrit pendant ce galop d'essai.
- **Le périmètre documentaire a été vérifié, pas supposé** : `parallel-work.md` ne liste pas les
  commandes, et aucune table des six autres fichiers de `.claude/commands/` ne mentionne les
  commandes hors cycle — seule celle d'`/etat-des-lanes` le fait. Les quatre corrections sont donc
  exhaustives.

**Ce que cette preuve n'établit pas** : les étapes *explorer* et *conclure* attendent un
interlocuteur. Seul, on montre que les quatre sorties sont câblées dans le fichier, pas qu'un verdict
en sorte juste. Cela ne se vérifiera qu'à la première conversation réelle — limite inhérente à une
commande de discussion, et non travail resté en plan.

## Livré

- `.claude/commands/evoquer-un-sujet.md` **(+)** — la commande : sa place dans la table des moments,
  le § *Rien ne s'écrit, et c'est délibéré*, la frontière avec `OPS-025`, une procédure en cinq
  étapes, les quatre sorties, le format de clôture et cinq garde-fous.
- `dev-book/tasks/OPS-034-*.md` **(+)** — cette carte.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md` **(M)** — « la seule commande du dépôt
  qui ne modifie rien » devient « les deux seules », la nouvelle commande est décrite en septième, et
  `/demarrer-taches` passe septième → huitième.
- `CLAUDE.md` **(M)** — l'encadré du socle commun : « deux s'ajoutent sans y entrer » → trois.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md` **(M)** — les étapes 1-2 du chemin d'une
  demande portent désormais leur commande, et le § *Les commandes qui outillent le cycle* en compte
  trois à côté du cycle.
- `.claude/commands/etat-des-lanes.md` **(M)** — sa ligne de table et la fin de son exclusivité.

## Reste à faire (hors périmètre)

- **La cohabitation avec `dev-book/todo.md`.** Sa section *Décisions ouvertes* porte déjà, sans
  commande pour l'alimenter, exactement ce que `/evoquer-un-sujet` produira : des sujets non
  tranchés. Les deux vont coexister sans se connaître. Signalé au cadrage, délibérément non traité —
  la migration est un chantier distinct.
- **La trace écrite**, écartée ici. Si l'usage montre qu'un sujet perdu coûte cher, elle se rouvre
  comme une tâche à part entière, pas comme un ajout discret à cette commande.

## Définition de terminé

- [x] `.claude/commands/evoquer-un-sujet.md` existe, avec son frontmatter, et sa liste
      `allowed-tools` ne contient ni `Write` ni `Edit`.
- [x] La commande énonce ses quatre sorties possibles et interdit explicitement d'en forcer une.
- [x] La frontière avec `OPS-025` est écrite noir sur blanc : nommer ce qui découle du sujet apporté,
      jamais parcourir `dev-book/tasks/`.
- [x] Le hors périmètre « aucune trace écrite » figure dans la commande elle-même, pas seulement ici.
- [x] Les deux phrases devenues fausses sont corrigées dans `git-workflow.md` et `CLAUDE.md`.
- [x] `ai-workflow.md` rattache la commande au rythme « Qualifier », et `/etat-des-lanes` porte sa
      nouvelle ligne de table.
- [x] Les étapes solitaires de la procédure — instruction préalable, reformulation, découpage par
      lane — ont été jouées sur un sujet réel, et leur sortie figure dans la *Vérification*, avec la
      limite de ce que la preuve établit.
