# Runbook — exécuter les traitements profils et mesurer leur effet

Compagnon d'exécution de [`traitements-profils.md`](traitements-profils.md), qui décrit *ce que font*
les commandes. Ce document dit **dans quel ordre les lancer** et **comment vérifier qu'elles ont
produit ce qu'on attendait**.

Toutes les commandes passent par `make dev-api CMD="…"` (ou `make preprod-api CMD="…"`).

---

## Le principe : une photo avant, une photo après

Aucun traitement ne se déclare réussi sur son seul message de sortie. On photographie l'état de la
base, on lance, on rephotographie, et **on lit l'écart**.

```bash
make dev-api CMD="app:qualification:report --output=var/avant.json"
# … les traitements …
make dev-api CMD="app:qualification:report --compare=var/avant.json"
```

La seconde exécution affiche trois colonnes — **Avant · Après · Écart** — sur cinq sections : vivier,
qualifications du profil, poste actuel, tables dérivées, référentiels.

C'est ce qui attrape les régressions **silencieuses** : un référentiel non chargé ne fait échouer
aucune commande, il vide simplement l'expérience par secteur au prochain import. Sans écart affiché,
personne ne le voit.

---

## Depuis zéro — reconstruire tout l'environnement

C'est la vérification la plus probante : partir d'une base **vide** rend la colonne « Avant » nulle,
et la colonne « Écart » devient exactement *ce que les traitements ont produit*.

### Avant de lancer

`make dev-clean` supprime les volumes Docker, donc **la base de développement**. Deux choses à
vérifier d'abord :

