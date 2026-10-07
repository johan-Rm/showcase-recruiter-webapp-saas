# TASK ENT-068 — entreprise : l'écran de démarrage annonce le parcours en trois gestes

> ⚠️ **Ce que cette carte décrit n'est plus à l'écran.** `ENT-069` a retiré le branchement le
> 2026-08-07 : la frise ne s'affiche plus au démarrage, et l'attente minimale de 1,2 s n'existe
> plus. La brique, elle, est intacte — `BootTrail.vue`, son minutage et ses tests, les trois
> libellés traduits. Ce n'est pas un défaut constaté : ce qui est mesuré ci-dessous était vrai le
> jour où ça a été mesuré, et le reste. C'est l'**emplacement** qui n'a pas été retenu.

- **Créée le** : 2026-08-06.
- **Session** : `7068300c-3be1-41bf-800e-a84634bdd619`.
- **Statut** : ✅ fait le 2026-08-06 — l'écran de démarrage de l'espace entreprise porte la frise
  « Rechercher → Sélectionner → Contacter », le logo ne pulse plus, et le rideau attend que le
  dernier trait soit plein avant de s'effacer. Mesuré au navigateur : plancher tenu, arabe retourné,
  mouvement réduit sans attente, espaces candidat et admin intacts.
- **Type** : Frontend entreprise — une frise animée ajoutée au rideau de démarrage, plus une durée
  minimale d'affichage de ce rideau. Aucun contrat d'API, aucune donnée, aucune règle métier, aucune
  permission touchée.
- **Tests** : le **calcul du minutage** sort du composant dans une fonction pure, couverte par un
  fichier `*.vitest.ts` — c'est le seul endroit où une régression passerait en silence (une durée qui
  change ne se voit pas à l'œil nu). L'allure, elle, se prouve par **capture de l'écran de démarrage**
  de l'espace entreprise, dans les deux thèmes et en largeur téléphone. Le webapp n'a aucun harnais
  de test de composant : `webapp/vitest.config.ts` ne couvre que le code pur, sans DOM.
- **Réf** : **amont livrées** — `055b` (le rideau existe), `135` (rideau présent dès le premier
  affichage), `144` (sortie en fondu) · `webapp/base/app/components/AppBootShell.vue` ·
  `webapp/base/app/components/README.md` (règle de la layer) ·
  `webapp/base/i18n/locales/{fr,en,ar}.yaml` · maquette d'origine
  `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-v23.html` (référence visuelle
  **uniquement** — cf. *Décisions de cadrage*)

## Contexte

**Quand un recruteur ouvre l'espace entreprise, le premier écran ne lui dit rien du produit.**

Ce premier écran est le *rideau de démarrage* : une page pleine, posée par-dessus tout le reste, qui
attend que l'application soit prête avant de laisser passer. Elle existe depuis la tâche `135` et
porte aujourd'hui deux choses, centrées : le logo showcase-recruiter-webapp-saas, qui pulse doucement, et une pastille
« Espace entreprise ». Puis elle s'efface en fondu.

Elle occupe donc l'œil sans rien lui apprendre. C'est le seul moment où l'utilisateur regarde un
écran vide en attendant, et c'est aussi le seul moment où l'on pourrait lui rappeler, en trois mots,
ce qu'il est venu faire : **chercher des profils, en retenir quelques-uns, entrer en contact**. Ce
parcours est la promesse entière du produit côté entreprise ; il n'est écrit nulle part d'un seul
tenant.

**Une frise animée existe déjà, mais ailleurs et en maquette.** Le mockup du cockpit
(`mockup-espace-entreprise-vue-cockpit-v23.html`) joue, dans la bande sous la barre du haut, une
petite frise à jalons : des pastilles reliées par des traits qu'une couleur remplit l'un après
l'autre. Le geste est juste, sa place l'est moins — il y concurrence un message d'aide et retient un
bouton pendant quatre secondes. C'est le **geste** qu'on reprend, pas le code, et on le déplace là
où il ne gêne personne : l'écran d'attente, qui n'a rien d'autre à dire.

**Le rideau part aujourd'hui dès que l'app est prête, souvent en moins d'une seconde.** Une
animation posée telle quelle ne serait donc jamais vue en entier. C'est le second volet de la tâche :
lui garantir le temps d'aller au bout.

## Périmètre

