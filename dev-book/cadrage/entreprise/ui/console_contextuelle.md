# showcase-recruiter-webapp-saas — Console contextuelle (V1)

> **Panneau gauche** du cockpit ([application_shell.md](./application_shell.md) §2, zone ④) : c'est
> là qu'on **construit et retrouve une recherche**. La console appartient à la **Table** — la
> Visionneuse la masque.
>
> Critères de filtrage (quoi filtrer, sur quelle donnée) →
> [../criteres_de_filtrage_profils.md](../criteres_de_filtrage_profils.md) ; règles serveur →
> [../api/recherche_et_masquage.md](../api/recherche_et_masquage.md).
>
> Réf. mockup : `mockup-espace-entreprise-vue-cockpit-latest.html`.

---

# 1. Anatomie — 2 panneaux + 1 pied

La console est une colonne pleine hauteur. Son **corps** alterne entre **deux panneaux** ; son
**pied** est toujours là.

```text
┌─ CONSOLE ────────────────────────┐   ┌─ CONSOLE ────────────────────────┐
│                                  │   │                                  │
│  ◈ Modèle de recherche       ▾   │   │  MES RECHERCHES                  │
│  ─────────────────────────────   │   │                                  │
│  ▸ Poste recherché         (2)   │   │  ▸ Tech · France          34  👁🔔⧉│
│  ▸ Présence au Maroc             │   │  ▸ Finance · Direction    12  👁🔔⧉│
│  ▸ Mobilité au Maroc             │ ↔ │  ▸ Maroc Maintenant, …     8  👁🔔⧉│
│  ▸ Disponibilité                 │   │  ▸ Data · Disponible…    17  👁🔔⧉│
│  ▸ Métier                        │   │                                  │
│  ▸ Localisation actuelle         │   │                                  │
│  ▸ Projet de retour              │   │                                  │
│  ▸ Langues                       │   │                                  │
│  ▸ Qualité du profil             │   │                                  │
│                                  │   │                                  │
├──────────────────────────────────┤   ├──────────────────────────────────┤
│▓ RECHERCHE EN COURS              │   │▓ RECHERCHE EN COURS              │
│▓ Tech · France            [ ◎ ] ▓│   │▓ Tech · France            [ ☰ ] ▓│
└──────────────────────────────────┘   └──────────────────────────────────┘
        PANNEAU FILTRES                        PANNEAU MES RECHERCHES
```

| Bande | Contenu | Hauteur |
| --- | --- | --- |
| **Corps** | **Filtres** (accordéons) **↔ Mes recherches** (liste) — bascule, jamais les deux | `1fr`, scroll interne |
| **Pied** | **nom de la recherche en cours** (éditable) + bouton de bascule des panneaux | `auto` |

**Le pied est en fond accent plein.** C'est le seul aplat de couleur signature de la colonne : le nom
de la recherche en cours doit se lire **au premier coup d'œil**, quelle que soit la palette.

**Transition** : « Mes recherches » entre par la **droite** (on avance), « Filtres » revient par la
**gauche** (on retourne à la recherche). Directionnel = lisible.

---

# 2. Panneau **Filtres**

## 2.0 Header — **le résultat**

En tête du panneau, **collant au scroll** : le **nombre de profils** que retournent les critères
courants, en gros, en couleur signature.

```text
  34  profils
      correspondent à votre recherche
  ─────────────────────────────────────
```

- Il **s'anime** à chaque changement de critère : le nombre **roule** vers sa nouvelle valeur (~450 ms,
  easing sortant, comme un compteur mécanique). `prefers-reduced-motion` → valeur posée sèchement.
- Il compte le **périmètre des critères seuls** — les **tabs d'état** (Favoris, Dévoilés, Non dévoilés) ne
  le modifient pas : ils filtrent l'affichage, pas le périmètre.
- **Sans aucun critère, il affiche `0`** et sa phrase devient *« commencez par un poste ou un modèle »* :
  le vivier entier n'est pas un résultat (cf. [vue_la_table.md §3bis](./vue_la_table.md)).

> **Filtrer sans voir bouger le résultat, c'est filtrer à l'aveugle.** Le compteur est la **réponse** à
> chaque geste posé en dessous. Le mouvement fait le travail : un nombre qui saute ne se remarque pas,
> un nombre qui roule attire l'œil et confirme que le filtre a agi.

## 2.1 Modèle de recherche — **select souligné**

