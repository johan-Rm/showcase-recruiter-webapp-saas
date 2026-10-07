# TASK WEB-014 — socle : les noms que Nuxt UI revendique ne passent plus en silence

- **Créée le** : 2026-08-06.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-06 — écrire `bg-muted` ou `border-muted` bloque désormais la
  fusion, avec l'écriture de remplacement dans le message. 16 tests neufs.
- **Type** : Outillage frontend — une règle de convention vérifiée à la fusion, aucun code applicatif
  modifié.
- **Tests** : unitaires (vitest) sur le vérificateur — il attrape l'écriture fautive, il laisse
  passer l'écriture explicite, et il ne se déclenche pas sur une chaîne qui contient le motif sans
  être une classe. Le dépôt teste déjà ses vérificateurs (`scripts/ci/checkers.vitest.ts`, 51 cas).
- **Réf** : **`ENT-056` — amont** (le défaut qui a révélé le piège, et qui corrige l'occurrence) ·
  `webapp/scripts/ci/rules.yaml` · `webapp/scripts/ci/verify-conventions.ts` ·
  `webapp/scripts/ci/checkers.vitest.ts` · `webapp/base/app/assets/css/nuxt-ui.css` (le remappage qui
  crée la collision) · `webapp/base/app/assets/css/themes.css` (`--muted`, `--surface-soft`)

## Contexte

Le kit maison et Nuxt UI **emploient les mêmes mots pour des choses différentes**, et rien ne le
signale au moment d'écrire.

Le nuancier définit `--muted` : un gris de **texte**, `#a5a5a5` en thème sombre. Nuxt UI définit de
son côté un `bg-muted` qui lit `--ui-bg-muted`, une couleur de **fond** — que notre propre fichier de
raccordement branche sur `--surface-soft`. Écrire `class="bg-muted"` en pensant au gris du nuancier
donne donc une surface, pas un texte.

Le défaut est passé **inaperçu pendant toute la vie d'`ENT-050`**, et il a fallu mesurer la page pour
le voir : la barre d'avancement de la carte de recherche était peinte exactement dans la couleur de
son propre fond, au chiffre près, dans les deux thèmes. Elle se lisait donc **à l'envers** — ce qu'on
voyait était la piste non remplie.

Ce qui rend le piège coûteux n'est pas sa fréquence, c'est son **silence** : le nom demandé existe,
il répond, il répond simplement autre chose. Ni la relecture, ni le type-check, ni le lint ne peuvent
le signaler — seule une mesure de la page rendue le peut.

## Périmètre

- **Une règle de convention** qui refuse les classes dont le nom est revendiqué par Nuxt UI alors que
  l'auteur vise un token du nuancier, et qui **dit quoi écrire à la place**.
- Sa couverture par des tests unitaires, comme les treize règles existantes.
- La documentation qui porte la convention.

## Hors périmètre

- **La correction de la barre d'avancement** : elle appartient à `ENT-056`, sur la lane entreprise.
  Cette tâche pose la règle, elle ne corrige pas l'occurrence — et les deux ne partagent pas un
  commit.
- **Le style des menus déroulants** : c'est `WEB-013`, qui touche le même fichier mais pour une autre
  raison.
- **Une refonte du remappage `nuxt-ui.css`** : la collision est inhérente à l'emploi de Nuxt UI, elle
  ne se supprime pas. On la rend visible.
- **Les autres familles de collisions** que l'inventaire ferait apparaître au-delà des couleurs :
  cf. *Décisions de cadrage*.

## Décisions de cadrage

**Une règle vérifiée plutôt qu'un commentaire, parce que le défaut est silencieux.** Un commentaire
protège l'endroit où il est écrit ; il ne protège pas le prochain fichier. Or c'est précisément la
propriété de ce piège : il ne produit ni erreur, ni avertissement, ni test rouge — il produit un
écran juste-en-apparence, que seule une mesure démasque. C'est le cas d'école d'une règle
automatisable, et le dépôt en applique déjà treize par le même mécanisme.

**La règle nomme le remplacement, elle ne se contente pas d'interdire.** Un refus sans issue se
contourne en désactivant la règle. Le message doit donner l'écriture explicite qui vise le token du
nuancier sans passer par un nom intercepté.

**L'inventaire des noms à couvrir se fait en lisant le remappage, pas de mémoire.** `nuxt-ui.css`
déclare la liste des tokens sémantiques de Nuxt UI branchés sur les nôtres ; c'est là que se lisent
les collisions réelles. En établir la liste à l'implémentation, et ne pas la deviner ici — le
cadrage n'a mesuré qu'un cas, `bg-muted`, et supposer les autres serait exactement l'erreur que
cette tâche combat.

**Portée aux couleurs, pas à tout le vocabulaire.** Les collisions constatées portent sur les noms de
couleur, où le même mot désigne un texte chez nous et un fond chez Nuxt UI. Élargir la règle à
d'autres familles — espacements, rayons — demanderait de les avoir constatées ; aucune ne l'a été.
La règle s'élargira quand un deuxième cas la justifiera.

## Règles et cas limites

- **La règle ne se déclenche pas sur une chaîne de texte** qui contiendrait le motif sans être une
  classe — un libellé, un commentaire, une clé i18n.
- **Elle ne se déclenche pas dans `nuxt-ui.css` lui-même**, où les noms de Nuxt UI sont légitimes :
  c'est le fichier dont le rôle est précisément de les brancher.
- **Elle couvre les quatre espaces et le socle**, pas seulement `spaces/entreprise/`.
- **Le message nomme le fichier et la ligne**, comme les règles existantes : une règle qui dit
  seulement « quelque part » ne se corrige pas.
- **Aucune occurrence ne doit subsister** au moment où la règle entre : sinon la fusion est bloquée
  pour tout le monde. Il n'y en a qu'une aujourd'hui, dans la carte de recherche, et `ENT-056` la
  traite — **cette tâche ne peut donc pas être fusionnée avant elle**.

### Écart : la liste se réduit à DEUX noms, pas à une famille

L'inventaire demandé a été fait en lisant le remappage, puis **mesuré sur la feuille compilée**. Les
deux catalogues ne se croisent que sur **un seul mot**, `muted`, sous trois formes — et Nuxt UI
gagne les trois :

```
.bg-muted      → var(--ui-bg-muted)      = --surface-soft   ❌ on visait --muted
.border-muted  → var(--ui-border-muted)  = --line           ❌ on visait --muted
.text-muted    → var(--ui-text-muted)    = --muted          ✓ même valeur
```

**`text-muted` est donc épargné**, et c'est un choix : Nuxt UI le gagne aussi, mais notre remappage
le branche sur la bonne variable. L'interdire condamnerait une écriture correcte employée partout
dans le produit. C'est une coïncidence heureuse et non une garantie — d'où l'avertissement écrit
dans `nuxt-ui.css`, à l'endroit exact où quelqu'un pourrait rompre ce remappage sans le savoir.

### Écart : la convention s'écrit dans le système de design, pas dans un README de dossier

La règle devait pointer vers `nuxt-ui.css`. Aucune autre ne référence un fichier de code : elles
pointent toutes vers un `README.md` de dossier, ou vers le système de design — c'est déjà le cas de
`layouts-no-raw-spacing`, qui vise son interdit n°3. La convention rejoint donc les cinq autres
interdits d'écriture, comme **interdit n°6**, et le fichier de raccordement porte un avertissement
plutôt qu'une doctrine.

### Écart : je n'ai pas pu établir d'écriture courte de remplacement

Le message recommande de poser la couleur par `text-muted` puis de peindre avec `bg-current` — la
forme **mesurée vivante** dans `ENT-056`, où le remplissage de la barre rend `rgb(165,165,165)`,
exactement `--muted`.

J'ai cherché plus direct — `bg-[var(--muted)]`, `bg-(color:--muted)` — sans pouvoir conclure : la
feuille de style servie par la pile interrogée s'est révélée **incomplète** (les classes de la carte
de recherche n'y figuraient pas), ce qui invalide ses « non généré ». Plutôt que recommander une
écriture non vérifiée — le piège même que cette règle combat, et celui dans lequel `WEB-013` était
tombée — le message ne propose que ce qui a été observé.

## Documentation impactée

- **[design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md)** — la convention
  d'écriture des couleurs : quel nom vise le nuancier, lesquels appartiennent à Nuxt UI.
- **[testing-and-ci.md](../../docs/showcase-recruiter-webapp-saas/content/3.quality/testing-and-ci.md)** — s'il énumère les
  règles vérifiées à la fusion, la nouvelle s'y ajoute. À vérifier plutôt qu'à supposer.
- Le `README.md` du dossier des vérificateurs, s'il décrit les règles une à une.
- Le commentaire d'en-tête de `webapp/base/app/assets/css/nuxt-ui.css`, qui explique le remappage :
  il doit dire que ces noms sont **interceptés**, et pas seulement branchés.

## Définition de terminé

- [x] Écrire une classe dont le nom est revendiqué par Nuxt UI, en visant un token du nuancier,
      bloque la fusion.
- [x] Le message d'erreur nomme le fichier, la ligne, et **l'écriture à employer à la place**.
- [x] L'écriture explicite passe sans avertissement.
- [x] La règle ne se déclenche ni sur du texte, ni dans le fichier de raccordement du kit.
- [x] La liste des noms couverts a été établie **en lisant** `nuxt-ui.css`, et le cadrage dit
      lesquels ont été retenus.
- [x] La règle est couverte par des tests unitaires, cas passant et cas bloquant.
- [x] La convention est écrite dans la documentation de design.
- [x] Le dépôt est vert avec la règle activée — donc `ENT-056` est fusionnée avant.

## Vérification

**La règle mord, prouvée par une violation volontaire** — l'exigence du README de `webapp/` :

```
✖ app-no-ambiguous-kit-class — 3 violation(s)
    SondeFaute.vue:3 → <div class="… bg-muted …">        — ce nom appartient à Nuxt UI
    SondeFaute.vue:4 → <span class="… hover:bg-muted …"> — ce nom appartient à Nuxt UI
    SondeFaute.vue:5 → <em class="… border-muted …">     — ce nom appartient à Nuxt UI
```

Le même fichier d'appât portait **un commentaire citant les deux noms fautifs**, un `text-muted`
seul et un `bg-current text-muted` : **aucun des trois n'a été condamné**. C'est ce qui a imposé de
lire l'arbre du gabarit plutôt que les lignes — le commentaire qui explique ce piège cite forcément
la classe qu'il dénonce.

**Sonde retirée, le dépôt repasse au vert** : `✔ conventions — 14 règles, aucune violation`. La
règle peut donc entrer sans bloquer la fusion pour quiconque, ce que `ENT-056` a rendu possible en
retirant la seule occurrence existante.

**`checkers.vitest.ts` : 16 cas neufs**, et **67 tests** verts sur le fichier — les deux noms
refusés, leurs formes préfixées (`hover:`, `sm:`, `dark:`, `group-hover:`), le décompte par classe
et non par élément, les quatre espaces couverts ; et du côté de ce qu'elle épargne : `text-muted`,
l'écriture de remplacement, les noms qui *ressemblent* sans être visés, le commentaire, et la
liaison `:class`.

## Livré

- `webapp/scripts/ci/checkers.ts` **(M)** — `app-no-ambiguous-kit-class` : un checker structurel, et
  le commentaire qui dit pourquoi `text-muted` n'y est pas.
- `webapp/scripts/ci/checkers.vitest.ts` **(M)** — 16 cas.
- `webapp/scripts/ci/rules.yaml` **(M)** — la règle, son périmètre et son message.
- `webapp/base/app/assets/css/nuxt-ui.css` **(M)** — l'avertissement à l'endroit exact où quelqu'un
  pourrait rompre le remappage qui rend `text-muted` juste.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` **(M)** — l'interdit n°6, et le détour à
  employer pour peindre avec le gris du nuancier.

## Reste à faire (hors périmètre)

- **La règle ne lit pas les liaisons `:class`** — une expression peut porter n'importe quoi. Limite
  assumée et couverte par un test qui la nomme : elle attrape l'écriture courante, pas toutes les
  écritures.
- **Aucune écriture courte n'a pu être établie** pour viser un token du kit quand son nom est
  intercepté. Le détour recommandé fonctionne ; un raccourci reste à trouver, sur une pile dont la
  feuille de style soit un oracle complet.
- **Les autres familles de collisions** — espacements, rayons — restent non inventoriées : aucune
  n'a été constatée, et la règle s'élargira quand un deuxième cas la justifiera.
