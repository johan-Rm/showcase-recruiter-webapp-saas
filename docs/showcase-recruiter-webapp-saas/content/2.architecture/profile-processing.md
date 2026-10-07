# showcase-recruiter-webapp-saas — Traitements sur les profils

> Référence unique des traitements appliqués aux profils du vivier : **quelles commandes existent**,
> **dans quel ordre les lancer**, et **ce qu'elles produisent**.
> Voir aussi [data-model.md](./data-model.md) (tables `vocabulary_*`),
> [jarvi-integration.md](./jarvi-integration.md) (réplica source) et le runbook d'exécution
> `dev-book/runbooks/traitements-profils.md`.

Les profils arrivent bruts de Jarvi (texte libre, aucun agrégat). Tout ce qui suit est **dérivé et
recalculable** : on n'écrit jamais dans le profil d'origine, et une valeur absente n'est jamais
remplacée par une valeur par défaut.

## Les quatre chaînes

```
A · Ingestion Jarvi     app:jarvi:profiles:import
                        └─ réplica + toutes les qualifications dérivées   (à chaque import)

B · Vocabulaire         app:vocabulary:harvest → enrich → import → similarity
                        └─ dictionnaire compétences + postes, et concepts proches
                                                                         (rare, manuel)

C · Secteurs            app:company:sectors:classify → import
                        └─ référentiel entreprise → secteur              (rare, manuel)

D · Écoles              app:school:harvest → group (DÉSACTIVÉE)
                        └─ établissements par nom normalisé ; regroupement LLM prêt
                           mais verrouillé (cf. task 115)
```

**Ne pas confondre B et C.** B fait de la **synonymie** (regrouper des mots qui disent la même
chose) ; C fait de la **classification** (ranger une entreprise dans une case). Chaînes distinctes,
patrons voisins.

## Les 14 commandes

### Chaîne A — Ingestion Jarvi

