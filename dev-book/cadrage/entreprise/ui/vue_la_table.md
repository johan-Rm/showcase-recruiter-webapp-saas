# showcase-recruiter-webapp-saas — Outil « La Table » (V1) — Tableau de lignes

> **L'étape où l'on CONTACTE** : la récolte de la Visionneuse, en lignes — dévoiler des coordonnées,
> exporter. Elle s'ouvre sur les **profils retenus**, pas sur le vivier de la recherche : le parcours
> va de *rechercher* à *sélectionner* puis *contacter*, et un écran de contact ouvert sur tout invite
> à dépenser un crédit sur un profil que personne n'a regardé. Ne montre **jamais** un profil complet.
>
> Elle vient **après** la Visionneuse, et le fil d'ariane de sa gouttière y remonte — aucun bouton de
> sortie.
>
> Rôle produit et lexique → [../les_2_outils.md §3](../les_2_outils.md) ; construction de la recherche
> → [console_contextuelle.md](./console_contextuelle.md) ; aperçu d'un profil →
> [inspecteur.md](./inspecteur.md) ; lecture approfondie → [vue_la_visionneuse.md](./vue_la_visionneuse.md).
>
> Réf. mockup : `mockup-espace-entreprise-vue-cockpit-latest.html`.

---

# 1. Rôle

La Table répond à *« qui, dans le vivier, pourrait m'intéresser ? »*. Elle affiche **N profils
masqués** en largeur — beaucoup de profils, peu de détail — et permet d'**agir en lot**.

Un tableau, parce que la comparaison à la volée se fait **en colonnes** : à cette étape, le recruteur
balaie des colonnes (fenêtre Maroc, ville, mobilité, joignabilité), il ne lit pas des profils.

---

# 2. Anatomie

```text
② GOUTTIÈRE     Nom de la recherche › Ma sélection      💡 Les profils retenus, prêts à être contactés
                   ↑ le fil remonte à la Visionneuse ; aucun geste ici, quelle que soit la sélection
③ BARRE HAUTE   Nom de la recherche                                          Retenus 3 · Tous 34
                Nom de la recherche              ✓ 3 sélectionné(s) ▾  ✗   ← si sélection : le
                  │                                                          menu PREND la place
                  └ E-mail ·2cr. · Téléphone ·3cr. · CV ·3cr. · Compte rendu ·3cr. · Exporter
                    ─────────────────────────────────────────────────────────────────────────
                    Voir les profils sélectionnés · Tout désélectionner
④ ┌────────────────────────────────────────────────────────────────────────────────────────────┐
  │ ☐ │ Poste / compétences      │Exp.│Ret.│Photo│ Nom & prénom │E-mail│Tél.│Fenêtre│Ville│Mobi│Opp.│
  ├───┼──────────────────────────┼────┼────┼─────┼──────────────┼──────┼────┼───────┼─────┼────┼────┤
  │ ☐ │ Consultant SAP    actuel │11a │ ★  │ ▓▓  │ ░O. Sefrioui░│ ░░░░ │░░░ │08–24 j│Paris│Casa│Ouv.│
  │   │ ⌜SAP⌟ ⌜ABAP⌟ ⌜Finance⌟   │    │    │     │              │      │    │       │     │    │    │
  │ ☑ │ Chef de chantier   passé │ 9a │ ☆  │ ▓▓  │ ░B. Squalli░ │ ░░░░ │ —  │02–14 s│Lyon │Tout│ —  │
  │   │ ⌜BTP⌟ ⌜Coordination⌟     │    │    │     │              │      │    │       │     │    │    │
       └ BORNÉE à 28 caractères : autant de badges que cette largeur permet
  └────────────────────────────────────────────────────────────────────────────────────────────┘
```

Le tableau **scrolle en interne** (la page ne scrolle jamais). Les **profils vérifiés remontent en
tête** — c'est la notion centrale du système, pas un tri parmi d'autres.

---

# 3. Les colonnes

