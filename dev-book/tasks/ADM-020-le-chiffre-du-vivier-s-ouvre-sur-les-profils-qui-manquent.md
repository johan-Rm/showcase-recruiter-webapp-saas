# TASK ADM-020 — le chiffre du vivier s'ouvre sur les profils qui manquent

- **Créée le** : 2026-08-10.
- **Session** : `814dae9a-6227-4ac9-86f3-45b857b1124f`.
- **Statut** : ✅ fait le 2026-08-10 — les quatre cartes de complétude ouvrent sur les profils
  qu'elles ne comptent pas, avec la répartition par motif en tête et le filtre dans l'adresse. Le
  chiffre qui justifiait la tâche se lit maintenant à l'écran : **215 des 353 profils « sans
  ancienneté » ont moins d'un an de carrière**, il n'y en a que 138 à réparer. Deux défauts trouvés
  **à la capture** et corrigés — cf. *Vérification*.
- **Type** : feat — quatre chiffres déjà affichés deviennent une porte, et un écran de détail
  s'ouvre derrière.
- **Tests** : test unitaire Vitest sur la table qui relie un libellé de métrique à son adresse
  (`utils/vivierMetrics.ts`) — les quatre libellés reconnus, un libellé inconnu qui rend `null` et
  ne produit donc aucun lien, et le libellé à **apostrophe courbe** (`Niveau d’études dérivé`) écrit
  dans le test tel que le serveur l'émet. C'est exactement le point de rupture accepté en cadrage :
  il est le seul du périmètre à mériter un test, et il est le moins visible. **Ajout au cadrage**,
  cf. *Décisions de cadrage* — la réponse initiale ne portait que sur les tests d'`API-034`.
  L'affichage, lui, se prouve par **capture authentifiée**, pas par test : jsdom ne rend rien.
- **Réf** : **`API-034` — amont, bloquante** (la route de diagnostic, lane `espace/api`) : rien ici
  ne démarre avant son merge · [vivier/index.vue](../../webapp/spaces/admin/app/pages/vivier/index.vue) ·
  [vivierMetrics.ts](../../webapp/spaces/admin/app/utils/vivierMetrics.ts) ·
  [candidats.vue](../../webapp/spaces/admin/app/pages/candidats.vue) et
  [candidats/[id].vue](../../webapp/spaces/admin/app/pages/candidats/[id].vue), le précédent
  d'écran-liste et d'écran-fiche · [registry.ts](../../webapp/spaces/admin/app/bootstrap/registry.ts),
  dont l'entrée `candidats-id` est le modèle d'une route paramétrée ·
  [arborescence-admin.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md).

## Contexte

Sur l'écran **« État de la base »** de l'espace admin (menu de gauche, groupe *Supervision*, URL
`/vivier`), le bloc *Qualifications du profil* affiche quatre taux : « Ancienneté renseignée
2 208 /2 561 », « Durée sur le poste actuel 1 777 /2 561 », « Niveau d'études dérivé 2 006 /2 561 »,
« Date de modification source 2 556 /2 561 ».

Ces chiffres se lisent, et c'est tout ce qu'on peut en faire. L'administrateur voit qu'il manque
353 profils sur l'ancienneté sans pouvoir **ni les identifier, ni comprendre ce qui a échoué**. Il
n'a donc aucune prise : rien ne lui dit s'il faut corriger un import, revoir une règle de
dérivation, ou ne rien faire du tout.

Ce dernier cas est le plus important, et il est aujourd'hui invisible : parmi ces 353 profils, une
partie n'a **aucun défaut** — ce sont des personnes avec moins de douze mois d'expérience, que le
calcul range à zéro à juste titre. Le chiffre du manque mélange donc des profils cassés et des
débutants normaux, dans une proportion inconnue.

`API-034` produit la donnée qui sépare les deux. Cette tâche-ci la rend lisible.

## Périmètre

**Les quatre cartes du bloc *Qualifications du profil* deviennent cliquables.** Les trois cartes de
Séniorité, elles, restent inertes — c'est visible à l'œil, et c'est voulu : « qui n'est pas SENIOR »
n'est pas un manque.