- **`api/.env` survivra** (c'est un fichier, pas un volume) — mais c'est lui qui porte
  `JARVI_API_KEY`, non versionnée. Sans elle, l'étape 4 ne ramènera aucun profil. À contrôler avant
  de tout effacer, pas après.
- **Aucune donnée saisie à la main dans la base dev n'est récupérable.** Tout le reste se
  reconstruit : le schéma vient du mapping, les référentiels de fichiers versionnés, les profils de
  Jarvi.

### La séquence

```bash
# 1 — table rase : containers + volumes (la BDD dev est effacée)
make dev-down
make dev-clean

# 2 — bootstrap : .env, build, composer, JWT, schéma, fixtures, comptes, base de test
make dev-setup

# 3 — photo de départ. Pas tout à fait zéro : les fixtures chargent 3 candidats de démo
make dev-api CMD="app:qualification:report --output=var/zero.json"

# 4 — les référentiels AVANT l'ingestion (ils sont lus par elle)
make dev-api CMD="app:company:sectors:import"
make dev-api CMD="app:vocabulary:import"

# 5 — ingestion Jarvi : crée les profils ET calcule toutes les qualifications dérivées
make dev-api CMD="app:jarvi:profiles:import --full"

# 6 — concepts proches : a besoin des concepts ET des profils rattachés
make dev-api CMD="app:vocabulary:similarity"

# 7 — l'écart : ce que la chaîne complète a produit à partir de rien
make dev-api CMD="app:qualification:report --compare=var/zero.json"
```

**Étape 5 : l'import CRÉE les profils** au lieu de les mettre à jour — c'est l'étape longue, elle
appelle l'API Jarvi pour chaque profil. Attendre `0 créés` ici signifierait que quelque chose a
échoué ; on attend au contraire ~1 699 créations.

### Où passent les 11 commandes

La séquence n'en appelle que 5. Ce n'est pas un oubli — voici le sort de chacune :

| Commande | Dans la séquence ? | Pourquoi |
| --- | --- | --- |
| `app:company:sectors:import` | ✅ étape 4 | référentiel lu par l'ingestion |
| `app:vocabulary:import` | ✅ étape 4 | référentiel lu par l'ingestion |
| `app:jarvi:profiles:import` | ✅ étape 5 | crée les profils et toutes les qualifications |
| `app:vocabulary:similarity` | ✅ étape 6 | après l'ingestion |
| `app:qualification:report` | ✅ étapes 3 et 7 | la photo avant / après |
| `app:accounts:seed-team` | ⚙️ déjà exécutée | `dev-setup` l'appelle |
| `app:vocabulary:harvest` | ⭕ facultative | écrit un JSON, jamais en base |
| `app:school:harvest` | ⭕ facultative | idem |
| `app:jarvi:reconcile` | ⚠️ **à éviter en dev** | désactive tout candidat absent du périmètre Jarvi — donc **les comptes de fixtures et de recette** (voir ci-dessous) |
| `app:vocabulary:enrich` | 🔴 exclue | LLM, payante |
| `app:company:sectors:classify` | 🔴 exclue | LLM, payante |

Pour exercer les **9 commandes gratuites** et vérifier qu'aucune n'est cassée, ajouter en fin de
séquence — sans effet de bord :

```bash
make dev-api CMD="app:vocabulary:harvest"
make dev-api CMD="app:school:harvest"
```

#### `app:jarvi:reconcile` : ne pas la lancer sur une base de dev fraîche

Constaté le 2026-07-22 : sur une base issue de `dev-setup`, elle **désactive 5 candidats** — les 3
profils de démo des fixtures **et les 2 comptes de recette** (`candidat@showcase-recruiter-webapp-saas.example`,
`demo.member@showcase-recruiter-webapp-saas.example`). C'est son travail : elle désactive tout candidat absent du périmètre
Jarvi, et les comptes locaux en sont absents par construction.

`app:accounts:seed-team` ne les réactive pas — elle ne touche pas au champ `active`. Réparation :

```bash
docker compose -f docker-compose.dev.yml exec db \
  psql -U showcase-recruiter-webapp-saas -d showcase-recruiter-webapp-saas -c \
  "UPDATE candidate SET active = true WHERE id IN (SELECT c.id FROM candidate c JOIN person p ON p.id = c.person_id WHERE p.contact_email IS NOT NULL);"
```

La commande garde tout son sens en preprod et en production, où le périmètre Jarvi fait autorité.

### L'erreur d'ordre à ne pas refaire

La séquence longtemps pratiquée était :

```bash
make dev-setup
make dev-api CMD="app:jarvi:profiles:import --full"
make dev-api CMD="app:vocabulary:import"      # ← trop tard
                                              # ← et app:company:sectors:import absent
```

Elle **n'échoue pas** : les deux commandes affichent `[OK]`. Mais l'ingestion lit les référentiels
pour rattacher chaque poste à un secteur et à un métier ; lancée avant eux, elle ne trouve rien à
rattacher et produit une base silencieusement appauvrie :

| Métrique | Ordre erroné | Ordre correct |
| --- | ---: | ---: |
| Expérience secteur — candidats | **0** | 1 311 |
| Expérience métier — candidats | **0** | 1 405 |
| Poste actuel — métier reconnu | **0** | 691 |
| Concepts proches | **0** | 3 826 |

C'est exactement ce que le rapport avant/après rend visible — et rien d'autre ne le rendrait.

**Ne pas utiliser `make dev-vocabulary`** dans cette séquence : cette cible enchaîne
`vocabulary:enrich` (LLM, payant) avant l'import. Le chargement seul suffit et c'est
`app:vocabulary:import`.

---

## Ordre d'exécution complet

**L'ordre n'est pas libre.** L'ingestion (A) lit les référentiels produits par C et B : lancée avant
eux, elle ne trouve rien à rattacher et produit un profil appauvri, sans erreur.

```bash
# 0 — photo de départ
make dev-api CMD="app:qualification:report --output=var/avant.json"

# 1 — C · secteurs d'entreprise (référentiel lu par l'ingestion)
make dev-api CMD="app:company:sectors:import"

# 2 — B · vocabulaire (référentiel lu par l'ingestion pour les métiers)
make dev-api CMD="app:vocabulary:import"

# 3 — A · ingestion Jarvi : recalcule TOUTES les qualifications dérivées
make dev-api CMD="app:jarvi:profiles:import --full"

# 4 — B · concepts proches (a besoin des concepts ET des profils rattachés)
make dev-api CMD="app:vocabulary:similarity"

# 5 — photo d'arrivée et écart
make dev-api CMD="app:qualification:report --compare=var/avant.json"
```

Les étapes 1 et 2 sont instantanées et sans LLM. L'étape 3 est la plus longue : elle appelle l'API
Jarvi pour chaque profil.

### Récolte (facultative, hors chemin critique)

Ces commandes n'écrivent qu'un JSON dans `api/var/`, jamais en base. Elles servent à préparer un
enrichissement, pas à faire tourner la plateforme :

```bash
make dev-api CMD="app:vocabulary:harvest"   # termes distincts + fréquences
make dev-api CMD="app:school:harvest"       # établissements par nom normalisé
```

### Les deux commandes à clé LLM

`app:vocabulary:enrich` et `app:company:sectors:classify` exigent `ANTHROPIC_API_KEY`, coûtent de
l'argent et **ne font pas partie du cycle courant**. Elles s'exécutent rarement, à la main, et leur
sortie est relue en `git diff` avant d'être chargée.

---

## Ce qu'on doit lire dans l'écart

Repères constatés au 2026-07-22 sur le vivier de développement (1 699 profils). Un écart franc avec
ces ordres de grandeur, après un cycle complet, signale un problème.

Valeurs **constatées** lors d'une reconstruction complète depuis zéro le 2026-07-22 — la chaîne est
reproductible, ces nombres se retrouvent à l'identique :

| Métrique | Attendu | Ce qu'un effondrement signifierait |
| --- | ---: | --- |
| Profils actifs | 1 699 | ingestion incomplète |
| Expérience secteur — candidats | 1 311 | `company_sector` vide : l'étape 4 n'a pas tourné |
| Expérience métier — candidats | 1 405 | `vocabulary_*` vide : l'étape 4 n'a pas tourné |
| Expérience métier — postes rattachés | 5 500 dont 2 043 à l'identique | reconnaissance partielle inopérante |
| Poste actuel | 918 CERTAIN · 236 AMBIGUOUS · 545 ABSENT | — |
| Poste actuel — métier reconnu | 691 | vocabulaire chargé après l'ingestion |
| Langues | 1 479 candidats · 3 091 lignes | — |
| Niveau d'études dérivé | 1 309 | — |
| Date de modification source | 1 694 | ingestion partielle |
| Concepts proches | 3 826 | étape 6 non lancée, ou lancée avant l'étape 5 |
| Référentiels | 493 concepts · 1 072 libellés · 1 207 entreprises | fichier de données non chargé |

**Un écart nul partout après une réingestion `--full` est normal** : les traitements sont
idempotents. C'est un écart **négatif** qui doit alerter.

---

## Points de vigilance

- **`--full` rejoue tout le vivier** contre l'API Jarvi. Pour une vérification rapide, préférer
  `--limit=50`, qui suffit à prouver qu'une chaîne fonctionne sans solliciter la source entière.
- **Les tables dérivées sont recalculables** : `candidate_job_experience`,
  `candidate_sector_experience`, `candidate_language` et `vocabulary_concept_similarity` sont
  entièrement reconstruites. Les vider n'est jamais une perte, à condition de relancer.
- **Le rapport est en lecture seule** : il n'écrit jamais en base et peut être lancé à tout moment,
  y compris en preprod.
- **Après modification d'un DTO de sortie**, `make dev-openapi` ne suffit pas : le cache des
  métadonnées API Platform doit être vidé (`make dev-api CMD="cache:clear"`), sinon le contrat
  régénéré est incomplet et la CI échoue sur son `git diff --exit-code`.
