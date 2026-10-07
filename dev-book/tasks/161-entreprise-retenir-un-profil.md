# TASK 161 — entreprise : retenir un profil depuis la Visionneuse

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — aller-retour vérifié sur le dev.
- **Type** : Frontend espace entreprise.
- **Réf** : `vue_la_visionneuse.md` (les actions vivent dans la card), task 150 (la shortlist retirée
  du produit — le favori est la seule liste de rétention).

## Contexte

La Visionneuse affichait des profils sans permettre d'en retenir un. C'est pourtant son **unique
geste** depuis que la shortlist a disparu : on lit un profil à la fois, et on garde ceux qui
comptent.

## Décisions

### Le geste vit DANS la card

Pas dans une barre d'outils qui obligerait à viser deux endroits : la card porte ce qu'on décide
d'elle. La card, elle, reste **passive** — elle émet, la page exécute.

### Optimiste, avec retour arrière

La carte bascule **avant** la réponse du serveur : retenir doit être instantané, et attendre un
aller-retour ferait douter du clic. Si l'appel échoue, la valeur d'avant est remise — mieux vaut un
geste qui se rétracte visiblement qu'un état qui ment jusqu'au prochain rechargement.

C'est le premier geste qui écrit, et il est **sans crédit en jeu** : il valide ce chemin avant qu'on
l'emprunte pour les dévoilements, où une erreur coûterait de l'argent.

### Aucun rechargement du jeu

Un favori ne change ni le périmètre de la recherche ni son ordre. Rejouer les résultats derrière
chaque étoile ferait clignoter l'écran pour rien.

### Le filet large

Un profil **masqué** est un favori parfaitement valide : on retient avant de payer. C'est ce qui
distingue cette liste de tout ce qui vient après.

### Une recherche partagée ne se retouche pas

Le bouton n'apparaît pas sur la recherche d'un collègue — même règle que les critères : un seul
auteur, pas de co-édition.

## Vérification

Sur le dev, en session réelle : `POST` sur un profil du jeu → il revient `isFavorite: true` dans les
résultats ; `DELETE` → 204 et le drapeau retombe.

## Livré

- `stores/candidates.ts` : `setFavorite` — bascule d'une carte, sans toucher au reste du jeu.
- `base/server/api/searches/[id]/favorites.post.ts` et `favorites/[candidateId].delete.ts`.
- `useSearch().toggleFavorite()` : optimiste, avec remise en état sur échec.
- `SearchProfileCard` : le bouton « Retenir » / « Retenu », émis et non exécuté.

## Reste à faire (hors périmètre de cette tâche)

- **L'étoile de la Table** (4ᵉ colonne du cadrage) : même geste, autre écran — elle attend la
  sélection.
- **Le compteur « Vus »** du panneau de contrôle, qui demande de suivre la position dans la pile.