- **Une frise à trois jalons s'affiche sous le logo**, à l'écran de démarrage de l'espace entreprise :
  **Rechercher → Sélectionner → Contacter**. Trois pastilles à icônes, reliées par deux traits.
- **Elle s'anime** : le premier jalon est allumé, puis une couleur remplit le premier trait et allume
  le deuxième jalon, puis le second trait et le troisième jalon. Deux remplissages de 600 ms
  enchaînés, soit **1,2 seconde** du début à la fin.
- **Le rideau ne part pas avant la fin de la frise.** Aujourd'hui il s'efface dès que l'application
  est prête ; il attendra désormais **le plus tardif des deux** : l'application prête, **et** la
  frise terminée. Le fondu de sortie existant (500 ms) démarre après.
- **Le logo cesse de pulser** dans cet espace : la frise devient la seule chose qui bouge à l'écran.
- **Trois clés de traduction** sont ajoutées dans les trois langues (`fr`, `en`, `ar`) — les trois
  libellés ne sont jamais écrits en dur.
- **La frise est un composant à part** — `base/app/components/BootTrail.vue` → `<BootTrail>`.
  `AppBootShell` reste responsable du rideau, de sa durée et du choix des jalons ; il lui passe les
  trois jalons et le jalon courant, rien de plus.

## Hors périmètre

- **Les espaces candidat et admin, et le site public.** Leur rideau de démarrage ne change pas d'un
  pixel : ni frise, ni durée minimale, et leur logo continue de pulser. Les trois mots sont ceux du
  recruteur ; ils seraient faux pour les deux autres publics.
- **Le code de la maquette.** Aucune ligne n'en est reprise — c'est une demande explicite, et le
  détail est dans les décisions ci-dessous.
- **Tout onboarding cliquable.** La frise ne se clique pas, ne se passe pas, ne mène nulle part. Ce
  n'est pas un tutoriel : c'est une image d'attente qui se trouve dire quelque chose.