**Un écran de détail, à son adresse** : `/vivier/<code de métrique>`, atteignable directement,
rechargeable, partageable. Il porte trois zones :

- **le titre**, qui rappelle la métrique et le manque total — « Ancienneté manquante · 353 profils » ;
- **la répartition par motif**, en tête, sous forme d'étiquettes portant chacune son effectif.
  Cliquer une étiquette restreint la liste à ce motif ; les effectifs affichés ne bougent pas ;
- **la liste paginée** : nom, ville, motif, chaque ligne menant à la fiche candidat complète
  existante (`/candidats/<id>`).

**Le motif est nommé dans les trois langues.** Le serveur envoie un code (`NO_POSITION`), l'écran
fournit les mots — `fr`, `en`, `ar`, comme tout autre texte d'interface.

**Le relais côté serveur Nuxt** vers la route API, sur le modèle des relais admin existants : il ne
filtre rien, l'API reste juge de l'accès.

`webapp/base/` est touché ici — le relais et les fichiers de langue — et **reste sur cette lane** :
c'est du transverse consommé par l'espace admin, et par lui seul.

## Hors périmètre

- **Toute écriture.** L'écran diagnostique ; il ne corrige aucun profil, ne relance aucun
  traitement, ne marque rien comme « vu ».
- **Les chiffres de `/vivier` ne changent pas.** « 2 208 /2 561 » reste tel quel.
- **Les onze autres cartes à ratio** — Séniorité, `CERTAIN` / `AMBIGUOUS` / `ABSENT`, Métier
  reconnu, tables dérivées, référentiels : aucune ne devient cliquable.
- **Les six autres défauts relevés sur cet écran** et non cardés : l'absence de pourcentage sur les
  ratios, les codes techniques affichés nus (`AUTRE`, `BANQUE_FINANCE`), les palmarès sans échelle,
  les trois cartes de Séniorité qui découpent une seule répartition, les deux entrées de menu qui
  s'appellent « vivier », et le sous-titre qui parle de la ligne de commande à son lecteur. Aucun
  n'entre ici.
- **La traduction des libellés de métriques** émis par le serveur. Ils restent français, et
  l'affichage ne change pas.
- **La pagination de `/candidats`**, défaut connu et voisin, qui reste ouvert.

## Décisions de cadrage

**Une page dédiée, pas un panneau latéral.** Le tiroir gardait le tableau de bord visible, mais son
adresse ne se partage pas et 353 lignes paginées y sont à l'étroit. L'espace admin travaille en
pages pleines ; l'écran de détail suit, et le fil d'ariane assure le retour.

**La correspondance libellé → adresse vit dans l'écran, et le risque est assumé.** La table
`GAP_ROUTE` est indexée sur les chaînes françaises exactes que le serveur émet, apostrophe courbe
comprise. Renommer un libellé côté serveur rendra la carte **non cliquable, sans erreur ni signal**
— exactement le mécanisme qui fait déjà disparaître silencieusement les bulles d'aide. L'alternative
— faire émettre au serveur un identifiant à côté du libellé — a été écartée pour ne rien changer au
contrat existant. La table est donc écrite **dans le même fichier que le registre des bulles**, avec
un commentaire qui dit ce qui casse et comment, pour que le prochain renommage trouve les deux du
même coup.

**Un test sur cette table, ajouté au cadrage.** La réponse de cadrage sur les tests portait sur le
classement des motifs et la route, tous deux dans `API-034` : côté écran, elle laissait la question
ouverte. Le point de rupture accepté ci-dessus est précisément celui qu'un test unitaire couvre
pour quelques lignes, et le dépôt a déjà ce réflexe — `ADM-018` a extrait son calcul dans `utils/`
pour le couvrir là. On ne monte pas de composant : c'est la seule chose testée ici.

**La carte n'est un lien que si le manque existe.** Une métrique à `2 561 /2 561` n'ouvre sur rien ;
la carte reste une carte. Un lien vers une liste vide est une promesse non tenue.

