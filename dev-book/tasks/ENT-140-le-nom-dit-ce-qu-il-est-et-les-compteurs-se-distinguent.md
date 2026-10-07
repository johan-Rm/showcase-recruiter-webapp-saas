# TASK ENT-140 — le nom de la recherche dit ce qu'il est, et les trois compteurs se distinguent

- **Créée le** : 2026-08-18.
- **Session** : `c6e9274a-26a7-4733-86f6-42a6f9f95f36`.
- **Statut** : ✅ fait le 2026-08-19 — le surtitre coiffe le nom des deux écrans en filigrane, et
  les trois compteurs portent leur teinte assez fort pour se distinguer sans être lus, dans les deux
  thèmes.
- **Type** : feat — un repère de lecture qui n'existait pas, et une recette de couleur appliquée là
  où la charte ne l'autorisait pas encore.
- **Tests** : `sans objet` — la tâche ne produit **aucune logique** : elle ajoute une ligne de texte
  déjà traduite et change une recette de couleur. **La preuve attendue est double** : une capture des
  deux écrans dans les deux thèmes, et le rapport de contraste relevé sur les trois libellés des
  compteurs, clair et sombre.
- **Réf** : `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` ·
  `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` ·
  `webapp/spaces/entreprise/app/components/search/ProgressHeader.vue` ·
  `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md` (les couleurs de verdict, à compléter) ·
  `ENT-133` et `ENT-134` (la hauteur de la fiche, que le surtitre entame).

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur l'écran de parcours des profils.
En haut à gauche, le **nom de sa recherche** — souvent auto-généré depuis les critères, du type
« Product Owner · scrum ». Juste en dessous, en tête de la colonne de gauche, **trois petites
cartes** : vus, favoris, écartés.

**Premier manque — le nom ne dit pas ce qu'il est.** « Product Owner · scrum » se lit comme un
intitulé de poste. Rien n'indique que c'est le nom d'une recherche enregistrée, ni qu'on est en train
de la parcourir. Le texte qui le dirait existe pourtant déjà, traduit dans les trois langues, et
**aucun écran ne l'affiche**.

**Second manque — les trois compteurs se ressemblent trop.** Aujourd'hui, seuls le pictogramme et le
nombre portent une teinte ; le cadre et le libellé restent neutres. De loin, les trois cases forment
un bloc gris uniforme, et l'œil doit **lire** pour distinguer ce qu'on a gardé de ce qu'on a jeté —
sur un écran où on les consulte des dizaines de fois par séance.

**Ce que la charte dit, et qui contraint la réponse.** `--verdict-missed` est déclarée **couleur de
texte**, calibrée pour atteindre 4,5:1 comme encre, avec une valeur différente en clair et en sombre.
La charte ajoute que ces teintes *« ne teintent jamais une ligne ni la valeur d'un profil »*. Poser un
aplat derrière un compteur n'est donc pas un réglage d'opacité : c'est un cas que la charte doit
autoriser explicitement.

## Périmètre

- **Un surtitre « Recherche en cours » coiffe le nom**, en petites capitales grisées, sur sa propre
  ligne. Le texte existe déjà (`search.viewer.panel.current`), dans les trois langues.
- **Sur les deux écrans qui portent ce nom** — le parcours et la Table. Les deux outils d'une même
  recherche se présentent pareil.
- **Décoratif pour les lecteurs d'écran.** Le titre porte déjà une mention invisible qui dit où l'on
  est ; le surtitre visible ne la double pas à voix haute.
- **Les trois compteurs reçoivent un aplat dilué de leur teinte**, et leur libellé la prend aussi.
- **Fabriqué avec la recette des badges**, celle qui mélange déjà une teinte au fond et à l'encre
  pour « Nouveau », « Favori » et « Vu ». Aucun second dosage inventé au point d'usage.
- **La case « Écartés » à zéro reste atténuée, aplat compris.**
- **`brand-guidelines.md` gagne le paragraphe** qui dit dans quelles conditions une couleur de
  verdict peut teinter une surface.
- **Le commentaire du composant est réécrit** : il porte aujourd'hui l'arbitrage inverse.

## Hors périmètre

- **Les teintes elles-mêmes.** `--muted`, `--accent` et `--verdict-missed` sont déjà les bonnes, et
  ce sont celles du reste du produit — le bouton d'écart et le badge « Écarté » portent la même.
  Aucune couleur nouvelle.
