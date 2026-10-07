# TASK ENT-042 — entreprise : les filtres secondaires sortent du langage naturel

- **Créée le** : 2026-08-03.
- **Session** : `cb761d33-c5ed-45b5-a3db-3340b185010f`.
- **Statut** : ✅ fait le 2026-08-04 — les douze filtres du moteur ont un formulaire, en cinq
  groupes, et posent de vrais critères sans effacer les autres.
- **Type** : Frontend espace entreprise — un formulaire qui manquait, et la couche pure qui le
  traduit en critères.
- **Tests** : le formulaire → critères et son retour deviennent des **fonctions pures** de
  `services/search/criteria.ts`, où la suite front les ramasse — chaque facette, les valeurs vides
  qui ne posent rien, et surtout le **va-et-vient** : un critère lu, réécrit puis relu doit être
  identique. C'est la classe de bug qu'aucune capture ne montre — un aller-retour qui perd une
  facette efface un filtre en silence. Précédents : `fromPrimaryForm` / `toPrimaryForm`,
  `describeCriterion` (`ENT-038`).
- **Réf** : **amont `ENT-041`** (sans son onglet, ce formulaire n'a pas d'endroit où vivre) ·
  [criteres_de_filtrage_profils.md](../cadrage/entreprise/criteres_de_filtrage_profils.md) — la liste
  canonique · `SearchCriteriaTranslator` côté API — la liste **réelle** ·
  `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-v23.html` — mockup témoin ·
  [services/README.md](../../webapp/services/README.md)

## Contexte

Le moteur sait filtrer sur **seize facettes**. Le formulaire n'en pose que **quatre** — poste,
mots-clés, expérience, études.

Les douze autres — présence au Maroc, ville de présence, mobilité, pied-à-terre, horizon de retour,
disponibilité, secteur visé, secteur d'expérience, fonction, langue, niveau de langue, pays actuel —
ne sont accessibles **que par la recherche en langage naturel**. Un recruteur qui veut « les profils
présents à Casablanca dans les trente jours » doit espérer que le modèle comprenne, plutôt que de
cocher deux cases.

**Ce sont les différenciants du produit qui sont les plus mal servis.** La présence physique au Maroc
au bon moment est la promesse de showcase-recruiter-webapp-saas ; c'est un filtre que le moteur applique depuis longtemps et
qu'aucun formulaire ne propose.

## Périmètre

- **L'onglet « Filtres secondaires »**, ouvert par `ENT-041`, reçoit **douze filtres** en **cinq
  groupes repliables** : Maroc, Projet de retour, Métier, Profil, Lieu.
- **Un choix par filtre** — listes déroulantes et interrupteurs, jamais de cases multiples.
- **`fromSecondaryForm` / `toSecondaryForm`** dans `services/search/criteria.ts`, avec leurs tests.
- **`mergeWithSecondary`**, sur le modèle de `mergeWithPrimary` : le formulaire ne réécrit que **ses**
  facettes et préserve les autres.
- **Un bouton « Appliquer »** rejoue la recherche une fois.
- **Les libellés** des douze filtres, de leurs groupes et de leurs valeurs, dans les trois langues.

## Hors périmètre

- **La multi-sélection**, qui suppose que le moteur relie plusieurs valeurs d'une même facette en OU.
  Il sait le faire pour les mots-clés et pour eux seuls. Voir *Décisions* et *Aval*.
- **La ville actuelle** et les **villes de mobilité souhaitées**, que le mockup propose mais que le
  moteur ne connaît pas — le document canonique les marque lui-même « à modéliser ». Les afficher
  donnerait des cases sans effet.
- **Les compteurs par facette** (« France 12 ») que le canonique décrit : ils supposent des agrégats
  que l'API n'expose pas.
- **La Table**, et **la recherche en langage naturel**, qui continue de poser ces mêmes facettes de
  son côté.

## Décisions de cadrage

### La liste vient du traducteur serveur, pas du mockup

Le mockup dessine neuf groupes de facettes ; le document canonique en décrit dix. La liste qui
compte est celle que `SearchCriteriaTranslator` sait appliquer, et elle en contient **douze** hors
filtres classiques :

| Groupe | Filtres |
| --- | --- |
| **Maroc** | présence sous N jours · ville de présence · mobile dans tout le Maroc · pied-à-terre |
| **Projet de retour** | horizon de retour · disponibilité |
| **Métier** | secteur visé · secteur d'expérience · fonction visée |
| **Profil** | langue · niveau de langue · profil vérifié |
| **Lieu** | pays actuel |

