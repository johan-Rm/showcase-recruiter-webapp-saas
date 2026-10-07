# TASK ENT-113 — entreprise : le parcours s'affine sur le lieu et le poste du moment

- **Créée le** : 2026-08-13.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-13 — un quatrième volet « Métier et lieu » porte le pays de
  résidence en pastilles à compteur et l'interrupteur « métier exercé aujourd'hui », qui dit sous
  lui-même qu'il écarte aussi les profils non classés.
- **Type** : feat — deux affinages de plus dans la colonne de la Visionneuse.
- **Tests** : les deux facettes rejoignent la couche pure de `services/search/refine.ts` et sa suite
  `vitest` — chacune seule, leur combinaison avec les six existantes, le comptage facetté, et le
  cas qui compte le plus : un profil dont le **poste du moment n'a pas pu être établi** doit sortir
  du jeu quand l'affinage est posé, au même titre qu'un profil dont le métier est passé.
- **Réf** : **amont `ENT-110`** — le panneau, sa couche pure et ses trois volets ·
  `API-026` — la correspondance métier qui dit si le poste est actuel, et sa couverture de 51 % ·
  [criteres_de_filtrage_profils.md](../cadrage/entreprise/criteres_de_filtrage_profils.md) ·
  **aval `ENT-114`** — les affinages qui attendent l'API

## Contexte

Le panneau d'affinage ouvert par `ENT-110` porte six facettes, et **les six sont vides** : mesuré au
même moment, 3 candidats sur 3 899 renseignent leur présence au Maroc, leur horizon de retour, leur
disponibilité, leur pied-à-terre ou leur mobilité. Le panneau existe, il est juste, et il n'affine
rien — toutes ses options s'affichent à zéro et grisées sur une recherche réelle.

Deux données que la **fiche de profil porte déjà** changeraient cela :

- **le pays de résidence**, renseigné sur **99,5 %** du vivier — le champ le mieux rempli de tous ;
- **le poste du moment**, que chaque correspondance de métier porte déjà (`CURRENT` / `PAST`) et
  dont l'écran se sert pour l'afficher, sans qu'on puisse filtrer dessus.

Aucune des deux ne demande quoi que ce soit au serveur. C'est la différence avec `ENT-114`, qui
attend une tâche API.

## Périmètre

Un **quatrième volet, « Métier et lieu »**, sous les trois existants, portant :

| Affinage | Forme | Donnée de la carte |
| --- | --- | --- |
| Pays actuel | pastilles à compteur, les pays du jeu | `country` |
| Poste du moment | interrupteur — « occupe ce métier aujourd'hui » | `matches[].matchedWhen` |

Les pays offerts sont **ceux du jeu**, comme les villes de séjour : une liste fermée de pays
européens afficherait des options mortes sur une recherche qui n'en contient que trois.

## Hors périmètre

- **Les compétences**, bien que la fiche les porte et qu'elles couvrent 93 % du vivier : le
  formulaire classique les cherche déjà par mots-clés, et des centaines de valeurs distinctes n'ont
  pas de forme tenable en pastilles à compteur.
- **La ville de résidence** — la fiche la porte (`city`), mais elle n'est pas normalisée et manque
  sur trois profils sur dix. Un affinage y ramènerait des doublons d'orthographe.
- **Langue et secteur d'expérience** — ils attendent l'API : `ENT-114`.
- **L'accès sous 1024 px**, où la colonne entière reste masquée. Même report qu'`ENT-110`.

## Décisions de cadrage

**Le poste du moment écarte aussi ceux qu'on ne sait pas classer, et l'écran le dit.** Le serveur ne
résout le poste actuel que sur **la moitié du vivier** : quand le parcours est ambigu, il se tait
plutôt que de deviner, et la mention est alors absente. Traiter cette absence comme un « non »
serait faux ; la traiter comme un « oui » le serait aussi.

L'affinage retient donc **uniquement les `CURRENT` confirmés**, et le libellé porte cette réserve —
sans elle, un recruteur croit filtrer sur du certain et perd la moitié de son jeu sans comprendre
pourquoi. C'est le point le plus fragile de cette carte, et il se jugera à l'usage.

**Un quatrième volet plutôt que deux.** « Métier » et « Lieu » séparés se lisent mieux, mais cinq
volets dans une colonne de 280 px font une pile qu'on ne balaie plus. Les trois volets existants ne
bougent pas.

**Le pays est en pastilles, comme les villes.** Même forme, même règle : reposer l'option active la
retire, une option à zéro est désactivée.

## Règles et cas limites

- **ET avec les six affinages existants**, comme entre eux : chaque facette posée resserre.
- **Un interrupteur décoché ne pose rien** — il ne cherche pas « ceux qui ont changé de métier ».
- **Le comptage facetté vaut aussi pour ces deux-là** : l'option affiche ce qu'elle laisserait, tous
  les autres affinages appliqués.
