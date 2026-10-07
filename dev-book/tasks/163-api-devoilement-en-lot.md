# TASK 163 — API : dévoiler un lot, tout ou rien et plafonné

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27.
- **Type** : API — surface de contrat et politique de crédits.
- **Réf** : `vue_la_table.md` §4 (« l'action la plus coûteuse de l'application »),
  `credits_et_devoilement.md`, task 155 (les trois accès), task 162 (la sélection côté front).

## Contexte

L'API ne savait dévoiler qu'un profil à la fois. La Table, elle, sait désormais désigner un lot — et
sans endpoint, ce lot serait devenu une **boucle dans le navigateur** : aucune atomicité, aucun
plafond opposable, et une coupure au 40ᵉ profil sur 60 laissant 40 crédits dépensés sans que l'écran
sache où il en est.

Le cadrage l'avait anticipé : « un plafond de lot **côté serveur** (refus au-delà) ».

## Décisions

### Tout ou rien

Le solde est vérifié pour l'ensemble **avant le moindre débit**. Un lot passe entier ou pas du tout :
un lot à moitié passé laisserait des crédits dépensés sans que l'appelant sache lesquels.

### Un seul accès par lot

Cocher vingt profils et voir partir soixante crédits serait exactement la surprise que ce produit
doit éviter. Le recruteur choisit ce qu'il achète — coordonnées, CV **ou** compte rendu.

### Plafond à 50, et il vit dans le Domaine

`CreditPolicy::MAX_REVEAL_BATCH`. Un garde-fou que le navigateur applique n'en est pas un : n'importe
quel appel direct le franchit. La contrainte de validation le recopie (la couche `ApiResource` ne
peut pas dépendre du `Domain`, deptrac veille) mais ce n'est qu'un 422 lisible — la vraie garde est
celle que **tout appelant traverse**.

Refus **sec**, jamais de troncature : dévoiler les 50 premiers d'une demande de 200 laisserait croire
que tout est passé.

### Ce qui est déjà payé ne se repaie pas, et ne compte pas dans le coût

Recocher un profil déjà dévoilé ne facture rien, et le lot le renvoie quand même — l'appelant
retrouve ses profils dans l'ordre envoyé, existants compris.

### Un identifiant introuvable fait échouer le LOT

Plutôt que de le rétrécir en silence : on ne débite pas 19 profils quand l'utilisateur en a coché 20.
Le refus nomme les fautifs.

### L'unitaire délègue au lot

`reveal()` n'est plus qu'un `revealMany()` d'un élément. Les invariants — verrou du portefeuille,
idempotence, solde, notification dans la transaction — vivent à **un seul endroit** : deux copies
auraient divergé au premier correctif.

## Vérification

`RevealTest` : 17 tests, 157 assertions — dont le débit par profil, le refus intégral à solde
insuffisant (rien de dévoilé), l'absence de re-facturation, le plafond à 51 identifiants, et le refus
nommé sur un identifiant inconnu. Suite complète, phpstan, php-cs-fixer, deptrac et `lint:container`
verts.

## Reste à faire (hors périmètre de cette tâche)

- **Front** : la modale de coût, et la double confirmation au-delà de 10 profils (seuil d'UI, tranché
  avec Johan — il ne descend pas dans l'API, qui n'a pas à savoir ce qui se confirme à l'écran).
- **Le solde insuffisant** renvoie 402 sans dire combien il manque : à enrichir si l'écran veut le
  proposer à l'achat.
