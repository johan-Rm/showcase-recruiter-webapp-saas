# TASK 164 — entreprise : rendre à l'accueil sa racine unique

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — l'avertissement a disparu des logs.
- **Type** : Frontend espace entreprise — correctif.
- **Réf** : task 159 (le lancement d'une recherche, qui a révélé le défaut).

## Contexte

Lancer une recherche menait à un **écran blanc**. La console le disait :

> `pages/index.vue` does not have a single root node and will cause errors when navigating between
> routes.

Le template de l'accueil commençait par un **commentaire**, au-dessus de son `<div>` racine. Vue
compte un commentaire de premier niveau comme un nœud : la page en avait donc deux, et une transition
de page (`pageTransition`, actif dans la config racine) en exige **un seul**.

Le défaut était là depuis l'écriture de l'écran. Il ne s'est vu qu'à partir du moment où on est
arrivé sur cette page — et surtout reparti d'elle — par `navigateTo`.

## Décision

Le commentaire passe **à l'intérieur** de la racine, et dit pourquoi il y est. La prochaine personne
tentée de le remonter au-dessus, « pour qu'il documente la racine », lira la raison avant de le faire.

## Vérification

Après correction : plus aucune occurrence de l'avertissement dans les logs du conteneur, et la
Visionneuse d'une recherche rend bien ses cards au SSR.

## Ce qui n'était PAS un bug — la recherche à un seul profil

Signalé en même temps : une recherche `développeur` + `frontend` + `BAC` ne ramenait qu'un profil.
Mesuré sur le vivier de dev :

| Critères | Profils |
| --- | --- |
| `job=développeur` | **256** |
| `+ educationLevel=BAC` | 224 |
| `+ q=frontend` | **1** |

Le champ **Mots-clés** est une recherche plein texte en ET strict, sans aucun élargissement :
`frontend` → 1, `front` → 9, `React` → 20. Le champ **Intitulé de poste**, lui, passe par le
dictionnaire de concepts — d'où les 256. Les deux libellés du formulaire le disent déjà, mais rien à
l'écran ne montre **quel critère** a réduit le résultat.

C'est le manque que le cadrage comble par les **compteurs facettés** (chaque option affiche ce
qu'elle donnerait, tous les autres filtres appliqués sauf le sien). Ils n'existent pas côté API.

## Reste à faire (hors périmètre de cette tâche)

- **Compteurs facettés** — l'API ne les expose pas ; c'est ce qui rendrait visible le coût de chaque
  critère avant de le poser.
- **État vide actionnable** : quand une recherche ne ramène rien ou presque, proposer de retirer le
  critère le plus restrictif plutôt que de laisser l'utilisateur deviner.
