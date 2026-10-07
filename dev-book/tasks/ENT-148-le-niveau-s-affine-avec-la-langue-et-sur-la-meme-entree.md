# TASK ENT-148 — le niveau s'affine avec la langue, et sur la même entrée

- **Créée le** : 2026-08-19.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-19 — une rangée « Niveau déclaré, au moins » paraît sous les
  langues dès qu'une langue est posée, applique un seuil sur la même entrée du profil, et disparaît
  avec elle.
- **Type** : feat — un affinage de plus, adossé à celui de la langue ; aucun travail serveur.
- **Tests** : vitest sur la couche pure (`webapp/services/search/refine.vitest.ts`) — le seuil garde
  les paliers supérieurs, la langue et le niveau tombent sur **la même** entrée du profil, une entrée
  sans niveau est écartée, et la rangée ne s'offre pas tant qu'aucune langue n'est posée. Plus le test
  de parité des trois langues pour le libellé ajouté. **Aucun test de rendu** : le dépôt n'en a aucun
  sur ses composants.
- **Réf** : amont `ENT-110` (le panneau), `ENT-138` (l'ordre, aucun nombre affiché), `ENT-139` (le
  libellé qui porte sa réserve) · sœur `ENT-147`, les trois autres affinages du même lot · voisin :
  la fenêtre de présence et sa ville, qui portent le même couplage.

## Contexte

Un recruteur, espace entreprise, Visionneuse, **colonne de gauche**, sous la rangée des langues.

L'affinage par langue existe et ignore le niveau : poser « anglais » ramène aussi bien un bilingue
qu'une personne qui en a des notions. Le recruteur qui cherche quelqu'un capable de tenir une réunion
en anglais n'a aucun moyen de le dire, alors que **le serveur sait déjà filtrer sur un seuil** —
la facette existe au contrat de recherche, aucun écran ne la propose.

**C'est le seul des quatre filtres de ce lot dont la donnée existe déjà** : 4 671 lignes de langue
sur 7 043 portent un niveau, et 1 385 candidats sur 3 899 en ont au moins un. Le filtre servira dès
le premier jour.

## Périmètre

- **Un champ** de plus au formulaire d'affinage : le niveau minimum.
- **Une rangée sous les langues**, cinq paliers — notions, limité, professionnel, courant, bilingue.
- **Le libellé porte sa réserve** : « Niveau déclaré, au moins ».
- **Un libellé** à traduire dans les trois langues ; les cinq paliers existent déjà.

## Hors périmètre

- **Les trois autres affinages du lot** : `ENT-147`.
- **Tout travail serveur.** La carte porte déjà les langues avec leur `proficiency`, et la facette de
  recherche existe côté serveur depuis longtemps — elle reste disponible comme critère.
- **Le niveau comme critère de recherche enregistré** : il existe déjà là-bas, il n'y a rien à y
  faire.
- **Le panneau reste non rendu sous 1024 px.**

## Décisions de cadrage

### Trois règles reprises du serveur, pas réinventées

Le dépôt les porte déjà, et son commentaire les énonce : *« Le niveau minimum n'a de sens qu'adossé à
une langue, et il EXCLUT les entrées sans niveau : un tiers du vivier n'en porte pas, on ne le
suppose pas. »*

- **C'est un SEUIL**, pas une égalité — le serveur applique `atLeast`. Poser « professionnel » garde
  aussi les courants et les bilingues.
- **Il ne s'offre qu'adossé à une langue.** Sans langue posée, « au moins courant » ne désigne rien.
- **Il porte sur la MÊME entrée** du profil. C'est le point qui décide tout : un arabophone maternel
  et anglophone débutant satisferait « anglais » d'un côté et « bilingue » de l'autre, et répondrait
  à une demande à laquelle il ne répond pas. Le même piège s'est présenté sur la fenêtre de présence
  et sa ville, qui doivent tomber sur la même période.

L'affinage s'aligne sur ces trois règles plutôt que d'en inventer d'autres : deux moteurs qui
filtrent différemment sur la même facette rendraient l'écran incompréhensible.

### « Déclaré » n'est pas un mot de remplissage

Le niveau écarte 2 372 lignes de langue sur 7 043 — celles qui n'en portent pas. Une personne qui
parle anglais sans que personne n'ait déclaré son niveau **sort** du résultat.

Écarté — une phrase grise sous la rangée : c'est exactement la forme qu'`ENT-139` vient de retirer de
l'interrupteur du poste, à l'endroit du produit que personne ne lit. Le libellé porte donc la
réserve : « Niveau **déclaré**, au moins » dit qu'on filtre sur ce qui a été renseigné, et l'exclusion
en découle sans note de bas de page.

### Les libellés se réutilisent, malgré un bloc mal nommé