| Commande | Rôle | LLM | Options |
| --- | --- | :---: | --- |
| `app:jarvi:profiles:import`<br>*alias `app:jarvi:ingest`* | Importe les profils **et recalcule toutes les qualifications dérivées** (ancienneté, séniorité, durée poste actuel, expérience par secteur, niveau d'études) | non | `--full` `--since` `--profile-id` `--limit` `--dry-run` `--reconcile` |
| `app:jarvi:reconcile` | Désactive les projections sorties du périmètre Jarvi | non | — |

`--full` = chargement initial · sans option = incrémental via curseur `updatedAt` ·
`--profile-id` = rafraîchit un seul profil.

### Chaîne B — Vocabulaire (compétences + postes)

| Commande | Rôle | LLM | Options |
| --- | --- | :---: | --- |
| `app:vocabulary:harvest` | Récolte les termes distincts et leurs fréquences depuis la réplica | non | `--output` |
| `app:vocabulary:enrich` | Regroupe les termes récurrents en concepts → enrichit `taxonomy.json` | **oui** | `--file` `--dry-run` |
| `app:vocabulary:import` | Charge concepts + libellés en base (upsert idempotent), et **retire** les libellés qu'un concept du fichier ne revendique plus | non | `--file` `--dry-run` |
| `app:vocabulary:reproject` | Rejoue le dictionnaire sur le vivier déjà chargé — métiers exercés et concept du poste actuel — **sans repasser par Jarvi** | non | `--limit` `--dry-run` |
| `app:vocabulary:similarity` | Calcule les concepts proches par co-occurrence | non | `--min-cooccurrences` `--min-npmi` `--dry-run` |
| `app:vocabulary:related` | Affiche les concepts proches d'un concept (métier ↔ compétences inclus) | non | `--type` `--limit` |
| `app:vocabulary:missing` | Liste les intitulés de poste **cherchés** que le dictionnaire ignore, du plus fréquent au moins fréquent — une liste à instruire, pas un enrichissement | non | `--limit` `--min` |

Le confort dev `make dev-vocabulary-refresh` enchaîne `enrich`, `import` puis `reproject`.

**`import` seul ne déplace personne** (task API-035). Le rattachement d'un profil à un métier est
calculé à l'ingestion et figé dans `candidate_job_experience` : corriger `taxonomy.json` puis le
charger laisse les profils là où ils étaient, et un concept nouvellement créé reste sans personne —
donc invisible à l'auto-complétion, qui ne propose que ce qu'au moins un profil exerce. C'est
`reproject` qui ferme la boucle. Elle lit `candidate_jarvi.work_experiences`, où les positions sont
conservées dans la forme même que reçoit l'ingestion, et ne recalcule que les deux projections dont
le dictionnaire décide : les métiers exercés et le concept du poste actuel. L'intitulé, l'employeur,
la certitude et l'ancienneté restent tels que l'ingestion les a posés.

**Le fichier fait autorité sur l'appartenance d'un libellé, pas seulement sur son ajout.** Un libellé
qu'un concept **décrit par le fichier** ne revendique plus est retiré en suppression douce, et rendu
si le fichier le réclame de nouveau — sur sa ligne d'origine, sans doublon. Un concept **absent** du
fichier, lui, garde ses libellés intacts : le silence ne vaut pas retrait, une taxonomie tronquée ne
doit pas vider la base. Le retrait passe par `deleted_at` et non par le statut `DEPRECIE`, parce que
la reconnaissance d'intitulé à l'ingestion ne regarde pas le statut : un libellé seulement déprécié
continuerait de rattacher des profils au concept dont on vient de le retirer.

### Chaîne C — Secteurs (entreprises)

| Commande | Rôle | LLM | Options |
| --- | --- | :---: | --- |
| `app:company:sectors:classify` | Classe les entreprises dans un secteur → `sectors.json` | **oui** | `--file` `--dry-run` |
| `app:company:sectors:import` | Charge `sectors.json` en base | non | `--file` `--dry-run` |

### Chaîne D — Écoles (établissements)

| Commande | Rôle | LLM | Options |
| --- | --- | :---: | --- |
| `app:school:harvest` | Récolte les établissements distincts, regroupés par nom normalisé | non | `--output` |
| `app:school:group` | Regroupe les écoles par nom canonique — **DÉSACTIVÉE** (`SCHOOL_GROUPING_ENABLED`) | **oui** | `--file` `--dry-run` |

### Reporting

| Commande | Rôle | LLM | Options |
| --- | --- | :---: | --- |
| `app:qualification:report` | État chiffré de la qualification, et **écart avant/après** traitement | non | `--output` `--compare` |

#### Pourquoi une qualification manque

Le rapport compte les manques ; il ne les explique pas, et **la cause n'est écrite dans aucune
colonne** — les colonnes dérivées gardent le résultat (`0`, `null`), jamais la raison. Elle est donc
rejouée à la demande par `GET /v1/admin/vivier/gaps/{metric}`, sur les seuls profils déjà comptés
comme en manque. Rien n'est stocké : un motif figé en base deviendrait faux à la première évolution
d'un calcul, sans que rien ne le signale.

Chaque motif est le miroir d'une branche du calcul, et **deux d'entre eux ne sont pas des défauts** :

| Qualification | Motifs | Rendu par |
| --- | --- | --- |
| Ancienneté | `NO_POSITION` · `NO_USABLE_START_DATE` · `INCOHERENT_DATES` · **`UNDER_ONE_YEAR`** | `ExperienceYearsCalculator` |
| Durée sur le poste actuel | `NO_ONGOING_POSITION` · `ONGOING_WITHOUT_START_DATE` · **`UNDER_ONE_MONTH`** | `CurrentPositionExtractor` |
| Niveau d'études | `NO_EDUCATION` · `UNRECOGNIZED_TITLE` | `EducationLevelCalculator` |
| Date de modification source | `SOURCE_WITHOUT_DATE` | ingestion |

**`intdiv($months, 12)` rend zéro pour onze mois d'expérience réelle.** Le compteur range donc les
débutants avec les profils cassés, et le manque affiché surestime le travail à faire — mesuré sur le
vivier : 202 des 340 profils « sans ancienneté » ont simplement moins d'un an de carrière. Même
mécanique sur le poste actuel, où un poste pris il y a trois semaines mesure zéro mois. C'est cette
distinction que les motifs existent pour rendre lisible ; le compteur, lui, ne bouge pas — l'écran et
le rapport en ligne de commande doivent continuer d'annoncer le même nombre.

### Hors périmètre données

| Commande | Rôle | Options |
| --- | --- | --- |
| `app:accounts:seed-team` | Comptes de recette (candidat / entreprise / admin) | `--password` `--credits` `--force` |
| `app:searches:seed-demo` | Grille de recherches de démonstration du cockpit entreprise, sur le vivier présent | `--recruiter` `--force` |
| `app:candidates:consent-demo` | Rend le vivier exposable sur un environnement de démonstration | `--revoke` `--force` |

`app:searches:seed-demo` **suppose le vivier déjà chargé et exposable**. Elle ne crée aucun profil :
elle pose des recherches et laisse le recalcul d'appartenance produire leur jeu de résultats. Deux
manques donnent le même écran — une grille de cartes annonçant toutes zéro profil :

- **le vivier n'est pas chargé** : c'est A qu'il faut avoir passé avant, pas elle qu'il faut
  relancer ;
- **le vivier est chargé mais fermé** : un profil importé de Jarvi naît **sans consentement**, et la
  recherche n'expose qu'un profil visible, actif *et* consenti. Sur un environnement de démonstration,
  `app:candidates:consent-demo` lève cette barrière en posant la source `DEMONSTRATION` — jamais celle
  d'un parcours candidat —, et `--revoke` referme exactement ce qu'elle a ouvert, sans toucher aux
  consentements réels. Relancer `app:searches:seed-demo` ensuite recalcule les jeux.

## Ordre d'exécution

**L'ordre n'est pas libre : C avant A.** L'ingestion rattache chaque poste à un secteur en lisant le
référentiel produit par C. Si C n'a pas tourné, la ré-ingestion ne trouve rien à rattacher et
l'expérience par secteur reste vide.

Remise en état d'un environnement :

```bash
make dev-api CMD="app:company:sectors:import"         # C — instantané, sans LLM
make dev-api CMD="app:vocabulary:import"              # B — instantané, sans LLM
make dev-api CMD="app:jarvi:profiles:import --full"   # A — recalcule toutes les qualifications
make dev-api CMD="app:vocabulary:similarity"          # B — après A, a besoin des rattachements
```

Quand **seul le dictionnaire** a changé, la ré-ingestion complète est inutile : `app:vocabulary:import`
puis `app:vocabulary:reproject` suffisent, et la seconde travaille depuis la réplica locale, sans clé
Jarvi ni appel réseau.

**Séquence complète et vérification de l'effet** : `dev-book/runbooks/traitements-profils.md`.

**Trois commandes utilisent le LLM** (`vocabulary:enrich`, `sectors:classify`, `school:group`) :
rares, manuelles, exigeant `ANTHROPIC_API_KEY` — et `school:group` est de surcroît **désactivée par
défaut**. Les douze autres sont déterministes, gratuites et rejouables sans risque.

## Invariants

- **Le LLM ne tourne que dans les trois commandes citées** — rare et manuel. Le pipeline courant ne
  dépend d'aucun service externe payant.
- **Tout ce qui est produit entre en statut `candidat`** — proposé, jamais validé automatiquement.
  La promotion `candidat → validé` reste une décision humaine.
- **`import` est idempotent** : un concept par `(type, pref_label)`, un libellé par
  `(concept, label, lang)`.
- **La réplica `candidate_jarvi` est en lecture seule** — le vocabulaire s'en déduit, ne l'écrit
  jamais.
- **`enrich` n'écrit que `taxonomy.json`**, jamais la base : sa sortie est relue en `git diff` avant
  `import`.

### Règle de conception : une commande LLM doit être exécutable À LA MAIN

La clé n'est débloquée qu'en production. Aucun traitement LLM n'attend pour autant : **le résultat
que la commande produirait est produit à la main**, et l'artefact est versionné. En production, la
commande ne traitera plus que les **entrées nouvelles** — le stock est déjà fait.

Cela impose trois propriétés à toute commande LLM du projet :

1. elle écrit un **fichier versionné** (`sectors.json`, `taxonomy.json`, `schools.json`), **jamais la
   base directement** — sinon le remplissage manuel est impossible ;
2. elle est **incrémentale** : elle lit ce fichier et ne soumet que ce qui manque, donc le travail
   manuel n'est ni re-soumis ni écrasé ;
3. son résultat entre en statut `candidat`, qu'il vienne du modèle ou de la main — aucun privilège.

Déjà produit ainsi : **1 207 entreprises** classées (`sectors.json`) et **860 concepts / 3 358
libellés** (`taxonomy.json`), sans un seul appel API.

## Modèle de données du vocabulaire

Le sens et les mots sont séparés (cf. [data-model.md](./data-model.md)) :

- **`vocabulary_concept`** — l'idée unique (`pref_label`, `type` = compétence/métier, `frequency`,
  `rome_code`/`esco_uri` optionnels, `status`).
