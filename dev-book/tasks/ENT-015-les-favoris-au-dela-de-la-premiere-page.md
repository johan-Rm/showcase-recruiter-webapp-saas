# TASK ENT-015 — entreprise : les favoris au-delà de la première page

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : `ENT-009` (la limite qui y est écrite) · `API-014` (le périmètre qui la lève).

## Contexte

L'onglet « Favoris » **compte juste et montre faux**. Le compteur porte sur la liste entière des
retenus, servie à part ; les lignes, elles, viennent du jeu de résultats, qui arrive par pages de
trente. Un favori posé au-delà de la première page est donc compté sans être affiché.

`API-014` a livré ce qui manquait : `GET /v1/searches/{id}/results?scope=favorites` sert le jeu réduit
aux retenus, avec son `total` et ses `resultIds` portant sur ce sous-ensemble.

## Décisions

### Une seule source pour l'onglet

Les lignes ET le compteur viennent du même appel. Deux sources pour la même question, c'est
exactement ce qui a produit l'écart — et ce qui le reproduira au premier changement.

### Le chargement suit l'onglet

Changer d'onglet demande le périmètre correspondant, plutôt que de filtrer côté client ce qu'on a
déjà. « Tous » n'a jamais porté les favoris hors page, aucun filtrage local ne les fera apparaître.

### Ce qui reste, et pourquoi

`loadFavorites` **ne disparaît pas** : la liste d'identifiants qu'il alimente peint l'étoile des
cartes de la pile, y compris pour un favori hors de la page chargée. Elle ne sait simplement plus
compter — c'est `?scope=favorites` qui porte désormais le compteur ET les lignes.

Et c'est bien la même erreur qui aurait resurgi : `GET /favorites` ne déduit pas les écartés, le
périmètre si. Deux sources auraient recommencé à diverger, cette fois d'un profil écarté.

## Critères d'acceptation

- [x] L'onglet « Favoris » affiche autant de lignes que son compteur en annonce, au-delà de trente.
- [x] Le compteur et les lignes viennent du même appel.
- [x] Retenir ou libérer un profil met les deux à jour sans rechargement.
- [x] Écarter un profil le sort aussi de la récolte, sans rechargement (`ENT-011`).
- [x] L'étoile reste juste sur les cartes du parcours.

## Vérifié en réel

```
périmètre servi   → total 1 · ids ['…0000c1']
onglets rendus    → « Tous 1 » · « Favoris 1 »
```
