# TASK 108 — API : joignabilité WhatsApp, ingérée et dévoilée

- **Créée le** : 2026-07-21.
- **Statut** : 🟢 code livré — reste la ré-ingestion `--full` (run Jarvi).
- **Type** : API — ingestion Jarvi / réplica `candidate_jarvi` + exposition masquée.
- **Lié à** : `020` (ingestion), `006` (masquage), `109` (photo, même contrat de lecture).
- **Réf** :
  - `dev-book/cadrage/entreprise/api/recherche_et_masquage.md`
  - `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`
  - `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md`

## Contexte

Jarvi n'expose **pas** de numéro WhatsApp distinct : il marque un `phoneNumber` d'un
`whatsappIdentifier`, un JID de la forme `212600000000@s.whatsapp.net` (parfois suffixé du
terminal, `…:12@…`). Le champ n'était pas demandé par la requête GraphQL.

La donnée est **rare** — 1,1 % des profils (18 / 1701) — parce que le téléphone lui-même n'est
présent que sur 3,3 %. Elle reste utile là où elle existe : au Maroc, WhatsApp est le canal de
premier contact.

## Objectif

Ingérer l'identifiant brut et servir, au seul profil dévoilé, un **numéro joignable** en E.164 —
directement utilisable en `https://wa.me/<numéro>`.

## Livré (code)

- [x] Requête GraphQL `JarviClient` étendue : `phoneNumbers { … whatsappIdentifier }` (3 variantes) ;
  l'identifiant brut est conservé dans `candidate_jarvi.contacts` (réplica opaque).
- [x] `WhatsAppIdentifier` (Domain\Jarvi) : dérivation JID → E.164, bornes 8–15 chiffres, tolérante
  au suffixe de terminal et au domaine historique `@c.us`.
- [x] `CandidateOutput::$whatsappNumber`, `null` tant que `revealed` est faux.
- [x] Tests : `WhatsAppIdentifierTest` (unitaire, table de JID), masquage / dévoilement
  (`CandidateTest`).
- [x] Exposition admin (non masquée) : `AdminCandidateDetailOutput` + lien `wa.me` sur la fiche
  candidat admin.

## Reste à faire

- [ ] **Ré-ingestion `--full`** pour peupler l'existant (run Jarvi, clé + rate limit 500/j).
- [ ] Lien WhatsApp côté entreprise, sur la fiche dévoilée — quand la surface existera.

## Invariants

- La réplica garde le **JID brut** ; la dérivation en numéro vit à l'exposition. Jarvi maître, rien
  de normalisé à l'ingestion.
- Masquage serveur : le JID **contient le numéro en clair**, il ne sort donc jamais avant
  dévoilement — ni brut, ni dérivé.

## Points de vigilance

- **Ce n'est pas une coordonnée de plus** : le JID reprend le téléphone déjà présent. Ce que la
  donnée ajoute, c'est la **joignabilité sur WhatsApp**, pas un numéro inconnu.
- **Incohérence apparente à assumer** : `whatsappNumber` est lu dans la réplica Jarvi tandis que
  `phone` est lu dans `person.phone`, que l'ingestion ne remplit pas (limite connue,
  cf. `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md` § Limites). Un profil peut donc afficher un WhatsApp sans
  téléphone. Le vrai correctif est le câblage des coordonnées Jarvi vers l'exposition — hors
  périmètre ici.
- **Volume** : 18 profils concernés aujourd'hui. Ne pas construire de fonctionnalité produit
  au-dessus de ce champ sans revérifier le taux après ré-ingestion.

## Docs à tenir à jour

- `dev-book/cadrage/entreprise/api/recherche_et_masquage.md`
- `dev-book/cadrage/candidat/api/profil-jarvi-reference.md`
- `dev-book/cadrage/entreprise/rapport-taux-remplissage-jarvi.md`
