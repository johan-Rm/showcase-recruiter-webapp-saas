# TASK ADM-023 — le compte et les notifications descendent en pied de colonne

- **Créée le** : 2026-08-11.
- **Session** : `417ad4f5-232c-42c6-8257-f61c6e7c2f12`.
- **Statut** : ✅ fait le 2026-08-11 — au-dessus de 1 024 px la barre haute ne porte plus que le fil
  d'ariane, et les deux contrôles tiennent les bords du pied de colonne : le compte à gauche, la
  cloche à droite. Sous le seuil, rien n'a bougé. Deux écarts au cadrage, tous deux consignés —
  l'agencement du pied, et les rôles de menu.
- **Type** : refactor — trois contrôles changent de place et deux menus écrits à la main passent à la
  bibliothèque ; aucune fonction n'est ajoutée ni retirée.
- **Tests** : `sans objet côté unitaire` — le déplacement ne produit aucune fonction pure : il change
  **où** des éléments sont rendus et confie leur positionnement à la bibliothèque. Et jsdom ne
  calcule aucune mise en page : un test de composant ne verrait ni un menu ouvert dans le vide, ni un
  débordement — c'est-à-dire rien de ce qui peut réellement casser ici. La preuve est **mesurée dans
  le navigateur** : barre haute vidée à droite au-dessus de 1 024 px et inchangée en dessous, pied de
  colonne présent aux **deux** états du rail, menus qui s'ouvrent **vers le haut** et restent
  entièrement à l'écran, noms accessibles conservés une fois repliés, thème et langue réellement
  opérants depuis le menu.
- **Réf** : **`ADM-022` — amont, bloquante** (le rail repliable : ce pied de colonne doit savoir se
  replier avec lui, il ne peut donc pas le précéder) · **`ADM-021` — amont** (la barre haute a cessé
  de porter un hamburger de trop) · **`WEB-015`**, le précédent du menu qui sort de l'écran et le
  remède employé ici · fichiers touchés :
  [admin/app/components/Topbar.vue](../../webapp/spaces/admin/app/components/Topbar.vue),
  [admin/app/components/SidebarNav.vue](../../webapp/spaces/admin/app/components/SidebarNav.vue),
  [arborescence-admin.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md).

## Contexte

Un administrateur sur grand écran a, en haut à droite, trois contrôles — **thème, notifications,
compte** — qui occupent de la largeur sans jamais servir la page qu'il regarde. Ils sont là parce
qu'une barre haute en porte d'habitude ; pas parce que cet écran en a besoin à cet endroit.

Pendant ce temps, **le pied de la colonne de gauche est entièrement vide** : la liste des sept
entrées s'arrête au tiers de la hauteur, et rien n'occupe le reste.

### Ce que ça coûte aujourd'hui

Le fil d'ariane et le cluster de droite se partagent une bande dont le contenu utile — savoir où
l'on est — tient en quelques mots. Le reste est de l'outillage permanent qu'on consulte rarement :
on ne change pas de thème plusieurs fois par jour, on n'ouvre pas son compte à chaque écran.

### La décision inverse est écrite dans le code

L'en-tête de la navigation dit aujourd'hui, en toutes lettres :

> Le compte n'est **PAS** en pied : il vit dans le dropdown de l'avatar (Topbar), qui porte aussi
> langue et déconnexion.

Cette carte renverse cette décision. Le commentaire doit donc être **remplacé** et non supprimé —
effacé en silence, il laisserait le fichier muet sur une question qui s'est posée et se reposera.

### Et les deux menus ne savent pas s'ouvrir ailleurs

Les menus « notifications » et « compte » sont positionnés à la main, ancrés **sous** leur bouton
(`top: calc(100% + 6px)`), sans aucune détection de bord. Déplacés en bas d'écran, ils s'ouvriraient
vers le bas, c'est-à-dire dans le vide. C'est exactement le défaut que `WEB-015` vient de corriger
sur la bulle d'aide, et pour la même raison : un placement écrit à la main ne regarde pas si la
place existe.

## Périmètre

