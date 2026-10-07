# TASK ENT-082 — entreprise : les deux gestes du verdict s'affinent et se nomment

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — « Retenir » et « Écarter » sont fins, portent une icône plus
  petite que leur texte, se nomment tous les deux, et « Retenir » prend la couleur de la marque.
  **Tous les boutons du produit retrouvent au passage le curseur de la main.**
- **Type** : mise en forme, plus un correctif de socle qui déborde volontairement de l'écran.
- **⚠️ Cadrée après coup.** Retours donnés en conversation, écran sous les yeux, quatre allers-retours
  successifs. La carte est rédigée au commit ; chaque arbitrage a été montré avant d'être figé.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle change des classes de
  présentation, un réglage de thème et la valeur de trois libellés. La preuve est une **mesure des
  styles calculés** avant / après, reportée plus bas, et le rendu des deux états du bouton.
- **Réf** : `ENT-076` — qui a posé ces deux gestes dans la colonne du verdict · `ENT-058` — l'aplat
  accent unique de l'écran, que cette carte respecte · maquette
  `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-latest.html`, règle `.viewer-sl`.

## Contexte

Les deux gestes de la colonne du verdict pesaient plus que ce qu'ils font. L'icône faisait **vingt
pixels pour un texte de quatorze** : elle dominait le mot qu'elle ne fait que redire. Et « Écarter »
n'était qu'une icône, sans libellé — un geste qui retire un profil de la pile, qu'il fallait deviner.

La maquette de référence pose le rapport inverse : un signe **à la taille du texte, ou plus petit**,
sur un bouton fin.

## Périmètre

- **Les deux boutons du verdict** : corps réduit, icône ramenée à la taille du texte, hauteur
  identique pour les deux.
- **« Écarter » se nomme** : le libellé devient visible, et il est raccourci dans les trois langues
  pour tenir à côté de « Retenir » dans une colonne de trois cents pixels.
- **« Retenir » prend la couleur de la marque**, en contour.
- **Le curseur de la main sur TOUS les boutons du produit** — cf. *Décisions de cadrage*.

## Hors périmètre

- **La couleur du bouton « Écarter »**, qui reste neutre : il vient après, et le dire par la couleur
  coûte moins que par la taille.
- **Les deux archives `search-v1/` et `search-v2/`**, qui gèlent un état et gardent leurs boutons.
- **Les vingt avertissements de lint** du webapp, sans rapport et préexistants.

## Décisions de cadrage

### L'icône passe SOUS la taille du texte

Douze pixels d'icône pour douze pixels de texte : elle ponctue le mot au lieu de l'annoncer. C'est le
rapport du gabarit compact de la maquette (`.viewer-sl.sl-compact` : icône 12, texte 11).

Deux passes ont été nécessaires — quatorze pixels d'abord, puis douze sur retour. La première valeur
tenait le rapport de la maquette non compacte ; la seconde va au bout de l'intention.

### La couleur de la marque, mais en CONTOUR

Arbitré, et contraint par une décision déjà écrite. `ControlPanel.vue` porte noir sur blanc : *« C'est
le seul aplat accent de l'écran — les accès de la carte y ont renoncé pour que celui-ci veuille
encore dire quelque chose »*, à propos de la sortie vers la Table.

Un « Retenir » en aplat accent en poserait un second et retirerait sa préséance au premier. Le
contour donne la signature sans la disputer : encre et bordure accent, fond transparent. L'état
**retenu**, lui, garde son aplat dilué — c'est un état acquis, pas une invitation.

### « Écarter » se nomme, et le libellé raccourcit dans les trois langues

Une icône seule laisse chercher ce qu'elle fait, et celle-ci retire un profil de la pile.

Le libellé existant — « Écarter ce profil », « Set this profile aside » — ne tient pas à côté de
« Retenir » dans une colonne de trois cents pixels. Il devient donc « Écarter » / « Set aside » /
« استبعاد ». **La valeur change, pas la clé** : deux clés pour le même geste auraient été du bruit,
et le mot court reste exact comme nom accessible.

Conséquence assumée : les deux archives emploient cette clé en infobulle d'un bouton sans libellé.
Elles disent maintenant « Écarter » au lieu de « Écarter ce profil » — deux mots de moins sur un
échafaudage temporaire.

