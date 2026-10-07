# TASK 025a — DEV : renommage des rôles + hiérarchie

- **Créée le** : 2026-06-29.
- **Statut** : ⏳ **à faire** — sous-tâche de dev de **025** (qui n'en fait que la validation).
- **Périmètre** : implémenter le **référentiel de rôles** validé en cadrage le 2026-06-29 : enum,
  `role_hierarchy`, **renommage transverse**, alignement des **voters** et des **fixtures**.
- **Hors périmètre** : les **tests & validation** de bout en bout (→ tâche **025**) ; le **mot de
  passe libre-service** (→ tâche **025b**) ; les routes métier entreprise et leurs gaps (→ **026/a/b**) ;
  la **doc officielle `docs/`** (→ **027**).

## Décision de cadrage (verrouillée — 2026-06-29, source de vérité d'implémentation)

> Réf. mémoire projet « rôles & permissions ». Tant que cette tâche n'est pas livrée, le code/doc
> utilisent encore les **anciens** noms.

**Rôles canoniques** : `ROLE_CANDIDATE`, `ROLE_COMPANY_OWNER`, `ROLE_COMPANY_MEMBER`,
`ROLE_ADMIN_OWNER`, `ROLE_ADMIN_MEMBER` (+ `ROLE_USER`, socle Symfony).

**Mapping depuis l'existant** :

| Avant | Après |
| --- | --- |
| `ROLE_CANDIDATE` | `ROLE_CANDIDATE` *(inchangé)* |
| `ROLE_COMPANY_ADMIN` | `ROLE_COMPANY_OWNER` |
| `ROLE_COMPANY_RECRUITER` | `ROLE_COMPANY_MEMBER` |
| `ROLE_ADMIN` *(unique)* | scindé : `ROLE_ADMIN_OWNER` + `ROLE_ADMIN_MEMBER` |
| `ROLE_RECRUITER` *(entrée morte)* | **supprimé** |

**Hiérarchie** (`security.yaml`) :

```yaml
role_hierarchy:
    ROLE_COMPANY_OWNER: [ROLE_COMPANY_MEMBER]   # owner ⊇ member
    ROLE_ADMIN_OWNER:   [ROLE_ADMIN_MEMBER]     # super-admin ⊇ admin
    ROLE_ADMIN_MEMBER:  [ROLE_ADMIN]            # parapluie conservé (checks ROLE_ADMIN survivent)
```

- **Pas** d'héritage `ROLE_ADMIN → company/candidate` : l'accès cross-tenant admin reste **explicite
  dans les voters** (cf. data-model.md « pas de court-circuit global »).
- **Multi-rôles** préservé : un `Person` peut cumuler `ROLE_CANDIDATE` + `ROLE_COMPANY_MEMBER`.

## Matrice de permissions (V1 — oracle pour la validation 025)

| Capacité | CANDIDATE | COMPANY_MEMBER | COMPANY_OWNER | ADMIN_MEMBER | ADMIN_OWNER |
| --- | :---: | :---: | :---: | :---: | :---: |
| Gérer son profil candidat (→ sync Jarvi) | ✅ | — | — | — | — |
| Gérer sa visibilité / consentement | ✅ | — | — | — | — |
| Voir activité / notifications | ✅ | — | — | — | — |
| Rechercher & lire candidats (masqués) | — | ✅ | ✅ | 👁 | 👁 |
| Dévoiler un profil (consomme crédits) | — | ✅ | ✅ | — | — |
| Favoris partagés · assigner | — | ✅ | ✅ | — | — |
| Voir solde + journal crédits | — | ✅ | ✅ | 👁 | 👁 |
| Acheter des crédits (Stripe) | — | — | ✅ | — | — |
| Allouer/diffuser crédits à l'équipe | — | — | ✅ | — | — |
| Modifier la fiche entreprise | — | — | ✅ | — | — |
| Inviter / retirer membres · gérer rôles | — | — | ✅ | — | — |
| Gérer abonnement / facturation | — | — | ✅ | — | — |
| Valider / rejeter une entreprise | — | — | — | ✅ | ✅ |
| (Re)envoyer magic-link / password-setup | — | — | — | ✅ | ✅ |
| Consultation cross-tenant (entreprises & candidats) | — | — | — | 👁 | 👁 |
| Modération basique | — | — | — | ✅ | ✅ |
| Gérer les comptes admin (créer/retirer) | — | — | — | — | ✅ |
| Config plateforme (feature flags, `CreditPolicy`…) | — | — | — | — | ✅ |
| Opérations sensibles / destructrices | — | — | — | — | ✅ |

`👁` = lecture seule. Les capacités héritent **vers le haut**.

**Invariants** : 1 seul `COMPANY_OWNER` par entreprise (transfert = V2) ; admin cross-tenant =
consultation seule ; `ADMIN_OWNER` seul à gérer les admins et la config plateforme.

## Travail (dev)

1. **Enum & constantes** : `OrganizationRole` → `COMPANY_OWNER`/`COMPANY_MEMBER` ; nouvel enum admin
   (`AdminRole` : `ADMIN_OWNER`/`ADMIN_MEMBER`) **ou** constantes — choisir le plus sobre.
2. **`security.yaml`** : remplacer la `role_hierarchy` morte par celle ci-dessus ; relire l'`access_control`.
3. **Renommage transverse** : code (`OrganizationCreator`, `OrganizationVoter`,
   `OrganizationValidationProcessor`, `MemberInviter`, `InvitationAcceptor`…), fixtures, et les
   tâches/docs `dev-book` citant les anciens noms (la doc `docs/` = tâche 027).
4. **Données dev** : pas de migration Doctrine en période développement ; recréer la BDD depuis le
   mapping à jour puis recharger les fixtures alignées (`COMPANY_OWNER`, `COMPANY_MEMBER`,
   `ADMIN_OWNER`).
5. **Voters** : `OrganizationVoter` (MANAGE = `COMPANY_OWNER` ; ACCESS/VIEW = membre) ; garde admin
   (consultation `ADMIN_MEMBER` lecture seule, gestion admin `ADMIN_OWNER`).

## Définition de terminé

- [ ] 5 rôles canoniques + `role_hierarchy` réparée en place.
- [ ] 0 occurrence des anciens noms (`ROLE_COMPANY_ADMIN`/`_RECRUITER`/`ROLE_RECRUITER`) hors historique.
- [ ] Aucune migration Doctrine ajoutée ; fixtures alignées avec le nouveau référentiel.
- [ ] Voters conformes à la matrice ; pas de capacité entreprise héritée par `ADMIN_MEMBER`.
- [ ] `make api-quality` vert (la validation fonctionnelle complète relève de 025).

## Références

- Mémoire projet « rôles & permissions » ; [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) §User/§Recruiter.
- [security.yaml](../../api/config/packages/security.yaml) · [OrganizationRole](../../api/src/Enum/OrganizationRole.php) · [OrganizationVoter](../../api/src/Security/OrganizationVoter.php).
- Parent : **025** (validation). Sœur : **025b** (mot de passe).
