# TASK ENT-129 — la barre de défilement de la console touche le bord

- **Créée le** : 2026-08-16.
- **Session** : `b5fe5724-1c7f-4c06-ad0b-9ce84888cfe0`.
- **Statut** : ✅ fait le 2026-08-16 — la zone défilante occupe toute la largeur de la console et sa
  barre touche le bord ; écart mesuré à 0, contre 19 pixels avant.
- **Type** : fix — une marge intérieure posée un cran trop haut, qui empêche la zone défilante
  d'atteindre les bords de sa colonne.
- **Tests** : `sans objet` — la tâche ne produit **aucune logique** : elle déplace des marges dans un
  gabarit. Le dépôt n'a aucun test de rendu sur ses 28 fichiers de test, et ce qui se juge ici est
  une géométrie. **La preuve attendue est la mesure** : l'écart entre le bord droit de la console et
  celui de la zone défilante, relevé à l'écran avant et après.
- **Réf** : `webapp/spaces/entreprise/app/components/search/ControlPanel.vue` ·
  `webapp/base/app/components/README.md` (conventions de composant) ·
  `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (les jetons d'espacement, et l'interdit sur les
  valeurs écrites à la main).

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, parcourt les profils d'une recherche. À
**gauche**, la console : l'avancement en haut, les filtres d'affinage au milieu, le décompte des
profils retenus par l'affinage en bas, et le pied « Ma sélection » en aplat plein.

**Ce qu'on voit.** Dès que les filtres dépassent la hauteur disponible, une barre de défilement
apparaît — **à 19 pixels du bord droit**, flottant au milieu de rien. Elle se lit comme un élément
mal posé, et rien à l'écran n'explique pourquoi elle s'arrête là.

**Pourquoi elle ne peut pas aller plus loin.** La marge intérieure de la console est posée sur un
**cadre qui contient** la zone défilante, en même temps que l'avancement et le décompte. La zone
défilante hérite donc de ce retrait des deux côtés, et sa barre avec elle. Mesuré à l'écran :

```
console          left 9    right 324   (314 px de large)
zone défilante   left 28   right 305
écart            19 px à gauche · 19 px à droite
marge du cadre   19,2 px
```

**Le modèle existe déjà dans le même fichier.** Le pied « Ma sélection » est **hors** de ce cadre, et
il touche les bords — avec son commentaire qui dit pourquoi : « une marge sous lui le transformerait
en bouton posé dans un cadre ». C'est exactement le raisonnement qui manque à la zone défilante.

## Périmètre

- **La marge horizontale quitte le cadre commun** et redescend sur chacun de ses trois enfants :
  l'avancement, le contenu **à l'intérieur** de la zone défilante, et le décompte du bas.
- **La marge verticale ne bouge pas** : elle n'empêche aucune barre d'atteindre quoi que ce soit.
- **La zone défilante réserve la place de sa barre en permanence**, qu'elle soit affichée ou non.
- **Le commentaire du fichier est réécrit** : il annonce aujourd'hui l'inverse de ce qui sera fait.

## Hors périmètre

- **La colonne du verdict**, à droite, qui défile aussi. Elle pose un problème différent : ses
  étiquettes débordent volontairement de leur cadre pour respirer, et le défaire demande de vérifier
  leur allure. **Non ouvert.**
- **La pile de profils** au milieu, qui défile également et dont la barre n'a pas été signalée.
- **L'allure de la barre** — épaisseur, teinte, discrétion au repos. C'est un sujet de charte, qui
  vaudrait pour tout le produit et pas pour cette colonne. **Non ouvert.**
- **Le trait du décompte**, qui garde son retrait actuel : il souligne un bloc, il ne coupe pas la
  colonne.
- **Le pied « Ma sélection »**, déjà collé aux bords — il sert de modèle, il ne change pas.
- **Toute retouche serveur ou de données** : rien ici ne sort du gabarit.

## Décisions de cadrage

**La correction porte sur la console de gauche seule.** Appliquer la même règle à la colonne du
verdict aurait été plus cohérent d'un bord à l'autre de l'écran — écarté pour deux raisons : c'est
la console qui a été vue et mesurée, et la colonne du verdict utilise un débordement volontaire
(marge négative compensée par un retrait) pour que ses étiquettes ne soient pas rognées au survol.
Le défaire sans casser leur allure est un travail à part.

**La place de la barre est réservée en permanence.** Sur la machine où la mesure a été prise, la
barre flotte par-dessus le contenu et ne prend aucune largeur. Ce n'est pas le cas partout : sur
beaucoup de configurations elle occupe une gouttière de quinze à dix-sept pixels. Sans réservation,
le texte sauterait latéralement au moment où la barre apparaît, et viendrait se coller contre elle
là où elle est épaisse. Écarté : ne rien réserver, qui gardait un diff plus petit au prix d'un
comportement différent d'une machine à l'autre.

**Le texte ne bouge pas d'un pixel** — ~~des deux côtés~~ **à gauche seulement**. Corrigé après
mesure : la gouttière réservée prend sa largeur au contenu, si bien que le retrait passe de 19 à
34 pixels **à droite** là où la barre est épaisse. C'est le prix de la réservation, pas un défaut
d'exécution, et il ne se paie pas là où la barre flotte. Les deux façons d'y échapper ont été pesées
à l'écran et écartées : ne plus réserver rend le saut de quinze pixels à chaque déroulé de volet ;
retirer le retrait droit du contenu ferait toucher le texte au bord partout où la barre flotte.

**L'allure de la barre reste celle du système.** L'habiller — barre fine, teintée sur les jetons —
aurait été plus abouti, mais c'est une règle de charte à tenir dans les deux thèmes, et elle
déborderait de cette colonne : les autres zones qui défilent garderaient l'ancienne, ce qui produit
exactement l'incohérence qu'on cherche à éviter.

**Aucun test, et la raison est écrite.** La tâche ne produit pas de logique. Ouvrir la voie aux tests
de composant dans le dépôt — monter l'écran pour vérifier une largeur — est une décision d'outillage
qui dépasse une correction de marges. Écarté explicitement, pas oublié.

## Règles et cas limites

- **La marge intérieure se lit sur les jetons du socle**, jamais écrite à la main : c'est le même
  jeton qui se déplace, pas une nouvelle valeur. Le design system l'interdit et aucune gate ne le
  verrait.
- **La réservation de gouttière passe par du CSS local**, faute d'utilitaire dans le kit — même
  motif que le bornage de bulle d'`UiInfoHint`, et aucune **valeur** n'y est écrite.
- **L'avancement et le décompte gardent leur position exacte.** Ils portent désormais leur retrait
  eux-mêmes ; à l'écran, rien n'a bougé pour eux — ils ne sont pas dans la zone défilante et ne
  paient donc pas la gouttière.
- **Sans défilement, rien ne change visuellement** — sauf la gouttière réservée, invisible là où la
  barre flotte.
- **Le décompte est conditionnel** : il n'apparaît qu'une fois un affinage posé. Sa marge doit le
  suivre, et non rester sur un bloc qui disparaît.
- **Colonne étroite** : la console fait dans les 280 à 320 pixels. Le retrait qui descend sur les
  enfants est celui qui existait ; la largeur utile du texte ne perd que la gouttière réservée, là
  où la barre en occupe une.
- **Aucun texte utilisateur touché**, donc aucune clé de traduction.

## Documentation impactée

- **Aucun déclencheur.** Ni contrat d'API, ni modèle de données, ni convention de dossier, ni jeton
  de design : la tâche déplace une marge existante à l'intérieur d'un seul gabarit.
- **`webapp/base/app/components/README.md`** : à relire, corrigé seulement si la responsabilité de la
  marge intérieure y est énoncée — a priori non, elle est écrite dans le commentaire du fichier.
- Le **commentaire de `ControlPanel.vue`** part dans le même commit, mais c'est du code.

## Vérification

Stack du worktree entreprise, navigateur piloté, connecté en `owner@demo.showcase-recruiter-webapp-saas.example`, recherche à
neuf critères, **deux volets dépliés** pour forcer le défilement.

**La mesure, avant et après** — bords de la console contre bords de la zone défilante :

```
                        avant        après
