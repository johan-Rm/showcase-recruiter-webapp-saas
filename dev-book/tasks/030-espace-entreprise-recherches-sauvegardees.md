# TASK 030 — DEV : recherches sauvegardées (critères opaques JSON)

- **Créée le** : 2026-06-30.
- **Statut** : ⏳ **à faire** — **dev** (structure `SavedSearch` déjà esquissée en V1 par le
  data-model ; alertes = V2). *Après 025a* (rôles).
- **Périmètre** : permettre à un recruteur d'**enregistrer, lister, renommer et supprimer** ses jeux
  de **critères de recherche**. L'API persiste un **JSON nommé** (critères métier opaques côté API),
  **rejoué par le front** qui gère les filtres « classiques ». Sauvegardes **privées au recruteur**.
- **Hors périmètre** : **moteur d'alertes** (notification quand un nouveau profil correspond) →
  **V2** ; `alertEnabled` / `channels` non exposés en V1 ; validation sémantique fine des critères
  (le front fournit un payload déjà structuré) ; recherche IA langage naturel (V2, 014) ; front ;
  doc officielle (→ **027**).

## Décision de cadrage

- L'API stocke des **critères opaques (JSON)** : mots-clés + `codeValue` (slugs) secteurs / fonctions /
  compétences, langues, dispo, dates — **pas d'ids/FK** (cf. `SavedSearch.criteria`, data-model).
  L'API ne ré-exécute pas la recherche ; elle conserve la **config** que le front rejoue.
- **Propriété** : `owner` = `Recruiter` courant. Sauvegardes **privées** (non partagées en V1).

## Routes à livrer

| Méthode | Route | Rôle | Payload |
| --- | --- | --- | --- |
| GET | `/v1/organizations/{id}/saved-searches` | membre validé (propriétaire) | liste **de ses** recherches |
| POST | `/v1/organizations/{id}/saved-searches` | membre validé | `{name, criteria}` → crée |
| PATCH | `/v1/organizations/{id}/saved-searches/{savedSearchId}` | propriétaire | `{name?, criteria?}` → renomme / met à jour |
| DELETE | `/v1/organizations/{id}/saved-searches/{savedSearchId}` | propriétaire | supprime |

## Règles métier (cf. data-model.md §`SavedSearch`)

- **Garde** : membre d'une `Organization` `VALIDEE` (`OrganizationVoter::ACCESS`) ; chaque recruteur
  ne voit / modifie / supprime **que ses propres** recherches (sinon **403/404**).
- **Champs V1** : `name` (requis), `criteria` (json, requis). `alertEnabled` (défaut `false`) et
  `channels` **non exposés** (réservés V2).
- **Isolation** : `owner` + `organization` fixés depuis le contexte d'auth, **jamais** depuis le
  payload ; cross-tenant / cross-owner → 403/404.

## Structure (cf. api-structure.md)

- Entité **`SavedSearch` déjà existante** — exposer les routes (Input/Output/Processor/Provider),
  migration uniquement si un champ manque.
- `ApiResource/Input/SavedSearchInput` (`name`, `criteria`) ; `Output/SavedSearchOutput`
  (enveloppe plate : `id`, `name`, `criteria`, `createdAt`).
- `State/Processor/SavedSearchProcessor` (POST/PATCH/DELETE) ;
  `State/Provider/SavedSearchesProvider` (filtré sur `owner` courant).
- Garde de propriété : voter dédié `SavedSearchVoter` **ou** filtrage `owner` dans le provider +
  contrôle dans le processor.

## Tests (tests/Functional)

- Création → 201 ; liste ne renvoie **que** les recherches du propriétaire.
- PATCH (renommage + maj critères) par le propriétaire → OK ; par un autre membre → 403/404.
- DELETE par le propriétaire → retiré ; par un autre → 403/404.
- Isolation : membre d'une autre entreprise → 403.

## Définition de terminé

- [ ] 4 routes gardées `COMPANY_*` (membre validé) + **propriété** (owner courant uniquement).
- [ ] `criteria` persisté/restitué tel quel (JSON opaque) ; `owner`/`organization` non falsifiables.
- [ ] Alertes (`alertEnabled`/`channels`) **non exposées** en V1.
- [ ] `make api-quality` vert (phpstan, cs, deptrac, phpunit).

## Références

- [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) §`SavedSearch`
  (structure V1, alertes V2). Entité existante : [SavedSearch](../../api/src/Entity/SavedSearch.php).
- Patterns proches : **008** (favoris) · **005** (Input/Output/Processor/Provider). Rôles : **025a**.
  Recherche : **014** (filtres V1). Aval : **027** (doc).
