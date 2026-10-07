# TASK ENT-034 — entreprise : le volume se lit avant de lancer la recherche

- **Créée le** : 2026-07-31.
- **Session** : `57a6103a-e57e-429e-82e9-aed454da48f3` — **reprise** le 2026-08-02, sur instruction humaine. Le cadrage
  vient d'une autre session : les décisions déjà inscrites ne sont pas les miennes, et je
  les relis avant d'implémenter plutôt que de les découvrir au commit.
- **Statut** : ✅ fait le 2026-08-02 — le formulaire annonce ce que la combinaison ramène pendant
  qu'on le remplit, et ne laisse plus partir un lancement sans critère.
- **Type** : Frontend espace entreprise — ajout de fonctionnalité.
- **Tests** : `vitest` sur `services/search/volume.ts` — l'agrégation de critères en paramètres
  d'URL et l'empreinte qui périme une réponse sont du code **pur**. Le débounce et l'affichage, eux,
  se prouvent au navigateur : rien d'unitaire ne dirait qu'une frappe n'a pas déclenché un appel.
- **Réf** : [criteres_de_filtrage_profils.md § Moteur](../cadrage/entreprise/criteres_de_filtrage_profils.md)
  — les compteurs facettés · [index.vue](../../webapp/spaces/entreprise/app/pages/index.vue) —
  « lancer, c'est créer » · `ENT-033` (les facettes qui bénéficieraient des compteurs)

## Contexte

**On remplit le formulaire à l'aveugle, et chaque essai laisse une trace.**

Le formulaire prend soin d'afficher un **compte de profils par suggestion** — le commentaire du code
l'argumente bien : sans lui, on pose « communication », 28 % du vivier, en croyant restreindre. Mais
il n'existe **aucun compte pour la combinaison**. On empile quatre critères et on découvre le
résultat après coup.

Or **lancer, c'est créer** : une recherche s'auto-enregistre dès le premier critère, il n'y a pas
d'action « Enregistrer » côté produit. Trois tâtonnements laissent donc trois recherches persistées,
que le recruteur devra distinguer et nettoyer. Le geste d'exploration et le geste d'engagement sont
le même clic.

Le cadrage demande d'ailleurs déjà mieux : « chaque option affiche son résultat *tous les autres
filtres appliqués sauf le sien*. Une option à `0` est grisée. → l'API doit exposer des **agrégats
par facette**, pas seulement une liste de résultats. »

## Le blocage — levé le 2026-08-02

**Il n'y avait aucun moyen de compter sans créer**, et cette carte le tenait pour un manque d'API. Ce
diagnostic était **faux à moitié** : `GET /v1/search/candidates` accepte dix-huit filtres en
paramètres et rend `total` sans rien persister — le comptage sans effet de bord existait déjà. Ce qui
manquait était bien plus étroit : l'endpoint ne lisait qu'**un** mot-clé, là où le formulaire en
accumule.

`API-020` l'a corrigé et est mergée. Il n'y avait donc pas d'endpoint à créer, et la crainte d'une
route de plus à documenter et sécuriser tombe avec.

**Ce qui reste hors de cette carte** : les **agrégats par facette** que le cadrage réclame — « chaque
option affiche son résultat, tous les autres filtres appliqués sauf le sien ». Ils servent `ENT-033`,
demandent une requête par facette ou une agrégation dédiée, et se mesurent avant d'être promis.

## Périmètre

- Interroger le comptage pendant que le formulaire se remplit, **débouncé**, et afficher le volume
  près du bouton de soumission.
- Traiter l'état « zéro profil » **avant** le clic plutôt qu'après la création.

## Hors périmètre

- **Le comptage lui-même** et les agrégats : `espace/api`. Le premier est livré par `API-020` ;
  les seconds restent à carter.
- **Les compteurs par option** (une valeur par choix possible) : ils dépendent des agrégats
  facettés, pas du volume global. Aval.
- **Changer « lancer, c'est créer »** : la règle produit reste. Cette carte lui donne le retour
  qui lui manque, elle ne la remplace pas.
- **Le nettoyage des recherches d'essai déjà créées.**

## Décisions de cadrage — les trois arbitrages, tranchés

### Compter à chaque changement de critère, débouncé à 400 ms

