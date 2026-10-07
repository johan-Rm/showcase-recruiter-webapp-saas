# TASK ENT-044 — entreprise : le nombre de profils quitte les listes de propositions

- **Créée le** : 2026-08-04.
- **Session** : `da944620-6b4c-4e3a-94d4-e0f07ebbf86a`.
- **Statut** : ✅ fait le 2026-08-04 — les deux listes n'affichent plus aucun nombre, captures et
  relevé du contenu à l'appui. Le classement des propositions est intact.
- **Type** : Frontend entreprise — retrait d'un élément d'interface, aucune logique touchée.
- **Tests** : `sans objet — aucun code exécutable produit`. Le changement est purement d'affichage,
  et le webapp n'a **aucun harnais de test de composant** : les `*.vitest.ts` couvrent les services
  et les utilitaires, jamais un rendu. En introduire un pour ce retrait déborderait très largement la
  tâche. **Décision de l'utilisateur, tracée ici** — cf. *Décisions de cadrage*.
- **Réf** : `API-025` (le champ quitte la réponse du serveur — **cette carte doit être livrée
  AVANT**) · `API-009` (qui a introduit ce nombre, et pourquoi) · `API-023` (l'auto-complétion, où le
  sujet est venu) · `ENT-045` (l'autre message du même champ) ·
  `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue`

## Contexte

Un recruteur ouvre l'espace entreprise, page « Décrivez le profil que vous cherchez ». Il tape dans
« Intitulé de poste » ; la liste déroulante s'ouvre sous le champ. À droite de chaque proposition,
un nombre :

```
Développeur logiciel                    293 profils
Business Developer                      156 profils
Ingénieur DevOps                         17 profils
Développeur front-end                     9 profils
Développeur mobile                        5 profils
```

Le champ « Mots-clés » fait la même chose avec les compétences.

**Ce nombre décrit le vivier avant qu'on l'ait acheté.** En tapant quelques lettres au hasard, un
recruteur relève la taille de chaque population et la composition du portefeuille — combien de
développeurs, combien de commerciaux, combien de profils au total — sans avoir dévoilé un seul
profil, donc sans avoir dépensé un crédit. C'est une information commerciale, et elle est servie
gratuitement à qui sait taper trois lettres.

`API-009` l'avait introduite pour une bonne raison, et cette raison ne disparaît pas : sans le
nombre, on pose « communication » — **28 % du vivier** — en croyant restreindre. On accepte de perdre
cet avertissement ; voir *Décisions de cadrage*.

## Périmètre

- **`FiltersPrimary.vue`** : `jobSuggestions` et `keywordSuggestions` cessent de composer une
  indication ; les propositions ne portent plus que leur libellé.
- **Les deux champs** : « Intitulé de poste » et « Mots-clés ».
- **La clé de traduction** `search.primary.suggestion.count`, qui n'a plus d'appelant, dans les
  **trois** fichiers de langue (`fr.yaml`, `en.yaml`, `ar.yaml`).
- **Le commentaire du composant**, qui explique aujourd'hui pourquoi ce nombre est là et devient faux.

## Hors périmètre

- **Le classement des propositions**, qui reste celui du serveur : les plus portées d'abord. Il
  s'appuie sur ce nombre, et cette carte ne le touche pas.
- **La règle « aucune proposition ne ramène zéro »** (`API-009`), qui s'appuie elle aussi sur ce
  décompte, côté serveur.
- **Le retrait du champ de la réponse HTTP** — c'est `API-025`, et c'est ce qui rend le retrait réel.
- **La ligne de volume à côté du bouton** (« 12 profils correspondent »), qui répond à une autre
  question : ce que la recherche **entière** ramènerait, une fois tous les critères posés. Elle n'est
  pas un inventaire du vivier, et elle reste.
- **Le champ `profileCount` de `webapp/shared/types/search.ts`**, qui décrit ce que le serveur envoie
  encore. Il partira avec `API-025`.

## Décisions de cadrage

### Cacher dans la page ne cache rien — mais c'est quand même le premier geste

Le nombre voyage dans la réponse à `/api/suggestions`, que n'importe qui lit en ouvrant les outils de
développement de son navigateur. **Retirer l'affichage seul serait du décor.**

L'ordre est pourtant celui-ci, et il est imposé par le typage : si le serveur retirait le champ en
premier, ce composant lirait une propriété que le contrat ne décrit plus, et la construction du front
échouerait. Cette carte prépare le terrain ; `API-025` referme.

**Tant que les deux ne sont pas livrées, le problème n'est pas réglé.** À dire tel quel si la
question se pose entre les deux.

### Les deux champs, pas seulement les postes

La demande visait les postes. Le motif retenu — ne pas exposer le volume du vivier — vaut identiquement
pour les compétences : « communication, 717 profils » renseigne autant sur le portefeuille que
« Développeur logiciel, 293 ».

### Ce qu'on perd, et on le perd sciemment

`API-009` avait mis ce nombre pour empêcher une erreur réelle : la tête de distribution est occupée
par des compétences qui ne discriminent rien — `communication` 717 profils, `microsoft office` 625,
`management` 623. Le recruteur qui pose « communication » croit restreindre et vient de sélectionner
un quart du vivier.

Cet avertissement disparaît. Il n'est **pas** remplacé ici : la ligne de volume à côté du bouton dit
déjà ce que la recherche entière ramènerait, ce qui rattrape le cas au moment de lancer plutôt qu'au
moment de choisir. Si l'usage montre que c'est trop tard, la question se rouvrira avec un cas réel.

### Pas de test, et c'est un choix, pas un oubli

Le webapp n'a aucun harnais de test de composant — les `*.vitest.ts` du dépôt couvrent des services
et des utilitaires purs, jamais un rendu. Ce retrait ne produit aucune logique testable : il supprime
deux lignes qui composaient une chaîne.

Introduire un harnais de rendu pour couvrir un retrait d'affichage coûterait infiniment plus que ce
qu'il protège. La preuve attendue est donc une **capture d'écran** de la liste sans les nombres.

## Règles et cas limites

- **Aucun texte en dur** ne remplace la clé retirée : il n'y a rien à afficher à la place.
- **Les trois langues** perdent la clé ensemble. Une clé orpheline dans un seul fichier passe les
  gates mais laisse une traduction qui ne sert plus.
- **L'alignement de la liste** doit rester correct une fois la colonne de droite vide : les
  propositions ne doivent pas se retrouver centrées ou étirées parce que leur second élément a
  disparu.
- **Le composant `UiRemoteCombobox` et `UiTagPicker` vivent dans `ui/`** et acceptent une indication
  facultative. Ils ne changent pas : ce sont leurs appelants qui cessent d'en fournir une. Un
  composant du socle ne se modifie pas pour un besoin d'un seul espace.

## Documentation impactée

- **Le commentaire de `FiltersPrimary.vue`**, qui justifie aujourd'hui la présence du nombre sur
  quatre lignes et dit l'inverse de ce que fait le code.
- **Vérifié, non impacté** : `arborescence-entreprise.md` (aucun écran ni parcours ne change),
  `i18n.md` (aucune règle de traduction ne bouge, seule une clé disparaît),
  `composables-standard.md` (`useSuggestions` n'est pas touché).

## Définition de terminé

- [x] La liste du champ « Intitulé de poste » n'affiche plus aucun nombre — **capture à l'appui**.
- [x] La liste du champ « Mots-clés » non plus.
- [x] La clé `search.primary.suggestion.count` a disparu des trois fichiers de langue et n'est
      référencée nulle part.
- [x] Le classement des propositions est inchangé : les plus portées restent en tête.
- [x] La ligne de volume à côté du bouton fonctionne comme avant.
- [x] La liste reste correctement alignée sans sa colonne de droite.

## Vérification

**Constaté dans le navigateur**, sur la stack de ce worktree, avec un compte recruteur réel — pas en
lisant le code. Le 2026-08-04, en tapant `dev` dans « Intitulé de poste » puis `python` dans
« Mots-clés » :

```
MÉTIERS   : Développeur logiciel · Business Developer · Ingénieur DevOps
            · Développeur front-end · Développeur mobile
MOTS-CLÉS : python (langage de programmation) · python (programming language) · python
            · python · python (langage de programmation) · …
```

Aucun nombre nulle part. **L'ordre est celui d'avant** — « Développeur logiciel » reste en tête,
« Développeur mobile » en queue : le serveur classe toujours par nombre de porteurs, il ne le dit
simplement plus.

La capture montre la liste déroulante ouverte sous le champ, alignée à gauche sans colonne de droite
résiduelle. La ligne « Aucun profil ne correspond » à côté du bouton fonctionne comme avant.

**Ce que la sortie révèle au passage, et qui n'appartient pas à cette carte** : `python` apparaît
deux fois dans les mots-clés. Cette stack sert l'API **d'avant `API-023`**, qui fusionne justement
ces doublons d'espace parasite. Le défaut disparaîtra quand la lane API sera mergée — il n'y a rien à
faire ici.

## Livré

- `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue` **(M)** — les deux listes ne
  composent plus d'indication ; `useLocale` et son `t` disparaissent avec elles, faute d'emploi ; le
  commentaire qui justifiait le nombre est remplacé par celui qui justifie son absence.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — la clé `search.primary.suggestion.count`,
  retirée des trois langues ensemble.

## Écart au cadrage

Aucun sur le fond. Un détail non prévu : `t` n'avait **pas d'autre usage** dans le composant, si bien
que retirer les deux indications rendait `useLocale` orphelin. Le modèle est parti avec — le gabarit
utilise `$t`, la forme globale, qui ne dépend pas de cet appel.
