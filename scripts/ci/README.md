# scripts/ci

> **État : placeholder.** Le moteur décrit ici sera **implémenté au scaffolding** (étape 2 de la
> roadmap). Ce README pose l'intention et le contrat ; les scripts et règles seront ajoutés à ce
> moment-là.

## Rôle et responsabilités

Moteur de **validation des conventions techniques** piloté par des règles déclaratives, exécuté
en CI pour détecter les dérives architecturales et réduire la dette de façon incrémentale et
documentée.

Modèle :

```
README (convention courte) → docs/showcase-recruiter-webapp-saas/content/ (explication + exemples) → YAML (règle) → script CI (contrôle)
```

C'est le principal **garde-fou contre la dérive du code généré par IA** : chaque convention
automatisable devient une règle vérifiée, qui bloque le merge si elle échoue (cf.
[docs/showcase-recruiter-webapp-saas/content/3.quality/testing-and-ci.md](../../docs/showcase-recruiter-webapp-saas/content/3.quality/testing-and-ci.md) — gates de pré-merge).

Périmètre (monorepo) :

- **`webapp/`** — contrôles TypeScript/Vue exécutés par des scripts `verify-*.ts` (ex. composants
  sans logique métier, `services/` sans import framework, placement des types, SoC, SOLID).
- **`api/`** — frontières d'architecture déléguées à **Deptrac** + règles PHP ; les contrôles
  transverses (taille des fonctions/fichiers, secrets, fichiers interdits) suivent le même
  format de règle.

La spec détaillée du moteur sera formalisée dans une tâche `dev-book/tasks/` au moment du
scaffolding.

## Conventions techniques

### Structure d'une règle (YAML)

```yaml
name: string                # identifiant unique kebab-case
description: string         # ce que la règle détecte
families:                   # une ou plusieurs familles de contrôle
  - Lisibilite
severity: error | warning | info
requiresManualReview: bool  # false = déterministe (bloquant possible) ; true = heuristique (rapport)
rule:                       # paramètres du contrôle (seuils, patterns…)
message: string             # message affiché en cas de violation
```

### Familles de contrôles

| Famille | Exemples |
|---|---|
| Lisibilité | taille des fonctions, taille des fichiers |
| Clean Code | TODO sans contexte, duplication |
| Architecture & Découplage | dépendances circulaires, couplage fort |
| Robustesse & Fiabilité | exceptions silencieuses, paramètres excessifs |
| Performance & Stabilité | N+1 détectables, eager loading systématique |
| Tests | classes sans tests, couverture minimale |
| Documentation | méthodes publiques non documentées |
| Gouvernance Technique | fichiers interdits committés, secrets en clair |

### Règles déterministes vs heuristiques

- **Déterministes** (`requiresManualReview: false`) : le script statue seul (compter des lignes,
  détecter un pattern, analyser les imports). **Bloquant en CI.**
- **Heuristiques** (`requiresManualReview: true`) : le script signale, un humain tranche
  (duplication sémantique, couplage non trivial). **Rapport uniquement, jamais bloquant.**

### Approche progressive (MVP du moteur)

Démarrer avec quelques règles simples avant toute règle projet-spécifique :

1. **Taille des fonctions** (méthodes > N lignes, ex. 40)
2. **Taille des fichiers** (> N lignes, ex. 300)
3. **Dépendances circulaires** (analyse des imports / `use`)

Convention de placement : un sous-dossier par périmètre (`webapp/`, `services/`, …), chacun avec
son `rules.yaml` et ses scripts `verify-*.ts` ; chaque script de contrôle est lui-même couvert
par un test (`*.test.ts`).
