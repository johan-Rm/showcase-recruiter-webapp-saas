# Design system

- Rôle : décrire **comment** la [charte de marque](./brand-guidelines.md) est implémentée dans
  `webapp/` — où vivent les tokens, comment les consommer, et ce qu'il est interdit d'écrire dans
  un composant.
- Related : [brand-guidelines.md](./brand-guidelines.md) (les valeurs et leur intention),
  [responsibility-boundaries.md](../2.architecture/responsibility-boundaries.md),
  [i18n.md](../2.architecture/i18n.md) (dark mode et RTL)

La charte dit **quoi** ; ce document dit **où** et **comment**. Une valeur nommée dans la charte a
exactement un fichier propriétaire ici, et un seul.

## La cascade à trois étages

Le système se lit sur trois étages. Seul le deuxième s'emploie dans un écran.

```
palette.css      ← le RÉSERVOIR : 2 échelles, 11 paliers. Aucun composant n'y touche.
      ↓
themes.css       ← les TOKENS SÉMANTIQUES : --bg, --surface, --accent… Les seuls à utiliser.
      ↓
nuxt-ui.css      ← le PONT : la bibliothèque tierce lit les mêmes tokens.
```

Un composant qui écrit `bg-slate-200` court-circuite l'étage du milieu : il ne suivra pas la
bascule clair / sombre, et le bug ne se verra que dans l'autre thème.

## Où vit quoi

Tout est sous `webapp/base/app/assets/css/`, chargé par un point d'entrée unique déclaré dans
`webapp/nuxt.config.ts`.

| Fichier           | Propriétaire de                                                                                    |
| ----------------- | -------------------------------------------------------------------------------------------------- |
| `main.css`        | Point d'entrée : imports, exposition des tokens à Tailwind, primitives de gabarit                  |
| `palette.css`     | Le nuancier ardoise et bleu                                                                        |
| `themes.css`      | Les tokens sémantiques, les couleurs d’état et la bascule clair / sombre                           |
| `nuxt-ui.css`     | Le remappage de la bibliothèque de composants sur le nuancier et les tokens                        |
| `sizing.css`      | Échelle typographique, rythme d'espacement, largeurs de gabarit, hauteurs de bande, palier `xwide` |
| `fonts.css`       | Les déclarations de fontes auto-hébergées                                                          |
| `transitions.css` | Durées, courbes et transitions nommées                                                             |

L'ordre des imports dans `main.css` n'est pas décoratif : `nuxt-ui.css` vient **après** la
bibliothèque et après `themes.css`, puisqu'il remappe l'une sur l'autre.

## Consommer un token

Les tokens sont exposés à Tailwind, qui en dérive des utilitaires. On écrit l'utilitaire, jamais la
variable ni la valeur.

| Famille     | Utilitaires                                                                                                                                                                                                                                  | Source       |
| ----------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------ |
| Couleur     | `bg-bg`, `bg-surface`, `bg-surface-soft`, `bg-dropdown`, `bg-accent`, `bg-accent-soft`, `text-text`, `text-muted`, `text-accent`, `text-on-accent`, `text-alert`, `text-verdict-met`, `text-verdict-missed`, `border-line`, `outline-accent` | `main.css`   |
| Typographie | `text-display`, `text-h1`, `text-h2`, `text-h3`, `text-body`, `text-small`, `text-caption`, `text-label`                                                                                                                                     | `sizing.css` |
| Fonte       | `font-sans` (Geist), `font-display` (Geist)                                                                                                                                                                                                  | `main.css`   |
| Espacement  | `gap-element`, `gap-block`, `p-pane`, `gap-section`, `p-block`, `px-gutter`, `px-cell-x`, `py-cell-y`…                                                                                                                                       | `sizing.css` |
| Gabarit     | `max-w-readable`, `max-w-form`, `max-w-sentence`, `max-w-panel`, `max-w-sheet`, `max-w-shell`, `w-nav`, `w-nav-rail`                                                                                                                         | `sizing.css` |
| Bande       | `min-h-(--band-gutter)`, `min-h-(--band-gutter-standby)`, `min-h-(--band-action)`                                                                                                                                                            | `sizing.css` |
| Grand écran | variante `xwide:`                                                                                                                                                                                                                            | `sizing.css` |
| Élévation   | `shadow-overlay`                                                                                                                                                                                                                             | `themes.css` |

