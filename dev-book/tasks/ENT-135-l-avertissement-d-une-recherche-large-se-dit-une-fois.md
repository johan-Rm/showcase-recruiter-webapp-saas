# TASK ENT-135 — l'avertissement d'une recherche large se dit une fois, puis se tait

- **Créée le** : 2026-08-18.
- **Session** : `c6e9274a-26a7-4733-86f6-42a6f9f95f36`.
- **Statut** : ✅ fait le 2026-08-18 — l'avertissement a quitté ses trois emplacements, la hauteur
  qu'il prenait est intégralement rendue, et le conseil se dit une fois par recherche et par onglet
  sous forme de notification passagère.
- **Type** : refactor — le conseil ne change ni de règle, ni de seuil, ni de mots. Seule sa forme
  change, et un composant disparaît avec ses clés.
- **Tests** : vitest sur la règle du « déjà annoncé », dans la couche pure — une recherche déjà
  annoncée ne l'est plus, une nouvelle l'est, un nouveau franchissement du seuil se réannonce, et
  rien ne s'annonce tant que le seuil est inconnu. Le test existant du seuil
  (`webapp/services/search/card.vitest.ts`) ne bouge pas : c'est la présentation qui change, pas la
  règle qui la déclenche.
- **Réf** : **amont** `ENT-131` (le conseil et ses deux formes, que cette carte révise) ·
  `API-059` (le seuil servi par la configuration) · `ADM-025` (le même seuil côté supervision) ·
  `webapp/spaces/entreprise/app/components/search/BroadNotice.vue` (supprimé) ·
  `webapp/base/i18n/locales/` (trois langues).

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, à deux endroits : sur **« Vos recherches
enregistrées »**, la liste de ses recherches en cartes ; et sur l'**écran de parcours**, celui au
compteur « 1 / 352 », quand il ouvre l'une d'elles.

**Ce qu'on voit aujourd'hui.** `ENT-131` a livré un conseil juste — au-delà d'une centaine de
profils, le tri devient difficile à mener jusqu'au bout — sous deux formes permanentes :

- sur chaque carte de la liste, une ligne jaune « ⚠ Recherche large — 149 profils » ;
- à l'ouverture, un bandeau pleine largeur de trois lignes, en haut de l'écran de parcours.

**Ce que ça coûte, et c'est mesurable.** Sur une liste de quatre recherches, **trois portent
l'avertissement**. Quand presque tout est signalé, plus rien ne l'est : le jaune ne prévient plus, il
décore. Et le nombre est déjà écrit en gros à gauche de chaque carte — « 352 » à côté de « 93 » dit
la même chose sans qu'on ait besoin de l'écrire.

Sur l'écran de parcours, le bandeau prend **une centaine de pixels de hauteur en permanence**, sur
l'écran où `ENT-133` et `ENT-134` viennent de se battre pour chacun d'eux : la fiche du candidat se
partage exactement ce que le bandeau lui retire.

**Le conseil reste juste, sa présentation coûte plus qu'elle ne rapporte.** Il ne s'agit ni de le
supprimer, ni de le rendre bloquant : il s'agit de le dire une fois, au bon moment, et de rendre la
place.

## Périmètre

- **La mention disparaît de la carte de la liste.** Le nombre de profils, déjà présent en gros,
  porte seul l'information.
- **Le bandeau disparaît de l'écran de parcours et de la Table.**
- **Une notification passagère paraît sur le seul écran de parcours**, avec le texte existant : le
  titre porte le constat et son nombre, le corps porte le conseil et le geste.
- **Une fois par recherche et par onglet.** L'état de ce qui a déjà été annoncé survit aux
  rechargements de page dans le même onglet, pas à sa fermeture.
- **Elle s'efface toute seule** au bout de quelques secondes, comportement par défaut du kit.
- **Aucun geste dans la notification** — pas de bouton vers les critères.
- **Un nouveau franchissement du seuil se réannonce** : une recherche repassée sous les cent profils
  puis au-dessus est un fait nouveau.
- **`BroadNotice.vue` est supprimé**, n'ayant plus aucun point de montage.
- **Les deux clés de langue devenues mortes** — `search.broad.role` et `search.broad.short` — sont
  retirées **des trois langues**. `title` et `body` restent, portées par la notification.

## Hors périmètre

