# showcase-recruiter-webapp-saas — Source de vérité des contrats de données

> Comment `api/` (PHP) et `webapp/` (TypeScript) partagent **un seul** contrat (DTO, types, enums,
> constantes), sans duplication ni dérive à travers la frontière de langage. Complète
> [api-conventions.md](./api-conventions.md) et [api-structure.md](./api-structure.md).

## Principe

**Une seule source de vérité ; le reste est généré ou dérivé.** Recopier les contrats des deux
côtés recrée, au niveau du contrat, l'incohérence qu'on combat au niveau de la base
([data-integrity.md](./data-integrity.md)) — mais en silence.

**La source = l'API.** Cohérent avec « l'API Symfony est le seul point d'écriture et la source de
vérité ». L'**OpenAPI** (généré par API Platform depuis nos DTO + enums PHP) est le **contrat
formalisé** ; le webapp en **dérive** ses types.

## Chaîne de génération (types & enums)

```
api/  DTO (ApiResource Output/Input) + enums PHP
        │  (API Platform)
        ▼
   OpenAPI  api/openapi.json (exposé sur /v1/docs)   ← contrat formalisé, source unique
        │  (openapi-typescript)
        ▼
webapp/  shared/types/api.ts (généré)  →  consommé par la couche services/
```

- **Enums** : enums PHP (string) → enums OpenAPI → **unions TS** automatiquement.
  Source unique = l'enum PHP.
- **DTO** : nos **Output DTO plats** définissent les schémas OpenAPI → types TS exacts (relations
  `uuid`, value objects en sous-objet). C'est la même forme que le **JSON Schema des tests**.
- **Valeurs runtime** dont le webapp a besoin (listes à afficher) : exposées par l'API via `/v1/config`.

### Outillage & emplacement
- Générateur webapp : **`openapi-typescript`**, via `bun run quality-generate-schemas`
  (source `api/openapi.json`, sortie `webapp/shared/types/api.ts`).
- Les types générés sont **committés** dans `webapp/` et **régénérés** depuis l'OpenAPI courant.
- Consommés par la couche **`services/`** du webapp.

## Accès aux DTO & Enums

**Où ils vivent (source de vérité PHP) :**
- **DTO** → `src/ApiResource/Input/` (entrée) et `src/ApiResource/Output/` (sortie).
- **Enums** → `src/Enum/` (enums PHP backed).

**Exposition & accès :** les deux remontent dans l'**OpenAPI** (DTO = schémas, enums = `enum` des
propriétés) → le webapp en **génère** ses types.

- **Types** (interfaces DTO + unions d'enum) : générés depuis l'OpenAPI, consommés par `services/`.
- **Valeurs runtime d'enum** (pour un `<select>`) : dérivées des types générés — pas de recopie.
- **Labels d'affichage d'enum** : **i18n UI webapp** (clés `enums.<enum>.<valeur>`), conformément à
  « enums techniques → labels via i18n ».

> **Enums ≠ vocabulaires ouverts.** Les **enums** (availability, returnHorizon, visibility,
> organizationRole…) sont du **code** (`src/Enum/`) → types générés + i18n webapp. Les **vocabulaires
> ouverts** (secteur, fonction, compétences) sont des **valeurs libres** portées par la donnée : ils ne
> sont ni typés ni traduits côté back, et les listes du cockpit se déduisent du vivier (facettes).
> Depuis l'abandon de `CategoryCode` (2026-07-13), il n'y a **plus de référentiel en base**.

## Constantes & valeurs métier — exposées par l'API

Les **valeurs métier** (coût d'un accès dévoilé = 1, crédits offerts = 2, locales FR/AR/EN, bornes
de pagination…) ne sont **pas** codées en dur des deux côtés. **L'API les expose**
(`GET /v1/config`) ; le webapp les **lit**.

> Le coût est celui d'**un accès**, pas d'un profil : `EMAIL`, `PHONE`, `RESUME` et
> `INTERVIEW_REPORT` se paient chacun un crédit et s'obtiennent indépendamment. La carte candidat
> le reflète par `revealedEmail`, `revealedPhone`, `revealedResume` et `revealedReport` — quatre
> booléens, jamais un seul.

- Source unique = le **domaine backend**.
- Valeurs modifiables **sans redéploiement du webapp**.
- Les constantes **purement UI** (thème, breakpoints, libellés non métier) restent côté webapp.

## Règles de validation runtime

L'OpenAPI expose déjà une partie des contraintes (`required`, `minLength`, `maxLength`, `format`,
`minimum`) mais ne couvre pas proprement toutes les règles Symfony Validator, notamment les règles
croisées (`endDate >= startDate`) et les groupes métier.

Pour éviter de recopier ces règles côté webapp, l'API expose :

```http
GET /v1/validation-rules/{form}
```

La réponse est dérivée des métadonnées Symfony Validator des DTO `ApiResource/Input` :

```json
{
  "form": "presencePeriod",
  "inputClass": "App\\ApiResource\\Input\\PresencePeriodInput",
  "fields": {
    "endDate": [
      { "type": "notNull", "groups": ["Default"] },
      { "type": "greaterThanOrEqual", "propertyPath": "startDate", "groups": ["Default"] }
    ]
  },
  "classRules": []
}
```