| # | Colonne | Contenu | Note |
| --- | --- | --- | --- |
| 1 | **Sélection** | case | tout profil est sélectionnable, **dévoilé compris** |
| 2 | **Poste / compétences** | l'intitulé à gauche, `actuel / passé` et les badges d'état à droite ; jusqu'à 3 compétences en badges sur une 2ᵈᵉ ligne | §3.0 |
| 3 | **Exp.** | ancienneté (« 11 ans ») | |
| 4 | **Retenir** | ★ toggle, dans les deux sens | ajoute aux retenus **de la recherche** — cf. ci-dessous |
| 5 | **Photo** | vignette **floutée** tant que non dévoilée | |
| 6 | **Nom & prénom** | **flouté** tant que non dévoilé | |
| 7 | **E-mail** | valeur **floutée** si présente · `—` si absente | §3.1 |
| 8 | **Tél.** | valeur **floutée** si présente · `—` si absente | §3.1 |
| 9 | **Fenêtre Maroc** | `Résident` · `08–24 juil` · `—` | ⭐ différenciant |
| 10 | **Ville actuelle** | ville + pays (Europe) | |
| 11 | **Mobilité** | `Tout le Maroc` ou villes ciblées | |
| 12 | **Ouvert aux opp.** | `Ouvert` ou `—` | §3.2 |

> **L'étoile est en 4ᵉ position, pas en fin de ligne.** **Retenir** est une **action** qu'on pose en
> balayant la table, pas une donnée qu'on lit : elle vit à portée du poste et de l'expérience,
> c'est-à-dire **là où la décision de retenir se prend**. Son entête nomme donc un **verbe**, seul de
> son espèce : les autres colonnes montrent une donnée, celle-ci offre un geste.

## 3.0 Les trois compétences disent **pourquoi la ligne est là**

La ligne dit ce que le profil **est** ; sans elles, rien n'y dit pourquoi il **ressort**. Sur une
recherche à quatre critères dont le profil n'en satisfait que deux, aucune colonne ne nomme lesquels
— il faut ouvrir la fiche, profil par profil.

**Ordre : ce qui a été trouvé d'abord**, dans l'ordre où le serveur l'a reconnu, complété par les
premières compétences du profil. Les afficher dans l'ordre de Jarvi donnerait trois mots sans rapport
avec la recherche, et la seconde ligne aurait coûté une hauteur pour du bruit.

> **Mesuré : la promotion ne s'active que sur ~26 % des profils** (39 sur 149, recherche
> « Développeur logiciel » + mots-clés `backend` et `php`). Ce que le serveur reconnaît sur un
> mot-clé est le plus souvent l'**accroche** du candidat, pas une de ses compétences. Le repli sur
> les trois premières n'est donc **pas un cas de bord** : c'est le cas courant. Une comparaison
> insensible à la casse et aux espaces n'ajoute **aucun** profil ; la sous-chaîne en ajouterait six,
> au prix d'une correspondance approximative.

## La cellule a **deux registres**, et une largeur

L'intitulé tient la gauche et tronque s'il déborde ; **le statut du poste et les badges d'état filent
au bout de la ligne**. Mêlés sur un même plan typographique, ils se lisaient comme un titre à
rallonge dont la longueur changeait à chaque ligne, et la colonne cessait d'être balayable — ce qui
est pourtant sa raison d'être (§1). Le statut dit `actuel` ou `passé` : la colonne s'appelle POSTE et
l'intitulé est juste à côté, le mot « Poste » y serait redondant.

**La cellule est bornée à 28 caractères**, la largeur que l'intitulé avait déjà le droit d'occuper —
et c'est elle qui borne la colonne. Sans elle, la seconde ligne poussait le poste à **48 % du
tableau** : 731 px de compétences contre 241 px de titre au plus. Mesuré après bornage : **23 %**, et
la fenêtre Maroc double, l'e-mail passe de 54 à 90 px.

**Ce qui cède, c'est le NOMBRE de badges — jamais la longueur d'un mot.** La ligne n'en montre
qu'une rangée : ceux qui passeraient à la ligne sont hors champ. Une compétence tronquée se reconnaît
mal, or c'est exactement ce qu'on vient y lire ; et les faire passer à la ligne coûterait de la
hauteur, la ressource rare d'un tableau. Le nombre visible varie donc d'une ligne à l'autre — deux
ou trois selon les libellés — et ce qui tombe est **le moins parlant**, puisque l'ordre met en tête
ce qui a fait entrer le profil.

