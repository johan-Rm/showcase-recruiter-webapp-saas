# TASK ADM-026 — l'écran qui dit qui a regardé qui

- **Créée le** : 2026-08-18.
- **Session** : `87c642c6-d8b6-4447-954e-5e3e0b6db5c9`.
- **Statut** : ✅ fait le 2026-08-19 — l'écran *Activité* liste les visites de profil, du plus
  récent au plus ancien, chaque ligne disant si l'organisation a fini par dévoiler le candidat.
- **Type** : Frontend back-office d'administration — écran de lecture.
- **Tests** : vitest sur la mise en forme d'une ligne (date, recruteur, organisation, candidat,
  mention de dévoilement) et sur l'état unique de l'écran — vide, chargement, erreur.
- **Réf** : **amont bloquant `API-073`** (la route qui sert le fil), elle-même dépendante de
  `API-072` (l'enregistrement) · `requirements/brief/initial_brief.md` §4 ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (`/activite`, proposé) · `ADM-025`
  (l'écran de liste le plus récent, à prendre pour modèle).

## Contexte

Le brief demande « l'historique des profils visités ». Une fois les visites enregistrées (`API-072`)
et servies (`API-073`), il manque l'endroit où les regarder : la carte de navigation prévoit un écran
*Activité*, marqué proposé et jamais construit.

Ce que l'écran doit rendre lisible d'un coup d'œil : qui s'intéresse à qui, et si cet intérêt est
allé jusqu'au dévoilement. C'est ce que le brief appelle « préparer nos futurs indicateurs ».

## Périmètre

- **Un écran *Activité*** dans le back-office, servi par `API-073` : le fil des visites, du plus
  récent au plus ancien.
- **Une ligne montre** : quand, quel recruteur et de quelle entreprise, quel candidat, et si ce
  profil a été dévoilé par cette entreprise.
- **Chargement par tranches** en descendant, comme les autres listes du back-office.
- **Entrée dans la navigation**, dans le groupe où vivent les vues de supervision. Elle s'ajoute dans
  `useNav`, seule source de la barre latérale — l'entrée apparaît alors d'un coup dans la barre, le
  tiroir mobile et le fil d'ariane.
- **Textes en clés i18n** dans les trois langues, aucun libellé en dur.

## Hors périmètre

- **Filtrer** par entreprise, par candidat ou par période : la première version est un fil. Ajouter
  des filtres suppose de savoir ce qu'on cherche ; l'usage le dira.
- **Agir** sur une ligne : écrire au recruteur, ouvrir la fiche du candidat, exporter. L'écran regarde.
- **Tout agrégat ou classement** — « profil le plus consulté », courbe, compteur de tête.
- **La fiche de l'entreprise** et ses crédits : c'est `ADM-027`.

## Décisions de cadrage

### Un fil, pas un tableau de bord

Le brief dit « voir en temps réel » et « préparer nos indicateurs ». Un fil chronologique répond aux
deux : il se lit sans apprentissage, et il montre la matière brute à partir de laquelle les
indicateurs seront définis plus tard. Un tableau de bord supposerait de savoir déjà quoi mesurer.

### Le nom du candidat en clair

L'administration voit les profils non masqués — invariant de l'espace. Un fil d'initiales serait
illisible.

### Un état unique, décidé par une fonction pure

Même règle que `ADM-025` : vide, chargement et erreur ne sont pas trois conditions empilées dans le
gabarit. Une fonction pure décide de l'état, et l'écran s'y conforme. Sans ça, une panne peut
s'afficher comme « aucune visite » — une bonne nouvelle que personne n'a constatée. C'est aussi ce
qui rend les états testables sans monter d'application.

## Règles et cas limites

- **Aucune visite** : un état vide explicite qui dit ce qu'il signifie — personne n'a encore ouvert
  de fiche —, pas un tableau vide sans explication.
- **Chargement et erreur** : les états habituels des listes du back-office, l'erreur annoncée aux
  lecteurs d'écran.
- **Recruteur ou entreprise supprimé** : la ligne reste lisible, elle ne disparaît pas et ne casse pas.
- **Écran étroit** : mobile-first, la table se replie en cartes empilées, **aucun débordement
  horizontal**.
- **Accessibilité** : en-têtes de colonnes annoncés, dates lisibles par un lecteur d'écran.
- **Volume** : le fil est potentiellement très long ; le chargement par tranches est requis dès la
  première version, et l'écran ne doit pas tout garder en mémoire indéfiniment.

## Écarts au cadrage

### Le fil se pagine par boutons, il ne se charge pas en descendant

La carte annonçait un « chargement par tranches en descendant, **comme les autres listes du
back-office** ». Les deux moitiés de la phrase se contredisaient : aucune liste du back-office ne
charge en descendant, toutes paginent par deux boutons. C'est la **cohérence** qui a été retenue —
l'intention de la carte —, et non la mécanique qu'elle nommait par erreur.

### Le compteur ne s'affiche plus quand le fil n'a pas pu être lu

Trouvé en provoquant la panne : l'écran annonçait « 0 consultation(s) de profil » **au-dessus** du
message d'échec. Il affirmait que personne ne consulte les profils, au moment précis où il venait de
ne rien pouvoir lire. Le compte n'est désormais montré que lorsqu'il est connu.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` — `/activite` passe de proposé à
  livré, avec sa place dans la navigation.

## Vérification

**L'écran, sur la pile de ce worktree**, session administrateur, dix visites produites par de vrais
appels (trois recruteurs × trois fiches) :

```
── /activite — 10 lignes, entrée « Activité » dans la barre latérale (Supervision), état actif
   colonnes : Quand | Recruteur | Entreprise | Candidat consulté | Dévoilé
     19 août 2026 à 08:06  Leila Mansouri   Atlas Talent Conseil  Achraf Khalki     Non dévoilé
     19 août 2026 à 08:06  Leila Mansouri   Atlas Talent Conseil  Reda Elhasnaoui   Dévoilé
     19 août 2026 à 08:06  Omar Alaoui      Atlas Talent Conseil  Achraf Khalki     Non dévoilé
     19 août 2026 à 08:06  Sara Bennani     showcase-recruiter-webapp-saas Demo SARL    Walid Bakir       Non dévoilé
   pagination : Page 1 sur 1
```

Deux entreprises sur le **même** candidat sortent avec une mention différente : c'est l'écart que
l'écran doit rendre lisible.

**Écran étroit (390 px)** : la table cède la place aux cartes empilées, candidat en tête, **aucun
débordement horizontal**.

**Les états, provoqués à la source et non simulés** — l'interception côté navigateur ne voit rien,
le fil étant chargé au SSR par le préchargement :

```
vide    base vidée de ses visites
        → « 0 consultation(s) de profil » + « Aucune consultation enregistrée pour le moment. »
erreur  conteneur de l'API arrêté
        → « Le fil n'a pas pu être chargé. » en rouge, role="alert", et AUCUN compteur
```

**Tests**

```
vitest services/admin/activity.vitest.ts        →  9 tests, verts
vitest services/search/overflow.vitest.ts       →  14 tests, verts (non-régression du ré-export)
```

## Livré

- `webapp/spaces/admin/app/pages/activite.vue` **(+)** — l'écran : table au large, cartes en étroit,
  pagination, et les états.
- `webapp/services/admin/activity.ts` **(+)** — la mise en forme d'une ligne et l'état unique de
  l'écran, purs. Ré-exporte `pageCount` plutôt que de le recopier.
- `webapp/spaces/admin/app/composables/useActivity.ts` **(+)** et `stores/activity.ts` **(+)** — la
  pagination et l'état partagé.
- `webapp/base/server/api/admin/activity.get.ts` **(+)** — le relais.
- `webapp/shared/types/admin-activity.ts` **(+)** — la forme servie, écrite à la main : l'enveloppe
  générée type ses items en opaque.
- Le chargeur de préchargement, le registre, l'entrée de navigation, et les clés dans les trois
  langues.
- `docs/…/arborescence-admin.md` **(M)** — `/activite` passe de proposé à livré.

## Reste à faire (hors périmètre)

- **Le fil d'ariane tronque le nom de l'écran sur mobile** (« A… ») — comportement existant de la
  barre haute, commun à tous les écrans, pas propre à celui-ci.
- **Filtres et agrégats** : hors périmètre assumé de cette version.

## Définition de terminé

- [x] L'écran liste les visites, du plus récent au plus ancien.
- [x] Chaque ligne porte date, recruteur, organisation, candidat et mention de dévoilement.
- [x] Le fil se pagine — **critère RÉÉCRIT** : par boutons, comme toutes les listes du
      back-office, et non « par tranches en descendant ». La carte nommait une mécanique que
      le back-office n'emploie nulle part, tout en demandant d'être « comme les autres »
      (cf. Écarts au cadrage).
- [x] Une base sans visite affiche un état vide explicite.
- [x] Les états d'erreur et de vide sont traités et **observés en capture**. Le chargement est
      couvert par le test de l'état et présent dans l'écran, mais **jamais surpris à l'œil** : sur
      cette base il passe trop vite.
- [x] L'écran est accessible depuis la navigation, en clés i18n dans les trois langues.
- [x] Lisible sur écran étroit, sans débordement horizontal.
- [x] Tests vitest sur la mise en forme d'une ligne et sur l'état de l'écran.
- [x] `arborescence-admin.md` à jour.