Un **select** (pas une combobox : on choisit parmi quelques entrées, on ne saisit rien) listant des
**jeux de critères pré-établis**. Chaque option porte une **pastille** (pleine = active) et **son
nombre de candidats** — on voit ce que le modèle rapporte **avant** de l'appliquer. Choisir un modèle
**remplace** les critères de la recherche courante.

**Forme** : un **trait**, pas une boîte. Le **libellé est le placeholder** (« MODÈLE DE RECHERCHE »),
et il devient **le nom du modèle** une fois choisi — pas d'intitulé au-dessus qui dirait deux fois la
même chose. Un raccourci ne doit pas peser autant qu'un champ de formulaire.

**Décrochage** : toute retouche manuelle d'un critère **vide le select**. Un modèle est un **point de
départ**, pas une étiquette collée à la recherche — sinon il mentirait (« Tech · France » affiché sur
une recherche qui n'en est plus une).

| Modèle (mockup) | Critères |
| --- | --- |
| Tech · France | secteur `Tech` + pays `France` |
| Finance senior | secteur `Finance` + fonction `Direction` |
| Bientôt au Maroc | présence `< 30 j` |
| Vérifiés · dispo | vérifié en entretien + disponible immédiatement |

> Les modèles sont un **raccourci d'amorçage**, pas une entité : ils écrivent dans les critères de la
> recherche courante, qui reste seule persistée. **Catalogue figé par showcase-recruiter-webapp-saas** : il incarne la
> promesse (présence au Maroc, profils vérifiés). Le recruteur qui veut son propre jeu de critères
> **duplique une recherche** — la mécanique existe déjà, inutile d'en ajouter une seconde.

## 2.2 Les 9 accordéons

**Tous repliés par défaut**, forme uniforme : `icône · titre · badge (n sélections) · réinit. · chevron`.
Le badge reste visible **replié** — on voit ce qui filtre sans déplier.

| # | Accordéon | Contenu | Sélection |
| --- | --- | --- | --- |
| 1 | **Poste recherché** | combobox autocomplétion — **fait office de recherche libre sur les intitulés de poste** → chips retirables | **multiple** |
| 2 | **Présence au Maroc** ⭐ | `Maintenant · < 15 j · < 30 j · < 60 j` + **ville de présence** | fenêtre = **exclusive** |
| 3 | **Mobilité au Maroc** | `Tout le Maroc` + villes cibles | multiple |
| 4 | **Disponibilité** | `Disponible immédiatement · Préavis 1 mois · Préavis 3 mois` | multiple |
| 5 | **Métier** | Secteur · Fonction · Compétences | multiple |
| 6 | **Localisation actuelle** | Pays actuel · Ville actuelle (Europe) | multiple |
| 7 | **Projet de retour** ⭐ | horizon (`< 3 mois … en réflexion`) + **pied-à-terre au Maroc** | multiple |
| 8 | **Langues** | Français · Anglais · Arabe / Darija | multiple |
| 9 | **Qualité du profil** | **Vérifié en entretien**, avec sa définition sous la case | — |

**« Vérifié en entretien »** porte sa définition **dans l'UI**, sous la case — c'est le cœur de la
promesse, il ne doit pas dépendre d'un tooltip :

> Rencontré·e par showcase-recruiter-webapp-saas (5–10 min), en recherche active et prêt·e à s'installer au Maroc —
> **constaté, pas supposé.**

## 2.3 Moteur de filtrage

- **OR** entre les valeurs d'une même facette, **AND** entre facettes.
- **Compteurs facettés** : chaque option affiche le nombre de profils qu'elle donnerait, **tous les
  autres filtres appliqués sauf le sien** (comptage facetté classique). Une option à `0` est grisée.
- Les **tabs d'état** (Favoris / Dévoilés / Non dévoilés) ne comptent **pas** comme des
  critères de recherche : ils filtrent l'affichage, pas le périmètre. Les compteurs des tabs se
  calculent donc dans le **périmètre des critères seuls**, indépendamment du tab actif.
- **Un seul point d'entrée textuel** : l'accordéon **Poste recherché**, dont l'input **sert de
  recherche libre sur les intitulés de poste**. Il n'y a **pas de champ de recherche libre générique**.
  *(Le moteur FTS reste donc requis côté API — cf.
  [../api/recherche_et_masquage.md](../api/recherche_et_masquage.md).)*

---

# 3. Panneau **Mes recherches**

Liste des recherches de l'utilisateur. Par ligne : **nom · nombre de profils · boutons d'action** (§3.0).

| Action | Effet | Statut |
| --- | --- | --- |
| **Sélectionner** (clic sur le nom) | active la recherche (critères restaurés dans les Filtres) — on **reste** sur le panneau | ⏳ V1 |
| 👁 **Ouvrir dans la visionneuse** | active la recherche **et** ouvre la Visionneuse sur ses résultats | ⏳ V1 |
| ⧉ **Dupliquer** | copie critères + favoris + shortlist, nom « … (copie) », **sans** basculer dessus | ⏳ V1 |
| **Partager** | rend la recherche visible de l'équipe, **en lecture seule** ; **désactivé** sur la recherche d'un collègue | ⏳ V1 |
| 🔔 **Alerte** | être notifié des nouveaux profils correspondants — **activable pour un collaborateur** sur une recherche partagée | 🔭 V2 |

**Supprimer une recherche** n'est **pas** dans ce panneau : l'action est réservée à une future page de
gestion des recherches (éviter la destruction accidentelle depuis un panneau de navigation).

## 3.0 Anatomie d'une ligne — **invariable**

```text
▸ Tech · France                          34   👁 ⇄ 🔔 ⧉
▸ Finance · Direction                    12   👁 ⇄ 🔔 ⧉      ⇄ actif = partagée
▸ Maroc Maintenant, Casabl…               8   👁 ⇄ 🔔 ⧉
▸ Data · Disponible immédiatem…          17   👁 ⇄̶ 🔔 ⧉      ⇄ désactivé = recherche d'un collègue
```

**Une ligne contient exactement trois choses, toujours les mêmes** :

1. le **nom** de la recherche, **tronqué** s'il est long ;
2. le **nombre de profils** qu'elle retourne ;
3. les **4 boutons d'action** : visionner · **partager** · alerter · dupliquer.

> **Une action impossible se DÉSACTIVE, elle ne disparaît pas.** On ne peut pas partager la recherche
> d'un collègue — on n'en est pas l'auteur : le bouton reste **à sa place**, **désactivé**, et son
> tooltip donne le motif (« Dupliquez-la pour la partager »). **Retirer le bouton casse l'alignement
> des lignes et se lit comme un bug, pas comme une règle.**

> **Ce qui n'entre PAS dans une ligne, et pourquoi.**
>
> - **Pas de badge de nouveautés `+N`.** Les profils qui rejoignent une recherche sont une
>   **notification** : ils vivent dans le **module de notifications**, pas dans une ligne de liste.
> - **Pas de pastille « partagée » / « lecture seule ».** Le partage se lit sur **son bouton** (icône
>   active) ; l'appartenance et la lecture seule sont **énoncées dans le message de guidage** au moment
>   où elles comptent — à la sélection, et au refus d'une écriture (« Recherche de Sophie Martin —
>   lecture seule. Dupliquez-la pour la modifier. »).
>
> **Règle transverse** : ce qui est **dynamique** ou **lié à une action** passe par le **message de
> guidage** de la gouttière ([application_shell.md §4](./application_shell.md)) ; ce qui est
> **récurrent** passe par le **module de notifications**. Jamais par des pastilles empilées dans un
> composant : elles écrasent l'information qui porte la décision — ici, le **nom**.

