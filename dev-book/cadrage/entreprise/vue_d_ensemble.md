# showcase-recruiter-webapp-saas — Espace Entreprise (référence)

> Vue d'ensemble **consolidée** de l'espace entreprise (`entreprise.showcase-recruiter-webapp-saas.example`, organization-centric).
> Ce doc **route vers le détail**, il ne le duplique pas : **modèle produit** →
> [les_2_outils.md](./les_2_outils.md) ; **UI** → [ui/](./ui/) ; besoin →
> [initial_brief.md](../../../requirements/brief/initial_brief.md) §3 ; modèle →
> [data-model.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) ; livraison →
> `dev-book/tasks/` ; backlog → [todo.md](../todo.md) ; inscription → [inscription.md](./api/inscription.md).
>
> **Phase** : Espace Entreprise = **P2** (cf. [roadmap.md](../roadmap.md)).

> **Refonte cockpit 2026-07-12.** L'espace entreprise est un **cockpit à 2 outils** (Table ·
> Visionneuse) sur une **route unique** `/le-vivier`. Source de vérité UI :
> `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-latest.html`.

## Organisation documentaire

- [les_2_outils.md](./les_2_outils.md) : **modèle produit** — les 2 outils, Favoris/Shortlist, lexique.
- [criteres_de_filtrage_profils.md](./criteres_de_filtrage_profils.md) : critères de recherche.
- [api/](./api/) : parcours métier, règles serveur, intégrations et contraintes API.
- [ui/](./ui/) : cockpit, console, Table, Visionneuse, inspecteur, shortlist.
- Ce fichier reste une vue d'ensemble produit : il oriente vers les détails sans porter les contrats
  API ni les spécifications UI fines.

## 1. Fonctionnalités

Statut : ✅ livré · 🚧 partiel · ⏳ à faire (V1) · 🔭 V2.

