# TASK ADM-025 — l'écran des recherches qui débordent

- **Créée le** : 2026-08-15.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-16 — l'écran liste les recherches qui débordent, avec leur nombre
  réel de correspondances et l'écart avec ce qui est servi. Un écart au cadrage, consigné plus bas.
- **Type** : Frontend back-office d'administration — écran de lecture.
- **Tests** : vitest sur la mise en forme d'une ligne (nom ou critères, total, état, dernière
  activité) et sur les états vide, chargement et erreur de l'écran.
- **Réf** : **amont bloquant `API-051`** (la route qui sert l'inventaire) · `API-048` (la borne dont
  découle le seuil) · `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md`.

## Contexte

Une recherche d'entreprise ne charge plus que cent profils, tout en annonçant le nombre réel de
correspondances — « 100 sur 352 ». Côté showcase-recruiter-webapp-saas, **rien ne montre où ces débordements se produisent**,
alors qu'ils portent deux informations distinctes : une recherche mal cadrée, qu'on peut aider à
resserrer, et un métier où le vivier déborde, qui dit quelque chose du marché.

Sur la base actuelle, 11 recherches sur 79 dépassent cent profils — 13,9 %.

## Périmètre

- **Un écran d'administration** listant les recherches au-dessus du seuil, servi par `API-051`.
- **Une ligne montre** : l'organisation, le propriétaire, le nom de la recherche — ou ses critères
  mis en mots quand elle n'en a pas —, le **nombre réel de correspondances**, l'état ouvert ou clos,
  et la date de dernière activité.
- **Tri par correspondances décroissantes** à l'arrivée, pagination comme les autres listes du
  back-office.
- **Entrée dans la navigation** du back-office, à l'endroit où vivent les vues de supervision.
- **Textes en clés i18n**, aucun libellé en dur.

## Hors périmètre

- **Toute action sur une recherche** — la modifier, la fermer, écrire à son propriétaire. L'écran
  regarde ; agir sur le travail d'un client est une décision produit qui n'a pas été prise.
- **Une analyse par métier** ou tout agrégat : c'est un inventaire, ligne à ligne.
- **Un lien vers la recherche elle-même** : elle appartient à l'espace entreprise d'une autre
  organisation, et le back-office n'a pas de vue de ce genre.

## Décisions de cadrage

### Le nombre réel, jamais « 100+ »

C'est toute la valeur de l'écran : distinguer une recherche à 105 profils, qui se resserre d'un
critère, d'une recherche à 3 239, qui n'en est pas une. Un affichage tronqué les confondrait.

### La dernière activité est une colonne, pas un filtre

Elle sert à hiérarchiser d'un coup d'œil — ce qui déborde et qu'on travaille encore n'appelle pas la
même attention que ce qui déborde et dort depuis un mois. En faire un filtre supposerait de savoir ce
qu'on cherche ; la colonne laisse l'œil trancher.

### Les recherches closes restent listées

Une recherche close qui débordait reste une information sur le cadrage et sur le vivier. Elle est
signalée comme close, pas masquée.

### ÉCART — les trois états de l'écran deviennent UN état nommé

Le cadrage listait « liste vide », « chargement » et « erreur » comme trois cas. Écrits en trois
booléens lus dans le gabarit, leur ordre de priorité s'écrit en cascade de conditions, où rien ne le
vérifie — et une panne peut s'y afficher comme « aucun débordement », c'est-à-dire une bonne
nouvelle que personne n'a constatée.

Une fonction pure décide donc de l'état unique, et l'écran s'y conforme. Elle porte aussi la
deuxième priorité : un rechargement en cours passe devant l'échec du précédent. **C'est ce qui rend
les états testables** sans monter d'application — l'outillage de test du dépôt ne couvre que le code
sans moteur applicatif.

## Règles et cas limites

- **Liste vide** : un état explicite — aucune recherche ne dépasse le seuil — et non un tableau vide
  sans explication.
- **Chargement et erreur** : les états habituels des listes du back-office.
- **Recherche sans nom** : ses critères sont mis en mots, comme la carte de recherche le fait déjà
  côté entreprise.