Le webapp peut donc générer les contrôles UX à partir de cette route, tout en laissant l'API comme
autorité finale via les réponses `422 ConstraintViolation`.

## Anti-dérive (gate CI)

- Un job CI **régénère** les types TS depuis l'OpenAPI courant et **échoue si le résultat diffère**
  des types committés → garantit que le webapp ne dérive **jamais** du contrat API.
- Tout changement de DTO/enum côté API force la régénération (et donc l'adaptation explicite du
  webapp) au lieu d'une casse silencieuse au runtime.

## Tests du contrat (déjà couverts par la stratégie)

Le contrat se vérifie à trois niveaux, chacun déjà outillé :

| Vérification | Mécanisme | Doc |
| --- | --- | --- |
| L'API respecte son contrat (forme de réponse) | **JSON Schema par ressource** (tests fonctionnels) | [api-testing.md](../3.quality/api-testing.md) |
| Les types TS collent au contrat | **gate CI anti-dérive** (régénère depuis l'OpenAPI, échoue si diff) | ci-dessus |
| Le webapp utilise correctement les types | **vue-tsc type-check** (CI) | [testing-and-ci.md](../3.quality/testing-and-ci.md) |

**Clé de voûte — une seule source nourrit tests + webapp + doc.** API Platform génère le **JSON
Schema** *et* l'**OpenAPI** depuis les **mêmes métadonnées** (nos Output DTO) :

- les tests fonctionnels assèrent contre le **JSON Schema généré par API Platform**
  (`assertMatchesResourceItemJsonSchema()` / `...CollectionJsonSchema()`), **jamais un schéma
  écrit à la main** ;
- le webapp dérive ses types du **même** OpenAPI.

⇒ Un changement de DTO se propage partout et est rattrapé par **deux gardes** (JSON Schema côté
API, drift-gate + type-check côté webapp). **Ne jamais écrire de JSON Schema à la main** (recrée une
source qui dérive).

### Les exceptions : la carte de recherche, et le profil complet

Deux DTO du vivier échappent aux gardes, **pour deux raisons différentes**, et tous deux ont un
miroir écrit à la main dans `webapp/shared/types/`.

**`CandidateCardOutput` y échappe entièrement.** Il n'apparaît nulle part dans l'OpenAPI : la carte
n'y existe que nichée dans `CandidateSearch.CollectionOutput` et `…SearchResultsOutput`, dont les
`items` sont typés `(string | null)[]`. Ajouter un champ à ce DTO **ne change pas
`api/openapi.json`** — donc ni la drift-gate ni `vue-tsc` ne voient l'écart. Miroir :
`candidate-card.ts`. Constaté en `API-012` (376 tests API verts, front rouge) et reconduit en
`API-030`.

**`CandidateOutput` n'y échappe qu'à moitié**, et c'est plus insidieux. Le schéma `Candidate`, lui,
**est** publié : ses champs scalaires sont couverts par la drift-gate. Mais ses tableaux — parcours,
formations, langues, CV, périodes de présence — sortent en `(string | null)[]`, l'OpenAPI ne
descendant pas dans la forme de leurs éléments. Le miroir `candidate-profile.ts` rétablit ces formes
à la main ; **changer la structure d'une expérience ou d'une formation ne fera donc rougir aucune
garde**, alors que renommer un champ scalaire, si.

Trois conséquences pour qui touche l'un de ces DTO : reporter le champ dans le miroir **dans la
tâche de l'espace consommateur** ; se méfier d'un champ **obligatoire**, qui casse tout ce qui
construit un profil en littéral, à commencer par les fixtures de test du front ; et se rappeler que
**la fiche détaillée ne porte pas tout ce que porte la carte** — `returnProject`, `hasPiedATerre`,
`piedATerreCity` et `isInMorocco` n'existent que sur la carte, ce que `services/search/profile.ts`
compense en recollant les deux sources.

**Un champ du miroir que personne n'affiche n'est vérifié par rien** — et c'est la conséquence la
plus coûteuse, parce qu'elle ne se déclenche pas au moment où l'erreur est écrite. `ENT-121` a trouvé
trois formes fausses posées de longue date : `jobExperiences` et `sectorExperiences` étaient déclarés
en tableaux de chaînes quand le serveur envoie des objets, et l'école d'une formation arrive tantôt
en texte, tantôt en objet `{ id, name }` selon la source du profil. Aucune garde n'a rougi, ni au
moment de la déclaration ni ensuite : le contrôle des types ne peut comparer le miroir à rien, et
tant qu'aucun écran ne lit le champ, aucun test ne l'exerce. Les trois se sont vues **à la capture
d'écran**, en JSON brut au milieu d'une fiche.

⇒ Écrire dans le miroir un champ qu'on n'affiche pas **le même jour**, c'est poser une affirmation
que rien ne relira. Soit on le consomme, soit on ne l'écrit pas.

## Règles structurantes

- **Source de vérité = l'API** ; OpenAPI = contrat formalisé ; **types TS générés** côté webapp
  (openapi-typescript). Zéro type recopié à la main.
- **Enums** : enum PHP source → OpenAPI → TS.
- **Constantes/valeurs métier** : **exposées par l'API** (`GET /v1/config`), lues par le webapp ;
  pas de valeur en dur des deux côtés.
- **Gate CI anti-dérive** : types générés vérifiés contre l'OpenAPI courant.
