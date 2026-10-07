# TASK 025 — Auth & rôles : tests & validation (tous les sous-domaines)

- **Créée le** : 2026-06-29.
- **Statut** : ⏳ **validation** — **dédiée uniquement aux tests & à la validation**. Aucun dev ici :
  le code est livré par les sous-tâches **025a** (renommage rôles + hiérarchie + voters + fixtures)
  et **025b** (mot de passe libre-service).
- **Périmètre** : valider **de bout en bout** que le **référentiel de rôles & permissions** et la
  **gestion du mot de passe** fonctionnent conformément au cadrage verrouillé le 2026-06-29, sur les
  **3 sous-domaines** (candidat, entreprise, admin).
- **Hors périmètre** : toute écriture de feature (→ 025a / 025b) ; les routes métier entreprise (→ 026
  et ses sous-tâches) ; le host-routing / BFF Nuxt (→ 022) ; la doc officielle `docs/` (→ 027).

## Dépendances (dev à livrer avant validation)

| Sous-tâche | Objet |
| --- | --- |
| **025a** | Renommage des rôles, `role_hierarchy`, voters, fixtures. |
| **025b** | Mot de passe libre-service : `PUT /me/password` + `password-reset/request|consume`. |

> **Oracle de validation** = la **matrice de permissions** et le **modèle de rôles** définis dans
> **025a** (et la mémoire projet « rôles & permissions »).

## Plan de tests (tests/Functional)

**Rôles & hiérarchie**
- Un `COMPANY_OWNER` passe les gardes `COMPANY_MEMBER` ; un `ADMIN_OWNER` passe celles d'`ADMIN_MEMBER`
  et de `ROLE_ADMIN`.
- `ADMIN_MEMBER` peut valider/rejeter une entreprise mais **ne peut pas** gérer un compte admin (403).
- `ADMIN_MEMBER` n'obtient **aucune** capacité d'action métier entreprise via la hiérarchie.
- `COMPANY_MEMBER` ne peut pas modifier la fiche entreprise / inviter (403).
- Multi-rôles : un `Person` candidat **et** membre entreprise accède aux deux espaces.

**Données dev**
- Les fixtures recréées à zéro portent directement les rôles canoniques (login OK, accès préservés).
- Grep : 0 occurrence des anciens noms hors migration/historique.

**Mot de passe**
- Changement avec bon `currentPassword` → 200 ; mauvais → 403/422.
- Reset par token valide → 200 puis login avec le nouveau mot de passe ; token expiré/réutilisé → refus.
- `password-reset/request` sur e-mail inconnu → **202** (anti-énumération).

## Critères de validation

- [ ] Tous les scénarios ci-dessus passent (suite fonctionnelle verte).
- [ ] Couverture de la matrice : chaque cellule `✅`/`👁`/`—` sensible a un test correspondant.
- [ ] BDD recréée depuis le mapping à jour + fixtures validées sur données réalistes.
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).
- [ ] Aucune régression d'accès sur les espaces candidat / entreprise / admin existants.

## Références

- Modèle & matrice : **025a** ; mémoire projet « rôles & permissions ».
- [security.yaml](../../api/config/packages/security.yaml) · [OrganizationVoter](../../api/src/Security/OrganizationVoter.php).
- Sous-tâches : **025a**, **025b**. Liées : **026** (routes entreprise) · **027** (doc) · 022 · 009 · 004.
