# showcase-recruiter-webapp-saas — Outil « La Visionneuse » (V1) — Pile de cards

> **Travail UNITAIRE** : lire à fond, dévoiler à bon escient, resserrer la sélection finale. La
> Visionneuse prend **tout le cadre** (console et inspecteur masqués) — un profil, aucune distraction.
>
> Rôle produit et lexique → [../les_2_outils.md §3](../les_2_outils.md) ; shell →
> [application_shell.md](./application_shell.md) ; masquage serveur →
> [../api/recherche_et_masquage.md](../api/recherche_et_masquage.md).
>
> Réf. mockup : `mockup-espace-entreprise-vue-cockpit-latest.html`.

---

# 1. Rôle

La Visionneuse répond à *« celui-là, je le veux ou pas ? »* — et « je paie pour le savoir vraiment ».
C'est là que le **dévoilement** prend son sens : profil par profil, à bon escient.

**On y entre toujours avec un jeu de profils**, jamais avec un profil isolé :

| Entrée | Jeu chargé |
| --- | --- |
| `Ouvrir la visionneuse` (gouttière) | tous les résultats de la recherche |
| Menu d'un **tab d'état** → *Consulter* | le sous-ensemble du tab (Favoris, Dévoilés…) |
| Menu de la **sélection** → *Voir ma sélection* | les profils cochés |
| **Inspecteur** → *Ouvrir la fiche complète* | les résultats, **ancrés** sur ce profil |
| **Mes recherches** → 👁 | les résultats de cette recherche |
| **Vue Shortlist** → *Ouvrir dans la visionneuse* | la shortlist |

---

# 2. Anatomie

```text
② GOUTTIÈRE    💡 Visionnez le profil, dévoilez à bon escient, puis shortlistez.
③ ┌─ BARRE HAUTE ─────────────────────────────────────────────────────────────────────┐
  │ Tech · France                                        [Classiques] [Secondaires]   │
  └───────────────────────────────────────────────────────────────────────────────────┘
④ ┌───────────────────────┐  ┌──────────────────────────────────────────────────────┐
  │ [≥1 an ×][Licence ×]  │  │ ✓Rencontré ★Favori ⏱Disponible ✦Nouveau [Dévoiler]  │
  │ [vue ×]               │  │ ┌────┐                                                │
  │                       │  │ │▓▓▓▓│  ░nom░  ✉ ░░░░  ☎ ░░░░           [CV] [C.R.]  │
  │                       │  │ └────┘  11 ans d'expérience · Paris, France           │
  │                       │  ├──────────────────────────────────────────────────────┤
  │                  3/34 │  │ Profil │ Situation & langues │ Parcours │ Maroc & …   │
  │ ▬▬▬▬▬▬░░░░░░░░░░░░░░░ │  ├──────────────────────────────────────────────────────┤
  │                       │  │                                                      │
  │ 12 vus · 22 à voir    │  │            panneau actif (slide horizontal)          │
  │ · 3 écartés           │  │                                                      │
  │                       │  └──────────────────────────────────────────────────────┘
  │ CE PROFIL / CRITÈRES  │        ↕ scroll vertical, une card = un plein écran
  │ ✓ Études Licence Mast.│
  │ ✗ postgres  Non trouvé│
  │                       │
  │ SHORTLIST           2 │  scrollable,
  │ ⊙ Othmane Sefrioui  ✕ │  toujours
  │ ⊙ Lina Mercier      ✕ │  visible
  ├───────────────────────┤
  │ ▓ Télécharger la SL ▓ │
  └───────────────────────┘
```

| Zone | Rôle |
| --- | --- |
| **Barre haute** *(pleine largeur)* | le nom de la recherche ouverte, et les deux entrées vers ses filtres |
| **Console de recherche** *(gauche)* | l'état de la recherche : où j'en suis, et ce que ce profil vaut face à mes critères |
| **Pile de cards** *(droite)* | une **card par profil** du jeu, `scroll-snap` vertical — une card = un plein écran |

---