- **Le seuil et sa configuration servie** (`API-059`) : ni sa valeur, ni son chemin, ni le moment où
  il arrive ne changent.
- **L'écran de supervision côté showcase-recruiter-webapp-saas** (`ADM-025`), qui garde sa liste des recherches larges.
- **L'écran de création d'une recherche** : rien n'y est signalé aujourd'hui, rien n'y sera ajouté.
- **Empêcher ou forcer quoi que ce soit.** `ENT-131` a tranché que le conseil informe et n'agit pas
  sur le travail d'un client. Cette carte ne rouvre pas cet arbitrage.
- **L'allure des notifications dans le produit** — position, teinte, durée exacte. C'est le premier
  toast du dépôt ; en faire une charte demanderait de savoir à quoi serviront les suivants.
  **Non ouvert.**

## Décisions de cadrage

- **Une notification passagère plutôt qu'une pastille permanente.** La pastille contre le compteur
  « 1 / 352 » avait été proposée : coût de hauteur nul, information disponible en permanence, geste
  jamais perdu. Elle a été **écartée** au profit du toast, qui rend la place entière. Le motif du
  refus est assumé : on préfère un écran net à une information toujours disponible.
- **Une fois par recherche et par onglet, et non à chaque ouverture.** Un recruteur ouvre et referme
  la même recherche plusieurs fois par jour ; répété, le conseil devient du bruit et se fait ignorer
  précisément par ceux qu'il vise. L'onglet plutôt que le rendu : « session » veut dire ça pour un
  utilisateur, et recharger une page ne devrait pas rejouer une annonce.
- **Elle s'efface toute seule, et le conseil ne porte pas de geste.** Deux décisions qui vont dans le
  même sens — le dispositif reste léger. Elles ont un coût, écrit plus bas.
- **Rien sur la Table.** Le conseil vise le tri un par un, qui est le travail du parcours. La Table
  compare des lignes et n'a pas ce problème.
- **Le composant est supprimé, pas conservé « au cas où ».** Un composant sans point de montage
  laisse croire qu'il sert encore ; l'historique git le rendra si l'encadré revient.
- **⚠️ ÉCART — trois accesseurs meurent avec le composant, ce que le cadrage n'avait pas dit.**
  Retirer la mention de la carte de collection rendait sans consommateur la prop `broad` de cette
  carte, l'accesseur `isBroad` de `useSearches` et celui de `useSearch`, ce dernier remplacé par
  `broadState`. Ils sont supprimés : une prop que personne ne lit et un accesseur que personne
  n'appelle sont du code mort, et les laisser aurait de toute façon fait échouer le lint.
- **La règle du « déjà annoncé » est testée**, alors que la tâche est surtout un déplacement
  d'affichage. C'est une **mémoire**, et une mémoire qui se trompe ne se voit pas à l'œil : elle se
  tait quand elle devrait parler, ce qui ne se remarque jamais.

## Règles et cas limites

- **Rien tant que le seuil ou le nombre sont inconnus.** La règle d'`ENT-131` survit intacte : un
  seuil à zéro le temps que la configuration arrive signalerait *toute* recherche non vide.
- **Le seuil exact ne déclenche rien** — « au-delà d'une centaine » commence à cent un. Inchangé.
- **Une recherche partagée par un collègue** reçoit la même notification : le conseil parle de la
  largeur de la recherche, pas de qui la possède.
- **La mémoire est par recherche**, jamais globale : annoncer une recherche ne doit pas faire taire
  les autres.
- **Le stockage de session ne se lit que côté client.** Rendu côté serveur, il n'existe pas — la
  notification ne part donc jamais avant que la page soit vivante.
