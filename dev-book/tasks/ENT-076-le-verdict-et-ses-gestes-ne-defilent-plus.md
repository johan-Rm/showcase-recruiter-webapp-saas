# TASK ENT-076 — entreprise : le verdict et ses gestes ne défilent plus

- **Créée le** : 2026-08-10.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-10 — « Ce profil face à vos critères » et les deux gestes
  « retenir » / « écarter » quittent la fiche pour une **colonne de droite pleine hauteur, qui ne
  défile pas avec la pile**. Le fil d'avancement de la barre haute est supprimé.
- **Type** : déplacement de deux blocs d'interface, suppression d'un troisième, et **correction d'un
  défaut de visée** rendu bloquant par le déplacement — cf. *Décisions de cadrage*.
- **⚠️ Cadrée après coup, et remaniée en cours de route.** Les changements ont été demandés en
  conversation puis écrits directement ; cette carte est rédigée au commit, et récrite à
  l'amendement. Elle n'a donc **pas eu de cadrage**. Quatre arbitrages ont été posés à l'humain :
  la place de la colonne, le sort de la colonne de gauche, le repli sur petit écran, et la technique
  de mise en place.
- **Tests** : **aucun test écrit, sur décision explicite de l'humain le 2026-08-10.** Ce n'est pas
  une exemption de droit. Le dépôt n'outille pas le test de composant — ses 24 suites portent sur les
  services purs, les utilitaires et les vérificateurs de CI, **aucune sur un `.vue`** — et la
  correction de `useVisibleProfile` n'est pas davantage couvrable : elle **mesure une géométrie
  rendue**, ce qu'aucun environnement DOM simulé ne calcule. La preuve tenue est donc un **banc de
  défilement réel** (ci-dessous), plus les suites impactées jouées.
- **Réf** : `ENT-047`, `ENT-063` — la liste des critères et sa passivité · `ENT-049` — l'ordre du
  parcours, qui reste dans la gouttière · `ENT-071` — le rang et l'avancement dans la barre haute,
  dont cette carte retire la moitié.

## Contexte

Sur la page qui fait défiler les profils un par un, trois choses étaient mal placées :

- **le fil d'avancement** — un trait de deux pixels au bord bas de la barre haute ;
- **« Ce profil face à vos critères »** — dans la colonne de gauche, sous les trois compteurs ;
- **« Retenir » et « Écarter »** — dans le bandeau de chaque fiche, donc **mobiles** : sur un écran
  où l'on tranche cent profils à la suite, le geste le plus répété changeait de hauteur à chaque
  profil, puisque le bandeau descend avec la fiche.

## Périmètre

- **Un composant nouveau, `search/Verdict.vue`**, qui réunit ce qui juge et ce qui tranche : le nom
  du profil visé, les deux gestes, la liste des critères confrontés.
- **Rendu à deux endroits selon la largeur** : colonne de droite pleine hauteur à partir de 1280 px,
  bloc en pied de fiche en dessous.
- **La fiche perd ses deux boutons et sa troisième colonne** ; son bandeau se réduit au poste.
- **`useVisibleProfile` — correction de visée**, détaillée plus bas.
- **Le fil d'avancement** : le composant, la fente qui l'accueillait dans la barre haute, et sa clé
  de traduction dans les trois langues.

## Hors périmètre

- **Le sort de la colonne de gauche.** Réduite aux trois compteurs et à son bouton, elle laisse un
  grand vide. Question **posée et non tranchée**, sur décision explicite de l'humain.
- **Le découpage de `ProfileCard.vue`.** Le fichier redescend de 496 à 449 lignes en perdant ses
  gestes et sa troisième colonne, mais la convention en autorise 200 et il porte encore quatre blocs
  indépendants. **Nommé ici, non traité.**
- **L'écart d'hydratation du compteur « Favoris »** — défaut préexistant, décrit plus bas.
- **Les deux boutons de filtres** de la barre haute, toujours désactivés faute d'être branchés.

## Décisions de cadrage

### Sortir les gestes de la fiche crée un problème que la fiche résolvait toute seule

C'est la seule difficulté sérieuse de cette carte, et elle n'est pas visuelle.

Tant que « Retenir » vit **dans** une fiche, il ne peut pas se tromper de profil : il appartient à
celle qui le porte. Posé dans une colonne fixe, il n'appartient plus à personne — il agit sur « le
profil du moment », et quelque chose doit décider en permanence lequel c'est.

