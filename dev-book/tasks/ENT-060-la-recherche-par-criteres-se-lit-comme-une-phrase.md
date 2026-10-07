# TASK ENT-060 — entreprise : la recherche par critères se lit comme une phrase

- **Créée le** : 2026-08-05.
- **Session** : `3db5bcc2-83f7-46f0-b9f9-de636b59f810`.
- **Statut** : ✅ fait le 2026-08-05 — la recherche par critères de l'accueil se lit comme une phrase
  en deux propositions, le nom s'écrit tout seul et se corrige, et « Rechercher » ne sort plus de
  l'écran : mesuré à zéro débordement sur six configurations, dont un écran de 720 px de haut, un
  téléphone, et l'arabe de droite à gauche.
- **Type** : Frontend espace entreprise — refonte d'un composant, plus une extension du contrat de
  création (le nom de la recherche se met à voyager).
- **Tests** : unitaires (vitest) sur le **calcul pur des étapes de frappe** du nom — c'est le seul
  code exécutable que la tâche produit, et il se teste sans DOM. `autoName()` est déjà couvert par
  `criteria.vitest.ts` et ne bouge pas. Le reste — mise en page, hauteur, sens de lecture — se prouve
  par **mesure DOM et captures** dans le navigateur connecté, comme `ENT-031` et `ENT-040` :
  la suite front ne couvre que le code pur (`webapp/vitest.config.ts`), jsdom ne calcule aucun
  layout.