**Le badge vient de la bibliothèque**, neutre et à contour, sans fond : la charte veut que ce qui se
répète sur chaque ligne se distingue « par l'icône et le libellé, pas par la teinte », et un fond
plein s'annulerait sur une ligne survolée ou sélectionnée. Le dièse a disparu — le contour dit la
même frontière, et il la dit mieux sur un libellé à rallonge comme « Artificial Intelligence (AI) ».

Un profil **sans compétence** ne pose pas de seconde ligne : une ligne vide sous l'intitulé se lit
comme une donnée manquante, alors qu'il n'y a rien à manquer.

> ⚠️ **« Vérifié » n'est pas une colonne, et c'est bien présent dans le produit.** Le **badge** le porte
> (cartes, inspecteur, card de la Visionneuse), la **facette de recherche** « Vérifié en entretien » le
> filtre, et les **profils vérifiés restent en tête** du tableau. Une colonne binaire coûterait une
> largeur de tableau pour redire ce qu'un badge dit déjà — et le tri l'exprime mieux qu'un `✓`.

## 3.1 La joignabilité se voit **avant** de payer

Une colonne E-mail / Tél. affiche **la présence de la coordonnée**, jamais sa valeur :

| Cas | Rendu | Sens |
| --- | --- | --- |
| Coordonnée présente, profil **masqué** | valeur **floutée** | « il y a bien un contact — dévoilez pour l'obtenir » |
| Coordonnée **absente**, profil masqué | `—` | « dévoiler ne permettra **pas** de joindre la personne » |
| Profil **dévoilé** | valeur en clair (ou `—` si absente) | |

> **C'est un vrai enjeu produit** : sans ce signal, le recruteur grille un crédit sur un profil
> injoignable. **Conséquence API** : la réponse doit exposer, avant dévoilement, deux booléens
> (`hasEmail`, `hasPhone`) — **jamais** la valeur (cf.
> [../api/recherche_et_masquage.md](../api/recherche_et_masquage.md)).
>
> ⚠️ Le **flou visuel** est une convention d'UI appliquée à un **placeholder** : l'API ne descend
> jamais la donnée masquée dans le navigateur.

## 3.2 « Ouvert aux opportunités »

Signal issu de Jarvi, **complémentaire** de « Vérifié » : *« nous avons échangé avec la personne, elle
confirme être en attente d'opportunité au Maroc »*. Un profil peut être ouvert sans être vérifié.

---

# 3bis. Sans critère, il n'y a pas de résultat

Tant qu'**aucun critère** n'est posé — recherche neuve **ou** filtres tous réinitialisés — la Table
affiche un **état vide** et le header du panneau Filtres compte **0**.

| Situation | Table | Header |
| --- | --- | --- |
| **Aucun critère** | *« Aucun critère de recherche — commencez par un poste, ou choisissez un modèle. »* | `0` · *commencez par un poste ou un modèle* |
| Critères, aucun résultat | *« Aucun profil ne correspond — élargissez vos critères. »* | `0` · *correspondent à votre recherche* |
| Critères, N résultats | les N lignes | `N` · *correspondent à votre recherche* |

> **Le vivier entier n'est pas un résultat de recherche.** L'afficher par défaut ferait dire au header
> « 180 profils correspondent à votre recherche » alors que rien n'a été demandé : ça laisse croire que
> le travail est fait, et ça pousse à **dévoiler au hasard** — sur un produit où chaque dévoilement
> coûte un crédit, c'est un défaut coûteux, pas une commodité.

**Créer une recherche prépare le geste suivant** : la console bascule sur **Filtres**, l'accordéon
**Poste recherché** s'ouvre, le curseur s'y place, et le guidage dit quoi faire. Le **nom reste vide**
(son placeholder parle) — il naîtra du premier critère, en s'écrivant tout seul.

---

# 4. Les tabs d'état (barre haute, à droite)

**Choix unique**, avec compteur. Ils filtrent l'**affichage**, jamais le **périmètre** de la recherche
— les compteurs restent donc stables quel que soit le tab actif.