## 3.0bis Les **nouveautés** d'une recherche — module de notifications

La notion reste **⏳ V1**, mais **hors du panneau** : le nombre de profils ayant **rejoint le jeu de
résultats** d'une recherche depuis sa **dernière consultation** par ce recruteur alimente le **module
de notifications** ([topbar.md §4](./topbar.md)).

- Ouvrir la recherche **remet le repère de lecture à jour**.
- **Ce n'est pas un filtre**, et **ce n'est pas la fraîcheur d'un profil** : c'est un **delta par
  recherche**, propre au recruteur.

> ⚠️ **« A rejoint le jeu de résultats » ≠ « est nouveau dans le vivier ».** Un candidat présent depuis
> six mois qui **déclare un séjour au Maroc du 8 au 24 juillet** vient d'entrer dans la recherche
> « Supply Chain · présent au Maroc » — **sans être un nouveau profil**. C'est **précisément** le moment
> qu'on veut signaler : c'est la promesse du produit (organiser un entretien pendant un séjour prévu).
> Compter les seules **entrées dans le vivier** raterait ce cas.

> **C'est le seul signal qui donne une raison de revenir.** Sans lui, une recherche enregistrée est un
> signet ; avec lui, c'est une veille. Impact modèle : un `lastViewedAt` par **(recherche, recruteur)**
> — cf. tâche `072`. C'est **le même événement** que celui qu'une **alerte** pousse (§3.0ter).

## 3.0ter Ce qu'une **alerte** surveille — 🔭 V2

Une alerte surveille **deux événements** sur le **jeu de résultats** de la recherche — jamais « le
vivier » en général.