| Fonctionnalité | Statut | Source |
| --- | --- | --- |
| Onboarding entreprise (création `Organization`, voter multi-tenant) | ✅ | task 004 |
| Multi-comptes : invitations recruteurs, rôles `ROLE_COMPANY_OWNER`/`ROLE_COMPANY_MEMBER` | ✅ | task 009 |
| Crédits : solde + journal de consommation | ✅ | task 005 |
| Dévoilement d'un profil (débit atomique, idempotent, solde ≥ 0) | ✅ | task 005 |
| Recherche & lecture candidats avec **masquage** | ✅ | task 006 |
| Dévoilés partagés entreprise | ✅ | task 005/006 |
| **Recherche sur la réplica locale** (Postgres FTS + filtres) | ⏳ V1 | task 014 (réplica via 020) |
| **Dévoilement groupé** (sélection multiple + « tout dévoiler ») | ⏳ V1 | TODO |
| **Recherches auto-enregistrées** (critères, nommage auto, duplication) | ⏳ V1 | **nouveau — task à créer** |
| **Favoris + Shortlist rattachés à une recherche** | ⏳ V1 | **nouveau — task à créer** |
| **Partage explicite d'une recherche** avec l'équipe (lecture seule) | ⏳ V1 | **nouveau — task `072`** |
| **Nouveautés** par recherche (profils l'ayant rejointe depuis la dernière visite) → **module de notifications** | ⏳ V1 | **nouveau — task `072`** |
| **Espace collaboratif** (équipes, commentaires, notification ciblée, résumés) | 🔭 V2 | **§4bis** |
| **Export CSV de la shortlist** (+ accusé RGPD) | ⏳ V1 | **nouveau — task à créer** |
| **Modèles de recherche** (jeux de critères pré-établis) | ⏳ V1 | **nouveau** |
| Achat / recharge de crédits (Stripe) | ⏳ V1 | TODO (billing, `ROLE_COMPANY_OWNER`) |
| Diffusion/allocation de crédits aux recruteurs | ⏳ V1 | TODO (billing, `ROLE_COMPANY_OWNER`) |
| **Recherche assistée par IA** (langage naturel → filtres) | ⏳ cadrée | [recherche_assistee_ia.md](./recherche_assistee_ia.md) — **POC sur la page Smart Home**, hors cockpit |
| **Note** collaborative sur un candidat | 🔭 V2 | task 008 (backend posé) |
| **Statut candidat** (`OrganizationStatus`) | 🔭 V2 | TODO — vocabulaire à trancher |
| **Alertes** sur recherche enregistrée | 🔭 V2 | TODO |
| **Recherche IA** (MCP `advancedSearch`) | 🔭 V2 | task 014 — probablement caduque |
| Diffusion d'offres (ciblée Europe) | 🔭 V2 | brief §3, **§5 ci-dessous** |

> ⚠️ Recherche : **V1 = classique sur la réplica locale (Postgres FTS + filtres)** ; la recherche **ne
> tape plus Jarvi en direct** (ingestion task `020`). Cf.
> [jarvi-integration.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md).

## 2. Actions sur un profil / une sélection

L'action dépend de **l'outil** (cf. [les_2_outils.md](./les_2_outils.md)) : la Table agit **en masse**,
la Visionneuse **à l'unité**.

| Action | Outil | Effet | Crédits | Dévoilé requis | Statut |
| --- | --- | --- | --- | --- | --- |
| **Consulter** (aperçu) | Table → inspecteur | ouvre le tiroir (profil masqué) | non | non | ✅ V1 |
| **Visionner** | Visionneuse | fiche complète (anonymisée si masquée) | non | non | ✅ V1 |
| **Favoriser** | **Table** | filet large, **portée : recherche** | non | **non** | ⏳ V1 |
| **Dévoiler** ⭐ | les deux | identité + coordonnées + CV + **compte rendu** | **oui (1)** | — | ✅ unit. / ⏳ groupé |
| **Shortlister** | **Visionneuse** | liste serrée, **portée : recherche** | non | **oui** | ⏳ V1 |
| **Exporter** | Visionneuse / vue Shortlist | CSV de la shortlist | **non** | **oui** (garanti) | ⏳ V1 |
| **Télécharger** (CV, compte rendu) | Visionneuse | fichiers du dossier | non | **oui** | ⏳ V1 |
| **Partager une recherche** | console | rend la recherche visible de l'équipe | non | non | ⏳ V1 |
| **Note** | — | annotation partagée | non | non | 🔭 V2 |
| **Statut** | — | `OrganizationStatus` exclusif | non | non | 🔭 V2 |
| **Comparer** | — | 2–4 profils côte à côte | non | non | 🔭 V2 |
| **Contacter** | — | hors plateforme (via l'export) | — | **oui** | hors périmètre |

**Règles transverses**

- **Portées** — la ligne de partage est nette :

  | Donnée | Portée |
  | --- | --- |
  | **Dévoilés** · **solde de crédits** | **`Organization`** (partagés, on trace **qui** a agi) |
  | **Recherches** · **favoris** · **shortlist** · crédits consommés | **recherche** → **son auteur**, sauf partage explicite |

  > **Impact modèle.** **Favoris** = `Favorite` **+ clé recherche** ; **Shortlist** = **collection
  > distincte** (`shortlist ⊆ dévoilés`) ; la **recherche** porte un **auteur** et un **état de
  > partage** (privée par défaut, partageable en V1). **Favoris** et **Dévoilés** sont des **tabs
  > d'état** de la Table. → **tasks API à écrire** (refonte task 008).

- **Masquage** : avant dévoilement, un profil est anonymisé. **Favoriser** fonctionne sur un profil
  masqué ; **Shortlister / Exporter / Télécharger** exigent un profil **dévoilé**.
- **Dévoilement groupé** : coût **recalculé serveur** (déjà-dévoilés **gratuits**), **tout-ou-rien** si
  solde insuffisant (402, aucun débit), **atomique** (verrou pessimiste sur le wallet partagé),
  endpoint **batch**. Détail : [api/credits_et_devoilement.md](./api/credits_et_devoilement.md).
- **Achat / recharge** de crédits : réservé `ROLE_COMPANY_OWNER`.

## 3. Navigation & accès — V1

> **La carte de navigation applicative fait foi dans
> [arborescence-entreprise.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)** (routes,
> arborescence, subdomain `entreprise.showcase-recruiter-webapp-saas.example`). Cette section ne garde que les **entrées
> d'authentification** (hors shell) et les **portées d'accès** produit.

**Entrées d'authentification** (avant le shell applicatif)

```text
├─ /login                  Connexion classique après mot de passe défini
├─ /auth/magic-link        Consommation du Magic Link déclarant
├─ /auth/define-password   Définition du mot de passe après validation entreprise
├─ /onboarding             Création entreprise en revue (`ROLE_COMPANY_OWNER`)
└─ /invitation/:token      Acceptation invitation (`ROLE_COMPANY_MEMBER`, lien tokenisé)
```

**Portées & accès**

| Zone | Portée | Accès |
| --- | --- | --- |
| `/` (Smart Home), `/le-vivier`, `/credits` | partagé `Organization` | tous les recruteurs |
| **recherches · favoris · shortlist** | **recruteur** (portées par la recherche) | son auteur |
| `/l-equipe`, `/compte/entreprise` | entreprise | **ROLE_COMPANY_OWNER** uniquement |
| `/compte` | individuel | chaque recruteur |
| recherche réplica locale | index/réplica locale | passe par `api/` |

> **Favoris** et **Dévoilés** sont des **tabs d'état** de la Table
> ([arborescence-entreprise.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)).

**Accès entreprise**

- Le Magic Link sert à authentifier le déclarant pour créer l'`Organization` en `EN_REVUE`.
- Tant que l'entreprise n'est pas `VALIDEE`, les fonctions sensibles restent bloquées côté API.
- Après validation admin, le déclarant (`ROLE_COMPANY_OWNER`) reçoit un lien `define-password` à
  usage unique ; en définissant son mot de passe il est **connecté automatiquement** à son espace.
  `/login` sert ensuite aux connexions suivantes.
- Les membres invités (`ROLE_COMPANY_MEMBER`) ne reçoivent pas un accès implicite : l'owner déclenche
  une invitation, showcase-recruiter-webapp-saas envoie un e-mail contenant un lien `/invitation/:token`, puis le membre
  accepte l'invitation et définit son mot de passe avant sa première connexion classique. Le token
  retourné par l'API en V1 reste un raccourci de test, pas le parcours produit cible
  (cf. [api/invitation_membre.md](./api/invitation_membre.md)).

## 4. V2 / différé

```
espace collaboratif  la recherche devient un espace d'équipe → détail §4bis
/les-offres          diffusion d'offres (ciblée Europe) → détail §5
+ actions : Note · Statut · Comparer
+ recherche IA (MCP advancedSearch)
```

→ détail et décisions ouvertes : [todo.md](../todo.md), [les_2_outils.md §8](./les_2_outils.md).

## 4bis. Espace collaboratif — 🔭 V2

> **Origine** : proposition produit du 2026-07-12. **Décision** : cadré **d'un bloc en V2**, pas
> saupoudré en V1. La V1 tient debout sans lui (partage en lecture seule + compteurs suffisent).

**Le pivot** : la recherche cesse d'être le **plan de travail privé** d'un recruteur pour devenir un
**espace de travail d'équipe**. Ce n'est pas un ajout de fonctionnalités, c'est un **changement de
nature de l'objet** — d'où le traitement en bloc.

### Cible

```text
Recherche : Développeurs Symfony Senior

👤 Créée par Sophie
👥 Partagée avec l'équipe Technique

42 profils · +5 nouveaux depuis hier

💬 3 commentaires   ⭐ 12 favoris   👁 8 profils dévoilés
```

### Découpage

| Capacité | Statut | Note |
| --- | --- | --- |
| Compteurs de la recherche (**vus**, **favoris**, **dévoilés**) | ✅ **déjà V1** | portés par la recherche, en **ligne fine** au panneau de contrôle |
| **Nouveautés** d'une recherche (`+5 depuis hier`) | ⏳ **tiré en V1** | delta par recherche, servi par le **module de notifications** — cf. [criteres §9](./criteres_de_filtrage_profils.md) |
| Partage d'une recherche (**lecture seule**, toute l'organisation) | ⏳ **V1** | binaire : partagée / privée |
| **Alertes** : pour moi · pour toute l'équipe | 🔭 V2 | **deux événements** : *entrée* dans le jeu de résultats (= le badge, poussé) et *mise à jour* d'un profil du jeu (fenêtre Maroc ⭐, disponibilité, vérifié, ville). **Non configurable** : les critères disent déjà quoi surveiller. Détail : [ui/console_contextuelle.md §3.0bis](./ui/console_contextuelle.md) |
| **Équipes / sous-groupes** dans l'`Organization` (« l'équipe Technique ») | 🔭 V2 | ⚠️ **n'existe pas au modèle** — aujourd'hui : des membres et 2 rôles, rien d'autre |
| **Notification nominative** (« notifier uniquement certains recruteurs ») | 🔭 V2 | suppose les équipes, ou un ciblage individuel |
| **Commentaires sur une recherche** | 🔭 V2 | ⚠️ **3ᵉ objet distinct** : ni les *notes* sur un candidat (V2, task 008), ni la shortlist |
| **Résumés périodiques** (quotidien / hebdomadaire, par e-mail) | 🔭 V2 | déjà listé en évolution du hub ([ui/smart_home.md §9](./ui/smart_home.md)) |