**Un seul niveau d'élévation**, et il ne s'adresse qu'aux **surcouches** — menus, listes
déroulantes, tiroirs, modales. Tout ce qui est _posé sur_ la page reste plat : c'est le fond qui
découpe, jamais l'ombre (guide de marque, _Formes et élévation_). Sa valeur se redéclare en thème
sombre, où une ombre noire ne se verrait pas : c'est alors la surface plus claire qui sépare, et
l'ombre ne fait plus qu'ancrer le bord.

C'est le **seul** token exposé hors du bloc `@theme inline`, et le commentaire de `main.css` dit
pourquoi : `inline` demande à Tailwind de résoudre la valeur à la compilation, ce qu'il ne sait pas
faire pour une variable déclarée ailleurs — il abandonne alors l'utilitaire **en silence**. Mesuré :
la classe n'était pas générée du tout. Un futur token qui référence une variable suivra la même
écriture.

### Les bandes, seule famille consommée par la variable

Une **bande** est la barre fine et fixe d'une coque : elle ne défile pas, porte un repère et une
action, et tout ce qu'elle prend est pris à la zone de travail qui vit dessous. Le socle en connaît
trois — la gouttière, son mode veille, la barre d'actions.

Elle enfreint la règle de la ligne d'ouverture — _on écrit l'utilitaire, jamais la variable_ — et
c'est la seule. Tailwind dérive des utilitaires des familles qu'il connaît : `--text-*` donne les
corps, `--spacing-*` les espacements, `--container-*` donne `max-w-shell`. **Il n'a aucune famille
pour les hauteurs.** Le palier ne produit donc pas de nom, et se lit au point d'usage :
`min-h-(--band-gutter)`. L'écriture n'est pas un contournement maison — la bibliothèque de
composants emploie déjà la même pour sa propre hauteur d'en-tête.

Deux traits distinguent cette famille du reste du fichier, et aucun n'est accidentel :

- **Elle se mesure sur `vh`, pas sur `vw`.** Un corps de texte suit la place horizontale ; une bande
  coûte de la **hauteur**. Calculée sur la largeur, elle reste grasse sur un écran bas — une fenêtre
  de 600 px, un portable chargé de barres d'outils — pendant que le vertical manque.
- **Elle se pose en `min-height`, jamais en `height`.** Une hauteur imposée rogne son contenu en
  silence dès que le texte grandit : zoom du navigateur, traduction plus longue. Réservée, la bande
  grandit et prend sur ce qui défile en dessous — visible, donc corrigible.

Ne pas confondre `--band-gutter` avec `--spacing-gutter` : le produit appelle « gouttière » deux
choses distinctes, une **hauteur** de bande et un **écart** horizontal de page. C'est la famille qui
les sépare.

Les deux barres **hautes** (coque admin, coque cockpit) portent encore leur `clamp` écrit à la main,
avec deux valeurs différentes. Elles rejoindront cette famille le jour où quelqu'un tranchera
laquelle des deux gagne.

Chaque niveau typographique **apparie déjà son interlignage** : `text-h2` pose la taille et la
hauteur de ligne. Y ajouter un `leading-*` défait l'appariement.

`text-label` va plus loin, et c'est le seul : il apparie aussi **graisse, capitales et
interlettrage**, parce qu'il ne nomme pas une taille mais un rôle — l'étiquette d'une section. Les
trois attributs ne valent qu'ensemble : un petit corps gras et espacé se lit comme une étiquette, le
même texte au corps courant se lit comme du contenu. On écrit `text-label`, jamais
`text-caption uppercase`.

Il porte aussi sa propre correction d'écriture : l'arabe est **cursif**, et espacer ses lettres brise
les liaisons. Le palier annule donc son interlettrage sous `[dir='rtl']`. Conséquence à connaître :
un `normal-case` posé à côté ne reprend pas la main — un libellé sans capitales n'est plus ce palier,
il est `text-caption`.