# 3. Navigation : le scroll, rien d'autre

On parcourt le jeu **en scrollant de card en card** (snap). La card « courante » est celle dont le haut
a franchi la ligne des 40 % du conteneur ; elle pilote la pagination et le compteur « Vus ».

Chaque card **contient tout** : ses badges, ses actions, ses onglets. Rien n'est déporté dans une
barre d'outils qui obligerait à viser deux endroits.

> **Aucun scroll vertical *dans* une card** : les 4 panneaux d'onglets sont en `overflow-hidden` et
> slident **horizontalement**. La molette pilote donc toujours le déplacement de card en card —
> jamais un scroll imbriqué. C'est la condition pour que le geste reste lisible.

---

# 4. La card d'un profil

## 4.1 Rangée de badges et d'actions

**5 badges**, mêmes que l'inspecteur — une **source unique** (cf. [inspecteur.md §2bis](./inspecteur.md)) :

| Badge | Famille | Quand | Couleur |
| --- | --- | --- | --- |
| `✓ Rencontré` | qualification | entretien showcase-recruiter-webapp-saas réalisé | olive |
| `★ Favori` | état de travail | retenu dans cette recherche | signature de la marque |
| `⏱ Disponible` | qualification | disponible immédiatement | vert |
| `✦ Nouveau` | qualification | arrivé récemment dans le vivier | rose |
| `📍 Au Maroc` ⭐ | qualification | physiquement au Maroc **en ce moment** | rouge |

L'**ordre est fixe** et ne suit pas les badges présents : sur un écran où l'on tranche cent profils,
la place d'un signe compte autant que le signe.

Un seul porte la **signature de la marque** — *Favori*, parce que c'est le seul que le recruteur
produit ; les autres constatent un fait et portent leur propre teinte. Aucun ne se lit à la couleur
seule : chacun garde son libellé en toutes lettres et son icône.

Ils n'apparaissent que **s'ils sont vrais** : un badge nomme un attribut **acquis**, jamais son
absence — « Non rencontré » n'existe pas, et le coin reste vide quand le profil ne porte rien.

**Un profil retenu se voit aussi de loin** : sa card porte une bordure à la couleur de la marque.

L'état de dévoilement se lit **déjà** dans la card — identité floutée, bouton `Dévoiler · 1 crédit`,
CV et compte rendu désactivés : il n'a pas besoin d'un badge.

À droite de la même rangée, les **deux actions de l'outil** :

| Action | Règle |
| --- | --- |
| **Dévoiler · 1 crédit** | visible **seulement si masqué** → modale de coût |
| **Ajouter à la shortlist** | **désactivé tant que le profil n'est pas dévoilé** (`shortlist ⊆ dévoilés`) |

**Deux actions, pas trois** : le tri positif est déjà porté par **Favoriser**, dans la Table.

## 4.2 En-tête

`photo · nom & prénom · e-mail · téléphone` puis `N ans d'expérience · ville, pays`, et à droite les
deux téléchargements : **CV** et **Compte rendu** — **désactivés tant que le profil est masqué**.

Coordonnées **floutées** avant dévoilement (même convention que la Table : on voit qu'il **y a** un
contact, jamais sa valeur).

## 4.3 Les 4 onglets

Slide horizontal, sans scroll vertical :

| Onglet | Contenu |
| --- | --- |
| **Profil** | accroche, résumé, compétences complètes, poste actuel / entreprise / depuis / secteur |
| **Situation & langues** | localisation, disponibilité, horizon de retour, mobilité, langues |
| **Parcours** | expériences, formation |
| **Maroc & dossier** ⭐ | présence, pied-à-terre, mobilité, horizon · **projet de retour exprimé par le candidat** · **compte rendu d'entretien** (si dévoilé + vérifié) · **contacts** (si dévoilé) · **historique d'équipe** |

L'onglet **Maroc & dossier** est le cœur de la promesse : c'est là que se joue la décision.

