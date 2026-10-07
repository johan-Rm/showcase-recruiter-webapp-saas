# TASK ENT-069 — entreprise : la frise quitte l'écran de démarrage et attend son heure

- **Créée le** : 2026-08-07.
- **Session** : `7068300c-3be1-41bf-800e-a84634bdd619`.
- **Statut** : ✅ fait le 2026-08-07 — l'écran de démarrage a retrouvé son logo qui pulse, sans frise
  et sans attente imposée : le rideau repart dès que l'application est prête. La frise, son minutage
  et ses 7 tests, et les trois libellés traduits sont conservés en l'état.
- **Type** : Frontend entreprise — un branchement retiré. Aucun contrat, aucune donnée, aucune règle
  métier, aucune permission touchée. La brique reste, seul son emploi disparaît.
- **Tests** : `aucun test nouveau — rien de nouveau ne se calcule`. Les 7 cas de
  `webapp/base/app/utils/bootTrail.vitest.ts` restent en place et verts : ils couvrent le minutage,
  qui ne bouge pas d'une milliseconde. La preuve attendue est **l'écran de démarrage observé** :
  plus de frise, le logo qui pulse à nouveau, et le rideau qui repart dès que l'application est
  prête.
- **Réf** : **`ENT-068` — amont directe**, dont cette carte retire le branchement sans rien détruire
  de ce qu'elle a construit · `webapp/base/app/components/AppBootShell.vue` ·
  `webapp/base/app/components/BootTrail.vue` (conservé) · **aval : rebrancher la frise**, carte à
  écrire le jour où on le décide

## Contexte

**`ENT-068` a livré deux choses d'un coup, et une seule est retenue.**

Elle a construit une **frise de jalons** — des pastilles reliées par des traits qu'une couleur
remplit l'un après l'autre —, et elle l'a **posée sur l'écran de démarrage** de l'espace entreprise,
avec ses trois mots « Rechercher → Sélectionner → Contacter » et une attente minimale d'une seconde
et deux dixièmes.

Après l'avoir vue tourner, la décision est prise : **la brique est bonne, l'emplacement n'est pas
retenu pour l'instant.** Ce n'est pas un défaut constaté — la frise fait exactement ce que la carte
annonçait, et les mesures sont dans `ENT-068`. C'est un arbitrage : on ne veut pas, aujourd'hui,
ajouter une seconde et deux dixièmes à chaque ouverture du produit pour une animation dont la place
n'est pas encore arrêtée.

**Le coût de laisser en l'état serait payé par tout le monde, tous les jours.** L'attente imposée est
la seule chose de `ENT-068` qui se voit sans qu'on l'ait demandée : elle retarde l'entrée dans le
produit à chaque rafraîchissement. La retirer coûte une ligne ; la laisser coûte une seconde par
démarrage, indéfiniment.

## Périmètre

- **`AppBootShell` revient exactement à son état d'avant `ENT-068`** : logo qui pulse, badge
  d'espace, fondu de sortie, et le rideau qui s'efface dès que l'application est prête. Aucune
  attente minimale, aucune frise, aucune garde d'espace.
- **La carte `ENT-068` est amendée** d'un renvoi vers celle-ci : livrée comme « fait », elle
  décrirait sinon un écran qui n'existe plus.

## Hors périmètre — ce qui reste en place, intact

- **`webapp/base/app/components/BootTrail.vue`** — la frise elle-même. Elle ne s'affiche plus nulle
  part, et c'est voulu.
- **`webapp/base/app/utils/bootTrail.ts` et son fichier de tests** — le minutage et ses 7 cas.
- **Les trois libellés** dans `webapp/base/i18n/locales/{fr,en,ar}.yaml`.
- **Les autres espaces et le site public**, qui n'ont jamais été touchés.

## Décisions de cadrage

### Tout ce qui n'était pas le branchement est conservé — décision explicite

Trois choses n'existaient que pour brancher la frise : le minutage, ses tests, et les trois libellés
traduits. Elles auraient pu partir avec lui. Elles restent, et c'est un choix assumé :

- **le minutage est le contrat de la frise**, pas un détail d'appel — il dit qu'un trait met 600 ms
  et que le parcours entier en met 1 200. L'enlever laisserait le composant avec un chiffre écrit en
  dur dans ses classes et plus aucun test ;
- **les sept tests sont la seule chose qui protège ce chiffre**, et une durée qui dérive ne se voit
  pas à l'œil nu ;
- **les trois libellés sont traduits dans trois langues**, dont l'arabe. Les jeter pour les
  réécrire dans six mois, c'est refaire deux fois le seul travail de cette tâche qui demande une
  compétence qu'on n'a pas sous la main.

Le prix est nommé, pas caché : **le socle porte désormais un composant que rien n'affiche**, et trois
textes que rien ne montre. C'est de la dette, elle est petite, elle est tracée ici, et la carte aval
la solde dans un sens ou dans l'autre — rebrancher, ou retirer.

### On retire le branchement, on n'annule pas le commit

`ENT-068` n'est plus le dernier commit de la lane : une autre session a livré par-dessus. Réécrire
l'historique arracherait son travail. Et un `revert` automatique emporterait aussi la frise, les
tests et les libellés, qu'on garde. Le retrait est donc **écrit à la main**, limité au seul fichier
qui portait le branchement.

### La carte `ENT-068` est corrigée plutôt que laissée telle quelle

