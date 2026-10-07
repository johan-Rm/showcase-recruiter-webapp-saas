# TASK OPS-027 — le workflow IA raconte le chemin d'une demande

- **Créée le** : 2026-08-02.
- **Session** : `65acb369-82b3-411a-a75f-31de108a5cd1`.
- **Statut** : ✅ fait le 2026-08-02 — `ai-workflow.md` raconte le chemin d'une demande jusqu'aux
  commits intégrés, avec deux schémas ; la définition de l'espace et le champ `Session` l'ont
  rejointe depuis `git-workflow.md` et `parallel-work.md`, dans le même commit.
- **Type** : Documentation — réécriture d'une page et déménagement de contenu depuis une autre.
- **Tests** : `sans objet` — la carte ne produit aucun code exécutable, et **aucune gate CI ne couvre
  `docs/`** (vérifié le 2026-08-02 : seuls `api-quality.yml`, `webapp-quality.yml` et
  `commit-check.yml` existent). La preuve est le **rendu réel des deux schémas** dans le site
  `docs/showcase-recruiter-webapp-saas/`, servi en local, et la résolution de toutes les ancres internes.
- **Réf** : `OPS-028` — `parallel-work.md` se recentre en aval · `OPS-029` — le routeur suit ·
  `OPS-025` — la discipline de session dont cette page portera le *pourquoi* · `OPS-026` **amont
  bloquant**, elle modifie les deux mêmes fichiers ·
  [architecture.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md) et
  [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) — les précédents Mermaid
  supposés, que la vérification a démentis.

## Contexte

**Personne ne trouve le chemin d'une demande jusqu'au code intégré.** Il existe, il est appliqué tous
les jours, mais il est écrit en morceaux sur trois pages qui ne se désignent pas de propriétaire :

- la **définition d'un espace** — périmètre de code, branche, préfixe — vit dans
  `git-workflow.md` §22-66, alors que c'est une notion d'organisation du travail IA et pas une notion
  git. Elle est re-mentionnée dans `ai-workflow.md` §20, `parallel-work.md` §17 et `CLAUDE.md` :
  quatre endroits, aucun propriétaire ;
- la **carte de tâche** est éclatée en trois : sa numérotation dans `git-workflow.md` §110, son
  dossier partagé et son champ `Session` dans `parallel-work.md` §95 et §111, « cadrer avant de
  coder » dans `ai-workflow.md` §81 ;
- `ai-workflow.md` §70-79 **duplique** le contrat `README → règle → script CI → gate` que
  `3.quality/testing-and-ci.md` possède déjà — trente lignes avant d'énoncer elle-même la règle de
  non-duplication.

Résultat : `ai-workflow.md` est aujourd'hui une **liste de règles écrite pour un agent**. Elle ne dit
ni *pourquoi* huit lanes, ni *pourquoi* un worktree par espace, ni *pourquoi* la carte avant le code.
Un humain qui découvre le dépôt doit lire trois pages et reconstituer l'ordre lui-même.

## Périmètre

`ai-workflow.md` est **réécrite de bout en bout** et devient la page du process. Elle prend le titre
« Workflow IA — d'une demande au code livré » ; « VSCode » disparaît, l'éditeur n'a jamais été le
sujet.

Ce qu'elle porte après :

1. **Le chemin d'une demande**, en quatorze étapes groupées par **portée** — une fois par demande,
   une fois par lane, à chaque carte, une fois par PR. C'est cette notion de portée qui manque
   aujourd'hui : une étape ne rejoue pas au même rythme qu'une autre.
2. **Un schéma du process** — le fan-out (une demande → plusieurs lanes → plusieurs cartes
   par lane → un commit chacune → **une PR par lane**) et la boucle qui se referme après le commit.
3. **La définition de l'espace et son routage** — un périmètre de code, une branche, un préfixe, un
   agent. Avec la table qui **définit** (espace → périmètre, branche, préfixe), les deux règles de
   routage, et pourquoi un sub-agent par lane plutôt qu'un agent unique vigilant.
4. **Un schéma d'architecture** — Claude Code → sub-agent → worktree → périmètre → stack →
   branche → PR → `develop`, en marquant le seul lien qui ne soit pas mécanique : rien ne met un
   agent dans son dossier, il doit s'y mettre.
5. **La carte de tâche comme spec** — écrite avant le code, sa numérotation lue sur toutes les
   branches, son champ `Session` et ce qu'il protège.
6. **Le pourquoi de la discipline de session** — l'incident du 2026-08-02, et pourquoi c'est la
   *proposition* d'une file d'attente, pas son exécution, qui ouvre la brèche.
7. **La boucle de delivery et ses commandes**, avec la borne : la page s'arrête au réalignement des
   branches, `develop` à jour.