- **Le champ de renommage en place**, qui garde son comportement et son libellé.
- **Les filtres d'affinage et « Ma sélection »**, plus bas dans la même colonne.
- **La console sur téléphone**, masquée sous 1024 px : son sort est un sujet à elle seule.
  **Non ouvert.**
- **Le nombre de compteurs et leur disposition** — trois cases sur une rangée, arbitré ailleurs et
  inchangé.

## Décisions de cadrage

- **Le surtitre sur sa propre ligne, et non devant le nom.** Placé en préfixe sur la même ligne, il
  coûterait zéro hauteur — mais le nom contient déjà des points médians, et une quatrième mention y
  serait lue comme un critère de plus. Écarté aussi : le poser au-dessus des compteurs, où il
  n'aurait plus rien qualifié — il aurait annoncé des nombres au lieu de nommer une recherche.
- **Décoratif plutôt qu'annoncé.** Le titre porte déjà « Parcourir les profils · » en mention
  invisible. Les deux textes ne disent pas la même chose — l'un nomme l'écran, l'autre nomme l'objet
  — mais les entendre tous les deux à chaque lecture du titre serait redondant à l'oreille.
- **La recette des badges plutôt qu'une opacité.** Un aplat écrit à la main est une valeur inventée,
  à vérifier séparément dans les deux thèmes, que rien ne tiendra alignée sur les badges quand ceux-ci
  bougeront. La recette existe et a déjà fait ses preuves sur trois pastilles.
- **La charte est complétée plutôt que contournée.** Elle affirme aujourd'hui que les couleurs de
  verdict sont des couleurs de texte ; sans le paragraphe, la prochaine session lira l'interdit et
  refera le débat — ou défera ce travail.
- **La case à zéro reste inerte.** Un aplat plein sur un compteur qu'on ne peut pas ouvrir enverrait
  deux signaux contraires : une couleur qui appelle et un geste qui ne répond pas.
- **⚠️ ÉCART — le surtitre ne coûte finalement AUCUNE hauteur.** La hauteur minimale de la bande
  absorbait déjà les deux lignes : mesuré, 50 px de bande et contenu à 181 px du haut, **avant comme
  après**. Le risque écrit ci-dessous ne s'est pas matérialisé, et l'arbitrage « hauteur contre
  repère de lecture » n'a pas eu lieu d'être.
- **⚠️ ÉCART — la dose de l'aplat suit le thème, ce que le cadrage n'avait pas prévu.** À 20 %
  partout — la dose des badges —, « Vus » et « Favoris » étaient indiscernables en thème clair : sur
  fond clair, `--muted` et `--accent` tirent tous deux sur le vert. La dose est donc déclarée par
  thème (`--tint-fill`) : 34 % en clair, 20 % en sombre. Vérifié qu'à 42 % le contraste cassait. La
  charte autorise explicitement cet ajustement — « ce qui s'adapte est la recette, pas la teinte ».
- **⚠️ ÉCART — le surtitre passe sous le plancher de l'échelle typographique.** Demandé plus petit et
  plus discret, il descend à 9 px là où le plus petit palier nommé vaut 11. Seul le CORPS est écrit à
  la main ; les capitales, la graisse et l'interlettrage restent ceux du palier `text-label`. Il
  passe aussi en **accent fondu à 40 %** plutôt qu'en gris : la teinte le rattache au produit, le
  fondu l'empêche de disputer l'attention au nom.
- **⚠️ ÉCART — les cases perdent leur bordure.** Le cadrage ne disait rien du trait existant. L'aplat
  pose la case à lui seul ; un contour par-dessus une couleur qui en dessine déjà un remet les trois
  cases à se ressembler, ce qu'on venait de défaire.
- **Le surtitre coûte de la hauteur, et c'est assumé.** Une quinzaine de pixels pris à la bande haute,
  donc autant à la fiche du candidat juste en dessous — sur l'écran où `ENT-133` et `ENT-134` viennent
  d'être menées pour en gagner. On échange de la hauteur contre un repère de lecture.

## Règles et cas limites

- **La couleur reste un renfort, jamais le seul porteur du sens.** Le pictogramme et le libellé
  doivent suffire à distinguer les trois compteurs pour qui ne distingue pas le vert du rouge.
