# TASK WEB-015 — socle : la bulle d'aide cesse de sortir de l'écran

- **Créée le** : 2026-08-06.
- **Session** : `417ad4f5-232c-42c6-8257-f61c6e7c2f12`.
- **Statut** : ✅ fait le 2026-08-06 — la bulle se retourne au contact d'un bord et se rend hors du
  flux : **0 bulle sur 27 hors écran ou rognée**, aux quatre largeurs, là où 19 sur 27 l'étaient à
  390 px. Les trois modes d'ouverture sont démontrés un par un, et le rattachement accessible tient.
  Un écart au cadrage : la largeur **ne vient pas** de la bibliothèque, contrairement à ce que la
  carte annonçait — cf. *Décisions de cadrage*.
- **Type** : fix — un défaut de placement visible à l'écran, corrigé en confiant le positionnement à
  la bibliothèque plutôt qu'à des classes écrites à la main.
- **Tests** : `sans objet côté unitaire` — la carte **retire** du code exécutable au lieu d'en
  produire : la petite machine à états écrite à la main (ouverture, fermeture au blur, à la sortie
  souris, à Échap) est remplacée par celle de la bibliothèque. Il ne reste aucune fonction pure à
  couvrir, et jsdom ne calcule aucune mise en page — un test de composant ne verrait ni un
  débordement, ni un rognage. La preuve est une **mesure dans le navigateur** : le bord droit de
  chaque bulle comparé à la fenêtre **et** à son plus proche ancêtre qui rogne, aux quatre largeurs
  (390, 768, 1280, 1920), avant et après ; plus une vérification à la main des **trois modes
  d'ouverture** — souris, clavier, doigt.
