# TASK ENT-011 — entreprise : un seul verdict par profil

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : `ENT-008` (le tri comme file de travail, dont ce critère restait non tenu) · `ENT-009`
  (les deux onglets, où l'incohérence se voyait).

## Contexte

`ENT-008` posait qu'un profil ne peut pas être retenu et écarté en même temps. **Rien ne
l'empêchait** — ni l'API, ni le client. Constaté sur données réelles pendant `ENT-009` : un profil
écarté figurait toujours dans la liste des retenus, et les deux onglets affichaient des comptes qui
ne se recouvraient pas — « Tous 1 » à côté de « Favoris 2 », sur un jeu de deux profils.

Le symptôme est un compteur ; la cause est un modèle qui laissait coexister deux verdicts opposés sur
le même profil.

## Décisions

### Le dernier geste posé l'emporte

**Écarter un profil retenu efface le favori.** Retenir et écarter répondent à la même question par
l'inverse ; les garder tous les deux revient à ne pas avoir décidé.

Les deux autres pistes ont été écartées. Interdire d'écarter un profil retenu — masquer le bouton —
protège mieux d'un geste involontaire, mais impose deux gestes pour se dédire, sur l'écran dont tout
le propos est d'aller vite. Laisser coexister les deux états supprime la contradiction en la
déclarant normale : les compteurs continueraient de diverger, et plus personne ne saurait dire ce que
« retenu » signifie.

### Remettre rend le profil, pas le favori

Sortir un profil de la corbeille le remet dans la pile. Il n'y retrouve pas son étoile : remettre
défait l'écart, pas la révocation qui l'accompagnait. Le profil revient à trier — ce qu'on voulait en
le remettant — et retenir à nouveau reste un clic.

### Deux appels, dans cet ordre

L'API n'a pas d'écriture qui porte les deux : l'écart part d'abord, la révocation du favori ensuite.
Si la seconde échoue, il reste un favori de trop — visible, corrigeable — plutôt qu'un profil disparu
de la pile sans qu'on sache pourquoi.

## Critères d'acceptation

- [x] Écarter un profil retenu le retire des favoris, côté écran et côté serveur.
- [x] Les deux compteurs de « Ma sélection » ne se contredisent plus.
- [x] Remettre un profil le rend à la pile sans lui rendre son favori.