### Le curseur : corrigé PARTOUT, et c'est un débordement volontaire

Le retour portait sur ces deux boutons. La mesure a montré que le défaut est **global** — Tailwind v4
a retiré le `cursor: pointer` que le navigateur posait sur `<button>`, et **tous** les boutons Nuxt UI
du produit rendent la flèche :

```
Retenir · Écarter (colonne)      default
CV · 1 cr. (fiche profil)        default
ordre du parcours (gouttière)    default
Ma sélection                     pointer   ← c'est un lien, pas un bouton
case « Écartés »                 pointer   ← bouton fait main, classe posée à la main
```

Le corriger sur deux boutons aurait laissé fautifs, **sur l'écran même qu'on soigne**, les accès CV
et compte rendu et le sélecteur d'ordre. Arbitré avec l'humain : une ligne de thème dans le socle,
qui vaut pour les quatre espaces.

Posée sur le **slot `base`** : elle s'ajoute aux classes du thème plutôt que de les remplacer, et
`disabled:cursor-not-allowed` continue de la supplanter — vérifié, un bouton désactivé rend toujours
`not-allowed`.

## Règles et cas limites

- **Les deux boutons ont la même hauteur** : « Écarter » passe de fantôme à contour, ce qui lui rend
  les deux pixels de bordure qui lui manquaient.
- **Le nom accessible suit le libellé visible** : l'`aria-label` disparaît, le texte du bouton le
  remplace. Un nom accessible qui diverge du libellé visible est un défaut à part entière.
- **Le repli sous 1280 px** — le verdict en pied de fiche — porte les mêmes boutons : un seul
  composant, une seule correction.
- **Recherche d'un collègue** : les deux boutons n'existent pas, rien ne change.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni convention de dossier ne change. La charte
(`4.design/design-system.md`) ne prescrit pas de taille de bouton — vérifié — et la règle qu'elle
porte, l'aplat accent unique, est **respectée** et non modifiée.

## Définition de terminé

- [x] L'icône est plus petite que le texte sur les deux boutons.
- [x] Les deux boutons ont la même hauteur et se nomment.
- [x] « Retenir » porte la couleur de la marque sans poser de second aplat accent.
- [x] Tous les boutons du produit rendent le curseur de la main ; les désactivés gardent le leur.
- [x] Le libellé raccourci tient dans les trois langues.

## Vérification

### Les styles calculés, avant et après

```
                    avant        après       référence maquette
hauteur             32 px        28 px       32 px  (.viewer-sl)
texte               14 px        12 px       12 px
icône               20 px        12 px       12 px  (.sl-compact)
rapport icône/texte 1,43         1,00        1,09
```

### Les deux états de « Retenir »

```
non retenu : fond rgba(0,0,0,0) · encre rgb(198,231,107) · contour accent   ← la signature
retenu     : aplat accent dilué, étoile allumée
```

### Le curseur, après le réglage

```
Retenir · Écarter · CV · 1 cr. · ordre du parcours · Ma sélection · case « Écartés »   → pointer
boutons désactivés « Classiques » et « Secondaires »                                   → not-allowed
```

### Les suites impactées

```
$ bunx vitest run base/i18n/locales/parity.vitest.ts      → 9 tests, 9 verts
$ bun run quality-conventions-check   (sur l'hôte)        → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/Verdict.vue` | (M) deux boutons fins, icône réduite, libellé sur « Écarter », signature sur « Retenir » |
| `base/app/app.config.ts` | (+) le curseur de la main sur tous les boutons du produit |
| `base/i18n/locales/{fr,en,ar}.yaml` | (M) `search.action.dismiss` raccourci |

## Reste à faire (hors périmètre)

- **Les autres espaces héritent du curseur** sans avoir été rendus ici : le correctif est une classe
  unique sur un slot de thème, mais il n'a été vérifié qu'à l'écran de la Visionneuse. Un coup d'œil
  sur l'admin, le candidat et le public le confirmera au prochain passage.
- **Les deux archives** gardent les anciens boutons, et c'est leur raison d'être.
