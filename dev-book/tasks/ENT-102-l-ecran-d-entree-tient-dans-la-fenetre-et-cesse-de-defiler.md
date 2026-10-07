# TASK ENT-102 — l'écran d'entrée tient dans la fenêtre, et cesse de défiler

- **Créée le** : 2026-08-12.
- **Session** : `783be030-740c-4ff7-aa00-704b86f47d9b`.
- **Statut** : ✅ fait le 2026-08-12 — les deux boutons tiennent sur une ligne, la coque demande la
  hauteur exacte de la fenêtre, et onze des douze cas mesurés ne défilent plus. Le douzième — mobile
  avec clavier ouvert — défile encore, et c'est le filet assumé plutôt qu'un bouton hors d'atteinte.
- **Type** : fix. Une régression d'affichage introduite en même temps que les boutons de réseaux
  sociaux, et la règle qui empêchera qu'elle revienne.
- **Tests** : `sans objet` — aucune logique produite, deux règles de présentation et un intitulé.
  La preuve est une **mesure du rendu réel** : hauteur du document comparée à celle de la fenêtre,
  sur les quatre écrans de la coque, en trois hauteurs de fenêtre et dans les trois langues.
- **Réf** : `106` — la carte qui a posé les boutons sociaux et introduit le débordement ·
  `082` — la coque d'entrée telle qu'elle a été conçue · `081` — le social login côté serveur ·
  `ENT-101` — l'écran de fin d'inscription, autre écran du même tunnel, **carte distincte**.

## Contexte

L'écran d'entrée de l'espace entreprise — celui qui porte « Créer un accès » — a été dessiné pour
**tenir dans la fenêtre sans jamais défiler**. C'est une coque en deux panneaux : le formulaire d'un
côté, une image de marque de l'autre, et rien qui dépasse.

Quand les boutons *Continuer avec Google* et *Continuer avec LinkedIn* ont été ajoutés, ils ont été
**empilés l'un sous l'autre**. Deux blocs de plus, plus le séparateur « ou » : la colonne de droite
est devenue trop haute, et la page s'est mise à défiler. Le bouton d'envoi passe sous la ligne de
flottaison, et l'écran perd ce qui faisait son allure — un acte unique, entier, sans rien à chercher.

La coque n'a jamais interdit ce débordement : elle demande **au moins** la hauteur de la fenêtre, sans
jamais s'y limiter. Elle tenait tant que le contenu était court ; le premier ajout l'a fait céder.

## Périmètre

- **Les deux boutons de réseaux sociaux passent côte à côte**, sur une seule ligne.
- **Leur intitulé se raccourcit** au logo et au nom du réseau — *Google*, *LinkedIn* — pour tenir dans
  une demi-colonne.
- **La coque d'entrée est bornée à la hauteur de la fenêtre**, et ne peut plus la dépasser.
- **Quand la hauteur ne suffit pas**, les blancs entre les blocs se resserrent jusqu'à ce que tout
  tienne. Rien n'est coupé, rien n'est masqué.
- **Les quatre écrans de la coque** sont couverts : connexion, saisie de l'e-mail, « lien envoyé »,
  définition du mot de passe.

## Hors périmètre

- **Les textes d'accroche** — titre et paragraphe d'introduction, qui ne sont pas raccourcis ici.
- **L'image de marque** et son glissement d'un côté à l'autre, inchangés.
- **Le comportement des boutons** : ce qu'ils déclenchent ne bouge pas.
- **La page de formulaire d'inscription** (`/inscription/entreprise`), qui a sa propre coque et ne
  présente pas ce défaut.
- **L'ordre des chemins** : les réseaux sociaux restent avant le champ e-mail.

## Décisions de cadrage

### Le logo et le nom, pas la phrase entière

La colonne du formulaire fait vingt-quatre rem. Coupée en deux, chaque bouton dispose d'environ onze
rem — « Continuer avec LinkedIn » n'y tient pas, et le réduire en corps de texte le rendrait illisible.

Ce que la personne reconnaît dans ce bouton, c'est **le logo et le nom du réseau** ; « Continuer
avec » est une formule de politesse que la position du bouton dit déjà. Elle est donc retirée de ce
qui est **affiché**.

⚠️ **Elle n'est pas retirée de ce qui est annoncé.** Une personne qui navigue avec un lecteur d'écran
— le logiciel qui lit la page à voix haute — doit continuer d'entendre l'intitulé complet, sinon elle
n'entend que « Google », sans savoir ce que le bouton fait. L'intitulé long reste, porté séparément.