Or ce quelque chose se trompait. `useVisibleProfile` ne changeait de profil courant **qu'une fois le
haut de la fiche suivante arrivé en haut de la zone**, c'est-à-dire à la toute fin du défilement.
Pendant toute la transition, la fiche suivante occupait déjà les trois quarts de l'écran alors que
le courant désignait encore la précédente. Sans conséquence tant que ça ne faisait qu'**afficher** un
mauvais verdict ; avec les deux gestes dedans, c'est **écarter le profil qu'on vient de quitter**,
qui disparaît de la pile derrière soi sans explication.

Deux corrections, donc, et elles étaient nécessaires **avant** le déplacement :

1. **Le seuil passe du bord à la médiane.** Est courante la fiche qui couvre le milieu de la zone.
   Elle bascule à mi-parcours, et la règle ne suppose rien de la hauteur des fiches.
2. **La mesure passe de `offsetTop` au rectangle affiché.** `offsetTop` se compte depuis l'ancêtre
   *positionné* — qui se trouve être la pile elle-même, `relative` pour une raison sans rapport. Lui
   soustraire en plus la position de la pile dans la page décalait le seuil de la hauteur de
   l'en-tête : **la bascule arrivait 211 px trop tôt**. Défaut préexistant, invisible tant qu'il ne
   faisait qu'avancer un affichage.

### La colonne nomme le profil qu'elle juge

Contrepartie directe : deux boutons de verdict à côté d'une pile qui défile n'ont **aucun sujet
visible**. Sans le nom en tête de colonne, « Retenir » ne dit pas qui l'on retient.

En pied de fiche, la question ne se pose pas — le sujet est le bloc qui entoure les boutons — et l'y
nommer écrirait le même nom deux fois dans la même carte. D'où le drapeau `detached` : le verdict ne
se présente que lorsqu'il est détaché.

### Une colonne sœur, et non une position absolue

Demande initiale : poser le panneau **en absolu** dans la zone principale. Le résultat voulu —
pleine hauteur, immobile — s'obtient à l'identique en faisant du panneau une **troisième colonne de
la grille**, et sans sa contrepartie : en absolu, la pile doit réserver la largeur du panneau à la
main, des deux côtés, sous peine de faire passer les fiches dessous. Arbitré avec l'humain.

### Trois colonnes de page à 1280 px, pas à 1024

Écart assumé par rapport à la consigne « sous 1024 px ». À 1024, trois colonnes de page laisseraient
**environ quatre cents pixels au profil lui-même**, entre une console de 280 px et un verdict de
300 px. Le repli en pied de fiche prend donc la main dès 1280.

### Le fil d'avancement — conséquence assumée

Sous 1024 px, la colonne de gauche est masquée. L'avancement vivait à deux endroits voisins dans la
barre haute, le fil et le rang « 8 / 234 ». Il n'en reste qu'un : **le rang**. On sait toujours *où
l'on est*, plus *quelle part on a vue*. C'est le prix de la décision, il est nommé.

## Règles et cas limites

- **Aucun profil** : le verdict détaché reçoit `null` et ne rend rien, plutôt qu'une colonne vide
  avec deux boutons sans sujet.
- **Aucun critère** : la pile affiche son état « aucun critère » et aucune fiche n'existe. Le
  booléen `hasCriteria` est remplacé par la liste elle-même, dont l'absence *est* l'état.
- **Recherche d'un collègue** (`editable` faux) : ni nom ni boutons, la liste des critères seule.
  On la lit, on n'y tranche rien.
- **Défilement par accrochage** : `snap-mandatory` interdit toute position intermédiaire *durable*,
  mais un geste de molette en traverse — c'est exactement l'instant que la correction de seuil
  couvre.
- **Coût du calcul** : une lecture de rectangle par fiche à chaque événement de défilement, limité à
  un toutes les 120 ms, et la boucle s'arrête à la première fiche sous la médiane.

## Documentation impactée

- **`2.architecture/arborescence-entreprise.md`** : la phrase « ses critères se lisent dans la
  console » et celle qui prêtait à la console « ce que vaut le profil courant » ; ajout des trois
  zones et d'une ligne au tableau des éléments qui ne sont pas des pages.
- **`search.state.emptyHint`, dans les trois langues** : le texte renvoyait à « la colonne de
  gauche » pour retirer un critère, qui n'en porte plus.
- **Les commentaires de tête** de `Console.vue`, `ControlPanel.vue`, `MatchList.vue`, `Stack.vue`,
  `ProfileCard.vue`, `ActionBar.vue` et `useVisibleProfile.ts`.
- **`dev-book/cadrage/entreprise/ui/`** — neuf pages y décrivent la console et la visionneuse.
  **Hors périmètre d'un commit** : écrit pour les clients, ce dossier suit son propre rythme.
  Signalé pour que la dérive soit connue.

## Définition de terminé

