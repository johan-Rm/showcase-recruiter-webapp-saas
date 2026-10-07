# Architecture

Architecture du projet showcase-recruiter-webapp-saas : couches, modèle de données, frontière Jarvi, auth et infra.

- Related : [../index.md](../index.md)

## Contenu de la section

| Document                                  | Rôle                                                          |
| ----------------------------------------- | ------------------------------------------------------------ |
| [architecture.md](./architecture.md) | Synthèse : vision, couches & flux, frontière Jarvi, modèle (résumé), auth & sous-domaines, monorepo |
| [jarvi-integration.md](./jarvi-integration.md) | Intégration Jarvi : endpoints, auth, ingestion descendante, création initiale si absent, stratégie de recherche |
| [profile-processing.md](./profile-processing.md) | Traitements sur les profils : les 4 chaînes (ingestion, vocabulaire, secteurs, écoles), les 14 commandes, l'ordre d'exécution, les limites mesurées |
| [data-model.md](./data-model.md) | Modèle de données complet, aligné schema.org (IDs hybrides, soft delete, Mermaid ERD) |
| [data-integrity.md](./data-integrity.md) | Intégrité BDD : défense en profondeur (FK/UNIQUE/CHECK), invariants, atomicité |
| [data-contracts.md](./data-contracts.md) | Source de vérité des contrats (DTO/types/enums) : OpenAPI → types TS générés |
| [api-conventions.md](./api-conventions.md) | Conventions API : réponses plates, relations uuid, i18n, auth, routing, DTO |
| [api-structure.md](./api-structure.md) | Structure interne `api/src/` : couches, State Provider/Processor, Domain, Deptrac |
| [dimensions-du-vivier.md](./dimensions-du-vivier.md) | Composition du vivier dimension par dimension : valeurs distinctes, couverture, dispersion, et la méthode de mesure |
| [inventaire-criteres-3-couches.md](./inventaire-criteres-3-couches.md) | Critères de recherche disponibles, suivis sur un profil réel à travers les 3 couches (Jarvi → base → API) ; couvertures mesurées et critères retenus |
| [filtres-vivier-tableau-partage.md](./filtres-vivier-tableau-partage.md) | Taux de remplissage du vivier champ par champ et état de préparation de chaque filtre, en langage clair |
| [arborescence-entreprise.md](./arborescence-entreprise.md) | Carte de navigation de l'espace entreprise : routes, portées d'accès, états de la Table |
| [arborescence-admin.md](./arborescence-admin.md) | Carte de navigation de l'espace administration : routes cross-tenant et rôles `ROLE_ADMIN_*` |
| [responsibility-boundaries.md](./responsibility-boundaries.md) | Frontières de responsabilité du `webapp/` : qui fait quoi entre pages, composables, stores, services, BFF et shared |
| [composables-standard.md](./composables-standard.md) | Standard des composables : passerelle réactive UI ↔ données, triptyque et frontières réseau |
| [stores-standard.md](./stores-standard.md) | Standard des stores Pinia du `webapp/` : état brut, getter enrichi, setters explicites, frontière composable/store/services |
| [services-standard.md](./services-standard.md) | Standard des services : métier pur framework-agnostic, organisation par domaine, transformation à la frontière |
| [api-routes.md](./api-routes.md) | Carte des routes/endpoints, par domaine et rôle |
| [i18n.md](./i18n.md) | i18n (FR/AR/EN, AR=RTL) : UI Nuxt + données traduisibles (pattern Symfony), dark mode |
| [docker-and-make.md](./docker-and-make.md) | Socle Docker (FrankenPHP, Caddy, 3 envs) & conventions Makefile |