Deux primitives de page sont fournies comme classes, dans `main.css` :

- `.readable` — gabarit **lecture** : largeur contrainte, gouttières fluides.
- `.shell` — gabarit **outil** : pleine largeur, bornée puis centrée.

### Une surface qui recouvre l'écran borne son CONTENU, pas elle-même

Un tiroir, une feuille, un panneau modal touchent les deux bords : c'est ce qui les distingue d'une
fenêtre posée au milieu, et ce qui donne son sens à la poignée qu'on tire. Ce qu'ils **portent**, en
revanche, se borne — sinon deux colonnes s'écartent jusqu'aux extrémités et l'œil traverse tout
l'écran pour passer de l'une à l'autre.

La borne s'écrit en **fraction** (`max-w-3/4`) et non en largeur de gabarit : les gabarits sont
calibrés pour la coque, et `--container-shell` vaut 160 rem — sur un écran de 1920 il ne mord pas.
Elle ne s'applique qu'**à partir du seuil où le contenu passe à deux colonnes** : en dessous, une
colonne unique n'a rien à resserrer, et un pourcentage y inventerait le vide qu'on cherchait à
chasser sur grand écran.

L'en-tête d'une telle surface suit la même borne que son corps, faute de quoi son titre et sa
fermeture flottent aux extrémités, loin de ce qu'ils coiffent.

## Ce qu'on n'écrit pas dans un composant

Ces cinq interdits sont la contrepartie de la règle « une valeur n'existe qu'à un seul endroit ».

1. **Aucun hexadécimal dans les composants** — les échelles vivent dans `palette.css`, les rôles et les états dans `themes.css`. Les e-mails et les favicons portent une copie documentée des couleurs nécessaires à leur support.
2. **Aucune teinte du nuancier** — `bg-slate-300` ou `text-slate-400` sautent le rôle. On veut
   `bg-surface-soft` et `text-muted`.
3. **Aucun `clamp()` ni `min-[…px]:` à la main** — l'échelle typographique et le rythme
   d'espacement sont fermés. Un besoin non couvert se règle en ajoutant un palier dans
   `sizing.css`, pas en improvisant dans un écran. **Les paliers bruts de Tailwind comptent pour de
   l'improvisation** : `p-1`, `gap-2`, `text-sm` sont figés là où les nôtres sont fluides, et ils
   produisent exactement l'écart qu'on cherche à éviter. La coque admin a porté `p-1` et un
   `--ui-radius` rabaissé pendant des semaines, ce qui faisait lire l'espace comme un autre produit.
   **Une entorse assumée n'est assumée que tant qu'elle a un seul consommateur.** Les bandes de la
   coque cockpit ont porté leur `clamp` en propre, avec un commentaire qui le justifiait — « un token
   global l'exporterait à tort ». L'argument tenait sur l'unicité, pas sur la nature de la valeur :
   le jour où l'espace admin a eu besoin de la même bande, il est tombé, et les trois valeurs sont
   remontées dans `sizing.css`. Une entorse porte donc sa date de péremption dans son propre motif.
4. **Aucune transition en ligne** — durées et courbes vivent dans `transitions.css`.
5. **Aucune couleur pour coder une donnée** — la couleur appartient à l'accent et aux états. Une
   information répétée sur chaque ligne se distingue par l'icône et le libellé.

   **L'accent ne dit pas « favori », il dit « ce qui compte ».** Il a d'abord servi le seul état que
   le recruteur produit, d'où l'idée qu'il lui appartenait ; la colonne de verdict de la Visionneuse
   l'emploie aussi pour un critère satisfait, à côté du geste qui retient un profil. Le sens commun
   aux deux est l'importance, pas la propriété — et c'est ce qui permet à un écran de tenir sur
   **une seule couleur et du gris** plutôt que sur deux teintes qui se répondent. Un verdict codé
   par une paire vert/rouge se lit comme un jugement sur le candidat ; une couleur et son absence se
   lisent comme un relevé.

