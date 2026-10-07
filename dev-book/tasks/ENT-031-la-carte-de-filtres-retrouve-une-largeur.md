# TASK ENT-031 — entreprise : la carte de filtres retrouve une largeur lisible

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-08-01 — le contenu se borne à 792 px dans une carte restée pleine
  largeur, le curseur et sa valeur tiennent ensemble, le bouton suit le dernier champ et le titre
  interne disparaît.
- **Type** : Frontend espace entreprise — refonte de mise en page.
- **Tests** : `sans objet` — la suite front ne couvre que le code **pur**
  (`webapp/vitest.config.ts`) et cette carte ne déplace que du gabarit. La preuve est une capture,
  dans les deux thèmes, sur un écran large **et** sur mobile. Précédent : `ENT-026`, `ENT-029`.
- **Réf** : `ENT-030` (le champ mots-clés, qui occupe le même bloc) ·
  [sizing.css](../../webapp/base/app/assets/css/sizing.css) — `--container-readable`,
  `--container-form` · [FiltersPrimary.vue](../../webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue)

## Contexte

La carte de recherche par critères s'étire sur **toute la largeur** de l'écran. Sur un poste de
travail courant, cela fait près de 1 900 px pour quatre champs, et chacun le paie à sa façon.

**Le curseur d'expérience étale quinze crans sur ~1 600 px.** Viser « 5 ans » devient un geste de
précision pour une donnée qui ne l'est pas. Sa valeur lue — « Toutes », « 5 ans » — est posée à
l'autre bout de la ligne, hors du champ de vision de la poignée qu'on déplace.

**Le bouton « Rechercher » est renvoyé en bas à droite** par `mt-auto` sur une carte en `h-full`. Il
se retrouve à plusieurs centaines de pixels du dernier champ rempli, dans un coin que rien ne
désigne.

**Les deux champs à auto-complétion font 1 800 px de large** pour y saisir deux mots.

**Et la carte porte deux titres.** La gouttière affiche déjà « Décrivez le profil que vous
cherchez » ; la carte, juste dessous, ajoute un `h2` « Filtres principaux » — du vocabulaire interne,
qui ne renseigne pas et redouble la consigne.

## Périmètre

- Borner la largeur du contenu de la carte.
- Rapprocher la valeur lue du curseur de sa poignée.
- Rapprocher le bouton de soumission du dernier champ.
- Trancher le sort du titre « Filtres principaux ».

Tout est contenu dans
[FiltersPrimary.vue](../../webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue) et,
pour le titre, dans les trois locales.

## Hors périmètre

- **Le composant de mots-clés** — `ENT-030` le remplace. Cette carte ne touche que le gabarit qui
  l'entoure. **Ordre imposé** : `ENT-030` d'abord, sinon les deux se disputent les mêmes lignes.
- **`FiltersAi`**, dont la carte jumelle a sa propre mise en page.
- **L'ajout ou le retrait d'un champ** — voir `ENT-033`.
- **La gouttière et le zonage de la page d'accueil**, qui ne sont pas en cause.

## Décisions de cadrage — tranchées le 2026-08-01

### La carte garde son aplat, c'est son CONTENU qui se borne

`mx-auto w-full max-w-readable` sur le bloc des champs, à l'intérieur d'une `UiCard` inchangée.

Borner la carte elle-même — à 32 rem comme la page `magic-link`, ou à 72 ch — aurait produit un objet
compact posé au centre d'un fond vide : l'aplat porte la surface de l'outil, et le rétrécir aurait
fait perdre à l'écran d'accueil le poids qu'il a. Le précédent retenu est `account/CompanyForm`, qui
borne son formulaire sans toucher à la carte qui l'entoure.

`max-w-form` (32 rem) est écarté malgré son nom : quatre champs dont un curseur à quinze crans n'y
tiennent pas confortablement.

### Le titre « Filtres principaux » disparaît

La gouttière dit déjà « Décrivez le profil que vous cherchez ». « Filtres principaux » est du
vocabulaire interne : il ne renseigne pas et redouble la consigne. Le reformuler aurait gardé un
niveau 2 au prix de répéter la gouttière presque mot pour mot — ce qui était le reproche.