Les cinq paliers vivent sous `search.ai.*` — le bloc de la recherche assistée —, ce que le cadrage
n'avait pas vu. Écarté quand même : les dupliquer sous `search.refine.*`. La fiche du profil affiche
**déjà** ces libellés depuis ce même catalogue, et deux sources pour le même mot sur le même écran
finiraient par diverger.

Le nom du bloc reste inadapté : il dit `ai` pour une nomenclature générale. Le renommer touche trois
fichiers et sort du périmètre — nommé, pas traité.

### Les paliers gardent leur échelle

Écarté — les ranger par nombre décroissant, comme les rangées sans ordre naturel depuis `ENT-138`.
Les cinq paliers **sont** une échelle : « notions » avant « bilingue » dit quelque chose que le compte
n'a pas à défaire. Même traitement que la fenêtre de présence, l'horizon de retour et la
disponibilité.

## Règles et cas limites

- **La rangée n'apparaît qu'une fois une langue posée**, et disparaît si on retire la langue — le
  niveau posé se retire avec elle, sinon il resterait actif sans rien désigner.
- **Aucun nombre sur les étiquettes**, règle du panneau depuis `ENT-138`.
- **Un profil qui parle la langue sans niveau déclaré est écarté** dès qu'un palier est posé. C'est
  la règle du serveur, et le libellé la porte.
- **Un profil peut porter la même langue deux fois** avec des niveaux différents selon la source :
  il suffit qu'**une** entrée satisfasse langue et seuil ensemble.
- **Reposer le palier actif le retire**, comme dans toutes les rangées du panneau.
- **Aucun texte utilisateur en dur.**

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/`.** Ni surface d'API, ni entité, ni contrat, ni convention de
  dossier : la facette serveur existe déjà et ne bouge pas.

## Vérification

Seuil manœuvré à l'écran sur « Preuve ENT-113 », **après stabilisation du jeu** — le remplissage de
fond tournait encore lors d'une première mesure, et les comptes n'étaient pas comparables :

```
Anglais seul                     407 profils
  + au moins « notions »         318
  + au moins « limité »          316
  + au moins « professionnel »   306
  + au moins « courant »         203
  + au moins « bilingue »         73
```

Strictement décroissant : le seuil garde bien les paliers supérieurs. **La marche la plus parlante
est la première** — 407 à 318 au palier le plus bas : ces 89 profils parlent anglais sans niveau
déclaré, et sortent dès qu'un seuil est posé. C'est ce que le mot « déclaré » annonce.

**Trois comportements vérifiés en plus** : la rangée est absente sans langue posée, apparaît dès
qu'une langue l'est, et disparaît en emportant le seuil quand la langue est retirée — le compte
revient à 641, pas à un état intermédiaire.

**Capture** : « Anglais » et « bilingue » posés, les paliers dans leur ordre d'échelle, et les
langues que le seuil vide éteintes d'elles-mêmes.

**73 tests verts** — 64 sur la couche d'affinage dont 7 ajoutés, plus les 9 de parité des langues.

## Livré

- `webapp/services/search/refine.ts` — l'échelle des paliers exportée, le champ du seuil, et un
  prédicat de langue qui confronte langue et niveau sur la même entrée.
- `webapp/services/search/refine.vitest.ts` — sept tests, dont le piège de l'arabophone maternel et
  la langue portée deux fois.
- `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` — la rangée conditionnelle, et la
  langue qui emmène son niveau quand on la retire.
- `webapp/base/i18n/locales/fr.yaml` · `en.yaml` · `ar.yaml` — une clé, le titre de la rangée.

## Reste à faire (hors périmètre)

- **Le catalogue des paliers vit sous `search.ai.*`**, un bloc mal nommé pour une nomenclature
  générale. Le déplacer toucherait trois fichiers, dont la fiche du profil. Non carté.
- **Les mesures prises sur cet écran ne valent qu'après stabilisation du jeu** : le remplissage de
  fond fausse tout compte lu trop tôt. Constat d'usage, pas un défaut.

## Définition de terminé

- [x] Une rangée « Niveau déclaré, au moins » apparaît sous les langues, une fois une langue posée.
- [x] Elle disparaît, et se retire, quand la langue est retirée.
- [x] Les cinq paliers sont dans leur ordre d'échelle, sans nombre affiché.
- [x] Poser un palier garde les paliers supérieurs.
- [x] Langue et niveau portent sur la même entrée du profil — couvert par un test dédié.
- [x] Une entrée sans niveau déclaré est écartée dès qu'un palier est posé.
- [x] Le libellé existe dans les trois langues, et le test de parité est vert.
- [x] Les tests de la couche pure sont écrits et verts.
- [x] Vérifié à l'écran : « anglais » seul et « anglais + au moins courant » ne rendent pas le même
      jeu.