6. **Aucun nom que Nuxt UI revendique aussi** — `bg-muted` et `border-muted` ne désignent **pas** le
   gris du nuancier. Nuxt UI expose ses propres utilitaires sous ces noms, que `nuxt-ui.css` branche
   sur `--surface-soft` et sur `--line`, et **c'est lui qui gagne**. L'écriture rend donc une autre
   couleur que celle visée, _sans rien dire_ : le nom demandé existe, il répond, il répond autre
   chose — la barre d'avancement de la carte de recherche s'est ainsi peinte dans la couleur de son
   propre fond pendant des semaines.

Le troisième interdit est vérifié par la règle `layouts-no-raw-spacing`, le sixième par
`app-no-ambiguous-kit-class` — parce que ni la relecture, ni le contrôle des types, ni le lint ne
peuvent les voir.

**Peindre avec le gris du nuancier** demande donc un détour : poser la couleur par `text-muted` — la
seule des trois formes que Nuxt UI remappe sur la bonne valeur — puis peindre avec `bg-current` ou
`border-current`. Le croisement des deux catalogues ne porte que sur ce mot, `muted` : partout
ailleurs, l'utilitaire du kit est sans ambiguïté.

## Les deux thèmes

Le thème sombre n'est pas le thème clair assombri : les deux changent de neutre **et** d'accent
(les raisons de contraste sont dans la [charte](./brand-guidelines.md)). La bascule est portée par
une classe `.dark` posée à la racine.

Conséquence à connaître : `.dark` sert aussi d'**îlot** posé sur un simple élément, pour qu'une
portion d'écran s'affiche en sombre alors que la page est claire — c'est le cas du tunnel
d'inscription entreprise. Un token qui en lit un autre doit donc être **re-déclaré dans `.dark`**,
et pas seulement à la racine : sinon il descend dans l'îlot déjà calculé sur le thème clair, et
l'encre d'un bouton accent tombe à un contraste inutilisable.

## Les composants

Deux familles cohabitent, et elles lisent les mêmes tokens.

- **Le kit maison** — `webapp/base/app/components/ui/` : avatar, badge, bouton, carte, champ,
  indice, saisie et sélection en ligne, autocomplétion de lieux, indicateur de chargement.
  Le **badge** porte deux objets sous une seule brique : sans teinte, c'est le badge de contexte en
  accent doux ; avec une teinte, c'est un badge d'état, et la recette change avec lui (cf. la charte,
  § _États et verdicts_). Il reçoit un **nom de variable CSS**, jamais une couleur — la valeur vit
  dans `themes.css`, et lui ne connaît aucun état.
- **La bibliothèque de composants** — branchée par `nuxt-ui.css` sur le nuancier (ses échelles) et
  sur les tokens sémantiques (ses rôles). Les deux étages sont remappés : sans les échelles, une
  variante douce retomberait sur la couleur par défaut de la bibliothèque, hors nuancier.

Le rayon commun est défini à `--ui-radius: 0.375rem` : la bibliothèque en dérive
les rayons Tailwind, ce qui donne 12 px à `rounded-lg` et 18 px à `rounded-xl`.
Cette valeur appartient au socle ; les espaces ne la redéfinissent pas.

**Ce pont porte les couleurs et le rayon, et rien d'autre.** L'espacement et la typographie de la
bibliothèque restent les siens — `p-4`, `gap-2.5`, `text-sm` : des paliers **figés** là où les nôtres
sont fluides, c'est-à-dire exactement ce que l'interdit n°3 refuse. Adopter un composant `U*` sans
plus fait donc rentrer par la fenêtre ce qu'on s'interdit d'écrire à la main, et **aucune gate ne le
voit**.

D'où la règle d'adoption, en deux temps : on prend le composant pour ce qu'il apporte — le
placement, l'accessibilité, le comportement —, puis on **rebranche son rythme** sur nos jetons, en
CSS local. Trois précisions qui viennent de l'usage :

- **En CSS et non par `:ui`** — la fusion de classes de la bibliothèque ne connaît pas nos paliers :
  elle a déjà pris `text-label` pour une couleur et laissé cohabiter `p-4` avec `px-cell-x`.
- **Cibler les `data-slot`** que le composant émet (`content`, `title`, `description`…), jamais ses
  classes utilitaires, qui changent de version en version.