Deux déménagements, **dans le même commit** que la réécriture :

- `git-workflow.md` **perd** la définition de l'espace, la table des huit et la règle de l'espace
  consommateur (§22-66). Il garde le **nommage** des branches, leur durée de vie, et renvoie vers
  `ai-workflow.md` pour la liste ;
- `parallel-work.md` **perd** le champ `Session` et la propriété d'une carte (§111-120). Il garde le
  fait que `dev-book/tasks/` est un dossier partagé, et renvoie.

Une suppression sèche : le contrat `README → règle → gate CI` d'`ai-workflow.md` §70-79 disparaît au
profit d'un renvoi vers `3.quality/testing-and-ci.md`, qui le possède.

## Hors périmètre

- **Le contenu de `.claude/commands/`.** Les cinq commandes du cycle — plus `/etat-des-lanes`,
  ajoutée par `OPS-026` — restent la source opérationnelle. La page les cite et dit à quel moment
  chacune sert ; elle ne recopie pas leurs procédures.
- **`3.quality/testing-and-ci.md`**, autrement que pour y renvoyer.
- **Toute règle nouvelle.** Ce chantier **déplace et réécrit**, il ne décide rien. Une règle qui
  n'existe aujourd'hui nulle part n'entre pas par cette porte.
- **La release** — `develop` vers `main`, tag `vX.Y.Z`, déploiement. Possédée par `git-workflow.md`
  et `testing-and-ci.md`, la page s'arrête avant et renvoie.
- **`parallel-work.md` au-delà du champ `Session`** : son allègement complet est `OPS-028`.
- **`CLAUDE.md`** : `OPS-029`.

## Décisions de cadrage

### La portée d'une étape est l'information qui manquait

Le premier découpage listait quatorze étapes à plat. Il ratait l'essentiel : « se positionner dans
son worktree » joue **une fois par lane**, « écrire la carte » joue **à chaque carte**, « ouvrir la
PR » joue **une fois par branche**. Une liste plate laisse croire que tout rejoue au même rythme, et
c'est précisément la confusion qui fait enchaîner deux implémentations avant un commit.

Le schéma et le texte portent donc quatre portées explicites : par demande, par lane, par carte, par
PR.

### Une demande produit plusieurs cartes sur une même lane, et une seule PR

Tranché avec l'humain pendant le cadrage. Le fan-out complet est : une demande se répartit entre les
lanes qu'elle touche, produit dans chacune **autant de cartes qu'il faut** — c'est le cas courant —,
chaque carte porte un commit, et la lane ouvre **une** PR qui les emmène toutes.

Corollaire qui donne son sens à la dernière étape : la branche ne meurt pas avec la PR. Elle est
**longue** et resservira pour la demande suivante. Le réalignement sur `develop` après merge n'est
donc pas du rangement, c'est ce qui la rend réutilisable.

### Les garde-fous s'inscrivent dans l'étape, pas en annexe

Travail parallèle, discipline de session, staging par chemins explicites : ces règles **traversent**
la chaîne au lieu d'y prendre place. Deux traitements possibles — une section « bonnes pratiques » en
fin de page, ou une inscription dans l'étape où chacune mord.

C'est la seconde. Une annexe se lit une fois et ne se relit jamais ; une règle posée à l'endroit où
elle sert est lue au moment où elle sert. `make worktree-list` appartient à l'étape de
positionnement, le champ `Session` à l'écriture de la carte, `git add <chemin>` au commit.

### Deux tables, deux usages — et ce n'est pas un doublon

`CLAUDE.md` porte déjà une table espace → agent → branche → préfixe. Y ajouter celle d'`ai-workflow`
ressemble à une duplication ; ce n'en est pas une, à condition que les colonnes diffèrent :

| Fichier | Colonnes | Se lit pour |
| --- | --- | --- |
| `ai-workflow.md` | espace, **périmètre de code**, branche, préfixe | comprendre ce qu'est un espace |
| `CLAUDE.md` | **ce que la demande touche**, agent, branche, préfixe | choisir une lane |

L'une définit, l'autre route. L'option « une seule table » a été écartée : le routeur perdrait sa
lisibilité immédiate, et choisir une lane demanderait d'ouvrir une autre page.

### La règle de discipline reste dans `CLAUDE.md`, le pourquoi vient ici

Faire déménager entièrement la discipline de session vers `ai-workflow.md` aurait été cohérent avec
« le routeur pointe, il ne définit pas ». C'est écarté pour une raison pratique : `CLAUDE.md` est le
fichier **réellement chargé à chaque session**, donc le seul dont la règle s'applique. Une règle
déplacée dans une page de doc devient une règle qu'il faut penser à lire.

