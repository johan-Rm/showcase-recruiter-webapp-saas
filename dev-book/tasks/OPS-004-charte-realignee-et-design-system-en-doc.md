# TASK OPS-004 — doc : la charte réalignée sur le code, et le design system dans la doc technique

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29 — charte réécrite depuis les fichiers de tokens, section
  `4.design` créée, doublons supprimés, références repointées.
- **Type** : Documentation — aucun changement de code, aucun changement de comportement.
- **Réf** :
  - `webapp/base/app/assets/css/` (les sept fichiers de tokens, source de vérité de la réécriture)
  - `webapp/base/app/pages/ux-kit.vue` (la galerie vivante)

## Contexte

Trois documents de marque cohabitaient dans `dev-book/cadrage/requirements/`, et **aucun ne
décrivait le produit** :

- `brand_guidelines.md` posait une palette d'hexadécimaux bruts (`--bg: #ffffff`,
  `--accent: #054a29`) qui n'existe nulle part dans le code ;
- `brand_guidelines_simplifie.md` annonçait une signature beige argile `#C8A882` — une palette
  abandonnée, en contradiction frontale avec le document principal ;
- `Brand Guidelines - Minimaliste.html` en était le rendu figé.

Le code, lui, a introduit des échelles nommées (`porcelain`, `shark`, `sherwood-green`, `gossip`,
`yellow-green`) et interdit tout hexadécimal hors `palette.css`. L'écart dépassait la palette : la
charte décrivait un pictogramme en « N » stylisé là où le kit dessine cinq pétales, et écrivait la
marque en deux mots quand le mot-symbole en fait un seul.

Trois chapitres du code n'étaient documentés nulle part : l'échelle typographique et le rythme
d'espacement (`sizing.css`), les tokens de mouvement (`transitions.css`), et le remappage de la
bibliothèque de composants (`nuxt-ui.css`).

Enfin, `AGENTS.md` renvoyait le rôle Frontend vers `dev-book/cadrage/BRAND_GUIDELINES.md`, un
chemin qui n'a jamais existé — deux fois, et une troisième dans `i18n.md`.

## Décisions

### Un seul document de marque, réécrit depuis le code

Les deux doublons disparaissent. La charte est reconstruite fichier par fichier contre
`palette.css`, `themes.css`, `sizing.css`, `fonts.css`, `transitions.css` et `nuxt-ui.css` : le
nuancier complet (5 échelles × 11 paliers), les 14 tokens sémantiques avec leur valeur dans chaque
thème, l'échelle typographique fermée à 7 niveaux, le rythme d'espacement, les gabarits, les
tokens de mouvement.

Les mesures de contraste qui vivaient en commentaire de `themes.css` deviennent l'argument
explicite du chapitre couleurs : c'est ce qui impose un accent **différent par thème**, et non un
goût.

### La charte quitte le cadrage pour la doc technique

`dev-book/cadrage/` est écrit pour le client. La charte, une fois alignée sur le code, nomme des
variables CSS et des utilitaires : elle a changé de lectorat. Elle rejoint donc le site Docus, dans
une quatrième section `4.design`, aux côtés de `1.getting-started`, `2.architecture` et
`3.quality`.

Deux ajustements de forme imposés par le site : nom en kebab-case comme le reste de `docs/`, et
hiérarchie de titres remise à plat — le document empilait onze `#` de niveau 1, ce qui aurait
produit un sommaire illisible.

### Charte et design system sont deux documents, pas un

La charte dit **quoi** : les valeurs et leur intention. Le design system dit **où** et **comment** :
la cascade à trois étages, le fichier propriétaire de chaque chose, les utilitaires à écrire, et ce
qu'il est interdit d'écrire dans un composant.

Les séparer évite le document unique que personne ne lit en entier. Un designer s'arrête à la
charte ; un développeur qui pose un écran va directement aux interdits.

### Les interdits sont écrits, faute de gate

Rien ne vérifie aujourd'hui qu'un composant n'écrit pas `#0f4b37` ou `bg-porcelain-300`. La
discipline n'était portée que par les commentaires en tête de `palette.css`. Elle est désormais
énoncée en cinq règles dans le design system — un pis-aller documentaire tant qu'aucune règle
ESLint ne l'applique.

### Le piège de l'îlot `.dark` sort du commentaire

`.dark` ne se pose pas qu'à la racine : le tunnel d'inscription entreprise l'applique à un `<div>`.
Un token qui en lit un autre doit donc être re-déclaré dans `.dark`, sinon il descend dans l'îlot
déjà calculé sur le thème clair, et l'encre d'un bouton accent tombe à 1,4:1. Le piège était enterré
dans `themes.css` ; il est maintenant dans la doc que lit celui qui ajoute un token.

## Vérification

- **Utilitaires** : chaque famille annoncée dans le design system a été comptée dans les composants
  réels — `text-muted` 167 occurrences, `gap-element` 101, `bg-surface` 65, `text-small` 55,
  `xwide:` 48, `max-w-readable` 11. Aucun utilitaire documenté qui n'existe pas.
- **Liens** : les treize liens relatifs des trois fichiers de `4.design` résolvent, vérifiés un par
  un. Le chemin relatif depuis `dev-book/cadrage/entreprise/ui/README.md` vers la doc aussi.
- **Références mortes** : plus aucune occurrence de `BRAND_GUIDELINES.md` ni de
  `requirements/brand_guidelines` hors cartes de tâches et brouillons de maquettes.

## Livré

- `docs/showcase-recruiter-webapp-saas/content/4.design/index.md` (+) : sommaire de section et « par où commencer ».
- `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md` (+, déplacé) : la charte réécrite.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (+) : cascade, fichiers propriétaires,
  utilitaires, cinq interdits, îlot `.dark`, galerie UX Kit et son export PDF, procédure
  d'extension.
- Les deux doublons de marque supprimés.
- `AGENTS.md`, `i18n.md`, `todo.md`, `entreprise/ui/README.md`, `docs/…/index.md` : références
  repointées, section ajoutée au sommaire.

## Reste à faire (hors périmètre de cette tâche)

- **Le PDF de l'UX Kit n'est pas déposé.** Le design system pointe `/ux-kit.pdf`, servi depuis
  `docs/showcase-recruiter-webapp-saas/public/ux-kit.pdf` : tant que le fichier n'y est pas, c'est un lien mort. Une
  commande qui régénère le PDF depuis la route `/ux-kit` en print-to-PDF headless éviterait de
  dépendre d'un export manuel.
- **Aucune gate n'applique les cinq interdits.** Une règle ESLint refusant l'hexadécimal et les
  teintes du nuancier dans `webapp/**` transformerait la convention en verrou, comme
  `app-prefer-useeventlistener` l'a fait pour VueUse.
- **La section « Design » de `dev-book/cadrage/todo.md` est périmée de bout en bout** : Cormorant
  Garamond, DM Sans, palette « terre & nature », « boutons argile », et un design system coché comme
  à faire alors qu'il tourne. Seuls les chemins ont été repointés ici.
- **Deux mentions subsistent dans `webapp/`** — `webapp/README.md` classe encore le design system
  dans « reste à brancher », et un commentaire de `ui/Button.vue` cite l'ancien nom de fichier.
  Elles relèvent d'`espace/socle-webapp`.
- **Les couleurs de vues du Vivier** (nuancier à onze teintes, règle des hues distinctes) ont été
  retirées de la charte : aucune n'existe dans le code, et c'est une décision produit, pas une
  décision de marque. Leur place est dans `dev-book/cadrage/entreprise/ui/`.