La page porte un `h1` en `sr-only` : la hiérarchie reste valide, aucun niveau n'est sauté. Les trois
clés `search.primary.title` partent avec, après vérification qu'elles n'étaient utilisées nulle part
ailleurs.

### Conséquence assumée — le fond reste vide sous la carte

`h-full` et `mt-auto` tombent **ensemble**, comme la carte l'exigeait. La carte prend donc la hauteur
de son contenu, et sur grand écran le fond apparaît en dessous. C'est le prix du bouton rapproché du
dernier champ, et il a été accepté : un bouton à plusieurs centaines de pixels du dernier geste coûte
plus qu'un aplat qui s'arrête où le formulaire s'arrête.

## Règles et cas limites

- **Mobile-first** : sous `sm`, la carte occupe toute la largeur disponible — le bornage ne doit
  jamais produire de marge sur petit écran.
- **La carte est montée à deux endroits** : la page d'accueil et le tiroir « Affiner »
  (`RefineDrawer`, un `USlideover` déjà étroit). Un bornage qui améliore l'accueil ne doit pas
  rétrécir le tiroir une seconde fois.
- **`h-full` et `mt-auto` se tiennent** : retirer l'un sans l'autre laisse un bouton flottant au
  milieu du vide. Si la carte cesse de remplir la hauteur, la page doit continuer à tenir son
  contrat de hauteur (racine en `flex min-h-0 flex-1`).
- **La sortie du curseur** (`<output>`) garde son lien `for` avec le champ : c'est lui qui la fait
  annoncer.
- **Aucun texte en dur**, y compris si le titre est reformulé — trois locales.
- **Aucun changement de comportement** : les mêmes critères produisent la même recherche.

## Documentation impactée

Aucune. Cette carte applique des conventions existantes et n'en change aucune.

## Définition de terminé

- [x] Le contenu de la carte est borné ; le curseur ne dépasse pas une largeur où viser reste
      possible.
- [x] La valeur lue du curseur est adjacente à sa poignée.
- [x] Le bouton de soumission suit le dernier champ.
- [x] Le sort du titre est tranché et appliqué dans les trois locales ; la hiérarchie de titres du
      document reste valide.
- [x] Captures dans les deux thèmes, sur écran large **et** sous `sm`.
- [x] Le tiroir « Affiner » n'a pas rétréci.
- [x] Les gates **impactées** sont vertes : conventions, lint, format. *(Le cadrage écrivait ici
      « `bun run quality-check` est vert » — critère corrigé : le dépôt réserve la passe complète à
      l'ouverture de la PR.)*

## Vérification

**Mesuré dans le navigateur, connecté**, sur la carte d'accueil :

| | Avant | Après |
| --- | --- | --- |
| Largeur de l'aplat | 1 901 px | **1 901 px** — inchangé |
| Largeur du contenu | 1 901 px | **792 px**, centré |
| Piste du curseur → valeur lue | ~1 600 px | **336 px** |
| Dernier champ → bouton | plusieurs centaines de px | **20 px** |
| `h2` dans la carte | « Filtres principaux » | **aucun** |

**Sous `sm` (390 px)** : carte 374, contenu **326** — la borne ne mord pas, aucune marge perdue, et
le bouton reste à 16 px du dernier champ. Les six niveaux d'études passent à la ligne sans déborder.

**Le tiroir « Affiner » n'a pas rétréci**, et c'est de l'arithmétique et non une impression : carte
**400 px**, contenu **352 px**, soit exactement `400 − 2 × 24` — le `p-6` de `UiCard`. La borne
(72 ch, ~700 px) est très au-dessus de la place disponible : elle ne s'applique jamais là.

**Captures dans les deux thèmes**, en 1920 et en 390. Conventions **9 règles, aucune violation** ·
lint sans erreur · format vert.

## Livré

- `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue` **(M)** — contenu borné et
  centré, `h-full` et `mt-auto` retirés ensemble, ligne du curseur bornée, `h2` supprimé.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — la clé `search.primary.title` retirée.

## Reste à faire (hors périmètre)

- **Aucun test unitaire** : la carte ne déplace que du gabarit, et la suite front ne couvre que du
  code pur. Décision prise au cadrage, preuve reportée sur les mesures et les captures.
- Les autres griefs de mise en page listés en « Aval » d'`ENT-030` restent ouverts.
- `bun run quality-check` complet appartient à `/creer-pr`.
