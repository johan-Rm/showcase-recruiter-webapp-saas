# showcase-recruiter-webapp-saas — Espace Entreprise · Smart Home

> **Page d'accueil** de l'espace entreprise (`entreprise.showcase-recruiter-webapp-saas.example/`). Ce n'est **pas** un
> tableau de bord : c'est un **hub d'activité** qui oriente le recruteur et le remet au travail en
> un clic. Le véritable espace de travail reste **[Le Vivier](./vue_la_table.md)**.
>
> Hébergée par le [shell d'application](./application_shell.md) fixe ; route déclarée dans
> [arborescence.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md) ; portées & accès dans [../vue_d_ensemble.md §3](../vue_d_ensemble.md).

---

# 1. Objectif

La Smart Home répond, en quelques secondes, à quatre questions du recruteur :

- **Ce qui est nouveau** — profils qui méritent son attention.
- **Où reprendre** — sa dernière recherche.
- **Ce qui nécessite une action** — crédits.
- **Ce que l'équipe a fait** — activité des collaborateurs.

Elle **prépare** l'entrée dans Le Vivier ; elle ne le remplace pas et ne doit **jamais** devenir une
page de consultation.

---

# 2. Principes UX

- Aucune statistique complexe, aucun graphique, aucun KPI métier.
- **N'afficher un bloc que lorsqu'il a quelque chose à dire** (cf. [§7 États vides](#7-états-vides)).
- Chaque information conduit à **une action** ; **une action = un clic**.
- Objectif unique : **faire gagner du temps** au recruteur.

---

# 3. Place dans le shell

> La Smart Home n'est **pas couverte par le mockup cockpit** : les principes ci-dessous font foi, leur
> **traduction dans le shell** ([application_shell.md](./application_shell.md)) reste à produire (§11).

La Smart Home est une **Vue principale** hébergée par le shell. Ce n'est **pas** un des deux **outils**
du vivier (Table · Visionneuse) : c'est un **hub d'accueil**.

- **Topbar** : identique à toutes les vues. On y accède par la **marque `showcase-recruiter-webapp-saas`** (route `/`) — la
  navigation vit dans le **menu compte** ([topbar.md §2](./topbar.md)).
- **Console, barre haute, inspecteur** : **absents**. Un hub n'a ni recherche, ni filtres, ni
  sélection — la Smart Home occupe **tout le cadre** sous la topbar.
- **Notifications** : la cloche de la topbar ([topbar.md §4](./topbar.md)) **recoupe** le bloc
  _Nouveautés_ — la Smart Home en est la version _page_, la cloche la version _ambiante_.

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│ ① TOPBAR   ⬢ showcase-recruiter-webapp-saas▸(accueil)              💰 844  🔔³  👤 Julien ▾         │
├──────────────────────────────────────────────────────────────────────────────┤
│ SMART HOME — plein cadre (ni console, ni barre haute, ni inspecteur)          │
│                                                                              │
│   👋 Bonjour Julien                                                          │
│                                                                              │
│   ┌─ 🆕 Nouveautés ───────────────┐  ┌─ 🔍 Reprendre son travail ─────────┐  │
│   │ 5 profils bientôt au Maroc    │  │ Tech · France                      │  │
│   │ 3 nouveaux profils vérifiés   │  │ 34 profils · 3 favoris · 2 en SL   │  │
│   │            [ Voir les profils ]│  │        [ Reprendre la recherche ]  │  │
│   └───────────────────────────────┘  └────────────────────────────────────┘  │
│                                                                              │
│   ┌─ ⭐ Derniers profils dévoilés ┐  ┌─ 👥 Activité de l'équipe ──────────┐  │
│   │ … cartes dévoilées …          │  │ Sophie a dévoilé 2 profils         │  │
│   │        [ Voir dans le vivier ]│  │ Marc a ajouté 3 profils en favoris │  │
│   └───────────────────────────────┘  │            [ Voir l'activité ]     │  │
│                                       └────────────────────────────────────┘  │
│   ┌─ 💳 Crédits ──────────────────────────────────────────────────────────┐  │
│   │ 844 crédits restants · Dernière conso : 2 profils (hier)              │  │
│   │                                        [ Recharger des crédits ] (OWNER)│  │
│   └───────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

# 4. Blocs

Chaque bloc répond à **une seule** question : _« Que dois-je faire maintenant ? »_.

## 4.1 👋 Accueil

```text
Bonjour Julien
```

- Salutation nominative. Le « Bienvenue sur showcase-recruiter-webapp-saas » est **réservé au premier login** (onboarding),
  pas répété à chaque connexion (ton « reprise de travail », pas « découverte »).

## 4.2 🆕 Nouveautés

N'informe **que** lorsqu'un élément mérite l'attention. Profils **masqués** (le dévoilement se fait
depuis Le Vivier). CTA unique : **Voir les profils** → `/le-vivier` préfiltré.

| Élément | Dépendance | Statut |
| --- | --- | --- |
| Nouveaux profils **disponibles** | réplica locale (ingestion task 020 / recherche task 014) | ⏳ V1 |
| Profils **bientôt au Maroc** | réplica locale + `nextTripToMorocco` | ⏳ V1 |
| Profils **récemment vérifiés** | réplica locale + `verifiedAt` | ⏳ V1 |
| Nouveaux profils **correspondant aux recherches enregistrées** | `SavedSearch` + alertes | 🔭 V2 |

## 4.3 🔍 Reprendre son travail

Rappelle la **dernière recherche** du recruteur pour la reprendre d'un clic. Depuis la refonte, une
recherche est un **plan de travail** : elle porte ses critères, ses favoris, sa shortlist et ses
crédits consommés ([console_contextuelle.md §5](./console_contextuelle.md)). Le bloc doit donc dire
**où on en était**, pas seulement ce qu'on cherchait.

```text
Tech · France
34 profils · 3 favoris · 2 en shortlist
        [ Reprendre la recherche ]
```

- CTA → `/le-vivier` avec la recherche **active** (critères restaurés).
- Les recherches sont **auto-enregistrées** et **multiples** (⏳ V1) — le hub en montre la **dernière
  active** ; le choix parmi toutes se fait dans le panneau **Mes recherches** de la console.

## 4.4 ⭐ Derniers profils dévoilés

Derniers profils **débloqués par l'entreprise** (identité visible — le dévoilement reste **partagé
`Organization`**). CTA : **Voir dans le vivier** → `/le-vivier`, tab d'état **Dévoilés**.

- S'appuie sur l'existant livré (dévoilement + dévoilés partagés, task 005/006). **✅ V1.**
- « Dévoilés » est un **tab d'état de la Table** ([arborescence.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)) : le CTA route
  vers `/le-vivier`, pas vers une page dédiée.

## 4.5 👥 Activité de l'équipe

Dernières actions des collaborateurs (on trace toujours **quel recruteur** a agi). CTA :
**Voir l'activité** → `/l-equipe`.

```text
Sophie a dévoilé 2 profils
Marc a ajouté 3 profils à ses favoris
```

| Élément | Réalité modèle | Statut |
| --- | --- | --- |
| Dévoilements récents (par recruteur) | tracé (task 005), **partagé entreprise** | ⏳ V1 |
| Partage d'une recherche (« Marc a partagé *Tech · France* ») | recherche **partagée** | ⏳ V1 |
| Favoris / shortlist **sur une recherche partagée** | visible car partagée | ⏳ V1 |
| Favoris / shortlist sur une recherche **privée** | ❌ **jamais affiché** | — |

> **Règle : le flux ne peut pas être plus partagé que les données qu'il expose.**
> Il montre donc les **dévoilements** (structurellement communs — ils consomment le budget partagé) et
> l'activité sur les recherches **explicitement partagées**. Le travail non partagé **reste privé** :
> *ce que je partage devient visible*.

## 4.6 💳 Crédits

Rappel du solde partagé `Organization`, sans détour par le journal. **Même vocabulaire que le chip
topbar** : **le solde restant** ([topbar.md §3](./topbar.md)).

```text
844 crédits restants
Dernière consommation : 2 profils (hier)
⚠ Solde faible                     (si sous seuil)
        [ Recharger des crédits ]   (ROLE_COMPANY_OWNER)
```

| Élément | Statut |
| --- | --- |
| Solde restant + dernière consommation | ✅ V1 (task 005) |
| Alerte solde faible | ⏳ V1 |
| CTA **Recharger des crédits** (→ `/credits`) | ⏳ V1 — **billing Stripe non livré** ; réservé `ROLE_COMPANY_OWNER` |

## 4.7 🤖 Recherche assistée par IA — **POC** (temporaire)

La [recherche assistée par IA](../recherche_assistee_ia.md) **ne s'accroche pas au cockpit** : elle est
**prototypée en POC sur la Smart Home** pendant les développements : un champ où l'on décrit son besoin en langage naturel, et l'IA
produit des **critères** — jamais des résultats.

> C'est un **banc d'essai**, pas un bloc de hub : il ne suit pas la règle « un bloc = une action » et
> a vocation à **disparaître ou à migrer** une fois le principe éprouvé. À ne pas confondre avec une
> feature validée.

---

# 5. Rôles

| Bloc | `ROLE_COMPANY_MEMBER` | `ROLE_COMPANY_OWNER` |
| --- | --- | --- |
| Accueil · Nouveautés · Reprendre · Dévoilés · Activité équipe | ✅ visible | ✅ visible |
| Crédits — **consommés / total + alerte** | ✅ lecture | ✅ lecture |
| Crédits — **CTA Recharger** | ❌ masqué | ✅ (achat réservé OWNER) |

Ce qui est **partagé `Organization`** : le **solde de crédits**, les **dévoilés**, l'**activité**.
Ce qui est **personnel** : les **recherches** et leurs **favoris / shortlist** (cf. §4.5).

---

# 6. Masquage & routes

- **Nouveautés** ciblent des profils **masqués** (anonymisés tant que non dévoilés) — cohérent avec
  la règle de masquage [../vue_d_ensemble.md §2](../vue_d_ensemble.md).
- **Derniers dévoilés** affichent l'**identité** (profils débloqués).
- Tous les CTA routent vers des vues existantes : `/le-vivier`, `/l-equipe`, `/credits`. La Smart Home
  **n'ouvre jamais** une fiche en propre : elle **renvoie** au Vivier.

---

# 7. États vides

Un hub « n'afficher que ce qui mérite attention » **vit ou meurt sur ses états vides**. Règle : un
bloc sans contenu **disparaît** plutôt que d'afficher un placeholder inutile.

| Situation | Comportement |
| --- | --- |
| Aucune nouveauté | bloc _Nouveautés_ **masqué** |
| Aucune recherche encore jouée | bloc _Reprendre_ **masqué** ; encart discret « Lancer une recherche → Le Vivier » |
| Aucun profil dévoilé | bloc _Dévoilés_ **masqué** |
| Équipe inactive / recruteur seul | bloc _Activité_ **masqué** |
| Premier login (rien à montrer) | message d'accueil onboarding + CTA unique **Explorer Le Vivier** |

---

# 8. Décisions validées

- ✅ **`/` = Smart Home** — l'accueil entreprise est une **page à part entière**.
- ✅ La Smart Home est un **hub d'activité**, **pas un dashboard** ni une page de consultation.
- ✅ Elle est **distincte des deux outils** (Table · Visionneuse) et occupe **tout le cadre** : ni
  console, ni barre haute, ni inspecteur. On y accède par la **marque `showcase-recruiter-webapp-saas`**.
- ✅ Chaque bloc conduit à **une action** ; un bloc **sans contenu est masqué**.
- ✅ CTA **Recharger des crédits** réservé `ROLE_COMPANY_OWNER`.

---

# 9. Évolutions possibles (V2)

Recommandations personnalisées · profils suggérés par l'IA · **alertes** sur recherche enregistrée ·
résumé quotidien · objectifs de recrutement · événements d'équipe · historique récent enrichi.

---

# 10. Parcours utilisateur

```text
Connexion
    │
    ▼
Smart Home ( / )
    ├── Voir les nouveaux profils       → /le-vivier (préfiltré)
    ├── Reprendre la dernière recherche → /le-vivier (recherche active, critères restaurés)
    ├── Consulter l'activité de l'équipe → /l-equipe
    ├── Vérifier / recharger des crédits → /credits
    └── Voir les profils dévoilés       → /le-vivier (tab d'état « Dévoilés »)
                                                │
                                                ▼
                                     Le Vivier — le cockpit (cœur de showcase-recruiter-webapp-saas)
```

---

# 11. TODO

- **Traduire la Smart Home dans le shell** : le mockup cockpit ne la couvre pas. Maquette à
  produire (blocs, responsive, états vides) — c'est le dernier écran de l'espace entreprise sans
  référence visuelle.
- **Trancher le flux d'activité d'équipe** (§4.5) : dévoilements seuls, ou recherches partageables ?