### Événement 1 — **Entrée** dans le jeu de résultats

Un profil devient matchant. C'est **le même événement** que les **nouveautés** (§3.0bis) : un seul
calcul, deux usages — le module de notifications le **compte**, l'alerte le **pousse**.

Un profil entre parce qu'il est **nouveau dans le vivier**, **ou** parce qu'un de ses champs a changé :
fenêtre de présence au Maroc ⭐, disponibilité, vérifié en entretien, ouvert aux opportunités,
consentement accordé.

> **Une recherche dit déjà ce qu'elle surveille.** Ses **critères** sont les seuls champs dont un
> changement peut faire entrer un profil : « Supply Chain · présent < 30 j · France » surveille
> mécaniquement le poste, la fenêtre Maroc et le pays. **Il n'y a rien à configurer.**

### Événement 2 — **Mise à jour** d'un profil déjà dans le jeu

Le profil était **déjà** dans mes résultats, et quelque chose de **décisif** bouge. L'entrée ne le capte
pas — il n'entre pas, il y est.

| Champ surveillé | Ce que ça dit |
| --- | --- |
| **Fenêtre de présence au Maroc** ⭐ | *« un profil de votre recherche annonce un séjour à Casablanca du 8 au 24 juillet »* — le moment d'organiser l'entretien |
| **Disponibilité** | passe à « disponible immédiatement », ou change de préavis |
| **Vérifié en entretien** | vient d'être rencontré par showcase-recruiter-webapp-saas (son compte rendu devient accessible) |
| **Ville actuelle** | changement de localisation en Europe |

**Périmètre : tous les profils du jeu de résultats** (pas seulement les favoris / dévoilés).

### Ce qui n'est **pas** configurable

**Le recruteur ne choisit pas les champs surveillés.** Ce serait une **seconde surface de
configuration disant la même chose que la première** — ses critères expriment déjà ce qui compte. Lui
faire répéter son intention dans un autre vocabulaire, c'est fabriquer le grand classique du support :
*« mon alerte ne se déclenche pas »*, parce qu'une case n'était pas cochée dans une liste que personne
ne relit. **Un défaut intelligent, un interrupteur, une fréquence.** Si la finesse manque, on
l'ajoutera en V2 en connaissance de cause.

### Invariants

- **Une notification ne dévoile rien.** Elle décrit le profil **sans l'identifier** (« un Responsable
  Supply Chain, présent au Maroc du 8 au 24 juillet ») — le masquage vaut aussi pour les notifications.
- **Pas de répétition** : un profil déjà signalé pour une recherche ne redéclenche pas la même alerte.
- La **fréquence** (temps réel · résumé quotidien · hebdomadaire) est **orthogonale** à l'événement.

### ⚠️ Point de vigilance — le bruit

Les **mises à jour** portent sur **tous** les profils du jeu : sur une recherche large (200 profils),
les changements de disponibilité tomberont **en continu**. Une alerte bruyante finit **désactivée** — et
on perd aussi les entrées, qui, elles, sont précieuses.

**Parade, sans nouvelle décision produit** : réserver le **temps réel aux entrées** (rares, fortes) et
router les **mises à jour vers le résumé** (quotidien / hebdomadaire), groupées. À valider dès les
premiers volumes réels.

### Écarté à ce stade

Les **sorties** du jeu (profil sorti du périmètre Jarvi, consentement retiré, désactivé). À rouvrir :
un profil **de ma shortlist** devenu injoignable, je devrais le savoir **avant** de l'appeler.

## 3.1 Une recherche partagée est en **lecture seule**

Elle apparaît dans le panneau des autres membres. Le collaborateur la **consulte** (critères, favoris,
shortlist), peut **dévoiler** un profil (le dévoilement est de toute façon commun) et **dupliquer** la
recherche pour en faire la sienne. Il ne peut **ni modifier les critères, ni toucher aux favoris ou à
la shortlist**.

**Rien de tout cela ne s'affiche dans la ligne** : sur la recherche d'un collègue, le bouton
« partager » reste à sa place, **désactivé**, et la lecture seule est **énoncée dans le message de
guidage** — à la sélection, et au refus d'une écriture (cf. §3.0).

> **Un seul auteur par recherche — pas de co-édition, pas de conflit d'écriture.** Celui qui veut la
> retravailler la duplique : sa copie lui appartient. **Non partagée, une recherche reste privée.**

---

# 4. Le pied — la recherche en cours

C'est la pièce la plus dense du cockpit. Trois comportements :

### 4.1 Auto-enregistrement

