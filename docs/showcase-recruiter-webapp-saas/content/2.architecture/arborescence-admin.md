# showcase-recruiter-webapp-saas — Espace Admin · Arborescence & routes

> **Carte de navigation canonique** de l'espace administration (`admin.showcase-recruiter-webapp-saas.example`,
> cross-tenant, rôles `ROLE_ADMIN_*`). Même convention que
> [arborescence-entreprise.md](./arborescence-entreprise.md).
>
> Ce document consolide [initial_brief.md §4](../../../../requirements/brief/initial_brief.md) (+ §1 pour
> les profils stars et les liens d'invitation), les routes admin livrées côté API et le backlog
> ([todo.md](../../../../dev-book/todo.md) § Dashboard admin). Rappel garde-fou : P3 = **surtout des vues**
> sur les entités existantes.
>
> **Il décrit la STRUCTURE, pas les libellés.** Le texte affiché dans la barre latérale vit dans
> `useNav` (`webapp/spaces/admin/app/composables/`) et nulle part ailleurs — le recopier ici
> rouvrirait la deuxième source de vérité que `ADM-011` a supprimée.

---

# Arborescence de l'espace admin

Statut : ✅ livré · 💡 proposition (à valider)

> **État vérifié le 2026-08-01** — `php bin/console debug:router` liste dix routes `/v1/admin/*` :
> `dashboard`, `candidates` (+ `{id}`), `organizations`, `users`, `referentials/{type}`,
> `referentials/reviews` et `referentials/labels/reviews` (POST), `jarvi`, `vivier`, auxquelles
> s'ajoute `vivier/gaps/{metric}` — le détail nominatif d'un manque de qualification, et `searches`
> — l'inventaire des recherches dont le vivier ramène plus de profils que l'écran recruteur n'en
> sert. Les huit écrans ci-dessous s'appuient dessus. Le socle d'accès n'a pas bougé : hiérarchie
> `ROLE_ADMIN_OWNER ⊇ ADMIN_MEMBER ⊇ ROLE_ADMIN` (security.yaml), garde admin sur validate/reject.

**Les huit écrans de la barre latérale, en trois groupes.** L'ordre et les groupes sont ceux de
`useNav` ; y ajouter une entrée la fait apparaître d'un coup dans la barre, dans le tiroir
mobile et dans le fil d'ariane.

```text
admin.showcase-recruiter-webapp-saas.example/
│
├─ ENTRÉE / AUTH
│   └─ /login                 Connexion admin (ROLE_ADMIN_*) — pas d'inscription self-service
│
├─ PILOTAGE
│  └─ /                    ✅ Tableau de bord — entreprises à revoir, connexions, dévoilements
│                             et inscriptions sur 7 jours, volume du vivier
│
├─ COMPTES
│  ├─ /entreprises         ✅ Liste cross-tenant, file de revue (EN_REVUE / VALIDEE / REFUSE /
│  │  │                       INCOMPLET) — valider / rejeter
│  │  └─ /:uuid            ✅ Fiche entreprise : solde de crédits et abonnement en tête, membres et
│  │                          rôles, puis le journal des mouvements — chaque ligne portant le solde
│  │                          APRÈS l'opération, ce qui rend le journal auto-portant : on suit la
│  │                          descente et on retombe sur le solde annoncé. Lecture seule. Atteignable
│  │                          parce que la liste vit en `entreprises/index.vue` : un `entreprises.vue`
│  │                          parent aurait rendu la fiche inatteignable, comme pour les candidats
│  ├─ /candidats           ✅ Le vivier cross-tenant (projection + réplica Jarvi)
│  │  └─ /:uuid            ⚠️ Fiche candidat — la barre haute y annonce le NOM de la personne.
│  │                          INATTEIGNABLE aujourd'hui : `candidats.vue` est le parent de
│  │                          `candidats/[id].vue` et ne rend pas son enfant — la liste s'affiche
│  │      └─ star          💡 Entretien showcase-recruiter-webapp-saas (starInterviewedAt), compte rendu et mise en
│  │                         avant en page d'accueil (brief §1)
│  └─ /utilisateurs        ✅ Comptes & droits, tous espaces confondus, avec la DATE DE DERNIÈRE
│                             CONNEXION de chaque compte — posée à chaque ouverture de session, et
│                             comptée sur le tableau de bord (fenêtre de 7 jours). Les deux demandes
│                             du brief §4 « connexions » et « droits d'accès » sont donc servies
│
├─ SUPERVISION
│  ├─ /jarvi               ✅ Ingestion : dernier import, importés / ignorés / erreurs
│  ├─ /referentiels        ✅ Vocabulaire dérivé — revue et validation des concepts et libellés
│  ├─ /vivier              ✅ État de la base — l'instantané du rapport CLI : complétude,
│  │                          top secteurs, métiers et langues. Les quatre cartes de complétude
│  │                          sont des liens vers l'écran ci-dessous ; les répartitions (séniorité,
│  │                          certitude du poste) n'en sont pas — elles n'ont pas d'inverse à montrer
│  ├─ /vivier/{metric}     ✅ Le détail d'un manque : la répartition par motif, puis les profils
│  │                          concernés, chacun avec le sien. Le motif filtré vit dans l'adresse
│  │                          (`?reason=`) : il se partage et survit à un rechargement. Un code de
│  │                          métrique inconnu dit que la page n'existe pas, sans appel ni erreur
│  ├─ /recherches          ✅ Recherches débordantes — les recherches dont le vivier ramène plus de
│  │                          profils que ce qui est servi, toutes organisations confondues. Le
│  │                          nombre RÉEL y figure, jamais « 100+ » : c'est lui qui distingue une
│  │                          recherche à resserrer d'une demande que le vivier couvre largement.
│  │                          Le seuil n'est pas réglable ici, c'est la borne de chargement d'une
│  │                          recherche. Lecture seule — agir sur la recherche d'un client n'a pas
│  │                          été tranché. Les closes restent listées et signalées comme telles
│  └─ /activite            ✅ Le fil des visites de profil, du plus récent au plus ancien. Chaque
│                             ligne dit QUI a regardé QUI, et si l'organisation a fini par dévoiler
│                             le profil — c'est cet écart qui porte la valeur du fil, visiter
│                             n'étant pas dévoiler. Lecture seule, sans filtre ni agrégat : la
│                             matière des indicateurs à venir, pas les indicateurs
│
├─ HORS BARRE LATÉRALE
│  └─ /compte              ✅ Compte admin courant — joignable par le menu de l'avatar, qui vit en
│                             PIED de la colonne de navigation (barre haute sous 1024px). Ce n'est
│                             pas une entrée de la barre latérale. La barre haute annonce son titre
│                             au-dessus de 1024px, le fil d'ariane le nomme en dessous
│
└─ PROPOSÉ, NON IMPLÉMENTÉ
   ├─ /invitations         💡 Liens d'invitation (brief §1) : magic link candidat à envoyer
   │                          soi-même, lien d'inscription entreprise
   └─ /notifications       💡 Infra d'e-mails automatiques et notifications recruteur
                              « nouveau profil qualifié » (brief §4) — dépend de la brique
                              notification multi-canal (todo.md, en cadrage)
```

---

# Portées & accès

> Rôles décidés le 2026-06-29 : `ROLE_ADMIN_OWNER` ⊇ `ROLE_ADMIN_MEMBER` (hiérarchie),
> `ROLE_ADMIN` parapluie. La répartition fine ci-dessous est une **hypothèse à trancher**.
>
> ⚠️ **Elle n'est encore appliquée nulle part** (vérifié le 2026-08-01) : `ROLE_ADMIN_OWNER`
> n'apparaît que dans l'entité `User` et son enum, aucune route ni aucun écran ne s'y adosse. Les
> deux rôles d'admin voient donc les mêmes sept entrées, et la barre latérale ne filtre rien.

| Zone | Portée | Accès (hypothèse) |
| --- | --- | --- |
| `/` (dashboard), `/activite`, `/entreprises` (lecture), `/candidats` (lecture), `/jarvi` (lecture) | cross-tenant, lecture | tous les admins (`ROLE_ADMIN_MEMBER`) |
| Valider/rejeter une entreprise, `/utilisateurs` (écriture droits), `/invitations`, statut Star, relance ingestion, `/notifications` (config) | cross-tenant, écriture | `ROLE_ADMIN_OWNER` uniquement |
| `/compte` | individuel | chaque admin |

**Invariants**

- **Lecture seule par défaut** : l'admin **consulte** cross-tenant ; les seules écritures sont
  celles listées ci-dessus (validation entreprise, droits, star, invitations). Aucune action
  métier « à la place » d'un recruteur ou d'un candidat.
- **Namespace `/v1/admin/*`** côté API (convention tâche 026a) : séparation nette des routes
  admin cross-tenant et des routes membre.
- **Coordonnées candidat** : l'admin voit les profils **non masqués** (opérateur de la
  plateforme), mais l'historique tracé (`ProfileView`) reste inchangé.