- **Un pied de colonne dans la navigation** : le compte et la cloche, séparés de la liste des
  entrées. Ils tiennent les deux bords — cf. *Décisions de cadrage* pour l'ordre, corrigé en cours
  de route.
- Il ne s'affiche **que dans la colonne dépliable**, pas dans le tiroir mobile — celui-ci garde la
  navigation seule.
- **Replié, le pied suit le rail** : avatar et cloche empilés sans texte, seul cas où la largeur
  interdit la ligne. La pastille de notification reste visible.
- **Les deux menus passent au composant de la bibliothèque** : retournement au contact d'un bord,
  sortie du flux, piège à focus et fermeture au clavier — aujourd'hui tous écrits à la main.
- **Le sélecteur de thème devient une ligne du menu du compte**, au-dessus de la langue, sur le même
  modèle : un libellé à gauche, le contrôle à droite.
- **Sous 1 024 px, la barre haute garde tout** : hamburger, cloche et compte.
- **Le commentaire d'en-tête de la navigation est réécrit** : il dit la nouvelle décision et ce qui
  a été écarté.

## Hors périmètre

- **La coquille de notifications** — trois lignes grises et un « 3 » écrit en dur — se déplace
  **telle quelle**. La fonction n'existe pas côté serveur ; décider du sort du faux compteur est un
  cadrage produit, pas un déplacement. On n'ajoute pas de dette, on la transporte.
- **Les 94 lignes de style local restantes** de la barre haute et leurs valeurs en dur, déjà nommées
  par `ADM-021`.
- **Ce que devient la barre haute une fois vidée** — sa hauteur, son existence même. Regard séparé,
  une fois qu'on la verra vide.
- **La coque du cockpit entreprise** : autre lane, autre agencement, et elle ne porte pas ces
  contrôles au même endroit.

## Décisions de cadrage

### La cloche descend avec le compte, et pas seulement lui

Une voie intermédiaire existait : ne descendre que le compte et laisser la cloche en haut à droite,
là où l'œil la cherche d'habitude. Écartée — elle ne récupère que la moitié de la place, et laisse
la barre haute avec un élément isolé à droite, ce qui est le pire des deux mondes : ni vide, ni
utile. La pastille reste visible en permanence, simplement à gauche.

### Le seuil, et pourquoi il y a deux agencements

Sous 1 024 px la colonne n'existe pas — c'est un tiroir, fermé par défaut. Y descendre le compte et
la cloche demanderait d'ouvrir le tiroir pour les atteindre : deux gestes au lieu d'un, et une
pastille de notification invisible tant qu'il est fermé.

Le déplacement ne vaut donc **qu'au-dessus de 1 024 px**, là où la colonne existe et là où la place
gagnée sert à quelque chose. Le prix est assumé : deux agencements à tenir dans le même écran.

### Le thème devient un réglage rangé avec les autres

Le menu du compte porte déjà « Langue » sur le modèle libellé-à-gauche / contrôle-à-droite. Le thème
prend la même forme, juste au-dessus. L'autre voie — une ligne « Passer en thème sombre » qui agit
et ferme le menu — a été écartée : son libellé devrait changer selon le thème courant, et le réglage
ne se lirait plus d'un coup d'œil, il faudrait ouvrir pour savoir où l'on en est.

### ÉCART — le pied est une LIGNE, pas une pile

Le pied avait d'abord été rendu **vertical**, par calque sur l'axe de la colonne. C'était un
raccourci : l'axe d'un conteneur ne dit rien de l'agencement de son pied, et deux boutons empilés
prenaient de la hauteur dans la seule zone où la hauteur ne manque jamais.

Corrigé à la relecture : les deux contrôles tiennent **une ligne** et **les deux bords** — le compte
ouvre, la cloche ferme. Seul le rail, large d'une icône, force encore l'empilement, et c'est
désormais lui seul qui le déclenche.

L'ordre des deux blocs a été inversé **dans le document**, pas seulement à l'affichage : un
retournement en CSS aurait donné la cloche au clavier avant le compte à l'œil.