Prendre le mockup pour source aurait produit deux filtres inertes. Prendre le canonique en aurait
produit autant : il décrit ce qu'on **voudrait** filtrer, pas ce que le moteur applique.

### Un choix par filtre, parce que c'est ce que le moteur fait

Le moteur ne garde **qu'une valeur par facette** — la dernière posée écrase la précédente. Seuls les
mots-clés s'accumulent.

Le mockup coche pourtant plusieurs pays. Livré tel quel, le recruteur cocherait « France » puis
« Belgique » en croyant élargir, et n'obtiendrait que la Belgique — **sans que rien ne le lui dise**.
Un formulaire qui ment sur ce qu'il fait est pire qu'un formulaire pauvre.

Huit des douze filtres sont concernés : pays, ville de présence, secteur visé, secteur d'expérience,
fonction, langue, horizon, disponibilité. Les quatre autres sont des **seuils** ou des **oui/non** —
« présent sous trente jours » contient déjà « sous quinze », et cocher deux fois « vérifié » n'a
aucun sens.

### Cinq groupes repliables, et le Maroc en premier

Douze filtres dépliés font une colonne interminable dans deux cent quatre-vingts pixels. Repliés, les
cinq groupes tiennent dans une hauteur d'écran, et le recruteur ouvre ce qu'il cherche.

Le groupe **Maroc** vient en premier et **s'ouvre par défaut** : c'est la promesse du produit, et le
seul filtre qu'aucun concurrent ne propose.

### Le formulaire ne réécrit que ses propres facettes

`mergeWithSecondary` préserve tout ce qu'il ne possède pas — les critères classiques, et ceux qu'une
recherche née du langage naturel a posés et que ce formulaire ne sait pas porter.

Sans cette précaution, ajuster un pays effacerait en silence un intitulé de poste. `mergeWithPrimary`
existe pour exactement cette raison, et la même règle vaut ici.

### « Appliquer » plutôt qu'à chaque changement

Une recherche se compose : on pose trois filtres, puis on lance.

Rejouer à chaque case reconstruit la pile sous les yeux du recruteur et lui fait perdre le profil
qu'il lisait — sur un écran de tri, c'est le geste le plus coûteux qui soit. Le tiroir fonctionnait
déjà ainsi.

## Règles et cas limites

- **Une valeur vide ne pose aucun critère.** Un filtre « toutes » ou « peu importe » ne doit pas
  apparaître en puce comme un filtre actif qui ne filtre rien — le précédent est `experienceMin` à
  zéro.
- **Le va-et-vient est fidèle** : les critères d'une recherche remplissent le formulaire, et le
  formulaire les réécrit à l'identique s'il n'a rien changé.
- **Les énumérations passent par leur catalogue** — disponibilité, horizon, niveau de langue et
  secteur d'expérience ont déjà leurs libellés traduits. Aucun n'est recopié.
- **Les puces suivent** : un filtre secondaire posé apparaît dans « Recherche en cours » et s'y
  retire, comme les autres (`ENT-041`).
- **Recherche non modifiable** : l'onglet n'existe pas (`ENT-041`).
- **Aucun texte en dur**, `aria-label` compris ; trois langues.
- **Chaque champ porte son libellé**, et les groupes repliables annoncent leur état.

## Documentation impactée

- **[criteres_de_filtrage_profils.md](../cadrage/entreprise/criteres_de_filtrage_profils.md)** — le
  canonique classe les critères par priorité mais ne dit nulle part **lesquels ont un formulaire**.
  Il gagne cette distinction : ce que l'écran pose, et ce qui reste hors de portée faute d'être
  modélisé.
- **Vérifié, non impacté** : `services-standard.md` — la carte applique sa règle plutôt que de la
  changer, et la page n'inventorie pas les fonctions.

## Définition de terminé

- [x] Les douze filtres sont posables, et **seulement** eux — la liste vient du traducteur serveur,
      pas du mockup.
- [x] Chaque filtre n'accepte qu'une valeur ; le groupe Maroc s'ouvre par défaut.
- [x] Ajuster un filtre secondaire ne perd aucun autre critère — **vérifié sur l'API** après
      application, pas déduit.
- [x] Le va-et-vient formulaire → critères → formulaire est fidèle, tenu par test.
- [x] Une valeur « peu importe » ne pose aucun critère, et un interrupteur décoché non plus.
- [x] « Appliquer » rejoue la recherche une fois — la pile ne se reconstruit pas à chaque case.
- [x] Les filtres posés apparaissent en puces et s'y retirent.
- [x] Captures : le groupe Maroc ouvert, et la recherche filtrée sur « profil vérifié » qui tombe de
      27 profils à 1. **Réserve** : les captures sont en thème clair — le sombre n'a pas été repris,
      les composants employés étant ceux, déjà éprouvés, du formulaire classique.
