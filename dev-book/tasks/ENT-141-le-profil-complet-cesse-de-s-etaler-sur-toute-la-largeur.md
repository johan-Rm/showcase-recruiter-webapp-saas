# TASK ENT-141 — le profil complet cesse de s'étaler sur toute la largeur

- **Créée le** : 2026-08-18.
- **Session** : `c6e9274a-26a7-4733-86f6-42a6f9f95f36`.
- **Statut** : ✅ fait le 2026-08-19 — le contenu du tiroir est borné aux trois quarts et centré à
  partir de 1024 px, la feuille garde toute sa largeur, et le jeton orphelin de la feuille latérale
  a disparu.
- **Type** : fix — un contenu qui prend toute la largeur parce que personne n'a décidé le contraire,
  et non parce que quelqu'un l'a voulu.
- **Tests** : `sans objet` — la tâche ne produit **aucune logique** : elle borne une largeur et retire
  un jeton mort. **La preuve attendue est la mesure** : la largeur du contenu du tiroir relevée à
  l'écran sur trois formats, avant et après, plus une capture.
- **Réf** : `webapp/spaces/entreprise/app/components/ProfilePanel.vue` ·
  `webapp/base/app/assets/css/sizing.css` (le jeton orphelin `--container-sheet`) ·
  `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (les familles de largeurs) ·
  **amont** `ENT-112` (le tiroir par le bas, qui a remplacé la feuille latérale) ·
  `ENT-121` (les deux colonnes et leur seuil).

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, ouvre le **profil complet** d'un candidat
— depuis la fiche de l'écran de parcours, ou depuis la Table. Une feuille monte du bas et recouvre
l'écran de travail : à gauche ce qu'on sait de la personne, à droite son parcours daté.

**Ce qu'on voit.** Le contenu s'étale d'un bord à l'autre de l'écran. Mesuré sur une fenêtre de
1920 px : la colonne de gauche court jusqu'à 790 px, la colonne de droite commence à 815 — mais son
contenu réel, la frise des expériences, s'arrête vers 1270. **Il reste environ 600 px de vide à
droite**, et rien ne cadre la lecture : l'œil traverse tout l'écran pour passer d'une colonne à
l'autre.

**Pourquoi c'est ainsi.** Personne ne l'a décidé. `ENT-112` a choisi la feuille qui monte du bas pour
sa **mécanique** — page inerte, focus piégé, `Échap`, poignée et glissement au doigt, tout cela
fourni par le kit. `ENT-121` a arbitré les **deux colonnes** à l'intérieur. La largeur, elle, est
restée le défaut du composant.

**Une règle produit avait pourtant existé.** Le jeton `--container-sheet`, « au moins 42 % de
l'écran », a été écrit pour la feuille **latérale** du profil complet — celle que `ENT-112` a
remplacée. Il n'est consommé nulle part aujourd'hui.

## Périmètre

- **Le contenu de la feuille est borné à 75 % de la largeur et centré**, avec l'utilitaire natif de
  Tailwind (`max-w-<fraction>`), sans valeur écrite à la main ni jeton nouveau.
- **À partir de 1024 px seulement** — le seuil où les deux colonnes existent déjà. En dessous, le
  contenu reprend toute la largeur.
- **L'en-tête suit le même bornage que le corps** : le titre du profil et la croix de fermeture
  s'alignent sur le contenu qu'ils coiffent.
- **Le jeton `--container-sheet` est retiré** de `sizing.css`, avec son commentaire.
- **La charte gagne sa ligne** sur la famille de largeurs concernée.
- **Le commentaire du tiroir porte le motif** du bornage.

## Hors périmètre

- **La feuille elle-même**, qui garde toute la largeur, ses coins, sa poignée et son glissement au
  doigt. C'est ce qui la distingue d'une fenêtre modale posée au milieu de l'écran.
- **Le partage entre les deux colonnes**, arbitré par `ENT-121`.
- **La hauteur du tiroir**, fixée pour que le geste reste prévisible d'un profil à l'autre.
- **Le contenu du profil** : aucun bloc ajouté, retiré ni réordonné.
- **Le retour à une feuille latérale**, que `ENT-112` a explicitement écartée — « le tiroir remplace
  la feuille latérale, il ne s'ajoute pas ». **Non ouvert.**

## Décisions de cadrage

- **On borne le CONTENU, pas la feuille.** Écarté : rétrécir la feuille elle-même pour laisser voir
  le fond de part et d'autre. Une feuille qui monte du bas sans toucher les bords se lit comme une
  fenêtre modale, et la poignée de glissement perd son sens — or c'est précisément cette mécanique
  qui avait fait choisir ce composant.
- **Écarté aussi : revenir à une feuille latérale.** Le jeton « au moins 42 % » existe encore pour
  ça, mais rouvrir `ENT-112` obligerait à réécrire à la main ce que le kit donne gratuitement en bas.
- **Un utilitaire de fraction, ni jeton ni valeur inventée.** Tailwind fournit `max-w-<fraction>` en
  natif. Un jeton nommé aurait été l'usage du dépôt pour une largeur de gabarit, mais il n'y a ici
  ni valeur à motiver ni famille à créer : une fraction se lit toute seule.
- **⚠️ La cible était « environ 1400 px », le pourcentage donne 1440 à 1920.** Les deux se
  rejoignent sur l'écran d'où vient la demande. Ailleurs ils divergent, et c'est le pourcentage qui
  gouverne : 960 px à 1280, 768 px à 1024.
- **Le bornage s'arrête à 1024 px, et ce plancher n'était pas dans la demande.** Appliqué vraiment
  partout, il aurait laissé **292 px de contenu utile sur un téléphone de 390** — le vide qu'on
  chasse sur grand écran, inventé sur petit. Le seuil retenu est celui qui existe déjà, pas un
  nouveau.
- **Le jeton orphelin part dans le même commit.** Il décrit une règle produit qui n'existe plus et un
  dispositif abandonné ; le laisser, c'est laisser la prochaine session le prendre pour la largeur du
  profil.

## Règles et cas limites

- **Sous 1024 px, rien ne change** : le contenu occupe toute la largeur disponible, comme
  aujourd'hui.
- **À 1024 px exactement**, le contenu tombe à 768 px pour deux colonnes — c'est le format le plus
  serré où le bornage mord encore. À juger sur capture.
- **La poignée, les coins et le glissement au doigt ne bougent pas** : ils appartiennent à la feuille,
  pas à son contenu.
- **Les états de chargement et d'erreur** du tiroir suivent le même bornage que le contenu : un
  message centré sur une largeur, une fiche centrée sur une autre, et le tiroir se mettrait à sauter.
- **La barre de défilement du corps reste au bord de la feuille**, pas au bord du contenu borné.
- **Aucun texte nouveau**, aucune clé i18n touchée.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` — la section des familles de largeurs, qui gagne
  ce que borne le profil complet. Même commit.