**Historique d'équipe** : un bloc en bas de cet onglet, trois lignes maximum — « Dévoilé par Sophie le
8 juillet », « Ajouté à la shortlist par Marc ». Il vit là où se joue la décision. Ne montre que ce qui
est **réellement partagé** : les dévoilements (toujours) et l'activité des recherches **partagées**
(cf. [console_contextuelle.md §3](./console_contextuelle.md)).

**Si le profil est masqué**, l'onglet affiche à la place un encart : *« Cette fiche est anonymisée.
Dévoilez le profil pour accéder à l'identité, aux coordonnées, au CV et au compte rendu d'entretien —
et pouvoir le shortlister. »* + le CTA de dévoilement.

---

# 5. La barre haute et la console de recherche

Ensemble, elles répondent à **« sur quoi je cherche »**. Avant elles, la réponse était éclatée entre
une barre d'outils et un tiroir qui se superposait à la pile — on ajustait sa recherche sans voir
l'effet sur les profils.

**La barre haute** traverse l'écran sous la gouttière. À gauche, le **nom de la recherche ouverte**,
en toutes lettres : c'est la seule chose qui dise de quoi on parle, et elle reste lisible sur
téléphone, où la console laisse la place à la pile. À droite, les **deux entrées vers les filtres**.

| Entrée | Ce qu'elle règle |
| --- | --- |
| **Classiques** | poste, mots-clés, années d'expérience, niveau d'études |
| **Secondaires** | présence au Maroc, projet de retour, métier, profil, lieu |

Sur la recherche d'un collègue, **les deux entrées disparaissent** : deux commandes qu'on n'a pas le
droit d'utiliser occuperaient la barre pour rien. Le nom, lui, reste.

**La console**, à gauche, parce que c'est le point de départ de la lecture : on part de ce qu'on
cherche, on va vers ce qu'on trouve. Elle ne porte plus qu'un seul contenu — l'état de la recherche.

### 5.0 Les critères posés — **en tête de la console**

Chaque critère a sa puce, avec sa croix. C'est le **seul endroit** du produit où l'on retire un
critère d'un clic, sans rouvrir un formulaire.

### 5.1 Où j'en suis
Pastille de la **vue chargée** (teintée à sa couleur) · **pagination `3 / 34`** · fine **barre de
progression**, qui mesure les profils **déjà vus**.

La barre suit le travail fait, pas le défilement : sauter des profils la fait reculer, et elle dit
donc toujours la même chose que la ligne juste dessous.

### 5.2 Où j'en suis dans mon tri — **une ligne fine**

Trois mentions sur **une seule ligne** : **combien de profils vus**, **combien restent à voir**,
**combien ont été écartés**. Les deux dernières sont cliquables — elles ramènent au premier profil
non vu, et rouvrent les écartés.

Une ligne et non des chiffres en grand : le panneau sert la **décision**, pas la **mesure**.

