# TASK 156 — entreprise : suivre le contrat des trois dévoilements

> Numérotée `153` au moment du commit, renumérotée en `156` : une autre lane avait pris
> le même numéro en parallèle. Les messages de commit mergés citent donc encore « task 156 ».

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — gates vertes.
- **Type** : Frontend espace entreprise — mise en phase avec le contrat.
- **Réf** : task 155 (le dévoilement en trois accès), task 154 (le retrait de la shortlist).

## Contexte

L'API ne sert plus `revealed` ni `isShortlisted` : le dévoilement s'est découpé en trois accès
indépendants (coordonnées, CV, compte rendu) et la shortlist a disparu du produit.

Le front lisait encore `candidate.revealed`. Merger l'API sans cette mise en phase n'aurait rien
cassé bruyamment — c'est le problème : les vues « Dévoilés » et « Non dévoilés » de la Table auraient
compté **tout le monde comme non dévoilé**, sur un champ devenu `undefined`. Un faux silencieux.

## Décisions

### L'entonnoir se lit sur les COORDONNÉES

Des trois accès, c'est `revealedContact` qui décide des vues d'entonnoir : le cadrage les définit par
« à dévoiler pour accéder aux coordonnées » d'un côté, « e-mail et téléphone accessibles » de l'autre.
Le CV et le compte rendu sont deux autres portes ; elles n'ont pas à changer le décompte de celle-là.

### Le type hand-authored suit le contrat

`candidate-card.ts` porte les trois drapeaux et perd `isShortlisted`. Il est écrit à la main (l'OpenAPI
type les `items` d'une collection en opaque) : rien ne l'aurait signalé, il fallait le corriger avec
le contrat.

## Livré

- `shared/types/candidate-card.ts` : `revealedContact` / `revealedResume` / `revealedReport`,
  `isShortlisted` retiré.
- `services/search/views.ts` : les vues d'entonnoir portent sur les coordonnées.
- Les tests du service suivent.

## Reste à faire (hors périmètre de cette tâche)

- **Les trois gestes de dévoilement** dans la Table, avec leur modale de coût par accès — c'est là
  que les deux autres drapeaux serviront.
- **La sélection** de la Table, préalable à l'export sur sélection (task 154).
