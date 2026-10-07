# TASK OPS-023 — le corps de PR prend deux étages

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-07-31 — gabarit à deux étages dans `/creer-pr`, avec la règle de
  rédaction du résumé.
- **Type** : Outillage — convention de contribution.
- **Tests** : sans objet — la tâche ne modifie qu'un fichier de commande Markdown.
- **Réf** : task `OPS-005` (la forme du titre de PR) · `OPS-007` (le cycle outillé)

## Contexte

`OPS-005` a donné une forme au **titre** de PR. Le **corps**, lui, avait un gabarit plat : Contexte,
Tâches, Vérifications, Points de vigilance — quatre sections de même niveau, toutes dépliées.

En pratique il produisait des corps de quarante à soixante lignes où la justification, les preuves de
gate et les mises en garde arrivaient au même rang. Une PR qui s'ouvre ainsi n'est pas lue, elle est
approuvée : ce qu'un relecteur doit absolument savoir se noie dans ce qu'il pourrait vouloir vérifier.

La gouvernance du dépôt impose pourtant déjà de **vulgariser d'abord** — la règle existait pour les
réponses, pas pour les PR.

## Périmètre

- Le gabarit de corps de `/creer-pr` passe à deux étages : un résumé visible, le détail replié dans
  un `<details>`.
- La règle de rédaction du résumé est écrite à côté du gabarit.

## Hors périmètre

- Le titre de PR, déjà réglé par `OPS-005`.
- `git-workflow.md`, qui porte la forme du titre et n'a pas à porter celle du corps : la commande
  est le seul endroit où le corps se rédige.

## Décisions de cadrage

### Le résumé dit ce que ça CHANGE

C'est la règle qui fait la différence entre un résumé utile et un communiqué. « Les coordonnées sont
rares à la source, une promesse de profil joignable ne peut pas s'appuyer dessus » informe une
décision ; « déplacé deux documents et corrigé trois liens » informe sur l'auteur.

Le gabarit interdit donc explicitement, dans ces trois phrases, le chemin de fichier, le nom de
commande et le numéro de tâche — tous disponibles un pli plus bas.

### Une ligne « Ce qui compte pour la suite »

Le résumé décrit ; cette ligne **avertit**. Elle porte le fait qu'un relecteur doit retenir même
s'il ne déplie rien : un piège, une limite, une conséquence qui change une décision. Elle se retire
quand il n'y en a pas — une rubrique remplie par obligation ne signale plus rien.

### Le pli n'est pas une coquetterie

`<details>` est le seul moyen d'avoir les deux : la brièveté qui se lit et la preuve qui reste
disponible. Sans lui, le choix est entre une PR trop longue et une PR sans justification.

Un piège de rendu est consigné avec le gabarit : **une ligne blanche est obligatoire** après
`<summary>` et avant `</details>`, faute de quoi GitHub affiche le Markdown replié en texte brut.

## Règles et cas limites

- Le gabarit reste **un gabarit** : une PR sans point de vigilance retire la section, comme avant.
- La forme n'est **pas vérifiée par la CI** — comme le titre, c'est une convention de lecture tenue
  à la main.

## Documentation impactée

- `.claude/commands/creer-pr.md` — le gabarit et sa règle de rédaction.

## Vérification

- Plus aucune trace de l'ancien gabarit plat dans la commande.
- La forme a été éprouvée sur la PR d'`OPS-022` avant d'être écrite : c'est de cette relecture
  qu'est venue la demande.

## Livré

- `.claude/commands/creer-pr.md` (M) — gabarit à deux étages, règle du résumé, piège de rendu
  GitHub.

## Reste à faire (hors périmètre de cette tâche)

- **Les PR déjà ouvertes** gardent l'ancienne forme ; elles ne sont pas reprises.

## Définition de terminé

- [x] Le gabarit de `/creer-pr` a deux étages.
- [x] La règle « le résumé dit ce que ça change » est écrite à côté.
- [x] Le piège de la ligne blanche autour du `<details>` est consigné.
