---
description: Discuter d'un sujet avant qu'il devienne une tâche — reformuler le manque, peser, conclure à faire / à ne pas faire / pas maintenant, et nommer les tâches qui en découlent sans les ouvrir
argument-hint: [le sujet, en langage courant — ex. « relancer un candidat sans le harceler »]
allowed-tools: Bash(git:*), Read, Glob, Grep, AskUserQuestion
---

Évoquer : **$ARGUMENTS**

## Ce que cette commande fait, et ce qu'elle ne fait pas

Elle **discute**. Elle n'écrit aucun fichier, ne branche pas, ne code pas, ne commite pas, n'ouvre
aucune tâche. C'est la **deuxième** commande du dépôt qui ne modifie rien, avec `/etat-des-lanes` —
et ici comme là-bas, la propriété n'est pas une consigne de prudence : elle est portée par la liste
`allowed-tools` ci-dessus, qui ne contient ni `Write` ni `Edit`.

| Moment | Commande | Ce qu'elle possède |
| --- | --- | --- |
| **Évoquer un sujet** | **`/evoquer-un-sujet`** | Rien — la conversation, et elle seule |
| Se situer | `/etat-des-lanes` | Rien — lecture seule |
| Cadrer et spécifier | `/creer-tache` | Le questionnement, la carte `dev-book/tasks/` |
| Ouvrir et mener une tâche | `/demarrer-tache` | La branche, le code, la preuve, tests et doc |
| Clore une tâche | `/commit-tache` | La clôture de la carte et **un** commit |
| Livrer la lane | `/creer-pr` | Les gates qualité, le titre et le corps de PR |
| Après le merge | `/apres-pr-mergee` | La vérification du merge et le réalignement |