- **Réf** : **`ADM-017` — parallèle, non bloquante** (la reprise de l'écran « État de la base », qui
  porte 27 de ces bulles et héritera de la réparation sans l'attendre) ·
  [ui/README.md](../../webapp/base/app/components/ui/README.md) ·
  [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md) § *Ce qu'on n'écrit pas
  dans un composant*, interdit n°3 · [sizing.css](../../webapp/base/app/assets/css/sizing.css) ·
  fichiers touchés : [base/app/components/ui/InfoHint.vue](../../webapp/base/app/components/ui/InfoHint.vue),
  et à vérifier sans forcément modifier :
  [artefacts/ux-kit.vue](../../webapp/spaces/public/app/pages/artefacts/ux-kit.vue),
  [admin/app/pages/vivier.vue](../../webapp/spaces/admin/app/pages/vivier.vue).

## Contexte

Le kit maison porte une **bulle d'aide** : un petit rond « i » à côté d'un libellé, qui ouvre une
courte définition. Elle n'a que deux consommateurs aujourd'hui — l'écran admin « État de la base »,
où elle apparaît 27 fois, et la galerie du kit sur l'espace public.

**Elle sort de l'écran, et la mesure est sans appel.** Relevé le 2026-08-06 sur la stack du worktree
admin, en ouvrant les 27 bulles une par une :

| Largeur de fenêtre | Bulles coupées | Débordement maximum |
| --- | --- | --- |
| 390 px | **19 sur 27** | 200 px |
| 1 280 px | 5 sur 27 | 182 px |
| 1 920 px | 2 sur 27 | 35 px |

Sur téléphone, la bulle mesure **240 px de large dans une carte de 183 px** : même celles de la
colonne de gauche débordent — de 9 px —, et celles de droite se réduisent à trois lettres lisibles.

### Deux causes, et il faut les deux pour comprendre

**La bulle ne sait pas se retourner.** Elle est ancrée au bord **gauche** de son icône (`left-0`) et
pousse vers la droite jusqu'à 240 px (`max-w-[15rem]`), **sans jamais regarder si la place existe**.
Aucune détection de bord, aucun repli.

**Et elle est enfermée dans une zone qui défile.** La bulle est en position absolue *à l'intérieur*
du contenu. Or ce contenu vit dans un conteneur défilant — `dashboard-body` sous 1 024 px, la zone
principale de la coque au-dessus. En CSS, un conteneur qui défile verticalement **rogne aussi
horizontalement** : `overflow-y: auto` fait calculer `overflow-x: auto`. Mesuré : le bord de rognage
est à 382 px sur téléphone, à 1 271 px sur un portable de 1 280.

**Conséquence pratique** : retourner la bulle ne suffirait pas. Une bulle un peu large resterait
rognée par ce conteneur. Il faut aussi qu'elle **sorte du flux**.

### Et elle enfreint l'échelle du socle

Six paliers bruts de Tailwind et une valeur arbitraire, alors que l'interdit n°3 les nomme depuis
`ADM-015` : `size-4`, `size-3.5`, `mt-1.5`, `p-2.5`, `text-xs`, et **`max-w-[15rem]`** — cette
dernière étant **exactement la cause du débordement**. `mt-1.5` est de surcroît une **marge
individuelle**, que `sizing.css` rejette en toutes lettres : « espacer par le conteneur (`gap`), pas
par des marges individuelles ».

## Périmètre

- **Confier le placement à `UPopover`** (Nuxt UI 4.10, déjà installé), qui traite les deux causes
  sans une ligne de calcul de notre côté :
  - il porte `collisionPadding: 8` par défaut — il **se retourne et se décale** au contact d'un bord ;
  - il se rend **hors du flux, à la racine du document** (`portal: true` par défaut) — donc aucun
    conteneur défilant ne peut plus le rogner.
- **Préserver les trois modes d'ouverture** — souris, clavier, doigt. Voir *Règles et cas limites* :
  c'est la contrainte qui gouverne le choix du mode.
- **Rebrancher le rythme du composant sur les jetons du socle**, selon le précédent de l'espace
  entreprise (cf. *Décisions de cadrage*). Les six paliers bruts et la valeur arbitraire disparaissent.
- **Conserver l'API du composant** — une seule propriété, le texte déjà résolu. Ses deux
  consommateurs ne doivent avoir aucune ligne à changer.
- **Vérifier les deux consommateurs** après coup : la galerie du kit et l'écran admin.

## Hors périmètre

- **La reprise de l'écran « État de la base »** — c'est `ADM-017`, sur la lane admin. Les deux
  cartes se complètent mais ne partagent pas un commit, et aucune n'attend l'autre.
- **Les autres composants du kit maison** — rien n'indique qu'ils divergent, et rien ne l'a mesuré.
- **Les paliers bruts du reste du socle** — 82 occurrences dans 17 fichiers, relevées le 2026-08-06.
  Sujet distinct, qui mérite son propre cadrage ; cette carte ne traite que le fichier qu'elle ouvre.
- **Remplacer la bulle par une infobulle de bibliothèque** (`UTooltip`) — écarté, voir ci-dessous.

## Décisions de cadrage

### `UPopover` et non `UTooltip`, et ce n'est pas un détail

Le réflexe serait `UTooltip` : c'est le composant qui porte le nom du besoin. Il est écarté, et le
commentaire en tête du fichier dit pourquoi mieux que n'importe quel argument nouveau — la bulle a
été écrite à la main pour s'ouvrir « au survol, au focus clavier **ET** au tap ; jamais hover-only,
inutilisable au tactile ». Une infobulle classique ne s'ouvre pas au doigt.

Or **le tactile est précisément la surface la plus abîmée** : 19 bulles sur 27 y sont coupées.
Réparer le débordement en cassant l'ouverture au doigt reviendrait à échanger un défaut visible
contre un défaut invisible, sur le même écran, pour les mêmes personnes.

`UPopover` offre ce que `UTooltip` offre — retournement aux bords, sortie du flux — **et** s'ouvre
au doigt, en mode clic comme en mode survol avec `enableTouch`. Le choix entre ces deux modes est
laissé à l'implémentation, sous la seule contrainte que les trois modes d'ouverture survivent et
soient **démontrés**, pas supposés.

### Le rythme se rebranche, il ne s'hérite pas

`nuxt-ui.css` ne rebranche que **les couleurs et le rayon** de la bibliothèque — rien sur
l'espacement ni la typographie. Adopter un composant sans plus ferait donc rentrer ses espacements
figés par la fenêtre, ce que l'interdit n°3 cherche justement à éviter.

Le dépôt a déjà résolu ce cas : `spaces/entreprise/app/pages/recherches/[uuid]/table.vue`, lignes
627-649, rebranche le rembourrage des cellules Nuxt UI sur `--spacing-cell-y` / `--spacing-cell-x`,
et son commentaire constate que la bibliothèque « laisse cohabiter `p-4` avec `px-cell-x` ». C'est ce
modèle qu'on suit — et c'est ce qui distingue cette carte d'un simple remplacement de composant.

### ÉCART AU CADRAGE — la bibliothèque ne borne pas la largeur, et le retournement ne suffit pas

Le cadrage écrivait que la valeur arbitraire `max-w-[15rem]` disparaîtrait « par construction,
la largeur venant de la bibliothèque ». **C'est faux, et la mesure l'a montré.** `UPopover` ne pose
aucune largeur maximale : il place, il ne dimensionne pas.

Premier essai, borné à la mesure de lecture du socle (`--container-readable`, ~620 px au corps de la
bulle) : correct de 768 px à 1 920, et **encore 16 bulles sur 27 hors écran à 390 px**. La leçon
tient en une phrase — **le retournement déplace, il ne rétrécit pas** : une bulle plus large que
l'écran déborde où qu'on la pose.

La largeur est donc bornée **deux fois** : par la mesure de lecture du socle, et par la place que la
bibliothèque a réellement trouvée après s'être retournée (`--reka-popper-available-width`). En CSS
local, parce qu'un `min()` de deux variables n'a pas d'écriture Tailwind qui ne soit pas une valeur
arbitraire — précisément ce qu'on retire. Aucune valeur n'est écrite : les deux bornes sont lues.

### Le socle plutôt que l'écran

L'autre voie était de remplacer l'usage **dans le seul écran admin**, sans toucher au kit : une
carte, une branche, un résultat plus rapide. Elle n'est pas retenue. Le composant serait resté cassé
pour le prochain qui s'en sert, et la galerie du kit aurait continué de l'exposer comme une
référence — c'est-à-dire de recommander un composant qui sort de l'écran.

## Règles et cas limites

- **Les trois modes d'ouverture sont la contrainte dure**, pas un confort : souris (survol), clavier
  (focus, et Échap ferme), doigt (tap). Chacun se démontre, aucun ne se déduit.
- **La fermeture ne doit pas piéger le focus.** L'implémentation actuelle n'installe **aucun
  écouteur global**, délibérément — c'est ce qui la rend sûre au rendu serveur. La bibliothèque a sa
  propre mécanique ; vérifier qu'aucun écouteur global n'est ajouté par nous, et que la règle CI
  `app-prefer-useeventlistener` reste satisfaite.
- **Rendu serveur.** Le composant sort désormais du flux (portail). Vérifier qu'il ne casse ni
  l'hydratation ni le rendu serveur — le socle est rendu côté serveur, et un portail mal posé
  produit une divergence entre le HTML servi et le DOM hydraté.
- **Le nom accessible ne bouge pas** : le bouton porte `aria-label` sur la clé `a11y.moreInfo`, et la
  bulle reste rattachée à son déclencheur pour un lecteur d'écran. Ne pas laisser la bibliothèque
  effacer ce rattachement.
- **Le libellé n'est jamais en dur** : le composant reçoit un texte **déjà résolu**, il ne traduit
  rien lui-même. Règle CI `app-no-hardcoded-text`.
- **Racine unique et marqueur** : le composant garde une seule racine portant sa classe marqueur,
  règle CI `components-root-marker`. Un portail rend son contenu ailleurs — vérifier que le marqueur
  reste bien sur la racine servie, et non emporté dans le portail.
- **Dégradation propre à préserver** : côté appelant, un libellé sans définition n'affiche **pas** de
  bulle du tout. Ce comportement vit chez l'appelant, mais l'implémentation ne doit pas le rendre
  impossible.
- **La galerie du kit annonce « Infobulles · UTooltip · UiInfoHint »** dans son titre de section. Si
  le composant cesse d'être une infobulle pour devenir un popover, ce titre devient trompeur : le
  corriger dans le même commit, ou dire pourquoi il reste juste.

## Documentation impactée

- **`webapp/base/app/components/ui/README.md`** — si, et seulement si, la convention du dossier
  change. Le remplacement d'une implémentation interne par un composant de bibliothèque ne change
  pas le contrat du kit ; ce qui pourrait le changer, c'est la **règle d'adoption** — « on prend le
  composant, on rebranche son rythme » —, aujourd'hui écrite nulle part et vivant seulement dans le
  code de l'espace entreprise. Si l'implémentation confirme que cette règle est générale, elle a sa
  place là, ou dans `design-system.md` § *Les composants*. À décider sur pièce, pas par avance.
- Aucune autre page. Cette carte **applique** l'interdit n°3, elle ne le modifie pas.

## Définition de terminé

- [x] Aucune bulle ne dépasse de la fenêtre ni n'est rognée par son conteneur, aux quatre largeurs —
      mesuré sur les 27 bulles de l'écran qui les porte, chiffres avant/après dans la carte.
- [x] Les trois modes d'ouverture sont démontrés : souris, clavier (avec Échap qui ferme), doigt.
- [x] Plus aucun palier brut ni valeur arbitraire dans le fichier ; le rythme est rebranché sur les
      jetons du socle.
- [x] L'API du composant est inchangée : ses deux consommateurs n'ont aucune ligne à modifier.
- [x] La galerie du kit rend la bulle correctement dans les deux thèmes, et son titre de section dit
      la vérité.
- [x] Aucune divergence d'hydratation au rendu serveur, constatée et non supposée.
- [x] La règle CI des conventions passe au vert sur le fichier touché.
- [x] Captures avant/après, dans les deux thèmes, sur la colonne la plus à droite — celle qui
      débordait.
- [x] La carte est à jour de ce qui a réellement été fait, y compris les écarts au cadrage.

## Vérification

Mesures relevées dans le navigateur, authentifié, sur la stack du worktree socle où l'espace admin a
été levé pour l'occasion — c'est le seul écran qui porte les 27 bulles. **La base de référence a été
remesurée sur cette stack avant le changement**, pas reprise de la mesure d'audit.

### Le débordement, bulle par bulle

| Largeur | Avant | Après |
| --- | --- | --- |
| 390 px | **19 sur 27** coupées, jusqu'à 200 px hors écran | **0 sur 27** — 27 ouvertes, 27 mesurées |
| 768 px | non mesuré à l'audit | **0 sur 27** |
| 1 280 px | 5 sur 27, jusqu'à 182 px | **0 sur 27** |
| 1 920 px | 2 sur 27, jusqu'à 35 px | **0 sur 27** |

Le débordement est mesuré sur les **quatre** bords, et contre le plus proche ancêtre qui rogne :
celui-ci n'existe plus, la bulle étant rendue à la racine du document.

> **Une mesure vide n'est pas une preuve.** Un premier passage annonçait « aucun débordement » à
> 1 280 px alors que **zéro bulle sur 27** s'était ouverte — la souris restait posée sur la
> précédente. Le script refuse désormais de conclure quand le compte des bulles ouvertes n'égale pas
> le total. C'est ce garde-fou qui a rendu les chiffres ci-dessus dignes de foi.

### Les trois modes d'ouverture, un par un

| Geste | Résultat |
| --- | --- |
| Souris — survol | ✔ ouvre |
| Clavier — focus | ✔ ouvre |
| Clavier — Échap | ✔ ferme |
| Doigt — tap (contexte tactile) | ✔ ouvre |

### Le rattachement accessible et le rythme

`aria-describedby` pointe vers un élément qui **existe** (`document.getElementById` le trouve),
porte `role="tooltip"`, et se situe hors de la coque — donc bien dans le portail. Le nom accessible
du bouton n'a pas bougé (`a11y.moreInfo`).

| | Écrit avant | Relevé après |
| --- | --- | --- |
| Rembourrage | `p-2.5` → 10 px, figé | **8,96 px** — `--spacing-element` |
| Corps | `text-xs` → 12 px, figé | **13 px** — `--text-caption` |
| Largeur maximale | `max-w-[15rem]` → 240 px | bornée par la mesure de lecture **et** la place disponible |

### Le second consommateur, et l'hydratation

**La galerie du kit** (espace public, `/artefacts/ux-kit`) : la bulle s'ouvre et reste entièrement à
l'écran dans les **deux thèmes**, couleurs correctement basculées — fond `#1a1a1a` / encre blanche en
sombre, fond `#ecf0ef` / encre `#070e08` en clair.

**Aucune divergence d'hydratation imputable à ce diff.** Deux avertissements apparaissent sur la
page — sur `AppBootShell` et `app.vue` —, et ils sont **pré-existants** : reproduits à l'identique
sur `/utilisateurs`, qui ne contient aucune bulle.

### Gates impactées

`bun run quality-conventions-check` → **14 règles, aucune violation** ✔ ·
`prettier --check` sur le fichier touché ✔.

## Livré

- `webapp/base/app/components/ui/InfoHint.vue` (M) — le placement passe à `UPopover`, en mode survol
  avec le tactile activé. Le **rattachement accessible reste écrit à la main** : une carte de survol
  n'en pose aucun, et le lien entre le bouton et sa définition aurait disparu pour un lecteur
  d'écran. Les six paliers bruts et la valeur arbitraire sont partis ; la largeur est bornée en CSS
  local par deux variables, sans qu'aucune valeur soit écrite.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (M) — § *Les composants*. La page affirmait que
  la bibliothèque est « branchée sur le nuancier et sur les tokens sémantiques » **sans jamais dire
  ce qui ne l'est pas** : c'est cette phrase incomplète qui fait supposer l'espacement couvert. Elle
  dit désormais que le pont porte les couleurs et le rayon, et rien d'autre, et elle écrit la règle
  d'adoption en deux temps — prendre le composant, rebrancher son rythme — avec ses trois
  précisions d'usage et les trois précédents qui la portent.

## Reste à faire (hors périmètre)

- **Les 82 paliers bruts du reste du socle**, dans 17 fichiers, relevés le 2026-08-06. Cette carte
  n'a traité que le fichier qu'elle ouvrait.
- **Le titre de section de la galerie** — « Infobulles · UTooltip · UiInfoHint » — n'a **pas** été
  touché : il reste juste. Le composant s'affiche toujours comme une infobulle et son contenu porte
  `role="tooltip"` ; seule sa mécanique interne a changé.
