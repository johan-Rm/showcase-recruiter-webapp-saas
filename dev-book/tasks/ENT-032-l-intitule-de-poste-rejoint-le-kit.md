# TASK ENT-032 — entreprise : l'intitulé de poste rejoint le kit maison

- **Créée le** : 2026-07-31.
- **Session** : `57a6103a-e57e-429e-82e9-aed454da48f3` — **reprise** le 2026-08-02, sur instruction humaine. Le cadrage
  vient d'une autre session : les décisions déjà inscrites ne sont pas les miennes, et je
  les relis avant d'implémenter plutôt que de les découvrir au commit.
- **Statut** : ✅ fait le 2026-08-02 — le dernier `UInputMenu` du formulaire a disparu, et avec lui
  les trois défauts qu'il portait. Le kit gagne `UiRemoteCombobox`, qui sert désormais l'adresse
  **et** l'intitulé de poste.
- **Type** : Frontend socle + espace entreprise — remplacement de composant.
- **Tests** : `sans objet` — aucune infra de test de composant dans le dépôt
  (`webapp/vitest.config.ts` ne ramasse que du code pur). Preuve : capture dans les deux thèmes et
  parcours clavier vérifié à la main. **Réserve** : si la multi-sélection est retenue, la
  transformation critères ↔ formulaire devient testable et **doit** l'être (`services/search/criteria.vitest.ts`).
- **Réf** : `ENT-030` (le champ mots-clés, même mouvement) ·
  [criteres_de_filtrage_profils.md §1](../cadrage/entreprise/criteres_de_filtrage_profils.md) —
  décision produit du 2026-07-12 ·
  [UiPlacesAutocomplete](../../webapp/base/app/components/ui/PlacesAutocomplete.vue) ·
  [SearchCriteriaTranslator.php](../../api/src/Domain/Search/SearchCriteriaTranslator.php)

## Contexte

Une fois `ENT-030` livrée, l'intitulé de poste reste **le dernier `UInputMenu` du formulaire de
recherche**. Deux composants voisins, deux grammaires visuelles, dans la même carte.

Mais le sujet n'est pas seulement d'habillage, et c'est ce que la lecture du cadrage fait
apparaître.

**Le cadrage demande une multi-sélection, le code est mono-valué.** La décision produit du
2026-07-12 dit que le point d'entrée de la recherche est « une **combobox "Poste recherché"** à
autocomplétion **multi-sélection**, dont l'input fait office de recherche libre sur les intitulés de
poste ». Aujourd'hui `PrimarySearchForm.jobTitle` est une chaîne, `fromPrimaryForm` pose un seul
critère `job`, et le moteur serveur n'en garde qu'un : `SearchCriteriaTranslator` applique le
« dernier gagne » à toutes les facettes **sauf `q`**, seule facette multivaluée.

Autrement dit : le formulaire ne peut pas exprimer « développeur **ou** ingénieur logiciel », alors
que c'est précisément la demande d'un recruteur — et que le champ mots-clés, lui, sait le faire.

