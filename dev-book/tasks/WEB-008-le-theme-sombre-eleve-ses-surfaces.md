# TASK WEB-008 — socle : le thème sombre élève ses surfaces au lieu de les creuser

- **Créée le** : 2026-08-02.
- **Statut** : ✅ fait le 2026-08-02 — en thème sombre, la page prend `shark-900` et les panneaux
  `shark-800` : les surfaces sont posées au lieu d'être creusées. Mesuré aux couleurs calculées dans
  deux espaces, thème clair inchangé ; gates au passage PR.
- **Type** : fix — inversion de deux tokens de thème.
- **Tests** : sans objet — le diff échange deux valeurs dans le bloc `.dark` de `themes.css` : ni
  calcul, ni branche, ni service. La suite vitest du webapp couvre le code pur, pas le rendu. Preuve
  par mesure des couleurs calculées et par capture.
- **Réf** : `WEB-007` (le badge d'espace, sur cette même lane) · `ADM-012` (la pilule d'item actif,
  qui lit `--bg` et dont la lecture s'inverse avec ce changement) ·
  [palette.css](../../webapp/base/app/assets/css/palette.css) (les deux échelles de neutres).

## Contexte

En thème sombre, les panneaux (`--surface`, `#1a1a1a`) sont **plus sombres** que la page
(`--bg`, `#212121`) : ils se lisent comme des creux dans le fond plutôt que comme des plans posés
dessus. C'est la transposition littérale du thème clair, où l'élévation se signale par l'ombre —
mais sur fond sombre, la convention s'inverse : la lumière vient d'en haut, donc ce qui est élevé
est plus **clair**.

Le thème sombre se contredit d'ailleurs déjà lui-même : `--surface-soft` (`#434343`) est, lui, plus
clair que la page. La marche part dans un sens pour les panneaux, dans l'autre pour les surfaces
douces.

## Périmètre

- **Dans le bloc `.dark` de `themes.css`**, `--bg` et `--surface` échangent leur valeur : la page
  prend `shark-900`, les panneaux `shark-800`.
- **`--dropdown-bg` suit les panneaux** (`shark-800`), pour qu'un menu continue de se lire comme un
  panneau — c'est sa relation actuelle, et elle est conservée.

## Hors périmètre

- **Le thème clair** : sa direction — panneaux plus sombres que la page — est la bonne pour un fond
  clair. Les deux thèmes divergent volontairement.
- **`--surface-soft`** (`shark-700`) : il devient simplement la dernière marche d'une échelle
  désormais monotone, sans changer de valeur.
- **L'échelle `shark` elle-même** : aucune valeur du kit n'est touchée, seuls les tokens sémantiques
  qui y puisent.
- **Les composants** : aucun ne change ; ils lisent des tokens, c'est tout l'intérêt.

## Décisions de cadrage

- **On inverse, on ne creuse pas l'écart.** Les deux valeurs restent séparées de 7 niveaux de gris,
  soit un contraste de 1,08:1 : le changement porte sur le **sens** de la séparation, pas sur sa
  force. Ouvrir l'écart serait un autre sujet, et il se mesurerait.
- **`--on-accent` n'a rien à faire** : il est déclaré `var(--bg)` dans `.dark` et suit donc tout
  seul. Le socle prévient qu'un token dérivé doit être re-déclaré par thème ; ici c'est déjà le cas.
- **Le menu déroulant garde sa relation, pas sa valeur.** Il vaut aujourd'hui exactement
  `--surface` ; le laisser sur `shark-900` en ferait un trou à la couleur de la page, posé sur des
  panneaux devenus plus clairs.

## Règles et cas limites

- **La cartouche d'item actif de la barre admin lit `--bg`** (`ADM-012`, pas encore mergée) : elle
  est aujourd'hui plus claire que la barre, elle deviendra plus sombre. Le repère de bord garde son
  rôle, mais la cartouche passe de posée à creusée — constaté au rendu et assumé côté admin.
- **Le contraste du texte ne bouge pas** : `--text` reste blanc, et les deux fonds concernés sont à
  16,1:1 et 17,4:1. Aucun risque d'accessibilité.
- **`.dark` sert aussi d'îlot** posé sur un `<div>` (l'inscription entreprise) : le changement vaut
  là aussi, à vérifier.

## Documentation impactée

Aucune. La charte fixe l'accent et les familles de neutres, pas l'ordre d'élévation des surfaces.

## Livré

**`themes.css`**, dans le seul bloc `.dark` : `--bg` et `--surface` échangent leur valeur, et
`--dropdown-bg` passe de `shark-900` à `shark-800` pour rester égal aux panneaux. Un commentaire
porte le pourquoi — l'élévation s'inverse avec le fond, et l'échelle devient monotone.

## Vérification

Couleurs **calculées** dans Chromium, en injectant le bloc `.dark` à l'identique sur les pages
vivantes.

| | Sombre, avant | Sombre, après | Clair |
|---|---|---|---|
| Page | `rgb(33,33,33)` | **`rgb(26,26,26)`** | inchangé |
| Panneaux | `rgb(26,26,26)` | **`rgb(33,33,33)`** | inchangé |
| Menu déroulant | `rgb(26,26,26)` | **`rgb(33,33,33)`** | inchangé |
| Cartouche d'item actif | `rgb(33,33,33)` | **`rgb(26,26,26)`** | inchangé |

- **Deux espaces** : l'admin (`wt-admin:8480`) et le site public (`wt-entreprise:8280`), même
  bascule dans les deux — le token vaut bien pour les quatre.
- **Le thème clair est identique au pixel** sur les quatre points mesurés : le changement est borné
  au sombre.
- **La cartouche d'item actif s'inverse comme prévu** : elle lit `--bg`, donc elle passe de plus
  claire à plus sombre que la barre et se lit désormais comme une encoche creusée. Constaté, assumé.
- **Non vérifié** : le rendu dans la stack propre à cette lane, qui n'est pas démarrée — la preuve
  est faite par injection de la déclaration exacte dans deux stacks voisines.

## Définition de terminé

- [x] En sombre, les panneaux sont plus clairs que la page, mesuré aux couleurs calculées.
- [x] Un menu déroulant se lit toujours comme un panneau.
- [x] Le thème clair est inchangé, vérifié.
- [x] Vérifié dans au moins deux espaces.
- [ ] Conventions, lint, format, type-check des 4 espaces verts — **au passage PR**.