- **`vocabulary_label`** — tous les libellés qui la désignent (`label`, `lang`, `source`,
  `frequency`, `status`, `reviewed_by`/`reviewed_at`). La synonymie = plusieurs libellés sur un même
  concept. Le `status` se pilote **au libellé** : un admin peut en **déprécier** un précis pour
  l'exclure de l'élargissement (task 124, réversible et tracé), sans toucher au concept. La décision
  survit à la réingestion (l'`import` upsert par `(concept, label, lang)` et ne touche que la
  fréquence).

### Fichier `taxonomy.json` & rafraîchissement

`api/data/vocabulary/taxonomy.json` est **committé** et **baké dans l'image** de prod/preprod
(`COPY . .`). Conséquence : `import` charge la version **de l'image**, pas un fichier local. Pour
rafraîchir hors dev : **committer le nouveau `taxonomy.json` → rebuild → redéploiement → import**.

### Configuration (`enrich`)

Clé et réglages côté `api/` uniquement (jamais exposés au webapp), défauts vides pour un démarrage
sans clé :

| Variable | Rôle |
| -------- | ---- |
| `ANTHROPIC_API_KEY` | Clé privée, dans `api/.env.dev` (local) — jamais committée |
| `ANTHROPIC_VOCABULARY_MODEL` | Modèle (défaut `claude-opus-4-8`) |
| `VOCABULARY_ENRICH_MIN_FREQUENCY` | Seuil de fréquence sous lequel un terme n'est pas soumis (défaut 3) |
| `VOCABULARY_ENRICH_BATCH_SIZE` | Taille de lot envoyé au modèle (défaut 80) |