### Garde-fous à tenir si on l'implémente

- **Le partage restera en lecture seule** tant qu'on n'a pas tranché la co-édition : commenter n'est
  **pas** éditer. Un espace collaboratif ne doit pas réintroduire par la bande l'écriture concurrente
  qu'on a explicitement écartée ([les_2_outils.md §4](./les_2_outils.md)).
- **Le flux d'équipe ne peut pas être plus partagé que les données qu'il expose** : une recherche
  privée ne remonte rien, nulle part.
- **Trois objets à ne pas confondre** : *note sur un candidat* · *commentaire sur une recherche* ·
  *shortlist*. Si les trois arrivent, le vocabulaire doit être posé **avant** le code.

## 5. Diffusion d'offres (ciblée Europe) — 🔭 V2

> Origine : « préparer le terrain pour que les entreprises demandent la diffusion d'offres vers
> l'Europe (groupe privé ou candidats ciblés) » — [initial_brief.md](../../../requirements/brief/initial_brief.md) §3.
> **Statut** : modèle de données à figer **maintenant** (P2) ; **feature livrée après** l'ingestion
> (task 020) et la recherche réplica (task 014) — sans elles, pas d'audience à cibler.

**Principe** : c'est **showcase-recruiter-webapp-saas qui route** l'offre vers les candidats. L'entreprise ne récupère
**jamais** les coordonnées (email/push partent via la plateforme). La diffusion **ne dévoile pas**
et ne débite **aucun crédit** : on cible des profils **masqués** (cf. règle de masquage §2). Un
crédit n'est débité **que si** l'entreprise dévoile ensuite un candidat qui a répondu.

