# TASK ADM-027 — la fiche entreprise montre ses crédits

- **Créée le** : 2026-08-18.
- **Session** : `87c642c6-d8b6-4447-954e-5e3e0b6db5c9`.
- **Statut** : ✅ fait le 2026-08-19 — la fiche d'une entreprise s'ouvre depuis la liste et montre
  son solde, son abonnement, ses membres et son journal de crédits ; la documentation périmée sur
  les connexions est corrigée.
- **Type** : Frontend back-office d'administration — écran de lecture, plus une correction de
  documentation périmée.
- **Tests** : vitest sur la mise en forme d'un mouvement de crédit (date, nature, montant signé,
  solde après opération) et sur l'état unique de l'écran — chargement, erreur, introuvable.
- **Réf** : **amont bloquant `API-074`** (la route qui sert la fiche) ·
  `requirements/brief/initial_brief.md` §4 ·
  `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (`/entreprises/:uuid`, proposé) ·
  `webapp/spaces/admin/app/pages/entreprises.vue` (la liste d'où l'on ouvre la fiche).

## Contexte

Le brief demande de voir « les soldes de crédits des entreprises et les options disponibles ».
L'écran *Entreprises* existe et sert la file de revue, mais il ne mène nulle part : cliquer sur une
ligne n'ouvre rien. L'équipe ne peut donc pas répondre à la question la plus courante d'un appel
client — combien de crédits lui reste-t-il, et sur quel abonnement est-il.

Cette tâche porte aussi une **correction de documentation**, indépendante mais du même sujet : la
carte de navigation de l'espace affirme encore que la date de dernière connexion n'existe pas sur les
comptes et laisse « les connexions en temps réel » parmi les points à trancher. C'est faux depuis
longtemps — la date est posée à chaque ouverture de session, affichée sur l'écran *Utilisateurs* et
comptée sur le tableau de bord. Deux des quatre demandes du brief §4 sont donc satisfaites, et la
documentation dit le contraire.

## Périmètre

- **Une fiche entreprise**, ouverte depuis une ligne de la liste existante, servie par `API-074` :
  - identité et statut de validation ;
  - **solde de crédits** et **abonnement** (plan, état, date de fin) mis en avant ;
  - **membres** et leurs rôles ;
  - **journal des mouvements de crédits** : date, nature, montant signé, solde après opération, et le
    recruteur à l'origine quand il y en a un.
- **Textes en clés i18n** dans les trois langues, aucun libellé en dur.
- **Correction de `arborescence-admin.md`** : les deux demandes du brief déjà satisfaites (connexions,
  droits d'accès) cessent d'être annoncées comme manquantes, et le point ouvert correspondant est
  retiré.

## Hors périmètre

- **Créditer manuellement une entreprise** : geste commercial qui touche l'argent, à cadrer à part.
- **Modifier** l'entreprise, son plan, ses membres ou leurs rôles. L'écran regarde — invariant de
  l'espace.
- **Le catalogue commercial** et la facturation : le modèle commercial n'est pas cadré.
- **Ce que cette entreprise a consulté** : l'historique des visites vit dans son écran à elle
  (`ADM-026`).
- **Un journal de connexions horodaté** et **l'édition des droits d'un compte** : extensions du
  brief, pas la demande.

## Décisions de cadrage

### Le solde et l'abonnement d'abord, le journal ensuite

Ce qu'on vient chercher en ouvrant la fiche, c'est un chiffre et un plan. Le journal explique ce
chiffre : il vient après, il ne se dispute pas la première place.

### Le journal montre le solde après chaque opération

Un montant seul (« −1 crédit ») ne se vérifie pas. Le solde après opération rend le journal
auto-portant : on suit la descente ligne à ligne et on retrouve le solde affiché en tête.

### La correction de documentation voyage avec cette carte

Elle ne mérite pas une tâche à elle seule, et elle touche exactement la page que cette carte modifie
déjà — l'arborescence de l'espace d'administration. La faire voyager seule ferait deux commits sur le
même fichier.

### Lecture seule, sans exception

Invariant de l'espace, et le brief demande de *voir*.

## Règles et cas limites

- **Entreprise sans portefeuille** : un solde à zéro et un journal vide, dits explicitement — pas une
  zone blanche.
- **Entreprise sans abonnement** : l'écran le dit (« aucun abonnement »), il ne laisse pas un champ muet.
- **Journal long** : borné, du plus récent au plus ancien.
- **Mouvement sans recruteur** : la ligne reste lisible, l'origine est simplement absente.
- **Identifiant inconnu** : un état introuvable explicite, pas une fiche vide.
- **Retour à la liste** : le chemin de retour est évident, et l'adresse de la fiche se partage et
  survit à un rechargement.
- **Écran étroit** : mobile-first, le journal se replie en cartes empilées, aucun débordement
  horizontal.
- **Accessibilité** : montants et dates lisibles par un lecteur d'écran, en-têtes annoncés.

## Décisions prises en implémentant

### La liste déménage en `entreprises/index.vue` — sinon la fiche serait inatteignable

Ajouter `entreprises/[id].vue` à côté d'`entreprises.vue` aurait fait de ce dernier le **parent** de
la fiche, qui ne serait jamais rendue : c'est exactement le défaut que la carte de navigation
signale sur la fiche candidat, inatteignable pour cette raison. La liste vit donc en
`entreprises/index.vue`, la forme que `vivier/` emploie déjà et qui fonctionne. **Le nom de route ne
change pas** : navigation et préchargement restent valides sans y toucher.

### Le rôle d'un membre se dit en français

La pastille affichait `ROLE_COMPANY_OWNER`, le nom que le code emploie. Elle dit « Propriétaire »,
dans les trois langues.

### Introuvable ne se lit pas dans le statut du chargement

Le chargeur **avale** le 404 pour mémoriser « fiche vide pour cet id » ; le chargement se termine
donc normalement, et le statut global ne dit rien. C'est le couple (id chargé, fiche nulle) qui
porte l'absence. La première version conditionnait l'introuvable à un statut d'erreur : un
identifiant inconnu s'affichait comme une panne, avec une alerte rouge et une invitation implicite à
réessayer sur une entreprise qui n'existe pas.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` — `/entreprises/:uuid` passe de proposé
  à livré ; **et** correction du point périmé sur la dernière connexion et de l'avertissement porté
  par `/utilisateurs`.

