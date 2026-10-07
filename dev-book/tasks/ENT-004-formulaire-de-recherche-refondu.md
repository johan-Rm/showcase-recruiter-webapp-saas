# TASK ENT-004 — entreprise : le formulaire de recherche refondu

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Dépend de** : `API-009` (suggestions) · `API-010` (filtre d'expérience) · `API-011` (mots-clés).
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

Les quatre champs sont posés dans un ordre qui ne suit pas la façon dont un recruteur formule sa
recherche, et trois d'entre eux n'ont pas la forme qui convient à ce qu'ils demandent.

## Décisions

### L'ordre change

| Rang | Champ | Forme |
| --- | --- | --- |
| 1 | Intitulé de poste | saisie avec **suggestions** |
| 2 | Années d'expérience | **curseur**, facultatif |
| 3 | Niveau d'études | **tags cliquables**, un seul choix |
| 4 | Mots-clés | saisie avec suggestions, **plusieurs mots**, reliés en **OU** |

Les mots-clés passent en dernier : c'est le champ le plus libre, donc le moins structurant.

### Le curseur n'a pas d'état vide à construire

**`0` signifie « toutes les expériences »**. La borne basse porte le « peu importe », ce qui évite
d'inventer un état neutre à un composant qui n'en a pas naturellement.

### Les tags se déselectionnent

Un second clic sur le tag actif le retire. Sans cela, un critère posé par erreur ne peut plus être
enlevé.

### Chaque suggestion annonce ce qu'elle ramène

Le nombre de profils s'affiche en face. Sans lui, le recruteur choisirait `communication` — **717
profils, 28 % du vivier** — en croyant poser un filtre.

### Ce que l'aide des champs doit dire

L'aide actuelle des mots-clés promet « une recherche libre sur l'ensemble du profil », ce qui n'a
jamais été vrai. Elle doit décrire le comportement réel une fois `API-011` livrée, y compris le fait
que plusieurs mots **élargissent** le résultat.

## Critères d'acceptation

- [x] Les quatre champs dans le nouvel ordre, chacun dans sa forme.
- [x] Une suggestion choisie pose bien le critère correspondant.
- [x] Le curseur à `0` ne restreint pas le jeu — il ne pose aucun critère.
- [x] Les tags se posent et se retirent au clavier comme à la souris.
- [x] Les aides décrivent ce que le moteur fait réellement.

## Mise en œuvre

Le formulaire passe des composants maison (`UiInlineInput`, `UiInlineSelect`) à ceux de Nuxt UI pour
les trois champs dont la forme change : `UInputMenu` pour les deux saisies à suggestions, `USlider`
pour l'expérience. Écrire à la main une liste déroulante accessible aurait redemandé les deux cents
lignes de `PlacesAutocomplete` — clavier, `aria-activedescendant`, panneau flottant — pour un
résultat que la bibliothèque déjà installée donne.

Le contrat du formulaire change avec lui : `keywords` devient une LISTE et `experienceRange` (un code
de plage jamais transmis, faute de facette côté moteur) devient `experienceMin`, un entier que le
moteur comprend depuis `API-010`.

`fromAiCriteria` en profite : les mots-clés proposés par le modèle partaient JOINTS en une chaîne, du
temps où le moteur n'en gardait qu'un. « react vue » se cherchait alors comme une expression et ne
trouvait personne. Ils partent maintenant un par un, comme ceux du formulaire.

## Limites connues

**L'auto-complétion ne tolère PAS les fautes, et pas davantage les accents.** Le critère annonçait
« tolérante aux fautes et ouverte à la similarité » : ce n'est pas livré, et la tolérance aux accents
manque aussi. Mesuré sur données réelles :

```
JOB q=dev   → []                                (rien)
JOB q=dév   → [('Développeur logiciel', 1)]     (trouvé)
```

La cause est côté API : `SuggestionFinder` compare en `LIKE 'terme%'`, sensible aux accents. La
corriger demande `unaccent` (ou les index trigrammes d'`API-013`) et une comparaison normalisée — une
carte côté API. En l'état, un recruteur qui tape sans accent ne voit aucune proposition sur un
intitulé qui en porte un, ce qui est le cas de la plupart des intitulés français.

**Deux systèmes visuels cohabitent dans la carte.** Les champs Nuxt UI (encadrés) voisinent avec les
champs maison (filet souligné). C'est visible, et c'est à la passe de design de trancher — soit en
portant le style maison sur les composants Nuxt UI, soit en basculant la carte entière.

**Les suggestions d'intitulé ne proposent que des concepts qu'un profil porte réellement** — une
suggestion qui ramène zéro est le pire échec d'une auto-complétion. Sur la base de recette du
worktree, aucun profil n'est rattaché à un concept métier : le champ ne propose donc rien, et c'est
le comportement attendu, pas une panne.