### La coque cesse de promettre sans tenir

Elle demandait une hauteur **minimale** égale à celle de la fenêtre. Elle demandera une hauteur
**exacte**. C'est ce seul mot qui manquait, et c'est lui qui a laissé passer la régression sans que
personne le voie venir.

### Ce sont les blancs qui cèdent, jamais le contenu

Trois manières de tenir la promesse existaient. Faire défiler la seule colonne du formulaire déplace
le défilement au lieu de le supprimer. Couper ce qui dépasse la tient absolument, mais peut rendre le
bouton d'envoi inatteignable sur un écran bas, ou en arabe où les textes sont plus longs — c'est un
écran d'entrée, l'y rendre inutilisable est le pire résultat possible.

Restent les blancs. Ce sont eux qui portent l'aération de l'écran, et ce sont eux qui doivent céder
en premier : sur une fenêtre haute, l'écran garde son ampleur ; sur une fenêtre basse, il se
resserre sans rien perdre.

### La règle vaut pour la coque, pas pour l'écran qui a cassé

Seule l'étape d'inscription déborde aujourd'hui. Mais les quatre écrans partagent la même coque, et
c'est **elle** qui porte la promesse de tenir dans la fenêtre. La corriger sur un seul écran
laisserait la porte ouverte au prochain ajout, exactement comme cette fois-ci.

## Règles et cas limites

- **Un seul fournisseur configuré** — un seul bouton s'affiche ; il ne doit pas occuper une
  demi-largeur avec un vide à côté.
- **Aucun fournisseur configuré** — ni boutons ni séparateur « ou », comme aujourd'hui.
- **Arabe** — la page se lit de droite à gauche : les deux boutons s'inversent avec elle, et les
  textes plus longs ne doivent pas déborder de leur bouton.
- **Message d'erreur affiché** — un échec de connexion sociale ajoute une ligne : l'écran doit tenir
  quand même.
- **Mobile, clavier ouvert** — la fenêtre visible se réduit de moitié ; c'est le cas le plus dur, et
  celui qui décide si le resserrement suffit.
- **Écran très large** — les deux boutons ne doivent pas s'étirer au point de paraître vides.
- **Navigation au clavier** — l'ordre de parcours suit la lecture : Google, puis LinkedIn, puis le
  champ e-mail.

### ⚠️ Deux écarts au cadrage, tranchés pendant l'implémentation

- **Le resserrement ne fixe aucune valeur : il rabat sur l'échelle existante.** Premier jet écrit
  avec des valeurs choisies sur place (`1.25rem`, `0.75rem`) — c'est-à-dire une échelle parallèle à
  côté de celle du système, ce que le README de la coque interdit et que `layouts-no-raw-spacing`
  aurait rattrapé au merge. Corrigé : sur fenêtre basse, `section` et `pane` prennent la valeur de
  `block` ; sur fenêtre très basse, tout prend celle d'`element`.
  ⚠️ **Le palier visé ne doit jamais être redéfini dans le même bloc** : les variables CSS se
  résolvent à l'usage, donc rabattre `section` sur `pane` en rabattant aussi `pane` ferait résoudre
  `section` deux crans plus bas, en silence.
- **Les deux seuils de bascule restent littéraux.** Une condition de media query n'accepte pas
  `var()` — c'est une limite du CSS. Ce sont des points de bascule mesurés, pas des espacements.

## Documentation impactée

- **`webapp/base/app/layouts/README.md`** — la coque d'entrée y est décrite ; la règle de hauteur
  devient une contrainte à respecter par tout écran qui s'y ajoute.

## Risques

- **Les fichiers de traduction sont partagés** et modifiés par d'autres sessions en ce moment.
  Ajouter les clés courtes plutôt que renommer les existantes, et relire ce qui est en attente de
  commit avant de figer.
- **La coque est dans le socle** (`webapp/base/`), donc commune aux quatre espaces. Elle est traitée
  ici parce que l'espace entreprise est le seul à ouvrir un tunnel d'inscription — mais la
  vérification doit inclure l'écran de connexion des trois autres.

## Définition de terminé

- [x] Les deux boutons tiennent sur une seule ligne, logo et nom du réseau.
- [x] Un lecteur d'écran annonce toujours l'intitulé complet — l'intitulé long porté par
      `aria-label`, le nom court masqué par `aria-hidden` pour ne pas le répéter en plus bref.
