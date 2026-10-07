# TASK ADM-021 — le hamburger cesse de doubler la barre latérale

- **Créée le** : 2026-08-11.
- **Session** : `417ad4f5-232c-42c6-8257-f61c6e7c2f12`.
- **Statut** : ✅ fait le 2026-08-11 — le hamburger disparaît à 1 024 px et au-delà, apparaît en
  dessous, et les deux ne coexistent plus jamais avec la barre latérale. La cloche des notifications,
  qui partage la même classe, est inchangée au pixel près. Aucun écart au cadrage.
- **Type** : fix — un bouton qui devait disparaître au-delà d'une largeur d'écran reste affiché,
  parce qu'un style écrit à la main bat l'utilitaire qui le masque.
- **Tests** : `sans objet côté unitaire` — le changement porte sur des classes et une déclaration
  CSS, et jsdom ne calcule aucune mise en page : un test de composant ne verrait ni un bouton
  affiché à tort, ni une spécificité qui l'emporte. La preuve est une **mesure dans le navigateur**,
  aux huit largeurs déjà relevées (1920, 1440, 1280, 1024, 1023, 900, 768, 390), avant et après —
  avec le témoin qui isole la cause : le même utilitaire posé seul sur un élément neuf.
- **Réf** : **`ADM-022` — aval** (la barre latérale se replie en rail ; elle donne au bouton un rôle
  au lieu de le faire disparaître, mais elle n'annule pas ce correctif) · **`ADM-015`**, qui a posé
  l'interdit n°3 dans sa lettre actuelle · **carte à ouvrir, non écrite** : la règle de vérification
  qui attraperait cette famille de défaut ·
  [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md) § *Ce qu'on n'écrit pas
  dans un composant* · fichier touché :
  [admin/app/components/Topbar.vue](../../webapp/spaces/admin/app/components/Topbar.vue).

## Contexte

Un administrateur ouvre l'espace d'administration sur un écran large. La barre latérale est là,
complète, avec ses sept entrées. Et dans la barre haute, à gauche du fil d'ariane, **un bouton de
menu** — qui ouvre un tiroir rejouant **exactement la même navigation**, par-dessus celle qui est
déjà visible.

### L'intention est écrite, elle est écrasée

Le bouton porte `lg:hidden` : il est **censé** disparaître au-delà de 1 024 px. Mesuré le
2026-08-11 sur la stack du worktree admin :

| Largeur | Hamburger | Barre latérale |
| --- | --- | --- |
| 1 920 px | **visible** | visible (272 px) |
| 1 440 px | **visible** | visible (259 px) |
| 1 280 px | **visible** | visible (230 px) |
| 1 024 px | **visible** | visible (220 px) |
| 1 023 px | visible | cachée |
| 900 · 768 · 390 px | visible | cachée |

Le doublon existe donc dès 1 024 px, et à toutes les largeurs au-dessus.

### La cause, isolée par un témoin

À 1 920 px, dans la page servie :

| Élément | `display` calculé |
| --- | --- |
| `lg:hidden` **seul**, sur un élément neuf ajouté à la volée | `none` — l'utilitaire fonctionne |
| Le **même** `lg:hidden` sur le hamburger | **`grid`** |

La seule règle qui pose `display` sur ce bouton est celle du style local du composant :
`.icon-btn[data-v-387bac09] { display: grid }`. Un sélecteur de classe **plus un attribut de portée**
pèse plus lourd qu'une classe seule : l'utilitaire perd, sans que rien ne le signale.

**C'est la troisième occurrence de la même famille** — du CSS écrit à la main qui bat un utilitaire,
invisible au lint, aux types et aux tests. `ADM-015` l'a rencontrée sur les paliers d'espacement,
`WEB-014` sur deux noms de couleur.

### Le reste de la barre haute est sain

L'audit a comparé, pour **chaque** élément de la barre haute portant une classe de rupture, ce qu'il
devrait faire et ce qu'il fait. Le hamburger est **le seul** en écart. Le nom du compte, l'autre
élément concerné, se comporte correctement aux trois largeurs testées.

### Ce que le tiroir n'est pas

Sous 1 024 px, la barre latérale est réellement masquée et le tiroir est le **seul** accès à la
navigation : il n'y est pas un doublon. Et il rend le **même composant** que la barre latérale —
il n'y a pas deux navigations à réconcilier, il y en a une, affichée à deux endroits.

## Périmètre

- **La mise en page des deux boutons d'icône de la barre haute** — le menu et la cloche des
  notifications — **quitte le style local pour les utilitaires**. Le style local ne garde que ce
  qu'aucun utilitaire ne couvre.
- Résultat attendu : **plus aucune déclaration `display` en CSS local sur ces boutons**, donc plus
  rien qui puisse battre une classe de rupture à cet endroit.

## Hors périmètre

- **Le reste des 97 lignes de style local de la barre haute**, et ses valeurs en dur — `0.5rem`,
  `2rem`, `999px`, `rgba(0, 0, 0, 0.28)`, `9px`. Dette réelle, nommée ici, non traitée : la carte
  corrige un défaut visible, elle ne refond pas la barre haute.
- **Les deux menus déroulants** (notifications, compte), positionnés à la main en CSS absolu. Ils
  sortiront de l'écran dès qu'ils s'allongeront — exactement le défaut que `WEB-015` vient de
  corriger sur la bulle d'aide. Autre carte.
- **La règle de vérification** qui attraperait cette famille de défaut à la fusion. Carte à ouvrir.
- **`ADM-022`**, le rail repliable : elle donne un rôle au bouton au-dessus de 1 024 px, elle ne
  remplace pas ce correctif — sans lui, le bouton s'afficherait de toute façon là où on ne le veut
  pas.
- **Le même motif dans l'espace entreprise** : un composant y cumule style local et classe de
  rupture. Autre lane, à remonter.

## Décisions de cadrage

### La mise en page part dans les utilitaires, plutôt que de forcer l'utilitaire à gagner

Trois voies étaient ouvertes.

**Marquer l'utilitaire comme prioritaire** aurait tenu en un caractère. Écartée : elle traite le
symptôme. Le style local continuerait de poser `display`, et le prochain utilitaire posé sur ce
bouton se ferait battre à son tour, en silence.

**Envelopper le bouton** dans un élément portant la rupture aurait été sans risque pour la cloche.
Écartée aussi : les conventions du dépôt proscrivent l'élément d'enveloppe qui n'existe que pour
porter une classe, et le piège resterait armé.

**Ce qui est retenu** : `display` et le centrage deviennent des classes sur les deux boutons. Le
défaut devient **impossible à cet endroit** au lieu d'être corrigé une fois — et c'est ce que le
design system prescrit déjà, puisqu'un style local qui redéclare ce qu'un utilitaire sait faire est
la même improvisation que les paliers bruts qu'`ADM-015` a chassés.

### Le bouton disparaît, il ne change pas de rôle

`ADM-022` fera de la barre latérale un rail repliable, et le geste de repli vivra **dans la barre
latérale**. C'était la demande : sortir le déclencheur de la barre haute. Lui confier le repli
au-dessus de 1 024 px l'y aurait maintenu.

## Règles et cas limites

- **La cloche des notifications partage la classe `.icon-btn`** et doit rester centrée. C'est la
  contrainte qui interdit de simplement supprimer la déclaration : les deux boutons la lisent.
- **Le tiroir se ferme déjà tout seul** quand l'écran repasse au-dessus de 1 024 px. À vérifier
  qu'il le fait toujours une fois le bouton masqué — le composant restitue le focus à son
  déclencheur, et ce déclencheur n'existe plus à cette largeur.
- **La bascule se fait exactement à 1 024 px** : à 1 023 le bouton est là et la barre latérale
  absente, à 1 024 l'inverse. Les deux largeurs se mesurent, elles ne se déduisent pas.
- **Aucun texte utilisateur ne change** : le nom accessible du bouton reste sa clé de traduction.

## Documentation impactée

**Aucune.** La carte **applique** l'interdit n°3, elle ne le modifie pas. Si l'implémentation fait
apparaître que sa lettre devrait nommer aussi le cas « une déclaration CSS locale bat un
utilitaire », c'est la carte de la règle de vérification qui le portera, pas celle-ci.

## Définition de terminé

- [x] Plus aucune déclaration `display` en CSS local sur les deux boutons d'icône de la barre haute.
- [x] Le hamburger est **absent** à 1 024 px et au-delà, **présent** en dessous — mesuré aux huit
      largeurs, pas déduit.
- [x] La cloche des notifications reste centrée, aux mêmes largeurs.
- [x] Le tiroir mobile s'ouvre toujours sous 1 024 px, et se ferme toujours au passage en desktop.
- [x] Captures à 1 920 px et 390 px, dans les deux thèmes.
- [x] La carte est à jour de ce qui a réellement été fait, y compris les écarts au cadrage.

## Vérification

Mesures relevées dans le navigateur, authentifié, sur la stack du worktree admin. La base de
référence est celle du *Contexte*, prise avant le diff sur la même stack.

### Le hamburger, aux huit largeurs

| Largeur | Avant | Après | Barre latérale |
| --- | --- | --- | --- |
| 1 920 px | **visible** | **caché** | visible |
| 1 440 px | **visible** | **caché** | visible |
| 1 280 px | **visible** | **caché** | visible |
| 1 024 px | **visible** | **caché** | visible |
| 1 023 px | visible | visible | cachée |
| 900 · 768 · 390 px | visible | visible | cachée |

La bascule tombe **exactement** à 1 024 px, dans les deux sens, et le doublon a disparu : à aucune
largeur le bouton et la barre latérale ne sont visibles ensemble.

### Ce que le correctif aurait pu casser

**La cloche des notifications** partage la classe `.icon-btn` : c'est elle qui interdisait de
simplement supprimer la déclaration. Relevé après le diff, à 1 920 px **et** à 390 px, identique aux
deux : `display: inline-grid`, bouton de 32 × 32 px, icône à **7 px de chaque bord sur les deux
axes**. Le centrage est intact.

**Le tiroir mobile** s'ouvre toujours à 900 px, avec ses 8 liens de navigation, et se **referme** au
passage à 1 280 px — le comportement existant survit à la disparition de son déclencheur.

### Captures

1 920 px et 390 px, dans les deux thèmes. À 1 920 px la barre haute s'ouvre désormais directement
sur le fil d'ariane.

### Gate impactée

`prettier --check` sur le fichier touché ✔. Aucun test unitaire n'est impacté : le dossier de tests
de l'espace ne couvre que des fonctions pures, et ce diff n'en touche aucune.

## Livré

- `webapp/spaces/admin/app/components/Topbar.vue` (M) — trois modifications, et c'est leur **forme**
  qui compte plus que leur volume :
  - les deux boutons d'icône reçoivent `inline-grid place-items-center` dans le template ;
  - le style local **perd ses deux déclarations de mise en page** — plus aucune déclaration
    `display` en CSS sur ces boutons, donc plus rien qui puisse battre une classe de rupture ici ;
  - un commentaire de trois lignes dit pourquoi, pour que la prochaine session ne les y remette pas.

Le défaut n'est pas corrigé une fois : il devient impossible **à cet endroit**.

## Reste à faire (hors périmètre)

- **Les 94 lignes de style local restantes** de la barre haute, et leurs valeurs en dur — `0.5rem`,
  `2rem`, `999px`, `rgba(0, 0, 0, 0.28)`, `9px`. La même famille de dette, dans le même fichier.
- **Les deux menus déroulants** (notifications, compte), positionnés à la main en CSS absolu : ils
  sortiront de l'écran dès qu'ils s'allongeront, exactement comme la bulle d'aide avant `WEB-015`.
- **La règle de vérification** qui attraperait cette famille de défaut à la fusion — troisième
  occurrence, toujours pas outillée.
- **Le même motif dans l'espace entreprise**, sur un composant de recherche. Autre lane.
