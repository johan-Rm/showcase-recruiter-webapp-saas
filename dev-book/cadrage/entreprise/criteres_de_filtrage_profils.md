# showcase-recruiter-webapp-saas — Critères de filtrage des profils (recherche vivier)

> **But** : liste canonique des critères **fonctionnels** de la recherche recruteur — quoi filtrer,
> sur **quelle donnée**, avec quelle priorité. Ce doc ne porte ni le placement UI (→
> [ui/console_contextuelle.md](./ui/console_contextuelle.md)), ni les règles serveur de masquage
> (→ [api/recherche_et_masquage.md](./api/recherche_et_masquage.md)).
>
> **Fil conducteur (objectifs Vivier v3, 2026-07-03)** : 1) construire une recherche et dévoiler des
> profils · 2) définir une **shortlist associée à une recherche** · 3) **exporter** la shortlist. La
> V1 reste volontairement simple — showcase-recruiter-webapp-saas n'est pas un ATS : la promesse est de trouver un profil
> qualifié basé en Europe, intéressé par le Maroc et **présent physiquement au Maroc au bon moment**.

> ⚠️ Le **badge de nouveautés** d'une recherche (`+N` depuis ma dernière consultation) est **tiré en
> V1** — cf. §9. Ce n'est pas un critère de filtrage.

## Source des données (rappel)

La recherche V1 interroge la **réplica locale PostgreSQL** (Postgres FTS + filtres), alimentée par
l'ingestion Jarvi (task 020) — **jamais Jarvi en direct** (décision 2026-06-23). La recherche IA (MCP
`advancedSearch`) est **V2**.

| Source | Nature | Filtrage |
| --- | --- | --- |
| `Candidate.*` | colonnes normalisées, maître showcase-recruiter-webapp-saas (différenciants) | SQL direct — trivial |
| `Person.*` | identité / localisation actuelle | SQL direct |
| `candidate_jarvi.*` | réplica JSON opaque Jarvi (profil pro) | FTS / JSON path |
| `PresencePeriod`, `Favorite`, `RevealedProfile`… | tables liées | jointure |

**Légende priorité** : ✅ V1 prioritaire · 🟡 V1 secondaire · 🔵 V2.

## Moteur

- **OR** entre les valeurs d'une même facette, **AND** entre facettes.
- **Compteurs facettés** : chaque option affiche son résultat *tous les autres filtres appliqués sauf
  le sien*. Une option à `0` est grisée. → l'API doit exposer des **agrégats par facette**, pas
  seulement une liste de résultats.
- Les **tabs d'état** (§10) ne sont **pas** des critères de recherche : ils filtrent l'affichage dans
  le périmètre, sans le redéfinir.

## 1. Point d'entrée : le poste recherché

| Critère | Donnée | Priorité |
| --- | --- | --- |
| **Poste recherché** (autocomplétion, multi-sélection) | intitulé du poste (référentiel dérivé du vivier) | ✅ |

**Décision 2026-07-12.** Le point d'entrée de la recherche est une **combobox « Poste recherché »** à
autocomplétion multi-sélection, dont **l'input fait office de recherche libre sur les intitulés de
poste**.

> **Conséquences** : (a) le moteur **FTS reste requis** côté API — pour cette recherche libre sur les
> intitulés et le matching des compétences ; (b) le **référentiel de postes** est à produire (le mockup
> le dérive des profils du vivier) ; (c) la [recherche assistée par IA](./recherche_assistee_ia.md) est
> prototypée en **POC sur la page Smart Home**, hors cockpit.

**Modèles de recherche** : jeux de critères pré-établis (« Tech · France », « Bientôt au Maroc »…),
appliqués en un clic à la recherche courante. Raccourci d'amorçage, pas une entité. ⏳ V1 — catalogue
à figer produit.

## 2. Métier

