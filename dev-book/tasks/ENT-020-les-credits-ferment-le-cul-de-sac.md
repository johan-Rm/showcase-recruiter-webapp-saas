# TASK ENT-020 — entreprise : les crédits ferment le cul-de-sac

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : `ENT-013` (le dévoilement, qui renvoie ici quand le solde manque) · `ENT-003`.

## Contexte

Onze lignes : un titre, une phrase.

Et surtout, **un cul-de-sac créé par `ENT-013`** : le dévoilement désactive son bouton quand le solde
est insuffisant et renvoie vers « Crédits »… où il n'y a rien. Un recruteur à court de crédits n'a
aujourd'hui aucun moyen d'en obtenir, ni même de comprendre où est passé le sien.

L'API sert le solde et le journal :

```
GET /v1/organizations/{organizationId}/credit-wallet         balance
GET /v1/organizations/{organizationId}/credit-transactions   type, montant, solde après, motif, date
```

## Décisions

### Pas de boutique — on nous contacte

Aucune route d'achat, aucun prestataire de paiement dans le projet. L'écran dit donc **comment
obtenir des crédits** : un contact. C'est honnête pour un MVP, ça ferme le cul-de-sac, et ça n'invente
pas une caisse qui n'existe pas.

Le jour où l'achat en ligne arrivera, il prendra la place de ce bloc sans rien déplacer d'autre.

### Le journal se lit comme un relevé

Une ligne = un mouvement : ce qui s'est passé, combien, et **le solde après**. Ce dernier est la
colonne qui rend un relevé lisible — sans lui, on additionne de tête pour vérifier.

Le signe porte le sens : `+2` pour des crédits offerts, `−1` pour un dévoilement. Pas de couleur qui
code le type — le guide la réserve aux vues et à l'accent, et cinq types coloriés ne hiérarchisent
rien.

### Le solde vient du store, pas d'un second appel

`ENT-013` a posé un store de crédits que la barre haute lit déjà. Cet écran s'y branche : deux
lectures du même chiffre finiraient par diverger.

### Ce que l'écran ne fait pas

Le journal n'est **pas paginé** : l'API ne l'expose pas, et le provider rend tout. Sur un compte
ancien, la liste sera longue — à revoir quand le volume le demandera, pas avant.

## Critères d'acceptation

- [x] Le solde s'affiche, et suit une dépense faite ailleurs sans rechargement.
- [x] Le journal liste les mouvements, du plus récent au plus ancien, avec le solde après chacun.
- [x] Un compte sans mouvement le dit, plutôt que d'afficher un tableau vide.
- [x] L'écran dit comment obtenir des crédits.

## Vérifié en réel

```
journal servi   CONSUMPTION −1 → solde 24 · « Dévoilement profil Imane Tazi »
                PURCHASE   +25 → solde 25 · « Pack 25 crédits »
écran rendu     « 24 crédits » · Mouvements · Dévoilement · solde · écrivez-nous
```

Le mouvement de consommation est celui qu'`ENT-013` a produit en dévoilant un profil : le journal
raconte donc bien ce que l'autre écran a fait.