## Limites à connaître

- **Rien n'est validé par un humain.** Tout l'enrichissement est en statut `candidat` : bon pour
  élargir une recherche, impropre à un filtre strict tant que `113` n'existe pas.
- **La longue traîne échappe au dictionnaire, mais plus à la recherche** (`API-037`). Des milliers de
  compétences, d'entreprises et d'intitulés de poste n'apparaissent qu'une fois — 5 439 intitulés sur
  6 480 non classés —, et aucun rangement du dictionnaire ne les rattrapera. Ils restent donc hors de
  l'**élargissement** : chercher l'un d'eux ne retrouvera ni ses synonymes ni sa traduction. Ils ne
  sont plus pour autant **hors d'atteinte** : les intitulés de poste sont projetés tels qu'écrits
  dans `candidate_job_title`, et la recherche s'y replie quand le dictionnaire ne connaît pas le
  terme. Mesuré : 260 profils visibles ne portent aucun métier reconnu et n'entrent dans le critère
  que par ce chemin.
- **La normalisation reste éparpillée hors vocabulaire.** Le vocabulaire partage désormais une seule
  règle (`094a`), mais deux implémentations — une PHP, une SQL — doivent toujours produire la même
  clé d'entreprise sans garantie de rester alignées. Les champs candidat, eux, ne sont pas
  normalisés du tout : c'est l'objet de `094`.
- **Un profil sans dates reste muet** : aucune durée n'est calculable, et on préfère ne rien afficher
  qu'un chiffre faux. Même règle pour le **niveau de langue**, absent sur un tiers des entrées : le
  filtre « niveau minimum » les exclut plutôt que de les supposer.
- **Une durée dérivée ne vaut que ce que le dictionnaire rattache.** L'expérience par métier ne voit
  que 62 % du parcours médian, celle par secteur 50 % : la **présence** est fiable, la **durée**
  sous-estime. Ne jamais en faire un filtre « au moins N années ».
- **Une date de source ne vaut pas une fraîcheur.** Mesuré sur l'API Jarvi : `updatedAt`, `addedAt`,
  `createdAt` **et `lastResumeUpdatedAt`** sont remplis à 100 % mais portent tous, à ~83 %, la date
  d'un même import de masse. Le nom d'un champ ne dit pas ce qu'il contient — `lastResumeUpdatedAt`
  promettait la fraîcheur et livre un batch. Le seul signal honnête est `lastInteractionDate` (6 %,
  étalé), parce que **la fraîcheur n'existe que pour un candidat qui a fait quelque chose** : 98 % du
  vivier vient du sourcing LinkedIn passif.
- **Un identifiant source ne vaut pas une clé de regroupement — et un nom normalisé non plus.**
  Mesuré sur les écoles : l'identifiant Jarvi donne 2 465 valeurs pour 2 444 noms (il fragmente), et
  la normalisation ne descend qu'à 2 366 (−3,2 %). Les doublons qui restent sont **sémantiques**
  (« ENCG Casablanca » = « École Nationale de Commerce et de Gestion de Casablanca ») : aucune
  comparaison de chaînes ne les rapprochera. À vérifier dimension par dimension avant de coder.

## Ce qui reste à faire

- **Exposition du vocabulaire** : aucune route API ne le lit encore ; il ne pilote pas la recherche.
- **Promotion `candidat → validé`** : écran admin `/referentiels` (task 120) au niveau concept,
  dépréciation au niveau libellé (task 124). Reste l'expansion qui consomme ces statuts, et la
  **relecture** elle-même (13 249 objets).
- **Couverture de la traîne** : les concepts couvrent la tête ; la traîne rare reste au filet texte
  brut.
- **Référentiel des écoles** : 2 366 récoltés, regroupement LLM prêt mais **verrouillé** — doublons
  sémantiques et valeur produit à confirmer (task 115).

Décisions et suite : `dev-book/tasks/096` (référentiel & couche sémantique) et
`dev-book/tasks/103` (pipeline vocabulaire).
