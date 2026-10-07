# TASK API-019 — les fixtures cessent de dépendre du calendrier

- **Créée le** : 2026-08-02.
- **Session** : `57a6103a-e57e-429e-82e9-aed454da48f3`.
- **Statut** : ✅ fait le 2026-08-02 — trois données de recette dont la validité dépend du présent
  deviennent relatives ; les trois tests rouges repassent au vert sans qu'une ligne de test bouge.
- **Type** : Backend API — correction de fixtures, fiabilité de la suite de tests.
- **Tests** : `InvitationTest` — les trois cas qui échouent aujourd'hui doivent repasser au vert sans
  qu'aucune ligne de test ne soit touchée. C'est la meilleure preuve possible : le test n'a pas
  changé, seule la donnée qu'il consomme a cessé de pourrir.
- **Réf** : `API-018` (dont la PR a buté dessus) · `API-015` (la suite ne reconstruit plus la base) ·
  [testing-and-ci.md](../../docs/showcase-recruiter-webapp-saas/content/3.quality/testing-and-ci.md)

## Contexte

**Trois tests sont rouges depuis cette nuit, et aucun code n'a changé.**

`AppFixtures` fige l'expiration de l'invitation de démo au **`2026-08-01`**. Le 2026-08-02, l'API
répond `410 Gone` — correctement — et les trois tests d'`InvitationTest` qui attendaient un succès
tombent. Le défaut n'est pas dans le code testé : il est dans une donnée de recette dont la validité
dépend du jour où on lance la suite.

**Deux autres dates sont armées pour septembre** : l'abonnement de démo expire le `2026-09-30`, et la
fenêtre de présence au Maroc court du `2026-09-01` au `2026-09-15`. Ce sont des données qui n'ont de
sens qu'**à venir** — un abonnement actif expiré, une présence future déjà passée. Elles casseront à
leur tour, et à des dates où personne ne fera le lien.

Le défaut a coûté sa journée à la PR d'`API-018` : trois échecs à disculper avant de pouvoir affirmer
quoi que ce soit sur un diff qui n'y était pour rien.

## Périmètre

Les **trois données** de `AppFixtures` dont la validité dépend du présent — quatre littéraux,
la fenêtre de présence en portant deux :

- l'expiration de l'invitation en attente ;
- la fin de l'abonnement de démo ;
- la fenêtre de présence au Maroc.

Elles deviennent **relatives à maintenant**.

## Hors périmètre

- **Les huit autres dates figées** — synchronisations Jarvi, consentements, entretien, notifications,
  transactions. Ce sont des **faits historiques** : les figer est juste, et les rendre relatives
  ferait dériver des données qui doivent rester stables d'un run à l'autre.
- **Toute autre fixture**, et tout code applicatif.

## Décisions de cadrage

### Relatif seulement là où le futur fait partie du sens

Une date de fixture n'a pas à être relative par principe. Un consentement donné le 18 juin est un
fait ; le rendre glissant ne rendrait service à personne et rendrait les tests moins lisibles.

La ligne de partage est ailleurs : **une donnée qui doit être valide au moment du test** — une
invitation en attente, un abonnement actif, une présence à venir — ne peut pas porter une date
absolue. Elle décrit un état, pas un instant.

### Des écarts lisibles, pas des dates calculées

`+30 days`, `+2 months`, `+1 month` / `+45 days` : l'intention se lit dans la fixture même. Le
lecteur voit « cette invitation est valable un mois » plutôt qu'une date qu'il doit comparer
mentalement au calendrier.

### Pourquoi ça n'a pas été vu plus tôt

La suite recharge les fixtures avant **chaque** test — le purge et le rechargement fonctionnent.
Le piège n'est pas l'état résiduel, c'est que la fixture rechargée porte la **même** date périmée à
chaque fois. Aucun mécanisme de reset ne pouvait rattraper ça.

## Règles et cas limites

- **Ne pas toucher aux tests** : s'ils passent après coup sans avoir été modifiés, la démonstration
  est complète. Si l'un d'eux devait bouger, c'est que le périmètre a débordé.
- **Les dates historiques restent figées** — la stabilité d'un run à l'autre en dépend.
- **La fenêtre de présence garde sa durée** (quinze jours) : c'est elle qui donne son sens au
  différenciant Maroc dans la démo, pas ses bornes exactes.
- **Aucune migration ni changement de schéma** : ce sont des données de recette.

## Documentation impactée

Aucune page : cette carte corrige une donnée de fixture, elle ne change aucune convention. Le
déclencheur « Tests, CI, gates » ne s'applique pas — la règle de test ne bouge pas, c'est son jeu de
données qui cesse de pourrir.

## Définition de terminé

- [x] Les trois dates dépendantes du présent sont relatives.
- [x] `InvitationTest` est vert, **sans qu'aucun fichier de test ait été modifié**.
- [x] Les huit dates historiques sont inchangées.
- [x] La suite complète repasse au vert.

## Vérification

**391 tests, 1 976 assertions, verts** — suite complète, sortie lue le 2026-08-02. Avant la
correction : `391 tests, 1 965 assertions, 3 failures`, toutes dans `InvitationTest`.

**`InvitationTest` seule : 13 tests, 64 assertions, vertes.** Et `git status` ne montre qu'un seul
fichier modifié, `AppFixtures.php` : **aucun fichier de test n'a été touché**. C'est la démonstration
que visait la carte — le test n'a pas changé d'avis, c'est la donnée qui a cessé de pourrir.

**Le diagnostic, pour qu'il ne se reperde pas.** L'échec disait `410 Gone` avec « Cette invitation
n'est plus valable » : le code répondait **juste**. La fixture figeait l'expiration au `2026-08-01`,
et la suite tournait le 2026-08-02. Le rechargement des fixtures avant chaque test fonctionnait
parfaitement — il rechargeait simplement la même date périmée.

**Écart de comptage corrigé en cours de route** : trois *données*, mais **quatre littéraux**, la
fenêtre de présence en portant deux. Le hors-périmètre parle donc de huit dates historiques.

## Livré

- `api/src/DataFixtures/AppFixtures.php` **(M)** — invitation à `+30 days`, abonnement à
  `+2 months`, présence Maroc de `+1 month` à `+45 days`. Les huit dates historiques intactes.
- `dev-book/tasks/API-019-…md` **(+)** — cette carte.

## Aval

- **Un garde-fou contre la récidive** : rien n'empêche d'écrire demain une nouvelle fixture datée en
  dur. Une règle de convention — ou un test qui avance l'horloge d'un an et rejoue la suite — dirait
  le défaut avant qu'il ne morde. À cadrer si le cas se reproduit.
