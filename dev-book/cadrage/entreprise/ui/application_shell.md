# showcase-recruiter-webapp-saas — Espace Entreprise · Shell d'application (V1)

> **Cadre fixe** de la SaaS Platform entreprise : les zones qui ne changent jamais, et la façon dont
> les **deux outils** (Table · Visionneuse) s'y installent.
>
> Ce doc ne décrit que le **cadre**. Ce qui change selon l'outil vit ailleurs : console →
> [console_contextuelle.md](./console_contextuelle.md) ; Table → [vue_la_table.md](./vue_la_table.md) ;
> Visionneuse → [vue_la_visionneuse.md](./vue_la_visionneuse.md) ; inspecteur →
> [inspecteur.md](./inspecteur.md) ; topbar → [topbar.md](./topbar.md) ; routes →
> [arborescence.md](../../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md) ; modèle produit → [../les_2_outils.md](../les_2_outils.md).
>
> Réf. mockup : `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-latest.html`
> (**source de vérité UI**).

---

# 1. Principe — un cockpit, deux postures

Le shell est un **cockpit** : une grille plein écran (`100dvh`, aucun scroll de page) qui héberge les
deux outils. Passer de la **Table** à la **Visionneuse** ne change **ni la topbar, ni la barre haute,
ni la gouttière** — seule la zone de travail se recompose.

---

# 2. Zoning

```text
┌──────────────────────────────────────────────────────────────────────────────┐
│ ① TOPBAR            showcase-recruiter-webapp-saas ·  · · · · · ·  💰 844 🔔  👤 Julien Martin ▾    │  --topbar-height
├──────────────────────────────────────────────────────────────────────────────┤
│ ② GOUTTIÈRE         fil d'ariane ›…              💡 message de guidage…       │  h-8, SANS fond
├──────────────────────────────────────────────────────────────────────────────┤
│ ③ BARRE HAUTE       + Nouvelle recherche    Tous · Nouveaux · Au Maroc · … (7) │  h-11 (auto)
├──────────────────────────────────────────────────────────────────────────────┤
│ ④ ZONE DE TRAVAIL                                                     1fr      │
│ ┌────────────────┐ ┌────────────────────────────────────┐ ┌─────────────────┐ │
│ │                │ │                                    │ │                 │ │
│ │  CONSOLE       │ │  VUE PRINCIPALE                    │ │  INSPECTEUR     │ │
│ │  (gauche)      │ │  (centre)                          │ │  (droite,       │ │
│ │                │ │                                    │ │   tiroir)       │ │
│ │  Filtres  ↔    │ │  Table  ·  Shortlist  ·            │ │                 │ │
│ │  Mes recherches│ │  Visionneuse (plein cadre)         │ │  aperçu profil  │ │
│ │                │ │                                    │ │                 │ │
│ ├────────────────┤ │                                    │ │                 │ │
│ │ ▓ Recherche 1 ▓│ │                                    │ │                 │ │
│ └────────────────┘ └────────────────────────────────────┘ └─────────────────┘ │
└──────────────────────────────────────────────────────────────────────────────┘
```

Grille externe : `grid-rows-[var(--topbar-height)_auto_auto_minmax(0,1fr)]`, gouttière `--gap`
uniforme, `overflow:hidden` — **la page ne scrolle jamais**, seules les zones internes le font.

| Zone | Rôle | Détail |
| --- | --- | --- |
| ① **Topbar** | marque · crédits · notifications · compte (**la navigation vit dans le menu compte**) | [topbar.md](./topbar.md) |
| ② **Gouttière** | le fil d'ariane à gauche, le guidage ou une notification à droite — **aucun geste** | §4 |
| ③ **Barre haute** | à gauche **Nouvelle recherche** ; à droite les **tabs d'état** de l'outil actif | §3 |
| ④ **Console** *(gauche)* | Filtres ↔ Mes recherches ; en pied, **le nom de la recherche en cours** | [console_contextuelle.md](./console_contextuelle.md) |
| ④ **Vue principale** *(centre)* | Table, vue Shortlist, ou Visionneuse en plein cadre | [vue_la_table.md](./vue_la_table.md) · [vue_la_visionneuse.md](./vue_la_visionneuse.md) |
| ④ **Inspecteur** *(droite)* | tiroir d'**aperçu** d'un profil, ouvert au clic d'une ligne | [inspecteur.md](./inspecteur.md) |

