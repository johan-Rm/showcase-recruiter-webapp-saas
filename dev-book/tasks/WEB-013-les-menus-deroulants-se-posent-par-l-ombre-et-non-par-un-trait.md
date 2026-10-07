# TASK WEB-013 — socle : les menus déroulants se posent par l'ombre, pas par un trait

- **Créée le** : 2026-08-06.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-06 — les menus déroulants se détachent par leur surface et leur
  ombre, sans anneau. Un token d'élévation entre dans le kit.
- **Type** : Frontend socle — un style de composant du kit, aucun contrat ni comportement touché.
- **Tests** : sans objet — aucun code exécutable n'est produit. La tâche pose des règles CSS dans le
  remappage du kit. Le résultat se prouve par **mesure** — feuille de style compilée, contraste
  menu/page, présence de l'anneau — et par capture dans les deux thèmes.
- **Réf** : **`ENT-055` — amont** (le menu de la carte de recherche, qui a rendu le défaut visible) ·
  `webapp/base/app/assets/css/nuxt-ui.css` (le remappage du kit sur Nuxt UI) ·
  `webapp/base/app/assets/css/themes.css` (l'élévation, déjà raisonnée mais jamais outillée) ·
  [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) ·
  [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md)

## Contexte

Les menus déroulants du produit sortent avec la **bordure par défaut de Nuxt UI**, alors que le guide
de marque pose l'inverse : *la séparation se fait par le fond, jamais par une bordure*. Le fichier de
remappage du kit l'écrit déjà noir sur blanc pour les champs de formulaire — « c'est le trait MAISON
qui l'emporte » —, mais l'a fait avec une **portée volontairement étroite**, en refusant de restyler
des écrans que personne n'avait relus.

Un menu flottant est justement le cas où la bordure ne sert à rien : il ne s'agit pas de délimiter un
bloc dans la page, mais de dire qu'un panneau **passe au-dessus** d'elle. C'est le rôle de l'ombre, et
`themes.css` le raisonne déjà — « sur une page sombre, l'élévation se lit par l'ombre » — sans qu'il
existe **aucun token d'ombre** dans le kit pour l'appliquer.

## Périmètre

- **Les menus déroulants perdent leur bordure** et gagnent une **ombre légère**, dans les quatre
  espaces.
- **Un token d'ombre entre dans le kit**, plutôt qu'une valeur écrite à un seul endroit.
- Les deux thèmes, `porcelain` et `shark`, où l'élévation ne se lit pas de la même façon.
- La documentation de design que ça rend fausse.

## Hors périmètre

- **Les trois retouches de la carte de recherche** — colonnes vus/retenus, « pas de nouveaux », badge
  « pas de mots clés » : c'est **`ENT-056`**, sur la lane entreprise.
- **Les autres panneaux flottants** — infobulles, fenêtres modales, panneaux latéraux. Ils
  bénéficieront du token, mais leur restyle se décide en les regardant, cf. *Décisions de cadrage*.
- **Les champs de formulaire**, déjà traités et à portée étroite assumée.
- **Le rayon, l'espacement interne et la typographie des menus** : inchangés.

## Décisions de cadrage

**Le style entre dans le thème, pas dans la carte de recherche.** Le poser localement aurait fait
coexister deux styles de menu dans le même produit — celui de la carte, et tous les autres. Un kit
n'a d'intérêt que si l'on peut lire son style sans savoir d'où vient le composant.

**Un token d'ombre plutôt qu'une valeur en dur, et c'est le vrai choix de cette tâche.** Le kit n'en
a aucun aujourd'hui : la première ombre écrite quelque part deviendrait la référence par imitation,
recopiée de fichier en fichier avec des valeurs qui divergent. Un token nommé la rend arbitrable une
fois — et surtout **redéclarable en thème sombre**, où la même ombre ne produit pas le même effet.
C'est exactement le raisonnement que `themes.css` tient déjà pour les surfaces, appliqué à
l'élévation qu'il nomme sans l'outiller.

**Un seul niveau d'élévation, pas une échelle.** Le produit n'a qu'une famille de panneaux flottants
à traiter aujourd'hui. Inventer trois niveaux pour un seul usage, c'est de l'abstraction prématurée :
le deuxième niveau s'ajoutera quand un deuxième cas le demandera, et il aura alors un cas réel pour
l'arbitrer.

**Les autres panneaux flottants ne sont pas embarqués.** Le token les rendra faciles à aligner, mais
les aligner **sans les regarder** rejouerait exactement ce que le remappage des champs avait refusé :
restyler des écrans que personne n'a relus. Chacun viendra avec sa capture.

**L'ombre doit rester visible sur fond sombre.** C'est la difficulté réelle et non un détail : une
ombre noire sur `shark` ne se voit pas. La valeur du thème sombre se mesure sur une capture, elle ne
se déduit pas de celle du thème clair.

### Écart : ce n'était pas une bordure, mais un anneau

`border` vaut **0** sur le menu — il n'y a aucune bordure. Ce qui le cerne est un **anneau glissé
dans la liste des ombres** (`0 0 0 1px`). Le périmètre écrit « les menus perdent leur bordure » :
appliqué à la lettre, il n'aurait rien produit. L'anneau se retire en réécrivant `box-shadow`.

### Écart : l'anneau était la SEULE séparation, il ne pouvait pas partir seul

Mesuré avant toute modification : le fond du menu est **identique à celui de la page**, au chiffre
près et dans les deux thèmes — `rgb(236,240,239)` en clair, `rgb(26,26,26)` en sombre, soit un
contraste de **1,00:1**. Retirer l'anneau sans rien d'autre aurait dissous le panneau dans la page.

La règle fait donc deux choses, et l'ordre compte : elle branche d'abord le menu sur `--dropdown-bg`
— le token existait déjà, avec son commentaire « un menu se lit comme un panneau posé : il suit
`--surface`, il ne reprend pas le fond de page » —, et seulement ensuite elle remplace l'anneau par
l'ombre. Le cadrage n'avait vu que la moitié du geste.

### Écart : le menu du compte n'est pas concerné

La définition de terminé demandait de le vérifier aussi. Il n'est **pas** un menu Nuxt UI mais un
panneau maison, déjà sans bordure et avec son ombre. Le **seul** menu Nuxt UI du produit entier est
celui de la carte de recherche (`UDropdownMenu`, posé par `ENT-055`) — vérifié sur tout le webapp.

Corollaire : cette tâche n'invente pas un style, elle en **aligne** un. Tous les panneaux flottants
maison respectaient déjà la règle du guide de marque ; les menus Nuxt UI faisaient exception.

### Écart : l'exposition Tailwind a été tentée, mesurée morte, puis réécrite

Le token a d'abord été exposé dans le bloc `@theme inline`, par imitation des couleurs juste
au-dessus. **La classe n'était pas générée du tout** — prouvé par une sonde où la classe témoin
`bg-surface-soft`, dans le *même* fichier, l'était. `inline` demande à Tailwind de résoudre la valeur
à la compilation, et il ne sait pas résoudre une variable déclarée dans un autre fichier : il
abandonne l'utilitaire, sans rien dire.

Livrée telle quelle, cette exposition aurait posé exactement le piège que `WEB-014` doit empêcher.
La bonne écriture est un bloc `@theme` **sans** `inline` : la référence survit dans `--tw-shadow`,
donc l'utilitaire existe **et** suit la bascule sombre. Les deux formes ont été mesurées avant de
trancher.

### Écart : changer le fond du panneau a effacé le repère clavier, en sombre

La carte prévenait — « c'est le genre de régression qu'une capture de menu fermé ne montre pas » —
et elle avait raison. Nuxt UI marque l'entrée survolée ou atteinte au clavier avec un calque peint
en `--surface`. Or `--surface` et `--dropdown-bg` valent **la même couleur en thème sombre**,
`shark-800` : en donnant `--dropdown-bg` au panneau, le calque devenait invisible sur son propre
fond. Le repère tenait **avant** ce changement, où le panneau portait le fond de page.

En thème clair rien ne cassait, les deux couleurs y différant — une capture claire n'aurait rien
montré.

Le panneau monte donc lui-même d'un cran l'échelle d'élévation, pour ses entrées seulement :
`--surface-soft` au lieu de `--surface`. Vérifié dans les deux thèmes, calque de l'entrée atteinte
contre calque de sa voisine.

### Écart : le guide de marque n'avait rien à apprendre

La carte demandait de le compléter « si elle ne le dit pas déjà ». Elle le dit déjà, mot pour mot :
*« les surcouches — menus, listes déroulantes, tiroirs, modales — portent une ombre franche. C'est
elle qui les détache du plan de la page »*. Le guide reste donc inchangé — le défaut n'était pas
dans la règle, il était dans son application.

## Règles et cas limites

- **Aucune bordure ne subsiste**, y compris celle que Nuxt UI pose par défaut sur le panneau et celle
  d'un éventuel sous-menu.
- **Le panneau doit rester distinct du fond dans les deux thèmes** : sans bordure, c'est l'ombre et
  la surface qui l'en séparent. Si l'un des deux thèmes ne tient pas, la tâche n'est pas finie —
  ne pas remettre une bordure sur ce seul thème.
- **Le contraste du texte du menu ne change pas** : cette tâche ne touche ni le fond du panneau ni sa
  couleur de texte.
- **Le focus reste visible.** L'anneau de focus des entrées ne doit pas disparaître avec la bordure du
  panneau — ce sont deux choses distinctes, et c'est le genre de régression qu'une capture de menu
  fermé ne montre pas.
- **Les menus des quatre espaces** sont concernés : le vérifier ailleurs que sur la carte de
  recherche, au moins sur le menu du compte.
- **Aucune règle ne se pose sur un sélecteur d'espace** : le kit est commun, une exception par espace
  serait le contraire de cette tâche.

## Documentation impactée

- **[design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md)** — le token d'ombre y
  entre : il devient un élément du vocabulaire du kit, au même titre que le trait et les surfaces.
- **[brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md)** — relu,
  **inchangé** : il portait déjà la règle, cf. l'écart correspondant.
- Le commentaire d'en-tête de `webapp/base/app/assets/css/nuxt-ui.css` annonce une portée étroite
  pour les CHAMPS ; il reste juste. La règle des menus est une section à part, qui porte son propre
  périmètre — `[role='menu']` seul, et pourquoi.

## Définition de terminé

- [x] Les menus déroulants n'ont plus aucune bordure.
- [x] Ils portent une ombre légère, qui les détache du fond.
- [x] L'ombre est lisible dans les deux thèmes, prouvée en capture pour chacun.
- [x] La valeur vient d'un token du kit, redéclaré en thème sombre, et non d'une valeur écrite dans
      un composant.
- [x] **Réécrit** : le menu du compte est un panneau maison, pas un menu Nuxt UI — il n'était pas
      concerné, cf. l'écart. Ce qui est vérifié à la place : le **seul** menu Nuxt UI du produit a
      été recensé sur tout le webapp, et c'est celui de la carte de recherche.
- [x] Le repère de l'entrée atteinte au clavier reste visible — il ne l'était **plus** en thème
      sombre après le changement de fond, cf. l'écart, et une correction le rétablit.
- [x] La documentation de design nomme le token.

## Vérification

**Le menu, avant et après**, mesuré sur l'application dans les deux thèmes :

```
──── CLAIR ────
  AVANT  menu rgb(236,240,239) · page rgb(236,240,239) · contraste 1.00:1 · anneau OUI
  APRÈS  menu rgb(248,251,250) · page rgb(236,240,239) · contraste 1.10:1 · anneau non

──── SOMBRE ────
  AVANT  menu rgb(26,26,26)    · page rgb(26,26,26)    · contraste 1.00:1 · anneau OUI
  APRÈS  menu rgb(33,33,33)    · page rgb(26,26,26)    · contraste 1.08:1 · anneau non
```

**La règle traverse bien la compilation** — lue dans la feuille de style servie par la stack montée
sur ce dossier de travail, et non seulement dans le fichier source :

```css
[data-slot='content'][role='menu'] {
  background: var(--dropdown-bg);
  box-shadow: var(--overlay-shadow);
}
```

```
  clair  : 0 12px 28px -8px rgb(0 0 0 / 0.18), 0 2px 6px -2px rgb(0 0 0 / 0.12)
  sombre : 0 16px 32px -12px rgb(0 0 0 / 0.65), 0 2px 8px -4px rgb(0 0 0 / 0.5)
```

**L'utilitaire est généré**, et il suit le thème :

```css
.shadow-overlay {
  --tw-shadow: var(--overlay-shadow);
  box-shadow: var(--tw-inset-shadow), …, var(--tw-shadow);
}
```

**Le repère de l'entrée atteinte au clavier**, mesuré sur le calque et comparé à celui de sa
voisine — avant correction, en sombre, le calque valait exactement le fond du panneau :

```
        entrée atteinte                    sa voisine        panneau
clair   oklab(0.875 …/0.5)                 transparent       rgb(248,251,250)
sombre  oklab(0.383 …/0.5)                 transparent       rgb(33,33,33)
```

**Le rendu se juge sur deux captures**, une par thème : le menu se lit comme un panneau posé, sans
trait, dans les deux cas, et l'entrée atteinte porte sa bande.

## Livré

- `webapp/base/app/assets/css/themes.css` **(M)** — `--overlay-shadow`, déclaré dans les deux
  thèmes. La valeur sombre est plus dense et plus courte : c'est la surface qui sépare, l'ombre
  n'ancre plus que le bord.
- `webapp/base/app/assets/css/main.css` **(M)** — l'utilitaire `shadow-overlay`, dans un bloc
  `@theme` sans `inline`, et le commentaire qui dit pourquoi ce n'est pas une inattention.
- `webapp/base/app/assets/css/nuxt-ui.css` **(M)** — la règle des menus, et les commentaires qui
  portent les trois découvertes : l'anneau qui n'est pas une bordure, le fond emprunté à la page, et
  le repère clavier que le nouveau fond effaçait en sombre. Le panneau y remonte `--ui-bg-elevated`
  d'un cran pour ses entrées.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` **(M)** — la famille *Élévation* dans le tableau
  des tokens, son unique niveau, et l'écriture à suivre pour un futur token qui référence une
  variable.

## Reste à faire (hors périmètre)

- **Les panneaux flottants maison portent des ombres Tailwind écrites en dur** — `shadow-2xl` sur le
  menu du compte et les notifications, `shadow-lg` sur le sélecteur d'étiquettes. Le token permet de
  les unifier, mais leurs fichiers vivent dans les lanes **entreprise** et **admin** : nommé, pas
  touché.
- **Les autres surcouches Nuxt UI** — listes de sélection, fenêtres — partagent le même `data-slot`
  et gardent leur anneau. Leur cas se juge en les regardant, comme prévu au cadrage.
- **`WEB-014`** — le garde-fou contre les noms interceptés par Nuxt UI — reste à mener, et **ne peut
  pas être fusionnée avant `ENT-056`**.
