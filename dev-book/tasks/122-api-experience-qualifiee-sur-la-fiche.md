# TASK 122 — API : exposer l'expérience qualifiée sur la fiche candidat

- **Créée le** : 2026-07-25.
- **Statut** : ✅ fait le 2026-07-25.
- **Type** : API — DTO de sortie (aucun calcul, la donnée existe).
- **Lié à** : `095` (producteur de l'expérience métier/secteur), `117` (poste actuel), `061`/`062`
  (filtres), `119` (l'écran qui montrera les agrégats, pas le profil).

## Contexte

L'ingestion produit **4 163 lignes d'expérience métier** et **3 022 lignes d'expérience secteur**.
Elles sont **filtrables** — `?job=Data Scientist`, `?experienceSector=INFORMATIQUE` — mais
**illisibles** : aucune route ne les renvoie.

Concrètement, un recruteur peut trouver un profil par son métier, puis ouvre sa fiche et **n'y voit
pas ce métier**. La donnée est produite, indexée, jamais montrée.

C'est le pendant profil de `119` : cette carte traite ce que porte **un candidat**, `119` ce que
contient **le vivier**.

## Objectif

Rendre lisibles, sur la fiche candidat, les dimensions déjà calculées : métiers exercés, secteurs
d'expérience, langues avec niveau.

## Périmètre

- `CandidateOutput` (détail) enrichi de :
  - **métiers exercés** — libellé du concept, mois cumulés, nombre de postes ayant alimenté la ligne ;
  - **secteurs d'expérience** — secteur, mois cumulés ;
  - les **langues** sont déjà exposées, mais brutes (`languages` JSON Jarvi) : les remplacer par la
    table normalisée `candidate_language` (code + niveau), seule filtrable et seule fiable.
- Aucun calcul : lecture des tables dérivées peuplées à l'ingestion.

## Ce qui doit accompagner le chiffre

**La durée seule ment.** `095` l'a mesuré : le rattachement des intitulés est partiel, un candidat
couvert ne l'est que sur **62 % de son parcours en médiane**. Donc :

- la **présence** d'un métier est fiable — l'erreur est en moins, jamais en trop ;
- la **durée** sous-estime et doit être présentée comme indicative ;
- `positions` et `exactPositions` accompagnent la ligne : combien de postes l'ont alimentée, et
  combien ont été reconnus à l'identique. Sans eux, rien ne distingue un métier vu sur 5 postes
  d'un métier déduit d'un seul rattachement partiel (faux dans ~7 % des cas, cf. `118`).

## Masquage

Ces dimensions **décrivent sans nommer** : « 3 ans en informatique » ne désigne personne. Elles
suivent donc la règle des agrégats (années d'expérience, ville) et sont visibles **même sur un
profil masqué** — c'est d'ailleurs là qu'elles servent, pour décider d'un dévoilement.

À ne pas confondre avec le **nom de l'employeur** (`117`), qui lui désigne et reste masqué.

## Hors périmètre

- **La carte de recherche** : `items` est un tableau non typé dans le contrat OpenAPI — lacune
  préexistante. L'enrichir demande de typer la collection, ce qui déborde cette carte.
- Toute pondération ou score de correspondance : c'est `096`, et la décision produit n'est pas prise.

## Critères d'acceptation

- [x] Le détail candidat renvoie métiers, secteurs et langues normalisées.
- [x] Chaque métier porte sa durée **et** le nombre de postes qui l'ont alimentée, dont ceux
      reconnus à l'identique.
- [x] Visible sur un profil masqué (test), l'employeur restant masqué.
- [x] `openapi.json` régénéré **et** types du front dans le même commit.
- [x] `api-quality` vert.

Vérifié sur un profil réel du vivier, masqué :

```
métiers   : Data Analyst (9 mois, 2 postes dont 1 exact) · Développeur logiciel · Assistant administratif
secteurs  : TRANSPORT_LOGISTIQUE 5 · INDUSTRIE 4 · PUBLIC_ADMINISTRATION 2 · AUTRE 2
langues   : fr (niveau non renseigné)
employeur : null  ← reste masqué
```

`Assistant administratif` sort avec `exactPositions: 0` : c'est un rattachement **partiel**, et le
chiffre le dit — exactement ce que la carte voulait rendre visible.

## Limite constatée : le contrat ne décrit pas la forme des tableaux

Les types TS générés donnent `jobExperiences?: (string | null)[]` au lieu de la structure réelle.
API Platform ne lit pas les annotations `list<array{...}>` du phpdoc pour les propriétés typées
`array`.

**Ce n'est pas propre à cette carte** : `workExperiences` et `presencePeriods` portent le même
typage depuis toujours. Le front doit donc caster à la main, ici comme ailleurs.

Le corriger demanderait de remplacer les tableaux associatifs par des DTO imbriqués sur **tout**
`CandidateOutput` — un changement de convention qui déborde largement, et qui touche le contrat de
champs existants. À traiter comme un chantier propre si la friction devient réelle côté front.

## Points de vigilance

- **Ne pas recalculer** : lire `candidate_job_experience` / `candidate_sector_experience`, jamais
  re-scanner les `workExperiences` JSON.
- **Ordonner par durée décroissante** : un profil peut porter 6 métiers, les premiers doivent être
  les plus significatifs.
- **Le libellé d'un concept peut changer** (`113`) : exposer le libellé courant, jamais le figer côté
  client comme identifiant.