Répartition retenue : l'**énoncé court** reste dans `CLAUDE.md`, le **raisonnement et l'incident**
viennent dans `ai-workflow.md`. Deux registres, pas deux copies.

### Deux schémas, pas trois

Le cadrage a produit trois vues : la chaîne du process, une topologie détaillée, et une vue
d'architecture. La topologie se dissout en prose — c'était du texte déguisé en pages. Les deux autres
deviennent du contenu de la page.

### Écart au cadrage — les schémas sont en art ASCII, pas en Mermaid

**Le cadrage se fondait sur un précédent qui n'en était pas un.** `architecture.md` et
`data-model.md` portent des blocs ```` ```mermaid ```` depuis des mois, d'où la conclusion « rien à
installer ». Le rendu réel dit autre chose : le site Docus les sort en **bloc de code coloré**, pas
en diagramme.

Preuve, site servi en local le 2026-08-02 :

```
<pre class="… language-mermaid shiki shiki-themes …">graph TB
```

Zéro élément SVG de diagramme sur la nouvelle page **comme sur `architecture.md`**. Docus ne rend pas
Mermaid dans cette installation.

Trois issues ont été présentées : garder le bloc de code, activer le rendu Mermaid, ou dessiner en
art ASCII. **L'art ASCII est retenu** — il s'affiche exactement comme écrit, sans dépendance, et le
dépôt a déjà ce précédent-là, celui-ci authentique : le modèle de branches et le flux de release de
`git-workflow.md` sont dessinés ainsi.

Activer le rendu Mermaid reste souhaitable, mais c'est une **décision nouvelle** — une dépendance et
une configuration du site — donc hors du périmètre de cette carte. Nommé, pas repris.

### Écart au cadrage — une commande a été touchée

`.claude/commands/` est déclaré hors périmètre, et il le reste pour son **contenu**. Mais
`demarrer-tache.md` désignait `git-workflow.md#branches-de-travail…` comme « seule source de vérité »
du tableau `espace → périmètre → préfixe` — un lien que ce commit rend **faux**.

Retarger un pointeur n'est pas recopier une procédure, et la règle « le commit embarque la
documentation qu'il rend fausse » l'emporte. Deux autres renvois devenus faux ont été réparés pour la
même raison : `parallel-work.md` §9 et la ligne d'`index.md` qui décrit cette page.

En revanche `ui-ux-design/CLAUDE.md` **n'a pas été touché** : il pointe vers `git-workflow.md` pour
sa lane et son préfixe, sans ancre, et ce fichier appartient à `espace/design`. Nommé en aval.

### Une grosse carte plutôt qu'un doublon transitoire

`OPS-027` touche deux fichiers et réécrit 128 lignes. Découper en « j'écris ici » puis « je retire
là » aurait donné deux commits plus courts, mais entre les deux la définition de l'espace aurait
existé à deux endroits — exactement le défaut que ce chantier corrige. Un déménagement reste
atomique.

## Règles et cas limites

- **Écrire pour un humain qui découvre le dépôt.** La page explique *pourquoi* huit lanes, *pourquoi*
  un worktree par espace, *pourquoi* la carte avant le code. Une liste d'impératifs adressée à un
  agent est un échec, même si elle est exacte.
- **Chaque information a un propriétaire.** Toute reprise d'un contenu possédé ailleurs se fait par
  renvoi. La règle vaut pour la page elle-même : elle l'énonce et s'y tient.
- **Le renvoi doit pointer vers la section**, pas vers la page entière, quand la page est longue.
- **`OPS-026` est un amont bloquant.** Elle modifie `ai-workflow.md` et `git-workflow.md` en fichiers
  non commités depuis une autre session (`bc77f304-…`), pour y documenter `/etat-des-lanes`.
  L'implémentation ne démarre **qu'après son commit**, sinon ces douze lignes sont écrasées.
- **Ne pas toucher aux fichiers de l'autre session** : ni `.claude/commands/etat-des-lanes.md`, ni
  `dev-book/tasks/OPS-026-*.md`.
- **Staging par chemins explicites** au commit : le worktree est partagé avec une session
  concurrente, `git add -A` embarquerait son travail.

## Documentation impactée

- [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) **(M)** — réécrite
  intégralement.
- [git-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md) **(M)** — perd la
  définition de l'espace et la table des huit, gagne un renvoi.
- [parallel-work.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md) **(M)** — perd
  le champ `Session`, gagne un renvoi. Son allègement complet est `OPS-028`.
- [index.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/index.md) **(M)** — la ligne qui décrit
  `ai-workflow.md` ne disait plus ce que la page fait.
- [demarrer-tache.md](../../.claude/commands/demarrer-tache.md) **(M)** — un seul lien retargeté, cf.
  les décisions de cadrage.

