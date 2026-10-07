# showcase-recruiter-webapp-saas — Espace Entreprise · Topbar

> Bande fixe ① du [shell](./application_shell.md) : marque · crédits · notifications · compte.
> Elle **ne change jamais** selon l'outil actif.
>
> Réf. mockup : `mockup-espace-entreprise-vue-cockpit-latest.html`.

---

# 1. Structure

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│  ⬢ showcase-recruiter-webapp-saas      [Filtres]                    💰 844   🔔³   👤 Julien Martin ▾   │
│    Europe ⇄ Maroc                                              NextGen Industries │
└──────────────────────────────────────────────────────────────────────────────┘
   marque              < 1000px        crédits  notifs   compte
                                       restants
```

| Zone | Contenu |
| --- | --- |
| **Marque** *(gauche)* | logo `showcase-recruiter-webapp-saas` + baseline `Europe ⇄ Maroc` → **Smart Home** (`/`) |
| **Filtres** *(gauche, < 1000 px)* | rouvre la console en **overlay** (elle est masquée sous 1000 px) |
| **Crédits** *(droite)* | **solde restant** → ouvre la **modale Crédits** (§3) |
| **Notifications** *(droite)* | cloche + badge → **drawer** ancré à droite (§4) |
| **Compte** *(droite)* | avatar + `nom` / `entreprise` → menu déroulant qui **porte la navigation** (§2) |

> **Le dropdown « Essais » du mockup** (survol du logo : affichage Tableau/Cartes, 6 palettes, 6 duos
> typographiques, variante de topbar) est un **outil de démo, hors produit**. Ne pas le porter en prod.

---

# 2. Menu compte — il porte la navigation

```text
   👤 Julien Martin                ← bouton compte (déclencheur)
      NextGen Industries
   ┌──────────────────────────────────────┐
   │ Le Vivier                    (actif) │  ← NAVIGATION
   │ Les Offres                           │
   │ L'Équipe                             │
   ├──────────────────────────────────────┤
   │ Mon profil                           │
   ├──────────────────────────────────────┤
   │ 👁 Crédits restants             844  │  → modale Crédits
   ├──────────────────────────────────────┤
   │ Apparence                      🌙    │
   ├──────────────────────────────────────┤
   │ Déconnexion                          │
   └──────────────────────────────────────┘
```

| Entrée | Route | Statut |
| --- | --- | --- |
| **Le Vivier** | `/le-vivier` | ✅ |
| **Les Offres** | `/les-offres` | 🔭 V2 — emplacement réservé, inactif en V1 |
| **L'Équipe** | `/l-equipe` | ✅ |
| **Mon profil** | `/compte` | ✅ |
| **Crédits restants** | → modale Crédits | ✅ |
| **Apparence** | bascule clair / sombre | ✅ |
| **Déconnexion** | fin de session | ✅ |

Le **nom** et l'**entreprise** vivent sur le **bouton compte**, pas dans le déroulant.

> **Pourquoi la nav vit dans le menu compte** : le cockpit est un espace de travail plein écran ;
> la barre haute est déjà prise par les outils (recherche, tabs d'état). Sortir du Vivier est un geste
> **rare** — il n'a pas à occuper la ligne la plus chère de l'écran.

---

# 3. Crédits — chip + modale

**Chip topbar** : le **solde restant** (ex. `844`). **Un seul vocabulaire partout : ce qu'il me
reste.** C'est la seule question que le recruteur se pose avant de cliquer « Dévoiler ».

**Modale « Crédits de dévoilement »** (ouverte par le chip **ou** l'entrée du menu compte) — c'est là,
et là seulement, que vit la **consommation** :

| Bloc | Contenu |
| --- | --- |
| **Jauge** | consommés / total + barre de progression |
| **3 chiffres** | Consommés · **Restants** · Total |
| **Rappel** | *« 1 crédit = 1 dévoilement — identité, coordonnées, CV et compte rendu d'entretien. Le solde est partagé par toute l'entreprise. »* |
| **Consommation par membre** | répartition + barres (on trace **qui** a dévoilé) |
| **CTA** | **Recharger des crédits** — réservé `ROLE_COMPANY_OWNER` (⏳ billing non livré) |

---

# 4. Notifications — cloche + drawer

Cloche + badge du nombre de non-lues → **drawer** ancré à droite, pleine hauteur de la zone de travail.
Chaque entrée : pastille · message · horodatage relatif.

| Type de notification | Statut |
| --- | --- |
| Nouveaux profils correspondant à une **alerte** de recherche enregistrée | 🔭 V2 (alertes = V2) |
| **Activité de l'équipe** (dévoilements, ajouts en shortlist) | ⏳ V1 |

---

# 5. Décisions validées

- ✅ La **navigation** vit **dans le menu compte**.
- ✅ La topbar porte les **crédits restants** et les **notifications** (drawer).
- ✅ **Un seul vocabulaire crédits : le solde restant** ; la consommation vit dans la modale.
- ✅ Une **modale Crédits** unique, atteignable depuis le chip et le menu compte.
- ✅ La **marque** route vers la **Smart Home** (`/`).
- ✅ Le bouton **Filtres** (< 1000 px) rouvre la console en overlay.
- ✅ Le **sélecteur d'ambiances / de variantes** est un outil de **démo**, hors produit.

---

# 6. TODO

- **Recharger des crédits** : billing Stripe non livré (réservé `ROLE_COMPANY_OWNER`).
- **Alertes** (🔭 V2) : sans elles, le drawer ne sert que l'activité d'équipe — vérifier qu'il porte
  assez de valeur en V1.