## Vérification

**La fiche, sur la pile de ce worktree**, session administrateur :

```
showcase-recruiter-webapp-saas Demo SARL · Casablanca · MA
  Solde de crédits  2          Abonnement  starter · Actif · jusqu'au 12 octobre 2026
  Membres (1)       Sara Bennani · owner@demo.showcase-recruiter-webapp-saas.example · [Propriétaire]
  Mouvements de crédits
    19 août 07:44  Achat            +1   solde après 2   Pack de dépannage
    19 août 06:44  Dévoilement      −1   solde après 1   Dévoilement — EMAIL
    12 août 10:58  Crédits offerts  +2   solde après 2   Crédits offerts à la création
```

Le journal **retombe juste** : on part de +2, on descend, on arrive au solde annoncé en tête.

**Écran étroit (390 px)** : solde et abonnement empilés, journal en cartes, **aucun débordement
horizontal**.

**Les deux absences, distinguées et provoquées :**

```
identifiant inconnu   « Cette entreprise n'existe pas. »      gris — pas une alerte
API arrêtée           « La fiche n'a pas pu être chargée. »   rouge — role="alert"
```

**Tests**

```
vitest services/admin/  →  22 tests, verts (organisation + activité)
```

## Livré

- `webapp/spaces/admin/app/pages/entreprises/[id].vue` **(+)** — la fiche.
- `webapp/spaces/admin/app/pages/entreprises.vue` → `entreprises/index.vue` **(déplacé)** — pour que
  la fiche soit atteignable.
- `webapp/services/admin/organization.ts` **(+)** et `organization.vitest.ts` **(+)** — mise en forme
  d'un mouvement, nom d'un membre, abonnement normalisé, état unique de l'écran.
- `webapp/spaces/admin/app/composables/useOrganization.ts` **(+)** et `stores/organization.ts` **(+)**.
- `webapp/base/server/api/admin/organizations/[id].get.ts` **(+)** — le relais.
- `webapp/shared/types/admin-organization.ts` **(+)** — la forme servie, écrite à la main.
- Les deux composants de la liste **(M)** — le nom de l'entreprise mène à sa fiche.
- Le chargeur, le registre, et les clés dans les trois langues.
- `docs/…/arborescence-admin.md` **(M)** — la fiche passe de proposée à livrée, **et** les trois
  affirmations périmées sur les connexions tombent.

## Reste à faire (hors périmètre)

- **La fiche candidat reste inatteignable** (`candidats.vue` parent de `candidats/[id].vue`) — même
  défaut, même remède, mais c'est un autre écran et il n'a rien demandé.
- **Les données de démonstration se contredisent sur les crédits**, comme `API-074` l'a signalé : le
  mouvement d'achat des fixtures écrit un solde après opération qui ne correspond pas au
  portefeuille. Sur cette base de développement, les mouvements ont été posés à la main pour la
  preuve ; les fixtures, elles, restent à corriger côté serveur.
- **Créditer une entreprise à la main** reste non cadré.

## Définition de terminé

- [x] Une ligne de la liste des entreprises ouvre sa fiche.
- [x] La fiche montre solde, abonnement, membres et journal des mouvements.
- [x] Une entreprise sans portefeuille ni abonnement s'affiche sans zone muette — l'écran écrit
      « Aucun abonnement » et un solde à zéro. **Vérifié côté serveur** (`API-074`, entreprise
      créée pour la mesure) ; à l'écran, le cas n'a pas été mis en scène.
- [x] Un identifiant inconnu affiche un état introuvable.
- [x] L'adresse de la fiche se partage et survit à un rechargement.
- [x] Textes en clés i18n dans les trois langues.
- [x] Lisible sur écran étroit, sans débordement horizontal.
- [x] Tests vitest sur la mise en forme d'un mouvement et sur l'état de l'écran.
- [x] `arborescence-admin.md` à jour, **y compris la correction du point périmé sur les connexions**.