écart bord / zone       19 px    →    0 px
texte, côté gauche      19 px    →   19 px
texte, côté droit       19 px    →   34 px   (19 de retrait + 15 de gouttière)
gouttière réservée       —           15 px, affichée ou non
débordement             oui          oui
```

La zone défilante va de `left 9` à `right 324`, soit exactement les bords de la console — elle en
occupe toute la largeur.

**Ce qui n'a pas bougé**, vérifié à l'écran : l'avancement en haut, les volets, le décompte et son
trait en retrait, le pied « Ma sélection » en aplat plein collé aux bords.

**Tests** : aucun, cf. la ligne `**Tests**` — la tâche ne produit pas de logique. La preuve est la
mesure ci-dessus.

## Livré

- `webapp/spaces/entreprise/app/components/search/ControlPanel.vue` **(M)** — la marge latérale
  quitte le cadre commun pour l'avancement, le contenu de la zone défilante et le décompte ; la
  marge verticale reste sur le cadre ; la zone défilante réserve sa gouttière en CSS local ; les
  commentaires disent la nouvelle répartition.

## Reste à faire (hors périmètre)

- **L'asymétrie du retrait** — 19 pixels à gauche, 34 à droite sur les machines à barre épaisse.
  Présentée à l'écran avec ses deux alternatives, laissée telle quelle. Rouvrir demande de
  choisir entre le saut au déroulé et le texte collé au bord.
- **La colonne du verdict** et **la pile de profils**, qui défilent aussi et gardent leur barre en
  retrait. Non ouvert.
- **`webapp/base/app/components/README.md`** : relu, la responsabilité de la marge intérieure n'y est
  pas énoncée — rien à corriger. Vérifié.

## Définition de terminé

- [x] La barre de défilement de la console touche son bord droit — écart mesuré à 0.
- [x] L'avancement, les filtres et le décompte gardent leur position — au retrait droit près, qui
      gagne la gouttière réservée, cf. *Décisions de cadrage*.
- [x] La place de la barre est réservée même quand elle ne s'affiche pas : rien ne saute
      latéralement à son apparition.
- [x] Le trait du décompte garde son retrait.
- [x] Le pied « Ma sélection » est inchangé.
- [x] Le commentaire qui décrivait l'ancienne répartition dit la nouvelle.
- [x] La mesure avant / après figure dans la carte.
- [x] La carte est clôturée et le commit ne porte que cette tâche.