- **Aucune valeur n'est réécrite** : ces règles lisent les mêmes variables que les utilitaires.

Trois précédents la portent — le tableau de l'espace entreprise (`--spacing-cell-*`), l'alerte de
l'écran « État de la base », la bulle d'aide du kit.

### L'icône d'un bouton ne domine pas son texte

**Dans un bouton qui porte un mot, l'icône se pose un cran en dessous du corps de ce mot** — environ
quatre cinquièmes. À taille égale une icône paraît déjà plus grosse qu'une lettre : son glyphe occupe
tout son carré, là où une minuscule n'en remplit que la moitié. « Pas plus grande que le texte » se
traduit donc en pixels par « plus petite », pas par « égale ».

```
texte 12 px  →  icône 10 px
texte 14 px  →  icône 12 px
texte 16 px  →  icône 14 px
```

La bibliothèque, elle, dimensionne l'icône **indépendamment** du texte et toujours plus grand —
vingt pixels pour un texte de quatorze. L'œil se pose alors sur le signe, qui ne fait que redire ce
que le mot dit déjà. La table ci-dessus est donc posée **une fois**, dans `appConfig.ui.button` du
`nuxt.config.ts` racine, aux côtés du curseur en main ; elle vaut pour les quatre espaces, qui
partagent cette configuration.

Deux réserves :

- **Un bouton à icône seule garde sa taille.** La règle énonce un _rapport_ entre une icône et son
  texte ; sans texte il n'y a pas de rapport, et l'icône devient le seul contenu du bouton — la
  réduire rendrait la cible plus difficile à viser pour rien.
- **Une taille écrite à la main sur un bouton l'emporte sur cette table**, et la défait en silence.
  C'est le seul moyen de rater la règle : n'en écrire aucune, et retirer celles qui restent.

Pour l'identité, trois composants dédiés dans `webapp/base/app/components/layout/` : le
pictogramme seul, le mot-symbole seul, et le verrouillage complet. Tous sont monochromes et
prennent la couleur de leur contexte.

## Rayons et identité

`--ui-radius: 0.375rem` dans `nuxt-ui.css` pilote les rayons communs : 12 px pour
`rounded-lg`, 18 px pour `rounded-xl`. `UiCard` emploie un filet `border-line` et le rayon de carte.
Le nom visible est **Shortlist** ; le nom technique du dépôt reste `showcase-recruiter-webapp-saas`.

`LayoutLogo` assemble `LayoutLogomark` et `LayoutWordmark`. La clé `app.name` des trois locales
alimente le mot-symbole et le nom accessible du lien de marque. Les favicons reproduisent le signe
et suivent le thème système. Les e-mails reprennent les valeurs du kit dans leur gabarit Twig,
car les clients de messagerie ne lisent pas les variables CSS.

## La galerie UX Kit

Le kit dispose d'une **galerie vivante** qui rend chaque composant dans ses variantes, ses tailles
et ses états, dans les deux thèmes : la route `/artefacts/ux-kit`, sur l'espace public. C'est la
référence à ouvrir avant de dessiner un écran — elle montre ce qui existe déjà, et évite de recréer
une variante qui est là.

Elle vit dans `spaces/public/app/pages/artefacts/`, avec les maquettes d'écrans. Le dossier n'est
servi **qu'en développement et en preprod** : en production les routes n'existent pas, et sur la
preprod elles sont derrière un mot de passe.

## Étendre le système

Dans l'ordre, et sans sauter d'étape :

1. **Vérifier que le besoin n'est pas déjà couvert** — la galerie `/artefacts/ux-kit` d'abord, les
   tables de la charte ensuite.
2. **Ajouter au bon étage** — une nouvelle teinte va dans `palette.css` ; un nouveau rôle dans
   `themes.css`, dans les deux thèmes ; un palier de taille dans `sizing.css`.
3. **Mesurer le contraste** si le token touche du texte ou un aplat accent, et l'inscrire dans la
   charte. C'est la mesure qui a imposé un accent différent par thème.
4. **Mettre à jour la charte** dans le même changement : un token qui existe dans le code mais pas
   dans la charte est un token que personne ne trouvera.