## Définition de terminé

- [x] `ai-workflow.md` s'intitule « Workflow IA — d'une demande au code livré » et raconte le chemin
      complet, de la demande en langage courant aux commits sur `develop`.
- [x] Les quatorze étapes sont groupées par **portée** — par demande, par lane, par carte, par PR.
- [x] Un schéma porte le process, un autre l'architecture ; **les deux rendent tels quels** dans le
      site `docs/showcase-recruiter-webapp-saas/`, vérifié sur la page servie.
- [x] La définition de l'espace, la table qui la porte et la règle de l'espace consommateur vivent
      dans `ai-workflow.md` — et **n'existent plus** dans `git-workflow.md`, qui renvoie.
- [x] Le champ `Session` et la propriété d'une carte vivent dans `ai-workflow.md` — et **n'existent
      plus** dans `parallel-work.md`, qui renvoie.
- [x] La section `README → règle → gate CI` a disparu au profit d'un renvoi vers
      `3.quality/testing-and-ci.md`.
- [x] La page dit *pourquoi* la discipline de session existe, sans recopier la règle de `CLAUDE.md`.
- [x] La page s'arrête au réalignement des branches ; la release renvoie ailleurs.
- [x] Aucune règle nouvelle n'a été introduite.
- [x] Le diff ne contient rien de l'autre session (`git diff --cached --name-only` relu).

## Vérification

**Le site de documentation a été servi en local**, et les deux pages produites répondent :

```
ai-workflow HTTP 200
blocs `language-mermaid` restants : 0
bloc 1 : 18 lignes, ligne la plus large 73 car.
bloc 2 : 25 lignes, ligne la plus large 80 car.
```

Les deux schémas ressortent **tels qu'écrits**, alignement intact, sous 80 caractères — donc sans
repli sur écran étroit. C'est cette sortie qui a fait abandonner Mermaid : au premier passage, le
même contrôle donnait un `<pre class="… language-mermaid shiki …">` et **zéro diagramme SVG**, sur la
nouvelle page comme sur `architecture.md`.

**Toutes les ancres internes résolvent.** Contrôle automatique sur l'ensemble de
`docs/showcase-recruiter-webapp-saas/content/` — 357 titres indexés, aucun lien ancré orphelin. Il couvre les renvois
créés par ce commit comme ceux qui existaient déjà.

**Aucune gate ne s'applique** : le diff est exclusivement du Markdown, et ne touche ni `webapp/` ni
`api/`. Vérifié le 2026-08-02 : `.github/workflows/` ne contient que `api-quality.yml`,
`webapp-quality.yml` et `commit-check.yml`.

**Le diff ne contient rien d'une autre session.** `OPS-026`, qui modifiait les deux mêmes fichiers
depuis une session concurrente, a été mergée sur `develop` avant cette implémentation ; la lane a été
rebasée dessus, et sa mention de `/etat-des-lanes` est reprise dans la nouvelle page.

## Livré

- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md` **(M)** — réécrite intégralement :
  le problème que le dépôt résout, la cascade de gouvernance, les espaces et leur table, deux
  schémas, les quatorze étapes par rythme de rejeu, la carte de tâche et son champ `Session`, le
  pourquoi de la discipline de session, la table de propriété.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md` **(M)** — perd la définition de l'espace,
  la table des huit et la règle de l'espace consommateur ; renvoie.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md` **(M)** — perd le champ `Session` ;
  renvoie. Son renvoi d'ouverture vise désormais `ai-workflow.md`.
- `docs/showcase-recruiter-webapp-saas/content/1.getting-started/index.md` **(M)** — la ligne de rôle d'`ai-workflow.md`.
- `.claude/commands/demarrer-tache.md` **(M)** — un lien retargeté, devenu faux.

## Reste à faire (hors périmètre)

- **`OPS-028`** — `parallel-work.md` garde encore le rappel du principe des espaces et sa discipline
  de commit. La carte est écrite, elle s'ouvre après ce commit.
- **`OPS-029`** — `CLAUDE.md` n'a pas été touché : il réexplique encore le cycle de delivery que
  cette page possède désormais.

## Aval

- **Le rendu Mermaid sur le site Docus** — décision nouvelle (dépendance + configuration), écartée du
  périmètre. Sans elle, `architecture.md` et `data-model.md` continuent d'afficher leurs deux
  diagrammes en bloc de code.
- **`ui-ux-design/CLAUDE.md`** pointe vers `git-workflow.md` pour sa lane et son préfixe : le renvoi
  gagnerait à viser `ai-workflow.md`. Le fichier appartient à `espace/design` — une carte `UX` chez
  eux, pas ici.