### 5.1 Modèle (vue d'ensemble — schéma canonique : [data-model.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md))

| Entité | Rôle |
| --- | --- |
| `JobOffer` | l'offre créée par l'`Organization` (réutilisable). |
| `JobOfferCampaign` | une diffusion datée d'une offre vers une **audience** (= `SavedSearch` réplica ou liste manuelle). |
| `JobOfferRecipient` | un candidat ciblé + son `status` (V1 borné : `pending → sent → opened → viewed`). |
| `Notification` | **brique transverse** (cloche/inbox §3, alertes V2, opportunités candidat) — **pas** un sous-objet de la diffusion. |

### 5.2 Invariants (non négociables)

- **showcase-recruiter-webapp-saas route** : toute notification candidat passe par `api/` ; l'entreprise ne voit jamais
  l'email/le push d'un profil non dévoilé.
- **Masquage préservé** : cibler ≠ dévoiler. Aucun débit de crédit à la diffusion.
- **Intersection consentement** : `Recipient` = `audience ∩ candidats visibles/consentants`
  (cf. task 018, visibilité self-service + trace de consentement). Tracer le **motif d'exclusion**.
- **Source = réplica locale** : l'audience est une requête FTS+filtres sur la réplica (task 020),
  **jamais** un appel Jarvi en direct (cf. §1 ⚠️ et [jarvi-integration.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md)).
- **Écriture via API** : `Recipient.status` est avancé par des events/webhooks côté `api/`, jamais
  par le front (garde-fou « toute écriture en base passe par l'API »).
- **Enum extensible mais borné** : pas d'état exposé sans parcours qui le produit (`applied` ⇒ V2).

### 5.3 Découpage V1 / V2

| Capacité | Statut |
| --- | --- |
| **Modèle de données** (`JobOffer`, `JobOfferCampaign`, `JobOfferRecipient`) | ⏳ figé en P2 (§5.1) |
| Créer une offre (`JobOffer`) | 🔭 V2 |
| Cibler via recherche réplica ou short list | 🔭 V2 |
| **Audiences dynamiques** (= `SavedSearch` rejoué comme cible) | 🔭 V2 (fusionne avec `/alertes`) |
| Diffuser : notification in-app + e-mail (via showcase-recruiter-webapp-saas) | 🔭 V2 |
| Onglet « Opportunités pour vous » côté candidat | 🔭 V2 (cf. [vue d'ensemble candidat](../candidat/vue_d_ensemble.md)) |
| Suivi basique : `envoyée / ouverte / consultée` | 🔭 V2 |
| Candidature 1-clic (`applied`) | 🔭 V2 |
| Matching auto quotidien · relances · scoring IA de pertinence | 🔭 V2 |
| Stats avancées (CTR, taux de réponse) | 🔭 V2 |

→ décisions ouvertes (coût/crédits exact, format des templates d'e-mail) : [todo.md](../todo.md).
