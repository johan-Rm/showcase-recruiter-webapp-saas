# TASK OPS-018 — Les mesures du vivier rejoignent la doc technique

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — l'inventaire des critères et le tableau des taux de
  remplissage migrés dans `2.architecture/`, un lien cassé d'`architecture.md` réparé.
- **Type** : Refactor — réorganisation documentaire ; aucun code touché.
- **Tests** : sans objet — aucun code exécutable. La vérification est la résolution de chaque lien
  déplacé ou repointé.
- **Réf** : `OPS-017` (le même mouvement, sur les arborescences) · `060` / `061` / `062` (les trois
  cartes ouvertes qui s'appuient sur ces mesures)

## Contexte

`dev-book/cadrage/draft/` servait de fourre-tout à des documents de travail. Deux d'entre eux n'ont
rien d'un brouillon : ce sont les seules **mesures** du projet, obtenues en analysant la donnée
réelle plutôt qu'en la décrivant.

- **`inventaire-criteres-3-couches.md`** suit un profil témoin à travers les trois couches — API
  Jarvi, base showcase-recruiter-webapp-saas après import, API showcase-recruiter-webapp-saas — pour établir quels critères de recherche
  existent réellement, et lesquels retenir.
- **`filtres-vivier-tableau-partage.md`** donne le taux de remplissage champ par champ sur les
  ~1 700 candidats importés, croisé avec l'état de préparation de chaque filtre.

Ces mesures ne sont pas restées lettre morte : le code de recherche les cite. `CandidateRepository`
explique que la recherche par mots-clés porte sur le titre et les compétences parce que « le résumé
est vide sur **98,5 %** des profils », et que le filtre niveau d'études exclut les profils sans
niveau reconnu, « **≈ 23 %** du vivier ». Ce sont les chiffres de ces deux documents.

Un document dont le code cite les résultats n'est pas un brouillon de cadrage — c'est de la
documentation technique, et `dev-book/cadrage/` est écrit pour les clients.

## Périmètre

- Déplacer les deux documents vers `docs/showcase-recruiter-webapp-saas/content/2.architecture/`, et la version HTML du
  second vers `docs/showcase-recruiter-webapp-saas/public/`, servie en statique.
- Repointer les trois cartes ouvertes qui s'y appuient et le renvoi croisé entre les deux.
- Les inscrire à l'index de la section.
- Réparer le lien `INITIAL_BRIEF` d'`architecture.md`, cassé depuis `OPS-012`.

## Hors périmètre

- **Les quatre documents restants de `draft/`** — `infrastructure_draft.md`, `notes_en_vrac.md`,
  `parcours_utilisateur_recruteur_draft.md`, `reunion-filtres-vivier-2026-07.md`. Les trois premiers
  sont cités comme **sources** par `architecture.md` et `docker-and-make.md` : les déplacer ou les
  supprimer demande de traiter ces renvois, ce qui est une décision distincte.
- **Le contenu des documents déplacés**, repris tel quel.

## Décisions de cadrage

### Déplacer plutôt qu'absorber puis supprimer

L'option d'inliner les constats cités dans les trois cartes ouvertes puis de supprimer les sources a
été écartée. Elle aurait dispersé une analyse cohérente en trois fragments, et perdu ce qui n'était
cité par aucune carte — le tableau complet des filtres possibles, les couches 1 et 2, les critères
écartés en V1 et leur raison.

### `2.architecture/`, aux côtés de `profile-processing.md`

Ces documents décrivent ce que la donnée contient réellement et ce qu'on peut en filtrer. C'est le
voisinage de `profile-processing.md` et `data-model.md`, pas celui de la qualité ni du design.

### Le HTML rejoint `public/`

`OPS-013` a posé le précédent avec `carte-domaines.html` : une page HTML destinée à être ouverte
telle quelle se sert depuis `docs/showcase-recruiter-webapp-saas/public/`. Le fichier n'était référencé nulle part, il
n'y avait donc aucun lien à repointer.

## Vérification

Aucune gate à jouer — la tâche ne produit pas de code. Chaque lien touché a été résolu sur le
disque :

- **Liens sortants** des documents déplacés : 3 sur 3 résolvent (`profil-jarvi-reference.md`,
  `criteres_de_filtrage_profils.md`, tous deux restés dans le cadrage, et le renvoi croisé entre les
  deux documents, devenu un lien de même dossier).
- **Références entrantes** : 3 cartes ouvertes repointées (`060`, `061`, `062`), qui citent ces
  documents par constat numéroté — « constat n°0 », « couvertures mesurées », « reco n°3 ».
- **Le lien `INITIAL_BRIEF` d'`architecture.md` était cassé** : il visait
  `dev-book/cadrage/requirements/initial_brief.md`, alors qu'`OPS-012` a remonté `requirements/` à
  la racine du dépôt. Les cinq autres liens de son bloc « Sources » résolvaient ; celui-là non.
- Le `.html` n'était référencé par aucun fichier du dépôt — vérifié par `git grep` avant de le
  déplacer.

## Livré

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` **(R)** — 2 liens
  sortants recalculés.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/filtres-vivier-tableau-partage.md` **(R)** — le renvoi vers
  l'inventaire devient un lien cliquable.
- `docs/showcase-recruiter-webapp-saas/public/filtres-vivier-tableau-partage.html` **(R)** — servi en statique.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/index.md` **(M)** — les deux documents inscrits au sommaire.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` **(M)** — lien `INITIAL_BRIEF` réparé.
- `dev-book/tasks/060|061|062` **(M)** — références repointées.

## Reste à faire (hors périmètre)

- **`dev-book/cadrage/draft/` garde quatre documents.** Trois sont cités comme sources par la doc
  vivante ; leur sort suppose de décider si `architecture.md` doit continuer de renvoyer vers des
  brouillons de cadrage. Le quatrième, `reunion-filtres-vivier-2026-07.md`, est une préparation de
  réunion dont l'intérêt résiduel est la liste d'écarts cadrage/implémentation qu'elle dressait.

## Définition de terminé

- [x] Les deux documents vivent dans `2.architecture/` et figurent à son index.
- [x] La version HTML est servie depuis `public/`.
- [x] Les trois cartes ouvertes pointent vers le nouvel emplacement.
- [x] Les liens sortants des documents déplacés résolvent.
- [x] Le bloc « Sources » d'`architecture.md` ne contient plus de lien cassé.
