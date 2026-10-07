# TASK ENT-067 — entreprise : créer une recherche a son écran, et la barre haute cesse d'appartenir aux pages

- **Créée le** : 2026-08-06.
- **Session** : `d6c47bca-7043-42fd-b4d0-53837807b7c2`.
- **Statut** : ✅ fait le 2026-08-06 — créer une recherche a son écran, `/recherches/nouvelle` ; la
  gouttière et la barre haute ont quitté les trois écrans de recherche pour une coque commune, si
  bien que le nom reste en place du moment où on l'écrit à celui où on parcourt ; et la zone de
  contenu glisse de la création vers la Visionneuse sans que la barre bouge d'un pixel.
- **Type** : Frontend entreprise — une route en plus, une coque partagée qui sort des pages, une
  transition. Aucun contrat d'API, aucune donnée, aucune migration.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Ce qui bouge est structurel : des bandes
  changent de propriétaire, une route s'ajoute, un mouvement s'anime. Le webapp n'a aucun harnais de
  test de composant (`webapp/vitest.config.ts` ne couvre que le code pur, sans DOM), et jsdom
  n'anime rien. La preuve attendue est une **capture de chacun des trois écrans connectés**, plus
  une **vérification du mouvement** : la barre haute ne bouge pas pendant le glissement.