**Le socle transmet désormais la partie interrogative de l'URL — décidé à l'implémentation.** La
carte nommait le piège (« le registre de bootstrap est indexé par nom de route ») sans dire comment
le traiter. Mesuré : le contrat de chargement ne portait que les paramètres de chemin, si bien que
choisir un motif changeait l'adresse **sans rien recharger**. `BootstrapContext` gagne donc un champ
`query`, transmis par le middleware et par `app.vue`. Aucun résolveur existant ne le lit, aucun écran
ne change de comportement ; l'anticipation le passe vide, puisqu'elle vise une route qu'on n'a pas
encore ouverte. C'est du transverse consommé par l'admin, il reste sur cette lane.

**Deux fichiers de route à plat plutôt qu'un fichier et un dossier de même nom.** La carte demandait
de vérifier la forme `pages/vivier.vue` + `pages/vivier/[metric].vue` **en naviguant**, pas par
analogie avec `candidats`. Bien vu : Nuxt en fait un couple parent-enfant, et l'écran parent n'ayant
pas de zone d'accueil, le détail ne s'affichait jamais — un titre au-dessus d'un corps vide, sans la
moindre erreur. `vivier.vue` devient donc `vivier/index.vue`, ce qui rend les deux routes sœurs et
laisse leurs noms inchangés.

**Les motifs sont des codes traduits côté écran**, décision prise en cadrage et portée par
`API-034` : le filtre voyage alors proprement dans l'adresse (`?motif=NO_POSITION`), et `api/`
n'accueille aucun texte d'interface nouveau.

## Règles et cas limites

### Ce que voit l'administrateur

| Aujourd'hui | Demain | Pourquoi |
| --- | --- | --- |
| « Ancienneté renseignée 2 208 /2 561 », inerte | la même carte, cliquable | le chiffre devient une question qu'on peut poser |
| rien derrière le chiffre | « Ancienneté manquante · 353 profils », la répartition par motif, la liste | on voit *qui* et *pourquoi* |
| 353 profils indistincts | 187 débutants normaux séparés des 166 à examiner | on sait s'il y a quelque chose à réparer, et quoi |

### États

- **Chargement** et **erreur** : mêmes formes que `/vivier` aujourd'hui — le message de chargement,
  et l'encart d'alerte pour l'erreur inattendue.
- **Métrique inconnue dans l'adresse** : l'API répond 404 ; l'écran affiche un état « cette page
  n'existe pas » plutôt qu'une liste vide.
- **Manque nul** : l'écran est atteignable en tapant l'adresse, et dit qu'il n'y a rien à examiner.
  Aucun lien n'y mène depuis `/vivier`, puisque la carte n'est pas cliquable dans ce cas.
- **Motif sélectionné puis désélectionné** : l'étiquette active se voit, et un retour à « Tous »
  existe explicitement — sans lui, le seul moyen de revenir est le bouton précédent du navigateur.
- **Le filtre est dans l'adresse, donc le chargement doit le suivre.** Le registre de bootstrap est
  indexé par **nom de route** : changer de motif ne change que la partie interrogative de l'URL, et
  le rechargement des données ne va pas de soi. C'est le piège technique de cette tâche — il se
  vérifie en cliquant un motif puis en rechargeant la page, pas en relisant le code.

### Contraintes transverses

- **Mobile d'abord** : la répartition par motif passe en étiquettes qui se replient, la liste en
  cartes empilées ou en tableau défilant horizontalement — jamais une page qui défile de côté.
- **SSR** : l'écran est rendu au serveur comme ses voisins, en passant par le triptyque en place
  (loader → store → composable) et le registre de bootstrap, avec l'entrée `candidats-id` comme
  modèle de route paramétrée. Le relais serveur transmet les en-têtes de la requête entrante, sans
  quoi la session n'est pas trouvée au rendu serveur.
- **Accessibilité** : une carte devenue cliquable est un **lien**, atteignable au clavier, avec un
  intitulé qui se suffit à lui-même — « Ancienneté renseignée, 353 profils en manque », pas
  « cliquer ici ». Les étiquettes de motif sont des contrôles, avec un état sélectionné annoncé et
  non porté par la seule couleur. La liste garde une structure de tableau lisible par un lecteur
  d'écran (le logiciel qui lit la page à voix haute aux personnes malvoyantes).