**Sept tabs, deux familles.**

| Tab | Famille | Sous-ensemble |
| --- | --- | --- |
| **Retenus** ⭐ | entonnoir | les profils mis de côté dans la Visionneuse — **la vue par défaut** |
| **Tous** | — | tous les profils de la recherche (aucun filtre d'état) |
| **Nouveaux** | qualification | ont **rejoint cette recherche** depuis ma dernière visite |
| **Au Maroc** ⭐ | qualification | **physiquement au Maroc en ce moment** (résident, ou séjour couvrant aujourd'hui) |
| **Disponibles** | qualification | disponibles immédiatement, sans préavis |
| **Non dévoilés** | entonnoir | à dévoiler pour accéder aux coordonnées |
| **Dévoilés** | entonnoir | e-mail et téléphone accessibles |

Les **vues de qualification** décrivent le **candidat** ; les **vues d'entonnoir** disent **où j'en suis
dans mon travail**. Chacune porte **sa couleur** du nuancier, reprise par le **badge** correspondant sur
la fiche : le profil ouvert depuis une vue **garde le signal qui l'y a fait entrer**.

« Vérifié » n'est **pas** un tab : c'est un **critère de recherche**, porté par une facette de la
console et par un badge sur la fiche.

Tant qu'**aucune recherche n'est lancée**, seul « Tous » est actif : filtrer un état n'a pas de sens
sur un périmètre vide.

**Menu par tab** (caret) — agit sur le **sous-ensemble du tab**, pas sur la sélection :

| Action | Effet |
| --- | --- |
| **Consulter dans la visionneuse** | ouvre la Visionneuse sur ce sous-ensemble |
| **Tout dévoiler** | dévoile tous les **non-dévoilés** du sous-ensemble (modale de coût) — masqué sur le tab « Dévoilés » |

> ⚠️ **« Tout dévoiler » est l'action la plus coûteuse de l'application** : sur le tab « Tous » d'une
> recherche large, un clic peut viser des centaines de profils. **Garde-fous** : un **plafond de lot
> côté serveur** (refus au-delà) et une **double confirmation** au-delà d'un seuil
> — le tout-ou-rien protège d'un solde insuffisant, pas d'un solde suffisant dépensé par erreur.
> Cf. [../api/credits_et_devoilement.md](../api/credits_et_devoilement.md).

---

# 5. La sélection

Cocher un profil fait basculer la **droite de la barre haute** : les onglets cèdent la place au
**menu de la sélection**, et reviennent dès que plus rien n'est coché. Rien ne s'ajoute au-dessus du
tableau — c'est la grammaire des deux bandes
([application_shell.md §4.1](./application_shell.md)), et elle vaut pour tous les écrans.

```text
✓ 3 sélectionné(s) · 2 hors de cette vue ▾     ✗
  ├ E-mail · 2 cr.        ─┐
  ├ Téléphone · 3 cr.      │ réservés à l'auteur de la recherche
  ├ CV · 3 cr.             │
  ├ Compte rendu · 3 cr.   │
  ├ Exporter              ─┘
  ├ ───────────────────────
  ├ Voir les profils sélectionnés
  └ Tout désélectionner
```

| Élément | Effet |
| --- | --- |
| **Le déclencheur** | le compte, suivi des sélectionnés hors de la vue courante s'il y en a. Il dit **sur quoi** on va agir, jamais ce que ça coûte — les quatre canaux ont des prix différents, un chiffre unique en inventerait un cinquième |
| **La croix**, hors du menu | vide la sélection en **un** clic et rend les onglets |
| **E-mail · N cr.** · **Téléphone** · **CV** · **Compte rendu** | un canal chacun, **avec son coût** — ou, quand il n'y a rien à payer, **la raison** de ce silence (voir ci-dessous) |
| **Exporter** | la fenêtre d'export et son engagement RGPD |
| **Voir les profils sélectionnés** | bascule sur « Tous » **sans rien décocher** |
| **Tout désélectionner** | vide la sélection |

**Le coût est dans l'entrée, et il exclut DEUX choses.** Ce qui est déjà payé — trois profils
sélectionnés dont un a déjà son e-mail : l'entrée annonce deux crédits, pas trois. Et ce qui **n'a
rien à ouvrir** : un profil sans adresse ne coûte rien sur le canal e-mail, parce qu'il n'y a rien à
lui acheter. Sans ces deux recalculs on ferait croire à une double facturation, ou pire, on vendrait
du vide — sur ce vivier, **1 % des profils ont une adresse**. Le chiffre est là parce qu'en deçà du
seuil de confirmation, **le clic débite sans rien demander**.

**Un canal qui ne coûte rien dit pourquoi.** Les deux zéros ne veulent pas dire la même chose, et
sous un « 0 cr. » commun le recruteur ne pouvait pas savoir lequel il avait sous les yeux :

| Ce que dit l'entrée | Quand | Ce que ça veut dire pour la suite |
| --- | --- | --- |
| **· N cr.** | il reste des profils à payer | le geste est disponible |
| **· déjà obtenu** | tous les sélectionnés ont payé ce canal | bonne nouvelle, on tient déjà la donnée |
| **· aucun** | aucun n'a rien à ouvrir sur ce canal | il faudra joindre ces personnes autrement |

L'entrée est **inactive dans les deux derniers cas** : ce qui change est ce qu'elle dit, pas ce
qu'elle permet. Un lot **mixte** n'a pas de libellé spécial — il reste des profils à payer, donc le
canal affiche son coût. Un canal à la fois vide et déjà payé affiche **« aucun »**, parce que c'est
l'information utile : l'accès est acquis, il n'ouvrira jamais rien.

> **Un canal ne s'éteint que sur une réponse explicite du serveur.** Si le signal de présence n'est
> pas servi — une version d'API plus ancienne, un déploiement en deux temps —, l'écran ne conclut pas
> à l'absence : il affiche le coût, comme avant. Éteindre sur un silence rendrait un contenu payant
> inatteignable. L'argent, lui, ne dépend pas de cette prudence : le serveur refuse de facturer un
> canal vide, que l'écran l'ait proposé ou non.

**Un geste qui aboutit rend la bande** : dévoiler et exporter vident la sélection, et les onglets
reviennent. Sans ça, on resterait enfermé après avoir agi — les lignes se dé-floutent sous les yeux,
et il faudrait penser à sortir d'un état qu'on n'a pas choisi de garder.

**En lecture seule** — la recherche d'un collègue —, le menu ne porte que ses deux dernières
entrées : dévoiler et exporter sont réservés à l'auteur, et le serveur refuse l'export en toutes
lettres. Un menu court dit ce qui est possible ; un menu à moitié éteint fait chercher pourquoi.

> **Retenir n'est pas dans ce menu**, et ce n'est pas un oubli : c'est un jugement qu'on porte sur un
> profil, pas une opération de masse. Le proposer sur un lot coché en balayant reviendrait à
> constituer une liste qu'on n'a pas regardée — ce que la Visionneuse existe pour éviter. Le geste
> reste disponible **ligne à ligne**, dans la colonne « Retenir » (§3).

## 5.1 La sélection survit à la navigation, et c'est pour ça que le menu a une issue

Elle est portée par la **recherche**, pas par l'écran : cocher trois profils, partir sur la
Visionneuse et revenir les retrouve cochés. La Table s'ouvrant sur « Retenus », ces profils peuvent
alors être **hors de la vue affichée** — le déclencheur le dit, « 2 hors de cette vue ».

Et comme les onglets sont masqués tant que la sélection tient, **rien ne permettrait d'aller les
voir**. C'est la seule raison d'être de « Voir les profils sélectionnés » : sans elle, l'écran
annoncerait un problème sans offrir le geste qui le résout.

**Ce qu'on perd en échange, et c'est assumé** : on ne change plus de vue pendant qu'une sélection est
active. Cocher dans « Retenus », basculer sur « Tous », en ajouter, puis dévoiler l'ensemble n'est
plus possible — il faut dévoiler, laisser la sélection se vider, et recommencer.

---

# 6. Interactions

| Geste | Effet |
| --- | --- |
| Clic sur la **case** | (dé)sélectionne |
| Clic sur la **ligne** | ouvre l'**inspecteur** (aperçu, tiroir droit) — [inspecteur.md](./inspecteur.md) |
| Clic sur l'**étoile** | (dé)favorise — feedback : « pop » de l'étoile + message de guidage |
| `Échap` | ferme l'inspecteur / l'overlay console |

**Case et ligne portent deux gestes distincts** : il n'y a **aucun mode** à activer pour sélectionner.

**Animation des résultats** : au re-filtrage, les lignes conservées **glissent** vers leur nouvelle
place (FLIP), les entrantes apparaissent en fondu. On ne « reconstruit » pas la table — le recruteur
suit du regard ce qui reste. `prefers-reduced-motion` → swap direct.

**Dévoilement** : la ligne se **dé-floute en place** (flou → net + flash accent). Le profil « se
révèle », il ne saute pas.

---

# 7. Accessibilité

- Nom, photo et coordonnées floutés sont **non exploitables** (non sélectionnables, hors ordre de
  lecture) : le masquage vaut aussi pour l'a11y, pas seulement pour l'œil.
- Le badge « Vérifié » porte sa définition complète en `title` ; la case du filtre la porte **en
  clair** dans la console.
- Tabs d'état et actions = `button` avec état exposé ; compteurs lus.
- Le message de guidage de la gouttière est une zone `aria-live="polite"`.

---

# 8. Décisions validées

- ✅ La Table est un **tableau de lignes**.
- ✅ **12 colonnes**, dont **Ouvert aux opportunités**.
- ✅ Le **badge « Vérifié »** vit sur les cartes, l'inspecteur et la card de la Visionneuse ; la facette
  « Vérifié en entretien » le filtre depuis la console.
- ✅ **Joignabilité visible avant dévoilement** (présence de l'e-mail / du téléphone, jamais la valeur).
- ✅ **Profils vérifiés en tête** de tableau — **et pas de tri par colonne** : le tri implicite incarne
  la promesse, les critères font le reste.
- ✅ **7 tabs d'état** en choix unique (Tous · Nouveaux · Au Maroc · Disponibles · Favoris · Non dévoilés · Dévoilés), avec menu par tab — 3 de **qualification**, 3 d'**entonnoir**.
- ✅ **La sélection survit à la navigation** — elle est portée par la recherche — et le déclencheur
  annonce les sélectionnés hors de la vue. On ne filtre plus pendant qu'elle tient : le menu porte
  « Voir les profils sélectionnés » pour la rattraper (§5.1).
- ✅ **Le menu de la sélection prend la place des filtres** dans la barre haute, et aucune bande ne
  s'ajoute au-dessus du tableau.
- ✅ **Aucun mode** à activer : la case sélectionne, la ligne ouvre l'inspecteur.
- ✅ **Retenir** est une action de la Table (profils masqués acceptés) ; **Shortlister** ne l'est pas.
  La colonne porte le **verbe**, et elle bascule dans les deux sens — l'écran ne sait pas défaire ce
  qu'il ne sait pas faire.
- ✅ **Trois compétences sous l'intitulé**, celles qui ont fait entrer le profil d'abord (§3.0) —
  en **badges neutres à contour**, et le « trois » est un **plafond**, pas un compte : la largeur en
  montre autant qu'elle permet.
- ✅ **La cellule du poste a deux registres et une largeur** : l'intitulé à gauche, le statut et les
  badges d'état à droite, l'ensemble borné à 28 caractères. La colonne passe de 48 % à 23 % du
  tableau, et ce qui cède quand ça déborde est le nombre de badges, jamais un mot.

---

# 9. TODO

- Brancher les colonnes sur le **contrat réplica** réel (mobilité multi-villes, `hasEmail` / `hasPhone`,
  `openToOpportunities`) — cf. [../api/recherche_et_masquage.md](../api/recherche_et_masquage.md).
- **Pagination / scroll infini** : le mockup charge ~180 profils d'un coup ; l'API pagine (≈ 20/page).
  Cadrer le comportement du tableau — **la sélection doit survivre au-delà de la page courante** (elle
  survit déjà au filtrage, §5).
- **Plafond de lot** de « Tout dévoiler » : valeur à fixer côté API.
