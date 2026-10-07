# TASK 100 — admin : verticale /utilisateurs & droits (API + front)

- **Créée le** : 2026-07-20.
- **Statut** : 🟡 verticale livrée (PR #93), un point encore ouvert — recalé le 2026-07-25.
- **Type** : Full-stack — API (Symfony/API Platform) + Front (webapp admin).
- **Dépend de** : `025a` (rôles, hiérarchie `ADMIN_OWNER ⊇ ADMIN_MEMBER`).
- **Réf cadrage** : `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (`/utilisateurs`, § Points ouverts).
- **Réf code** : `spaces/admin/app/pages/utilisateurs.vue` (coquille à remplacer).

> **Recalage 2026-07-25** : la verticale `/utilisateurs` est **livrée** — `AdminUserProvider` +
> `utilisateurs.vue`. Ce qui reste n'est pas le démarrage mais le **point ouvert d'origine** :
> répartition fine `ADMIN_OWNER` / `ADMIN_MEMBER`, et `lastLoginAt` / journal de connexions (que
> `102` attend pour sa tuile « connexions »).

## Contexte

`utilisateurs.vue` est une coquille. Il faut la liste des **comptes tous espaces confondus** avec
leurs droits. Aucune route `/v1/admin/users` n'existe.

## ⚠️ Point ouvert à trancher AVANT de coder

« Connexions des utilisateurs en temps réel » (brief §4) : **`User` n'a pas de `lastLoginAt`**.
- V1 = ajouter un simple `lastLoginAt` sur `User` (migration), **ou**
- journal de connexions dédié (entité `LoginEvent`) ?

Cette décision conditionne le modèle de données et la colonne « dernière connexion » de l'écran.

## À livrer

**API** — `GET /v1/admin/users` (`Output` + `Provider`, garde admin, filtrable par espace / rôle,
paginé) ; **écriture des droits** réservée à `ROLE_ADMIN_OWNER`. Contrat régénéré (`make dev-openapi`).

**Front** — `utilisateurs.vue` réel : table (nom, e-mail, rôle, espace, dernière connexion) +
édition des droits (OWNER seulement).

## Règles

- **Lecture** cross-tenant = tous les admins (`ROLE_ADMIN_MEMBER`).
- **Écriture des droits** = `ROLE_ADMIN_OWNER` **uniquement** (hypothèse à confirmer, arborescence).
- Namespace `/v1/admin/*`.

## Définition de terminé

- [ ] Point ouvert `lastLoginAt` tranché et implémenté.
- [ ] `GET /v1/admin/users` livrée + gardée ; écriture droits `ADMIN_OWNER` only (un `ADMIN_MEMBER`
      en écriture → 403).
- [ ] `utilisateurs.vue` affiche de vrais comptes (preuve : curl réel + écran).
- [ ] `openapi.json` régénéré ; `quality-check` + `api-quality` verts.