### ÉCART — les panneaux perdent les rôles `menu` et `menuitem`

Les deux menus les portaient. Ils les portaient **à tort** : un tel rôle promet une navigation aux
flèches que le code n'a jamais fournie. Ce sont désormais des panneaux contenant des liens et des
boutons, ce qui décrit exactement ce qu'ils sont. Le changement va dans le bon sens, mais c'est un
changement de sémantique et il ne doit pas passer inaperçu.

### Les menus ne sont pas un élargissement de périmètre

On pourrait croire que passer les deux menus à la bibliothèque déborde de « déplacer trois
contrôles ». C'est l'inverse : **sans ce changement, le déplacement ne peut pas marcher.** Un menu
ancré sous son bouton, en bas d'écran, s'ouvre hors de la fenêtre. Livrer le déplacement seul
reviendrait à introduire sciemment un défaut visible immédiatement.

## Règles et cas limites

- **La barre haute sous 1 024 px ne doit pas bouger d'un pixel.** C'est la moitié du travail qu'on
  ne voit pas : tout se joue au-dessus du seuil, et une régression en dessous passerait inaperçue.
- **Le pied de colonne appartient à la colonne, pas à la navigation elle-même.** Le tiroir mobile
  rend le même composant : ce qui distingue les deux est déjà en place depuis `ADM-022` — le tiroir
  n'est pas repliable et n'a pas de bouton de repli.
- **Replié, chaque contrôle garde son nom accessible.** `ADM-022` a montré que la bibliothèque
  masque les libellés en `display: none`, ce qui les retire **aussi** de l'arbre d'accessibilité. Le
  même piège attend ici, et il se vérifie de la même façon : en lisant l'arbre d'accessibilité, pas
  en supposant.
- **Les menus s'ouvrent vers le haut** parce qu'il n'y a pas de place en dessous — et c'est la
  bibliothèque qui doit le décider, pas nous. Le vérifier aux deux états du rail : replié, le menu
  part d'un bouton de 64 px de large et devra se décaler pour tenir.
- **Le nom du compte disparaît en mode rail**, faute de place. L'avatar seul doit rester nommé.
- **Aucun texte en dur** : le libellé du thème passe par une clé de traduction, dans les trois
  langues.
- **Le menu du compte reste atteignable au clavier** dans les deux agencements, et l'ordre de
  tabulation doit rester lisible — en pied de colonne, il vient après les sept entrées.

## Documentation impactée

- **`docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md`** — une ligne. Elle écrit que
  `/compte` est « joignable par le menu de l'avatar, **pas par la navigation** ». Après le
  déplacement, ce menu vit **dans** la colonne de navigation. L'intention reste vraie — `/compte`
  n'a toujours pas d'entrée de barre latérale — mais la lettre devient trompeuse. Vérifié dans le
  fichier avant d'écrire cette carte.
- Aucune autre page. La carte déplace des contrôles existants et ne crée aucune convention.

## Définition de terminé

- [x] Au-dessus de 1 024 px, la barre haute ne porte plus que le fil d'ariane — mesuré, pas déduit.
- [x] Sous 1 024 px, la barre haute est **inchangée** : hamburger, cloche et compte à leur place.
- [x] Le pied de colonne porte la cloche et le compte, séparés visuellement des entrées.
- [x] Replié, le pied montre les deux icônes sans texte, la pastille toujours visible.
- [x] Chaque contrôle du pied garde un nom accessible aux deux états — lu dans l'arbre
      d'accessibilité.
- [x] Les deux menus s'ouvrent **vers le haut** et restent entièrement à l'écran, aux deux états du
      rail et aux largeurs de bureau testées.
- [x] Le sélecteur de thème est une ligne du menu du compte et bascule réellement le thème.
- [x] La langue reste opérante depuis le menu.
- [x] Le tiroir mobile ne porte **ni** pied de colonne **ni** bouton de repli.
- [x] Le commentaire d'en-tête de la navigation dit la nouvelle décision et ce qui a été écarté.
- [x] La ligne d'`arborescence-admin.md` ne dit plus quelque chose de trompeur.
- [x] Clés de traduction présentes dans les trois langues.
- [x] Captures des deux états du rail et de la barre haute, dans les deux thèmes, plus une capture
      sous 1 024 px qui montre que rien n'a bougé.