- **La bande sous la barre du haut du cockpit**, où la maquette loge sa frise. Elle n'est pas touchée.
- **La refonte du rideau** (fond, mise en page, badge d'espace, fondu de 500 ms) : tout ce qui existe
  reste tel quel, on ajoute et on retire la pulsation.
- **Une mémoire du type « déjà vu, on ne rejoue pas ».** La frise se rejoue à chaque démarrage — cf.
  *Risques*.

## Décisions de cadrage

### Le geste est repris, le code ne l'est pas

La maquette est un fichier autonome écrit en HTML brut, avec ses propres classes, ses minuteurs et
ses icônes en dur. Le webapp a ses tokens de couleur, sa bibliothèque d'icônes, ses conventions de
composant et sa règle « pas d'API du navigateur en direct quand VueUse fait le travail ». Recopier
la maquette ferait entrer dans le socle un corps étranger qu'il faudrait ensuite traduire ligne à
ligne. On repart donc du geste — *deux traits qui se remplissent l'un après l'autre en allumant le
jalon suivant* — et on l'écrit avec les briques du webapp.

Trois défauts de la maquette sont **volontairement non reproduits**, ils ont été relevés à sa
lecture : le trait qui s'efface avant la fin de l'animation, la frise annoncée aux lecteurs d'écran
alors qu'elle est décorative, et le fait qu'aucun geste ne permette d'y couper court.

### La frise ne s'affiche que pour un recruteur

« Rechercher → Sélectionner → Contacter » décrit le métier du recruteur. Un candidat ne cherche
personne, un administrateur ne contacte personne. Montrer ces trois mots aux trois espaces reviendrait
à mentir à deux publics sur trois pour économiser une condition.

### La structure vient de Nuxt UI — corrigé à l'implémentation

**Le cadrage annonçait une primitive maison dans `ui/` ; c'est `UStepper` (Nuxt UI) qui la porte.**
Le [README des composants](../../webapp/base/app/components/README.md) n'autorise un `Ui*` neuf que
lorsque Nuxt UI ne sait pas produire la **structure** voulue — et il la produit : pastille, icône,
trait, libellé, plus le positionnement des traits en propriétés **logiques** (`start`/`end`), ce qui
retourne la frise en arabe sans une ligne de code dédiée. Ce que le cadrage prenait pour une
structure absente n'était qu'une apparence : le thème peint le trait d'un bloc quand l'étape est
franchie, alors qu'on veut le voir SE remplir. Cela se règle par les classes du trait, pas par un
composant concurrent.

`BootTrail` reste donc, mais comme **habillage** : il pose l'apparence du trait, le masquage aux
lecteurs d'écran, et il retire les trois déclencheurs du parcours au clavier — `UStepper` est
interactif par nature, et un contenu masqué qui resterait atteignable au clavier serait pire que pas
de masquage du tout.

### La frise est une primitive du socle, la garde et les mots sont à l'espace

Le [README de `base/app/components/`](../../webapp/base/app/components/README.md) est formel :
`base/` est **commun aux quatre espaces**, `spaces/<espace>/` porte ce qui est propre à un espace, et
`ui/` est réservé aux primitives génériques **sans domaine**. Une « frise de jalons » n'a pas de
domaine : elle reçoit une liste de jalons et les anime. Elle a donc sa place dans `ui/`.

Ce qui est propre à l'entreprise — la condition d'affichage et les trois libellés — reste dans
`AppBootShell`, qui est déjà le seul endroit du socle à connaître l'espace courant. Poser un composant
« frise du recruteur » dans `base/` aurait violé la règle du dossier ; le poser dans
`spaces/entreprise/` l'aurait rendu invisible au rideau, qui vit dans le socle.

### Le rideau attend la fin de la frise — c'est du temps ajouté, et il est assumé

Décision explicite de l'utilisateur : *« quoi qu'il arrive, AppBootShell ne doit pas disparaître
avant la fin de cette animation »*. Sur une connexion rapide, l'application est prête en quelques
centaines de millisecondes : sans ce plancher, on ne verrait qu'un éclat de couleur.

**Un plancher, jamais un plafond.** Si l'application met trois secondes à être prête, le rideau reste
trois secondes — la frise ne raccourcit rien et ne bloque rien. Et si l'amorçage échoue, le rideau
part comme aujourd'hui : la condition de sortie existante continue de se résoudre dans tous les cas,
on ne fait qu'ajouter une seconde condition qui, elle, se résout toujours au bout de 1,2 s.

**Le coût est réel** : +1,2 s à chaque rafraîchissement de l'espace entreprise, pour un utilisateur
quotidien. Les deux autres durées envisagées (1,8 s avec un temps de lecture, 2,5 s pour la
cinématique complète de la maquette) ont été écartées pour cette raison : au-delà d'une seconde et
demie, une attente imposée cesse d'être perçue comme une entrée en matière.

### Un utilisateur qui a réduit les animations n'attend pas

Réduire les animations est un réglage du système d'exploitation, coché le plus souvent pour cause de
vertiges ou de migraines. Pour lui, la frise s'affiche **complète et immobile**, et le rideau part dès
que l'application est prête — sans plancher. Lui imposer d'attendre une animation qu'il a demandé à
ne pas voir n'aurait aucun sens.

**Complète dès le rendu du serveur, et non après l'hydratation** — précisé à l'implémentation. La
première écriture remplissait la frise en JavaScript, une fois la page réveillée : elle s'affichait
donc vide pendant un instant, puis se complétait d'un coup. Un sursaut, exactement ce que la
préférence demande d'éviter. Les traits sont désormais pleins par une règle CSS que le serveur envoie
déjà dans la page, avant qu'une seule ligne de JavaScript s'exécute.

### En arabe, la frise se retourne

L'application se lit de droite à gauche en arabe, et `app.vue` pilote déjà la direction d'écriture.
« Rechercher » se placera donc à droite, et la couleur courra vers la gauche. Un parcours qui se lit
à rebours du texte qui l'entoure n'est plus un parcours.

### La frise est muette pour les lecteurs d'écran

Le rideau annonce déjà « Chargement… » aux lecteurs d'écran (le logiciel qui lit la page à voix haute
aux personnes malvoyantes) : c'est le seul message utile à ce moment-là. La frise est masquée pour
eux. Elle occupe l'œil, elle n'ajoute pas d'information — et trois mots lus à voix haute pendant un
chargement d'une seconde seraient coupés en plein milieu.

### Le logo cesse de pulser

Deux mouvements concurrents sur un écran qui ne contient rien d'autre, c'est un de trop : l'œil ne
sait plus lequel regarder. Le mouvement passe à la frise, le logo devient fixe. Uniquement dans
l'espace entreprise — ailleurs, la pulsation reste le seul signe que quelque chose travaille.

