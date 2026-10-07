# TASK ENT-122 — entreprise : la barre haute prend son troisième état

- **Créée le** : 2026-08-14.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff`.
- **Statut** : ✅ fait le 2026-08-14 — le menu de la sélection prend la place des filtres dans la
  barre haute, et le bloc du tableau ne porte plus que ses lignes.
- **Type** : refactor — le menu de la sélection remonte dans la barre haute, la bande qui le portait
  disparaît, et la grammaire des deux bandes se réécrit dans le cadrage du shell.
- **Tests** : **aucun test nouveau**, et ce n'est pas un oubli. Le déplacement d'un menu ne produit
  aucune fonction pure : le compte à payer par canal vit déjà dans `services/search/reveal.ts` et
  `reveal.vitest.ts` le couvre en cinq cas ; ce qui reste est un rendu conditionnel d'une ligne
  (« il y a une sélection ou non »), et le dépôt n'a aucun harnais pour monter un composant — les
  suites front ne portent que du code pur. Extraire une fonction pour ce booléen produirait un test
  qui coûterait plus à lire qu'il ne protège.
- **Réf** : `ENT-116` — la carte que celle-ci **renverse** en partie · `ENT-117` — le fil, les
  onglets inversés et l'état vide, commitée · `ENT-115` — la colonne « Retenir », qui reste ·
  [application_shell.md](../cadrage/entreprise/ui/application_shell.md) §4 et §4.1 ·
  [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) §2 et §5 ·
  [credits_et_devoilement.md](../cadrage/entreprise/api/credits_et_devoilement.md) — le coût et ses
  garde-fous · [components/README.md](../../webapp/base/app/components/README.md)

## Contexte

Cocher une case sur le tableau fait apparaître une **troisième bande horizontale**, à l'intérieur du
bloc du tableau, qui pousse les lignes vers le bas et repart quand on décoche. L'écran porte donc
deux grammaires à la fois : une bande qui **alterne son contenu** — la barre haute, avec le nom à
gauche et les onglets à droite — et une bande qui **apparaît et disparaît**.

C'est `ENT-116` qui l'a placée là, et son motif était juste : ces commandes vivaient auparavant dans
la gouttière, à trois rangées des lignes qu'elles visent, dans une bande dont le cadrage dit qu'elle
« ne doit jamais peser visuellement ». Les en sortir était nécessaire. Le choix de destination, lui,
a produit l'empilement.

**La barre haute existe déjà, et sa droite change de contenu par construction** : la jauge et le rang
sur la Visionneuse, les onglets sur le tableau. Y loger un troisième état n'invente rien — ça se sert
d'une bande faite pour ça, et ça évite d'en ajouter une quatrième.

## Ce que cette carte renverse, et ce qu'elle garde

`ENT-116` a écrit dans le cadrage du shell : *« tout geste qui engage vit dans la zone de travail, au
contact de sa cible »*. Cette carte **remplace cette règle**, elle ne la contourne pas.

Ce qui l'emporte, dit franchement : l'argument de distance perd sa force ici. La barre haute est
**une rangée** au-dessus des entêtes, pas trois comme la gouttière. Ce qui rendait l'ancienne place
indéfendable, c'était l'éloignement **doublé** d'une bande qui ne doit pas peser ; ici, ni l'un ni
l'autre. Et six commandes repliées en un menu, c'est l'intuition du cadrage d'origine — `✓ 3
sélectionnés ▾` — enfin posée à un endroit qui la supporte.

**Ce qui survit de `ENT-116`, intact** : le coût affiché par canal, recalculé hors profils déjà
payés. C'était son apport réel, il ne dépend pas du placement — il descend du bouton vers l'entrée
de menu.

## Périmètre

- **La droite de la barre haute prend un troisième état** : les onglets tant qu'aucun profil n'est
  coché, le menu de la sélection dès qu'il y en a un. Jamais les deux.
- **Le déclencheur porte le compte**, suivi du rappel des sélectionnés hors de la vue courante s'il y
  en a : « ✓ 3 sélectionné(s) · 2 hors de cette vue ▾ ».
- **Une croix visible à côté du déclencheur**, hors du menu, vide la sélection en un clic.
- **Le menu** porte, dans cet ordre : les quatre canaux de dévoilement **avec leur coût** et
  l'export — réservés à l'auteur de la recherche —, puis, après un séparateur, « Voir les profils
  sélectionnés » et « Tout désélectionner », qui restent offerts à tous.
- **« Voir les profils sélectionnés » bascule sur l'onglet « Tous »** sans rien décocher, et écrit
  la vue dans l'adresse comme le ferait l'onglet.
- **La barre de sélection au contact des lignes disparaît**, et le bloc du tableau ne porte plus que
  ses entêtes et ses lignes.
- **Dévoiler et exporter consomment la sélection** : les cases se décochent, les onglets reviennent.
- **En lecture seule** — recherche partagée par un collègue —, le menu se réduit aux deux sorties :
  ni canaux de dévoilement, ni export, que le serveur refuse à qui n'est pas l'auteur.
- **Le cadrage suit** : la règle des trois zones devient la grammaire des deux bandes.

## Hors périmètre

- **Le seuil de confirmation** (au-delà de dix profils payants) et **le plafond de lot** (cinquante) :
  inchangés. Les déplacer engage la sécurité des crédits pour toute l'application.
- **La fenêtre d'export** et son engagement RGPD, **la fenêtre de confirmation du dévoilement** :
  inchangées, seul le chemin qui y mène bouge.
- **La Visionneuse.** Elle n'a pas de sélection en lot, donc pas de troisième état à prendre : sa
  droite garde la jauge et le rang. La grammaire l'englobe sans rien lui demander.
- **Le fil d'Ariane qui répète le nom de la recherche** — le nom est écrit deux fois, dans le fil et
  dans le titre deux rangées plus bas. Défaut réel, nommé dans la même conversation, et qui fera sa
  carte.
- **La colonne « Retenir »** et l'absence de geste en lot pour retenir : tranchées en `ENT-115` et
  `ENT-116`, non rouvertes.
- **La pagination du tableau**, toujours ouverte.

## Décisions de cadrage

### La grammaire, en deux lignes, pour tous les écrans

> **La gouttière situe ou informe** — fil d'Ariane, notification, aide.
> **La barre haute porte le nom, et à sa droite un état contextuel** : les filtres, l'avancement, ou
> les actions sur la sélection. Un seul à la fois.

Elle remplace la règle des trois zones. Elle est plus courte, elle s'énonce sans exception, et elle
décrit ce que les deux écrans font déjà — au lieu de nommer une zone de travail qui devenait
l'endroit où l'on posait ce qui n'allait nulle part ailleurs.

### Les actions remplacent les filtres, elles ne les rejoignent pas

L'autre issue — les deux côte à côte — sauvait un parcours : cocher trois profils dans « Retenus »,
basculer sur « Tous », en ajouter deux, dévoiler les cinq. Elle a été écartée : la droite de la barre
porterait deux objets, et il faudrait arbitrer lequel se replie sous 1024 px.

**Conséquence assumée** : on ne change plus de vue tant qu'une sélection est active.

### Le compteur des hors-vue SURVIT — l'inverse a été affirmé puis corrigé

Il a d'abord été annoncé que « N hors de cette vue » deviendrait inatteignable, puisqu'on ne peut
plus filtrer pendant une sélection. **C'est faux**, et le magasin le dit en toutes lettres : la
sélection est portée par la recherche et *« survit à un aller-retour vers une autre recherche — ce
qu'on avait coché est encore là au retour »*.

Le chemin est donc réel : cocher trois profils depuis « Tous », partir sur la Visionneuse, revenir.
L'écran s'ouvre sur « Retenus », la sélection est restaurée, et des profils cochés sont hors de la
vue affichée. Le compteur s'affiche — et **c'est pour ça que le menu porte « Voir les profils
sélectionnés »** : sans cette entrée, l'écran annoncerait un problème sans offrir le geste qui le
résout.

### La croix est visible, hors du menu

La sortie d'un état qui **masque quelque chose** ne se cache pas dans ce qu'il masque. Reléguer
« Tout désélectionner » au seul menu demanderait de deviner que la sortie est là-dedans, et coûterait
deux clics pour revoir des onglets qu'on n'a pas choisi de perdre.

### L'action consomme la sélection

Sans ça, on reste enfermé après avoir agi : les lignes se dé-floutent sous les yeux, et les onglets
restent masqués tant qu'on n'a pas pensé à sortir. C'est aussi la règle que le cadrage d'origine
écrivait déjà pour le geste en lot — « l'action la consomme ».

Écarté : **distinguer selon l'action** — l'export garderait la sélection, le dévoilement non. Plus
juste à l'usage peut-être, mais deux gestes voisins dans le même menu qui ne se comportent pas pareil
s'expliquent mal et se retiennent moins bien.

### En lecture seule, le menu raccourcit au lieu de s'éteindre

Un menu dont la moitié des entrées est grisée fait chercher pourquoi. Un menu court dit ce qui est
possible.

> ⚠️ **Corrigé à l'implémentation.** Le cadrage laissait l'export dans le menu, au motif qu'« il ne
> modifie rien chez personne ». C'est faux : le serveur le refuse — *« L'export est réservé à
> l'auteur de la recherche »* —, et l'offrir produirait un refus après coup. En lecture seule, le
> menu ne porte donc que **« Voir les profils sélectionnés »** et **« Tout désélectionner »**. Le
> code actuel masquait déjà l'export dans ce cas ; c'est la carte qui se trompait, pas lui.

> L'étoile de la colonne « Retenir » suit la règle inverse — présente et inactive — et ce n'est pas
> une incohérence : **elle est seule à sa place**. La faire disparaître laisserait une colonne vide
> dont personne ne saurait dire ce qu'elle attend.

### Le déclencheur ne porte pas de coût

« Jusqu'à 3 cr. » aurait annoncé l'ordre de grandeur avant l'ouverture. Écarté : les quatre canaux
ont des coûts différents, et un chiffre unique sur le déclencheur en inventerait un cinquième qui ne
correspond à aucun geste réel. Le déclencheur dit **sur quoi** on va agir, le menu dit ce que ça
coûte.

## Règles et cas limites

- **Sélection vide** → les onglets, et rien d'autre. Décocher la dernière case les ramène.
- **Sélection héritée à l'arrivée** → le menu, le compte des hors-vue, et l'entrée qui bascule sur
  « Tous ». C'est le cas que le compteur sert.
- **Canal entièrement payé sur la sélection** → l'entrée affiche zéro et devient inactive : proposer
  un geste qui ne ferait rien est pire que ne rien proposer.
- **Solde insuffisant** → comportement actuel, la fenêtre de confirmation le dit.
- **Lot au-delà du plafond** → message actuel, non déplacé.
- **Recherche close** → consultable, gestes d'écriture inactifs comme ailleurs.
- **Largeur contrainte** : la droite de la barre déborde dans son propre défilement horizontal, comme
  aujourd'hui — le menu replié tient de toute façon dans deux contrôles.
- **Accessibilité** : le focus **ne bouge pas** quand les onglets s'effacent — il reste sur la case
  qu'on vient de cocher, sinon cocher trois profils au clavier deviendrait impossible. Le changement
  est annoncé poliment, le déclencheur expose son état d'ouverture, et la croix porte un libellé
  explicite plutôt qu'une icône nue.
- **Trois langues** : les libellés du déclencheur, de l'entrée « Voir les profils sélectionnés » et
  de la croix sont posés dans les trois fichiers — `search.selection.count`, `hidden` et `clear`
  existent déjà et se réemploient.

## Documentation impactée

- [application_shell.md](../cadrage/entreprise/ui/application_shell.md) — §4.1, la règle des trois
  zones remplacée par la grammaire des deux bandes ; §4, l'exemple de guidage qui dit « dévoilez
  leurs coordonnées depuis la barre du tableau » devient faux et doit suivre.
- [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) — §2, la figure d'anatomie qui dessine
  la barre au contact des lignes ; §5, la sélection et ce que porte son menu.

## Vérification

**Interaction réelle** dans le navigateur, stack du worktree entreprise, recherche « Développeur
logiciel » (352 profils, 1 retenu). Deux profils cochés depuis l'onglet « Tous », puis lecture du DOM
de la barre haute :

```
sans sélection : Développeur logiciel    Retenus 1 · Tous 352
avec sélection : Développeur logiciel    ✓ 2 sélectionné(s)        ✗
                 → onglets présents dans la page : 0