- [x] La carte est à jour de ce qui a réellement été fait, y compris les écarts au cadrage.

## Vérification

Mesures relevées dans le navigateur, authentifié, sur la stack du worktree admin.

### Le déplacement, de part et d'autre du seuil

| Largeur | Barre haute | Pied de colonne |
| --- | --- | --- |
| 1 920 · 1 280 · 1 024 px | **0 bloc à droite**, fil d'ariane seul | **présent**, 2 boutons |
| 1 023 · 390 px | contrôles présents | absent |

### Le pied suit le rail

| | Déplié | Replié |
| --- | --- | --- |
| Colonne | 259 px | **72 px** |
| Pied | 222 px, `row` | **35 px, `column`** |
| Bouton du compte | 120 px — il partage la ligne | **44 px** — l'avatar seul |

### Les quatre panneaux tiennent l'écran

| Panneau | Déplié | Rail |
| --- | --- | --- |
| Compte | `x = 28–268` ✔ | `x = 23–263` ✔ |
| Notifications | `x = 218–538` ✔ | `x = 29–349` ✔ |

Ils s'ouvrent **vers le haut** — mesurés à `y = 577–833` dans une fenêtre de 900 — et le panneau des
notifications, déclenché depuis le bord droit, déborde de la colonne pour se poser au-dessus du
contenu **sans jamais sortir de l'écran**. C'est très exactement ce que l'écriture manuelle
précédente ne savait pas faire.

### Les réglages opèrent réellement

Depuis le menu du compte : le thème bascule **sombre → clair**, et la langue passe de `fr` à `en`
avec l'adresse qui suit (`/en`). Vérifiés par l'effet, pas par la présence du contrôle.

### Les noms accessibles, lus dans l'arbre

Pied déplié : `button "Notifications": "3"` · `button "Compte": admin`.
Pied replié : `button "Notifications": "3"` · `button "Compte"`.
**Zéro élément sans nom** dans les deux états — en mode rail le nom d'affichage quitte l'écran, le
bouton reste nommé.

### Le tiroir mobile

Ouvert à 900 px : 8 liens, **aucun pied de colonne**, **aucun bouton de repli**.

### Gate impactée

`prettier --check` sur les fichiers touchés ✔.

## Livré

- `webapp/spaces/admin/app/components/UserControls.vue` (+) — **un seul composant pour deux
  places**. C'est le point qui compte : la barre haute et le pied de colonne rendent le même, si
  bien qu'ils ne peuvent pas diverger. Les deux panneaux passent à `UPopover`, qui se retourne au
  contact d'un bord et sort du flux ; leur rythme est rebranché sur les jetons du socle, par les
  `data-slot` du composant et non par ses classes utilitaires.
- `webapp/spaces/admin/app/components/Topbar.vue` (M) — ne garde que le hamburger et le fil
  d'ariane, plus les contrôles sous 1 024 px. **Perd 94 lignes de style local devenues mortes** avec
  le déplacement de leurs consommateurs — c'était la dette nommée par `ADM-021`, et elle disparaît
  ici par conséquence, pas par élargissement.
- `webapp/spaces/admin/app/components/SidebarNav.vue` (M) — gagne son pied, séparé des entrées par
  un filet, rendu **seulement** là où la colonne existe.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` (M) — le libellé du thème, dans les trois langues.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (M) — la ligne du compte disait
  « joignable par le menu de l'avatar, **pas par la navigation** » ; ce menu vit désormais dans la
  colonne de navigation.

## Reste à faire (hors périmètre)

- **La coquille de notifications** garde son compteur « 3 » écrit en dur et ses trois lignes grises.
  L'API n'existe pas ; elle a été déplacée telle quelle.
- **`ADM-024`** touchera le même fichier de barre haute : elle y remplacera le fil d'ariane par le
  titre de l'écran, au-dessus du seuil.