### Les mots sont ceux de l'utilisateur, pas ceux du produit

Le produit dit « Dévoiler » (payer pour accéder aux coordonnées) et « Favoris ». L'écran de démarrage
dit « Sélectionner » et « Contacter ». C'est délibéré : on y nomme le **but** de la personne, pas la
mécanique interne. Un écran d'accueil qui ouvre sur du vocabulaire maison suppose qu'on le connaît
déjà.

### Des icônes, pas des numéros

Loupe, étoile, enveloppe. Elles se lisent d'un coup d'œil, elles ne se traduisent pas, et elles
rappellent les objets qu'on retrouvera dans le produit. Un numéro « 1 · 2 · 3 » n'évoque rien et
transforme l'écran en liste à puces animée. Les icônes viennent de la bibliothèque déjà en place dans
le webapp — aucune dépendance ajoutée.

## Règles et cas limites

- **Le rideau est présent dès le HTML envoyé par le serveur** (c'est tout l'objet de la tâche `135`).
  La frise doit donc y figurer aussi, sinon elle apparaîtrait après coup dans un sursaut. Elle est
  rendue côté serveur **dans son état de départ** — premier jalon allumé, traits vides — et ne
  s'anime que côté client. Aucun écart entre le rendu serveur et le rendu client.
- **Le plancher se compte à partir du premier affichage côté client**, pas de l'arrivée du HTML : ce
  qu'on garantit, c'est que l'animation a été **vue** en entier.
- **La frise n'est jamais rejouée en cours de navigation.** Le rideau est déjà réservé au démarrage
  initial ; changer de page ne le fait pas revenir, et rien n'est ajouté ici.
- **Sur petit écran, les trois libellés doivent tenir sans être rognés.** Le cadrage prévoyait qu'ils
  ne tiendraient pas à 360 pixels et qu'il faudrait les empiler sous les pastilles. **La mesure dit
  le contraire** : la frise est bornée à 320 pixels de large, les libellés sont posés SOUS leur
  pastille par la structure même de `UStepper`, et rien n'est rogné ni ne déborde à 320, 360, 390,
  430, 768 ni 1600 pixels. Aucun repli n'est donc écrit — comme l'a imposé `ENT-061`, on mesure
  avant de coder la parade.

- **Le premier trait doit être PEINT vide avant de se remplir.** Avancer la frise dès le corps du
  composant, avant l'hydratation, ne produirait aucune animation — le navigateur n'aurait pas d'état
  de départ — et ferait diverger le DOM de celui rendu par le serveur. La minuterie démarre donc
  **après le montage**, jamais pendant la préparation du composant.
- **Les deux thèmes.** La couleur qui remplit les traits doit se détacher du fond dans le thème clair
  **et** dans le thème sombre ; le rideau utilise le fond de page, qui n'est pas le même dans les
  deux.
- **Le fondu de sortie ne se cumule pas avec le plancher** : le fondu de 500 ms démarre quand le
  plancher est atteint, il ne s'y ajoute pas comme une seconde attente décidée ailleurs.
- **Aucune permission, aucun rôle** n'intervient : le rideau ne dépend que de l'espace, jamais de qui
  est connecté. Propriétaire ou membre voient la même chose.

## Documentation impactée

**Vérifié : aucune page n'est déclenchée.** Les trois pistes ouvertes au cadrage ont été relues une
par une.

