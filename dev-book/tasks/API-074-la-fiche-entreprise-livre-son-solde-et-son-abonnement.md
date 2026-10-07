# TASK API-074 — la fiche entreprise livre son solde et son abonnement

- **Créée le** : 2026-08-18.
- **Session** : `87c642c6-d8b6-4447-954e-5e3e0b6db5c9`.
- **Statut** : ✅ fait le 2026-08-18 — `GET /v1/admin/organizations/{id}` sert identité, solde,
  abonnement, membres et journal de crédits ; l'administration peut enfin répondre « combien lui
  reste-t-il ? » sans ouvrir la base.
- **Type** : Backend API — route de lecture pour le back-office.
- **Tests** : phpunit fonctionnel — la route rend l'entreprise avec son solde, son abonnement, ses
  membres et ses mouvements de crédits ; refuse un appelant non administrateur ; et tient les cas
  d'une entreprise sans portefeuille et sans abonnement.
- **Réf** : **aval `ADM-027`** (l'écran) · `requirements/brief/initial_brief.md` §4 ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (`/entreprises/:uuid`, proposé) ·
  entités `CreditWallet`, `CreditTransaction`, `Subscription` (existent déjà).

## Contexte

Le brief demande de voir « les soldes de crédits des entreprises et les options disponibles ».
Aujourd'hui, **l'administration ne peut pas le savoir** : le portefeuille d'une entreprise n'est
lisible que par cette entreprise elle-même — le contrôle d'accès de la route existante exige
d'appartenir à l'organisation (`CreditWalletProvider.php:39`). Un administrateur qui veut connaître
le solde d'un client doit interroger la base à la main.

Les trois briques existent pourtant en base : le portefeuille et son solde, le journal des mouvements
(dévoilement, recharge, avec le montant et le solde après opération), et l'abonnement souscrit avec
sa date de fin. Rien ne les rassemble ni ne les ouvre à l'opérateur de la plateforme.

## Périmètre

- **`GET /v1/admin/organizations/{id}`** — la fiche complète d'une entreprise pour l'administration :
  - son identité et son statut de validation ;
  - **son solde de crédits** ;
  - **son abonnement** : le plan souscrit, son état et sa date de fin de période ;
  - **ses membres** avec leurs rôles ;
  - **son journal de crédits** : les derniers mouvements, avec date, nature, montant, solde après
    opération, et le recruteur à l'origine quand il y en a un.
- **Garde `ROLE_ADMIN` explicite**, accès à toute organisation.
- Contrat régénéré (`make dev-openapi`).

## Hors périmètre

- **Créditer manuellement une entreprise** (le geste commercial : offrir des crédits à un client).
  C'est une écriture qui touche l'argent — motif obligatoire, traçabilité de son auteur, réservée au
  rôle propriétaire. Ça se cadre à part, et ça reste un point ouvert de la carte de navigation.
- **Modifier** quoi que ce soit sur la fiche : ni le plan, ni les membres, ni les rôles.
- **Le catalogue commercial** — ce que showcase-recruiter-webapp-saas vend, ses plans et ses tarifs. Le modèle commercial
  n'est pas cadré (le choix du prestataire de paiement est toujours ouvert dans `dev-book/todo.md`) ;
  construire dessus serait bâtir sur du sable.
- **Les options payantes des candidats** (mise en avant sur l'accueil, envoi du profil complet) :
  elles existent en base, mais elles ne concernent pas les entreprises et n'ont rien à faire ici.
- **La facturation** sous toutes ses formes.
- **L'écran** qui l'affiche : `ADM-027`.

## Décisions de cadrage

### « Les options disponibles » = l'abonnement de l'entreprise

La formule du brief est ambiguë : elle peut désigner l'abonnement d'un client, les options achetées
par les candidats, ou le catalogue de ce qu'on vend. Elle est lue comme **le plan souscrit par
l'entreprise et sa date de fin** — c'est ce qui se lit à côté d'un solde, c'est ce qui existe déjà en
base, et c'est la seule lecture qui ne dépende pas d'un cadrage commercial absent.

### Une fiche par entreprise, pas deux colonnes de plus sur la liste

Ajouter « solde » et « plan » à la liste existante aurait été plus rapide, mais le **journal des
mouvements** n'a pas sa place dans une ligne de tableau — et c'est lui qui explique un solde. La
carte de navigation prévoit déjà cette fiche : on l'implémente plutôt que d'en dériver une version
appauvrie.

### Lecture seule, sans exception

C'est l'invariant écrit de l'espace d'administration : elle consulte, elle n'agit pas à la place d'un
recruteur. Le brief demande de *voir*.

## Règles et cas limites

- **Entreprise sans portefeuille** (jamais créditée) : solde à zéro et journal vide, pas une erreur.
- **Entreprise sans abonnement** : le champ est vide et l'écran doit pouvoir le dire, pas une erreur.
- **Journal long** : borner le nombre de mouvements rendus, du plus récent au plus ancien. Une
  entreprise active peut en accumuler beaucoup.
- **Mouvement sans recruteur** (recharge automatique, geste de service) : la ligne reste lisible.
- **Identifiant inconnu** : introuvable, jamais une fiche vide.
- **Appelant non administrateur** : refus explicite.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — la nouvelle ligne dans la table des routes
  d'administration.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` — **revient à `ADM-027`**, pas ici.
  C'est la carte de navigation de l'espace admin : y faire passer `/entreprises/:uuid` de proposé à
  livré alors que l'écran n'existe pas encore la rendrait fausse sur l'intégration. La lane admin la
  met à jour en même temps qu'elle construit l'écran.
- `api/openapi.json` — régénéré.

## Vérification

**Appels réels**, pile de ce worktree, session administrateur :

```
GET /v1/admin/organizations/{showcase-recruiter-webapp-saas Demo SARL}
  Casablanca (MA) · statut VALIDEE
  solde        1 crédit
  abonnement   starter · ACTIVE · fin 02/10/2026
  membres      Sara Bennani — ROLE_COMPANY_OWNER — Responsable RH
  journal      −1  CONSUMPTION  « Dévoilement — EMAIL »   solde après 1   par Sara Bennani
               +2  GRANT_FREE   « Crédits offerts »       solde après 2
```

**Les cas limites, provoqués :**

```
Atlas Talent Conseil            solde 24 · 2 membres · 3 mouvements · AUCUN abonnement
entreprise sans rien            solde 0 · 0 membre · 0 mouvement · aucune erreur
   (créée pour la mesure, supprimée après)
?transactions=1                 journal borné à 1
?transactions=999               plafonné à 100
identifiant inconnu             404
un recruteur (pas admin)        403
sans jeton                      401
```

**Tests**

```
phpunit tests/Functional/AdminOrganizationDetailTest.php   →  7 tests, 50 assertions, vert
phpunit AdminActivityTest + OrganizationTest (non-rég.)    →  27 tests, 213 assertions, vert
make dev-openapi                                           →  +134 lignes (AdminOrganizationDetail)
```

## Livré

- `api/src/ApiResource/Output/AdminOrganizationDetailOutput.php` **(+)** — la forme de la fiche.
- `api/src/State/Provider/AdminOrganizationDetailProvider.php` **(+)** — garde `ROLE_ADMIN`,
  portefeuille et abonnement résolus séparément, journal borné et trié.
- `api/tests/Functional/AdminOrganizationDetailTest.php` **(+)** — les sept cas.
- `api/openapi.json` **(M)** et `docs/…/api-routes.md` **(M)**.

Les membres et les mouvements réutilisent les convertisseurs déjà en place — aucun code dupliqué.

## Décision prise en implémentant — l'abonnement absent n'est pas servi

Sans abonnement, le champ `subscription` **disparaît de la réponse** au lieu de valoir `null` : c'est
le comportement de **toutes** les routes du dépôt (l'écran des comptes fait déjà pareil avec la date
de dernière connexion jamais renseignée). S'en écarter pour cette seule route aurait ajouté une
incohérence de plus. L'écran lit donc une clé absente, pas un `null` — c'est écrit dans la table des
routes.

## Reste à faire (hors périmètre)

- **Les données de démonstration se contredisent sur les crédits.** L'entreprise de démonstration
  affiche un solde de 2, tandis que son dernier mouvement annonce « solde après : 3 » — le
  mouvement d'achat écrit `WELCOME_CREDITS + 1` là où le commentaire voisin dit explicitement
  « le portefeuille retombe sur deux ». Le journal ne retombe donc pas sur le solde, et **l'écran
  d'`ADM-027` le montrera** comme si c'était un défaut de la fiche. C'est une ligne de fixture à
  corriger, hors de cette carte. C'est un test de cette tâche qui l'a mis au jour.
- **La page de conventions contredit le code sur les champs nuls** : son exemple montre
  `"organizationId": null` alors que l'API omet les nuls partout. Défaut préexistant, qui concerne
  toutes les routes.
- **Créditer une entreprise à la main** reste non cadré.
- **L'écran** qui affiche cette fiche : `ADM-027`, autre lane.

## Définition de terminé

- [x] `GET /v1/admin/organizations/{id}` rend identité, statut, solde, abonnement, membres et
      journal de crédits.
- [x] Une entreprise sans portefeuille et sans abonnement est servie sans erreur.
- [x] Le journal est borné et trié du plus récent au plus ancien.
- [x] Un identifiant inconnu rend introuvable.
- [x] Un appelant non administrateur est refusé.
- [x] Tests fonctionnels phpunit sur les points ci-dessus.
- [x] `api-routes.md` à jour et `openapi.json` régénéré. **`arborescence-admin.md` NON touchée** :
      c'est une page de l'espace admin, et l'écran qui y ferait passer `/entreprises/:uuid` de
      proposé à livré est `ADM-027` — la modifier ici la ferait atterrir sur l'intégration sans
      l'écran qu'elle décrit.
