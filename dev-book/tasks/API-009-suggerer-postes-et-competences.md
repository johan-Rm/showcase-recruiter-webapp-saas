# TASK API-009 — API : suggérer les intitulés de poste et les compétences

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 (préfixe seul) — 4 tests, 25 assertions.
- **Type** : API — nouvelle surface.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28 · mesures sur le vivier de dev
  (2 561 profils).

## Contexte

Le formulaire de recherche doit proposer des suggestions sur l'**intitulé de poste** et sur les
**mots-clés**, avec tolérance aux fautes et ouverture à la similarité. **Aucune route ne l'expose.**

Le vocabulaire existe pourtant : **860 concepts** (702 `COMPETENCE`, 158 `METIER`), **3 358
libellés** (fr 1 883, en 1 419, nl 53), **8 129 similarités**.

### CORRECTION — `pg_trgm` n'est PAS installé

Cette carte l'affirmait, en s'appuyant sur une décision de cadrage de juillet. Vérification faite :
la seule extension présente sur les bases du projet est `plpgsql`, et le dépôt ne mentionne
`pg_trgm` nulle part. **La recherche floue décidée n'a jamais été construite.**

Livré ici : le **préfixe seul**, qui n'a besoin de rien. La tolérance aux fautes et la similarité
demandent l'extension, ses index, et leur reproductibilité sur les trois environnements — une tâche
d'infrastructure à part entière, encore à faire. Ce qui est en place n'aura pas à être défait.

## Décisions

### La source est le vivier, adossée au vocabulaire

Deux fausses pistes ont été écartées, mesures à l'appui.

**« Les compétences qualifiées » est un ensemble vide** : les 860 concepts et 3 358 libellés sont
**tous au statut `CANDIDAT`**, aucun `VALIDE` — rien n'a jamais été relu. Une suggestion branchée là
ne proposerait rien.

**« Tout le texte du profil » n'existe pas non plus** : le `summary` n'est renseigné que sur **39
profils sur 2 561 (1,5 %)**. « Tout le profil » se réduit en pratique à `headline` + `skills`, et il
n'existe **aucun index plein texte** dans la base.

La source retenue est donc celle des **compétences réellement portées par les profils**, projetées
sur le vocabulaire `COMPETENCE`. Deux raisons :

- **aucune suggestion ne peut ramener zéro résultat** — c'est le pire échec d'une auto-complétion, et
  il devient impossible par construction ;
- le vocabulaire fond les variantes : « gestion de projet » (708 profils) et « project management »
  (362) deviennent **un concept à 1 070**, et ses libellés portent la tolérance orthographique.

Sa couverture est bonne là où ça compte : 18 818 chaînes distinctes dans les profils, dont 2 000
reconnues (10,6 %) — mais **51,7 % des occurrences**. Le vocabulaire a été bâti sur les termes
fréquents : il rate la longue traîne et couvre l'essentiel du volume. Les compétences non reconnues
restent proposables telles quelles, simplement sans regroupement bilingue.

### Chaque suggestion annonce ce qu'elle ramène

La tête de distribution est occupée par des compétences qui ne discriminent rien : `communication`
sur **717 profils (28 % du vivier)**, `microsoft office` 625, `management` 623. Classer par fréquence
brute mettrait donc en avant les filtres les plus inutiles.

Servir le **nombre de profils** avec chaque suggestion suffit à le rendre visible : le recruteur voit
qu'il s'apprête à filtrer sur un quart du vivier, et choisit en connaissance de cause.

### Une seule route, deux sources

`GET /v1/suggestions?type=JOB|SKILL&q=…`, réservée aux recruteurs d'une organisation validée : les
propositions décrivent le vivier, elles ne sont pas publiques.

**Poste** → le dictionnaire de concepts, qui porte l'élargissement ; la proposition est le libellé
**canonique**, quelle que soit la variante tapée. Les langues se fondent donc naturellement.

**Mots-clés** → les compétences réellement portées par les profils. Elles ne sont **pas** rattachées
à des concepts côté candidat (seuls les postes le sont), donc pas de fusion bilingue ici : « gestion
de projet » et « project management » restent deux propositions. La carte l'annonçait comme acquis,
la donnée dit non.

Un terme de moins de deux caractères ne propose rien : sur une lettre, une liste n'aide pas, elle
occupe l'écran. Et `%` ou `_` tapés au clavier sont échappés — une saisie n'est pas un opérateur.

## Vérification

- Une compétence proposée annonce son nombre de profils ; mesuré sur le vivier de dev, `ges` rend
  `gestion de projet` **704**, `gestion d'équipe` **256**, `gestion du temps` **93**.
- Un poste cherché par son synonyme anglais est proposé sous son **libellé canonique**.
- **Un concept que personne n'exerce n'est jamais proposé** — aucune suggestion ne peut ramener zéro.
- `SuggestionTest` : **4 tests, 25 assertions**.

## Reste à faire (hors périmètre de cette tâche)

- **Tolérance aux fautes et similarité** : activer `pg_trgm`, poser les index, rendre l'opération
  reproductible sur dev, preprod et prod.
- **Fusion bilingue des compétences** : elle suppose de rattacher les compétences des profils aux
  concepts, ce que l'ingestion ne fait pas aujourd'hui.
- **Le parcours complet du JSON** des compétences à chaque frappe : tenable sur 2 561 profils, à
  reprendre avec l'indexation.
