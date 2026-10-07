# TASK ENT-013 — entreprise : le dévoilement devient un geste

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Dépend de** : `API-017` — sans elle, le client ne peut pas connaître son organisation, et aucune
  URL de crédit ou de dévoilement n'est constructible.
- **Réf** : revue des écrans du 2026-07-28 · `ENT-006` (la carte de décision, qui montre le masquage
  sans offrir d'en sortir) · task 076 (dont ne reste vivante que l'imputation par recherche, écartée).

## Contexte

**Le dévoilement n'existe nulle part côté front.** Aucun bouton sur la carte, aucun relais BFF, aucun
solde affiché. Le parcours s'arrête exactement là où le produit commence à valoir quelque chose : on
peut chercher, trier, retenir — et rien de plus.

L'API, elle, sert tout depuis longtemps :

```
POST /v1/organizations/{id}/revealed-profiles        un profil, un accès
POST /v1/organizations/{id}/revealed-profiles/batch  un lot, 50 profils au plus
GET  /v1/organizations/{id}/credit-wallet            le solde
GET  /v1/organizations/{id}/credit-transactions      le journal
```

## Décisions

### Trois accès, trois boutons

`CONTACT` · `RESUME` · `INTERVIEW_REPORT` — les trois valeurs que l'API modélise. Chacun son bouton,
chacun sa dépense. Le recruteur paie ce qu'il veut, il n'achète pas un forfait dont il n'utilisera
qu'un tiers.

**Chacun coûte 1 crédit** (`CreditPolicy::REVEAL_COST`) : les trois accès ont le même prix, il n'y a
pas de tarif à afficher par type. L'écran annonce donc un coût, pas une grille.

### Le coût s'annonce avant, le solde est toujours là

Un bouton qui dépense sans dire combien est un piège. Le coût figure sur le bouton, et le solde vit
en permanence dans la barre haute — pas dans un menu qu'il faudrait ouvrir pour savoir si l'on peut
se permettre le clic suivant.

### Redévoiler ne coûte rien, et l'écran doit le dire

L'API ne facture pas un profil déjà dévoilé. Un bouton qui reste actif sans prévenir laisserait
croire à une seconde dépense ; il devient donc un état, pas une action.

### Solde insuffisant : on le dit avant, pas après

Le bouton se désactive et annonce ce qui manque plutôt que de laisser partir un appel qui reviendra
en erreur. Le lien vers les crédits est à côté — c'est le seul endroit où la rubrique « Crédits »
sert vraiment à quelque chose.

### Le lot vit sur « Ma sélection », pas sur le parcours

Parcourir, c'est décider un profil à la fois ; dévoiler un lot, c'est un geste de revue. Le plafond
de 50 est appliqué par l'API (`MAX_REVEAL_BATCH`) et **redit** par l'écran : au-delà, on explique au
lieu de laisser l'appel échouer.

Au-delà de dix profils, une confirmation qui **nomme le nombre et le coût**. Dix est le seuil où un
lot cesse d'être un geste et devient une décision budgétaire.

## Critères d'acceptation

- [x] Les trois accès se dévoilent à l'unité depuis la carte, et la carte affiche aussitôt ce qui a
      été débloqué.
- [x] Le coût est visible avant le clic, le solde en permanence.
- [x] Un profil déjà dévoilé n'offre plus le bouton — il montre l'accès obtenu.
- [x] Solde insuffisant : le bouton est désactivé et le dit.
- [x] Le lot ne part que depuis « Ma sélection », et au-delà de dix il demande confirmation en
      annonçant le nombre et le coût.
- [x] Le solde affiché suit la dépense sans rechargement de page.

## Mise en œuvre

**L'organisation ne voyage pas jusqu'au client.** Les relais la résolvent côté serveur depuis la
session (`organizationIdFor`), mémorisée par requête. Le navigateur n'a aucune raison de choisir
l'organisation qu'il débite, et un relais qui accepterait la sienne accepterait aussi celle d'un
autre.

**Une seule route pour l'unité et le lot.** L'API en expose deux, mais elles répondent à la même
intention : à un seul profil on prend la route unitaire, qui n'impose pas de plafond.

**Le dévoilement n'est PAS optimiste**, contrairement au favori. Il engage de l'argent : montrer des
coordonnées avant confirmation du débit, c'est montrer ce qu'on n'a peut-être pas acheté. On attend
la réponse, puis l'écran relit son jeu — le composable, lui, ne connaît aucune recherche.

**Le lot annonce ce qu'il coûte VRAIMENT** : les profils déjà dévoilés ne sont pas refacturés par
l'API, les compter ferait renoncer à un lot qui tenait dans le solde.

**Solde inconnu ≠ solde nul.** `affords()` rend `true` quand le solde n'a pas pu être lu : on ne
refuse pas un geste sur une ignorance, le serveur tranchera.

## Vérifié en réel

Le dévoilement joué de bout en bout à travers le BFF, sur la stack du worktree :

```
avant   solde 25 · contact False · prénom « Y. » · mail None
        POST /api/reveals {CONTACT} → 200
après   solde 24 · contact True  · « Yassine El Amrani » · yassine@example.com
```

`API-017` n'étant pas encore sur `develop`, la vérification a demandé de porter temporairement ses
deux fichiers dans la stack de cette lane, puis de les rendre — l'arbre `api/` est resté propre.
