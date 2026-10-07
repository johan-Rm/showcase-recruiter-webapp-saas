# TASK ENT-037 — entreprise : le front suit le dévoilement par canal

- **Créée le** : 2026-08-01.
- **Session** : `57a6103a-e57e-429e-82e9-aed454da48f3`.
- **Statut** : ✅ fait le 2026-08-02 — le front lit le contrat par canal, et chaque coordonnée est
  devenue son propre geste d'achat. **Réserve inscrite** : la preuve à l'écran a été reportée sur
  décision explicite, voir *Vérification*.
- **Type** : Frontend espace entreprise — remise en phase sur le contrat, et geste d'achat par canal.
- **Tests** : la règle « quel accès couvre quel champ » sort de `useReveal` vers `services/`, où la
  suite front la ramasse (`webapp/vitest.config.ts` ne couvre que du code **pur**) : quatre canaux,
  une fonction pure, des tests qui attrapent une inversion qu'aucune capture ne verrait. Précédent :
  `ENT-028` avec `describeDecision`. S'y ajoutent des captures dans les deux thèmes, sur un profil
  masqué et un profil dont **un seul** canal est payé.
- **Réf** : **amont `API-018`** (le contrat, sans lequel rien ne se régénère) · `ENT-029` (la carte
  profil en deux colonnes) · `ENT-013` (le dévoilement comme geste) ·
  [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md) ·
  [base/app/README.md](../../webapp/base/app/README.md) — « Données prêtes pour le template »

## Contexte

`API-018` a supprimé `revealedContact` du contrat : l'identité est gratuite, et l'e-mail et le
téléphone s'achètent **séparément**, un crédit chacun.

**Le front lit encore l'ancien contrat**, à quatre endroits — le `switch` de `useReveal.ts:52`, le
compteur de `useSearch.ts:365`, la carte profil et la modale de lot de la Table.

**Et deux de ces endroits ne crieront pas.** `shared/types/api.ts` est **généré** depuis
`openapi.json` et la gate de dérive le compare ; mais `shared/types/candidate-card.ts` et
`shared/types/reveal.ts` sont des **miroirs écrits à la main** que rien ne confronte au contrat réel.
Le type dira `revealedContact: boolean` sur une charge utile qui ne le porte plus : pas d'erreur, pas
d'alerte — un écran qui affiche mal l'état de dévoilement, et personne pour le signaler.

**Enfin le geste manque.** La colonne de gauche annonce « E-mail disponible » sans permettre de
l'acheter : il faut passer par un bouton « Coordonnées » qui achetait les deux canaux d'un bloc, et
qui n'a plus d'équivalent côté serveur.

## Périmètre

- **Les deux miroirs manuels** : `revealedEmail` / `revealedPhone` à la place de `revealedContact`,
  et l'union `RevealType` à quatre valeurs.
- **`shared/types/api.ts` régénéré** — `bun run quality-generate-schemas`.
- **`useReveal.ts`** : la correspondance accès → champ, extraite dans `services/` et testée.
- **`useSearch.ts`** : ce que compte « dévoilés ».
- **`ProfileCard.vue`** : chaque coordonnée devient son propre geste d'achat ; le bouton
  « Coordonnées » disparaît.
- **La modale de dévoilement en lot** de la Table : un choix de canal.
- **Les clés i18n** par canal, dans les trois langues.

## Hors périmètre

- **Unifier le vocabulaire du masquage** entre la Table (points floutés) et la Visionneuse (en toutes
  lettres). La dette reste inscrite ; la payer ici toucherait la Table sur un autre sujet et
  gonflerait un commit déjà large.
- **Le barème de crédits offerts**, que le doublement de prix pourrait justifier de revoir.
- **Le corps de la carte** (poste actuel, entreprise, ancienneté, secteur, résumé), qui attend
  d'autres champs de contrat.

## Décisions de cadrage

### La coordonnée EST le bouton

Chaque ligne de la colonne de gauche devient un bouton pleine largeur portant son coût :
« @ E-mail disponible · 1 cr. ». Cliquer achète **ce canal seul**.

C'est ce qui met l'achat exactement là où sa valeur apparaîtra — la ligne cliquée deviendra l'adresse.
Un bouton séparé à côté de la mention aurait mis deux éléments par ligne dans une colonne étroite,
soit quatre pour deux canaux ; l'icône seule aurait donné une cible de seize pixels et un coût
affiché nulle part, alors que la règle de la card est qu'un bouton qui dépense dit toujours combien.

