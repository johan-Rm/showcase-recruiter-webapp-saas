# showcase-recruiter-webapp-saas — Espace Entreprise · Les 2 outils & le lexique

> **Modèle produit** de la SaaS Platform (espace entreprise, `entreprise.showcase-recruiter-webapp-saas.example`) : deux outils
> pour **exploiter le vivier**, leurs responsabilités, le couple **Favoris / Shortlist**, et le
> **lexique des verbes** de l'application.
>
> Ce doc **route vers le détail**, il ne le duplique pas : vue d'ensemble produit →
> [vue_d_ensemble.md](./vue_d_ensemble.md) ; cockpit → [ui/application_shell.md](./ui/application_shell.md) ;
> routes → [arborescence-entreprise.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md) ; specs des outils →
> [ui/vue_la_table.md](./ui/vue_la_table.md), [ui/vue_la_visionneuse.md](./ui/vue_la_visionneuse.md) ;
> critères → [criteres_de_filtrage_profils.md](./criteres_de_filtrage_profils.md).
>
> **Phase** : Espace Entreprise = **P2**. La diffusion d'offres et la messagerie sont **hors
> périmètre**.

---

## 1. Le produit en une phrase

> **Une plateforme, 2 outils** — qui permettent au recruteur de **trouver** puis **choisir** les talents
> du vivier showcase-recruiter-webapp-saas, et de les **exporter** pour les contacter.

Les deux outils sont des **modes de travail** du même cockpit
(cf. [ui/application_shell.md](./ui/application_shell.md)) — deux **postures** face au vivier :

| Outil | Posture | Verbe signature |
| --- | --- | --- |
| **La Table** | la **masse** (largeur) — beaucoup de profils, peu de détail | **Trouver** |
| **La Visionneuse** | l'**unité** (profondeur) — un profil, tout le détail | **Choisir** |

---

## 2. L'entonnoir

```text
   TROUVER  ───────▶  CHOISIR  ───────▶  EXPORTER
  (La Table)        (La Visionneuse)     (la sortie)

  je cible dans      je décide profil     j'emporte la
  le volume          par profil           shortlist dévoilée

  ── beaucoup ──▶ ──── un seul ────▶ ──── pour contacter ────▶
```

- **La Table** répond à *« qui, dans le vivier, pourrait m'intéresser ? »*
- **La Visionneuse** répond à *« celui-là, je le veux ou pas ? »* (et je paie pour le savoir vraiment)
- **Export** = la sortie : la shortlist dévoilée, pour contacter hors plateforme.

---

## 3. Responsabilités par outil

### 🔍 La Table — travail de MASSE
> Exploiter le vivier en volume : chercher, trier, qualifier vite, agir en lot. Ne montre **jamais**
> un profil complet (aperçu masqué uniquement). Détail : [ui/vue_la_table.md](./ui/vue_la_table.md).