**Le référentiel de postes est par ailleurs listé en dette** par le cadrage (« Champs à modéliser » :
référentiel de postes, point d'entrée de la recherche). L'auto-complétion actuelle interroge le
vocabulaire `JOB` dérivé du vivier.

### Trois défauts visibles, tous portés par `UInputMenu`

Constatés à l'écran le 2026-07-31 : en tapant **un seul caractère**, le champ propose `Create "c"`.

1. **Le texte est en anglais.** `Create "c"` sort de la librairie, pas de nos locales :

   ```js
   // @nuxt/ui/dist/runtime/locale/en.js
   inputMenu: { create: 'Create "{label}"', noData: 'No data', noMatch: 'No matching data' }
   ```

   La règle `app-no-hardcoded-text` ne peut rien voir — la chaîne est dans `node_modules`.

2. **L'invitation arrive trop tôt.** Nuxt UI l'affiche dès que sa liste filtrée est vide
   (`return !filteredItems.value.length`). Or sous le seuil de deux caractères **aucune requête ne
   part** : la liste est vide parce qu'on n'a pas cherché, pas parce qu'il n'y a rien. Le composant
   lit « aucun résultat » là où la vérité est « pas encore demandé ».

3. **« Créer » serait faux même traduit.** Rien n'est créé : le terme part tel quel dans la
   recherche plein texte. C'est l'arbitrage déjà pris par `ENT-030` pour les mots-clés — le libellé
   dit **« Ajouter »**, jamais « Créer ».

Les trois disparaissent en même temps que `UInputMenu`. C'est ce qui fait passer cette carte d'une
mise en cohérence visuelle à une correction de défauts.

## Périmètre

- Le champ « Intitulé de poste » de
  [FiltersPrimary.vue](../../webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue)
  quitte `UInputMenu`.
- Le composant du kit qui le porte — existant ou neuf, selon l'arbitrage ci-dessous.
- Les clés i18n correspondantes, trois locales.

## Hors périmètre

- **Le champ mots-clés** — `ENT-030`. **Ordre imposé** : `ENT-030` d'abord ; si la multi-sélection
  est retenue ici, le composant qu'elle a produit est réutilisé tel quel.
- **Le référentiel de postes** et la qualité du vocabulaire `JOB` : c'est une dette de données, pas
  d'interface.
- **La mise en page de la carte** — `ENT-031`.
- **Le trou d'i18n de Nuxt UI**, dont le `Create "c"` n'est qu'un symptôme. Nuxt UI tire sa locale
  d'un `<UApp :locale>` ; l'app n'en a **pas à sa racine** (`base/app/app.vue` n'enveloppe rien, le
  seul `<UApp>` du dépôt vit dans une page d'artefacts publique). Tous les textes intégrés de la
  librairie sont donc en anglais, dans les **quatre** espaces — « No data », « No matching data » et
  leurs équivalents ailleurs. Aucun espace ne le consomme en propre : **à carter sur
  `espace/socle-webapp`**. Cette carte le nomme et s'arrête là ; elle éteint le symptôme sur son
  champ, pas la cause.

## À trancher avant d'implémenter

> Cette carte est **volontairement ouverte** sur son point central. Elle est écrite pour porter le
> constat ; la décision se prend en discussion et s'inscrit ici avant la première ligne de code.

**1. Mono-valué ou multi-sélection — réglé le 2026-07-31 : cette carte reste mono-valuée.**

Le cadrage du 2026-07-12 demande une multi-sélection, le moteur n'en garde qu'une valeur, et
l'arbitrage semblait devoir se prendre ici. Il se prend en fait dans **`ENT-035`** : pour retirer un
poste de l'élargissement, la recherche doit de toute façon accepter une **liste de concepts**, et la
multi-sélection ne coûte alors plus rien.

Rester mono-valué ici n'est donc pas une dette : c'est `ENT-035` qui apporte le multi, avec l'amont
API qui le rend possible. Cette carte garde le seul périmètre qu'elle peut livrer seule — le
composant.

**2. Quel composant ? — réglé le 2026-08-02 : le combobox distant se généralise.**

Aucun composant du kit ne couvre exactement le besoin — suggestions **distantes**, valeur **unique**,
**terme libre autorisé**, et un **compte de profils** en face de chaque proposition.
`UiPlacesAutocomplete` a la mécanique (distant, débouncé, combobox ARIA) mais résout des adresses ;
`UiInlineSelect` a la valeur unique mais une liste **locale**.

**Écrire un troisième composant a été écarté, et par une règle plutôt que par un goût.** `ENT-030`
vient d'inscrire dans le README des composants qu'un `Ui*` neuf ne se justifie que lorsque Nuxt UI ne
sait pas produire la **structure** voulue. Pour les mots-clés, l'argument tenait : `UInputMenu` en
mode `multiple` place ses chips *dans* le champ. **Ici il ne tient pas** — le champ est mono-valué et
`UInputMenu` produit exactement la structure attendue. Le grief est de cohérence visuelle, et la
règle refuse ce motif seul.

Le composant reçoit donc ses suggestions **par prop** au lieu d'appeler `usePlaces` lui-même, et
devient LE combobox distant mono-valué du kit. L'autocomplétion d'adresse n'est plus qu'un de ses
appelants. Un seul patron pour trois champs, et aucun composant de plus.

**Écart au cadrage — il change de nom.** La carte disait « `UiPlacesAutocomplete` se généralise ».
Il est devenu **`UiRemoteCombobox`**, et l'ancien fichier a été supprimé : garder un nom qui annonce
Places sur un composant qui ne connaît plus Places aurait été un contresens que le prochain lecteur
paie. Le mécanisme décidé est respecté à la lettre ; seul le nom suit ce que le composant fait.

**Conséquence, plus légère que redoutée** : la carte annonçait « l'inscription et les formulaires
publics » à revalider. Il n'y a **qu'un seul** appelant, `registration/CompanyForm.vue`, et il est
dans cette lane. Il reprend `usePlaces`, le debounce et la résolution que le composant portait pour
lui — c'est le prix d'un composant sans domaine, et il est payé à un seul endroit.

## Règles et cas limites

- **Le compte de profils reste affiché** en face de chaque suggestion : sans lui, on pose un intitulé
  qui couvre un quart du vivier en croyant restreindre.
- **Le terme libre reste accepté** — le cadrage le dit explicitement : « l'input fait office de
  recherche libre sur les intitulés de poste ».
- **Seuil de 2 caractères et debounce de 220 ms** conservés, comme pour les mots-clés.
- **`toPrimaryForm` / `mergeWithPrimary` / `PRIMARY_FACETS`** doivent suivre si la cardinalité
  change : le formulaire n'a le droit d'écraser que les facettes qu'il possède, et une recherche née
  du langage naturel porte des facettes qu'il ignore.
- **Le nom automatique d'une recherche** dérive des critères dans leur ordre ; deux intitulés
  produiraient un segment « métier » à deux valeurs, jointes par « , ». Vérifier que le nom reste
  lisible.
- **Aucun texte en dur**, trois locales.

## Documentation impactée

**Aucune page**, déclencheurs vérifiés un par un : `design-system.md` **inventorie** le kit sans
documenter d'API (« avatar, badge, bouton, champ… »), et remplacer un composant par un autre n'y
change rien ; aucun `README.md` de dossier ne change de convention — la règle d'`ENT-030` sur les
`Ui*` neufs est **appliquée**, pas modifiée. La multi-sélection demandée par
[criteres_de_filtrage_profils.md §1](../cadrage/entreprise/criteres_de_filtrage_profils.md) n'est ni
abandonnée ni révisée : elle arrive avec `ENT-035`. Le cadrage reste juste.

## Définition de terminé

- [x] Le champ ne référence plus `UInputMenu` ; il porte la grammaire du kit.
- [x] Suggestions distantes, terme libre, compte de profils : les trois survivent.
- [x] **Les trois défauts du contexte sont éteints** : plus un mot d'anglais, aucune invitation sous
      le seuil de deux caractères, et « Créer » a disparu avec le composant qui le produisait.
- [x] Clavier complet vérifié dans le DOM servi.
- [x] Trois locales à jour.
- [x] Captures dans les deux thèmes.
- [x] `bun run quality-check` — vert à l'ouverture de la PR : **0 erreur** (17 avertissements
      préexistants, sans rapport avec cette carte), conventions 9 règles sans violation, type-check
      sur les quatre espaces, **90 tests** sur 12 fichiers.