- [x] Le canonique dit quels critères ont un formulaire, et lesquels n'en ont pas.

## Vérification

**96 tests verts** sur `services/search/` — sortie lue le 2026-08-04 —, dont les **8 nouveaux** des
filtres secondaires. Le plus important est le **va-et-vient** : relire des critères puis les
réécrire sans rien toucher rend exactement les mêmes. Une facette perdue là effacerait un filtre en
silence, et aucune capture ne le montrerait.

**Le filtre agit, mesuré de bout en bout.** Poser « Profil vérifié » depuis l'onglet fait passer la
recherche de **27 profils à 1**.

**Rien n'est perdu au passage**, et c'est le risque principal de ce formulaire. Après application, la
recherche interrogée directement sur l'API porte :

| Facette | Valeur |
| --- | --- |
| `experienceMin` | 1 |
| `educationLevel` | LICENCE |
| `q` | vue |
| **`star`** | **true** |

Les trois critères classiques ont survécu à l'écriture des secondaires — `mergeWithSecondary` ne
réécrit que ses douze facettes.

**Le critère fait le tour complet** : il apparaît en puce retirable dans le premier onglet, et dans
la confrontation au profil — « ✓ Profil vérifié · Oui · Trouvé ».

**Une fausse alerte, levée avant de conclure.** Une capture semblait montrer l'onglet « En cours »
actif avec le contenu des filtres. Sondage des trois bascules : le mécanisme est sain, c'était un
rendu capturé trop tôt après le rejeu de la recherche.

## Livré

- `webapp/spaces/entreprise/app/components/search/FiltersSecondary.vue` **(+)** — les douze filtres,
  cinq groupes repliables, Maroc ouvert.
- `webapp/services/search/criteria.ts` **(M)** — `SecondarySearchForm`, `fromSecondaryForm`,
  `toSecondaryForm`, `mergeWithSecondary`, et le libellé des booléens sur les puces.
- `webapp/services/search/criteria.vitest.ts` **(M)** — 10 cas de plus.
- `webapp/spaces/entreprise/app/components/search/Console.vue` **(M)** — le troisième onglet cesse
  d'annoncer et livre.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` **(M)** — l'ajustement secondaire.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — groupes, « peu importe », fenêtres de
  présence, et le message d'attente qui n'a plus de lecteur.
- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` **(M)** — ce que l'écran pose, et ce
  qui reste hors de portée.

## Écart au cadrage

Trois divergences, dont une correction d'un défaut voisin.

### Quatre filtres sont des champs libres, pas des listes

La carte annonçait « listes déroulantes et interrupteurs ». Quatre filtres n'ont **aucun
référentiel** à proposer : le secteur visé, la fonction visée, la langue et la ville de présence au
Maroc sont des chaînes libres en base — « Informatique », « Développeur » — et non des
énumérations.

Ils reçoivent donc un champ de saisie avec un exemple en filigrane. Inventer une liste fermée aurait
produit des valeurs qui ne correspondent à rien dans les profils. Le secteur d'**expérience**, lui,
est bien une énumération du contrat : il garde sa liste, alimentée par le référentiel existant.

### Un booléen se lit par son libellé, pas par « Oui »

Défaut constaté à l'écran, et qui préexistait : la puce d'un critère booléen affichait « **Oui** » —
sans dire de quoi. Personne ne l'avait vu parce qu'aucun formulaire ne posait de booléen jusqu'ici ;
cette carte les rend posables, donc visibles.

Sur une puce et dans le nom automatique d'une recherche, un booléen rend désormais son **libellé** :
« Profil vérifié ». Le bloc de confrontation garde le couple libellé + valeur, puisqu'il a une
colonne pour chacun.

### `function` devient `jobFunction` dans le formulaire

La facette serveur s'appelle `function`, qui est un **mot réservé** en JavaScript et ne peut pas
nommer une propriété manipulée en `v-model`. Le formulaire l'appelle `jobFunction` ; la traduction
en critère rétablit `function`, et le va-et-vient le prouve.

## Aval

- **La multi-sélection** — huit de ces filtres gagneraient à accepter plusieurs valeurs reliées en
  OU. Le moteur sait déjà le faire pour les mots-clés ; l'étendre est une tâche `espace/api`, et le
  formulaire changera de forme après elle.
- **La ville actuelle et les villes de mobilité**, à modéliser côté données avant tout écran.
- **Les compteurs par facette**, qui demandent des agrégats côté API.
