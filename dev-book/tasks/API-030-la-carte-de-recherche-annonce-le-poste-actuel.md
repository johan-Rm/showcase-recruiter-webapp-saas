# TASK API-030 — la carte de recherche annonce le poste actuel

- **Créée le** : 2026-08-05.
- **Session** : `450f2479-6465-4986-9ed9-adc417bbbdaf`.
- **Statut** : ✅ fait le 2026-08-06 — `currentTitle` est servi sur la carte de recherche, masqué comme dévoilé, et la clé est omise quand aucun poste n'est dérivable.
- **Type** : feat.
- **Tests** : fonctionnel sur la recherche — le champ est présent, il porte l'intitulé attendu, la clé est **absente** quand aucun poste n'a été dérivé (le normaliseur omet les nulls, la carte annonçait `null` à tort), et il sort **même sur un profil non dévoilé**.
- **Réf** : aval `ENT-052` (l'affichage) · amont `API-018` (accès payants), task `117` (dérivation du poste actuel) · `api/src/ApiResource/Output/CandidateCardOutput.php` · `api/src/State/CandidateMapper.php` · `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md`.

## Contexte

Le poste actuel d'un candidat est calculé à l'ingestion et rangé dans la réplica Jarvi
(`currentTitle`, `currentCompanyName`, `currentJobConcept`, `currentPositionCertainty`). Le détail
d'un profil le sert déjà — `CandidateOutput` l'expose depuis la task `117`.

**La carte de recherche, elle, ne le sert pas.** `CandidateCardOutput` porte `headline` et rien
d'autre pour dire ce que fait la personne. Or `headline` n'est pas un intitulé de poste : c'est la
phrase d'accroche que le candidat écrit sur son réseau, et elle est faite pour vendre, pas pour
qualifier.

Mesuré sur le vivier au 2026-08-05, 3 053 profils :

| | `currentTitle` | `headline` |
|---|---|---|
| profils renseignés | 2 222 (73 %) | 3 042 (99,6 %) |
| longueur médiane | 25 caractères | 49 caractères |
| 90ᵉ centile | 52 | **128** |
| maximum | 100 | **752** |

Un profil réel : intitulé « Développeur & DevOps freelance — j'industrialise les produits nés de
l'IA » (73 car.), accroche « Le MVP monté à l'IA tourne en démo. En production, c'est une autre
histoire. J'industrialise : sécurité, scalabilité, fiabilité | Développeur + DevOps freelance |
Symfony · Laravel · Docker » (189 car.). C'est la seconde que le recruteur lit dans la colonne
« Poste », et le métier y arrive en quatrième position.

Le front en a conscience et ne peut rien faire : le commentaire de
`webapp/spaces/entreprise/app/components/search/ProfileCard.vue` note depuis `ENT-006` que *« le
vrai intitulé (`currentTitle`) n'est pas encore dans le contrat de la carte ; d'ici là on borne »*.
Cette tâche lève ce blocage. Elle ne touche pas au front.

## Périmètre

- `CandidateCardOutput` gagne un champ `currentTitle` (`?string`), documenté comme les voisins.
- `CandidateMapper::toCard()` le peuple depuis `$jarvi->currentTitle`.
- Test fonctionnel dans la suite de recherche existante.

## Hors périmètre

- **`currentCompanyName`** — l'employeur est une donnée de dévoilement, il ne descend pas sur la
  carte. Il reste servi par `CandidateOutput` sous condition d'accès.
- **`currentPositionCertainty`** — le doute existe et il est réel (410 profils, 13 % du vivier, ont
  plusieurs postes ouverts en même temps), mais il ne sort pas sur la carte pour l'instant. Décidé
  au cadrage : la colonne du tableau se recharge de ce qu'on vient d'en retirer.
- **`currentJob`** (le métier rattaché au référentiel) — la carte n'en a pas l'usage aujourd'hui ;
  les filtres passent par les critères, pas par le champ affiché.
- **Le repli sur l'accroche quand le poste manque** — c'est une règle d'affichage, elle appartient à
  `ENT-052`. Le serveur omet la clé, il ne choisit pas à la place du front.
- **La qualité des intitulés** — 20 % dépassent 40 caractères, un sur cinq empile périmètre,
  employeur et pile technique. Ni troncature ni nettoyage ici : le serveur sert ce que Jarvi porte.

## Décisions de cadrage

- **Un seul champ, pas le bloc « poste actuel » complet.** Servir la certitude et l'employeur avec
  aurait été cohérent, mais aucun des deux n'a de consommateur : le premier est écarté à l'écran, le
  second est payant. Un contrat s'élargit quand quelque chose le lit.
- **`currentTitle` est visible même sur un profil masqué**, sans condition d'accès. C'est la règle
  déjà écrite sur `CandidateOutput` et elle tient ici : un intitulé de poste **qualifie sans
  nommer**, comme les années d'expérience ou les métiers exercés. C'est l'employeur qui ré-identifie,
  et lui reste derrière le dévoilement.
- **`api/openapi.json` ne bouge pas, et c'est normal — découvert à l'implémentation.** La carte de
  recherche **n'existe pas** dans le contrat généré : elle n'y apparaît que nichée dans
  `CandidateSearch.CollectionOutput`, dont les `items` sont typés `(string | null)[]`. Régénérer le
  contrat après l'ajout du champ produit un fichier identique — vérifié, `currentTitle` n'y figure
  toujours qu'une fois, sur le schéma `Candidate` du détail. La ligne « openapi régénéré » du
  périmètre initial était donc fausse et a été retirée.
- **Conséquence, et elle sort de cette lane** : le type front `CandidateCard` est écrit **à la main**
  dans `webapp/shared/types/candidate-card.ts`, et son propre en-tête le dit — *« toute évolution de
  la carte côté API doit être reportée ici à la main, sans quoi rien ne le signalera »*. La gate de
  contrat ne couvre pas ce fichier. Le report du champ appartient à **`ENT-052`**, l'espace
  consommateur ; il n'est pas fait ici.
- **La clé est OMISE, pas servie à `null`.** `CandidateOutputNormalizer` supprime les nulls pour
  alléger une page de 1 600 cartes. Une garde `null !== currentTitle` côté front laisserait donc
  passer l'absence — c'est le piège déjà constaté sur `matchedOn`. Le test le verrouille par
  `assertArrayNotHasKey`.
- **Nullable assumé, et fréquent** : 831 profils (27 %) n'ont aucun poste dérivable — leur parcours
  ne porte aucune expérience ouverte. Ce n'est pas une donnée manquante à combler côté serveur, c'est
  un fait sur le profil. La page « Santé du vivier » de l'espace Admin le compte déjà sous
  `Poste actuel → ABSENT`.

## Règles et cas limites

- Profil jamais ingéré (coquille de réplica créée avec la projection) : `currentTitle` vaut `null`,
  comme `headline` aujourd'hui. Aucun traitement particulier.
- Profil masqué : le champ sort en clair. À vérifier explicitement en test — c'est la règle la moins
  intuitive de la tâche, et la plus facile à casser par excès de prudence.
- `headline` **reste dans le contrat**, inchangé. Deux consommateurs le lisent, et `ENT-052` s'en
  sert comme repli.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` — l'inventaire de ce que
  la carte de recherche expose (§ champs servis) ; la ligne devient fausse sans mise à jour.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md` — **ajoutée en cours de tâche.** La page
  affirme « zéro type recopié à la main » et deux gardes automatiques sur tout changement de DTO ;
  cette tâche est le contre-exemple. L'exception de la carte de recherche y est désormais écrite.
- `api/openapi.json` — vérifié inchangé, cf. décisions de cadrage.

## Vérification

**Appel réel** — `GET /v1/search/candidates?perPage=6`, authentifié en `entreprise@showcase-recruiter-webapp-saas.example`
(`ROLE_COMPANY_OWNER`), stack du worktree `api`, 3 052 profils dans le jeu :

| `givenName` | `headline` (tronqué) | `currentTitle` | clé |
|---|---|---|---|
| Youssef | Auditeur interne | — | **absente** |
| Lamiae | Chef de projet supply chaine-logi… | Assistant exploitation | présente |
| Manal | Senior Accountant | Comptable senior | présente |
| Walid | Researcher Ph.D Student\|Immunolog… | 1st Year Ph.D Studies | présente |
| Mohamed | INGÉNIEUR EN GÉNIE CIVIL ET EXPE… | Chargé d'études BIM et Réalité augmentée | présente |
| Achraf | SKEMA BUSINESS SCHOOL Student | — | **absente** |

Les six portent `revealedEmail: false` et `contactEmail: null` — **profils masqués**, et le poste
sort quand même. C'est le point le plus facile à casser par excès de prudence.

**Tests impactés** — `php bin/phpunit --filter 'CandidateTest::testSearchCard' --testdox` :

```
✔ Search card exposes differentiators
✔ Search card exposes experience years while masked
✔ Search card exposes current title while masked
✔ Search card omits current title without a derived position
✔ Search card exposes reachability without value while masked
✔ Search card caps skills preview but detail keeps all

OK (6 tests, 49 assertions)
```

**Contrat** — `api:openapi:export` relancé après vidage du cache : `api/openapi.json` **inchangé**,
`currentTitle` n'y figure toujours qu'une fois (schéma `Candidate` du détail). Conforme à la
décision de cadrage ci-dessus : la carte n'existe pas dans l'OpenAPI.

## Livré

- `api/src/ApiResource/Output/CandidateCardOutput.php` (M) — champ `currentTitle` entre `headline`
  et `experienceYears`, avec la raison de sa visibilité en masqué.
- `api/src/State/CandidateMapper.php` (M) — `toCard()` le peuple depuis la réplica Jarvi.
- `api/tests/Functional/CandidateTest.php` (M) — deux tests : le champ servi masqué, la clé omise
  sans poste dérivé.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` (M) — l'inventaire de ce
  que la carte expose.

## Reste à faire (hors périmètre)

- **`ENT-052`** porte l'affichage : le report du champ dans le type manuel
  `webapp/shared/types/candidate-card.ts`, puis la colonne de la Table et le titre de la fiche.
  Rien n'est visible à l'écran tant qu'elle n'est pas livrée.
- **Doc périmée non corrigée** : deux lignes sous le bloc édité, l'inventaire affirme encore que la
  carte « n'expose ni années d'expérience ni langues » — faux depuis la task `086` — et énumère des
  salaires retirés du produit par la task `074`. Hors périmètre de cette tâche, non cardé.

## Définition de terminé

- [x] `CandidateCardOutput` porte `currentTitle`, avec le commentaire qui dit pourquoi il est visible
      même masqué.
- [x] `CandidateMapper::toCard()` le peuple.
- [x] Un appel réel à la recherche montre le champ rempli sur un profil qui a un poste, **absent** sur
      un profil sans poste dérivé, et servi sur un profil non dévoilé.
- [x] Test fonctionnel couvrant ces cas — `CandidateTest::testSearchCardExposesCurrentTitleWhileMasked`
      et `::testSearchCardOmitsCurrentTitleWithoutADerivedPosition`.
- [x] `api/openapi.json` vérifié **inchangé**, et la raison écrite en décision de cadrage.
- [x] `inventaire-criteres-3-couches.md` à jour.
