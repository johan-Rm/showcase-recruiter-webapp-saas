# TASK API-032 — api : un vivier de démonstration s'ouvre, et se referme

- **Créée le** : 2026-08-06.
- **Session** : `82b13a37-3b91-47f4-8c01-fbf60e5f6b62`.
- **Statut** : ✅ fait le 2026-08-06 — `app:candidates:consent-demo` ouvre un vivier fermé et le
  referme sans jamais toucher un consentement réel. Chaîne complète reproduite sur 3 053 profils :
  vivier fermé → 63 cartes à zéro, puis ouverture → 63 cartes de 7 à 234 profils. 5 tests,
  25 assertions.
- **Type** : Backend API — une valeur au modèle de consentement, et une commande d'amorçage.
- **Tests** : fonctionnels sur la commande — elle ouvre les profils fermés, elle ne touche jamais un
  consentement donné par une personne, elle se rejoue sans rien changer, et elle referme exactement
  ce qu'elle a ouvert. S'y ajoute le refus en production, vérifié sans base. Un geste qui écrit sur
  de la donnée à portée juridique ne se prouve pas autrement que par son effet exact.
- **Réf** : `ConsentSource` · `Candidate::giveConsent()` · `CandidateRepository::searchableQuery()`
  (la barrière que cette tâche lève) · `API-029` / `API-031` (les recherches de démonstration que
  ça rend démontrables) · `SeedTeamAccountsCommand` (le patron d'une commande d'amorçage)

## Contexte

La preprod ne peut pas montrer le produit. Son vivier compte **3 888 profils importés de Jarvi et
9 395 rattachements métier** — tout est là — mais **2 profils seulement sont consentis**. Or la
recherche n'expose qu'un profil visible, actif **et consenti** : les soixante-trois recherches de
démonstration annoncent donc zéro profil chacune, et un client à qui l'on fait tester l'outil ne voit
qu'une grille de cartes vides.

Ce n'est pas un défaut : un profil importé naît sans consentement, et c'est la règle qui protège les
candidats. Elle ne prévoit simplement pas le cas d'un environnement de démonstration.

Le modèle ne connaît que trois façons de consentir — `SELF_SERVICE`, `ONBOARDING`, `MAGIC_LINK` — et
**les trois affirment que la personne a agi elle-même**. Ouvrir le vivier avec l'une d'elles
écrirait, sur 3 886 personnes réelles, qu'elles se sont inscrites. La donnée mentirait, et rien ne
distinguerait plus les deux vrais consentements des autres.

## Périmètre

- **`ConsentSource::DEMONSTRATION`** — une quatrième valeur, qui dit ce qui s'est réellement passé :
  un environnement a été ouvert pour être montré, personne n'a rien signé.
- **`app:candidates:consent-demo`** — ouvre les profils **sans consentement**, en posant cette
  source. Réservée aux environnements de démonstration : refusée en production sauf `--force`.
- **`--revoke`** — referme **exactement** ce que la commande a ouvert, en ne touchant qu'aux
  consentements portant cette source.
- **Un consentement donné par une personne n'est jamais écrasé, ni jamais retiré.**

## Hors périmètre

- **Le parcours de consentement réel** — inscription, lien magique, retrait par le candidat :
  inchangés, cette tâche n'y touche pas.
- **La visibilité des profils.** Un profil importé naît déjà visible ; seule la barrière du
  consentement le retenait.
- **Les recherches de démonstration** (`API-029`, `API-031`), inchangées. Elles se relancent après
  coup pour recalculer leur jeu, et c'est tout.
- **La production.** Cette commande n'y a rien à faire, et le dit.

## Décisions de cadrage

- **Une source dédiée plutôt qu'une source empruntée.** C'est le cœur de la tâche : la base doit
  pouvoir dire « ouvert pour la démonstration » et non « le candidat s'est inscrit ». Sans cette
  distinction, le geste est irréversible en pratique — on ne saurait plus quoi refermer.