Elle occupe le **premier rythme** du chemin d'une demande — *Qualifier*, étapes 1 et 2 de
[ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#le-chemin-dune-demande) :
la demande arrive en langage courant, puis elle est routée. Jusqu'ici ce moment n'avait pas d'outil,
et la première marche disponible était `/creer-tache` — trop haute, puisque sa quatrième dimension
demande *quel espace de rattachement*, c'est-à-dire qu'elle suppose déjà acquis que le sujet vaut la
peine et qu'il tient dans une seule lane.

## Rien ne s'écrit, et c'est délibéré

**Aucune trace.** Pas de note de sujet, pas de dossier `dev-book/sujets/`, pas de ligne ajoutée à
`dev-book/todo.md`, pas de carte. Un sujet évoqué et non tranché **disparaît à la fermeture de la
session**, et c'est le prix accepté pour que la conversation reste libre : une trace écrite aurait
créé un second backlog à côté de `todo.md`, avec sa convention de nommage et son cycle de vie à
tenir, pour un contenu dont l'essentiel se consomme dans l'échange.

Ce qui doit survivre à la conversation survit sous une autre forme : une carte de tâche, écrite par
`/creer-tache` dans un second temps. Ce qui ne mérite pas une carte ne méritait pas d'être gardé.

## La frontière avec « une session ne propose pas la suite »

> ⛔ Cette commande se termine parfois en **nommant des tâches**, ce qui la place à un pas de la
> brèche du 2026-08-02 : présenter une file d'attente comme « la suite » (cf. `OPS-025` et le
> § *Ce qu'aucun agent ne fait* du [CLAUDE.md racine](../../CLAUDE.md)).

La frontière tient en une phrase, et elle est stricte : **ce qui est nommé découle du sujet apporté
par l'humain dans cette conversation, et de rien d'autre.** En particulier :

- **ne jamais parcourir `dev-book/tasks/`** pour voir ce qui traîne, ni mentionner une carte
  existante autrement que comme un amont dont ce sujet dépend ;
- ne pas élargir le sujet à ce qui « irait bien avec », ni proposer un second sujet à évoquer ;
- ne jamais classer les tâches nommées par priorité, ni désigner par où commencer.

Ce que la commande produit n'est pas un choix orienté : c'est la conséquence directe de ce qui vient
d'être décidé ensemble. La différence est celle entre *répondre* et *proposer*.

## Procédure

### 1. S'instruire au minimum, et le dire

**Ne jamais faire réfléchir sur une question déjà tranchée.** Avant le premier échange, vérifier si
le sujet a déjà une réponse quelque part — et lire **le strict nécessaire**, pas le voisinage :

- le **cadrage produit** de l'espace concerné (`dev-book/cadrage/`) ;
- les **cartes de tâches** qui portent visiblement le sujet, par leur nom ;
- la page de `docs/showcase-recruiter-webapp-saas/content/2.architecture/` qui gouverne le périmètre.

Annoncer en **une ligne** ce que cette lecture a donné, avant de discuter. Deux issues immédiates :

- **le sujet est déjà tranché** → le dire, citer où la décision vit, et s'arrêter. Rouvrir un
  arbitrage écrit demande de le vouloir explicitement, pas de l'ignorer ;
- **le sujet est en fait une tâche** — un besoin déjà clair, tenant dans une lane → renvoyer vers
  `/creer-tache` sans dérouler la conversation. Discuter d'un sujet mûr le retarde, il ne l'éclaire
  pas.

Une exploration large du code n'a pas sa place ici : la conversation peut s'arrêter au bout de deux
phrases, et une enquête menée d'avance aura été payée pour rien.

### 2. Reformuler le manque, pas la solution

Un sujet arrive presque toujours sous forme de solution — « il faudrait un bouton pour… ». Le
reformuler en **manque observable** : qui, quand, qu'est-ce qui ne se fait pas aujourd'hui. C'est
cette reformulation que l'humain valide ou corrige, et c'est elle qui rend la suite possible : deux
solutions différentes servent souvent le même manque, et on ne peut pas les comparer avant de
l'avoir nommé.

Reprendre l'exigence d'**adresse** du socle global : qui est devant l'écran, dans quelle partie du
produit, sur quelle page, à quel endroit. Un sujet sans adresse est un sujet dont on parlera à côté.

### 3. Explorer, en prose

L'échange est une conversation, pas un formulaire. Reformuler, objecter, proposer des pistes, dire
ce qu'elles coûtent, écarter à voix haute. Ce qui compte à cette étape :

- **nommer le coût** de chaque piste, pas seulement son bénéfice — combien de lanes elle touche, ce
  qu'elle rend irréversible, ce qu'elle oblige à maintenir ;
- **distinguer le réversible du structurel** : ce qui se défait en une heure ne se discute pas comme
  ce qui engage le modèle de données ou le contrat d'API ;
- **écarter explicitement**, avec le motif. Ce qui a été écarté et pourquoi est l'information la plus
  utile six mois plus tard — et ici, elle ne survit que si elle est dite clairement.

**Le questionnaire à choix (`AskUserQuestion`) est réservé aux vraies bifurcations** — deux chemins
qui mènent à des produits différents. Il ne sert pas à dérouler un cadrage : `/creer-tache` est faite
pour *fermer* les portes, cette commande pour les garder ouvertes tant que le sujet n'est pas mûr.

### 4. Conclure — quatre sorties, aucune n'est un pis-aller

| Sortie | Ce qu'elle dit |
| --- | --- |
| ✅ **À faire** | Le manque est réel, la piste tient, on peut ouvrir des tâches. |
| ⛔ **À ne pas faire** | Le manque est réel ou non, mais la réponse ne vaut pas son coût — et le motif est dit. |
| ⏸ **Pas maintenant** | Le sujet est légitime, son moment n'est pas venu — dire ce qui devra avoir changé. |
| ❔ **Sans verdict** | Rien n'est tranchable en l'état — dire **ce qui manque** pour trancher. |

**Aucun verdict ne se force.** La quatrième sortie est une réponse complète, pas un échec de la
conversation : conclure faute de mieux fabrique de la fausse certitude, et une décision inventée
coûte plus cher qu'une décision différée. « Pas maintenant » n'est pas non plus un refus poli — c'est
un oui daté.

### 5. La passerelle — nommer les tâches, ne pas les ouvrir

**Seulement si le verdict est « à faire ».** Découper le sujet selon les deux règles de routage de
[ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md#deux-règles-de-routage-et-deux-seulement) —
le transverse se rattache à l'espace **consommateur**, et une demande qui traverse plusieurs lanes se
découpe en autant de tâches. Pour chacune : l'espace, et une phrase disant ce qu'elle couvre.

C'est **le seul endroit du cycle où ce découpage se fait à froid**, avant qu'une carte existe.
Jusqu'ici il se faisait de tête au moment d'ouvrir la première, donc mal et sans trace.

Puis **s'arrêter**. Ne jamais appeler `/creer-tache`, même quand le verdict est net et la lane
évidente : la frontière entre *on en parle* et *on l'ouvre* est ce qui empêche une réflexion de
glisser en code sans décision. L'ouverture est un geste humain.

## Sortie

Le fil de la conversation, puis une clôture courte :

```
Verdict — ⏸ pas maintenant.

Le manque est réel : un recruteur qui a dévoilé un profil n'a aucune trace
de ce qu'il lui a écrit. Mais le suivi n'a de sens qu'une fois la shortlist
portée par la recherche, ce qui n'est pas encore le cas.

Écarté en chemin — la notification automatique (risque de harcèlement, et
personne ne saurait la couper) et l'historique complet des échanges (c'est
un autre produit).

Ce qui devra avoir changé pour rouvrir : la shortlist rattachée à une
recherche.
```

Et quand le verdict est « à faire », la passerelle remplace le dernier paragraphe :

```
Verdict — ✅ à faire. Trois tâches, une par lane :

  espace/api          stocker et exposer la date de premier contact
  espace/entreprise   l'afficher sur la fiche du candidat
  espace/admin        la rendre visible dans la revue

Rien n'est ouvert. `/creer-tache` prend la suite, une lane à la fois.
```

Rien après. Pas de synthèse supplémentaire, pas de priorisation, pas de « prochaine étape » autre que
celle-là.

## Garde-fous

- **Lecture seule, sans exception.** Aucun fichier créé ou modifié, aucun `switch`, `checkout`,
  `add`, `commit`, `stash`. Les commandes `git` servent à lire, pas à agir.
- **Aucune trace écrite**, y compris quand la conversation a été longue et que ce serait dommage de
  la perdre. C'est la décision de cadrage d'`OPS-034`, pas un oubli — la rouvrir demande une tâche.
- **Ne jamais parcourir `dev-book/tasks/`** pour alimenter la discussion. Une carte se cite si elle
  est un amont du sujet, jamais comme une suggestion.
- **Ne pas confondre « sans verdict » et « conversation ratée ».** Un sujet qui ressort avec la liste
  de ce qui manque pour trancher a produit exactement ce qu'on lui demandait.
- **Ne pas cadrer.** Périmètre, hors périmètre, critères de terminé, tests, documentation impactée :
  tout cela appartient à `/creer-tache`. Le faire ici, c'est écrire une carte sans carte.