- **Réf** : **`ENT-031` — amont dont l'arbitrage est RÉVISÉ ici** · `ENT-034` (le volume annoncé
  avant de lancer) · `ENT-030`, `ENT-032`, `ENT-045` (les champs actuels, qui disparaissent) ·
  tâche `159` (« le nom n'est pas envoyé à la création » — **révisée ici**) · **`ENT-059`,
  dépendance de calendrier, portée par une autre session** ·
  [FiltersPrimary.vue](../../webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue) ·
  [index.vue](../../webapp/spaces/entreprise/app/pages/index.vue) ·
  [Console.vue](../../webapp/spaces/entreprise/app/components/search/Console.vue) ·
  [criteria.ts](../../webapp/services/search/criteria.ts) — `autoName()` ·
  [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) § Formes.

## Contexte

Sur l'accueil de l'espace entreprise, le recruteur décrit qui il cherche dans une carte de quatre
champs étiquetés : intitulé de poste, années d'expérience, niveau d'études, mots-clés. Cette carte a
deux défauts, et le second explique pourquoi on ne se contente pas de la retoucher.

**Le bouton « Rechercher » sort de l'écran.** La coque de l'espace est bornée à la fenêtre
(`h-dvh`, `overflow-hidden`) et la carte, elle, prend la hauteur de son contenu sans jamais accepter
de rétrécir. Mesuré : **en dessous de 740 px de hauteur de fenêtre, l'action principale de l'écran
passe sous la ligne de flottaison** et n'est atteignable qu'en faisant défiler une zone qui emporte
aussi le bouton de retour. Un portable courant en 768 px de haut est déjà concerné.

**Et la carte n'apprend rien à celui qui la remplit.** Quatre étiquettes empilées disent quels champs
existent ; elles ne disent pas ce qu'on est en train de demander. Le recruteur remplit un
formulaire, il ne formule pas une recherche — et il ne relit nulle part ce qu'il a demandé avant de
cliquer.

Un troisième défaut, plus discret, se répare en même temps : **le niveau d'études ment**. Le serveur
filtre en `EducationLevel::atLeast()` — demander Master ramène aussi les ingénieurs et les docteurs.
L'écran affiche « Master » tout court, sans jamais dire que c'est un plancher.

## Périmètre

La carte de recherche par critères de **l'accueil** devient une **phrase que l'on complète**, en deux
propositions et un pied :

```
┌──────────────────────────────────────────────────────────────────────┐
│                                                                      │
│  Je cherche un profil de [Développeur Symfony] avec [5 ans ▾]        │  ① proposition 1
│  d'expérience,                                                       │     — ne se coupe JAMAIS
│  niveau [Master + ▾]                                                 │  ② proposition 2
│                                                                      │
│  QUI MAÎTRISE L'UN DE CES MOTS-CLÉS                                  │  ③ les mots-clés
│  ⬚ Symfony ×  ⬚ API Platform ×  ⬚ Docker ×   ( + mot-clé )           │
│  ─────────────────────────────────────────────────────────────────   │
│  RECHERCHE EN COURS · AUTO                                           │  ④ le pied
│  Développeur Symfony · 5 ans · Master +1      229 profils [Rechercher]│
└──────────────────────────────────────────────────────────────────────┘
```

1. **La phrase.** Le libellé de chaque champ disparaît : la grammaire le remplace. « avec 5 ans
   d'expérience » n'a besoin d'aucun titre au-dessus de lui.
2. **Les valeurs sont des mots de la phrase** — soulignés d'un filet d'accent, sans cadre. Le métier
   se saisit et se complète ; l'expérience et le niveau se choisissent dans une liste.
3. **Le nom de la recherche devient un champ**, posé dans le pied. Il se compose tout seul depuis
   les critères et se réécrit à chaque changement ; on peut le corriger.
4. **Le nom voyage à la création** — mais seulement s'il a été corrigé à la main.
5. **Le « Retour aux recherches » monte dans la gouttière**, dans l'emplacement d'action qu'elle
   expose déjà et qui est vide sur cet écran.
6. **La colonne « Ajuster les critères » de la Visionneuse garde sa forme actuelle** : mêmes champs
   étiquetés, empilés, dans sa colonne étroite qui défile déjà.

## Hors périmètre

- **La recherche assistée** (la phrase en langage naturel) ne reçoit pas le nom et ne change pas de
  forme. Écart assumé entre les deux moteurs, nommé et non oublié.
- **Plusieurs métiers dans une même recherche.** Le formulaire n'en accepte qu'un, et cela ne change
  pas ici : ce serait une fonctionnalité, touchant le service de critères et probablement l'API.
- **Le renommage après création**, qui existe déjà sur l'écran de la recherche.
- **La grille à deux colonnes** explorée en cours de cadrage : écartée au profit de la phrase.
- **La parité des trois langues sur les clés existantes** — c'est `ENT-059`, portée ailleurs.

## Décisions de cadrage — tranchées les 2026-08-05

### « Je cherche un profil de », et non « Je cherche un »

Une phrase impose un article, un article impose un genre, et **nous n'avons pas le genre du métier**.
Le vocabulaire contient « Assistante de direction » et « Sage-femme » : « Je cherche un Assistante de
direction » s'afficherait en gros, en haut de l'écran principal. L'article s'accorde donc avec
« profil », qui ne change jamais, et l'intitulé devient une précision qui suit.

Deux mots de plus contre zéro faute d'accord, sans aucune donnée supplémentaire à aller chercher.

### La première proposition ne se coupe jamais, et la largeur en découle

« Je cherche un profil de … avec … d'expérience, » est **une ligne par construction**, pas par hasard
de largeur : « niveau … » ouvre la seconde quelle que soit la fenêtre ou la langue. Les deux
propositions sont deux blocs, pas un paragraphe qui se coupe où il peut.

**Pourquoi ce n'est pas cosmétique** : laissée au retour à la ligne automatique, la coupure se
déplaçait à chaque caractère tapé dans le champ métier — la phrase se réorganisait sous les yeux
pendant la frappe.

Conséquence mesurée : le texte de la première proposition réclame **≈ 983 px**, soit une carte
d'environ **1 056 px**. La carte est donc large et basse, et non compacte. C'est une conséquence, pas
un choix esthétique — et une proposition qui se coupe au milieu était précisément le reproche.

### Le vide autour de la carte est assumé

La carte fait environ **294 px de haut** sur un écran qui en offre 918 : elle est centrée dans ce qui
reste, et le fond se voit tout autour. Un écran de départ où il n'y a qu'une chose à faire.

**Ce choix annule explicitement l'objectif « la carte remplit la hauteur » exploré en cours de
cadrage.** Les deux directions ne poursuivaient pas le même but ; c'est celle-ci qui est retenue.

### `ENT-031` est révisée, et il faut le dire

`ENT-031` avait borné le contenu à 792 px et fait cesser à la carte de remplir la hauteur, en
acceptant que « le fond reste vide sous la carte ». Cette tâche **conserve l'intention** — un champ
ne doit pas s'étirer sur 1 900 px — mais **change le moyen** : ce n'est plus une borne sur une pile
de champs, c'est une phrase dont la longueur fixe la largeur.

Ce qui tombe avec : **le curseur d'expérience et les six étiquettes de niveau d'études**, remplacés
par deux listes. Le travail de `ENT-031` sur la proximité du curseur et de sa valeur lue disparaît
donc — c'est assumé, et écrit ici pour que personne ne le redécouvre en lisant le diff.

### Le niveau d'études s'affiche « Master + »

Le serveur filtre en `atLeast`. Le « + » n'est pas décoratif : il dit que le niveau choisi est un
**plancher**. Sans lui, l'écran laisse croire à une sélection exacte.

### La forme suit la charte, pas la maquette

Rayon **8 px** (`rounded-lg`) partout — cartes, boutons, **badges compris**, y compris les pastilles
de mots-clés et le témoin du nom. `border-radius: 999px` reste réservé aux avatars et aux petites
pastilles, comme la croix qui retire un mot-clé. **Aucune bordure, aucune ombre portée** sur la
carte : la charte veut des surfaces résolument plates. Corps de texte sur l'échelle typographique
fermée.

L'échelle des rayons est **fermée à 6, 8 et 12 px, « sans valeurs sur mesure »** : aucun rayon
intermédiaire ne doit apparaître dans le diff.

### Le nom ne part au serveur que s'il a été corrigé

| Ce que fait le recruteur | Ce qui part | État côté serveur |
| --- | --- | --- |
| Il ne touche pas au nom | `{ criteria }` | `isAutoNamed` reste vrai — le nom suivra les critères |
| Il écrit dans le champ | `{ criteria, name }` | `isAutoNamed` passe à faux — le nom est figé |
| Il vide le champ | `{ criteria }` | l'auto-nommage se rallume |

**Cela révise la tâche `159`**, qui avait décidé que le nom n'était pas envoyé à la création. Le
relais accepte déjà `name` ; c'est `create()` côté composable qui doit le laisser passer.

Persister le nom **composé** désactiverait l'auto-nommage et figerait un nom qui mentirait au premier
critère changé — c'est exactement ce que le commentaire d'`autoName()` interdit.

### Le champ métier vit dans l'espace entreprise, pas dans le socle

Un mot souligné qui s'élargit avec ce qu'on tape n'est pas un habillage d'`UiRemoteCombobox`, qui est
un champ encadré avec une liste flottante. Le nouveau composant vit donc dans
`spaces/entreprise/app/components/search/` et **réutilise l'appel d'auto-complétion existant**
(`useSuggestions`), sans toucher au socle.

Arbitrage assumé : si un autre espace en a besoin un jour, on le remontera à ce moment-là. Le faire
maintenant imposerait une tâche `WEB` sur une autre branche, livrée avant celle-ci.

### L'effet de frappe est porté

Le nom ne s'écrit pas, il se **réécrit** : retour arrière jusqu'au préfixe commun avec le nom
affiché, puis frappe de la suite, **durée totale bornée** quel que soit l'écart. Sans borne, un nom
long mettrait plusieurs secondes et on aurait fini de remplir la phrase avant qu'il ait fini de
s'écrire.

`UiTypedText` ne convient pas : il tape une fois au montage, en deux lignes, pour l'accueil
d'inscription. Le calcul des étapes est **du code pur**, donc testable.

## Règles et cas limites

- **Aucun texte utilisateur en dur.** La phrase est assemblée de **segments traduisibles**, jamais
  écrite d'un bloc : chaque morceau porte sa clé, et **l'ordre des morceaux appartient à la langue**.
  C'est la seule construction qui survive à l'arabe.
- **Isolation bidirectionnelle obligatoire** sur chaque valeur insérée dans la phrase (`<bdi>` ou
  `unicode-bidi: isolate`). Deux défauts réels, observés :
  - « Master + » devient « + Master » en arabe, et le « + » porte tout le sens du filtre ;
  - l'intitulé de poste vient d'un vocabulaire en caractères latins et forme un îlot de direction
    opposée au milieu d'une ligne arabe.
  Le dépôt n'utilise l'isolation nulle part aujourd'hui : ce sera un premier usage.
- **L'expérience est facultative**, et sa valeur basse porte le « peu importe ». La proposition doit
  rester grammaticale dans cet état — pas de « avec peu importe d'expérience » qui se lise mal.
- **Le niveau d'études est facultatif** de la même façon.
- **Reposer le même choix le retire** : un niveau sélectionné par erreur doit pouvoir être enlevé.
- **Sans aucun critère**, le lancement est fermé, le nom est vide et son gabarit affiche son
  invitation. **Aucun compteur n'est peint en accent** : un « 0 » se lirait comme un résultat alors
  que rien n'a été demandé.
- **Le compteur porte l'accent sur le NOMBRE, pas sur l'unité** — deux aplats accent côte à côte, le
  compteur et le bouton, se disputeraient le regard. Le volume reste annoncé en `role="status"` :
  il est lu sans voler le focus au champ en cours.
- **Aucun compte ne ferme le bouton** : un volume est une aide, pas une garde, et une panne de
  comptage ne doit rien empêcher. Seule l'absence de critère le ferme.
- **Mobile-first.** Sous le seuil, les propositions se replient, les mises en ligne se déplient
  (libellé au-dessus de son contrôle), le pied s'empile, et la carte **reprend sa hauteur naturelle** :
  « remplir l'écran » n'a aucun sens là où il ne reste rien à remplir, et un formulaire enfermé dans
  un défilement interne se pilote mal, clavier ouvert.
- **Les deux thèmes**, clair et sombre, avec leurs deux accents distincts.
- **Accessibilité** : chaque valeur de la phrase reste un contrôle nommé — un `<label>` lié, visible
  ou `sr-only`. Une liste déroulante doit se distinguer d'un champ de saisie autrement que par la
  couleur. L'effet de frappe est purement visuel : il ne doit pas faire ré-annoncer le nom à chaque
  caractère, et **`prefers-reduced-motion` pose la valeur d'un coup**.
- **La console de la Visionneuse ne rétrécit pas** — règle héritée de `ENT-031`, à vérifier
  explicitement.
- **Aucun changement de comportement du moteur** : les mêmes critères produisent la même recherche.

### Les paliers d'expérience sont arrêtés

*peu importe · 1 · 2 · 3 · 5 · 7 · 10 · 15 ans et plus* — huit entrées, resserrées en bas où une
année compte et espacées en haut où elle ne discrimine plus. Le curseur continu de 0 à 15 disparaît
avec.

Les seize valeurs du curseur ont été écartées : une liste de seize lignes dans une phrase se
parcourt mal, et personne ne cherche « 11 ans et plus » plutôt que 10. Des paliers parlés
(« confirmé », « senior ») ont été écartés aussi — leur traduction en années deviendrait une règle
produit à arbitrer, pas un habillage.

### Les trois langues partent ensemble

Le français fait référence ; **l'anglais et l'arabe sont écrits dans le même commit** et marqués
*à relire par un locuteur*. Livrer le français seul est exactement la dérive que `ENT-059` existe
pour corriger — le français étant la langue de repli, rien ne se verrait à l'écran, et l'écart se
creuserait d'une tâche de plus.

## Dépendances

- **`ENT-059`, portée par une autre session** — elle comble 27 clés manquantes en anglais et en arabe
  et ajoute un **test de parité des trois fichiers de langue**. Cette tâche ajoute une dizaine de
  clés dans **les mêmes trois fichiers**, qui sont une ressource partagée.

  **Arbitré le 2026-08-05 : on n'attend pas.** Cette session-là avait cadré sans rien coder — aucun
  fichier modifié dans l'arbre — et nos clés sont des **ajouts**, écrits d'emblée dans les trois
  langues. La parité reste donc vraie, et le test d'`ENT-059` passera quand il arrivera.

  **Risque résiduel assumé** : les deux tâches vivent sur la même branche, donc le même index. Si
  l'autre session démarre pendant celle-ci, les trois `*.yaml` se disputent. Le staging se fait par
  chemins explicites, jamais par dossier.

- **Aval, hors de cette lane** : la maquette d'arbitrage vit dans l'atelier non versionné. Si elle
  doit devenir une référence citable, elle demande une **promotion vers `ui-ux-design/validate/`**,
  qui est une tâche `UX` sur `espace/design`. Rien ici ne la cite, et rien ici ne peut l'écrire.

## Documentation impactée

- [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)
  — **mis à jour** : le schéma du parcours opposait « par critères, ou **en une phrase** », où
  « phrase » désignait le moteur en langage naturel. Le moteur par critères devenant lui aussi une
  phrase, l'opposition devenait fausse ; elle dit maintenant « ou en langage naturel », le
  vocabulaire déjà employé plus haut dans le même document.
- [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md) — **mis à jour** :
  `max-w-sentence` rejoint la liste des gabarits, que cette page énumère.
- **Aucune page de `docs/` sur la forme visuelle** : cette carte applique la charte, elle n'en change
  aucune règle.
- **Le cadrage client** (`dev-book/cadrage/entreprise/`) décrit le renommage **après** création ;
  nommer **avant** est un moment nouveau. Cela appartient à `espace-outillage` : **nommé ici, écrit
  ailleurs.**

## Définition de terminé

- [x] La recherche par critères de l'accueil se lit comme une phrase, en deux propositions et un pied.
- [x] La première proposition va jusqu'à « d'expérience » et **ne se coupe jamais**, quelle que soit
      la longueur du métier saisi.
- [x] Le niveau d'études affiche son « + » et dit donc qu'il est un plancher.
- [x] Le nom se compose depuis les critères, se réécrit à chaque changement, et se corrige.
- [x] Le nom part au serveur **seulement** s'il a été corrigé à la main ; le vider rallume
      l'auto-nommage.
- [x] Le « Retour aux recherches » est dans la gouttière.
- [x] « Rechercher » est visible **à toute hauteur de fenêtre réelle** — mesure DOM à l'appui, sur au
      moins trois hauteurs dont une inférieure à 740 px.
- [x] La colonne « Ajuster les critères » de la Visionneuse est **inchangée**, vérifié à l'écran.
- [x] Chaque valeur insérée dans la phrase est isolée bidirectionnellement ; « Master + » se lit
      correctement en arabe, capture à l'appui.
- [x] Les trois langues sont écrites ; **la parité est vérifiée en analysant les trois fichiers**, le
      test d'`ENT-059` n'existant pas encore — cf. *Écart au cadrage* ci-dessous.
- [x] Le calcul des étapes de frappe est couvert en vitest ; `prefers-reduced-motion` pose la valeur
      d'un coup.
- [x] Aucun rayon hors de l'échelle 6 / 8 / 12 px dans le diff ; aucune bordure ni ombre sur la carte.
- [x] Captures dans les deux thèmes, sur écran large, sur écran court et sous `sm`.
- [x] Les gates **impactées** sont vertes : lint, format, type-check, conventions, tests front et
      contrats — `make webapp-quality` en une passe, code de sortie 0.

## Écart au cadrage

**La parité des langues n'est pas prouvée par un test, mais par une analyse.** La carte annonçait
« le test de parité d'`ENT-059` est vert » ; cette tâche-là n'est pas commitée, son test n'existe
donc pas. La parité des dix-neuf clés ajoutées a été vérifiée en **analysant les trois fichiers
YAML** et en comparant les jeux de clés — même méthode, sans le filet permanent. Ce filet reste dû
par `ENT-059`.

## Vérification

**Mesuré dans le navigateur, connecté** (compte `owner@demo.showcase-recruiter-webapp-saas.example`), sur six configurations :

| | Carte | Débordement | Défilement page | « Rechercher » visible |
| --- | --- | --- | --- | --- |
| Bureau clair 1920×1000 | 1056×299 | **0** | **0** | **oui** |
| Bureau sombre 1920×1000 | 1056×299 | **0** | **0** | **oui** |
| Écran court 1366×720 | 1056×291 | **0** | **0** | **oui** |
| Téléphone 390×780 | 374×461 | **0** | **0** | **oui** |
| Anglais 1920×1000 | 1056×299 | **0** | **0** | **oui** |
| Arabe, droite à gauche | 1056×299 | **0** | **0** | **oui** |

Deux lignes de phrase dans les six cas. Pour mémoire, le gabarit précédent **perdait son bouton en
dessous de 740 px** de hauteur de fenêtre.

**Le nom ne voyage que s'il est corrigé** — requête réellement observée à la création :

```
affiché : « Développeur »          →  POST /api/searches { criteria: [...], name: "" }
```

L'auto-nommage reste donc actif côté serveur, et la recherche continuera de se renommer quand on
ajustera ses critères.

**L'arabe.** `dir="rtl"`, la phrase se lit de droite à gauche, « البكالوريا **+** » place le « + »
du bon côté et « Développeur Symfony » reste un îlot latin à sa place. C'était le défaut redouté :
l'isolation bidirectionnelle le règle.

**Le compteur**, sur une recherche qui ramène des profils : « **293** profils », nombre en
`rgb(15, 75, 55)` — l'accent du thème clair — et en graisse 700, unité en gris.

**La console de la Visionneuse est intacte** : onglet « Classiques » ouvert, carte de **355 px**,
contenu de **307 px**, et les quatre champs étiquetés toujours là — *Intitulé de poste, Années
d'expérience, Niveau d'études, Mots-clés*. La phrase n'y est pas.

**Gates, en une passe** — `make webapp-quality`, **code de sortie 0** :

| Gate | Sortie lue |
| --- | --- |
| lint | 18 avertissements, **0 erreur** — tous préexistants, dans d'autres fichiers |
| format | *All matched files use Prettier code style* |
| type-check | vert |
| conventions | **13 règles, aucune violation** |
| vitest | **20 fichiers, 269 tests**, dont 12 neufs |
| contrats front↔API | aucune dérive : les types générés correspondent à l'OpenAPI courant |

`api/` n'est pas touché : aucune gate API ne s'applique, et `api/openapi.json` n'avait pas à être
régénéré.

### Quatre défauts que seule la capture a montrés

Aucun n'aurait été vu par les gates, et c'est la raison d'être de cette étape.

1. **Le champ « 1 an » réservait 288 px** et traînait un filet vide. Un `<input>` vaut vingt
   caractères par défaut, et cette largeur intrinsèque écrasait le gabarit. Corrigé en retirant le
   champ du calcul de largeur → **173 px**.
2. **« Symfony » et « avec » se touchaient** : Vue supprime les blancs entre deux éléments.
   L'espacement est passé **dans les traductions**, ce qui est sa place — le français sépare, l'arabe
   colle sa virgule.
3. **Le bouton affichait « + + mot-clé »** : le composant du kit pose déjà son « + ».
4. **La recette partait en anglais** faute de langue forcée — ce qui a fait vérifier les traductions
   anglaises sans le prévoir.

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `search/Sentence.vue` **(+)** | La phrase : deux propositions, les mots-clés, le pied. Assemble le kit maison, ne duplique rien. |
| `search/SentenceName.vue` **(+)** | Le nom qui s'écrit seul et se corrige ; bascule auto ↔ manuel. |
| `services/search/typewriter.ts` **(+)** | Les étapes d'une réécriture et leur cadence — pur, sans DOM. |
| `services/search/typewriter.vitest.ts` **(+)** | 12 tests : préfixe commun préservé, arrivée exacte, budget de temps tenu. |
| `pages/index.vue` **(M)** | Monte la phrase, remonte le « Retour » dans la gouttière, laisse passer le nom. |
| `composables/useSearches.ts` **(M)** | `create()` accepte un nom et ne l'envoie que s'il est saisi. |
| `base/i18n/locales/{fr,en,ar}.yaml` **(M)** | 19 clés `search.sentence.*` par langue ; l'espacement de la phrase y est porté. |
| `base/app/assets/css/sizing.css` **(M)** | Palier `--container-sentence` — une largeur dictée par la grammaire, pas par le confort de lecture. |
| `docs/…/4.design/design-system.md` **(M)** | `max-w-sentence` rejoint la liste des gabarits. |

## Reste à faire (hors périmètre)

- **`FiltersPrimary` garde ses props `volume` et `submitDisabled`**, que plus personne ne lui passe :
  la console ne s'en sert pas. Dette nommée, pas traitée — les retirer toucherait un composant que
  cette tâche n'a aucune raison de modifier.
- **`UiRemoteCombobox` ignore « Échap » tant que sa liste est vide** : on ne peut pas fermer le
  panneau pendant qu'il charge, il se rouvre sous les doigts. Défaut du socle, **préexistant**,
  lane `WEB`.
- **Un accès direct à `/recherches/{uuid}`** laissait le voile de démarrage en place pendant la
  recette, là où le même écran atteint depuis l'accueil s'affiche. Observé sous forte charge machine,
  **non reproduit proprement** — à confirmer avant d'en faire une tâche.
- **La recherche assistée** ne reçoit pas le nom : écart entre les deux moteurs, assumé.