Pas « seulement sur les facettes structurantes » : le recruteur ne sait pas lesquelles le sont, et un
volume qui ne bouge qu'une fois sur trois se lit comme un volume en panne.

Le délai est **plus long que celui de l'auto-complétion** (220 ms), et c'est délibéré : celle-ci
accompagne la frappe et doit suivre la main, celui-ci suit un formulaire qu'on remplit champ par
champ. Mesuré au navigateur : une trentaine de frappes et dix pressions de touche sur le curseur
n'ont produit que **trois** appels.

Deux gardes s'ajoutent, dans le service : une **empreinte** des critères écarte les réponses périmées
— deux comptages en vol reviennent parfois dans le désordre, et c'est l'état courant du formulaire
qui fait foi — et **évite de recompter à l'identique** quand un champ bouge sans changer les critères
(un curseur ramené à zéro, un niveau d'études coché puis décoché).

### Deux « zéros » distincts, deux réponses opposées

Le cadrage posait une seule question ; l'écran en pose deux.

- **Zéro critère** → la soumission se **ferme**. C'est ce qui lève l'incohérence visée par la carte :
  la page répondait `search.create.noCriteria` *après* le clic, un refus qu'on ne pouvait pas
  anticiper. Le formulaire sait dès la première frappe qu'il n'a rien à chercher.
- **Zéro profil** → la soumission **reste ouverte**, avec « Aucun profil ne correspond ». Un volume
  est une aide, pas une garde : on peut vouloir enregistrer une recherche qui ne ramène rien
  aujourd'hui et se remplira demain, et une panne de comptage ne doit rien empêcher.

### Le compte est exact parce qu'il emprunte le même chemin

Il **doit** tenir compte des filtres implicites, sans quoi il mentirait. Il le fait, non par
convention mais par construction : le comptage appelle l'endpoint de recherche, et
`CandidateRepository::search()` comme `matchingIds()` — celui qui matérialise le jeu d'une recherche
enregistrée — partagent le même `searchableQuery()` : profils **visibles**, **actifs**, **consentis**.

Reste une réserve honnête : le jeu d'une recherche est matérialisé à l'instant du lancement. Un
vivier qui bouge entre l'annonce et le clic fera diverger les deux, comme il ferait diverger deux
lancements successifs. Au moment du clic, ils coïncident.

### Le volume descend en prop, il ne se compte pas dans le formulaire

`<SearchFiltersPrimary>` sert aussi à **ajuster** une recherche existante, dans le tiroir « Affiner ».
Cette recherche-là porte des critères que le formulaire ne montre pas — une langue, un pays, un
séjour au Maroc —, et un volume calculé sur ses seuls champs y serait **trop large**.

Le formulaire émet donc `change` et reçoit `volume` ; l'accueil compte, le tiroir ne compte pas. Un
compte absent est lisible ; un compte faux ne l'est pas.

### Le moteur assisté ne compte pas — pas encore

Le comptage suit un formulaire qui se **remplit**. Les critères du moteur assisté arrivent d'un bloc
après analyse de la phrase : il n'y a pas de remplissage à accompagner, et lui greffer le même
mécanisme demanderait de décider ce qu'on compte quand un critère est écarté à l'écran. Aval nommé,
hors de cette carte.

## Règles et cas limites

- **Un compte n'est pas un résultat** : il ne dévoile rien, ne consomme aucun crédit, et n'a pas à
  respecter le masquage champ par champ.
- **Aucun critère = aucun compte.** Le vivier entier n'est pas un résultat de recherche, la page le
  dit déjà.
- **Réponses dans le désordre** : c'est l'état courant du formulaire qui fait foi, pas l'ordre
  d'arrivée — même règle que l'auto-complétion.
- **Une panne du comptage ne bloque pas le lancement.** Le volume est une aide, pas une garde.
- **Aucun texte en dur**, trois locales, pluriel géré.

## Documentation impactée

**Aucune page.** Vérifié déclencheur par déclencheur : la surface de l'API Symfony ne bouge pas — la
cardinalité de `?q` a été documentée par `API-020` —, aucun type généré ne change, et les standards
`composables-standard.md` / `services-standard.md` fixent des règles sans inventorier les fichiers,
qu'un composable et un service de plus ne rendent donc pas faux. Aucune page n'inventorie les routes
BFF. Aucune convention de dossier ne change, donc aucun `README.md`.

## Définition de terminé

- [x] L'amont API existe et est mergé — `API-020`, sur `develop` depuis le 2026-08-02.
- [x] Les trois arbitrages sont tranchés et inscrits ci-dessus, avec ce qui les a décidés.
- [x] Le volume s'affiche près du bouton, débouncé, et suit les critères.
- [x] L'état « zéro » est traité avant le clic ; le refus qui n'arrivait qu'APRÈS le clic est levé.
- [x] Une panne du comptage laisse lancer — le bouton ne dépend que de la présence de critères.
- [x] Trois locales à jour.
- [x] `bun run quality-check` — vert à l'ouverture de la PR : eslint **0 erreur**, prettier **tous
      les fichiers conformes**, type-check **des quatre espaces** passé. Suite vitest complète :
      **106 tests, 14 fichiers, verts**.

## Vérification

**Au navigateur**, session recruteur réelle (Chromium, `fr-FR`, vivier de 3 052 profils) — le seul
endroit où le débounce et l'absence de création se constatent :

| Geste | Ce que l'écran dit | Ce que l'API rend |
| --- | --- | --- |
| Formulaire vide | bouton **désactivé** | — |
| « Chef de projet » | 515 profils correspondent | `?job=Chef de projet` → **515** |
| + 10 ans d'expérience | 199 profils correspondent | `&experienceMin=10` → **199** |
| « Cosmonaute de la lune » | Aucun profil ne correspond, **bouton actif** | 0 |

Trois faits qu'aucune capture ne montrerait, relevés dans la même session :

- **trois** appels de comptage pour une trentaine de frappes et dix pressions de touche sur le
  curseur — le débounce tient ;
- l'**URL n'a pas bougé** de l'accueil : rien n'a été créé pendant tout le tâtonnement, ce qui est
  l'enjeu même de la carte ;
- le cumul remonte jusqu'au bout de la chaîne : `?q[]=php&q[]=java` → **463** à travers le BFF,
  contre 193 et 435 pris séparément.

**Tests** : `services/search/volume.vitest.ts`, **9 tests verts** — la clé de tableau des mots-clés,
les valeurs vides écartées, et l'empreinte qui périme une réponse hors délai.

## Livré

- `webapp/services/search/volume.ts` **(+)** — critères → paramètres d'URL, mots-clés en `q[]`, et
  l'empreinte qui décide qu'une réponse est périmée. Métier pur.
- `webapp/services/search/volume.vitest.ts` **(+)** — les neuf cas ci-dessus.
- `webapp/base/server/api/candidates/count.get.ts` **(+)** — relais vers l'endpoint de recherche,
  `perPage=1`, réduit au seul total.
- `webapp/spaces/entreprise/app/composables/useSearchVolume.ts` **(+)** — débounce à 400 ms,
  dernier-gagne, panne non bloquante, aucun appel sans critère.
- `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue` **(M)** — émet `change`,
  reçoit `volume` et `submitDisabled` ; la ligne de volume se pose contre le bouton.
- `webapp/spaces/entreprise/app/pages/index.vue` **(M)** — orchestre le comptage et ferme la
  soumission à vide ; ouvrir le moteur repart d'une ardoise nette.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — quatre clés, pluriel géré.

## Reste à faire (hors périmètre)

- **Les agrégats par facette** — « chaque option affiche son résultat, tous les autres filtres
  appliqués sauf le sien ». Tâche `espace/api` à écrire ; ils débloquent `ENT-033`.
- **Le moteur assisté**, qui ne compte pas : ses critères arrivent d'un bloc, il n'y a pas de
  remplissage à accompagner (argumenté plus haut).
- **Le nettoyage des recherches d'essai déjà créées**, déjà hors périmètre au cadrage.

## Amont

- **Un comptage à partir de critères, sans persistance** — **levé** : il existait déjà, il ne savait
  pas lire plusieurs mots-clés. `API-020`, mergée le 2026-08-02.
- **Les agrégats par facette** demandés par le cadrage — `espace/api`. Non bloquant pour cette carte,
  bloquant pour les compteurs par option.