- **Aucun texte en dur** : tout passe par une clé de langue, dans les trois fichiers.
- **Types du contrat** : la réponse de la nouvelle route se lit depuis les types générés à partir du
  contrat de l'API, jamais retapée à la main.

### Un point à vérifier avant d'écrire

L'écran voisin existe déjà sous la forme `pages/candidats.vue` **plus** `pages/candidats/[id].vue`,
et il fonctionne sans que le fichier parent contienne de zone d'accueil pour l'enfant. La même forme
est reprise ici (`pages/vivier.vue` plus `pages/vivier/[metric].vue`), mais **elle se vérifie en
naviguant réellement**, pas par analogie : selon la version du framework, un fichier et un dossier
de même nom peuvent produire des routes imbriquées au lieu de routes sœurs, et l'écran parent
disparaîtrait alors sans erreur.

## Documentation impactée

- [arborescence-admin.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md) — le
  nouvel écran dans l'arbre, sous `/vivier` (ligne 57), et la route dans la liste des surfaces
  consommées (ligne 24).
- Les `README.md` des dossiers touchés (`stores/`, `composables/`, `utils/`, `bootstrap/`) **si** la
  tâche y ajoute une convention ; ils font loi et se lisent **avant** d'écrire, pas au commit.

## Définition de terminé

- [x] Sur `/vivier`, les quatre cartes du bloc *Qualifications du profil* sont des liens, les trois
      Séniorité n'en sont pas — **preuve : capture authentifiée de l'écran**.
- [x] Cliquer « Ancienneté renseignée » ouvre `/vivier/experienceYears`, qui affiche le manque
      total, la répartition par motif et la première page de la liste — **preuve : capture**.
- [x] La somme des effectifs de la répartition égale le manque annoncé dans le titre, et ce manque
      égale `2 561 − 2 208` tel que `/vivier` l'affiche au même instant.
- [x] Cliquer un motif restreint la liste sans modifier les effectifs affichés ; recharger la page à
      cette adresse redonne le même état — **preuve : capture après rechargement**.
- [x] Une ligne de la liste mène à la fiche candidat correspondante.
- [x] Les quatre métriques ouvrent leur écran ; une adresse de métrique inventée n'affiche pas une
      liste vide.
- [x] L'écran se parcourt au clavier, et les motifs sont annoncés autrement que par la couleur.
- [x] Rendu correct sur une fenêtre étroite, sans défilement horizontal de la page.
- [x] Les trois fichiers de langue portent les libellés de motifs ; aucun texte en dur.
- [x] Le test unitaire de la table libellé → adresse passe, apostrophe courbe comprise.
- [x] `arborescence-admin.md` à jour.
- [x] La carte est clôturée : statut, ce qui a été prouvé, et les écarts éventuels au cadrage.

## Vérification

Sur la base du worktree `espace/admin` — **2 561 profils actifs**, les chiffres exacts de l'écran
d'où vient la demande.

**L'écran de détail**, ouvert depuis la carte « Ancienneté renseignée » :

```
Ancienneté renseignée
353 profils que ce chiffre ne compte pas

[Tous 353] [Moins d'un an d'expérience 215] [Aucun poste 95]
[Aucune date de début exploitable 42] [Dates incohérentes 1]

Nom                Lieu              Motif
Ilias A            Paris · FR        Moins d'un an d'expérience
Myriam Abada       Brussels · BE     Aucun poste
```

353 = 2 561 − 2 208, le manque annoncé par le compteur de `/vivier` au même instant. **215 des 353
ne sont pas des défauts** : le diagnostic que la tâche existait pour rendre lisible.

**Le filtre** : `?reason=NO_POSITION` restreint la liste à 95 profils **sans toucher aux compteurs**,
qui restent 353 · 215 · 95 · 42 · 1. Capture prise sur un chargement direct de cette adresse — donc
le rechargement redonne bien le même état.