- [x] Le verdict et ses deux gestes occupent une colonne pleine hauteur qui ne défile pas.
- [x] Ils désignent la fiche que l'on regarde, à tout moment du défilement.
- [x] Sous 1280 px, ils retombent en pied de fiche sans doublon de nom.
- [x] Plus aucun élément de rôle `progressbar` sur la page.
- [x] La fiche tient dans un plein écran.

## Vérification

### Le banc de défilement — la visée des deux gestes

L'accrochage est neutralisé le temps de la mesure : il interdit toute position intermédiaire
durable, alors que le geste réel en traverse. On compare ce que le panneau affiche à la fiche qui
couvre réellement le milieu de l'écran, mesurée indépendamment du composable.

```
défilement  30 %  | fiche regardée: Ahmed Chekori  | panneau vise: Ahmed Chekori  → OK
défilement  45 %  | fiche regardée: Ahmed Chekori  | panneau vise: Ahmed Chekori  → OK
défilement  55 %  | fiche regardée: Zahira Hedri   | panneau vise: Zahira Hedri   → OK
défilement  70 %  | fiche regardée: Zahira Hedri   | panneau vise: Zahira Hedri   → OK
défilement 100 %  | fiche regardée: Zahira Hedri   | panneau vise: Zahira Hedri   → OK
```

Avant correction, le même banc donnait `✗ DÉSACCORD` à 30 % et 45 % : le panneau désignait déjà le
profil suivant alors que le précédent occupait encore tout l'écran.

### La disposition

Rendu réel de `/recherches/019fd61b-…` (Product Owner · scrum · jira, 19 profils, 6 critères) en
session `atlas.owner`, thème sombre.

```
verdictEnColonne     : true        verdictEnPiedDeFiche : true
colonneVisible       : "block"     piedVisible          : "none"     (à 1920 px)
sujetDeLaColonne     : "Ahmed Chekori"
gestesDeLaColonne    : ["Retenu", "Écarter ce profil"]
hauteurColonne       : 859 px  =  toute la hauteur de la zone de contenu
```

Trois largeurs capturées : **1920** (trois zones, la pile seule défile), **1150** (verdict en pied
de fiche, sans doublon de nom), **800** (les blocs empilés, verdict en dernier).

### Les suites impactées

```
$ bunx vitest run base/i18n/locales/parity.vitest.ts services/search/criteria.vitest.ts
 Test Files  2 passed (2)
      Tests  66 passed (66)
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/Verdict.vue` | (+) le verdict et ses deux gestes |
| `spaces/entreprise/app/components/search/ProfileCard.vue` | (M) perd ses boutons et sa 3ᵉ colonne, appelle le verdict en pied |
| `spaces/entreprise/app/components/search/Stack.vue` | (M) `hasCriteria` → `criteria`, relais à la fiche |
| `spaces/entreprise/app/components/search/MatchList.vue` | (M) `h2` → `h3`, commentaires réalignés |
| `spaces/entreprise/app/components/search/Console.vue` | (M) perd `outcomes` |
| `spaces/entreprise/app/components/search/ControlPanel.vue` | (M) perd `outcomes` et la liste |
| `spaces/entreprise/app/components/search/Progress.vue` | (D) le fil d'avancement |
| `spaces/entreprise/app/components/ActionBar.vue` | (M) perd la fente `progress` |
| `spaces/entreprise/app/composables/useVisibleProfile.ts` | (M) seuil à la médiane, mesure au rectangle |
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) troisième zone, profil courant, ne mesure plus |
| `base/i18n/locales/{fr,en,ar}.yaml` | (M) `progress` retirée, `verdict.region` ajoutée, `emptyHint` corrigé |
| `docs/…/2.architecture/arborescence-entreprise.md` | (M) les trois zones, et le verdict au tableau |

## Reste à faire (hors périmètre)

- **Le sort de la colonne de gauche**, à décider sur pièce.
- **Un défaut trouvé en passant, préexistant et non corrigé** : le compteur « Favoris » de la
  colonne de gauche affiche **0 alors que des profils sont retenus**, et Vue signale un écart
  d'hydratation dessus (`rendered on server: 9 — expected on client: 0`). Cause lue dans le code :
  `retainedIds` est une `ref` locale de `useSearch`, hors de tout store ; le rendu serveur la
  remplit, mais **le payload SSR ne transporte que les stores**, donc le client repart de zéro.
  Appartient au sujet des favoris, pas à celui-ci.
- **Le découpage de `ProfileCard.vue`** — quatre blocs indépendants dans 449 lignes.
- **La fusion de `Console.vue` dans `ControlPanel.vue`** : la première ne fait plus que poser un
  fond et recopier six paramètres à la seconde. Vestige des onglets supprimés.