- **Aucun texte nouveau** : les deux clés conservées suffisent, et rien n'est écrit en dur.

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/`** : ni le seuil, ni le contrat, ni une convention de dossier ne
  changent.
- **Aucun `README.md` de dossier** : le composant supprimé ne portait aucune règle.

## Définition de terminé

- [x] Plus aucune mention d'avertissement sur les cartes de « Vos recherches enregistrées ».
- [x] Plus aucun bandeau en haut de l'écran de parcours ni de la Table — la hauteur rendue est
      mesurée à l'écran, avant et après.
- [x] À la première ouverture d'une recherche au-dessus du seuil, une notification paraît avec le
      nombre exact et le conseil.
- [x] À la deuxième ouverture de la même recherche dans le même onglet, elle ne paraît pas.
- [x] Après réouverture de l'onglet, elle paraît de nouveau.
- [x] Une recherche sous le seuil n'annonce jamais rien.
- [x] Une recherche qui repasse sous le seuil puis au-dessus se réannonce.
- [x] `BroadNotice.vue` n'existe plus, et aucune référence ne subsiste.
- [x] `search.broad.role` et `search.broad.short` ont disparu des trois langues, et le test de parité
      passe.
- [x] La règle du « déjà annoncé » est couverte par vitest.

## Vérification

Navigateur réel piloté, session authentifiée en `entreprise@showcase-recruiter-webapp-saas.example`, thème sombre, fenêtre
1512 × 900. Deux recherches : une **large** (149 profils) et une **étroite** (93 profils).

### La hauteur est intégralement rendue

```
recherche LARGE   (149 profils)  contenu à 181 px du haut   zone de fiche 710 px
recherche ÉTROITE  (93 profils)  contenu à 181 px du haut   zone de fiche 710 px
écart : 0 px
```

Une recherche large et une recherche étroite démarrent désormais **au même endroit** et donnent à la
fiche **la même hauteur**. C'est la preuve directe que le bandeau ne coûte plus rien : avant, la
large perdait sa hauteur, l'étroite non.

### La liste ne signale plus rien

Sur « Vos recherches enregistrées », 64 recherches affichées : le texte « Recherche large » n'apparaît
**nulle part** dans la page.

### La notification et sa mémoire

```
1re ouverture, recherche large        notification affichée
quelques secondes plus tard           effacée toute seule
2e ouverture, même onglet             rien (12 s d'attente)
après rechargement de la page         rien (12 s d'attente)
nouvel onglet                         notification de nouveau
recherche sous le seuil, mémoire vidée   rien (22 s d'attente)
```

### Les tests

```
services/search/card.vitest.ts + base/i18n/locales/parity.vitest.ts
2 fichiers, 47 tests, tous au vert — dont 6 nouveaux sur la règle du « déjà annoncé »
```

## ⚠️ La notification arrive tard — défaut connu, non résolu

**Mesuré : 8,6 s puis 10,5 s après l'affichage des premières fiches**, route déjà compilée, mémoire
vidée entre les deux essais. Ce n'est pas la compilation du serveur de développement.

**Le délai est pré-existant et n'a pas été introduit ici** : le bandeau dépendait exactement des
mêmes données — la fiche de la recherche et le seuil servi — et s'affichait tout aussi tard. Personne
ne l'avait vu, parce qu'un bandeau qui s'installe en retard s'installe quand même, et reste.

**Mais le toast le rend décisif.** Il paraît alors que le recruteur a commencé à lire, puis disparaît.
Dans ces conditions, le conseil manque une partie de ceux qu'il vise — c'est-à-dire l'essentiel de ce
que la tâche cherchait à obtenir.

Ce qui reste à établir : laquelle des deux données arrive si tard, la fiche de la recherche ou le
seuil de configuration. La recherche a été interrompue avant d'aboutir. **Non ouvert** : c'est un
sujet de chargement, pas de présentation, et il déborde de cette carte.

## Risque assumé

**Un conseil qui s'efface tout seul est un conseil que certains ne liront jamais** : celui qui
regardait la fiche à ce moment-là, celui qui utilise un lecteur d'écran et se trouve ailleurs dans la
page au moment où la notification s'annonce. Et comme la mention disparaît aussi de la liste,
l'information ne sera plus disponible **nulle part** quelques secondes après l'ouverture.

C'est le prix de la place gagnée, et il a été accepté en connaissance de cause. Il est écrit ici pour
qu'une session future qui verrait des recherches larges se multiplier sans que personne ne réagisse
sache où chercher, plutôt que de le redécouvrir.


## Reste à faire (hors périmètre)

- **Le délai de 9 secondes ci-dessus**, qui appartient à l'ordre de chargement de l'écran et non à
  cette tâche. Tant qu'il tient, le conseil rate une partie de son public.
- **L'allure des notifications dans le produit** — position, teinte, durée. C'est le premier toast du
  dépôt ; il prend les valeurs par défaut du kit. **Non ouvert.**
