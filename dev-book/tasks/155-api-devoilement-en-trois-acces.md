# TASK 155 — API : dévoiler en trois accès, facturés séparément

> Numérotée `152` au moment du commit, renumérotée en `155` : une autre lane avait pris
> le même numéro en parallèle. Les messages de commit mergés citent donc encore « task 155 ».

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — contrat régénéré, types du front en phase.
- **Type** : API — modèle, crédits et surface de contrat.
- **Réf** : `dev-book/cadrage/entreprise/api/credits_et_devoilement.md`, task 154 (l'export).

## Contexte

Dévoiler était **tout ou rien** : un crédit ouvrait l'identité, les coordonnées, le CV et le compte
rendu d'entretien. Or dans la Table on ne vient pas chercher la même chose selon le moment — joindre
la personne, lire son parcours, ou lire ce que showcase-recruiter-webapp-saas a constaté en entretien.

## Décisions

### Trois accès indépendants, un crédit chacun

`RevealType` : `CONTACT`, `RESUME`, `INTERVIEW_REPORT`. Payer l'un ne donne **rien** sur les autres —
pas de dépendance, pas d'ordre imposé. L'unicité et l'idempotence portent donc sur
`(organisation, candidat, type)`, et le débit aussi : deux accès sur le même candidat coûtent deux
crédits.

Le tarif est uniforme **pour le moment** : `CreditPolicy::REVEAL_COST` reste partagé. Le jour où il
diffèrera par accès, c'est ce point-là qu'il faudra ouvrir, pas le modèle.

### Le type est obligatoire et sans défaut

`POST /revealed-profiles` refuse (422) une demande sans type. Dévoiler coûte un crédit : l'appelant
doit dire lequel des trois il paie, plutôt que d'en hériter un par omission — un défaut silencieux se
serait facturé à la place de l'utilisateur.

### Le masquage suit ce que chaque accès achète

| Accès | Ce qu'il ouvre |
| --- | --- |
| `CONTACT` | identité, photo, e-mail, téléphone, WhatsApp, LinkedIn, **employeurs** du parcours |
| `RESUME` | le CV déposé |
| `INTERVIEW_REPORT` | le compte rendu d'entretien showcase-recruiter-webapp-saas |

L'identité va avec les coordonnées : on ne joint pas quelqu'un sans savoir qui c'est, et servir un nom
sans moyen de le contacter n'a aucune valeur d'usage.

`RevealedAccess` porte les trois booléens plutôt que de les balader de signature en signature : le
masquage se lit partout de la même façon (`$access->contact`), et un quatrième accès ne réécrirait pas
les appels un par un.

### Le contrat expose les trois

`revealed` devient `revealedContact`, `revealedResume`, `revealedReport` — sur la carte comme sur la
fiche. Un booléen unique ne pouvait plus dire ce qui est ouvert, et l'UI a besoin de savoir quelle
porte proposer.

### L'export exige les COORDONNÉES

Un CSV sans moyen de contact ne sert à rien : `SearchExportHandler` vérifie `CONTACT`, pas « un accès
quelconque ». Le CV et le compte rendu ne sortent pas en CSV de toute façon.

## Vérification

`RevealTest` (dont deux tests neufs : facturation séparée, refus sans type), `CandidateTest` — qui
prouve désormais l'indépendance : coordonnées payées, CV toujours fermé, puis ouvert au second
crédit. Suite complète, phpstan, php-cs-fixer, deptrac et `lint:container` verts. `api/openapi.json`
et `webapp/shared/types/api.ts` régénérés dans le même commit.

**Piège rencontré deux fois** : le cache de métadonnées d'API Platform garde l'ancien contrat après
l'ajout d'une propriété — le champ manque à la réponse ET à l'export OpenAPI. `rm -rf var/cache/<env>`
avant de conclure quoi que ce soit.

## Reste à faire (hors périmètre de cette tâche)

- **Front** : trois actions de dévoilement dans la Table au lieu d'une, et la modale de coût par
  accès. Les vues « Dévoilés / Non dévoilés » doivent choisir ce qu'elles comptent — `RevealedAccess`
  expose `any()` pour ça.
- **Schéma** : la contrainte d'unicité change (`+ type`). Dev et preprod se recréent ; la prod, quand
  elle existera, demandera une migration.
- Le **plafond de lot** du dévoilement en masse reste à fixer.