*(Le compteur de crédits par recherche reste au modèle — il alimente l'imputation, cf. tâche `076` —
mais il ne s'affiche pas ici.)*

### 5.3 Ce profil face à mes critères

**Une ligne par critère de la recherche**, dans l'ordre où ils ont été posés : ce qui a été demandé,
si le profil le satisfait, et **ce que le profil porte en face**.

| Critère | Demandé | Ce profil |
| --- | --- | --- |
| ✓ Poste | développeur | Développeur logiciel |
| ✓ Expérience | 8 ans et plus | 23 ans |
| ✓ Études | Licence | Master |
| ✓ vue | | Vue.js |
| ✗ postgres | | Non trouvé |

C'est **le seul endroit** qui énonce la correspondance. La card, elle, met en avant les compétences
trouvées **là où elles sont** — une absence, elle, n'a aucun endroit où être mise en avant.

Trois principes s'y appliquent :

- **Le libellé reste factuel.** « Non trouvé », jamais un jugement de compétence : la donnée est
  déclarative, et affirmer une absence ferait écarter de bons profils.
- **Un critère demandé est un seuil, pas une égalité.** « Licence » demandé et « Master » trouvé est
  une correspondance : le niveau du profil s'affiche donc en toutes lettres plutôt que de se
  résumer à une coche.
- **Ce qui n'a pas de contrepartie ne s'invente pas.** Une langue ou une fenêtre de présence n'ont
  rien de comparable sur la card : la ligne dit alors le verdict, et rien d'autre.
- **La provenance se lit au survol.** S'arrêter sur une ligne ouvre une bulle qui redonne le
  critère en entier et dit **d'où vient la correspondance** — son accroche, une compétence, un poste
  occupé. Un terme lu dans une compétence déclarée ne vaut pas le même crédit qu'un terme lu dans
  l'accroche que le candidat s'écrit ; les critères qui n'ont qu'une origine possible n'affichent
  rien de plus.

Le bloc **disparaît** quand il n'y a pas de profil affiché, ou quand aucun critère n'est posé.

### 5.4 Ce que j'emporte — **la shortlist, en grand**

Elle prend **tout l'espace restant** : **toujours visible**, **scrollable**, avec son compteur et le
retrait à l'unité. Puis, en pied plein largeur et en **fond accent**, **Télécharger la shortlist**.

> La Visionneuse sert à **choisir**, et son unique livrable est la **shortlist** : elle est donc
> l'**objet principal** de la colonne. Le geste « Ajouter à la shortlist » fait **apparaître le nom sous
> les yeux**, plutôt que de nourrir un compteur.

**Vide** : *« Aucun profil retenu. Dévoilez un profil, puis ajoutez-le à la shortlist. »*

# 6. Dévoiler depuis la Visionneuse

Le geste est **unitaire** ici (le lot appartient à la Table) et passe par la **même modale** de
confirmation. Après confirmation :

- la card **se dé-floute en place** — le profil se révèle sous les yeux, il ne saute pas ;
- si la vue courante était « Non dévoilés », le profil en sortirait : la Visionneuse **rebascule sur
  « Tous » et se ré-ancre** sur lui plutôt que de sauter au profil suivant ;
- **Ajouter à la shortlist** devient actif ; **CV** et **Compte rendu** deviennent téléchargeables.

---

# 7. Décisions validées

- ✅ La Visionneuse est une **pile de cards** parcourue au **scroll**.
- ✅ Elle prend **tout le cadre** : console et inspecteur masqués.
- ✅ On y entre **avec un jeu de profils**, jamais avec un profil isolé.
- ✅ Les actions (**Dévoiler**, **Shortlister**) vivent **dans la card**, pas dans une barre d'outils.
- ✅ **`shortlist ⊆ dévoilés`** : le bouton est désactivé tant que le profil est masqué.
- ✅ **Aucun scroll vertical dans une card** (onglets en slide horizontal).
- ✅ **Barre haute pleine largeur** : le **nom de la recherche** à gauche, les deux entrées vers les
  filtres — **Classiques** et **Secondaires** — à droite. Elle remplace le tiroir d'affinage, qui se
  superposait à la pile.
- ✅ **Console de recherche à GAUCHE**, d'un seul tenant : l'état de la recherche ouverte.
- ✅ Les **critères posés** vivent en puces retirables en tête de la console.
- ✅ **La console** : progression + **ligne fine** (vus · à voir · écartés) + **ce profil face
  aux critères** + **la shortlist en grand** (visible, scrollable) + **Télécharger la shortlist**.
- ✅ La **correspondance** — ce qui est trouvé comme ce qui manque — s'énonce **dans le panneau**,
  avec la valeur du profil en face de chaque critère.
- ✅ La **barre de progression** mesure les profils **vus**, pas la position dans la pile.
- ✅ L'**historique d'équipe** s'affiche dans l'onglet **« Maroc & dossier »**.
- ✅ Le **compte rendu d'entretien** est **recruteur uniquement**, et n'apparaît qu'après dévoilement.

---

# 8. TODO

- **Responsive** : le panneau de contrôle latéral n'a pas de cadrage mobile (< 1000 px) — passer en
  pied de card ou en tiroir ?