| # | Responsabilité |
| --- | --- |
| 1 | **Gérer ses recherches** (panneau « Mes recherches » : créer, reprendre, dupliquer) |
| 2 | **Afficher N profils masqués** (**tableau de lignes**) |
| 3 | **Segmenter la population** (7 tabs d'état : Tous · Nouveaux · Au Maroc · Disponibles · Favoris · Non dévoilés · Dévoilés) |
| 4 | **Aperçu rapide** d'un profil sans quitter la table (**inspecteur**, [ui/inspecteur.md](./ui/inspecteur.md)) |
| 5 | **Favoriser** (marque-page large, profils masqués OK) |
| 6 | **Sélection en lot** → Dévoiler · Favoris · **basculer en Visionneuse** |
| 7 | **Dévoiler en lot** (aussi : « Tout dévoiler » un tab d'état) |

**Ne fait pas** : lecture approfondie, CV, compte rendu, dévoilement raisonné profil par profil,
**shortlist**, **export**.

### 🎯 La Visionneuse — travail UNITAIRE
> Travailler les profils un par un, sans distraction : lire à fond, dévoiler à bon escient, resserrer
> la sélection finale. Détail : [ui/vue_la_visionneuse.md](./ui/vue_la_visionneuse.md).

| # | Responsabilité |
| --- | --- |
| 1 | **Charger un jeu de profils** (résultats, tab d'état, sélection, shortlist, ou une recherche) |
| 2 | **Naviguer au scroll** dans la pile de cards (une card = un profil) |
| 3 | **Visionner** le profil complet (fiche anonymisée → complète après dévoilement) |
| 4 | **Dévoiler** (1 crédit → identité + coords + CV + compte rendu ; dévoilé ⇒ **télécharger**) |
| 5 | **Shortlister** (retenir → liste serrée, **dévoilé requis**) |
| 6 | **Exporter** la shortlist (panneau de contrôle → [ui/vue_la_shortlist.md](./ui/vue_la_shortlist.md)) |

**Ne fait pas** : recherche/filtres de masse, gestion des recherches, sélection en lot.

---

## 4. Favoris vs Shortlist — deux listes indépendantes

Décision (2026-07-08) : **Favoris** et **Shortlist** sont **deux collections indépendantes** (pas
d'état progressif, pas de hiérarchie). Un profil peut être favori sans être shortlisté, et inversement.

| Liste | Où | Sur quoi | Contrainte |
| --- | --- | --- | --- |
| **Favoris** | La Table | profils **masqués** OK | aucune — filet large |
| **Shortlist** | La Visionneuse | profils **dévoilés** uniquement | **shortlist ⊆ dévoilés** |

```text
RECHERCHE ─▶ FAVORIS ─────▶ DÉVOILER ─────▶ SHORTLIST ─────▶ EXPORT
 (Table)     (Table)         (Table lot /     (Visionneuse,     = la
             filet large      Visionneuse)     dévoilés only)    shortlist
             profils masqués                   liste serrée      telle quelle
```

**Conséquences**
- L'action **Shortlister** n'est proposée qu'**après dévoilement** (avant : Favoris + Dévoiler seuls).
- **Export = la shortlist, telle quelle** — inutile de filtrer `∩ dévoilés` : garanti par construction.

### Portée : les deux listes appartiennent à la RECHERCHE

**Décision 2026-07-12.** Favoris, shortlist et crédits consommés sont portés par la **recherche** — pas
par l'`Organization`. Seuls le **dévoilement** et le **solde de crédits** restent **partagés
entreprise**.

| Donnée | Portée |
| --- | --- |
| Recherches · **favoris** · **shortlist** · crédits consommés | **recherche** → **son auteur** |
| **Dévoilés** · **solde de crédits** | **`Organization`** (partagés) |

**Une recherche est personnelle, et explicitement partageable (V1).** Le recruteur peut **partager une
recherche avec son équipe** ; elle devient alors visible (critères, favoris, shortlist) des autres
membres. **Tant qu'elle n'est pas partagée, elle reste privée** — c'est la règle qui gouverne aussi le
flux d'activité de la [Smart Home](./ui/smart_home.md) : *ce que je partage devient visible*.

**Le partage est en LECTURE SEULE** (décision 2026-07-12) :

| Le collaborateur peut | Le collaborateur ne peut pas |
| --- | --- |
| **consulter** la recherche (critères, favoris, shortlist) | **modifier** les critères |
| **recevoir une alerte** activée pour lui sur cette recherche *(🔭 V2 — quand les alertes arrivent)* | modifier les **favoris** ou la **shortlist** |
| **dupliquer** la recherche pour en faire la sienne | — |
| **dévoiler** un profil *(le dévoilement est de toute façon partagé entreprise)* | — |

> **Une recherche a un seul auteur.** Pas de co-édition, pas de conflit d'écriture : celui qui veut la
> retravailler la **duplique**. Le collaborateur qui doit agir sur les profils a déjà tout ce qu'il
> faut — le dévoilement est commun, et sa copie lui appartient.

**Impact modèle** :
- **Favoris** = `Favorite` **+ clé vers la recherche** ; **Shortlist** = collection **distincte** à
  créer ; la **recherche** porte un **auteur** et un **état de partage**. → **tâches API à écrire**
  (cf. [vue_d_ensemble.md §2](./vue_d_ensemble.md)).
- **Favoris** et **Dévoilés** sont des **tabs d'état** de la Table
  (cf. [arborescence-entreprise.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)).

---

## 5. Le lexique des verbes

### 5.1 Version courte (3 max par outil)

| Outil | 1 mot | 3 verbes |
| --- | --- | --- |
| 🔍 **La Table** | *Trouver* | **Rechercher · Favoriser · Dévoiler** |
| 🎯 **La Visionneuse** | *Choisir* | **Visionner · Dévoiler · Shortlister** |

> **Dévoiler** est volontairement présent dans les deux triades : c'est le **pivot monétisable**
> commun (en lot côté Table, à l'unité côté Visionneuse). Ces deux triades sont **les steppers
> d'introduction** du cockpit ([ui/application_shell.md §4.1](./ui/application_shell.md)) — le lexique
> est littéralement affiché à l'utilisateur.

### 5.2 Lexique complet

Statut : ✅ V1 · ⏳ V1 à faire · 🔭 V2.

**🔍 TROUVER — La Table**

| Verbe | Sens | Statut |
| --- | --- | --- |
| **Rechercher** | construire la requête (poste, facettes) | ✅ |
| **Filtrer** | affiner via les 9 accordéons | ✅ |
| **Segmenter** | 7 tabs d'état (Tous · Nouveaux · Au Maroc · Disponibles · Favoris · Non dévoilés · Dévoilés) | ✅ |
| **Parcourir** | balayer les profils masqués | ✅ |
| **Consulter** | aperçu rapide dans l'inspecteur (masqué) | ✅ |
| **Favoriser** | marque-page large *(🏠 Table)* | ✅ |
| **Dévoiler** | débloque identité/coords/CV/compte rendu *(en lot — partagé)* | ✅ |
| **Dupliquer** | copier une recherche (critères + listes) | ⏳ |
| **Partager** | rendre une recherche visible de l'équipe (**lecture seule**) | ⏳ |
| **Alerter** | notif sur une recherche (pour moi / pour l'équipe) | 🔭 |
| **Commenter** | échanger sur une recherche *(espace collaboratif)* | 🔭 |

> Les recherches sont **auto-enregistrées** et **auto-nommées** depuis leurs critères
> ([ui/console_contextuelle.md §4](./ui/console_contextuelle.md)).

**🎯 CHOISIR — La Visionneuse**

| Verbe | Sens | Statut |
| --- | --- | --- |
| **Visionner** | fiche complète, un à un *(🏠)* | ✅ |
| **Naviguer** | **scroller** de card en card dans le jeu *(🏠)* | ✅ |
| **Dévoiler** | *(à l'unité — partagé ↔ Table)* | ✅ |
| **Shortlister** | retenir un dévoilé → liste serrée *(🏠)* | ✅ |
| **Télécharger** | CV, compte rendu — profil dévoilé requis *(🏠)* | ✅ |
| **Exporter** | sortir la shortlist en CSV *(🏠)* | ✅ |
| **Noter** | annotation partagée | 🔭 |
| **Statuer** | poser un statut interne | 🔭 |
| **Comparer** | 2–4 profils côte à côte | 🔭 |

**⚙️ PILOTER — le Shell** *(administrer l'espace, hors triptyque « exploiter le vivier »)*

| Verbe | Sens | Statut |
| --- | --- | --- |
| **Se connecter** | login / magic link / invitation | ✅ |
| **Recharger** | crédits (`ROLE_COMPANY_OWNER`) | ⏳ |
| **Suivre** | solde restant + répartition par membre | ✅ |
| **Inviter** / **Gérer l'équipe** | membres & rôles | ✅ |

---

## 6. Périmètre

- **Notre focus** = **Table + Visionneuse** : suffisent à la promesse cœur — *trouver → choisir →
  exporter* (pour contacter hors plateforme).
- **Hors périmètre** : **diffusion d'offres ciblées**, **notes**, **messagerie** intégrée (verbes
  *Rédiger · Diffuser · Publier · Contacter*) ; l'intention V2 reste tracée dans
  [vue_d_ensemble.md §5](./vue_d_ensemble.md).
- **Favoriser** joue le tri positif : c'est le seul geste de tri de la Table.

## 6bis. Pré-export (V1)

Le travail de pré-export est fait en amont (dévoiler + shortlister). Au moment d'exporter, **aucune
action métier supplémentaire n'est requise**. Détail : [ui/vue_la_shortlist.md](./ui/vue_la_shortlist.md).

Flux : **Exporter** → **récap** (nombre de profils, recherche de rattachement) → **mention/accusé
RGPD** → téléchargement CSV.

- **Pas de choix de format** — CSV par défaut, direct.
- **Aucun crédit consommé** — la donnée a été payée au dévoilement (à dire explicitement).
- **RGPD dès la V1** — accusé au clic. ⚠️ **Absent du mockup** : reste ⏳ à implémenter.
- Reporté : dédoublonnage, nommer/dater l'export.

---

## 7. Décisions validées

Les décisions du **2026-07-12** closent les questions ouvertes de la version précédente.

- ✅ Espace Entreprise = **2 outils** (Table · Visionneuse), deux modes du même cockpit.
- ✅ Verbes signature : **Trouver · Choisir** (affichés comme steppers d'intro).
- ✅ **Favoris** et **Shortlist** = deux listes **indépendantes** ; **shortlist réservée aux dévoilés**.
- ✅ **Les deux listes sont portées par la RECHERCHE** ; seuls dévoilés et solde restent partagés
  entreprise.
- ✅ Une recherche est **personnelle** et **explicitement partageable** (⏳ V1) — *ce que je partage
  devient visible*. Le partage est en **lecture seule** : un seul auteur, **pas de co-édition** ; celui
  qui veut la retravailler la **duplique**. On peut **activer une alerte pour un collaborateur** sur une
  recherche partagée (🔭 V2, avec les alertes).
- ✅ **Export = la shortlist** telle quelle, en CSV, sans coût en crédits.
- ✅ La Table est un **tableau de lignes** — « vérifiés en tête » suffit.
- ✅ Les recherches sont **auto-enregistrées** et **auto-nommées** ; **alertes = 🔭 V2**.
- ✅ **Nouveautés** d'une recherche (profils l'ayant **rejointe** depuis **ma** dernière visite) — ⏳ **V1**,
  servies par le **module de notifications**.
- ✅ L'**espace collaboratif** (équipes, commentaires sur une recherche, notification nominative,
  résumés) est cadré **d'un bloc en 🔭 V2** — cf. [vue_d_ensemble.md §4bis](./vue_d_ensemble.md).
- ✅ **Favoriser** reste strictement **Table** ; **Shortlister** strictement **Visionneuse**.
- ✅ L'**inspecteur** est conservé (triage), et l'**historique d'équipe** sur un candidat s'affiche
  dans l'onglet **« Maroc & dossier »** de la card.
- ✅ **Modèles de recherche figés produit** en V1 (le recruteur qui veut son jeu de critères *duplique*
  une recherche).
- ✅ Crédits : **un seul vocabulaire — le solde restant** ; la consommation vit dans la modale.
- ✅ **« Tout dévoiler »** : **plafond serveur** + **double confirmation** au-delà d'un seuil.

## 8. Manques à combler *(plus d'arbitrage ouvert)*

Toutes les questions produit sont tranchées. Ce qui reste est du **travail**, pas de la décision :

- **Accusé RGPD** de l'export et **export servi par l'API** (le mockup génère le CSV côté client) —
  cf. [ui/vue_la_shortlist.md](./ui/vue_la_shortlist.md).
- **Recherche assistée par IA** : prototypée en **POC sur la page Smart Home**, hors cockpit — cf.
  [recherche_assistee_ia.md](./recherche_assistee_ia.md).
- **Dette API** : entité recherche, refonte de `Favorite`, `hasEmail`/`hasPhone`, villes cibles,
  « ouvert aux opportunités », agrégats de facettes — cf. [api/README.md](./api/README.md).