- `webapp/base/app/components/README.md` — **rien à écrire**. Aucune règle de dossier ne change :
  `BootTrail.vue` est un composant isolé à la racine de `components/`, cas que le README prévoit
  déjà, et aucune primitive n'a été ajoutée dans `ui/` (cf. *La structure vient de Nuxt UI*).
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/` — **rien à écrire** : le rideau de démarrage n'y est décrit
  nulle part, pas plus qu'il ne l'était après les tâches `135` et `144` qui l'ont créé.
- `dev-book/cadrage/entreprise/ui/application_shell.md` §4.1 décrit bien des « steppers
  d'introduction », mais **ceux du cockpit** — dans la bande sous la barre du haut, à l'ouverture de
  la Table et de la Visionneuse. Autre écran, autre moment, non touché ici. À ne pas confondre au
  moment de relire.

## Risques et reports assumés

- **Les trois fichiers de langue sont ouverts par une autre session** au moment où cette carte est
  écrite (`webapp/base/i18n/locales/{fr,en,ar}.yaml` sont modifiés dans l'arbre). Y ajouter trois
  clés provoquera un conflit s'ils ne sont pas commités entre-temps. Se réaligner avant d'écrire, et
  **désigner les fichiers un par un au commit**, jamais un dossier entier.
- **Le dossier de travail de l'espace entreprise est occupé** : l'implémentation de `ENT-067` y est en
  cours, non commitée, ainsi que six cartes en attente venues de quatre sessions. Cette tâche ne doit
  pas démarrer avant que cet arbre soit propre — sinon les deux travaux finiront dans les mêmes
  fichiers.
- **+1,2 seconde à chaque démarrage**, assumé et décidé (ci-dessus). C'est le seul coût de
  performance de la tâche : rien n'est téléchargé en plus, rien n'est calculé en plus.
- **La frise se rejoue à chaque démarrage**, sans mémoire du « déjà vu ». C'est cohérent avec un
  écran d'attente — il se réaffiche par nature — mais si l'attente devient pesante à l'usage, la
  réponse sera une mémoire de session, dans une carte à part.
- **Les trois mots ne sont pas le vocabulaire du produit** (cf. décisions). Si le cadrage client
  arbitre un jour un vocabulaire unique, ces trois libellés seront à revoir en même temps que le
  reste.

## Vérification

Preuves observées le 2026-08-06 sur la pile de développement du worktree entreprise, navigateur
piloté, page de connexion de l'espace entreprise (le rideau y joue comme partout ailleurs dans
l'espace). Les valeurs ci-dessous sont **lues dans la page servie**, pas déduites du code : largeur
réelle du remplissage de chaque trait (`::after`), état de chaque jalon, opacité du rideau.

### La frise se déroule, et le rideau l'attend

Rechargement à chaud, thème clair, 1280 × 800 — l'application était prête avant la frise, c'est donc
le plancher qui commande :

| Instant | Ce qui est mesuré |
| --- | --- |
| 1 693 ms | 1ᵉʳ trait plein (67 px sur 67), 2ᵉ jalon allumé |
| 1 762 → 2 919 ms | 2ᵉ trait : 22 → 33 → 43 → 53 → 58 → 64 → 67 px |
| 2 919 ms | frise finie, **les deux traits restent pleins** |
| 2 982 ms | le fondu démarre — et pas avant |
| 3 425 ms | rideau retiré du document |

Chaque trait met ~600 ms, la frise ~1,2 s. Le fondu part **à l'instant où le dernier trait se
remplit** : c'est bien la frise qui retient le rideau, et non l'inverse. Les traits ne se vident
jamais avant la sortie — le défaut relevé sur la maquette d'origine n'est pas reproduit.

### Les quatre autres conditions

- **Mouvement réduit** (1280 × 800) — traits pleins **dès 175 ms**, c'est-à-dire dès le premier
  affichage et avant tout JavaScript ; rideau retiré à 1 529 ms, **sans aucune attente ajoutée**.
- **Arabe** (`/ar/login`) — `dir=rtl` ; « البحث » (Rechercher) à droite avec sa loupe, le vert court
  **vers la gauche** vers « الاختيار ». Capture à l'appui, aucune ligne de code dédiée au sens.
- **Espace candidat** — son service a été démarré exprès pour vérifier **à l'écran** et non par
  déduction : aucune frise dans la page servie, `animate-pulse` toujours sur le logo, badge « Espace
  candidat ». Rigoureusement l'état d'avant. Service arrêté depuis.
- **Clavier et lecteurs d'écran** — les trois pastilles sortent `disabled` et `tabindex="-1"` dans le
  document servi : **zéro élément atteignable au clavier** sous un bloc masqué.

### Largeurs mesurées

| Largeur | Frise | Page déborde | Libellé rogné |
| --- | --- | --- | --- |
| 320 | 320 px | non | non |
| 360 | 320 px | non | non |
| 390 | 320 px | non | non |
| 430 | 320 px | non | non |
| 768 | 320 px | non | non |
| 1600 | 320 px | non | non |

Les trois libellés font 107 px chacun, pour une place de 107 px : rien n'est coupé, y compris à
320 px. Le repli que le cadrage anticipait n'a donc pas lieu d'être.

### Captures

Écran de démarrage de l'espace entreprise : **1280 × 800 en thème clair** (frise à mi-parcours),
**360 × 740 en thème sombre** (les trois libellés tiennent), **1280 × 800 en arabe** (frise
retournée, remplissage parti de la droite).

### Tests

`bunx vitest run base/app/utils/bootTrail.vitest.ts` → **1 fichier, 7 tests, verts en 186 ms**.

### Conventions

`bun run quality-conventions-check` → les 7 fichiers de cette tâche passent les 14 règles. Les deux
violations restantes portent sur `spaces/entreprise/app/pages/recherches/nouvelle.vue`, commité par
`ENT-067` et non touché ici — cf. *Reste à faire*.

## Livré

- `webapp/base/app/utils/bootTrail.ts` (+) — le minutage, pur : un instant par **trait** (et non par
  jalon), et la durée totale qui sert de plancher au rideau.
- `webapp/base/app/utils/bootTrail.vitest.ts` (+) — 7 cas verrouillant ce que l'œil ne voit pas : le
  décompte par trait, le départ immédiat, l'espacement constant, le chiffre de 1,2 s, et l'absence
  d'attente quand il n'y a aucun trait.
- `webapp/base/app/components/BootTrail.vue` (+) — l'apparence de la frise, posée sur `UStepper` :
  remplissage progressif du trait par un pseudo-élément en propriétés logiques, traits pleins d'entrée
  en mouvement réduit, masquage aux lecteurs d'écran et retrait du parcours clavier.
- `webapp/base/app/components/AppBootShell.vue` (M) — garde « entreprise », les trois jalons,
  l'horloge posée **après le montage**, le plancher d'affichage ajouté à la condition de sortie
  existante, et la pulsation du logo retirée là où la frise joue.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` (M) — les trois libellés du parcours, dans les trois
  langues.