- `webapp/base/app/assets/css/sizing.css` — le retrait de `--container-sheet` et de son commentaire.
- `webapp/spaces/entreprise/app/components/ProfilePanel.vue` — le commentaire d'en-tête, qui ne dit
  rien de la largeur aujourd'hui.

## Définition de terminé

- [x] À 1920 px, le contenu du tiroir occupe 1440 px centrés au lieu de toute la largeur — mesuré à
      l'écran, avant et après.
- [x] À 1280 px, il en occupe 960 ; à 1024 px, 768.
- [x] Sur téléphone (390 px), la largeur du contenu est inchangée.
- [x] Le titre du profil et la croix de fermeture s'alignent sur le contenu.
- [~] La poignée, le glissement au doigt et la fermeture par `Échap` fonctionnent comme avant —
      **`Échap` est vérifié** (c'est lui qui refermait le tiroir entre chaque format mesuré) et la
      poignée est visible en capture ; **le glissement au doigt n'a pas été rejoué**. Il appartient
      au kit, que ce changement ne touche pas.
- [x] `--container-sheet` n'existe plus, et aucune référence ne subsiste.
- [x] `design-system.md` et le commentaire du tiroir disent ce qui est vrai.

## Vérification

Navigateur réel piloté, session authentifiée en `entreprise@showcase-recruiter-webapp-saas.example`, thème sombre. Le tiroir
est ouvert depuis la fiche d'un candidat, sur quatre formats.

### La largeur du contenu

```
                feuille   contenu   marge de chaque côté
1920 × 1080      1920      1440       240 px
1280 ×  900      1280       960       160 px
1024 ×  900      1024       768       128 px
 390 ×  780       390       390         0 px   (inchangé, sous le seuil)
```

Les trois premières valeurs sont exactement celles qu'annonçait le cadrage. **La feuille, elle,
touche toujours les deux bords** — 1920 px de large sur un écran de 1920 — et garde sa poignée
centrée : c'est le contenu qui est borné, pas elle.

### L'en-tête suit le corps

```
1920 px   contenu à x=240   titre à x=256   (l'écart est la marge intérieure, pas un flottement)
1280 px   contenu à x=160   titre à x=176
1024 px   contenu à x=128   titre à x=144
```

### Le jeton orphelin

`--container-sheet` — « au moins 42 % de l'écran », écrit pour la feuille latérale qu'`ENT-112` a
remplacée — est retiré de `sizing.css`. Recherche dans tout `webapp/` : **plus aucune référence**,
ni en CSS, ni en gabarit, ni en script.

### À l'œil

Capture à 1920 : les deux colonnes se lisent comme un bloc centré au lieu de partir aux extrémités,
et la croix de fermeture s'aligne sur le bord droit du contenu.

## Livré

- `webapp/spaces/entreprise/app/components/ProfilePanel.vue` (M) — le conteneur du tiroir est borné
  aux trois quarts et centré à partir du seuil des deux colonnes ; l'en-tête suit, puisque le
  bornage porte sur le conteneur qui l'inclut. Le commentaire d'en-tête porte le motif.
- `webapp/base/app/assets/css/sizing.css` (M) — `--container-sheet` retiré, avec son commentaire.
- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` (M) — la règle : une surface qui recouvre l'écran
  borne son contenu et non elle-même, en fraction et non en largeur de gabarit, à partir du seuil
  des deux colonnes.

## Reste à faire (hors périmètre)

- **À 1024 px, le contenu tombe à 768 px pour deux colonnes.** C'est le format le plus serré où le
  bornage mord encore ; il se juge à l'œil, et le seuil devra monter si les colonnes y deviennent
  illisibles. **Non ouvert.**
- **Le glissement au doigt n'a pas été rejoué** — comportement du kit, non touché par ce changement.
