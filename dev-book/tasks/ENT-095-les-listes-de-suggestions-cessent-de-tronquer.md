# TASK ENT-095 — entreprise : les listes de suggestions cessent de tronquer, et le panneau des mots-clés cesse d'être découpé

- **Créée le** : 2026-08-12.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-12 — les listes déroulantes s'élargissent à leur contenu, les
  suggestions de mots-clés passent à la ligne au lieu d'être coupées, et le panneau latéral s'ouvre
  en entier au lieu d'être rogné par sa carte.
- **Type** : Frontend entreprise — correction de deux défauts d'affichage, dont un introduit la
  veille. Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Ce qui change est une largeur, un
  débordement et un retour à la ligne. Le webapp n'a aucun harnais de test de composant
  (`webapp/vitest.config.ts` ne couvre que le code pur, sans DOM), et jsdom ne mesure pas une
  largeur. **La preuve attendue est un relevé dans la page** — largeur de la liste face à celle du
  champ, présence de troncature, dimensions du panneau — complété de captures.
- **Réf** : **`ENT-084` — amont, et corrigée ici** (elle a posé le `overflow-hidden` qui découpe le
  panneau) · **`ENT-085` — amont** (elle a rendu son étirement au champ « poste », ce qui a ramené la
  troncature que cette tâche règle autrement) ·
  `webapp/base/app/components/ui/{RemoteCombobox,InlineSelect,TagPicker}.vue` ·
  `webapp/spaces/entreprise/app/components/search/Sentence.vue`

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur la page « Décrivez le profil que
vous cherchez ».

**Deux défauts, et le second est de notre fait.**

1. **Les listes déroulantes coupent ce qu'elles sont censées faire lire.** Taper « se » dans le champ
   « poste » propose « Développeur l… », « Commercial te… ». La liste est clouée à la largeur de son
   champ, et ce champ est étroit depuis qu'`ENT-085` lui a retiré son étirement pour permettre le
   centrage de la phrase. Cette tâche-là avait nommé la contrepartie et le remède : **élargir le
   panneau indépendamment du champ**. C'est ce que celle-ci fait.

2. **Le panneau des mots-clés ne s'ouvre plus.** Cliquer sur « Ajouter un mot-clé » n'affiche qu'un
   liseré collé au bord gauche de la carte. La cause est un réglage posé la veille par `ENT-084` :
   pour que le pied de carte ait des coins nets, la carte masquait ce qui déborde d'elle. Or le
   panneau s'ouvre **à côté** de la carte, donc en dehors — il se faisait découper.

## Périmètre

- **Les listes du champ « poste » et des deux sélecteurs de la phrase s'élargissent à leur contenu** :
  jamais plus étroites que leur champ, aussi larges qu'il faut pour lire un intitulé entier, avec un
  plafond.
- **Les suggestions de mots-clés passent à la ligne** au lieu d'être coupées.
- **La carte des mots-clés cesse de masquer ce qui déborde d'elle**, et le pied arrondit lui-même ses
  deux coins bas.
- **Le panneau des mots-clés retrouve la hauteur de sa colonne**, perdue au passage en deux cartes.

## Hors périmètre

- **La largeur du panneau des mots-clés**, qui reste fixe : la faire suivre son contenu le ferait
  sauter à chaque frappe, puisqu'il s'agit d'une feuille latérale et non d'un menu.
- **Les listes des autres écrans** : la correction vit dans le kit, donc elle leur profite, mais
  aucune n'a été retouchée ni vérifiée ici.
- **Le placement vertical des listes** (au-dessus ou en dessous du champ), qui ne change pas.

## Décisions de cadrage

**La liste part du bord de lecture du champ et grandit vers l'extérieur.** Elle garde un plancher —
la largeur du champ — pour ne jamais paraître décrochée de lui, et un plafond pour qu'un libellé
aberrant ne traverse pas l'écran. Le bord d'ancrage est logique, donc l'arabe l'inverse sans rien
ajouter.

