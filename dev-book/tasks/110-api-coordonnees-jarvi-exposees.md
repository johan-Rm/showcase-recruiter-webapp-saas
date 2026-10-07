# TASK 110 — API : exposer les coordonnées détenues dans la réplica Jarvi

- **Créée le** : 2026-07-21.
- **Statut** : 🟢 code livré.
- **Type** : API — exposition / masquage (aucun changement d'ingestion, aucune migration).
- **Lié à** : `020` (ingestion), `006` (masquage), `108` (WhatsApp), `109` (photo).
- **Réf** :
  - `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md` § Limites structurelles (point 1)
  - `dev-book/cadrage/entreprise/api/import_profils_jarvi.md` § Coordonnées multi-valuées
  - `dev-book/cadrage/entreprise/api/recherche_et_masquage.md`

## Contexte

L'ingestion écrit les coordonnées Jarvi dans `candidate_jarvi` (`contacts`, `rawPayload.profileUrl`)
— mais `CandidateMapper` les lisait dans `Person.contactEmail` / `Person.phone` /
`Person.linkedinUrl`, **que l'ingestion ne remplit jamais**. Résultat sur le vivier réel :

| Coordonnée | Détenue en base | Exposée |
|---|---:|---:|
| LinkedIn | 98,7 % (1676) | **0 %** |
| E-mail | 3,9 % (67) | ~0 % |
| Téléphone | 3,3 % (56) | ~0 % |

La livraison de `whatsappNumber` (`108`) a rendu l'écart visible et absurde : la fiche affichait
« téléphone : — » **au-dessus** du même numéro servi comme WhatsApp, puisque celui-ci se lit, lui,
dans la réplica.

Plus grave que l'affichage : `hasEmail` / `hasPhone` — les booléens de **joignabilité** exposés
même masqué, ceux qui doivent éviter de dépenser un crédit sur un profil injoignable — répondaient
« injoignable » pour des profils dont on détient les coordonnées.

## Objectif

Servir les coordonnées **détenues**, sans rien changer à l'ingestion ni au schéma.

## Livré

- [x] `JarviContacts` (Domain\Jarvi) : aplatissement des coordonnées multi-valuées de la réplica —
  priorité **MANUAL > RESUME > LINKEDIN**, normalisation E.164, `profileUrl` → LinkedIn.
- [x] `CandidateMapper` : `Person` **d'abord**, réplica **ensuite**, sur les 4 vues (carte, détail,
  admin liste, admin fiche) — `hasEmail` / `hasPhone` compris.
- [x] Tests : `JarviContactsTest` (règle de choix, normalisation), `AdminCandidateTest` (profil
  sourcé → coordonnées servies ; profil inscrit → ses coordonnées priment).

## Invariants

- **Aucune écriture** : la réplica reste la vérité Jarvi intacte, `Person` n'est pas alimentée par
  l'import. L'aplatissement vit à la LECTURE, où il est réversible.
- Le candidat inscrit reste maître : ce qu'il a déclaré dans `Person` prime toujours.
- Le masquage ne bouge pas : les valeurs sortent au dévoilement, la présence sort avant.

## Points de vigilance

- **Un numéro national reste national.** `0603584617` sans indicatif n'est pas complétable : sans
  pays, préfixer serait une invention. On affiche ce que Jarvi détient.
- **Effet de bord attendu et voulu** : des milliers de profils deviennent « joignables » du jour au
  lendemain côté recruteur (1676 LinkedIn). C'est la donnée qu'on détenait déjà — mais l'affichage
  du vivier change d'un coup.
- **À source égale, la première rencontrée** l'emporte : la réplica ne porte pas de date de saisie,
  la règle « puis la plus récente » du cadrage n'est donc pas applicable en l'état.

## Docs à tenir à jour

- `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md` (§ Limites, point 1)
- `dev-book/cadrage/entreprise/api/recherche_et_masquage.md`
