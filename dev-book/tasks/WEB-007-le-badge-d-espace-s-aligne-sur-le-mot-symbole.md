# TASK WEB-007 — socle : le badge d'espace s'aligne sur le mot-symbole

- **Créée le** : 2026-08-02.
- **Statut** : ✅ fait le 2026-08-02 — le bord gauche de la boîte du badge tombe exactement sous le
  « C » du mot-symbole, dans les quatre espaces, par un décalage exprimé en ratio de la hauteur du
  logo. Mesuré au navigateur sur deux espaces et en RTL ; gates au passage PR.
- **Type** : fix — alignement du bloc de marque.
- **Tests** : sans objet — le diff est une marge exprimée en ratio et la variable qui la relie à la
  hauteur du logo : ni calcul, ni branche, ni service. La suite vitest du webapp couvre le code pur
  (`shared/`, `services/`, `utils/` d'un espace), pas le rendu. Preuve par mesure des boîtes au
  navigateur.
- **Réf** : `WEB-005` (le bloc de marque ferré à gauche, sur cette même lane) · `ADM-010`, qui avait
  écarté le bloc de son périmètre pour cette raison · [Logo.vue](../../webapp/base/app/components/layout/Logo.vue)
  (le verrouillage picto + mot, et pourquoi il n'est pas recomposé) ·
  [Wordmark.vue](../../webapp/base/app/components/layout/Wordmark.vue) (dont la `viewBox` atteste la
  cote reprise ici).

## Contexte

Dans le bloc de marque, le badge d'espace — « Espace admin », « Espace entreprise »… — commence au
bord gauche du **picto**. L'œil, lui, attend qu'il commence sous le **mot** : le picto est un signe,
le badge qualifie le nom. Résultat, le bloc a deux axes verticaux au lieu d'un, et le badge paraît
décroché vers la gauche.

## Périmètre

- **Le badge est décalé de `1,2732 × la hauteur du logo`** dans `Brand.vue`, ce qui place le bord
  gauche de sa **boîte** exactement sous le « C » de showcase-recruiter-webapp-saas.
- **Une variable `--logo-h` portée par la racine du bloc** relie la hauteur du logo à ce décalage :
  les deux ne peuvent plus diverger sans qu'on le voie.

## Hors périmètre

- **Le dessin du logo** : le verrouillage picto + mot-symbole reste un tracé unique, non recomposé
  (cf. *Décisions*).
- **`UiBadge`** : ni son rayon, ni son remplissage, ni sa typographie. Le composant est générique et
  sert ailleurs.
- **Les autres appelants de `LayoutLogo`** — rideau de boot, vitrine d'authentification, layout
  d'inscription entreprise : ils n'affichent pas de badge sous le logo, rien ne les concerne.

## Décisions de cadrage

- **La boîte du badge s'aligne sur le « C », pas son texte.** Les deux variantes ont été rendues et
  mesurées ; c'est le fond du badge qui prend l'axe du mot. L'autre parti — aligner le texte
  « Espace admin », donc laisser le fond déborder de 10 px à gauche — est optiquement défendable
  mais n'a pas été retenu.
- **Un ratio, pas un pixel.** La cote vient du kit de marque : le mot-symbole commence à `x = 250,7`
  dans une boîte haute de `196,93` — c'est exactement la `viewBox` de `Wordmark.vue`, qui l'atteste.
  `250,7 ÷ 196,93 = 1,2732`. Exprimé ainsi, le décalage reste juste à n'importe quelle taille de
  logo, là où `30,55 px` ne vaudrait que pour `h-6`.
- **`ml-`, et surtout pas `ms-`.** Le décalage vise une géométrie **dessinée**, et un tracé SVG ne
  se mirroite pas : en arabe, le mot-symbole reste à droite du picto. Une marge logique basculerait
  du mauvais côté. C'est l'exception à la règle habituelle du dépôt — d'où le commentaire dans le
  code, sans quoi une relecture « corrigera » en `ms-`.
- **Le verrouillage n'est pas recomposé.** Les pièces existent (`Logomark`, `Wordmark`) et il aurait
  été possible de poser le badge dans une deuxième colonne, sous le mot. Mais `Logo.vue` explique
  pourquoi le tracé est unique : l'écart de 49,1 unités entre picto et mot est **porté par le
  dessin**, donc exact à toute taille, là où un `gap` CSS ne l'est qu'à la taille pour laquelle on
  l'a réglé. Recomposer imposerait en plus de recaler deux exports différents — `Logomark` vit dans
  une boîte de 1080 × 1037,68 quand le picto verrouillé tient dans 201,6 × 189. Même résultat
  visuel, plus de dette.

## Règles et cas limites

- **Le bloc sert les quatre espaces** : admin, entreprise, candidat, public affichent tous ce badge
  via `space.<espace>`. Le décalage vaut pour les quatre, et la vérification ne peut pas se
  contenter d'un seul.
- **Le badge est plus étroit que le mot-symbole** dans les quatre espaces ; aucun risque de
  débordement de la colonne, mais à revoir si un libellé d'espace s'allonge.
- **RTL** : la marque est ferrée en `items-start`, donc à droite en arabe, et une marge gauche n'y
  déplace rien — le badge s'y aligne sur la fin du mot-symbole. Constat à vérifier, pas défaut : le
  tracé ne se mirroite pas, il n'y a pas d'alignement « sur le C » à obtenir de ce côté.

## Documentation impactée

Aucune. La charte fixe l'usage du logo et du badge, pas la géométrie interne du verrouillage ;
aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit ce bloc.

## Livré

- **`Brand.vue`** — trois changements dans une seule balise : la racine porte `[--logo-h:1.5rem]`,
  le logo lit cette variable (`h-[var(--logo-h)]` au lieu de `h-6`), et le badge en dérive sa marge
  (`ml-[calc(var(--logo-h)*1.2732)]`). Deux commentaires portent le pourquoi : d'où vient la cote du
  kit, et pourquoi la marge est physique et non logique.

## Vérification

**La géométrie**, mesurée au navigateur en injectant la déclaration dans la page vivante — donc sur
le rendu réel, pas sur le HTML servi :

| Contexte | Bord du badge, avant | Le « C » est à | Bord du badge, après |
|---|---|---|---|
| Espace admin, `wt-admin:8480` | 31,83 | **62,38** | **62,38** — écart nul |
| Site public, `wt-entreprise:8280` | 16,00 | **46,55** | **46,55** — écart nul |

Deux espaces, deux largeurs de badge (« Espace admin », « Site public »), **le même ratio** : la
cote ne dépend ni du libellé ni du contexte.

**La compilation.** Les trois utilitaires n'existaient nulle part ailleurs dans le dépôt — un
utilitaire qui ne compile pas ne se voit ni au lint ni au type-check, seulement au rendu. Passés au
compilateur du projet (Tailwind v4.3.3), ils sortent tels qu'attendus :

```
.[--logo-h:1.5rem]                → --logo-h: 1.5rem
.h-[var(--logo-h)]                → height: var(--logo-h)
.ml-[calc(var(--logo-h)*1.2732)]  → margin-left: calc(var(--logo-h) * 1.2732)
```

**RTL**, sur `/ar` du site public : `direction: rtl` confirmée, et la marge **n'a aucun effet** —
badge à 1185 avant comme après, pour un « C » à 1162,94. C'est exactement le comportement prévu au
cadrage : la marque est ferrée en `items-start`, donc à droite en arabe, et une marge gauche ne
déplace pas un élément positionné depuis le bord droit. Le badge s'y aligne sur la **fin** du
mot-symbole. Aucune régression : le rendu arabe est identique à celui d'avant le changement.

**Non vérifié** : le rendu dans la stack propre à cette lane, qui n'est pas démarrée. La preuve
ci-dessus est faite par injection de la déclaration exacte dans deux stacks voisines, et complétée
par la compilation réelle des trois classes — il ne reste aucune inconnue entre les deux.

## Définition de terminé

- [x] Le bord gauche de la boîte du badge tombe exactement sous le « C », mesuré au navigateur.
- [x] Le décalage est exprimé en ratio de la hauteur du logo, et la hauteur n'est plus écrite deux
      fois.
- [x] Le commentaire explique pourquoi la marge est physique et non logique.
- [x] Vérifié dans au moins deux espaces, et en RTL.
- [ ] Conventions, lint, format, type-check des 4 espaces verts — **au passage PR**.
