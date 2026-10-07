# TASK OPS-036 — les accès de recette quittent les réglages du serveur

- **Créée le** : 2026-08-18.
- **Session** : `1c04995a-7633-4e0b-869c-95fbe9a4a23c`.
- **Statut** : ✅ fait le 2026-08-18 — le réglage n'existe plus nulle part : ni script, ni fichier
  d'exemple, ni documentation. Les trois accès sont écrits dans le script et partent en
  préproduction ; hors préproduction, ils sont refusés et le refus s'affiche.
- **Type** : refactor — un réglage à composer à la main disparaît au profit de valeurs écrites dans
  le script. Le mail affiche la même chose ; c'est la façon de l'alimenter qui change.
- **Tests** : `sans objet` — le dépôt n'a pas de suite pour les scripts shell, et cette tâche ne
  produit aucun code exécutable neuf : elle remplace une lecture de réglage par des valeurs
  littérales. La preuve est l'**exécution réelle** — le mail composé contient les trois accès, et
  n'en contient aucun hors préproduction.
- **Réf** : `OPS-035`, qui a introduit le réglage · `API-065`, qui rend le bloc côté application et
  **ne change pas** · fichiers : [scripts/notify-deploy.sh](../../scripts/notify-deploy.sh),
  [.env.example](../../.env.example),
  [docker-and-make.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md).

## Contexte

Les accès de la préproduction sont joints au mail de fin de déploiement. Ils arrivent aujourd'hui
par un réglage du serveur, `DEPLOY_NOTIFY_ACCESSES`, dont la valeur est une ficelle à composer :
une entrée par point-virgule, quatre champs par barre verticale, le tout entre apostrophes.

**Cette ficelle est le problème.** Elle se compose à la main, sur un serveur, dans un fichier que
personne ne relit — et une faute de frappe n'y produit aucune erreur, juste un bloc qui n'apparaît
pas. C'est exactement ce qui s'est passé : le mail est parti trois fois sans les accès avant que
quiconque comprenne pourquoi.

Le même fichier a d'ailleurs produit un défaut plus grave le 2026-08-18 sur une autre ligne :
l'adresse du serveur d'envoi écrite sans son protocole a fait envoyer une requête web à la place
d'un e-mail, et le script a annoncé « notification envoyée » sans que rien ne parte. Le point commun
des deux incidents n'est pas la ligne fautive : c'est qu'il faille en écrire une.

**Les valeurs, elles, ne bougent pas.** Trois adresses de préproduction, trois identifiants, et un
mot de passe unique — celui passé à la remise à zéro. Rien là-dedans ne justifie un mécanisme de
configuration.

## Périmètre

- **Retirer `DEPLOY_NOTIFY_ACCESSES`** : du script, du fichier d'exemple, de la documentation.
- **Écrire les trois accès dans le script**, adresses, identifiants et mots de passe compris.
- **Conserver le garde-fou d'environnement** — et le renforcer, il devient le seul.

## Hors périmètre

- **Le mécanisme d'affichage côté application** (`EmailAccess`, le bloc des gabarits) : il reçoit
  des accès et les rend, il ne sait pas d'où ils viennent. Rien à y changer.
- **Le reste du mail** : titre, liste des changements, bouton, identifiant de version.
- **L'adresse du serveur d'envoi**, l'autre réglage qui a fauté le même jour. Le défaut est réel —
  une adresse sans protocole fait annoncer un succès sans envoi — mais il appartient à sa propre
  tâche, non ouverte ici.
- **La production** : le garde-fou continue d'y refuser tout accès.

## Décisions de cadrage

### Des mots de passe dans le dépôt, décidé en connaissance de cause

**Le dépôt interdit les secrets en clair**, et cette tâche déroge à la règle. Ce que la règle
protège a été dit avant le choix : un mot de passe commité entre dans l'historique et n'en sort
plus, y compris chez tous ceux qui possèdent une copie du dépôt. L'humain a tranché pour la
simplicité, la dérogation est donc **assumée et écrite ici** plutôt que découverte plus tard.

Trois conditions la rendent tenable, et elles cessent de l'être si l'une tombe :

1. il s'agit d'une **préproduction jetable**, sans données réelles ;
2. ce mot de passe est **posé à chaque remise à zéro** et ne sert nulle part ailleurs ;
3. il ne doit **jamais** être réutilisé en production, ni pour un compte réel.

### Le garde-fou devient le seul rempart

Le double verrou d'`OPS-035` reposait sur deux conditions : accès renseignés **et** environnement
reconnu comme préproduction. La première disparaît avec le réglage — des valeurs écrites dans le
script sont toujours « renseignées ». **Il ne reste que la vérification du domaine**, et c'est elle
seule qui empêche des mots de passe de partir depuis une production.