Elle est clôturée « fait » et décrit, preuves à l'appui, un écran de démarrage qui n'existera plus
après ce commit. On ne réécrit pas son histoire — ce qu'elle a mesuré était vrai le jour où elle l'a
mesuré — mais elle porte désormais, en tête, le renvoi vers cette carte-ci. Une carte qui ment est
pire qu'une carte absente : c'est elle qu'on relit dans six mois.

## Règles et cas limites

- **Le fichier revient à l'identique**, pas « à peu près » : le contenu restauré est celui du commit
  qui précède `ENT-068`, comparé octet à octet. Un retrait à la main qui laisse traîner un `import`
  ou une variable est un retrait raté.
- **Le rideau retrouve sa garantie d'origine** : sa condition de sortie ne dépend plus que de
  l'application prête, donc elle se résout dans tous les cas — succès, erreur, ou espace non amorcé.
- **Les trois autres espaces et le site public** ne changent pas, puisqu'ils n'avaient rien reçu.

## Documentation impactée

- **Aucune page.** `ENT-068` avait déjà vérifié qu'aucune documentation d'architecture ni de cadrage
  ne décrit le rideau de démarrage ; retirer ce qu'elle y avait ajouté n'en rend aucune fausse. Le
  README des composants ne change pas non plus : un composant sans emploi reste un composant, et
  aucune de ses règles ne parle d'usage.
- La **carte `ENT-068`** est amendée — c'est le seul document que ce commit rend faux.

## Risques et reports assumés

- **Du code sans emploi dans le socle** : la frise, son minutage, ses tests et trois libellés
  traduits. Assumé et tracé ci-dessus ; à solder par la carte aval.
- **Le dossier de travail est partagé** : une autre session écrit dans `webapp/services/search/` au
  moment où cette carte est écrite. Le commit devra désigner ses fichiers un par un.

## Vérification

Preuves observées le 2026-08-07 sur la pile de développement du worktree entreprise, navigateur
piloté, écran de démarrage de l'espace entreprise, thème clair, 1280 × 800. Valeurs **lues dans la
page servie**, au même endroit et avec le même outil que `ENT-068` — les deux séries se comparent
donc directement.

### L'écran de démarrage est revenu à ce qu'il était

| Ce qui est mesuré | Avec la frise (`ENT-068`) | Après retrait |
| --- | --- | --- |
| Jalons présents dans la page | 3 | **aucun** |
| Traits présents | 2 | **aucun** |
| Le logo pulse | non | **oui** |
| Le fondu de sortie démarre à | 2 982 ms | **1 031 ms** |
| Rideau retiré à | 3 425 ms | **1 519 ms** |

Le fondu part désormais **dès que l'application est prête**, et non plus une seconde et deux
dixièmes après : c'est exactement la seconde perdue à chaque démarrage que cette tâche récupère.

### Le fichier est revenu à l'identique

`git diff 9a0288f^ -- webapp/base/app/components/AppBootShell.vue` ne sort **aucune ligne** : le
contenu est celui d'avant `ENT-068`, comparé et non relu. Aucun `import` orphelin, aucune variable
laissée derrière.

### Ce qui est conservé est toujours là

`BootTrail.vue`, `bootTrail.ts` et `bootTrail.vitest.ts` sont en place, et les trois libellés sont
dans les trois fichiers de langue. `bunx vitest run base/app/utils/bootTrail.vitest.ts` → **7 tests
verts en 220 ms**.

## Livré

- `webapp/base/app/components/AppBootShell.vue` (M) — restauré à sa version d'avant `ENT-068` :
  la garde d'espace, les trois jalons, l'horloge, le plancher d'affichage et le retrait de la
  pulsation disparaissent ; la condition de sortie redevient « application prête », et rien d'autre.
- `dev-book/tasks/ENT-068-*.md` (M) — un avertissement en tête renvoie vers cette carte : ce qu'elle
  décrit n'est plus à l'écran, mais ce qu'elle a mesuré reste vrai.
- `dev-book/tasks/ENT-069-*.md` (+) — cette carte.

## Reste à faire (hors périmètre)

- **Décider du sort de la frise.** Elle vit dans le socle sans que rien ne l'affiche, avec son
  minutage, ses 7 tests et trois libellés traduits. Deux issues, toutes deux à carter : la
  rebrancher quelque part, ou la retirer. Laisser en l'état indéfiniment serait la troisième, et
  c'est celle qui ne se décide jamais.
- **Deux violations de conventions dans `ENT-067`** — `spaces/entreprise/app/pages/recherches/nouvelle.vue`
  porte un `<form>` et un `@submit` dans une page. Signalé lors de `ENT-068`, toujours ouvert :
  cela bloquera l'ouverture de la PR de la lane.

## Définition de terminé

- [x] Au démarrage de l'espace entreprise : plus de frise, le logo pulse à nouveau, le rideau part
      dès que l'application est prête — vérifié à l'écran.
- [x] `AppBootShell.vue` est **identique** à sa version d'avant `ENT-068` (comparaison, pas
      relecture).
- [x] `BootTrail.vue`, `bootTrail.ts`, `bootTrail.vitest.ts` et les trois libellés sont toujours là.
- [x] Les 7 tests de `bootTrail.vitest.ts` sont verts.
- [x] La carte `ENT-068` porte le renvoi vers celle-ci.
