# showcase-recruiter-webapp-saas — Principes d'ingénierie

> Rappel des principes structurants (définis dans le socle global) **dans le contexte showcase-recruiter-webapp-saas**,
> et surtout leur **mécanisme d'application** : ce qui est vérifié automatiquement vs ce qui
> relève du jugement humain. Alimente les familles de règles de
> [scripts/ci/](../../../../scripts/ci/README.md) et la [checklist de review](./code-review-checklist.md).

## Les 5 principes

| Principe | Définition | Application à showcase-recruiter-webapp-saas |
| --- | --- | --- |
| **Clean Code** | Code simple, lisible, cohérent, maintenable, orienté humain autant que machine. | Nommage explicite, fonctions courtes, pas de TODO sans contexte, pas de duplication. |
| **SOLID** | 5 principes de conception orientée objet évolutive et testable. | Entités/services Symfony à responsabilité unique, contrats clairs (interfaces), faible couplage côté `api/` ; composables/services ciblés côté `webapp/`. |
| **YAGNI** | *You Aren't Gonna Need It* — ne pas développer ce qui n'est pas strictement nécessaire. | Périmètre mince, pas d'abstraction prématurée : on n'écrit que ce que le produit demande. |
| **KISS** | *Keep It Simple* — rechercher la simplicité, éviter la complexité inutile. | Guide les choix structurants : GitHub Flow allégé (vs Git Flow), recherche Postgres sur la réplica locale, seuils ratchet plutôt que seuils durs. |
| **Separation of Concerns** | Séparer clairement les responsabilités : code plus clair, modulaire, maintenable. | Frontières strictes : `pages`/`components`/`services` côté webapp ; couches Domain/ApiResource/Repository côté api. Écriture en base **uniquement** via l'API Symfony. |

## Mécanisme d'application : automatisé vs humain

Distinction essentielle — **un script ne garantit pas tout**. Certains principes sont vérifiables
en CI, d'autres relèvent de la review.

Deux natures de contrôle (cf. [scripts/ci/](../../../../scripts/ci/README.md)) :

- **Déterministe** (`requiresManualReview: false`) → peut être **bloquant** en CI.
- **Heuristique** (`requiresManualReview: true`) → **warning non bloquant** : la machine signale
  un candidat, l'humain tranche. Même les principes « humains » ont des règles heuristiques qui
  aident à améliorer la base de code.

| Principe | Contrôle | Mécanisme |
| --- | --- | --- |
| **Separation of Concerns** | déterministe (bloquant) | **Deptrac** (frontières de couches `api/`) ; `verify-conventions.ts` + `rules.yaml` (`webapp/`, ex. `services-no-framework-imports`, `app-no-fetch-in-components`) ; règle « écriture base via API Symfony seulement » |
| **Clean Code** | déterministe (bloquant) + lint | taille fonctions/fichiers, TODO sans contexte, duplication via `scripts/ci/` ; ESLint, Prettier, php-cs-fixer |
| **SOLID** | mixte | déterministe : PHPStan (typage, contrats), nombre de paramètres ; heuristique (warning) : taille de classe, responsabilités multiples, couplage fort |
| **KISS** | **heuristique (warning)** | signaux de complexité : complexité cyclomatique élevée, imbrication profonde, fichiers/fonctions trop longs → **warning**, l'humain arbitre |
| **YAGNI** | **heuristique (warning)** | signaux de spéculation : code mort, exports non utilisés, abstraction à implémentation unique → **warning**, l'humain arbitre |

**Règle d'or** : un script ne *garantit* pas KISS/YAGNI — il ne peut pas juger de la pertinence
d'une conception. Mais il peut **signaler en warning** les candidats à simplification. La décision
finale reste à la review. Les contrôles **bloquants** se limitent à SoC, Clean Code déterministe
et la partie statique de SOLID.

## Familles de règles correspondantes (moteur `scripts/ci/`)

| Famille de règles | Principe(s) couvert(s) | Sévérité typique |
| --- | --- | --- |
| Lisibilité, Clean Code | Clean Code, KISS | error (déterministe) + warning (heuristique) |
| Architecture & Découplage | SoC, SOLID | error |
| Robustesse & Fiabilité | SOLID, Clean Code | error + warning |
| Simplicité & Sobriété | **KISS, YAGNI** | **warning** (heuristique, non bloquant) |
| Tests | (transverse — soutient la testabilité visée par SOLID) | error + warning |

KISS et YAGNI disposent donc bien de règles (famille « Simplicité & Sobriété »), en **warning**
uniquement. Elles complètent la [checklist de review](./code-review-checklist.md), qui reste
l'arbitre final.