Elle reste ce qu'elle était : une **liste d'autorisation**. Le bloc n'est joint que si l'adresse du
site commence par `preprod.` ; tout le reste est traité comme une production. Et son refus continue
de s'afficher pendant le déploiement, parce qu'un garde-fou muet qui se déclenche à tort passe pour
une panne.

### Le sous-domaine seul, l'adresse construite — écart au cadrage assumé

La carte prévoyait d'écrire les adresses entières, et signalait en conséquence qu'une préproduction
déplacée afficherait des liens morts. L'implémentation a tranché autrement, pour rien de plus cher :
seul le **sous-domaine** est écrit (`mailpit`, `admin`, `entreprise`), l'adresse se construit sur le
domaine déjà connu du script. Le risque nommé au cadrage disparaît, et le garde-fou garantit que ce
domaine est bien une préproduction au moment où le bloc part.

### Écrire plutôt que configurer, ici et seulement ici

Ce choix ne vaut pas comme principe : un réglage reste la bonne réponse dès qu'une valeur diffère
d'une machine à l'autre. Il vaut parce que ces valeurs-là sont **les mêmes partout où ce script
tourne** — une seule préproduction — et qu'aucune n'est un secret au sens propre.

## Règles et cas limites

- **Le mail affiche exactement la même chose qu'aujourd'hui.** Toute différence de rendu est un
  défaut : seule la provenance des valeurs change.
- **Les adresses se construisent sur le domaine du déploiement**, seul le sous-domaine est écrit :
  une préproduction déplacée suit sans retouche.
- **Le mot de passe écrit dans le script doit correspondre à celui posé par la remise à zéro.** S'ils
  divergent, le mail annonce des accès qui ne fonctionnent pas — pire qu'un mail sans accès.
- **Aucun autre réglage n'est touché** : les destinataires, le compte d'envoi et son mot de passe
  restent dans les réglages du serveur, où ils doivent être.

## Documentation impactée

- `.env.example` — le réglage et son explication disparaissent.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` — le paragraphe qui décrit le double
  verrou dit désormais qu'il n'en reste qu'un, et pourquoi.

## Définition de terminé

- [x] `DEPLOY_NOTIFY_ACCESSES` n'existe plus : ni script, ni fichier d'exemple, ni documentation.
- [x] Les trois accès sont écrits dans le script et apparaissent dans le mail composé.
- [x] Hors préproduction, aucun accès ne part et le refus s'affiche.
- [x] La dérogation à la règle des secrets est écrite dans la carte **et dans le script**, avec ses
      trois conditions — c'est dans le script qu'on la lira le jour où quelqu'un se demandera
      pourquoi un mot de passe est là.
- [x] Le mot de passe du serveur d'envoi et les destinataires restent, eux, dans les réglages.

## Vérification

Banc d'essai reconstruit, **avec le réglage retiré du fichier de configuration de test** — c'est ce
retrait qui démontre qu'il ne sert plus à rien.

| Situation | Résultat |
| --- | --- |
| Préproduction, aucun réglage d'accès | les **trois accès** dans le mail, adresses construites sur le domaine |
| Hors préproduction (`showcase-recruiter-webapp-saas.example`) | `⚠ accès NON joints`, bloc **absent**, mot de passe **introuvable** dans les deux versions |
| Occurrences restantes du réglage | **0** dans le script, **0** dans le fichier d'exemple, **0** dans la documentation |

Ce que le destinataire reçoit en préproduction :

```
Vos accès

Boîte aux lettres de test
https://mailpit.preprod.showcase-recruiter-webapp-saas.example
login : showcase-recruiter-webapp-saas
mot de passe : change-me-demo-password
```

**Le refus hors préproduction est la vérification qui compte le plus** : c'est désormais le seul
rempart, et il a été éprouvé en changeant le domaine du banc d'essai — pas en le supposant.

## Livré

- `scripts/notify-deploy.sh` (M) — les trois accès en constante, le réglage disparu, la dérogation
  et ses trois conditions écrites à l'endroit où on les lira.
- `.env.example` (M) — le réglage retiré, remplacé par une ligne qui dit où les accès vivent
  désormais et sous quelle condition ils partent.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` (M) — le double verrou devient simple,
  et la page dit pourquoi il n'en reste qu'un.

## Reste à faire (hors périmètre)

- **Retirer `DEPLOY_NOTIFY_ACCESSES` du `.env` du serveur** au prochain déploiement : la ligne y
  restera sans effet, mais elle induira en erreur celui qui la relira.
- **L'adresse du serveur d'envoi sans protocole**, qui fait annoncer un succès sans envoi. Défaut
  réel du même script, croisé le même jour, non traité ici.