- **Réf** : **`ENT-066` — amont** (la carte dont le nom et le bouton sortent ici) ·
  **`ENT-061` — amont** (elle a rétabli la barre haute de la Visionneuse, que cette carte déménage)
  · `ENT-041`, `ENT-057` (l'histoire des bandes de la Visionneuse) ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` ·
  `webapp/base/app/layouts/README.md` · `webapp/spaces/entreprise/app/pages/README.md` ·
  `webapp/spaces/entreprise/app/pages/{index.vue,recherches/[uuid]/index.vue,recherches/[uuid]/table.vue}`
  · **aval : brancher les deux boutons de filtres** de la barre haute (ouverte par `ENT-061`, carte
  à écrire) · **aval : la recherche assistée**, qui pourra rejoindre la coque le jour où elle aura
  besoin d'une barre.

## Contexte

**Le moteur par critères n'a pas d'écran à lui.** Il s'ouvre *dans* l'accueil, à la place des cartes
de recherche, dans une section qui ne lui appartient pas. Il y est un invité : faute d'une bande où
les poser, le nom de la recherche et le bouton qui la crée ont dû tenir *dans la carte*, sous un
filet — alors que partout ailleurs dans l'espace, le nom d'une recherche et ses commandes vivent
dans la barre haute. Le recruteur qui passe de la création à la Visionneuse ne les retrouve donc pas
au même endroit.

**Et les trois écrans de recherche redessinent chacun les mêmes bandes.** Gouttière, barre haute,
zone de contenu : le gabarit est identique sur la création, la Visionneuse et la Table, mais chaque
page le reconstruit pour elle-même. Conséquence directe : passer d'un écran à l'autre démonte tout,
barre comprise. **Rien ne peut donc glisser** — un mouvement sur la seule zone de contenu suppose que
le reste ne soit pas démonté, et Nuxt ne garde monté d'une route à l'autre que ce qui vit dans un
**layout** (`webapp/base/app/layouts/README.md`).

Le manque se résume en une phrase : **on crée une recherche dans un écran qui n'est pas le sien, et
l'on ne peut pas relier ce moment au suivant.**

## Périmètre

- **Une route** `/recherches/nouvelle` : l'écran de création par critères, avec le gabarit des écrans
  de recherche.
- **Une coque partagée** — un layout — portant la gouttière et la barre haute pour les **trois**
  écrans : création, Visionneuse, Table. Les pages n'y gardent que leur zone de contenu.
- **Un canal** par lequel chaque écran remplit sa barre : ce qui s'affiche à gauche (le nom) et les
  commandes à droite diffèrent d'un écran à l'autre.
- **Le nom, modifiable au crayon sur les trois écrans**, l'enregistrement partant aussitôt sur une
  recherche déjà créée.
- **Le glissement** de la zone de contenu, de la création vers la Visionneuse.
- L'accueil : « Créer une recherche » navigue au lieu d'ouvrir le moteur sur place ; l'accueil perd
  le moteur par critères et tout ce qui le servait (comptage, critères en cours).
- La carte de recherche perd son pied : le nom, le décompte et le bouton montent dans la barre.

## Hors périmètre

- **La recherche assistée**, qui reste sur l'accueil, inchangée, avec son propre retour.
- **Les deux boutons de filtres** de la Visionneuse, toujours inertes : les brancher est une autre
  tâche, ouverte par `ENT-061`.
- **Le contenu** des trois zones : la carte de recherche, la pile de profils, la table. Seul leur
  contenant change.
- **Toute création côté serveur avant le clic** : aucune recherche vide n'est posée à l'ouverture.
- Le glissement entre la Visionneuse et la Table.

## Décisions de cadrage

- **`/recherches/nouvelle` plutôt qu'un `{uuid}` posé d'avance.** Une recherche vide n'existe pas
  côté serveur, et une adresse définitive obligerait à en créer une à l'ouverture — donc à nettoyer
  derrière chaque essai abandonné. L'adresse devient `/recherches/{uuid}` au moment où la recherche
  naît.
- **La coque est un layout, pas un composant.** C'est la seule enveloppe que Nuxt garde montée d'une
  route à l'autre ; un composant partagé serait remonté à chaque navigation et la barre bougerait
  quand même.
- **Les trois écrans y entrent d'emblée.** La Table n'était pas nécessaire au glissement demandé,
  mais la laisser dehors aurait figé deux modèles de page concurrents pour le même objet.
- **Le glissement n'anime que création → Visionneuse.** Le mouvement marque la naissance de la
  recherche ; l'aller-retour fréquent entre la Visionneuse et la Table reste net, pour ne pas
  imposer une animation à chaque bascule d'outil.
- **Le mouvement se coupe si le système demande moins d'animation.** Le dépôt le fait déjà ailleurs
  (l'effet de frappe du nom), et un réglage d'accessibilité ne se contourne pas pour un effet.
- **Le nom se corrige partout, avec le même geste.** Un seul nom, un seul crayon, où qu'on soit —
  plutôt que deux endroits pour la même chose.
- **Le nom devient visible sur la Table**, où il n'était jusqu'ici lisible que par les lecteurs
  d'écran : c'était le seul écran où l'on ne voyait pas sur quoi on travaillait.
- **Rechargement = ardoise nette.** Les critères en cours ne vivent que dans la page ; rien n'est
  créé côté serveur avant le clic, donc il n'y a rien à retrouver. Mémoriser la saisie ouvrirait la
  question de sa péremption, sans bénéfice à ce stade.
- **Un seul formulaire par écran de création, mais il n'englobe PAS la barre haute** — écart au
  cadrage, tranché à l'implémentation. Le `<Teleport>` sort le bouton du formulaire *dans le DOM*,
  et l'association native qu'un `type="submit"` suppose se perd avec lui. Le bouton déclenche donc
  le lancement au clic ; Entrée, elle, garde son effet depuis les champs de la carte, qui sont bien
  dans le formulaire.

## Règles et cas limites

- **Recherche partagée d'un collègue : pas de crayon.** Une recherche partagée est en lecture seule
  — son destinataire la duplique s'il veut la retoucher. La coque suit cette règle existante.
- **Sans critère, on ne crée rien** : le bouton reste fermé, et le message « le vivier entier n'est
  pas un résultat de recherche » garde son sens.
- **Une création qui échoue laisse l'écran en place** avec son message, et les critères saisis.
- **Le décompte est une aide, pas une garde** : il ne ferme jamais le bouton, et une panne de
  comptage n'empêche pas de lancer.
- **Sur téléphone**, la barre haute porte le nom et le bouton ; le décompte s'efface avant eux, et le
  nom se tronque avant le bouton — c'est le bouton qui doit rester entier.
- **Le repère principal du document reste unique** : la coque en porte exactement un, les pages
  aucun. Deux verrous d'intégration le vérifient (`layouts-single-main`, `pages-no-main`), chacun sur
  les fichiers qu'il peut juger.
- **Le titre de premier niveau vit dans la barre**, avant la zone de contenu, pour qu'on n'entre pas
  dans le document par un chapitre.
- **La Visionneuse s'ouvre déjà servie** : la création remplit les magasins avant de naviguer, donc
  l'écran d'arrivée n'a pas à recharger ce qu'il vient de recevoir — sans quoi le glissement
  amènerait un écran de chargement.

## Documentation impactée

- [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)
  — **source unique des routes** : `/recherches/nouvelle` entre dans l'arbre, et la section « Le
  parcours » ne part plus de `/`.
- [webapp/base/app/layouts/README.md](../../webapp/base/app/layouts/README.md) — un layout de plus,
  et un layout qui porte pour la première fois des bandes contextuelles.
- [webapp/spaces/entreprise/app/pages/README.md](../../webapp/spaces/entreprise/app/pages/README.md)
  — la convention « la page compose ses sections » cesse de valoir pour les trois écrans de
  recherche.

## Vérification

Preuves prises le 2026-08-06 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `owner@demo.showcase-recruiter-webapp-saas.example`, parcours complet
joué de bout en bout. Captures dans le dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/d6c47bca-7043-42fd-b4d0-53837807b7c2/scratchpad/`.

| Étape du parcours | Ce qui a été observé |
| --- | --- |
| L'accueil | « Créer une recherche » ouvre `/recherches/nouvelle` ; l'accueil ne porte plus le moteur par critères |
| L'écran de création | Gouttière « Décrivez le profil que vous cherchez » + retour ; barre « Se nomme tout seul… ✎ » et « Créer ma recherche » (`f-creation.png`) |
| Un critère posé | Le nom s'écrit dans la barre, « 293 profils » s'affiche à côté du bouton, qui s'active |
| Le lancement | La recherche naît, l'adresse devient `/recherches/{uuid}` |
| **La barre** | **y = 122 px avant, y = 122 px après** — elle ne bouge pas |
| L'arrivée | La Visionneuse est complète : `sections=2`, `aside=1`, la fiche du premier profil, « 1 / 293 » (`f-visionneuse.png`) |
| La Table | Même coque, et le nom de la recherche y est désormais visible (`e67-table.png`) |

**Le défaut qui a coûté le plus cher, et sa mesure.** Pendant l'implémentation, la Visionneuse
arrivait avec sa barre à jour et une zone de contenu VIDE — `sections=0`. Deux fichiers portaient un
commentaire AVANT leur racine, ce que Vue compte comme un second nœud ; Nuxt l'annonçait en clair
dans la console. Corrigés, l'écran arrive complet, et le glissement — qu'on croyait impossible —
fonctionne : c'était le même nœud.

Aucune gate qualité n'a été jouée : elles appartiennent à l'ouverture de la PR.

## Livré

- `webapp/spaces/entreprise/app/layouts/recherche.vue` — la coque : cadre plein écran, topbar,
  gouttière et barre haute, avec deux emplacements où les écrans projettent leurs commandes.
- `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` — l'écran de création.
- `webapp/spaces/entreprise/app/composables/useSearchDraft.ts` — le brouillon partagé entre la carte
  de critères, qui vit dans la page, et le nom, qui vit dans la coque.
- `webapp/spaces/entreprise/app/components/search/ShellName.vue` — le nom de la barre, en lecture et
  en correction, avec son effet de frappe. Remplace `SentenceName.vue`, supprimé : le nom a quitté
  la carte.
- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — la carte perd son pied et son
  formulaire ; elle ne fait plus que collecter et remonter.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/{index,table}.vue` — passés sur la coque,
  leurs commandes projetées.
- `webapp/spaces/entreprise/app/pages/index.vue` — l'accueil ne porte plus le moteur par critères.
- `webapp/spaces/entreprise/app/layouts/cockpit.vue` — racine unique rétablie.
- `webapp/base/app/assets/css/transitions.css` — le glissement, dont le sens suit la lecture.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — le titre du nouvel écran.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md`,
  `webapp/base/app/{layouts,pages}/README.md` — la route et la convention de coque.

## Reste à faire (hors périmètre)

- **La gouttière ne se rétracte plus** quand la Visionneuse n'a aucun profil : la coque ne porte pas
  la notion de veille que le composant `Gutter` sait pourtant rendre.
- Les deux boutons de filtres de la Visionneuse restent inertes — carte ouverte par `ENT-061`.
- La recherche assistée reste sur l'accueil, avec sa propre bande.

## Définition de terminé

- [x] « Créer une recherche » depuis l'accueil ouvre `/recherches/nouvelle` ; l'accueil ne porte plus
      le moteur par critères.
- [x] L'écran de création montre la gouttière, la barre haute — nom à gauche, décompte et « Créer ma
      recherche » à droite — et la carte de recherche étirée sur toute la zone de contenu.
- [x] Le nom s'écrit tout seul à mesure que les critères se posent, et se corrige au crayon.
- [x] La Visionneuse et la Table affichent la même barre haute, servie par la même coque ; le nom y
      est visible et corrigible, sauf sur une recherche partagée.
- [x] Créer la recherche fait **glisser la seule zone de contenu** vers la Visionneuse : la barre
      haute ne bouge pas, et l'écran d'arrivée n'affiche pas de chargement.
- [x] Le glissement ne se joue pas quand le système demande moins d'animation.
- [x] Recharger l'écran de création le rend vide.
- [x] Sans critère, le bouton reste fermé.
- [x] Les trois pages ne portent plus ni gouttière ni barre haute, et aucune ne porte de repère
      principal.
- [x] `arborescence-entreprise.md` et les deux README sont à jour dans le même commit.
- [x] Captures fournies : les trois écrans, grand écran et largeur téléphone.