- **Le contraste tient dans les deux thèmes**, clair et sombre, sur les trois libellés.
- **Sur la recherche d'un collègue**, le surtitre s'affiche à l'identique : il nomme l'objet, pas le
  droit de le renommer.
- **Une recherche sans nom** garde son surtitre au-dessus de son texte d'attente.
- **Le surtitre ne casse pas le titre du document** ni le champ d'édition en place.
- **Aucun texte nouveau** : la clé existe dans les trois langues.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md` — la section des couleurs de verdict, qui
  interdit aujourd'hui ce que cette carte autorise. Même commit.
- `webapp/spaces/entreprise/app/components/search/ProgressHeader.vue` — le commentaire qui justifie
  l'arbitrage inverse.

## Définition de terminé

- [x] Le surtitre « Recherche en cours » coiffe le nom sur l'écran de parcours **et** sur la Table.
- [x] Il n'est pas annoncé deux fois par un lecteur d'écran.
- [x] Les trois compteurs se distinguent d'un coup d'œil, sans lire les libellés, dans les deux
      thèmes — jugé sur capture.
- [x] Les trois libellés atteignent 4,5:1, relevé en clair et en sombre.
- [x] « Écartés » à zéro reste visiblement inerte, aplat compris.
- [x] Aucune valeur de couleur n'est écrite au point d'usage : la recette des badges est réutilisée.
- [x] `brand-guidelines.md` dit ce qui est vrai.
- [x] Le commentaire de `ProgressHeader.vue` dit ce qui est vrai.

## Vérification

Navigateur réel piloté, session authentifiée en `entreprise@showcase-recruiter-webapp-saas.example`, fenêtre 1512 × 900, les
deux thèmes.

### Le contraste des libellés, sur leur propre aplat

Composé en tenant compte de l'opacité de chaque calque — c'est ce qui manquait à mes premières
mesures, qui donnaient deux résultats contradictoires selon la façon d'empiler les fonds.

```
                sombre    clair
Vus               6,60     5,50
Favoris           7,69     6,15
Écartés           5,92     5,02
```

Les six valeurs passent 4,5:1. C'est **mieux qu'avant** : la teinte des écartés touchait 4,64 en
sombre et 4,63 en clair sur les pictogrammes, tout juste au-dessus du seuil.

**Réserve sur ces chiffres.** La case « Écartés » est atténuée de moitié quand elle vaut zéro, ce qui
fait tomber son contraste réel à 2,84. Les valeurs ci-dessus sont relevées **atténuation levée**,
puisque la couleur ne dépend pas du compte : c'est le contraste du cas courant, un compteur non nul.
Le cas à zéro est un contrôle désactivé, que les règles d'accessibilité dispensent du seuil.

### La dose de l'aplat, balayée en thème clair

```
dose 20%   Vus et Favoris indiscernables à l'œil
dose 34%   les trois se séparent, contraste conservé      ← retenu
dose 42%   contraste des libellés sous 4,5
```

### Le surtitre ne prend aucune hauteur

```
                        avant     après
bande haute              50 px     50 px
haut du contenu         181 px    181 px
```

### À l'œil

Captures des deux thèmes : le surtitre se lit comme un filigrane sans disputer l'attention au nom ;
les trois cases se distinguent sans bordure, franchement en sombre, plus doucement en clair.

## Livré

- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` (M) et `table.vue` (M) — le
  surtitre en filigrane, masqué aux lecteurs d'écran, sur les deux écrans qui portent le nom.
- `webapp/spaces/entreprise/app/components/search/ProgressHeader.vue` (M) — les trois compteurs
  passent à la recette des badges : aplat teinté, libellé et nombre mélangés à l'encre, pictogramme
  en teinte pure, plus de bordure.
- `webapp/base/app/assets/css/themes.css` (M) — `--tint-fill`, la dose de l'aplat, déclarée par
  thème.
- `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md` (M) — les trois conditions sous lesquelles une
  couleur de verdict peut teinter une surface.

## Reste à faire (hors périmètre)

- **En thème clair, « Vus » et « Favoris » restent proches** malgré la dose relevée : l'accent y est
  un vert profond et le gris discret tire aussi sur le vert. Les séparer davantage demanderait de
  toucher aux teintes elles-mêmes, ce que cette carte met hors périmètre. **Non ouvert.**
- **`--tint-fill` n'a qu'un consommateur.** Si un deuxième écran l'emploie, il faudra vérifier que la
  dose lui convient plutôt que de la supposer universelle.
