# TASK ENT-030 — entreprise : les mots-clés passent au kit maison

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-08-01 — `UiTagPicker` rejoint le kit, le champ mots-clés tient sur une
  ligne, et le terme ne reste plus affiché après avoir été posé.
- **Type** : Frontend socle + espace entreprise — remplacement de composant.
- **Tests** : `sans objet` — la suite front ne ramasse que `**/*.vitest.ts` et ne couvre que du code
  **pur** (`webapp/vitest.config.ts`) ; il n'existe aucune infra de test de composant. La preuve est
  double : une capture avant / après dans les **deux thèmes**, et le **parcours clavier** complet
  vérifié à la main (Tab, ↑↓, Entrée, Escape, retrait d'une chip). Précédent : `ENT-026`, `ENT-029`.
- **Réf** : `ENT-028` (les composants de la Visionneuse, et le marqueur de racine) ·
  [base/app/components/README.md](../../webapp/base/app/components/README.md) — « Nuxt UI et le kit
  maison », « Marqueur de composant sur la racine » ·
  [base/app/README.md](../../webapp/base/app/README.md) — « Réseau dans les composants », « Écoute
  d'événements et minuteries (VueUse) » ·
  [UiPlacesAutocomplete](../../webapp/base/app/components/ui/PlacesAutocomplete.vue) (le jumeau
  mécanique) · [UiInlineSelect](../../webapp/base/app/components/ui/InlineSelect.vue) ·
  référence de forme externe : `blue-bay-mogador/app/components/dashboard/CategoryCodeSelect.vue`,
  mode `multiple`

## Contexte

Le champ mots-clés de la recherche classique est **le seul contrôle de l'application à ne pas suivre
la grammaire du kit**. Il s'appuie sur `UInputMenu` (Nuxt UI) quand ses deux voisins de fonction —
`UiInlineSelect` pour une liste fermée, `UiPlacesAutocomplete` pour des suggestions distantes — sont
des comboboxes maison, au même trait et au même panneau flottant.

L'écart se paie de trois façons, toutes visibles à l'écran.

**Il occupe deux lignes en permanence.** Un champ texte pleine largeur, puis la rangée de badges en
dessous. Sur la carte de filtres, c'est le bloc le plus haut pour l'information la moins
structurante — le formulaire pose lui-même les mots-clés en dernier « parce que ce sont les plus
libres ».

**Le terme reste affiché après avoir été posé.** Le mot apparaît **deux fois** : dans le champ et
dans son badge. `addKeyword` remet pourtant la saisie à vide ; mais le menu est monté **sans
`v-model`**, donc reka-ui conserve sa sélection en interne et la réaffiche via `displayValue`. Vider
le `search-term` ne touche pas cet état. Conséquence : pour poser un second mot-clé, il faut effacer
le premier à la main.

**La liste ne dit pas ce qui est déjà choisi.** Recliquer un mot-clé déjà posé ne produit rien de
visible — `addKeyword` le rejette en silence, à juste titre, mais l'écran n'en dit rien.

## Périmètre

- **Un composant neuf** : `webapp/base/app/components/ui/TagPicker.vue`.
- **Le bloc mots-clés de `FiltersPrimary.vue`** (aujourd'hui lignes 229-277) le consomme.
- **`useSuggestions` expose l'échec** du service de suggestions, aujourd'hui effacé.
- **Une ligne dans `base/app/components/README.md`** : l'exception à « un écran neuf part de
  Nuxt UI ».
- Les clés i18n correspondantes dans les **trois** locales (`fr`, `en`, `ar`).

## Hors périmètre

- **Le champ « Intitulé de poste »**, mono-valué : ni chips, ni bouton d'ajout, c'est une autre
  forme. Son alignement sur `UiPlacesAutocomplete` est une carte aval.
- **`FiltersAi`** et ses puces de critères écartables : forme voisine, source et sémantique
  différentes (le modèle propose, on retire — on n'ajoute pas).
- **Le passage de `UiInlineSelect` et `UiPlacesAutocomplete` en utilitaires Tailwind.** Ils portent
  un `<style scoped>` que le README ne veut plus ; les convertir ici toucherait l'inscription et les
  formulaires publics pour un bénéfice nul sur cet écran.
- **La mise en page du formulaire** — largeur de la carte, curseur d'expérience étiré, position du
  bouton, titre « Filtres principaux ». Diagnostic posé, cartes à écrire séparément.
- **Toute évolution du moteur** : facettes, comptage avant lancement, création de la recherche.

## Décisions de cadrage

### Un composant maison, et pas un habillage de `UInputMenu`

Le README des composants pose que « un écran neuf part des composants Nuxt UI ». Cette carte va
contre ce défaut, et voici pourquoi.

`UInputMenu` en mode `multiple` place ses chips **dans** le champ. La forme voulue les place
**avant** un bouton d'ajout, et n'affiche aucun champ tant qu'on n'ajoute pas. Ce n'est pas une
question de style : c'est la structure du composant, et aucune valeur de la prop `:ui` ne la change.
Habiller `UInputMenu` aurait donné « des chips dans un champ », pas « des chips, puis un `+` ».

L'hybride — chips maison, `UInputMenu` monté dans le panneau — a été écarté : il aurait fait
cohabiter deux grammaires visuelles dans une même surface, à quelques pixels l'une de l'autre.

Le coût réel est faible : **le patron ARIA existe déjà deux fois dans le dépôt**. Le composant se
copie sur `UiPlacesAutocomplete`, dont il partage tout — suggestions distantes, debounce,
`role="combobox"`, listbox, `aria-activedescendant`, flèches. Il n'y ajoute que le multi-valué.

### `ui/` est sans domaine : le composant ne va pas chercher ses suggestions

Le README réserve `ui/` aux « primitives génériques, **sans domaine**, réutilisables partout ». Un
`UiTagPicker` qui appellerait `useSuggestions` ne serait réutilisable nulle part ailleurs : il
saurait ce qu'est une compétence.

Il reçoit donc ses données et rend ses gestes :

| Entrée | Ce que c'est |
| --- | --- |
| `modelValue` | les termes posés — `defineModel<string[]>` |
| `items` | les suggestions courantes (`{ value, hint }` — voir l'écart ci-dessous) |
| `loading` | une requête est en vol |
| `failed` | la dernière requête a échoué |
| `id`, `describedBy` | le lien vers le `<label>` et l'aide de l'appelant |
| libellés | tous par prop, aucun texte en dur (`app-no-hardcoded-text`) |

| Sortie | Quand |
| --- | --- |
| `update:term` | à chaque frappe dans le panneau |
| `update:modelValue` | ajout ou retrait d'un terme |

Le **debounce de 220 ms**, le **seuil de 2 caractères** et le « dernière réponse gagne » restent dans
`FiltersPrimary`, où la fabrique `useVocabulary` les tient déjà et les partage avec le champ poste.
Les descendre dans le picker les aurait dupliqués.

### Écart au cadrage — `hint` plutôt que `profileCount`

Le cadrage donnait `{ value, profileCount }`. Le composant reçoit `{ value, hint }`, un texte déjà
formaté.

C'est la conséquence directe de la décision juste au-dessus : `profileCount` aurait obligé un
composant de `ui/`, déclaré **sans domaine**, à connaître la clé `search.primary.suggestion.count`
pour l'accorder en nombre. Le décompte se formate donc chez l'appelant, qui tient déjà `t`. Le rendu
est identique, et `UiTagPicker` reste utilisable par un écran qui n'a pas de profils à compter.

### Écart au cadrage — le panneau mesure sa place au lieu de la supposer

Le cadrage demandait un panneau « plafonné à 240 px », et signalait qu'un ancêtre en `overflow:
hidden` pourrait le découper. La vérification a montré autre chose : **aucun ancêtre ne le découpait
vraiment**, mais à hauteur fixe il dépassait le bas de la zone visible — de sept pixels dans la carte
d'accueil, de six dans le tiroir.

Deux tentatives ont échoué avant la bonne, et elles valent d'être notées :

- **décider du côté d'ouverture sur la hauteur du moment** : à l'ouverture le panneau est vide, donc
  bas, donc il tient partout — et il déborde dès que la liste arrive ;
- **basculer vers le haut** : plus de débordement, mais le panneau masquait alors les quatre champs
  déjà remplis pour gagner sept pixels.

Ce qui est en place : le panneau **reste sous le champ** tant que la place y est consultable
(200 px), prend la **hauteur disponible** plafonnée à 300, et mesure cette place contre le **premier
ancêtre qui masque son débordement** — pas contre la fenêtre, précisément parce que ce composant est
monté dans une carte *et* dans un tiroir. Relevé à l'ouverture seulement : il ne saute pas pendant la
frappe.

### La forme vient de blue-bay, la mécanique du socle

Ce qui est repris de la référence :

- une rangée de chips qui passe à la ligne, **chaque chip portant son ×** ;
- **pas de champ texte permanent** — un bouton clôt la rangée ;
- ce bouton **devient un `×`** quand le panneau est ouvert : une affordance, deux états ;
- le panneau s'ouvre en dessous avec une **recherche autofocus** puis la liste ;
- chaque option porte une **pastille pleine si le terme est déjà posé** ; la recliquer le **retire** ;
- une ligne d'ajout quand la saisie ne correspond à rien ;
- fermeture au clic extérieur et à Escape.

Ce qui n'est **pas** repris, et pourquoi :

- **les couleurs en dur et les `style=` inline** (`#212121`, `#6B7A4A`) — blue-bay est mono-thème
  sombre ; ici les tokens sont déjà exposés en utilitaires (`bg-dropdown`, `border-line`,
  `bg-accent-soft`, `text-muted`) et les deux thèmes doivent tenir ;
- **`text-[0.65rem]`** — 10 px conviennent au tableau de bord d'un administrateur qui connaît son
  outil, pas à un écran de travail lu toute la journée ;
- **l'absence de clavier** — la liste blue-bay est une pile de `<button>` : ni `role="listbox"`, ni
  flèches, ni `aria-expanded`. La reprendre telle quelle serait une **régression** sur ce que
  `UInputMenu` offre aujourd'hui ;
- **le filtrage local** — blue-bay filtre une trentaine de codes chargés en store. Le vocabulaire des
  compétences compte des milliers de termes servis par l'API : le panneau **interroge**, il ne filtre
  pas ;
- **la création côté serveur** — blue-bay `POST` un code de catégorie. Ici rien n'est créé : le terme
  part tel quel dans la recherche plein texte. Le libellé dit donc **« Ajouter »**, jamais « Créer ».

### Le bouton se nomme tant que la rangée est vide

Vide, il porte « + Ajouter un mot-clé » ; dès la première chip, il se réduit au `+`. Un `+` seul en
permanence — la solution de la référence — suppose un utilisateur qui connaît déjà l'outil. Le
recruteur découvre l'écran.

### Le panneau reste ouvert entre deux ajouts

On pose rarement un seul mot-clé. Après un choix : la chip apparaît, la pastille de l'option se
remplit, la saisie se vide, **le focus reste dans le champ de recherche**. Fermer à chaque choix
coûterait deux gestes par terme.

**Ces quatre exigences se contredisent si la saisie est vidée bruyamment** — la première mise à
l'épreuve l'a montré. Vider en prévenant l'appelant lui fait retirer ses suggestions, et la pastille
qu'on venait de remplir s'en va avec la liste. Le composant vide donc son champ **sans** émettre :
la liste survit, et un second terme se prend dedans sans retaper.

### `useSuggestions` cesse d'effacer la panne

Le composable rend aujourd'hui une liste vide sur erreur, par décision écrite : « une
auto-complétion qui échoue doit laisser saisir, pas interrompre ». Le principe reste juste — mais il
rend **panne et vocabulaire vide indistinguables**, et le panneau ne peut donc pas dire lequel des
deux il montre.

Il rendra `{ items, failed }`. La saisie reste possible dans les deux cas, et la ligne « Ajouter "X" »
reste proposée **même en erreur** : le terme libre ne dépend pas du vocabulaire.

### Le panneau est flottant

Hors flux, plafonné à 240 px, exactement comme celui de `UiPlacesAutocomplete` — un panneau en flux
ferait sauter la carte à chaque ouverture. **Point de vigilance** : `FiltersPrimary` est monté à deux
endroits, dans la carte de l'accueil et dans le tiroir « Affiner » (`RefineDrawer`, un `USlideover`).
Un ancêtre en `overflow: hidden` découperait le panneau. À vérifier **dans les deux montages** avant
de clore.

## Règles et cas limites

- **Unicité** : un terme déjà posé ne s'ajoute pas deux fois. Le poser de nouveau depuis le panneau
  le **retire** ; depuis la ligne « Ajouter "X" », il ne fait rien.
- **Trois notes muettes** dans le panneau, non cliquables : recherche en cours, aucun résultat,
  service indisponible.
- **Sous le seuil de 2 caractères**, aucune requête et aucune liste — c'est le seuil que l'API
  applique, l'annoncer ici évite un aller-retour pour rien.
- **Le compte de profils reste affiché** en face de chaque suggestion (`search.primary.suggestion.count`).
  C'est un apport propre à showcase-recruiter-webapp-saas, absent de la référence, et il est argumenté dans le code : sans
  lui on pose un terme qui couvre un quart du vivier en croyant restreindre.
- **Escape** ferme le panneau et rend le focus au bouton. **Le clic extérieur** ferme sans rien poser.
- **Le retrait d'une chip** garde le focus dans la rangée ; retirer la dernière rend au bouton son
  libellé.
- **VueUse obligatoire** pour le clic extérieur (`onClickOutside`) et toute écoute — `addEventListener`
  brut est refusé par `app-prefer-useeventlistener`.
- **Marqueur de racine** : la racine unique porte `ui-tag-picker` en première classe
  (`components-root-marker`).
- **Aucun texte en dur**, `aria-label` compris ; `$t` seulement, jamais `useI18n` (`encapsulation-i18n`).
- **Utilitaires Tailwind, pas de `<style scoped>`** : c'est la règle courante du README, que les deux
  comboboxes existantes précèdent.
- **SSR-safe** : le panneau n'existe qu'après interaction, aucun accès au DOM au montage.
- **Mobile-first** : la rangée passe à la ligne, les cibles tactiles tiennent 44 px, le panneau ne
  déborde pas de la carte.

## Documentation impactée

- [base/app/components/README.md](../../webapp/base/app/components/README.md), section « Nuxt UI et le
  kit maison » — une ligne : un `Ui*` neuf se justifie quand Nuxt UI ne sait pas produire la forme
  voulue, et la carte qui le crée doit le dire. Sans cette phrase, la prochaine carte relira « un
  écran neuf part de Nuxt UI » et refera ce débat.

Rien d'autre : cette carte n'ouvre aucune facette, ne touche ni au contrat d'API ni au modèle.

## Définition de terminé

- [x] `UiTagPicker` existe dans `base/app/components/ui/`, sans domaine : il reçoit `items`,
      `loading`, `failed` et rend `update:term` / `update:modelValue`.
- [x] Rangée de chips, bouton nommé quand elle est vide et réduit au `+` ensuite, `+` ↔ `×` à
      l'ouverture.
- [x] Panneau flottant : recherche autofocus, liste avec pastille pleine sur les termes posés, reclic
      qui retire, ligne « Ajouter "X" », trois notes muettes.
- [x] Le panneau **reste ouvert** entre deux ajouts, la saisie se vide, le focus ne bouge pas.
- [x] Clavier complet : Tab, ↑↓, Entrée, Escape — `role="combobox"`, `aria-expanded`,
      `aria-activedescendant` vérifiés dans le DOM servi.
- [x] `useSuggestions` rend `{ items, failed }` ; la saisie reste possible en cas de panne.
- [x] Le bloc mots-clés de `FiltersPrimary` ne référence plus `UInputMenu` ; `useVocabulary` garde le
      debounce et le seuil.
- [x] **Le terme ne reste plus affiché après ajout** — le défaut d'origine est vérifié éteint.
- [x] Le panneau ne se fait découper ni dans la carte d'accueil, ni dans le tiroir « Affiner ».
- [x] Trois locales à jour, aucun texte en dur.
- [x] Captures dans les deux thèmes — **« après » seulement** : l'ancien champ n'a pas été
      photographié avant d'être remplacé. Le défaut qu'il portait est prouvé autrement, par la
      mécanique observée (la saisie se vide, le panneau reste ouvert).
- [x] Une ligne ajoutée au README des composants.
- [x] Les gates **impactées** sont vertes : conventions, type-check des quatre espaces, lint, format.
      *(Le cadrage écrivait « `bun run quality-check` est vert » — critère corrigé : le dépôt réserve
      la passe complète à l'ouverture de la PR.)*

## Vérification

**Le parcours clavier, joué dans le navigateur** sur la carte d'accueil, connecté :

| Geste | Ce qui a été observé |
| --- | --- |
| au repos | bouton « Ajouter un mot-clé », `aria-expanded="false"`, aucun champ |
| ouverture | panneau posé, `role="combobox"`, **focus dedans** |
| frappe « java » | 10 suggestions avec leur décompte (`java` 269 profils…) |
| ↓ | `aria-activedescendant` = `…-keywords-option-0` |
| Entrée | chip posée, **saisie vidée**, panneau ouvert, focus inchangé, pastille remplie (`● java`) |
| reclic sur l'option posée | retirée — chip et pastille disparaissent ensemble |
| Escape | fermé, focus rendu au bouton, qui reprend son libellé |
| Tab | fermé |

**Les trois notes muettes**, chacune atteinte pour de vrai — la dernière en coupant le service au
niveau du navigateur :

```
① 1 caractère (sous le seuil) : Ajouter « z »
② terme sans résultat        : Aucun résultat Ajouter « zzzzzzzz »
③ service coupé              : Suggestions indisponibles — saisissez votre terme. Ajouter « zzzzzzzq »
```

Sous le seuil, aucune note ne s'affiche : l'appelant n'a rien demandé, l'absence de liste n'y prouve
rien. C'est ce troisième cas qui a fait apparaître un défaut, corrigé : la ligne d'ajout était
enfermée dans la branche « tout va bien » et **disparaissait en panne**, au moment précis où elle est
la plus utile.

**Le panneau ne se fait découper nulle part**, mesuré panneau ouvert et liste chargée :

| Montage | Bas du panneau | Premier ancêtre qui masque | Marge |
| --- | --- | --- | --- |
| Carte d'accueil | 887 | 891 | 4 px à l'intérieur |
| Tiroir « Affiner » | 896 | 900 | 4 px à l'intérieur |

**Rendu vérifié en clair et en sombre** : chip en `accent-soft`, panneau sur `dropdown`, pastille et
décomptes lisibles dans les deux thèmes.

Conventions **9 règles, aucune violation** · type-check vert sur les quatre espaces · format vert ·
lint sans erreur (un avertissement `describedBy` sans défaut, identique à celui de
`UiPlacesAutocomplete` dont le composant reprend le patron).

## Livré

- `webapp/base/app/components/ui/TagPicker.vue` **(+)** — la rangée, le bouton à deux états, le
  panneau flottant qui mesure sa place, le patron ARIA de combobox.
- `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue` **(M)** — le bloc mots-clés
  consomme le picker ; `addKeyword` / `removeKeyword` disparaissent, l'unicité vit désormais dans le
  composant.
- `webapp/spaces/entreprise/app/composables/useSuggestions.ts` **(M)** — rend `{ items, failed }`.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — cinq clés par langue.
- `webapp/base/app/components/README.md` **(M)** — quand un `Ui*` neuf se justifie.

## Reste à faire (hors périmètre)

- **Aucun test unitaire** : la suite front ne ramasse que du code pur et le dépôt n'a pas d'infra de
  test de composant. Décision prise au cadrage, preuve reportée sur le parcours réel.
- `bun run quality-check` complet appartient à `/creer-pr`.

## Aval

- **Le champ « Intitulé de poste »**, à aligner sur `UiPlacesAutocomplete`.
- **La mise en page du formulaire** : carte non bornée en largeur, curseur d'expérience étiré sur
  toute la ligne, bouton renvoyé loin du dernier champ, titre « Filtres principaux » qui double la
  consigne de la gouttière.
- **Les facettes absentes du formulaire classique** — présence au Maroc, ville, horizon de retour,
  mobilité, langue : exprimables par la phrase en langage naturel, par rien d'autre. Le cœur du
  produit n'a pas de case.
- **Un compte de profils avant lancement** : aujourd'hui chaque essai crée une recherche persistée
  pour découvrir son volume après coup.
- **`UiInlineSelect` et `UiPlacesAutocomplete` en utilitaires Tailwind**, pour retirer les deux
  derniers `<style scoped>` du kit.
