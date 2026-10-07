# TASK API-073 — l'historique des visites s'ouvre à l'administration

- **Créée le** : 2026-08-18.
- **Session** : `87c642c6-d8b6-4447-954e-5e3e0b6db5c9`.
- **Statut** : ✅ fait le 2026-08-18 — `GET /v1/admin/activity` sert le fil des visites, du plus
  récent au plus ancien, chaque ligne disant si l'organisation avait dévoilé le profil.
- **Type** : Backend API — route de lecture pour le back-office.
- **Tests** : phpunit fonctionnel — la route rend les visites du plus récent au plus ancien, pagine,
  refuse un appelant non administrateur, et signale sur chaque ligne si le profil a été dévoilé.
- **Réf** : **amont bloquant `API-072`** (sans elle, la route rend une liste vide) · **aval
  `ADM-026`** (l'écran) · `requirements/brief/initial_brief.md` §4 ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` · convention du préfixe `/v1/admin/*` (tâche `026a`).

## Contexte

`API-072` fait enfin exister les visites en base. Personne ne peut les lire : il n'y a ni route, ni
écran. Le brief demande que l'équipe voie « l'historique des profils visités » — c'est cette route
qui le sert.

L'espace d'administration a déjà neuf routes de ce genre (`/v1/admin/candidates`, `/users`,
`/vivier`, `/searches`…) : celle-ci suit exactement leur forme, il n'y a rien à inventer.

## Périmètre

- **`GET /v1/admin/activity`** — le fil des visites, **du plus récent au plus ancien**, paginé
  (`?page`, `?perPage` ≤ 100), enveloppe plate comme les autres listes du back-office.
- **Une ligne porte** : la date et l'heure, le recruteur (nom et adresse), son organisation, le
  candidat regardé (nom complet — l'administration voit les profils non masqués), et **si ce profil
  a été dévoilé** par cette organisation.
- **Garde `ROLE_ADMIN` explicite**, comme les autres routes du préfixe : accès à toutes les
  organisations, sans distinction de rattachement.
- Contrat régénéré (`make dev-openapi`) — c'est du contenu de commit, pas une porte de qualité.

## Hors périmètre

- **Filtrer** par organisation, par candidat ou par période : la première version est un fil. Le
  besoin de filtre viendra de l'usage, pas de l'anticipation.
- **Agréger** : aucun compteur, aucun classement, aucun « profil le plus regardé ». Le brief parle de
  *préparer* les indicateurs — c'est la matière qu'on sert, pas les indicateurs.
- **Exporter** la liste.
- **L'écran** qui l'affiche : `ADM-026`.
- **Écrire** quoi que ce soit : l'administration regarde, elle n'agit pas.

## Décisions de cadrage

### Le nom du candidat en clair

L'administration est l'opérateur de la plateforme : elle voit les profils non masqués, c'est un
invariant écrit de l'espace. Un fil d'activité qui n'afficherait que des initiales serait illisible
et sans usage.

### La mention « dévoilé » sur la ligne

C'est l'information qui donne son sens au fil : un candidat regardé dix fois et jamais dévoilé
raconte une fiche qui accroche sans convaincre. Sans cette mention, on ne lit qu'une suite de dates.
Elle se déduit des dévoilements déjà enregistrés pour le couple entreprise / candidat.

### Le nom de la route

`/v1/admin/activity` plutôt que `/v1/admin/profile-views` : l'écran s'appelle *Activité* dans la
carte de navigation, et la route peut accueillir d'autres natures d'événements plus tard sans être
renommée.

## Règles et cas limites

- **Aucune visite en base** : liste vide et pagination cohérente, jamais une erreur.
- **Recruteur ou organisation supprimé depuis** : la ligne reste lisible — une trace historique ne
  disparaît pas parce qu'un compte a été fermé. Traiter le cas plutôt que le laisser lever une erreur.
- **Volume** : la table grossit sans limite (cf. la réserve de conservation dans `API-072`). La
  pagination est requise dès la première version, et le tri par date doit s'appuyer sur un accès
  indexé.
- **Appelant non administrateur** : refus explicite, comme les autres routes du préfixe.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — la nouvelle ligne dans la table des routes
  d'administration.
- `api/openapi.json` — régénéré.

## Vérification

**Appels réels**, pile de ce worktree, session administrateur :

```
GET /v1/admin/activity
  total 4 · page 1 · perPage 20
  18/08 19:34  Sara Bennani (owner@demo…)      showcase-recruiter-webapp-saas Demo SARL    Yassine El Amrani  dévoilé non
  17/08 19:34  Leila Mansouri (atlas.member…)  Atlas Talent Conseil  Yassine El Amrani  dévoilé OUI
  17/08 19:34  Sara Bennani (owner@demo…)      showcase-recruiter-webapp-saas Demo SARL    Yassine El Amrani  dévoilé non

?page=1&perPage=2 puis ?page=2&perPage=2  →  l'ordre décroissant tient ENTRE les pages
?perPage=999                              →  ramené à 100
un recruteur                              →  403
sans jeton                                →  401
```

Les deux organisations sur le **même** candidat sortent avec un `revealed` différent : c'est
l'information qui donne son sens au fil.

**Le cas du compte fermé, provoqué et non supposé** :

```
compte du recruteur Leila Mansouri fermé (suppression douce)
  → le fil garde ses 2 lignes (total 4, inchangé)
  → la MÊME personne, vue par /v1/organizations/{id}/members : DISPARUE (total 1)
```

La seconde mesure est la preuve que la levée du filtre est **locale à cette route** et ne fuit pas.

**Tests**

```
phpunit tests/Functional/AdminActivityTest.php        →  8 tests, 93 assertions, vert
phpunit ProfileViewTest + AdminDashboardTest
        + AdminUserTest (non-régression)              →  11 tests, 55 assertions, vert
make dev-openapi                                      →  +67 lignes (route AdminActivity)
```

## Livré

- `api/src/ApiResource/Output/AdminActivityOutput.php` **(+)** — la forme d'une ligne du fil.
- `api/src/State/Provider/AdminActivityProvider.php` **(+)** — garde `ROLE_ADMIN`, tri décroissant,
  pagination bornée à 100, dévoilements de la page résolus en **une** requête.
- `api/tests/Functional/AdminActivityTest.php` **(+)** — les huit cas, dont la liste vide et le
  compte fermé.
- `api/openapi.json` **(M)** — contrat régénéré.
- `docs/…/api-routes.md` **(M)** — la route dans la table d'administration.

## Décision prise en implémentant — la suppression douce est levée le temps de la lecture

La règle « un compte fermé garde ses lignes » se heurtait au filtre de suppression douce global du
dépôt : en jointure interne, fermer un recruteur aurait fait **disparaître la visite entière** du
fil. Le filtre documente lui-même l'issue — « un administrateur, qui doit voir le supprimé, le
désactive explicitement ». C'est donc une fenêtre ouverte puis refermée autour de la seule lecture du
fil, avec deux garde-fous : les visites supprimées restent écartées par une clause explicite, et la
restauration passe par un `finally`. Mesuré ci-dessus : ailleurs, le supprimé reste invisible.

Ce n'est pas un écart au cadrage — c'est le moyen d'une règle qui y était déjà écrite.

## Reste à faire (hors périmètre)

- **L'écran** qui affiche ce fil : `ADM-026`, autre lane.
- **Filtres et agrégats** : hors périmètre assumé de cette version.

## Définition de terminé

- [x] `GET /v1/admin/activity` rend le fil paginé, du plus récent au plus ancien.
- [x] Chaque ligne porte date, recruteur, organisation, candidat et mention de dévoilement.
- [x] Un appelant non administrateur est refusé.
- [x] Une base sans visite rend une liste vide, sans erreur.
- [x] Un recruteur supprimé ne casse pas la ligne.
- [x] Tests fonctionnels phpunit sur les points ci-dessus.
- [x] `api-routes.md` à jour et `openapi.json` régénéré.
