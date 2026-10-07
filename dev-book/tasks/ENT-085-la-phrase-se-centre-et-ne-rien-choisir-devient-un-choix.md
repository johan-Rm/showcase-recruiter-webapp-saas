# TASK ENT-085 — entreprise : la phrase se centre, et ne rien choisir devient un choix

- **Créée le** : 2026-08-11.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-11 — les trois lignes de la phrase se centrent les unes sous les
  autres, « peu importe » s'affiche en couleur d'accent comme une valeur retenue, et les gestes
  cliquables de la colonne des mots-clés portent le curseur en main.
- **Type** : Frontend entreprise — alignement, statut d'une valeur par défaut et affordance de
  curseur sur un écran existant. Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Ce qui change est un alignement, une
  couleur et une forme de curseur. Le webapp n'a aucun harnais de test de composant
  (`webapp/vitest.config.ts` ne couvre que le code pur, sans DOM), et jsdom ne mesure ni position ni
  couleur calculée. **La preuve attendue est un relevé dans la page** — positions des lignes, couleur
  effective des valeurs, curseur calculé — complété d'une capture. Aucune clé de langue ne bouge.
- **Réf** : **`ENT-066` — amont, et partiellement défaite ici** (elle avait étiré le champ « poste »
  et introduit le « choix vide ») · **`ENT-084` — amont** (la carte des mots-clés et son pied) ·
  `webapp/spaces/entreprise/app/components/search/Sentence.vue` ·
  `webapp/base/app/components/ui/TagPicker.vue`

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur la page « Décrivez le profil que
vous cherchez ». À gauche, la carte qui porte la phrase à remplir ; à droite, celle des mots-clés.

**Trois choses clochent.**

1. **La phrase paraît collée à gauche alors que son bloc est centré.** Mesuré : le bloc a exactement
   le même vide de chaque côté — 122 px à 1440, 301 px à 1920 — et son milieu tombe sur celui de la
   carte. Mais **la première ligne s'étire jusqu'au bout** parce que le champ « poste » prend toute
   la place restante, tandis que « avec… » et « niveau… » sont courtes et calées à gauche sous elle.
   L'œil lit donc un bloc de gauche, pas une phrase centrée.

2. **« Peu importe » se donne pour une invite alors que c'est une réponse.** Affiché en gris de
   texte d'aide, il laisse croire qu'on a oublié de répondre. Or ne pas restreindre l'expérience ou
   le niveau **est** un choix, et c'est même celui par défaut.

3. **Les gestes de la colonne des mots-clés ne s'annoncent pas.** Un `<button>` garde le curseur de
   flèche par défaut : ni « Ajouter un mot-clé », ni les croix de retrait, ni la croix du plein écran
   ne disaient qu'on pouvait cliquer.

## Périmètre

- **Les trois lignes de la phrase se centrent** chacune dans le bloc, qui reste centré dans sa carte.
- **Le champ « poste » cesse de s'étirer** et se règle sur ce qu'on tape, comme les deux autres
  valeurs — sans quoi la première ligne empêche le centrage.
- **« Peu importe » devient une valeur affichée**, en couleur d'accent, et non plus une invite grise.
- **Le curseur passe en main** sur le bouton d'ajout de mot-clé, sur les croix de retrait des
  étiquettes et sur la croix de fermeture du panneau en plein écran.

## Hors périmètre

- **Le texte de la phrase**, ses trois critères et leurs libellés, qui ne changent pas.
- **La colonne des mots-clés et son pied**, posés par `ENT-084`.
- **Le curseur des deux sélecteurs de la phrase**, déjà en main.
- **Le réglage « choix vide » de `UiInlineSelect`** : il n'a plus d'appelant après cette tâche, mais
  le retirer du kit dépasse cet écran. Nommé en *Reste à faire*.

## Décisions de cadrage

**Centrer les lignes, et non le bloc — parce que le bloc l'était déjà.** La demande disait
« centrer horizontalement et verticalement » ; la mesure a montré que c'était fait dans les deux
axes. Le défaut était ailleurs : une première ligne pleine largeur au-dessus de deux lignes courtes.
Sans cette vérification, on aurait recentré ce qui l'était et le résultat n'aurait pas bougé.

**Le champ « poste » cesse de s'étirer, et cela défait une décision d'`ENT-066` — assumé.** Cette
tâche l'avait étiré exprès : le panneau de suggestions prend la largeur de son champ, et un champ
étroit y tronquait les intitulés en « Respo… ». **La contrepartie revient donc**, et elle est
acceptée : un intitulé long sera de nouveau coupé dans la liste. Arbitré avec l'utilisateur le
2026-08-11, options et conséquence exposées avant l'écriture. Si la troncature gêne à l'usage, le
remède n'est pas de réétirer le champ mais d'élargir le panneau indépendamment de lui.

