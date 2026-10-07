# TASK API-067 — les types du front rattrapent le contrat

- **Créée le** : 2026-08-18.
- **Session** : `1c04995a-7633-4e0b-869c-95fbe9a4a23c`.
- **Statut** : ✅ fait le 2026-08-18 — les types du front sont régénérés depuis le contrat :
  **79 lignes ajoutées, aucune retirée**, exactement celles du point d'entrée introduit par
  `API-062`. Le contrôle de typage reste vert sur les quatre espaces.
- **Type** : fix — la vérification anti-dérive du contrat est rouge sur la lane, et elle bloque une
  Pull Request qui porte trois travaux.
- **Tests** : `sans objet` — aucun code n'est écrit. Le fichier livré est **entièrement généré** par
  l'outil du dépôt à partir du contrat ; le tester reviendrait à tester le générateur. Ce qu'il faut
  démontrer est que rien ne casse en aval, et c'est le contrôle de typage du webapp qui le dit — il
  a été joué sur les quatre espaces.
- **Réf** : `API-062` (`a3414f5c`), qui a modifié la surface de l'API · gate
  `quality-contracts-check` · [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md)
  · fichier : [webapp/shared/types/api.ts](../../webapp/shared/types/api.ts).

## Contexte

Le front ne réécrit pas à la main ce que l'API promet : ses types sont **générés** depuis
`api/openapi.json`. Une vérification les régénère à chaque passage d'intégration continue et échoue
si le résultat diffère de ce qui est commité — c'est le garde-fou qui empêche le front de compiler
contre un contrat périmé.

`API-062` a ajouté un point d'entrée — les explications d'une recherche, servies page par page — et
a bien régénéré le contrat : **80 lignes de plus** dans `api/openapi.json`. Mais les types qui en
dérivent n'ont pas suivi. La régénération produit **79 lignes** absentes de la branche, et la
vérification sort rouge.

**Le rouge ne dit rien de la qualité du travail livré** : le point d'entrée fonctionne, ses tests
passent. Il dit qu'un artefact généré a été oublié — exactement ce que ce garde-fou existe pour
attraper, et exactement pourquoi il est bloquant.

## Périmètre

- **Régénérer** `webapp/shared/types/api.ts` depuis le contrat, avec l'outil du dépôt.

## Hors périmètre

- **Le code front qui consommera ce point d'entrée** : c'est un travail d'espace entreprise, sur sa
  lane. Ici on remet en phase un artefact généré, on n'écrit aucun écran.
- **Le contrat lui-même** : il est juste, `API-062` l'a régénéré correctement.
- **Les contraintes d'organisation** (`organization-constraints.ts`), également générées : la
  régénération ne les change pas, le point d'entrée ajouté ne les concerne pas.
- **La cause en amont** — une surface d'API modifiée sans que les types suivent dans le même commit.
  C'est une habitude de travail, pas du code ; aucune tâche n'est ouverte ici pour elle.

## Décisions de cadrage

### Une carte plutôt qu'un rattrapage silencieux

Le fichier appartient au commit d'une **autre session**, déjà poussé : il ne peut plus être corrigé
là où il aurait dû l'être. Le glisser dans un commit voisin mêlerait deux travaux ; le corriger sans
carte produirait un commit sans tâche. La lane a déjà tranché ainsi pour le style une heure plus tôt
(`API-066`), et c'est la même forme d'obstacle.

### L'outil génère, la main ne touche pas

Le fichier est intégralement produit par le générateur. Une retouche manuelle serait effacée à la
prochaine régénération, et la vérification la signalerait aussitôt.

## Règles et cas limites

- **Aucun comportement ne change.** Le front ne consomme pas encore ce point d'entrée ; les types
  ajoutés ne sont utilisés nulle part. Toute erreur de typage apparue après cette tâche serait un
  défaut.
- **Le diff doit être exactement l'ajout des types du nouveau point d'entrée.** Une suppression ou
  une modification ailleurs signifierait que le contrat a bougé autrement — à relire avant de
  commiter.
- **Cette tâche ne rend pas la lane verte à elle seule** : elle lève la vérification de contrat. Les
  autres étapes du webapp — style, mise en forme, typage, compilation — doivent l'être aussi.

## Documentation impactée

Aucune. Le mécanisme de génération et sa gate sont déjà décrits dans `data-contracts.md` ; cette
tâche applique la règle, elle ne la change pas.

## Définition de terminé

- [x] `webapp/shared/types/api.ts` est régénéré depuis le contrat et commité.
- [x] La vérification anti-dérive passe : régénérer ne produit plus aucune différence.
- [x] Le contrôle de typage du webapp reste vert sur les quatre espaces.
- [x] Le diff n'ajoute que les types du point d'entrée ajouté par `API-062`.

## Vérification

Sorties lues, sur l'hôte — la seule exécution qui reproduise l'intégration continue.

| Ce qui a été lancé | Résultat |
| --- | --- |
| Régénération des types depuis le contrat | **79 ajouts, 0 suppression** — uniquement le nouveau point d'entrée |
| Contraintes d'organisation, également générées | **inchangées**, comme prévu |
| Contrôle de typage, les quatre espaces | **sortie 0, aucune erreur** |
| Vérification anti-dérive, après commit | **verte** — régénérer ne produit plus aucune différence |

**L'échec avait d'abord été reproduit** : avant régénération, la vérification sortait rouge en
local exactement comme en intégration continue, sur les mêmes lignes. C'est ce qui distingue une
correction d'une coïncidence.

## Livré

- `webapp/shared/types/api.ts` (M) — régénéré par l'outil du dépôt depuis `api/openapi.json`. Aucune
  ligne écrite à la main, aucune suppression.

## Reste à faire (hors périmètre)

- **Le code front qui consommera ce point d'entrée** — travail d'espace entreprise, sur sa lane.
- **La cause en amont** : une surface d'API modifiée sans que les types générés suivent dans le même
  commit. Habitude de travail, pas code.