- **Réversible par construction**, et pas seulement en théorie : `--revoke` filtre sur la source, ce
  qui garantit qu'un vrai consentement survit à la fermeture.
- **On n'ouvre que ce qui est fermé.** Un profil déjà consenti garde sa source et sa date : la
  commande ne réécrit jamais un geste humain.
- **Écriture en masse, assumée.** Trois mille lignes passent par une requête plutôt que par
  l'entité : c'est le même choix que le recalcul d'appartenance, pour la même raison.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` — la commande rejoint le tableau
  « Hors périmètre données », et la note qui gouverne `app:searches:seed-demo` doit dire la
  dépendance réelle : un vivier chargé **et consenti**, faute de quoi la grille reste vide.

## Définition de terminé

- [x] Une quatrième source de consentement dit « ouvert pour la démonstration », distincte des trois
      gestes candidat.
- [x] La commande ouvre les profils fermés et laisse intacts ceux qui portent un consentement réel —
      date et source comprises.
- [x] `--revoke` referme exactement les profils portant cette source, et rien d'autre.
- [x] Rejouée, elle n'ouvre rien de plus.
- [x] Elle refuse de s'exécuter en production sans `--force`.
- [x] Le contrat `api/openapi.json` est inchangé : aucune surface publique n'expose cette énumération.

## Vérification

**Le symptôme de preprod, reproduit puis levé**, sur une copie du vivier de développement dont le
consentement a été effacé pour recréer les conditions exactes :

| Étape | Profils consentis | Cartes | Plus grande carte | Cartes vides |
| --- | --- | --- | --- | --- |
| vivier fermé (comme en preprod) | **0** | 63 | **0** | **63** |
| après `app:candidates:consent-demo` | 3 053 | — | — | — |
| après recalcul des recherches | 3 053 | 63 | **234** | **0** |

**La réversibilité, éprouvée en réel** : trois profils reçoivent un consentement `MAGIC_LINK`, puis
`--revoke` tourne.

| | Avant révocation | Après |
| --- | --- | --- |
| ouverts pour la démonstration | 3 050 | **0** |
| consentements réels | 3 | **3** |

La commande l'annonce elle-même : « 3050 profil(s) refermé(s). Les 3 consentement(s) réel(s) sont
intacts. »

**Tests : 5, 25 assertions, vert.** L'ouverture ne touche que les profils fermés · un consentement
réel garde sa date et sa source · rejouée, elle n'ouvre rien de plus · la révocation ne referme que
ce qu'elle a ouvert · le refus en production. Les 95 tests des parcours candidat et consentement
passent (619 assertions).

**Style, PHPStan et deptrac** sur les fichiers touchés : 0 correction, 0 erreur, 0 violation.

**Aucune migration** : `consent_source` est une colonne texte, une valeur d'énumération de plus n'y
change rien.

## Livré

- `api/src/Enum/ConsentSource.php` **(M)** — `DEMONSTRATION`, et ce qu'elle engage.
- `api/src/Domain/Candidate/DemoConsentGranter.php` **(+)** — ouvre, referme, recense.
- `api/src/Domain/Candidate/DemoConsentCensus.php` **(+)** — l'état lu avant d'écrire.
- `api/src/Command/ConsentDemoVivierCommand.php` **(+)** — la commande et son refus en production.
- `api/tests/Functional/DemoConsentCommandTest.php` **(+)** — 5 tests.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/profile-processing.md` **(M)** — la commande, et les deux
  manques qui donnent une grille vide.

## Reste à faire (hors périmètre)

- **`app:searches:seed-demo` se tait quand elle ne peut rien faire.** Elle pourrait compter les
  profils exposables avant de commencer et annoncer « aucun profil consentant : les cartes seront
  vides » plutôt qu'afficher un tableau de zéros. C'est ce silence qui a coûté une preprod entière
  à diagnostiquer.