menu ouvert    : E-mail · 2 cr. | Téléphone · 2 cr. | CV · 2 cr. | Compte rendu · 2 cr. | Exporter
                 ─────────────
                 Voir les profils sélectionnés | Tout désélectionner
croix de sortie présente : 1
```

**Le cas qui justifie « Voir les profils sélectionnés » est reproduit** — et il ne l'a pas été du
premier coup. Une première tentative rechargeait la page, ce qui vide le magasin, et concluait à tort
que la sélection ne survivait pas. En repassant par le **fil d'Ariane** puis par le **lien de la
console** — les chemins qu'un recruteur emprunte réellement, sans rechargement — la sélection tient
et la barre affiche :

```
au retour      : ✓ 2 sélectionné(s) · 2 hors de cette vue        ✗
```

Sans l'entrée du menu, ces deux profils seraient annoncés sans aucun moyen d'y accéder : les onglets,
masqués par la sélection, ne sont plus là pour le faire.

**La bande a bien disparu du bloc du tableau** : cocher ne décale plus les lignes.

**Le reste de la définition de terminé, éprouvé par interaction** — chaque mesure attend sa
condition, elle ne se prend pas après un délai fixe (une première tentative mesurait trop tôt et
concluait à tort que la sélection ne se vidait pas) :

```
1 coché                    → onglets dans la page : 0
0 coché                    → onglets dans la page : 1
après la croix             → onglets : 1, déclencheur : 0        (un seul clic)
dévoilement de l'e-mail    → la sélection se vide, onglets : 1
le même profil re-coché    → « E-mail · 0 cr. », aria-disabled = true
```

**Deux cases ne sont PAS observées**, et le disent :

- **la lecture seule** — il aurait fallu une recherche partagée par un autre compte ; le
  raccourcissement du menu est déduit de la garde `editable` qui ouvre le premier groupe ;
- **le clavier** — ce qui est observé, c'est que le focus **n'est pas volé** quand les onglets
  s'effacent : la frappe suivante est reçue et la sélection s'enregistre. Cocher trois profils
  d'affilée demande de tabuler à travers l'étoile et la ligne, ce que cette tâche ne change pas.

**Tests** : `vitest run base/i18n/locales/parity.vitest.ts services/search/reveal.vitest.ts` →
**2 fichiers, 21 tests verts en 1,19 s**. Aucun test ajouté, comme la ligne `**Tests**` l'annonce.

## Livré

- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` — le menu de la sélection et son
  déclencheur dans la barre haute, la croix de sortie, la bande du bloc du tableau supprimée, et le
  vidage de la sélection après un dévoilement ou un export.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — `search.selection.show`, la seule clé nouvelle.
