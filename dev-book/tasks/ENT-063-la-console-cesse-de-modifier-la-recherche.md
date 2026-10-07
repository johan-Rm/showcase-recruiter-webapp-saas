# TASK ENT-063 — entreprise : la console cesse de modifier la recherche

- **Créée le** : 2026-08-06.
- **Session** : `82b13a37-3b91-47f4-8c01-fbf60e5f6b62`.
- **Statut** : ✅ fait le 2026-08-06 — le bouton de suppression et tout son câblage ont quitté la
  console, qui redevient un outil de consultation. Mesuré à l'écran : 10 lignes de critères, 0
  bouton.
- **Type** : fix — un geste d'édition posé au milieu d'un écran de lecture.
- **Tests** : sans objet — le changement retire un élément d'interface et son câblage, sans logique
  ni fonction pure à couvrir. Le dépôt ne pratique aucun test de composant côté front : en monter un
  harnais pour une suppression serait un chantier bien plus lourd que la tâche, et il appartiendrait
  au socle. La preuve est une **capture d'écran** de la liste des critères, avant et après.
- **Réf** : `ENT-041` (le ✕ y était « le seul endroit du produit où l'on retire un critère d'un
  clic ») · `ENT-047` (qui l'a déplacé dans la liste des critères) · `ENT-062` (l'ordre, à faire
  d'abord) · `MatchList.vue` · `ControlPanel.vue` · `Console.vue` · `pages/recherches/[uuid]/index.vue`

## Contexte

La Visionneuse est l'écran où l'on **juge des profils** : on parcourt une pile, on retient, on
écarte. Le panneau « Ce profil face à vos critères » y est là pour expliquer pourquoi un profil
remonte — pas pour retoucher la recherche.

Il porte pourtant, sur chaque ligne, un bouton qui **retire le critère et relance la recherche**. Le
jeu de résultats change donc sous les yeux de quelqu'un qui était en train de trier des gens, depuis
un écran qu'il consultait.

S'y ajoute une confusion de lecture, et c'est elle qui a fait remonter le sujet : **chaque ligne
porte deux croix de sens opposés**. À gauche, une croix rouge dit « critère non trouvé sur ce
profil » ; à droite, une croix grise supprime le critère. Deux fois le même dessin, à quelques
centimètres, pour un constat d'un côté et une action irréversible de l'autre.

## Périmètre

- **Le bouton de suppression quitte la liste des critères**, ainsi que l'événement qu'il émet et le
  câblage qui remonte jusqu'à la page.
- **La croix du verdict reste seule** sur la ligne : plus d'ambiguïté possible.
- **Les propriétés devenues inutiles disparaissent** — celle qui disait si la recherche est
  modifiable, celle qui bloquait pendant un ajustement — partout où elles ne servaient qu'à ce
  bouton.

## Hors périmètre

- **Rebrancher l'ajustement sur la barre haute.** Le geste doit revenir là où l'on ajuste — c'est
  ce que décrit déjà `arborescence-entreprise.md` —, mais ce branchement n'appartient pas à cette
  tâche. Cf. *Écart au cadrage* : entre l'écriture de cette carte et son implémentation, les filtres
  secondaires ont cessé d'être accessibles, et le trou est assumé.
- **La clé de traduction « Retirer le critère »** reste dans les trois fichiers de langue, devenue
  inutilisée — cf. *Décisions de cadrage*.
- **L'ordre d'affichage des critères** : c'est `ENT-062`.
- **Le signe du verdict lui-même** — la croix rouge —, qui ne change pas : une fois seule sur la
  ligne, elle ne se confond plus avec rien.

## Décisions de cadrage

- **Retirer plutôt que renommer.** Remplacer le ✕ par un libellé « Retirer » aurait levé la
  confusion en gardant la fonction ; c'est la fonction elle-même qui n'a pas sa place ici. La
  console est un outil de consultation et d'aide à la décision — retenir ou écarter —, pas
  d'édition.
- **Aucun renvoi vers les filtres à la place.** Un lien « ajuster les critères » réintroduirait une
  action dans un écran dont on veut qu'il n'en porte aucune sur la recherche.
- **Cette tâche inverse `ENT-041` et `ENT-047`, et l'assume.** `ENT-041` décrivait ce bouton comme
  le seul endroit du produit où retirer un critère d'un clic ; `ENT-047` l'a déplacé ici « là où on
  lit ce qu'on défait ». Le raisonnement se tenait, la conclusion non : l'écran de lecture n'est pas
  le bon hôte. **Écrit noir sur blanc pour que personne ne le remette dans six mois en croyant
  réparer une régression.**
- **La clé de traduction reste en place.** La supprimer imposerait d'éditer les trois fichiers de
  langue, actuellement modifiés par une autre session dans le même dossier de travail. Une clé
  inutilisée ne s'affiche nulle part et ne casse rien ; le ménage se fera quand ces fichiers seront
  libres.
- **ÉCART AU CADRAGE, tranché en cours d'implémentation.** Cette carte affirmait que retirer un
  critère resterait possible par les filtres secondaires. **C'est devenu faux entre son écriture et
  son implémentation** : `ENT-061`, commitée entre-temps, a débranché les deux formulaires — « les
  deux formulaires n'ont plus rien qui les ouvre, le temps que les boutons de la barre haute soient
  branchés », dit son propre commentaire. Le ✕ était donc redevenu le **dernier** geste d'ajustement
  depuis cet écran.
  Le retrait a été maintenu : la console n'a pas à porter ce geste, et l'y garder par défaut aurait
  installé durablement un compromis que personne n'a choisi. Mais le trou est réel — **ajuster une
  recherche demande désormais de repasser par l'accueil**, jusqu'à ce que la barre haute rouvre les
  filtres. C'est écrit dans le code, à l'endroit exact où le geste vivait.

## Règles et cas limites

- **La recherche d'un collègue** était déjà lue sans ce bouton — le panneau le masquait en lecture
  seule. Après cette tâche, les deux cas se rejoignent : plus personne ne peut éditer depuis là.
- **Un ajustement en cours** ne verrouille plus rien dans ce panneau, puisqu'il n'y a plus d'action
  à verrouiller.
- **La liste garde tout le reste** : le libellé du critère, le verdict, et ce que le profil porte en
  face. Seule la colonne d'action disparaît.

## Documentation impactée

**Aucune, vérifié.** `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` situe déjà
les critères et les filtres d'affinage dans la **barre haute** de la Visionneuse, jamais dans la
console : cette tâche va dans son sens plutôt que de la contredire. La page décrit une cible que le
code n'atteint pas encore — c'est une dette de produit, pas une doc rendue fausse par ce diff.

## Définition de terminé

- [x] Aucun bouton de suppression n'apparaît dans « Ce profil face à vos critères ».
- [x] Une seule croix subsiste par ligne, celle du verdict.
- [x] L'événement de retrait et son câblage jusqu'à la page ont disparu.
- [x] Les propriétés qui ne servaient qu'à ce bouton ont disparu.
- [x] ~~Retirer un critère reste possible par les filtres secondaires~~ — **critère caduc**,
      cf. *Écart au cadrage* : les filtres ne sont plus accessibles depuis `ENT-061`.
- [x] Une capture d'écran est jointe à la vérification de la carte.

## Vérification

**À l'écran**, sur une recherche à dix critères de la stack entreprise, panneau « Ce profil face à
vos critères » :

| | Avant | Après |
| --- | --- | --- |
| Lignes de critères | 10 | **10** |
| Boutons de suppression | 10 | **0** |
| Croix par ligne | 2 (verdict + action) | **1** (verdict seul) |

```
CE PROFIL FACE À VOS CRITÈRES
✓ Poste  Développeur logiciel     poste actuel
✓ Expérience  5 ans et plus              12 ans
✓ java              Senior software an…
✗ nestjs                      Non trouvé
```

Le compte des boutons est **mesuré dans le DOM**, pas lu sur l'image : la capture pourrait masquer
un bouton transparent, le comptage non.

**Aucune régression d'affichage** : la console se monte, les compteurs de parcours s'affichent, et
la liste conserve ses dix lignes avec leur verdict et ce que le profil porte en face.

**Le trou est nommé dans le code**, à l'endroit où le geste vivait : un commentaire de la page dit
que les deux formulaires de filtres n'ont rien qui les ouvre depuis `ENT-061`, et que tant que la
barre haute n'est pas branchée, ajuster une recherche demande de repasser par l'accueil.

**Tests** : aucun, conformément à l'exemption de la ligne `**Tests**` — le dépôt ne pratique aucun
test de composant côté front.

## Livré

- `webapp/spaces/entreprise/app/components/search/MatchList.vue` **(M)** — le bouton, l'événement,
  les deux propriétés qui ne servaient qu'à lui ; le docblock dit désormais que la liste ne retouche
  rien, et pourquoi.
- `webapp/spaces/entreprise/app/components/search/ControlPanel.vue` **(M)** — le relais retiré.
- `webapp/spaces/entreprise/app/components/search/Console.vue` **(M)** — le relais retiré, et son
  docblock qui n'annonce plus qu'une intention au lieu de deux.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` **(M)** — la fonction de retrait,
  l'état d'ajustement et l'appel au service, devenus sans appelant ; le trou documenté à leur place.

## Reste à faire (hors périmètre)

- **Brancher l'ajustement sur la barre haute** — dépendance directe de cette tâche. `arborescence-entreprise.md`
  situe déjà les critères et les filtres d'affinage dans la barre haute de la Visionneuse : la
  documentation décrit la cible, le code ne l'atteint pas encore. Tant que ce n'est pas fait,
  ajuster une recherche demande de repasser par l'accueil.
- **La clé de traduction « Retirer le critère »** reste dans les trois fichiers de langue, sans
  usage.
- **`withoutCriterion`** n'a plus d'appelant applicatif — la fonction reste exportée et testée, elle
  resservira au branchement ci-dessus.