- **Une recherche sans critère de métier** n'a aucune correspondance à interroger : l'interrupteur
  « poste du moment » n'a alors rien à dire et **ne s'affiche pas** — un filtre qui viderait tout
  sans raison lisible est pire qu'un filtre absent.
- **Plusieurs correspondances de métier** sur un même profil : il suffit qu'**une** soit `CURRENT`.
- **Aucun texte en dur**, et la réserve sur les indéterminés passe par une clé i18n comme le reste.

## Documentation impactée

- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` — la ligne « Affinages du parcours »
  du § *Ce que l'écran pose aujourd'hui* gagne ses deux entrées.
- Aucun autre déclencheur : pas de surface d'API, pas d'entité, pas de contrat front↔API touché.

## Ce que l'implémentation a tranché autrement

**Le volet s'appelle « Métier et lieu », et il est le quatrième — pas le dernier par défaut.** Rien
d'autre n'a bougé : les trois volets existants gardent leur ordre et leur contenu.

**La réserve est un `description` de l'interrupteur, pas un libellé rallongé.** Mise dans le libellé,
elle aurait fait trois lignes de gras dans une colonne de 280 px ; en dessous, en petit et en gris,
elle se lit sans disputer la place au geste.

**Le compte de l'interrupteur suit le comptage facetté comme les autres.** Constaté à l'écran : sur
41 logisticiens il annonce `(3)` ; en posant le pays « BE », il passe à `(1)` — un seul des cinq
Belges exerce encore ce métier.

## Vérification

Stack du worktree entreprise, navigateur piloté, sur une recherche « Logisticien » de **41 profils**
— la première dont un critère de métier permet d'afficher l'interrupteur.

**Les quatre volets, et le contenu du dernier :**

```
volets : 4
titres : Présence au Maroc · Projet de retour · Disponibilité · Métier et lieu
pays          : BE 5 · FR 35 · MA 1
interrupteur  : Métier exercé aujourd'hui (3)
```

Les trois pays et leurs comptes correspondent exactement à la base pour cette recherche :

```
 FR | 35
 BE |  5
 MA |  1
```

**Un pays posé resserre le parcours :**

```
rang avant    : profil 1 sur 41
rang après BE : profil 1 sur 5
pied          : 5 profils sur 41   ·   Tout retirer
```

Le volet prend son point accent, et l'interrupteur se recalcule de `(3)` à `(1)`.

**Tests** — `bun run vitest run services/search/refine.vitest.ts base/i18n/locales/parity.vitest.ts` :

```
 Test Files  2 passed (2)
      Tests  42 passed (42)
```

## Livré

- `webapp/services/search/refine.ts` **(M)** — les deux facettes, `refinableCountries`, et
  `carriesJobCriterion` qui décide si l'interrupteur a lieu d'être.
- `webapp/services/search/refine.vitest.ts` **(M)** — 12 tests de plus, dont le silence du serveur.
- `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` **(M)** — le quatrième volet.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — le volet, le libellé et sa réserve.
- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` **(M)** — les deux affinages, et la
  réserve sur le poste du moment, mesurée.

## Reste à faire (hors périmètre)

- **`ENT-114`** — langue et secteur d'expérience, qui attendent `API-044`.
- **L'accès sous 1024 px**, où la colonne entière reste masquée.

## Ce que la preuve a montré, et qui se jugera à l'usage

**Sur 41 logisticiens, 3 ont un poste du moment confirmé.** Le filtre en écarterait 38, et la grande
majorité **par ignorance** — non parce qu'ils ont changé de métier, mais parce que le serveur n'a pas
tranché. L'ordre de grandeur est plus sévère que la moitié annoncée par `API-026` au cadrage.

Le libellé porte la réserve, et c'est tout ce que le code peut faire. Si l'usage montre que
l'interrupteur dessert plus qu'il ne sert, la réponse est en amont — améliorer la résolution du poste
actuel — pas dans cet écran.

## Définition de terminé

- [x] Un quatrième volet « Métier et lieu » porte le pays actuel et le poste du moment.
- [x] Les pays offerts sont ceux du jeu, chacun avec son compte ; à zéro, l'option est désactivée.
- [x] L'interrupteur « poste du moment » ne garde que les correspondances confirmées comme
      actuelles, et son libellé dit qu'il écarte aussi les profils non déterminés.
- [x] L'interrupteur ne s'affiche pas sur une recherche sans critère de métier.
- [x] Les deux facettes se combinent avec les six existantes et suivent le comptage facetté.
- [x] La sélection et le comptage restent des fonctions pures de `services/search/refine.ts`,
      couvertes par leurs tests — y compris le cas du poste non déterminé.
- [x] Prouvé à l'écran sur une vraie recherche du vivier, où le pays, lui, n'est pas vide.