- `dev-book/cadrage/entreprise/ui/application_shell.md` — §4 réécrit (« situer ou informer »), §4.1
  porte la grammaire des deux bandes à la place de la règle des trois zones, l'exemple de guidage ne
  renvoie plus à une barre disparue, et deux décisions validées remplacent l'ancienne.
- `dev-book/cadrage/entreprise/ui/vue_la_table.md` — figure d'anatomie, §5 réécrit avec le menu et
  ses raisons, §5.1 sur la sélection qui survit à la navigation, décision validée alignée.

## Reste à faire (hors périmètre)

- **On ne change plus de vue tant qu'une sélection est active.** Conséquence assumée du choix
  « les actions remplacent les filtres » ; « Voir les profils sélectionnés » la rattrape, et c'est à
  l'usage qu'on saura si ça suffit.
- **Le nom de la recherche est écrit deux fois** sur cet écran — dans le fil d'Ariane et dans le
  titre deux rangées plus bas. Défaut réel, nommé dans la même conversation, pas encore cardé.
- **Le seuil de confirmation reste à onze profils payants** : en deçà, le clic débite sans rien
  demander.

## Écart au cadrage

**L'export n'est pas offert en lecture seule**, contre ce que la carte prévoyait d'abord. Le motif
écrit — « il ne modifie rien chez personne » — était faux : le serveur le refuse en toutes lettres,
*« L'export est réservé à l'auteur de la recherche »*. La carte a été corrigée **avant** le code, et
le comportement retenu est celui que la page appliquait déjà.

