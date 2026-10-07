# TASK ADM-022 — la barre latérale se replie en rail d'icônes

- **Créée le** : 2026-08-11.
- **Session** : `417ad4f5-232c-42c6-8257-f61c6e7c2f12`.
- **Statut** : ✅ fait le 2026-08-11 — la colonne se replie en rail d'icônes au-dessus de 1 024 px,
  le bouton vit dans la colonne, l'état survit au rechargement et à la navigation. **Un écart au
  cadrage, et il est de taille** : les libellés ne reviennent PAS au survol — l'infobulle réclame un
  fournisseur que l'application ne monte nulle part. Ce sont les noms accessibles qui portent seuls
  le sens des icônes. Cf. *Décisions de cadrage*.
- **Type** : feat — une fonction nouvelle : sur grand écran, la navigation se réduit à ses icônes et
  rend sa largeur au contenu.
- **Tests** : `sans objet côté unitaire`, et c'est une décision assumée plutôt qu'une facilité —
  l'état replié ne produit pas de fonction pure : il s'appuie sur la primitive de cookie du
  framework, qu'un test unitaire ne ferait que remplacer par un faux pour se vérifier lui-même. Ce
  qui compte ici — la largeur du rail, les titres de groupes qui s'effacent, le libellé qui revient
  au survol, l'état retrouvé après rechargement — ne se voit qu'en **mesure dans le navigateur**,
  jsdom ne calculant aucune mise en page. Si l'implémentation dégage malgré tout une fonction pure,
  elle est couverte et la carte est corrigée.