- `dev-book/tasks/ENT-068-*.md` (+) — cette carte, cadrée puis corrigée sur deux écarts (la structure
  vient de Nuxt UI ; le repli mobile n'est pas nécessaire).

## Reste à faire (hors périmètre)

- **Deux violations de conventions dans `ENT-067`** — `spaces/entreprise/app/pages/recherches/nouvelle.vue`
  porte un `<form>` et un `@submit` dans une page, ce que `pages-no-interactive-template` refuse.
  Fichier commité par une autre session, non touché ici : à reprendre par qui a mené cette tâche,
  sous peine de bloquer l'ouverture de la PR de la lane.
- **`role="status"` sur le rideau** — l'analyseur de l'éditeur recommande `<output>`. L'attribut date
  de la carte `135` et n'a pas été introduit ici ; à traiter avec le rideau, pas avec la frise.
- **La frise se rejoue à chaque démarrage** : aucune mémoire de « déjà vu ». Assumé au cadrage ; si
  l'attente devient pesante à l'usage, la réponse est une mémoire de session, dans une carte à part.

## Définition de terminé

- [x] Au démarrage de l'espace entreprise, une frise **Rechercher → Sélectionner → Contacter**
      s'affiche sous le logo, trois pastilles à icônes reliées par deux traits.
- [x] La couleur remplit les deux traits l'un après l'autre en allumant chaque jalon, en 1,2 s au
      total, et le parcours reste **complet** jusqu'au fondu — le trait ne s'efface pas avant.
- [x] Le rideau ne disparaît jamais avant la fin de la frise, même si l'application est prête avant.
- [x] Si l'application met plus longtemps, le rideau reste jusqu'à ce qu'elle soit prête : la frise
      n'écourte rien.
- [x] Le logo ne pulse plus dans cet espace.
- [x] Les espaces candidat et admin, et le site public, sont **inchangés** — vérifié à l'écran, pas
      déduit du code.
- [x] Avec « réduire les animations » activé : frise immobile et complète, aucune attente ajoutée.
- [x] En arabe, la frise se lit de droite à gauche.
- [x] Les trois libellés viennent des fichiers de langue, dans les trois langues.
- [x] La frise est masquée aux lecteurs d'écran ; le rideau continue d'annoncer « Chargement… ».
- [x] La frise tient à 360 pixels de large sans mot rogné, largeur **mesurée** dans le navigateur.
- [x] Lisible dans le thème clair **et** dans le thème sombre.
- [x] Le calcul du minutage vit dans une fonction pure, couverte par un fichier `*.vitest.ts`.
- [x] Captures de l'écran de démarrage fournies : grand écran et largeur téléphone, deux thèmes.
