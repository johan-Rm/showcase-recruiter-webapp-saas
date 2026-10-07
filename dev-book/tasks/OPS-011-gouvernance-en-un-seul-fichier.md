# TASK OPS-011 — La gouvernance du projet n'a plus qu'un fichier

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — `AGENTS.md` supprimé, son registre de cinq rôles fusionné
  dans `CLAUDE.md`, et les quatre références vivantes repointées.
- **Type** : Outillage — gouvernance IA et documentation du workflow.
- **Tests** : sans objet — aucun code exécutable. La vérification est la relecture du registre
  fusionné et l'absence de référence pendante vers `AGENTS.md`.
- **Réf** : `OPS-009` (le cycle de tâche outillé) ·
  [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) ·
  [architecture.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md#invariants)

## Contexte

Le projet portait **deux fichiers de gouvernance** pour la même information : `AGENTS.md`, décrit
comme la source canonique, et `CLAUDE.md`, décrit comme son « équivalent Claude Code, synchronisé
depuis ce fichier ». Ce doublon existait pour une raison précise — deux assistants travaillaient sur
le dépôt, chacun ne lisant que le fichier à son nom.

Codex a été désactivé. La raison d'être du doublon disparaît avec lui, et ce qu'il coûtait reste :

- **La synchronisation manuelle avait déjà décroché.** `AGENTS.md` porte **cinq** rôles depuis
  l'ajout du MOA ; `CLAUDE.md` en annonçait toujours quatre. Personne ne l'avait vu, parce que rien
  ne le signale — c'est exactement le mode de panne qu'un fichier jumeau produit.
- **Le renvoi coûtait une lecture de plus.** `CLAUDE.md` était un routeur de 1.3 Ko dont le seul rôle
  était d'envoyer vers un fichier de 10.3 Ko. L'agent chargeait le premier automatiquement, puis
  devait aller chercher le second.
- **La documentation décrivait un montage disparu** : un runtime `.codex/` isolé, une cascade
  `~/.agents/AGENTS.md`, un tableau à deux assistants.

## Périmètre

- Fusionner le contenu d'`AGENTS.md` dans `CLAUDE.md`, et supprimer `AGENTS.md`.
- Repointer les références vers le fichier survivant.
- Réécrire [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) : un seul
  assistant, une seule cascade, plus de runtime Codex.

## Hors périmètre

- **Le contenu des cinq rôles**, repris tel quel. Cette carte déplace et supprime, elle n'arbitre
  aucune responsabilité d'agent.
- **Les cartes de tâche closes** (`010`, `OPS-004`, `OPS-006`, `OPS-007`) qui citent `AGENTS.md`.
  Elles décrivent ce qui était vrai au moment où elles ont été écrites : c'est un journal, le
  réécrire le falsifierait. Seule la carte `081`, encore ouverte, est repointée.
- **`videos/`**, qui porte aussi des `AGENTS.md` mais n'est pas versionné (`.gitignore:48`).
- **Le socle global `~/.claude/CLAUDE.md`**, traité hors dépôt dans le même mouvement.

## Décisions de cadrage

### Le fichier qui survit est `CLAUDE.md`, pas `AGENTS.md`

C'est le fichier que Claude Code charge **automatiquement**. `AGENTS.md`, lui, n'était lu que parce
que `CLAUDE.md` disait d'aller le chercher — une lecture conditionnelle, à la merci de la phrase qui
la déclenche.

L'argument inverse a été pesé : `AGENTS.md` est le format inter-agents qui s'installe dans
l'écosystème, donc le plus portable si un second assistant revient un jour. Il n'a pas emporté la
décision, parce que le coût d'un retour en arrière est faible — renommer un fichier — alors que le
coût de la lecture conditionnelle se paie à chaque session.

### Le registre monte d'un cran, il ne se recopie pas

`CLAUDE.md` ne devient pas un fichier qui *contient un renvoi vers* le registre : il **est** le
registre. Le routeur de 1.3 Ko disparaît entièrement plutôt que de cohabiter avec le contenu qu'il
annonçait.

### La documentation dit ce qui n'existe pas

`ai-workflow.md` déclare explicitement que le projet **n'expose pas** de `AGENTS.md`. Un lecteur —
humain ou agent — qui cherche ce fichier par habitude trouve la réponse au lieu du silence.

## Règles et cas limites

- La hiérarchie normative du socle global perd son échelon `AGENTS.md` : il n'y a plus de fichier
  entre `CLAUDE.md` du projet et `docs/`.
- Une carte close qui cite `AGENTS.md` n'est **pas** une référence pendante à corriger : c'est un
  fait daté. La distinction se fait sur le statut de la carte, pas sur la présence du mot.

## Documentation impactée

- [ai-workflow.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/ai-workflow.md) — le tableau à deux
  assistants, la cascade `~/.agents`, le runtime `.codex/` et l'ordre de lecture.
- [index.md](../../docs/showcase-recruiter-webapp-saas/content/1.getting-started/index.md) — la ligne de sommaire qui
  annonçait deux assistants.
- [architecture.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md#invariants) — le
  registre qui référence les invariants change de nom.
- [081](./081-produit-redaction-livrables-clients.md) — carte ouverte, son lien vers le rôle MOA.

## Vérification

Aucune gate à jouer — la tâche ne produit pas de code. Les preuves sont trois relevés sur l'arbre
de travail, à la fusion terminée :

- `git grep "AGENTS\.md"` hors cartes closes → **une seule occurrence**, la phrase d'`ai-workflow.md`
  qui déclare l'absence du fichier. Aucune référence pendante.
- `git grep -iE "codex|~/\.agents"` sur les `*.md` suivis (hors jeux de données `api/data` et
  `dev-book/resources`) → **aucun résultat**.
- `CLAUDE.md` relu de bout en bout : cinq rôles présents (Architecte, Backend, Frontend, Product,
  MOA), en-tête sans mention de jumeau, socle global pointant `~/.claude/CLAUDE.md`.

Le décrochage qui motivait la carte est mesuré : `AGENTS.md` portait 5 rôles, `CLAUDE.md` en
annonçait 4 dans sa phrase d'introduction.

## Livré

- `AGENTS.md` **(D)** — 180 lignes, supprimé.
- `CLAUDE.md` **(M)** — passe du routeur de 1.3 Ko au registre complet ; `~/.agents` → `~/.claude`,
  mention du fichier jumeau retirée.
- `ai-workflow.md` **(M)** — le tableau à deux assistants devient une cascade à deux portées ; la
  ligne du runtime `.codex/` disparaît ; l'ordre de lecture ne nomme plus qu'un fichier ; une phrase
  déclare que le projet n'expose pas d'`AGENTS.md`.
- `index.md` **(M)** — ligne de sommaire alignée sur un seul assistant.
- `architecture.md` **(M)** — le registre qui référence les invariants change de nom.
- `081-produit-redaction-livrables-clients.md` **(M)** — carte ouverte, lien du rôle MOA repointé.

## Reste à faire (hors périmètre)

- **`videos/`** porte encore deux paires `CLAUDE.md` / `AGENTS.md` identiques à l'octet près. Le
  dossier est gitignoré (`.gitignore:48`) : ces fichiers ne sont pas dans le dépôt et ne peuvent pas
  être traités par un commit.

## Définition de terminé

- [x] `CLAUDE.md` porte les cinq rôles ; `AGENTS.md` n'existe plus.
- [x] Aucune référence pendante vers `AGENTS.md` hors cartes closes.
- [x] Plus aucune mention de Codex ni de `~/.agents` dans les fichiers suivis.
- [x] `ai-workflow.md` décrit un seul assistant et une seule cascade.
