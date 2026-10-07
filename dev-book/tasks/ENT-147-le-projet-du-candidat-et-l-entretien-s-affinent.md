# TASK ENT-147 — le projet du candidat et l'entretien s'affinent

- **Créée le** : 2026-08-19.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-19 — un cinquième volet « Poste recherché au Maroc » porte le
  secteur et la fonction visés, et un second interrupteur « Profil vérifié par showcase-recruiter-webapp-saas » ouvre le
  panneau.
- **Type** : feat — trois affinages de plus dans la colonne de gauche ; aucun travail serveur.
- **Tests** : vitest sur la couche pure (`webapp/services/search/refine.vitest.ts`) — chacun des trois
  affinages garde les profils qui portent la valeur et écarte les autres, les deux rangées n'offrent
  que ce que le jeu porte, et l'interrupteur décoché ne retire rien. Plus le test de parité des trois
  langues pour les deux libellés ajoutés. **Aucun test de rendu** : le dépôt n'en a aucun sur ses
  composants.
- **Réf** : amont `ENT-110` (le panneau), `ENT-123` (l'agencement des volets), `ENT-138` (l'ordre par
  poids, aucun nombre affiché) · voisin `ENT-142`, qui fera dire aux rangées vides pourquoi elles le
  sont · sœur `ENT-148`, le niveau de langue, qui a sa propre règle.

## Contexte

Un recruteur, espace entreprise, écran de parcours des profils (« Visionneuse »), **colonne de
gauche** — le panneau qui resserre la liste sans toucher aux critères de la recherche.

**Trois filtres sont prévus au contrat de recherche et affichés nulle part.** Le composant qui les
portait — `FiltersSecondary.vue` — n'est monté sur aucun écran : il existe, il est complet, personne
ne l'appelle. Le recruteur ne peut donc resserrer ni sur ce que le candidat **cherche** — son secteur
et sa fonction visés —, ni sur le fait qu'il ait été **vu en entretien** par showcase-recruiter-webapp-saas.

Ce dernier est le plus qualifiant du produit : un profil vérifié a été rencontré, pas seulement
importé.

**La donnée manque aujourd'hui, et c'est assumé.** Mesuré sur 3 899 candidats : secteur visé 3,
fonction visée 3, profil vérifié 1. Ces champs se remplissent par l'espace candidat, et
l'enrichissement est prévu avant la mise en production. Le filtre est posé maintenant pour être prêt
quand la donnée arrivera — décision explicite, prise en connaissance des chiffres.

## Périmètre

- **Trois champs** de plus au formulaire d'affinage : secteur visé, fonction visée, profil vérifié.
- **Un cinquième volet « Poste recherché au Maroc »** portant les deux premières, en rangées
  d'étiquettes.
- **Un second interrupteur « Profil vérifié par showcase-recruiter-webapp-saas »** en tête du panneau, à côté du poste
  actuel.
- **Deux libellés** à traduire dans les trois langues — le titre du volet et celui de l'interrupteur.

## Hors périmètre

- **Le niveau de langue** : `ENT-148`. Il porte une règle de couplage que les trois autres n'ont pas.
- **Tout travail serveur.** La carte du candidat porte déjà `desiredSector`, `desiredFunction` et
  `isStar` : rien à demander, rien à exposer, aucun contrat à changer.
- **L'enrichissement de la donnée elle-même**, qui se joue dans l'espace candidat.
- **Ces filtres comme critères de recherche enregistrés** : ils restent des affinages, jetables en
  quittant l'écran.
- **Le panneau reste non rendu sous 1024 px** — dette antérieure.
- **Aucun changement de rôle ni de permission** : les trois données se lisent déjà sans dévoilement.

## Décisions de cadrage

### Un cinquième volet plutôt qu'un volet surchargé

Écarté — loger secteur visé et fonction visée dans « Projet de retour ». Ce volet dit **comment le
départ se prépare** — horizon, pied-à-terre, mobilité ; les deux nouvelles disent **quel poste est
cherché**. Deux questions distinctes, et les entasser aurait porté le volet à cinq contrôles.

### L'interrupteur reste dehors

Le panneau range en volet ce qui tient plusieurs entrées, et laisse dehors ce qui tient sur une
ligne — un volet coûte un geste à chaque consultation. Un interrupteur seul dans un volet paierait ce
geste pour rien. Les deux interrupteurs se lisent donc ensemble en tête, sans rien déplier.

### Les deux interrupteurs de tête n'apparaissent pas aux mêmes moments

Écart constaté à l'écran, que le cadrage n'avait pas prévu et qui n'appelle pourtant aucune
correction : « Occupe ce poste aujourd'hui » ne s'offre **que si la recherche nomme un métier** —
sans critère de poste, aucun profil ne porte de correspondance à interroger. « Profil vérifié par
showcase-recruiter-webapp-saas » n'a pas cette condition : la donnée existe sur la carte quelle que soit la recherche, il
s'affiche donc toujours.