- **Réf** : **`ADM-021` — amont, non bloquante** (le hamburger cesse de doubler
  la barre latérale ; sans elle le bouton s'afficherait quand même là où on ne le veut pas) ·
  fichiers touchés : [admin/app/components/SidebarNav.vue](../../webapp/spaces/admin/app/components/SidebarNav.vue),
  [admin/app/layouts/dashboard.vue](../../webapp/spaces/admin/app/layouts/dashboard.vue),
  [base/app/assets/css/sizing.css](../../webapp/base/app/assets/css/sizing.css),
  [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md),
  et un état partagé à créer dans `admin/app/composables/`.

## Contexte

Sur un écran dense, la navigation occupe **jusqu'à 272 px en permanence**, alors qu'un opérateur qui
travaille toute la journée dans l'espace d'administration connaît ses sept entrées par cœur. Rien ne
permet aujourd'hui de récupérer cette largeur pour le contenu — la seule alternative est de réduire
la fenêtre sous 1 024 px, ce qui fait basculer tout l'écran en disposition mobile.

### Ce qui rend cette carte petite, et qui est vérifié

La navigation est rendue par le composant de menu de la bibliothèque, et **le mode replié y est
natif**. Relevé dans la version installée :

- une propriété bascule le menu en rail d'icônes ;
- **les titres de groupes** — « Pilotage », « Comptes », « Supervision » — **se masquent d'eux-mêmes**
  quand il est replié ;
- ~~les libellés reviennent en infobulle au survol~~ — **affirmation fausse au cadrage**, corrigée
  à l'implémentation : c'est une capacité native, mais elle exige un fournisseur que l'application
  ne monte pas. Voir *Décisions de cadrage*.

Et la condition qui manque le plus souvent est remplie : **les sept entrées portent déjà une icône**.
Aucune ne deviendrait muette une fois son libellé masqué. La navigation est par ailleurs déclarée à
**un seul endroit**, lu par la barre latérale, le tiroir mobile et le fil d'ariane — il n'y a rien à
dupliquer.

Reste donc à construire : l'**état**, le **bouton**, la **largeur**, et la **marque réduite**.

## Périmètre

- **Un bouton de repli en tête de la barre latérale**, aligné avec la marque. Il ne vit pas dans la
  barre haute : c'était la demande d'origine.
- **Le mode replié du menu**. L'infobulle au survol en est **exclue** : elle n'est pas activable
  ici, cf. *Décisions de cadrage*.
- **La marque se réduit au pictogramme** quand la barre est repliée. Le socle fournit déjà une
  version isolée du pictogramme, à côté du mot-symbole seul et du verrouillage complet.
- **Deux largeurs entrent dans l'échelle du socle** — la largeur dépliée de la colonne de
  navigation, et celle du rail — et la coque les lit au lieu de les écrire. La valeur en dur qu'elle
  porte aujourd'hui disparaît avec.
- **L'état est conservé par cookie** : replié une fois, la barre l'est encore à la page suivante et
  au prochain retour.

## Hors périmètre

- **Le tiroir mobile et le hamburger sous 1 024 px** : inchangés. La barre latérale n'existe pas à
  ces largeurs, le tiroir reste le seul accès à la navigation.
- **Le contenu de la navigation** : ni entrée, ni icône, ni libellé ne bouge.
- **La barre haute** : `ADM-021` s'en occupe, et sa dette de style local reste nommée là-bas.
- **Les coques des autres espaces** : le cockpit entreprise a sa propre coque, hors de cette lane.

## Décisions de cadrage

### CORRECTION DE CADRAGE — les deux largeurs reviennent ici, elles ne font pas une carte socle

Ces deux largeurs avaient d'abord été cadrées en carte socle, au motif que `sizing.css` vit dans
`webapp/base/`. **C'était le mauvais critère**, et la règle de routage dit l'inverse en toutes
lettres : le transverse se rattache à l'espace **consommateur**, et `espace/socle-webapp` ne prend
que ce qu'**aucun** espace ne consomme. Ces deux largeurs ne servent qu'à la coque admin. Le
précédent existe d'ailleurs : `ADM-015` a modifié `design-system.md`, fichier partagé, depuis cette
lane, pour cette raison exacte.

**Et une mesure a rendu la séparation carrément intenable.** Tailwind **retire de la feuille servie
tout jeton que personne ne consomme** — vérifié dans la page : `--container-sentence`, déclaré,
commenté et documenté, en est **absent**, faute d'un seul consommateur. Une carte qui aurait posé
les deux largeurs sans le rail aurait donc livré deux jetons **inexistants dans la page**, et son
relecteur n'aurait rien eu à mesurer. Les jetons naissent avec leur consommateur, dans le même
commit.

### Le cookie, et pas le stockage local

L'état doit survivre au rechargement. Le stockage local du navigateur ne se lit **qu'après** le
rendu serveur : la page arriverait dépliée puis se replierait sous les yeux, et le décalage entre le
HTML servi et le DOM hydraté est exactement ce qu'on passe son temps à éviter ailleurs. Le cookie,
lui, est lu par le serveur avant le rendu — aucun sursaut.

### ÉCART AU CADRAGE — l'infobulle n'est pas activable, et l'activer casse le rail

Le cadrage annonçait que les libellés reviendraient en infobulle au survol, « comportement par
défaut du composant en orientation verticale ». **C'est faux, et je l'avais écrit sans le vérifier.**

L'infobulle est une **capacité** native — ses réglages par défaut sont bons — mais elle réclame un
fournisseur (`injectTooltipProviderContext`) que seul `<UApp>` monte. Or **`<UApp>` n'existe nulle
part dans l'application** : sa seule occurrence du dépôt est une page de démonstration de l'espace
public. C'est la même pièce manquante qui laisse tous les textes intégrés de la bibliothèque en
anglais dans les quatre espaces, défaut connu depuis le 2026-07-31 et jamais cardé.

**Et l'activer quand même ne reste pas sans effet.** Mesuré, propriété posée, serveur redémarré : le
rail perdait **une entrée sur sept**, voyait **deux titres de groupes réapparaître**, et la colonne
**disparaissait au rechargement**. Sans fournisseur, la branche d'infobulle fait échouer le rendu de
ces éléments. La propriété a donc été retirée, et le composant porte un commentaire qui dit pourquoi.

**Ce qui reste, et qui n'est pas rien** : chaque icône garde son **nom accessible** — vérifié dans
l'arbre d'accessibilité, neuf éléments nommés, zéro anonyme. Un lecteur d'écran annonce donc
correctement les sept entrées. **Ce qui manque** : un opérateur voyant qui a oublié une icône n'a
aucun moyen de retrouver son libellé sans déplier. C'est le prix assumé de cette livraison, et il se
lève d'une ligne le jour où le socle monte le fournisseur.

### Le bouton en tête, pas en pied

Le pied de la barre latérale n'entre en concurrence avec rien et ne se clique pas par erreur. C'est
aussi l'endroit où l'œil ne va jamais : un réglage qu'on ne trouve pas n'existe pas. Le bouton se
pose donc en tête, à hauteur de la marque, là où le regard entre dans la barre.

## Règles et cas limites

- **Le nom du bouton dit ce qu'il va faire**, et il change avec l'état : « replier » quand la barre
  est dépliée, « déplier » quand elle l'est. Il porte aussi l'état lui-même, pour qu'un lecteur
  d'écran l'annonce sans avoir à le deviner.
- **Le rail n'existe qu'au-dessus de 1 024 px.** Sous ce seuil la barre latérale est masquée : ni le
  bouton ni l'état n'ont de sens, et l'état conservé ne doit pas fuir dans le tiroir mobile, qui
  reste toujours déplié.
- **Chaque entrée garde son nom accessible** une fois le libellé masqué. Une icône seule sans nom
  est une entrée muette pour un lecteur d'écran — c'est le risque principal de cette carte.
- **La page courante doit rester repérable en mode rail.** Elle se signale aujourd'hui par une
  cartouche et un repère de bord ; à vérifier que le repère survit à la réduction de largeur, et
  qu'il reste du côté de la fin de lecture — donc à gauche en arabe.
- **Le changement de largeur ne doit pas faire sauter le contenu** : la zone principale s'élargit,
  ses grilles se recalculent. À regarder sur l'écran le plus dense.
- **Aucun texte en dur** : le nom du bouton et les infobulles passent par des clés de traduction,
  dans les trois langues.
- **Le composant de menu reçoit sa configuration par ses propriétés**, pas par du CSS écrit à la
  main — c'est le piège que `ADM-021` corrige à deux pas d'ici.

## Documentation impactée

- **`docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md`** — § *Consommer un token*. Son tableau
  **énumère** les familles et leurs utilitaires ; les deux largeurs de colonne de navigation s'y
  ajoutent. Vérifié : sans cette ligne, le tableau décrit une échelle qui n'est plus la bonne.
- Rien d'autre. La carte emploie un mode natif du composant de menu et ne crée aucune convention.
  Si l'implémentation fait émerger une règle — par exemple sur la façon dont une coque mémorise un
  réglage d'affichage — elle se signale plutôt qu'elle ne s'écrit sans décision.

## Définition de terminé

- [x] Les deux largeurs existent dans `sizing.css`, fluides, commentées — le commentaire dit ce que
      chacune borne et pourquoi, comme ses voisines.
- [x] La coque ne porte plus aucune largeur de barre latérale écrite à la main, et la largeur
      **dépliée est inchangée à l'œil** — mesurée à au moins trois largeurs de fenêtre.
- [x] Les deux jetons sont **présents dans la page servie**, et pas seulement dans le fichier source.
- [x] Le tableau des familles de `design-system.md` les liste.
- [x] Un bouton en tête de la barre latérale replie et déplie la navigation, au-dessus de 1 024 px.
- [x] Replié, le rail montre les sept icônes, sans titre de groupe, à la largeur du palier du socle.
- [x] Chaque entrée conserve un nom accessible en mode rail — vérifié dans le DOM, pas supposé.
- [x] La marque est réduite au pictogramme quand la barre est repliée.
- [x] L'état survit à la navigation **et** au rechargement, sans sursaut visible au chargement.
- [x] La page courante reste repérable en mode rail, dans les deux sens de lecture.
- [x] Sous 1 024 px, rien n'a changé : le tiroir s'ouvre, déplié, comme avant.
- [x] Clés de traduction présentes dans les trois langues.
- [x] Captures des deux états, dans les deux thèmes.
- [x] La carte est à jour de ce qui a réellement été fait, y compris les écarts au cadrage.

## Vérification

Mesures relevées dans le navigateur, authentifié, sur la stack du worktree admin.

### Les deux états

| Largeur | Dépliée | Repliée |
| --- | --- | --- |
| 1 024 px | 220 px | **64 px** |
| 1 280 px | 230 px | **64 px** |
| 1 920 px | 272 px | **76 px** |

Les largeurs dépliées sont **identiques au pixel** à celles d'avant : le jeton reproduit exactement
la valeur qui était écrite à la main dans la coque. Et les deux jetons sont **présents dans la page
servie**, pas seulement dans le fichier source — le critère venait d'un échec précédent, où deux
jetons sans consommateur avaient été élagués par Tailwind sans que rien ne le signale.

Replié : la marque passe au pictogramme seul, les **3 titres de groupes disparaissent**, les **7
entrées restent**, le nom du bouton et son état basculent.

### Les noms accessibles, et le piège qu'ils ont révélé

Lu dans l'arbre d'accessibilité du navigateur, colonne repliée : **9 éléments nommés, 0 anonyme** —
le lien de marque, le bouton de repli, et les sept entrées avec leur libellé.

**Ce n'était pas le cas au premier essai.** La bibliothèque masque le libellé en `display: none`
quand elle replie, ce qui le retire **aussi** de l'arbre d'accessibilité : les sept liens s'y
présentaient **sans aucun nom**. Le libellé est désormais caché à l'œil seulement, ce qui a demandé
d'annuler explicitement ce masquage — la classe de masquage visuel ne touche pas l'affichage.

### Le repère de page courante, dans les deux sens de lecture

Visible et large de 4 px en français **comme en arabe** — et en arabe la colonne entière passe à
droite de l'écran, le lien actif se mesurant à 1 378 px au lieu de 28.

### La persistance et le tiroir

Replié à 72 px, la colonne l'est encore **après rechargement complet** et **après navigation** vers
un autre écran. Le tiroir mobile est inchangé : ouvert à 900 px, 8 liens, ses 3 titres de groupes,
le logo complet, **aucun bouton de repli**.

### Ce qui n'est pas livré, et qui a été mesuré comme tel

L'infobulle au survol : **zéro sur les six entrées sondées**, propriété activée et serveur
redémarré. Puis, l'activation forcée ayant cassé le rail — une entrée perdue, deux titres de groupes
réapparus, colonne disparue au rechargement —, la propriété a été retirée. Cf. *Décisions de
cadrage*.

### Gate impactée

`prettier --check` sur les cinq fichiers touchés ✔. Aucun test unitaire n'est impacté : le dossier
de tests de l'espace ne couvre que des fonctions pures, et ce diff n'en touche aucune.

## Livré

- `webapp/base/app/assets/css/sizing.css` (M) — deux largeurs de colonne de navigation entrent dans
  l'échelle, dépliée et rail, fluides comme leurs voisines. Elles se consomment en `w-nav` /
  `w-nav-rail`, la forme canonique — celle qu'emploie déjà `w-sheet` côté entreprise.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (M) — le tableau des familles les liste, sans
  quoi il décrirait une échelle qui n'est plus la bonne.
- `webapp/spaces/admin/app/layouts/dashboard.vue` (M) — la coque possède l'état de repli et décide
  de sa largeur ; sa valeur écrite à la main a disparu.
- `webapp/spaces/admin/app/composables/useSidebar.ts` (+) — l'état, conservé par cookie. Le stockage
  local ne se lit qu'après le rendu serveur : la page serait arrivée dépliée puis se serait repliée
  sous les yeux.
- `webapp/spaces/admin/app/components/SidebarNav.vue` (M) — reçoit l'état et demande sa bascule, ne
  le mute pas. Porte le bouton, le masquage visuel du libellé, et le commentaire qui explique
  pourquoi l'infobulle est absente.
- `webapp/base/app/components/layout/Brand.vue` (M) — une variante compacte : le pictogramme seul,
  sans mot-symbole ni badge. Une propriété et non un composant jumeau — le lien, son nom accessible
  et son anneau de focus ne varient pas, et le commentaire du pictogramme réclamait justement qu'on
  lui donne un nom « le jour où il servirait seul dans un rail réduit ».
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` (M) — les deux noms du bouton, dans les trois langues.

## Reste à faire (hors périmètre)

- **Monter `<UApp>` à la racine de l'application** — carte de socle, à ouvrir. Elle débloquerait
  d'un coup l'infobulle de ce rail **et** les textes intégrés de la bibliothèque, en anglais dans
  les quatre espaces depuis le 2026-07-31.
- **`--container-sentence` est un jeton mort** : déclaré, commenté, documenté, et absent de la page
  servie faute de consommateur. Repéré en établissant l'élagage de Tailwind. Lane entreprise.