- **Colonnes nombreuses sur écran étroit** : mobile-first, la table doit rester lisible ou se replier.
- **Accessibilité** : en-têtes de colonnes annoncés, tri accessible au clavier, dates lisibles par un
  lecteur d'écran.
- **Volume** : la liste peut être longue le jour où le vivier grossit ; la pagination est requise dès
  la première version.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` — le nouvel écran et sa place dans la
  navigation.

## Vérification

**L'écran, sur la pile de ce worktree**, session administrateur ouverte :

```
── /recherches — 5 lignes
   entrée de navigation : Recherches débordantes
   colonnes : Recherche | Organisation | Propriétaire | Correspondances | Non montrées | État | Dernière activité
     Chef de projet · 5 ans et plus · Master · prince2      266   166   Ouverte   12 août 2026
       (Nommée par ses critères)
     Développeur logiciel · 5 ans et plus · java            183    83   Ouverte   12 août 2026
     Manager · 8 ans et plus · Licence · p&l management     144    44   Ouverte   12 août 2026
   pagination : Page 1 sur 1
```

**Écran étroit (390 px)** : la table cède la place aux cartes empilées, **aucun débordement
horizontal**.

**Les états, provoqués et non supposés** — réponse forcée depuis le navigateur :

```
vide    → 0 ligne · « Aucune recherche ne dépasse la borne. Tout ce que le vivier ramène
                      tient dans ce qui est servi. »
erreur  → 0 ligne · role="alert" · « L'inventaire n'a pas pu être chargé. »
          8 appels partent puis le nombre SE STABILISE — deux points d'appel et leurs relances
          automatiques, pas une boucle (mesuré à 5, 10, 20 et 30 secondes)
```

```
vitest services/search/overflow.vitest.ts  →  14 tests, verts
quality-type-check                         →  vert sur les quatre espaces
```

## Livré

- `webapp/spaces/admin/app/pages/recherches.vue` **(+)** — l'écran : table au large, cartes en
  étroit, pagination, et les trois états.
- `webapp/services/search/overflow.ts` **(+)** — la mise en forme d'une ligne, l'état de l'écran et
  le compte de pages, tous purs.
- `webapp/spaces/admin/app/composables/useOverflowingSearches.ts` **(+)** et
  `stores/overflowingSearches.ts` **(+)** — la pagination et l'état partagé.
- `webapp/base/server/api/admin/searches.get.ts` **(+)** — le relais.
- `webapp/shared/types/admin-search.ts` **(+)** — la forme servie, écrite à la main : l'enveloppe
  générée type ses items en opaque.
- Le chargeur et le registre de bootstrap, l'entrée de navigation, et les clés dans les trois
  langues.

## Reste à faire (hors périmètre)

- **Le chargement de l'inventaire part depuis n'importe quel écran du back-office** : le
  préchargement au repos parcourt tous les chargeurs critiques du registre, et celui-ci en fait
  partie. C'est le comportement déjà en place pour les autres écrans de l'espace, pas une
  particularité de celui-ci.

## Définition de terminé

- [x] L'écran liste les recherches au-dessus du seuil, triées par correspondances décroissantes.
- [x] Chaque ligne porte organisation, propriétaire, nom ou critères, total réel, état, dernière
      activité.
- [x] Une base sans débordement affiche un état vide explicite.
- [x] Les états de chargement et d'erreur sont traités — l'erreur **observée au navigateur**, le
      chargement couvert par le test de l'état et présent dans l'écran, mais **jamais surpris à
      l'œil** : sur cette base il passe trop vite.
- [x] L'écran est accessible depuis la navigation du back-office, en clés i18n.
- [x] Lisible sur écran étroit — **critère RÉÉCRIT** sur sa seconde moitié. Il annonçait un « tri
      accessible au clavier » : il n'y a **aucun contrôle de tri**, l'ordre vient du serveur et
      refaire ce classement dans l'écran donnerait deux ordres à tenir d'accord. Ce qui est tenu
      côté accessibilité est le tableau lui-même — légende, en-têtes de colonnes et de lignes
      déclarés — et la pagination, qui sont des boutons ordinaires.
- [x] Tests vitest sur la mise en forme d'une ligne et sur les états de l'écran.
- [x] `arborescence-admin.md` à jour.