## Vérification

**Les trois défauts, constatés au navigateur dans les deux thèmes** — sondage du DOM servi, le
2026-08-02 :

| Défaut | Constat |
| --- | --- |
| Texte anglais (`Create "c"`, `No data`) | `anglaisPresent: false` — aucun mot d'anglais dans la page |
| Invitation sous le seuil | Avec **un** caractère : `panneau: false`, rien ne s'ouvre |
| « Créer » mensonger | Disparu avec `UInputMenu` |

**Les trois exigences fonctionnelles survivent** : au-delà du seuil, **9 suggestions** avec leur
décompte — « Chef de projet · 515 profils » —, le terme libre reste la valeur, et le contrôle porte
`role="combobox"` avec `aria-expanded` piloté.

**Le parcours clavier, dans le DOM servi** : ↓ surligne la première option et `aria-activedescendant`
la désigne ; ↓↓ avance ; ↑ revient ; Échap ferme en gardant la saisie ; ↓ puis Entrée valide
« Chef de projet » — **et le formulaire ne se soumet pas**, l'URL reste `/`. C'est le piège classique
d'un combobox dans un formulaire, et il est écarté.

**Gates locales** : type-check **vert sur les quatre espaces**, conventions **9 règles sans
violation**, **53 tests purs verts**.

**Ce qu'il a fallu pour prouver.** La base de ce worktree avait été reconstruite : le vivier était
vide, donc aucune suggestion. La remise en état a révélé une **dépendance d'ordre** qui vaut d'être
sue : importer les profils **avant** de charger le vocabulaire laisse `candidate_job_experience`
vide, et le champ ne propose rien alors que tout est correct. La séquence est **vocabulaire d'abord,
import ensuite**. Après réimport : 3 053 candidats, 4 417 liaisons, 860 concepts — et aucun appel
LLM, la taxonomie étant versionnée dans le dépôt.

## Livré

- `webapp/base/app/components/ui/RemoteCombobox.vue` **(+)** — le combobox distant du kit :
  mono-valué, terme libre, suggestions par prop, sans domaine.
- `webapp/base/app/components/ui/PlacesAutocomplete.vue` **(D)** — remplacé ; son unique appelant a
  repris la mécanique Places.
- `webapp/spaces/entreprise/app/components/registration/CompanyForm.vue` **(M)** — porte désormais
  `usePlaces`, le debounce et la résolution d'adresse.
- `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue` **(M)** — le dernier
  `UInputMenu` du formulaire disparaît.
- `webapp/base/app/components/ui/TagPicker.vue` **(M)** — une référence au nom disparu.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — le libellé de chargement du champ.

## Aval

- **`ENT-035`** — l'élargissement explicite, le retrait des postes similaires, le choix actuel /
  exercé, et la multi-sélection qui vient avec.