| Critère | Donnée | Priorité |
| --- | --- | --- |
| Secteur recherché | `Candidate.desiredSector` (valeur libre) | ✅ |
| Fonction recherchée | `Candidate.desiredFunction` (valeur libre) | ✅ |
| Compétences | `candidate_jarvi.skills` (jsonb) | ✅ |
| Expériences (intitulé, entreprise) | `candidate_jarvi.workExperiences` (jsonb) | 🟡 |
| Niveau d'études (au moins) | `Candidate.educationLevel` — niveau le plus élevé dérivé des formations (6 niveaux : bac → doctorat) | ✅ |
| Formations (école, intitulé) | `candidate_jarvi.educations` (jsonb) — texte libre | 🔵 V2 |

## 3. Localisation actuelle

| Critère | Donnée | Priorité |
| --- | --- | --- |
| Pays actuel | `Person.country` | ✅ |
| Ville actuelle | `Person.city` | ✅ |

## 4. Projet de retour au Maroc ⭐ différenciant

| Critère | Donnée | Priorité |
| --- | --- | --- |
| Horizon de retour | `Candidate.returnHorizon` — enum `M3` / `M6` / `Y1` / `THINKING` | ✅ |
| Pied-à-terre au Maroc | `Candidate.hasPiedATerre` (+ `piedATerreCity`) | ✅ *(promu : filtre du mockup)* |

L'horizon est **un seul critère multi-valeurs**. Le texte libre `Candidate.returnProject` n'est pas un
filtre — il est **lu sur la fiche** (onglet « Maroc & dossier » de la Visionneuse), et c'est souvent
lui qui emporte la décision.

## 5. Présence physique au Maroc ⭐ différenciant majeur

> La vraie question showcase-recruiter-webapp-saas : **quand ce candidat sera-t-il physiquement au Maroc ?**
> → organiser un entretien pendant un séjour déjà prévu.

