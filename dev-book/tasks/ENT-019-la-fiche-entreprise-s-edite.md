# TASK ENT-019 — entreprise : la fiche entreprise s'édite

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : `ENT-003` (les quatre rubriques du compte) · `ENT-018` (l'équipe, même coque).

## Contexte

Dix lignes : un titre, une phrase. Pourtant `GET /v1/organizations/{id}` sert la fiche complète et
`PUT` l'écrit — c'est la seule des quatre rubriques dont l'API couvre déjà l'écriture.

## Décisions

### Ce que l'écran modifie, et ce qu'il montre sans y toucher

**Modifiables** : raison sociale, dénomination légale, identifiant fiscal, pays, ville, adresse de
facturation, site web, secteur, description.

**En lecture seule** : le statut de validation, et la provenance — « comment nous avez-vous connus »
est **figée à l'inscription**, le `PUT` l'ignore délibérément. La montrer sans permettre de la changer
serait un piège ; l'écran ne la montre donc pas du tout.

### L'API le dit déjà : identité et provenance ne sont exigées qu'à la création

`givenName`, `familyName` et `acquisitionChannel` portent `NotBlank(groups: ['create'])`. Le
formulaire d'édition n'a donc **rien à renvoyer d'une identité personnelle** pour satisfaire une
validation — ce qui aurait été absurde sur un écran qui parle de l'entreprise.

### Seul le propriétaire écrit

Comme pour l'équipe. Un membre voit la fiche ; il ne la modifie pas.

### Le secteur reste une liste fermée

`Assert\Choice` sur le référentiel côté serveur : le champ propose, il n'invente pas. La liste vient
du palier métadonnées déjà chargé (`ENT-010`).

## Critères d'acceptation

- [x] La fiche s'affiche remplie de ce que l'API sert.
- [x] Le propriétaire modifie et enregistre ; l'écran confirme.
- [x] Un membre voit la fiche sans pouvoir l'enregistrer.
- [x] Le refus de l'API (URL invalide, secteur inconnu) s'affiche sans faire perdre la saisie.

## Vérifié en réel

```
secteur hors référentiel → 422 « Secteur inconnu. »
secteur légal            → 200 · Atlas Talent SARL · MA-4421 · Rabat · Automobile et aéronautique
écran rendu              → tous les champs remplis de la réponse
```

## Une règle du projet que j'avais enfreinte

`pages-no-interactive-template` : **une page orchestre, le code interactif vit dans un composant.**
Le formulaire est donc extrait en `AccountCompanyForm`, et la page se contente de lire la fiche, de
la lui donner, et de renvoyer ce qu'il émet.

`AccountTeamInviteForm` suit le même chemin : `ENT-018` avait laissé son formulaire dans la page, et
le verrou ne l'avait pas vu — voir ci-dessous.

### Le verrou a un angle mort, et il est large

`verify-conventions` isole la région `template` avec une regex **non gourmande** : elle s'arrête au
**premier** `</template>`. Toute page qui utilise un `<template v-if>` ou `<template #slot>` avant la
fin n'est donc lue qu'en partie.

**22 pages sur 33** sont tronquées, et six portent des interdits que le verrou n'a jamais signalés :

```
base/app/pages/auth/define-password.vue          v-model, <form, @submit
base/app/pages/ux-kit.vue                        v-model
spaces/entreprise/app/pages/l-equipe.vue         v-model, <form, @submit
spaces/entreprise/app/pages/recherches/[uuid]/index.vue   v-model
spaces/entreprise/app/pages/recherches/[uuid]/table.vue   v-model
spaces/admin/app/pages/referentiels.vue          v-model
```

Rendre la regex gourmande est une ligne — mais elle ferait rougir ces six pages d'un coup. C'est donc
une carte `OPS` avec du travail derrière : corriger le verrou ET extraire les formulaires qu'il
révélera.
