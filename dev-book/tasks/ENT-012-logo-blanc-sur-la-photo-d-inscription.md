# TASK ENT-012 — entreprise : le logo redevient lisible sur la photo d'inscription

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : `WEB-001` (le logo passe en couleur de premier plan).

## Contexte

`WEB-001` a fait passer le logo en couleur de premier plan, ce qui est juste partout — sauf sur la
coque d'inscription de l'espace entreprise. Là, le panneau de marque est posé sur une **photo**
(`fond-inscription-2.webp`), et un logo en couleur d'accent y perd son contraste.

La signature juste dessous était déjà en blanc : le logo s'en était écarté sans que rien ne le
justifie.

## Décision

**`text-white`, et pas le jeton `text-on-accent`.**

Le jeton semble plus juste — c'est bien la couleur qu'on pose sur un fond d'accent. Mais il vaut
`var(--bg)` : il **bascule avec le thème**. Sur un fond sombre, le logo virerait au sombre lui aussi,
alors que la photo, elle, ne bascule pas.

Une couleur littérale se justifie ici précisément parce que la surface n'appartient pas au thème.
C'est déjà le choix de la signature voisine (`text-white/60`), et les deux se lisent maintenant
comme un seul bloc.

## Critères d'acceptation

- [x] Le logo est lisible sur la photo, en thème clair comme en thème sombre.
- [x] Il s'accorde à la signature qui le suit.