Une fois le canal payé, la ligne redevient du **texte** : il n'y a plus rien à acheter.

### Une coordonnée absente n'est pas cliquable

« Téléphone indisponible » reste en gris italique, inerte, sans curseur.

Le serveur, lui, accepterait le paiement — et le vivier bouge, un numéro peut arriver après coup.
Refuser côté client fige donc une absence temporaire, et c'est assumé : laisser payer pour découvrir
qu'il n'y a rien est exactement le défaut qu'`ENT-029` vient de corriger. On ne le réintroduit pas
pour un cas de bord.

### « Dévoilé » reste une propriété du profil

Le compteur de la colonne compte les profils dont **au moins un canal** est payé, pas les accès
achetés. Le recruteur y suit combien de personnes il a engagées ; compter les accès en ferait une
mesure de dépense qui gonfle sans dire pourquoi, et qui ne serait plus comparable à ce qu'il lisait
hier.

### La règle d'accès descend dans `services/`

`useReveal.hasAccess` fait correspondre un type d'accès à un champ du profil. C'est une **règle
pure**, et c'est le genre d'inversion — l'e-mail lu sous l'accès téléphone — qu'aucune capture ne
révèle et qu'un test attrape en une ligne.

Elle rejoint donc `services/`, seule couche que la suite front ramasse. Le composable garde ce qui
est réactif : le solde, le coût, l'appel.

### Rien ne commence avant le merge d'`API-018`

`openapi-typescript` régénère `shared/types/api.ts` depuis `../api/openapi.json` — **le fichier de
la lane courante**. Tant qu'`API-018` n'est pas sur `develop` et la lane entreprise rebasée, cette
génération reproduit l'ancien contrat, et `quality-contracts-check` sort rouge.

Écrire les types à la main pour aller plus vite livrerait une lane qui ne passe pas ses propres
gates. L'ordre est donc imposé : **pousser et merger `API-018` → merger ou rebaser la lane
entreprise → implémenter cette carte**.

## Règles et cas limites

- **Un canal déjà acquis** n'affiche plus de bouton, et l'API ne refacture pas — les deux doivent
  rester vrais en même temps.
- **Solde insuffisant** : le bouton reste visible mais désactivé, avec son infobulle. Masquer le prix
  parce qu'on ne peut pas payer cacherait la raison du blocage.
- **Recherche partagée** (`editable` faux) : aucun geste d'achat, mais les coordonnées déjà payées
  par l'organisation restent lisibles.
- **Un profil sans aucune coordonnée** n'offre aucun geste : deux lignes grises, et c'est tout.
- **Aucun texte en dur**, `aria-label` compris ; les libellés de canal passent par le catalogue.
- **La carte reste passive** : elle émet `reveal` avec son type, la page paie et relit.
- **Relire `ControlPanel.vue` avant de toucher au compteur** : `ENT-036` l'a refactoré entre-temps.

## Documentation impactée

**Aucune page**, et les déclencheurs ont été vérifiés un par un plutôt que supposés :

- `composables-standard.md` et `services-standard.md` — l'extraction vers `services/` **applique** la
  règle qu'ils portent déjà (« le métier pur ne lui appartient pas : il est délégué à `services/` »).
  Elle ne la change pas, et aucune des deux pages n'inventorie les services un par un.
- Le contrat lui-même est documenté **côté API**, et `API-018` s'en est chargé.
- Aucune page d'architecture ni de cadrage ne cite `revealedContact` ou `CONTACT` — vérifié par
  recherche, rien à corriger.

## Définition de terminé

- [x] Aucun `revealedContact` ni `'CONTACT'` dans `webapp/`.
- [x] `shared/types/api.ts` était déjà régénéré par `API-018` — le contrat et son miroir généré
      voyagent ensemble, cette carte n'avait donc rien à y faire.
- [x] Les deux miroirs manuels disent le contrat réel.
- [x] Chaque coordonnée achetable est un bouton portant son coût ; une coordonnée absente est inerte.
- [x] Le bouton « Coordonnées » n'existe plus.
- [x] La modale de lot laisse choisir le canal : elle itère `REVEAL_TYPES`, donc elle suit le contrat
      d'elle-même et propose désormais quatre accès.