**Le pied arrondit ses coins plutôt que la carte ne les rogne.** `ENT-084` avait choisi l'inverse,
au motif juste que le rayon n'est alors écrit qu'une fois. Ce choix devient intenable dès qu'un
enfant doit sortir de la carte : masquer le débordement coupe aussi ce qui doit dépasser. Le rayon
est donc répété sur le pied — un doublon assumé, contre un panneau qui ne s'ouvre pas.

**Les suggestions de mots-clés se replient sur deux lignes plutôt que d'être coupées.** Une liste de
suggestions existe pour être lue ; « système de contrôle et d'acquisition de donnée… » ne renseigne
sur rien. Le panneau garde sa largeur fixe, seule la hauteur d'une ligne varie.

**Le sélecteur de mots-clés remplit de nouveau sa colonne.** Le passage en deux cartes lui avait
retiré ce qui l'étirait, si bien que le panneau latéral — calé sur lui — ne faisait plus que la
hauteur d'un bouton. La règle posée plus tôt tient toujours : le panneau prend sa taille définitive
à l'ouverture, il ne grandit pas au fil des résultats.

## Règles et cas limites

- **Une liste ne descend jamais sous la largeur de son champ** : plus étroite, elle paraîtrait
  détachée de lui.
- **Le plafond de largeur est relatif à la fenêtre autant qu'absolu** : sur un petit écran, la liste
  ne dépasse pas neuf dixièmes de la largeur visible.
- **Le repli d'une suggestion sur deux lignes ne casse pas l'alignement de sa pastille**, qui reste
  en tête de ligne.
- **Le panneau latéral n'est plus rogné**, mais il chevauche toujours la marge intérieure de sa
  carte : c'est voulu, il sort par la gauche.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni règle métier ne change. Aucun `README.md`
ne change de convention — le kit gagne un comportement, pas une règle.

## Vérification

Relevés pris le 2026-08-12 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`, à 1440 px.

| Ce qui est prouvé | Relevé |
| --- | --- |
| La liste du poste dépasse son champ | champ **161 px**, liste **250 px** |
| Plus aucune troncature dans cette liste | « Développeur logiciel », « Commercial terrain / Ventes » entiers ; aucun élément dont le contenu dépasse sa boîte |
| Le panneau des mots-clés s'ouvre en entier | **384 × 626 px**, visible, ses suggestions listées |
| Il s'ouvre bien à côté de la carte | panneau à `x=709`, carte à `x=1078` |
| Avant correction | le même panneau mesurait **68 px** de haut et n'apparaissait qu'en liseré |

**Captures** : `preuve-panneau-poste.png`, `preuve-panneau-motscles.png`, dans le dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

Aucune gate qualité n'a été jouée : elles appartiennent à l'ouverture de la PR.

## Livré

- `webapp/base/app/components/ui/RemoteCombobox.vue` — la liste part du bord de lecture, ne descend
  pas sous la largeur du champ, s'étend à son contenu, plafonnée.
- `webapp/base/app/components/ui/InlineSelect.vue` — même correction, même règle.
- `webapp/base/app/components/ui/TagPicker.vue` — les suggestions se replient au lieu d'être coupées.
- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — la carte des mots-clés ne masque
  plus ce qui déborde d'elle, et son sélecteur remplit de nouveau la colonne.
- `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` — le pied arrondit ses deux coins bas.

## Reste à faire (hors périmètre)

- **La correction de largeur profite à tous les écrans qui emploient ces deux composants** sans
  qu'aucun ait été revérifié. Si une liste y devenait trop large, le plafond est le seul réglage à
  revoir.

## Définition de terminé

- [x] La liste du champ « poste » est plus large que son champ et n'y coupe plus aucun intitulé.
- [x] Les deux sélecteurs de la phrase suivent la même règle.
- [x] Les suggestions de mots-clés se replient sur deux lignes plutôt que d'être coupées.
- [x] Cliquer sur « Ajouter un mot-clé » ouvre le panneau en entier, à côté de la carte.
- [x] Le panneau occupe la hauteur de sa colonne dès l'ouverture.
- [x] Le pied de la carte garde ses coins bas arrondis sans que la carte masque son débordement.
- [x] Relevés et captures fournis.