Une recherche **n'a pas besoin d'être sauvegardée** : elle existe dès qu'on pose un critère et se
persiste au fil de l'eau. « Enregistrer » n'est pas un verbe de showcase-recruiter-webapp-saas.

### 4.2 Nommage automatique

Le nom se **déduit des critères**, dans leur **ordre de sélection** :

```text
1 groupe de facette = 1 segment · valeurs jointes par « , » · 3 segments max, puis « +N »
Présence au Maroc → préfixé « Maroc » une seule fois en tête de segment

   secteur=Tech, pays=France        →  « Tech · France »
   présence=now, ville=Casablanca   →  « Maroc Maintenant, Casablanca »
   (aucun critère)                  →  aucun nom (le placeholder parle)
```

Le nom se **retape en direct** (machine à écrire) à chaque changement de critère : le recruteur voit
sa recherche **se formuler toute seule**.

### 4.2bis Une recherche sans critère n'a pas de nom

Le champ reste **vide**, avec son placeholder (« Nommer cette recherche… »). Le nom **naît du premier
critère** — il n'y a rien à nommer avant, et **aucun nom par défaut** n'est posé.

### 4.3 Renommage manuel

Saisir un nom **désactive l'auto-nommage** (la recherche devient « nommée »). **Vider le champ le
réactive** — le nom repart des critères. Auto-save au blur / après 500 ms.

---

# 5. Ce que la console porte **par recherche**

Une recherche n'est pas qu'un jeu de critères : c'est un **plan de travail**. Chacune porte, en propre :

| Donnée | Portée | Détail |
| --- | --- | --- |
| **Critères** (`order`, ordonnés) | recherche | §4.2 |
| **Favoris** | **recherche** | filet large, profils masqués OK — [vue_la_table.md](./vue_la_table.md) |
| **Shortlist** | **recherche** | liste serrée, **dévoilés uniquement** — [vue_la_visionneuse.md](./vue_la_visionneuse.md) |
| **Crédits consommés** | recherche | compteur d'imputation (le **solde**, lui, est entreprise) |
| **Auteur** + **état de partage** | recherche | privée par défaut, partageable explicitement |

> ⚠️ **Les listes sont rattachées à la RECHERCHE, pas à l'`Organization`.** Seuls le **dévoilement** et
> le **solde de crédits** sont partagés entreprise. Une recherche est **personnelle**, et
> **explicitement partageable** avec l'équipe (⏳ V1). Impact modèle : `Favorite` doit porter une clé
> vers la recherche, la shortlist devient une collection distincte, et la recherche porte un auteur +
> un état de partage → **tâches API à créer** (cf.
> [../vue_d_ensemble.md §2](../vue_d_ensemble.md)).

---

# 6. Décisions validées

- ✅ Console = **panneau gauche**, corps à **2 panneaux** (Filtres ↔ Mes recherches) + **pied
  permanent** (recherche en cours).
- ✅ **9 accordéons repliés** par défaut, badge visible replié, **compteurs facettés** par option.
- ✅ **Un seul point d'entrée textuel** : l'input **Poste recherché** fait office de recherche libre
  sur les intitulés de poste ; il n'y a **pas de champ de recherche libre générique**.
- ✅ Les recherches sont **auto-enregistrées** et **auto-nommées** depuis leurs critères ; renommer à
  la main désactive l'auto, vider le champ le réactive.
- ✅ **Modèles de recherche** = jeux de critères pré-établis, **figés produit** (raccourci, pas une
  entité) ; pour un jeu sur mesure, on **duplique une recherche**.
- ✅ **Favoris, shortlist et crédits consommés sont portés par la recherche**.
- ✅ Une recherche est **privée par défaut**, **explicitement partageable**, et le partage est en
  **lecture seule** (un seul auteur ; pour la retravailler, on **duplique**).
- ✅ Une ligne porte **le nom et le nombre de profils**, rien d'autre : ni badge de nouveautés (→ module
  de notifications), ni pastille d'état (→ message de guidage).
- ✅ **Nouveautés** d'une recherche (profils ayant rejoint son jeu de résultats) — ⏳ **V1**, servies par
  le **module de notifications**.
- ✅ **Alertes = 🔭 V2** ; suppression d'une recherche renvoyée à une page de gestion dédiée.

---

# 7. TODO

- **Page de gestion des recherches** (supprimer / archiver).
- La **recherche assistée par IA** ne s'accroche **pas** au cockpit : elle sera prototypée en **POC sur
  la Smart Home** ([../recherche_assistee_ia.md](../recherche_assistee_ia.md)).