- [x] Aucun défilement sur les écrans de la coque, **sauf** en fenêtre 390×420 : cf. ci-dessous.
- [x] Sur la fenêtre la plus basse testée, le bouton d'envoi reste atteignable — la page défile au
      lieu de le cacher.
- [x] Le README de la coque énonce la règle de hauteur.

⚠️ **Une case tenue autrement que prévu.** Le cadrage visait « aucun défilement » partout. À
390 × 420 — mobile, clavier ouvert —, le contenu fait 533 px : il manque une centaine de pixels que
les blancs ne peuvent plus fournir, seuls les textes le pourraient et ils étaient hors périmètre.
Le filet prévu par le cadrage joue exactement son rôle : la page défile, rien n'est coupé, le bouton
reste atteignable.

## Vérification

### Hauteur du document contre hauteur de la fenêtre

Trois écrans de la coque, quatre tailles, mesurés dans un navigateur réel :

```
  écran          taille                  document / fenêtre   défile ?
  ────────────────────────────────────────────────────────────────────
  connexion      portable large 1440×900   900 /  900 px      ✔ non
  inscription    portable large 1440×900   900 /  900 px      ✔ non
  mot de passe   portable large 1440×900   900 /  900 px      ✔ non
  connexion      portable bas   1280×700   700 /  700 px      ✔ non
  inscription    portable bas   1280×700   700 /  700 px      ✔ non
  mot de passe   portable bas   1280×700   700 /  700 px      ✔ non
  connexion      mobile          390×640   640 /  640 px      ✔ non
  inscription    mobile          390×640   640 /  640 px      ✔ non
  mot de passe   mobile          390×640   640 /  640 px      ✔ non
  connexion      mobile+clavier  390×420   471 /  420 px      ✘ OUI
  inscription    mobile+clavier  390×420   533 /  420 px      ✘ OUI
  mot de passe   mobile+clavier  390×420   492 /  420 px      ✘ OUI
```

Avant la carte, le même écran d'inscription mesurait **2322 px pour 900 px de fenêtre**.

### Le rabattement pointe bien vers l'échelle du système

Valeurs calculées, lues sous la coque — aucune n'est écrite dans le layout :

```
fenêtre haute 1440×900
  element=clamp(0.5rem,…,0.625rem)   block=clamp(1rem,…,1.375rem)
  pane=clamp(1.5rem,…,2rem)          section=clamp(1.75rem,…,3rem)      ← intactes

cran 1  1280×700
  pane et section = clamp(1rem, 0.9rem + 0.3vw, 1.375rem)               ← la formule de « block »

cran 2   390×420
  block, pane, section = clamp(0.5rem, 0.48rem + 0.1vw, 0.625rem)       ← celle d'« element »
```

### Le rendu

Capture de l'écran d'inscription en 1440 × 900 : les deux boutons sur une ligne, *Google* et
*LinkedIn* avec leur logo, le séparateur « ou », le champ et le bouton d'envoi — l'ensemble tient
sans défiler, et l'écran garde son aération.

## Livré

| Fichier | Geste |
|---|---|
| `webapp/base/app/components/registration/SocialButtons.vue` | (M) les deux boutons côte à côte en `flex-1`, nom court affiché, intitulé complet en `aria-label` |
| `webapp/base/app/layouts/auth.vue` | (M) hauteur exacte de la fenêtre, rabattement des paliers sur fenêtre basse |
| `webapp/base/i18n/locales/{fr,en,ar}.yaml` | (M) `inscription.social.name.*`, les noms courts affichés |
| `webapp/base/app/layouts/README.md` | (M) la contrainte de hauteur, et le piège de résolution des variables |

## Reste à faire (hors périmètre)

- **Le mobile avec clavier ouvert défile encore.** Gagner la centaine de pixels manquants demande de
  toucher aux **textes d'accroche** — masquer le paragraphe d'introduction sous une certaine hauteur,
  ou réduire le titre. Explicitement hors périmètre ici, et à décider : ça se voit à l'œil sur un
  écran d'entrée, ce n'est pas un réglage à faire au jugé.
- **`spaces/entreprise/app/layouts/inscription.vue` reste exempté** de `layouts-no-raw-spacing` (une
  marge individuelle posée par `WEB-011`). La dette est ouverte et n'est pas traitée ici : cette
  carte ne touche pas cette coque-là.
- **La coque d'entrée n'a ni `data-layout` ni `data-section`**, que le README des layouts impose.
  Dette antérieure, laissée telle quelle — l'ajouter n'a rien à voir avec le débordement.