---

# 3. Barre haute — la rangée qui alterne

Les deux outils **se partagent la rangée ③** : elle porte les contrôles de l'outil actif.

> **La barre haute est SOUS la gouttière.** Elle porte les **tabs d'état** — c'est-à-dire des
> **filtres** — et un filtre doit **toucher ce qu'il filtre** : les tabs sont collés au tableau. La
> gouttière, elle, vit sous la topbar : le **discours** de l'application (guidage, intro) précède le
> travail.

| Outil actif | Gauche | Droite |
| --- | --- | --- |
| 🔍 **Table** | `+ Nouvelle recherche` | **7 tabs d'état** : Tous · Nouveaux · Au Maroc · Disponibles · Favoris · Non dévoilés · Dévoilés (compteurs + menu par tab) |
| 🎯 **Visionneuse** | `‹ Retour à la recherche` | **quick views** du jeu chargé (mêmes vues, même vocabulaire) |

> Même anatomie, même vocabulaire des deux côtés : le recruteur ne réapprend rien en changeant d'outil.

---

# 4. Gouttière — situer ou informer

Bande **sans fond** (transparente, sur le fond de page), **rangée ② — juste sous la topbar**. Elle
porte deux choses, et **jamais un geste** :

1. **Où l'on est** *(gauche)* — le fil d'ariane, dont le dernier maillon nomme la page et dont les
   précédents y ramènent. C'est le seul procédé de remontée de l'espace : **aucun bouton de sortie**.
2. **Ce qu'il faut savoir** *(droite)* — le guidage contextuel, une notification, une aide. En
   pastille, parce qu'un texte nu à cette place se lit comme un bouton éteint. Zone
   `aria-live="polite"`.

Elle a porté, un temps, le menu de la sélection — quatre canaux de dévoilement, l'export, le
retrait. Une bande dont on dit qu'elle « ne doit jamais peser » ne pouvait pas être l'endroit où
l'on dépense des crédits.

### 4.1 Ce que porte chaque bande — la grammaire, pour tous les écrans

> **La gouttière situe ou informe.**
> **La barre haute porte le nom, et à sa droite un état contextuel** : les filtres, l'avancement, ou
> les actions sur la sélection. **Un seul à la fois.**

| Bande | Ce qu'elle porte | Ce qu'elle ne porte jamais |
| --- | --- | --- |
| **Gouttière** ② | le fil d'ariane · le guidage, une notification, une aide | aucun geste — ni navigation en bouton, ni action |
| **Barre haute** ③ | le **nom** de ce sur quoi on travaille, à gauche · à droite **un** état : filtres, avancement, ou actions sur la sélection | deux états à la fois |

**La droite de la barre haute est faite pour alterner**, et c'est ce qui rend la grammaire tenable :
la Visionneuse y met la jauge et le rang, la Table ses onglets — puis le menu de la sélection dès
qu'un profil est coché. Y loger les actions évite d'ajouter une quatrième bande qui apparaîtrait et
pousserait les lignes ; c'est ce qu'une version antérieure faisait, et l'écran portait alors deux
grammaires, une bande qui alterne et une bande qui surgit.

**Ce que ça coûte, et c'est assumé** : on ne change plus de vue tant qu'une sélection est active.
Le menu porte donc « Voir les profils sélectionnés », qui bascule sur le jeu entier sans rien
décocher — sans quoi une sélection héritée d'un autre écran s'annoncerait « 2 hors de cette vue »
sans offrir le geste qui les ramène.

**La sortie d'un état qui masque quelque chose se voit** : une croix vide la sélection en un clic, à
côté du déclencheur et hors du menu. Reléguée dans le menu seul, elle demanderait de deviner qu'elle
est là-dedans, pour revoir des filtres qu'on n'a pas choisi de perdre.