La tête du panneau porte donc tantôt un interrupteur, tantôt deux. C'est correct, et c'est écrit ici
pour que personne ne le prenne pour un défaut.

### Les valeurs s'affichent brutes

Secteur visé et fonction visée sont des textes libres venus de la source. Comme le secteur
d'expérience, et pour la même raison qu'`ENT-138` a retenue : aucun référentiel connu de l'exécution
ne les nomme, et une table écrite à la main serait la première chose à dériver. Les pays et les
langues font exception parce que l'exécution les connaît ; ce n'est pas le cas ici.

### Le filtre précède la donnée, et c'est voulu

Décision de l'utilisateur, prise après mesure : poser les filtres maintenant même si trois profils
seulement les portent, l'enrichissement étant prévu avant la mise en production. La conséquence est
donc **attendue et non subie** — les rangées seront vides jusque-là, et `ENT-142` leur fera dire
pourquoi plutôt que de les faire disparaître.

## Règles et cas limites

- **Les rangées n'offrent que ce que le jeu porte**, rangées par poids décroissant et sans nombre
  affiché — règles du panneau depuis `ENT-138`.
- **Reposer une étiquette active la retire** : c'est le seul moyen d'annuler un choix dans une rangée
  sans entrée « peu importe ».
- **Un interrupteur décoché ne retire rien.** Coché veut dire « je veux ceux qui l'ont » ; décoché
  veut dire « peu importe », jamais « ceux qui ne l'ont pas ». Règle commune aux interrupteurs du
  panneau.
- **Un profil sans secteur ni fonction visés** ne répond à aucune de ces deux rangées — ce qui est
  exact : on ne sait pas ce qu'il cherche.
- **Le volet s'affiche même vide** : le point qui signale un filtre posé dans un volet replié
  fonctionne comme pour les quatre autres.
- **Aucun texte utilisateur en dur** : les deux libellés passent par des clés.

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/`.** Le tableau des déclencheurs ne s'applique pas : ni surface
  d'API, ni entité, ni contrat, ni convention de dossier — seul l'affichage change.

## Vérification

Filtres manœuvrés à l'écran sur « Profils de démo », la seule recherche contenant les deux profils
porteurs de la donnée. Compte lu dans la barre haute :

```
au repos                        2 profils
« Profil vérifié » coché        1
décoché                         2
« Secteur visé : Finance »      1     · « Développeur » ÉTEINTE
retiré                          2
« Fonction visée : Dévelop. »   1     · « Finance » ÉTEINTE
retiré                          2
```

**Les deux extinctions confirment la combinaison par ET** sans qu'on ait eu à l'écrire : « Finance »
posé, il ne reste que le comptable, donc « Développeur » mènerait à un écran vide et la pastille
s'éteint d'elle-même.

**Capture du panneau, tous volets dépliés** : le volet « Poste recherché au Maroc » se lit en
cinquième position, avec ses deux rangées garnies — Finance · Informatique, puis Comptable ·
Développeur.

**57 tests verts** sur la couche pure — les 49 existants plus 8 ajoutés — et le test de parité des
trois langues au vert.

## Livré

- `webapp/services/search/refine.ts` — trois champs au formulaire, leurs trois prédicats, et deux
  listes offertes sur le modèle du pays : une valeur par carte, ou aucune.
- `webapp/services/search/refine.vitest.ts` — huit tests : les trois affinages, la combinaison par
  ET, l'interrupteur décoché qui ne retire rien, l'ordre par poids, et le choix unique qui ne
  s'offre pas.
- `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` — le cinquième volet, le second
  interrupteur, et les deux rangées avec leurs intitulés.
- `webapp/base/i18n/locales/fr.yaml` · `en.yaml` · `ar.yaml` — deux clés : le titre du volet et
  celui de l'interrupteur.

## Reste à faire (hors périmètre)

- **Le volet s'ouvre sur du vide** tant que la donnée manque, comme le volet Secteur avant lui :
  c'est `ENT-142` qui fera dire aux rangées vides pourquoi elles le sont.
- **Le niveau de langue** : `ENT-148`.

## Définition de terminé

- [x] Le panneau porte un volet « Poste recherché au Maroc » avec secteur visé et fonction visée.
- [x] Un second interrupteur « Profil vérifié par showcase-recruiter-webapp-saas » se lit en tête, à côté du poste actuel.
- [x] Poser l'un des trois resserre la liste sur les profils qui portent la valeur.
- [x] Les deux rangées sont rangées par poids décroissant et ne portent aucun nombre.
- [x] Les libellés existent dans les trois langues, et le test de parité est vert.
- [x] Les tests de la couche pure sont écrits et verts.
- [x] Vérifié à l'écran, sur une recherche où au moins un profil porte la donnée.