## Définition de terminé

- [x] Aucune bande n'apparaît dans le bloc du tableau : cocher ne déplace plus les lignes.
- [x] Cocher remplace les onglets par le menu de la sélection ; décocher la dernière case les ramène.
- [x] La croix vide la sélection en un clic, sans ouvrir le menu.
- [x] Le coût par canal exclut ce qui est déjà payé — observé : « E-mail · 0 cr. » sur un profil
      dont l'e-mail vient d'être dévoilé.
- [x] Un canal entièrement payé sur la sélection est inactif et affiche zéro.
- [x] Arriver avec une sélection héritée affiche le compte des hors-vue **et** l'entrée qui bascule
      sur « Tous » ; l'entrée bascule sans rien décocher.
- [x] Dévoiler vide la sélection et fait revenir les onglets ; exporter aussi.
- [x] Sur une recherche partagée, le menu ne porte que « Voir les profils sélectionnés » et
      « Tout désélectionner » — *déduit de la garde `editable` ; non observé, faute d'un second
      compte*.
- [x] Au clavier, **le focus n'est pas volé** quand les onglets s'effacent — observé. *Cocher trois
      profils d'affilée traverse l'étoile et la ligne, ce que cette tâche ne modifie pas.*
- [x] `application_shell.md` porte la grammaire des deux bandes, et son exemple de guidage ne renvoie
      plus à une barre qui n'existe plus.
- [x] `vue_la_table.md` décrit le menu tel qu'il est, figure d'anatomie comprise.