**Un geste qui aboutit rend la bande** : dévoiler et exporter vident la sélection, et les filtres
reviennent. Sans ça, on reste enfermé après avoir agi.

### 4.2 Steppers d'introduction

À l'ouverture d'un outil, un **stepper** balaie la gouttière (l'accent glisse de step en step) puis
s'efface — c'est une **présentation du parcours**, pas un état applicatif ; aucun step n'est cliquable.

| Outil | Steps | Rejoué |
| --- | --- | --- |
| 🔍 **Table** | `Rechercher → Retenir → Dévoiler` | une fois, au chargement |
| 🎯 **Visionneuse** | `Visionner → Dévoiler → Shortlister` | **à chaque ouverture** |

Le guidage ne démarre qu'**après** l'intro. `prefers-reduced-motion` → pas de sweep, pas de frappe.

---

# 5. Comportement des zones (④)

| Situation | Console | Vue principale | Inspecteur |
| --- | --- | --- | --- |
| **Table** (défaut) | visible | Table | fermé |
| Clic sur une **ligne** | visible | Table | **ouvert** (tiroir) |
| **Vue Shortlist** | visible | panneau shortlist (superposé) | fermé |
| **Visionneuse** | **masquée** | plein cadre (cards + panneau de contrôle) | **masqué** |
| Écran **< 1000 px** | **masquée**, rouverte en **overlay** via le bouton `Filtres` de la topbar | plein cadre | overlay |

**Règles de fermeture** — `Échap` remonte du plus superficiel au plus profond :
`modale → visionneuse → vue shortlist → notifications / overlay console / inspecteur`.

**Règle de recentrage** : toute interaction dans la vue principale ou la barre haute **ramène la
console sur le panneau « Filtres »** (on revient toujours à la recherche en cours). Sélectionner une
recherche dans le panneau « Mes recherches » est la seule exception.

---

# 6. Décisions validées

- ✅ Le shell est un **cockpit plein écran** : topbar · barre haute · gouttière · zone de travail.
  Aucun scroll de page.
- ✅ La **console est à gauche**, en colonne pleine hauteur.
- ✅ **Deux outils seulement** : Table et Visionneuse (cf. [../les_2_outils.md §6](../les_2_outils.md)).
- ✅ Les deux outils **partagent la gouttière** (rangée ②) et la **barre haute** (rangée ③) : mêmes
  emplacements, mêmes tabs, même vocabulaire.
- ✅ **La barre haute est SOUS la gouttière** : les tabs d'état sont des filtres, ils touchent le
  tableau qu'ils filtrent.
- ✅ L'**inspecteur** (droite) est un **tiroir d'aperçu**, pas une fiche : la lecture approfondie
  appartient à la Visionneuse.
- ✅ La **navigation applicative vit dans le menu compte** de la topbar (plus d'onglets au centre).
- ✅ Le **guidage contextuel** est une brique du shell, pas d'un outil.
- ✅ **Deux bandes, deux responsabilités** (§4.1) : la **gouttière situe ou informe** — fil d'ariane,
  guidage, notification —, la **barre haute porte le nom** et, à sa droite, **un** état contextuel :
  filtres, avancement, ou actions sur la sélection. Jamais deux à la fois.
- ✅ **Aucune bande ne s'ajoute au moment d'agir** : les actions sur la sélection prennent la place
  des filtres dans une bande qui alterne déjà, plutôt que de surgir au-dessus du contenu et d'en
  pousser les lignes.

---

# 7. Questions ouvertes

- **Mobile (< 1000 px)** : la Visionneuse (cards + panneau de contrôle latéral) n'a pas de cadrage
  responsive ; le panneau de contrôle doit-il passer en pied de card ou en tiroir ?
- **Inspecteur vs Visionneuse** : deux surfaces de lecture pour un même profil — l'inspecteur
  survit-il à l'usage réel, ou fusionne-t-il avec la Visionneuse ?