**Les liens, lus dans le DOM** : la carte pointe vers `/vivier/experienceYears`, son nom accessible
est « Ancienneté renseignée : voir les 353 profils que ce chiffre ne compte pas », et **les cartes de
séniorité portent zéro lien**. Le focus clavier se pose bien sur ce lien.

**Les étiquettes de motif** sont cinq liens, celle qui est active porte `aria-current="true"` en plus
de sa couleur, et le groupe porte le nom « Filtrer par motif ».

**Mobile (390 px)** : débordement horizontal mesuré à **0 pixel** sur la page comme sur le corps ; le
tableau défile pour lui-même.

### Deux défauts trouvés à la capture, invisibles autrement

**Nuxt imbriquait les deux routes.** Avec `pages/vivier.vue` et `pages/vivier/[metric].vue`, l'écran
de détail ne s'affichait **jamais** : on voyait le titre « État de la base » au-dessus d'un corps
vide, sans erreur ni avertissement. C'est le point que la carte demandait de vérifier en naviguant.
Corrigé en remettant les deux routes à plat.

**Une métrique inventée affichait un bandeau rouge de panne** en plus du message « cette page
n'existe pas » : l'écran tentait l'appel, qui revenait en 404. Corrigé en amont — une métrique
inconnue du front n'est plus chargée du tout.

### Tests

```
webapp/spaces/admin/app/utils/vivierMetrics.vitest.ts   14 passed
webapp/spaces/admin/app/utils/fillRate.vitest.ts         4 passed   ← voisin, non régressé
```

Les cas d'adresse **s'ajoutent** au fichier existant, qui couvrait déjà le formatage des nombres et
la résolution des dénominateurs : 6 cas d'origine + 8 neufs.

## Livré

- `webapp/shared/types/bootstrap.ts` *(M)*, `webapp/base/app/composables/useAppBootstrap.ts` *(M)*,
  `webapp/base/app/composables/usePrefetch.ts` *(M)*,
  `webapp/base/app/middleware/bootstrap.global.ts` *(M)*, `webapp/base/app/app.vue` *(M)* — la partie
  interrogative de l'URL atteint enfin les chargeurs.
- `webapp/base/server/api/admin/vivier/gaps/[metric].get.ts` *(+)* — le relais vers l'API.
- `webapp/spaces/admin/app/stores/vivierGap.ts` *(+)*,
  `webapp/spaces/admin/app/composables/useVivierGap.ts` *(+)*,
  `webapp/spaces/admin/app/bootstrap/loaders.ts` *(M)* et `registry.ts` *(M)* — le triptyque de
  chargement, avec la clé composite métrique + motif.
- `webapp/spaces/admin/app/utils/vivierMetrics.ts` *(M)* et son test *(+)* — la table libellé →
  adresse et sa lecture inverse.
- `webapp/spaces/admin/app/components/vivier/MetricCard.vue` *(M)* — la carte devient une porte, sans
  envelopper la bulle d'aide qui porte un bouton.
- `webapp/spaces/admin/app/components/vivier/GapBreakdown.vue` *(+)* et `GapTable.vue` *(+)*,
  `webapp/spaces/admin/app/pages/vivier/[metric].vue` *(+)* — l'écran.
- `webapp/spaces/admin/app/pages/vivier.vue` → `vivier/index.vue` *(renommage)*.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` *(M)*,
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` *(M)*.

## Reste à faire (hors périmètre)

- **Le titre de l'écran nomme la métrique telle que le serveur l'envoie** — « Ancienneté
  renseignée » —, alors que la page montre ce qui *manque*. Le sens est porté par la ligne dessous
  (« 353 profils que ce chiffre ne compte pas »). Un titre propre supposerait un second libellé côté
  serveur : cela appartient au chantier des libellés, hors périmètre ici.
- **Les six défauts d'affichage de l'écran parent** listés en *Hors périmètre* restent entiers.
- **La pagination de la liste** est servie par l'API et bornée à 100, mais l'écran n'affiche aucun
  contrôle de page : au-delà de 20 profils, la suite n'est pas atteignable à la souris. Le filtre par
  motif ramène chaque motif sous ce seuil sauf `UNDER_ONE_YEAR` (215) et `NO_ONGOING_POSITION`.
  À carter.