**« Peu importe » devient une valeur, ce qui défait l'autre moitié d'`ENT-066`.** Cette tâche avait
introduit un « choix vide » pour que le sélecteur reste vide tant que rien n'est retenu, au motif
que « avec peu importe d'expérience » occupait la phrase de deux mots qui ne disent rien. Le champ
vide s'est révélé pire : il ne distingue pas « je n'ai pas répondu » de « je ne restreins pas ».
Le mot revient donc, et **en couleur d'accent** — la doctrine de la phrase veut que ce qu'on a
choisi porte la couleur de la marque, et ne pas restreindre est un choix.

**Le curseur en main est une correction, pas une préférence.** Un `<button>` n'a pas le curseur de
lien par défaut, contrairement à ce que presque toutes les interfaces laissent croire. Le kit le
pose déjà sur ses boutons et sur les options de liste ; ces trois-là avaient été oubliés.

## Règles et cas limites

- **Le centrage ne touche pas au sens de lecture** : en arabe, les lignes se centrent aussi, et les
  valeurs restent isolées bidirectionnellement.
- **Le champ « poste » garde un plancher de largeur** : vidé, il ne se réduit pas à un trait de deux
  caractères.
- **Le sélecteur affiche toujours son option courante** : choisir « 5 ans » puis revenir à « peu
  importe » réaffiche le mot, en accent, et non un blanc.
- **Le curseur en main ne s'applique qu'à ce qui est réellement cliquable** — pas aux notes du
  panneau (« Recherche en cours… », « Aucun résultat »), qui ne sont pas des options.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni règle métier ne change, et la carte de
navigation ne décrit pas l'alignement interne de la phrase. Aucun `README.md` ne change de
convention.

## Vérification

Relevés pris le 2026-08-11 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`.

**Le centrage — avant, puis après**

| Ce qui est mesuré | Avant | Après |
| --- | --- | --- |
| Vide de part et d'autre du bloc, à 1440 | 122 px / 122 px | inchangé |
| Vide de part et d'autre du bloc, à 1920 | 301 px / 301 px | inchangé |
| Milieu du bloc vs milieu de la carte | 536 vs 536 | inchangé |
| Alignement du texte | `start` | **`center`** |

Le bloc était donc bien centré des deux côtés dès le départ ; c'est l'alignement interne qui a
changé, et c'est ce que l'œil lisait.

**Les valeurs et les curseurs**

| Ce qui est prouvé | Relevé |
| --- | --- |
| « Peu importe » est une valeur, pas une invite | champ non vide, valeur affichée « peu importe » |
| Sa couleur est celle de l'accent | `rgb(198, 231, 107)` — l'accent de la page vaut `#c6e76b` |
| Curseur sur les deux sélecteurs | `pointer` |
| Curseur sur « Ajouter un mot-clé » | `pointer` |

**Capture** : `preuve-valeurs.png`, dans le dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

Aucune gate qualité n'a été jouée : elles appartiennent à l'ouverture de la PR.

## Livré

- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — la phrase se centre ligne à ligne,
  la première rangée centre ses mots, le champ « poste » perd son étirement et la règle de style qui
  le portait, les deux sélecteurs perdent leur « choix vide » et leur invite.
- `webapp/base/app/components/ui/TagPicker.vue` — curseur en main sur le bouton d'ajout, les croix de
  retrait et la croix du plein écran.

## Reste à faire (hors périmètre)

- **Le réglage « choix vide » de `UiInlineSelect` n'a plus d'appelant.** Il reste dans le kit, sans
  usage : à retirer par une tâche de socle, ou à garder si un autre écran en a besoin.
- **Le panneau de suggestions du poste retrouve la largeur de son champ**, donc la troncature des
  intitulés longs revient. Le remède, si besoin, est d'élargir le panneau indépendamment du champ —
  ce que le panneau des mots-clés sait déjà faire.

## Définition de terminé

- [x] Les trois lignes de la phrase sont centrées les unes sous les autres.
- [x] Le champ « poste » se règle sur ce qu'on tape et ne s'étire plus jusqu'au bord.
- [x] « Peu importe » s'affiche comme une valeur retenue, en couleur d'accent.
- [x] Le bouton « Ajouter un mot-clé », les croix de retrait et la croix du plein écran portent le
      curseur en main.
- [x] Capture fournie, et relevés de position, de couleur et de curseur consignés.