| Critère | Donnée | Priorité |
| --- | --- | --- |
| Présent **maintenant** | `PresencePeriod` (période couvrant aujourd'hui) **ou** résident | ✅ |
| Fenêtre glissante **15 / 30 / 60 jours** | `PresencePeriod` | ✅ |
| **Ville de présence** | `PresencePeriod.city` (ou ville de résidence) | ✅ |
| Recherche par plage de dates libre | `PresencePeriod` | 🟡 |

**La fenêtre est un choix exclusif** (on ne coche pas « < 15 j » et « < 30 j » ensemble) ; la **ville**
se combine librement avec elle.

## 6. Disponibilité professionnelle

| Critère | Donnée | Priorité |
| --- | --- | --- |
| Disponibilité (préavis) | `Candidate.availability` — enum `IMMEDIATE` / `NOTICE_1M` / `NOTICE_3M` | ✅ |

Distincte de l'horizon de retour (§4) : un candidat peut être disponible immédiatement mais ne rentrer
que dans 6 mois.

## 7. Mobilité au Maroc

| Critère | Donnée | Priorité |
| --- | --- | --- |
| **Tout le Maroc** | `Candidate.geoMobility` (booléen) | ✅ |
| **Ville(s) souhaitée(s) au Maroc** | ⚠️ **à modéliser** — liste de villes cibles | ✅ |
| Région souhaitée | à modéliser | 🔵 V2 |

**Décision 2026-07-12.** La mobilité s'exprime en **« Tout le Maroc » OU une liste de villes cibles**.
Le mockup l'affiche en colonne de la Table et en facette. **Pas de remote.**

> **Impact modèle** : une **collection de villes cibles** est requise, au-delà du booléen
> `Candidate.geoMobility`. Sémantique de filtrage : filtrer sur une ville retourne *les mobiles
> partout* **OU** *ceux qui ciblent cette ville*.

## 8. Langues

| Critère | Donnée | Priorité |
| --- | --- | --- |
| Langue parlée (Français · Anglais · **Arabe / Darija**) | `candidate_jarvi.languages` (jsonb) | ✅ |
| Niveau minimum | `proficiency` — échelle **Jarvi/LinkedIn**, pas CECRL | 🟡 |

« Arabe / Darija » est **une seule option** de filtre (deux valeurs, un seul geste).

## 9. Qualité du profil

| Critère | Donnée | Priorité |
| --- | --- | --- |
| **Vérifié en entretien** | `Candidate.starInterviewedAt` (booléen dérivé, visible avant dévoilement) | ✅ |
| **Ouvert aux opportunités** | ⚠️ **à modéliser** (source Jarvi) | ✅ *(nouveau)* |

> **Nouveautés d'une RECHERCHE** (« N profils correspondent depuis ma dernière visite ») — attribut de
> la **relation recruteur ↔ recherche**, ✅ **V1** (2026-07-12). Elle répond à *« qu'est-ce qui a bougé
> pendant mon absence ? »* — c'est le signal qui donne une **raison de revenir** sur la plateforme. Elle
> **n'est pas un filtre** : c'est un **badge** sur la ligne de la recherche
> (cf. [ui/console_contextuelle.md §3](./ui/console_contextuelle.md)).

**« Vérifié »** porte une définition **affichée dans l'UI** — c'est le cœur de la promesse :

> Rencontré·e par showcase-recruiter-webapp-saas (5–10 min), en recherche active et prêt·e à s'installer au Maroc —
> **constaté, pas supposé.**

**« Ouvert aux opportunités »** *(nouveau)* est un signal **complémentaire** et **distinct** : *« nous
avons échangé avec la personne, elle confirme être en attente d'opportunité au Maroc »*. Un profil
peut être ouvert sans être vérifié. Affiché en colonne de la Table ; **champ à modéliser** côté
ingestion Jarvi.

Les **profils vérifiés remontent en tête** des résultats (tri implicite, pas un filtre).

## 10. Filtres d'état (tabs de la Table)

Ce ne sont **pas des critères de recherche** : ils segmentent l'affichage **dans** le périmètre de la
recherche courante. Choix unique.

**Sept tabs, deux familles** : la **qualification** décrit le candidat, l'**entonnoir** dit où j'en suis.

| Tab | Famille | Donnée | Portée | Priorité |
| --- | --- | --- | --- | --- |
| **Tous** | — | — | — | ✅ |
| **Nouveaux** | qualification | delta depuis `lastViewedAt` **(recherche, recruteur)** | **recherche** | ✅ |
| **Au Maroc** ⭐ | qualification | `PresencePeriod` couvrant **aujourd'hui**, ou résident | — | ✅ |
| **Disponibles** | qualification | `Candidate.availability = IMMEDIATE` | — | ✅ |
| **Favoris** | entonnoir | `Favorite` **+ clé recherche** ⚠️ | **recherche** | ✅ |
| **Non dévoilés** / **Dévoilés** | entonnoir | `RevealedProfile` | **`Organization`** | ✅ |
| Avec note | `ProfileNote` | — | 🔵 V2 |
| Avec statut interne | `CandidateOption` | — | 🔵 V2 |

*« Vérifié » est un **critère** (§9) et un **badge**, pas un état de travail : il n'a pas de tab.*

> ⚠️ **Portée (2026-07-12).** **Favoris** et **shortlist** sont portés par la **recherche** ; seuls
> **dévoilés** et **solde de crédits** sont partagés entreprise. Une recherche est **privée par
> défaut**, **explicitement partageable**. Impact modèle : `Favorite` doit porter une clé vers la
> recherche, la **shortlist** est une collection **distincte** (`shortlist ⊆ dévoilés`), la recherche
> porte un **auteur** + un **état de partage**. Cf. [les_2_outils.md §4](./les_2_outils.md).

## Filtres serveur implicites (jamais affichés)

Toujours appliqués côté API, quel que soit l'état de l'UI :

- `Candidate.visibility = VISIBLE` (consentement candidat, trace `visibilityUpdatedAt` — task 018)
- `Candidate.active = true` (profil sorti du périmètre Jarvi = désactivé, jamais supprimé — task 020)
- `deletedAt IS NULL` (soft delete `AbstractEntity`)
- masquage champ par champ avant dévoilement → [api/recherche_et_masquage.md](./api/recherche_et_masquage.md)
- résolution des filtres d'état (§10) sur l'`Organization` du recruteur connecté

## Ce que l'écran pose aujourd'hui

Ce document dit ce qu'on **voudrait** filtrer. Voici ce qu'un recruteur peut réellement poser, et
depuis où.

| Formulaire | Filtres |
| --- | --- |
| **Filtres classiques** | poste recherché · mots-clés · années d'expérience · niveau d'études |
| **Filtres secondaires** | présence au Maroc et sa ville · mobilité · pied-à-terre · horizon de retour · disponibilité · secteur visé · secteur d'expérience · fonction visée · langue et son niveau · profil vérifié · pays actuel |
| **Affinages du parcours** | présence au Maroc et sa ville · pied-à-terre · horizon de retour · mobilité · disponibilité · pays actuel · poste actuel · langue parlée · secteur d'expérience |

Les deux premiers posent des **critères** : ils définissent la recherche, la nomment, et se
retrouvent à la visite suivante. Ils vivent dans la console de recherche
([ui/vue_la_visionneuse.md §5](./ui/vue_la_visionneuse.md)).

### Critère et affinage, deux gestes qu'il ne faut pas confondre

Un **affinage** resserre l'angle de lecture sur le jeu **déjà ramené**, au même titre que les tabs
d'état (§10) : il n'entre pas dans la recherche, ne change pas son nom, et disparaît en quittant
l'écran. Il vit dans la colonne gauche de la Visionneuse, là où l'on parcourt les profils un par un.

**Ce qui mérite un volet et ce qui n'en mérite pas** s'y décide sur le volume, parce qu'un volet
coûte un geste à chaque consultation. Les quatre volets tiennent des listes à plusieurs entrées ou
plusieurs contrôles — la mobilité et le pied-à-terre suivent l'horizon de retour, puisque tous trois
disent comment le retour se prépare. Le reste se lit sans rien déplier : l'interrupteur du poste
actuel ouvre la colonne, le pays et la langue la ferment.

**Une liste qui n'offre qu'un seul choix ne s'affiche pas.** Elle ne resserrerait rien — tous les
profils du jeu la satisfont déjà — et occuperait pourtant son intitulé et sa ligne. Sur la plupart
des recherches, le pays n'offre que la France.

C'est ce qui l'autorise à cohabiter avec la règle « chaque écran fait une chose » : la Visionneuse
sert toujours à **choisir**, et changer les critères reste le métier de l'écran de modification.

Deux conséquences pratiques :

- l'affinage porte sur le **jeu entier**, que la Visionneuse charge en un seul appel à l'ouverture
  — filtrer une partie du jeu cacherait des profils qui correspondent ;
- il ne connaît que des facettes que la **carte de profil** sait répondre. En ajouter une suppose
  d'abord de vérifier qu'elle y descend.

**Le poste actuel écarte aussi ceux qu'on ne sait pas classer**, et c'est le seul
affinage dont le libellé porte une réserve. Le serveur ne résout le poste du moment que sur une part
du vivier — mesuré sur une recherche de 41 logisticiens : **3 profils classés « actuel »**, tous les
autres sans mention. Il se tait plutôt que de deviner quand le parcours est ambigu, et ce silence ne
veut pas dire « poste passé ». Le retenir mentirait, l'écarter est ce qu'on fait, et l'écran le dit
sous l'interrupteur.

**La langue s'affine sans niveau**, alors que le moteur, lui, sait filtrer sur un seuil de maîtrise.
La raison tient à la donnée : un tiers du vivier porte une langue **sans** niveau, la maternelle
arrivant souvent nue du réseau d'origine. Exiger « anglais courant » écarterait donc des bilingues
dont personne n'a rien dit. Le seuil reste un critère de recherche ; l'affinage se contente de
« parle cette langue ».

**Le secteur d'expérience répond sur chacun**, pas seulement sur le principal : un candidat passé par
la santé puis la banque se retrouve sur les deux. C'est chez ceux qui ont bifurqué que la question se
pose, et n'en servir qu'un les rendrait invisibles sur la moitié de leur parcours.

### La durée d'expérience change de sens selon le poste

Depuis `API-044`, la borne d'expérience ne compte pas la même chose partout : **avec un poste
recherché**, elle compte les années exercées **dans ce métier** ; **sans poste**, la carrière
entière. Le résultat est plus juste — « architecte, 5 ans » cessait autrement de vouloir dire quoi
que ce soit —, mais le critère ne se lit plus de la même façon selon ce qu'on a rempli.

La phrase de recherche le dit d'elle-même : « avec **5 ans** *dans ce métier*, » quand un poste est
posé, « avec **5 ans** *d'expérience*, » sinon.

⚠️ Cette durée ne totalise que les postes que le dictionnaire a su rattacher — **73 %** de ceux du
vivier. Le filtre est donc **strictement plus sévère** que la réalité : il n'ajoute jamais un profil
qui ne convient pas, il en écarte qui conviendraient.

**Une valeur par filtre.** Le moteur ne retient que la dernière posée sur une même facette — seuls
les mots-clés s'accumulent et se relient en OU. Les formulaires n'offrent donc jamais de choix
multiple : proposer de cocher deux pays laisserait croire qu'on élargit alors qu'on remplace.

**Ce qui n'a pas de formulaire, et pourquoi.** La **ville de résidence** et les **villes cibles au
Maroc** sont décrites ici mais absentes des écrans : le moteur ne sait pas les filtrer, faute d'être
modélisées (cf. *Champs à modéliser*). Les afficher donnerait des cases sans effet.

Les **compteurs par facette** décrits au § *Moteur* n'existent pas côté serveur : ils demandent des
agrégats que l'API n'expose pas. Les **affinages** en portent, eux, et sans rien devoir à l'API —
une fois le jeu entier en mémoire, compter est une boucle sur un tableau.

### ⚠️ Ces filtres portent sur des données que le vivier n'a pas

Mesuré le 2026-08-13 sur les **3 899 candidats** de la base de développement :

| Donnée | Renseignée |
| --- | --- |
| Pays actuel | 99,5 % |
| Compétences | 93 % |
| Langue parlée | 88 % |
| Niveau d'études | 81 % |
| Secteur d'expérience | 78 % — 18 secteurs distincts |
| Présence au Maroc · horizon · disponibilité · pied-à-terre · mobilité · secteur **visé** · fonction **visée** | **0,08 %** (3 profils) |
| Profil vérifié | 0,03 % (1 profil) |

**Les deux secteurs n'ont rien de comparable** et les confondre trompe : celui qu'on a *exercé* se
dérive du parcours LinkedIn et couvre les trois quarts du vivier ; celui qu'on *vise* est une
déclaration du candidat, et personne ne l'a faite.

Les différenciants du produit sont **précisément** les champs vides : le vivier vient de Jarvi et de
LinkedIn, qui ne connaissent ni projet de retour ni séjour prévu. Ces données n'existeront qu'à
mesure que les candidats compléteront leur espace showcase-recruiter-webapp-saas.

Conséquence à connaître avant de juger un écran : **les filtres qui portent la promesse sont inertes
sur le vivier actuel** — toutes leurs options à zéro. Ce n'est pas un défaut d'implémentation, et ça
ne se corrige pas côté recruteur.

## Hors périmètre V1 (V2)

Recherche IA en langage naturel (MCP `advancedSearch`) · **alertes** sur recherche enregistrée ·
matching/scoring · statuts avancés · notes · comparaison de profils · diffusion d'offres ciblées ·
export avancé (au-delà de l'export shortlist).

## Champs à modéliser (dette ouverte par le mockup)

| Champ | Pourquoi | Où |
| --- | --- | --- |
| **Villes cibles au Maroc** | la mobilité s'exprime en villes cibles | §7 |
| **Ouvert aux opportunités** | nouveau signal, colonne + facette | §9 |
| **`hasEmail` / `hasPhone`** | joignabilité visible **avant** dévoilement | [ui/vue_la_table.md §3.1](./ui/vue_la_table.md) |
| **Clé recherche** sur `Favorite` + collection **shortlist** | portée par la recherche | §10 |
| **Référentiel de postes** | point d'entrée de la recherche | §1 |