---

# V2 / différé

```text
statuts candidat     Gestion du vocabulaire des statuts candidat (liste globale, commune à
                     toutes les entreprises) — dépend du Statut candidat entreprise
                     (🔭 V2, todo.md). À ne pas confondre avec /referentiels, livré, qui
                     porte le vocabulaire métier dérivé du vivier.
/billing             Plans, packs de crédits, abonnements — dépend du cadrage
                     Stripe (Europe) / CMI (Maroc) (todo.md § modèle commercial)
/moderation offres   Supervision de la diffusion d'offres (🔭 V2, entreprise §5)
options candidat     Gestion des options payantes (CandidateOption, 🔭 V2)
```

---

# Points ouverts (à trancher au cadrage P3)

- [ ] Répartition exacte `ADMIN_OWNER` / `ADMIN_MEMBER` (le tableau ci-dessus est une hypothèse).
- [x] « Connexions des utilisateurs en temps réel » (brief §4) : **tranché par l'existant** — la
      date de dernière connexion est posée à chaque ouverture de session, affichée sur
      `/utilisateurs` et comptée sur le tableau de bord. Un journal de connexions horodaté reste
      possible, mais c'est une extension du brief, pas sa demande.
- [ ] Attribution manuelle de crédits à une entreprise (geste commercial) : dans `/entreprises/:uuid/credits` ?
- [ ] `/invitations` candidat : lien unitaire seulement (V1) ou campagne (hors périmètre 033) ?
- [ ] La fiche candidat admin réutilise-t-elle la Visionneuse entreprise (non masquée) ou une vue dédiée ?