- [x] La règle d'accès vit dans `services/` et ses tests passent.
- [x] **Acheter l'e-mail n'ouvre pas le téléphone**, constaté à l'écran sur un profil réel, dans les
      deux thèmes.
- [x] `bun run quality-check` — vert à l'ouverture de la PR : **0 erreur** (17 avertissements
      préexistants), conventions 9 règles sans violation, type-check sur les quatre espaces,
      **90 tests** sur 12 fichiers.

## Vérification

**53 tests verts** sur `services/search/` — sortie lue le 2026-08-02 —, dont les **7 nouveaux** de
`reveal.vitest.ts`. Le plus utile vérifie l'inversion que rien d'autre n'attraperait : acheter
l'e-mail n'ouvre pas le téléphone, et réciproquement. Les deux coordonnées s'affichant au même
endroit, une capture montrerait la même chose dans les deux cas.

**Plus aucune trace de l'ancien contrat** : `grep -rn "revealedContact\|'CONTACT'"` sur `webapp/`
ne rend plus rien hors `shared/types/api.ts`, qui est généré.

**La preuve à l'écran, faite après coup.** Elle avait été reportée — la stack était tombée deux fois
et la base à reconstruire. Une fois le vivier remonté (3 053 candidats), elle a été produite sur un
profil réel, `Zakaria Matrouf`, dans les deux thèmes :

| Moment | Ce que la card montre |
| --- | --- |
| Avant achat | **deux boutons** : « E-mail disponible · 1 cr. », « Téléphone disponible · 1 cr. » |
| Après clic sur l'e-mail | l'adresse en clair, `zakaria.matrouf@hotmail.fr`, **et le téléphone toujours un bouton** |

Le sondage du DOM le dit sans ambiguïté : `boutonsDeCanal: [false, true]`. La ligne payée a cessé
d'être un geste, l'autre l'est restée. C'est la règle centrale de la task, constatée sur un débit
réel et non simulée.

Reste non constaté, et sans conséquence sur cette carte : le compteur « dévoilés » avec les **deux**
canaux payés sur un même profil — la règle est couverte par `isRevealed` et ses trois tests.

## Livré

- `webapp/services/search/reveal.ts` **(+)** et son `.vitest.ts` **(+)** — `hasAccess` et
  `isRevealed`, la règle d'accès en couche pure.
- `webapp/shared/types/{reveal,candidate-card}.ts` **(M)** — les deux miroirs manuels disent le
  contrat réel.
- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` **(M)** — la coordonnée est le
  bouton ; « Coordonnées » disparaît.
- `webapp/spaces/entreprise/app/composables/useReveal.ts` **(M)** — le `switch` sort du composable.
- `webapp/spaces/entreprise/app/composables/useSearch.ts` **(M)** — « dévoilé » compte des personnes.
- `webapp/services/search/export.ts` **(M)** — l'export s'aligne sur le seuil du serveur.
- `webapp/services/search/{export,normalize,views}.vitest.ts` **(M)** — fixtures réalignées.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` **(M)** — commentaire du lot.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — les libellés par canal.

## Écart au cadrage — un consommateur oublié

Le cadrage listait quatre consommateurs. Il en existait un **cinquième** : `services/search/export.ts`
filtrait l'exportable sur `revealedContact`. Aligné sur « au moins un canal », le seuil exact du
serveur — sans quoi le front aurait annoncé un export plus étroit que ce que l'API accepte.

Et la modale de lot de la Table n'a demandé **aucun code** : elle itère `REVEAL_TYPES`, donc elle
propose les quatre accès d'elle-même. Le bénéfice d'avoir posé la liste dans le contrat plutôt que
dans l'écran.

## Aval

- **La preuve à l'écran de cette carte**, reportée : elle bloquera `/creer-pr`, dont le contrôle
  d'état refuse une case vide.
- **Unifier le vocabulaire du masquage** entre la Table et la Visionneuse.
- **Le corps de la carte profil**, qui attend `summary`, `currentTitle`, `currentCompanyName` et
  `currentRoleMonths` sur le contrat de carte.
- **La galerie `/artefacts/ux-kit`** doit montrer la variante carrée de l'avatar — lane
  `espace/public`.
