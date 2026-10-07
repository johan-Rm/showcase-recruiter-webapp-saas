# showcase-recruiter-webapp-saas — Espace Entreprise · Vue Shortlist & export (V1)

> **La sortie de l'entonnoir** : la liste serrée d'une recherche, et son **export**. Étape
> intermédiaire entre la Table et la Visionneuse — on y vérifie ce qu'on emporte, puis on l'emporte.
>
> Modèle produit → [../les_2_outils.md §4](../les_2_outils.md) ; d'où viennent les shortlistés →
> [vue_la_visionneuse.md](./vue_la_visionneuse.md) ; règles serveur →
> [../api/credits_et_devoilement.md](../api/credits_et_devoilement.md).
>
> Réf. mockup : `mockup-espace-entreprise-vue-cockpit-latest.html`.

---

# 1. Rôle

La shortlist est **la liste serrée d'une recherche**, composée **uniquement de profils dévoilés**
(`shortlist ⊆ dévoilés`, garanti par construction : le bouton *Shortlister* est désactivé sur un
profil masqué).

**Export = la shortlist, telle quelle.** Aucun filtrage `∩ dévoilés` n'est nécessaire à l'export :
l'invariant est tenu **à la source**.

> ⚠️ **Ne pas confondre avec les Favoris** (filet large, profils masqués acceptés, portés par la
> Table). Deux listes **indépendantes** — cf. [../les_2_outils.md §4](../les_2_outils.md).

---

# 2. Deux surfaces pour une même liste

| Surface | Où | Pour quoi |
| --- | --- | --- |
| **Panneau de contrôle** *(Visionneuse)* | **toujours visible**, scrollable, occupe l'espace restant | voir / retirer **en cours de tri** |
| **Vue Shortlist** *(plein cadre)* | superposée à la Table | **relire l'ensemble avant d'exporter** |

```text
┌─ VUE SHORTLIST ─────────────────────────────────────────────────┐
│ ‹ Retour à la recherche              Shortlist · Tech · France  │
│                                             2 profils           │
├─────────────────────────────────────────────────────────────────┤
│ ▓  Othmane Sefrioui   Consultant SAP · Paris, France   08–24 j 🗑│
│ ▓  Lina Mercier       Scrum Master · Lyon, France      12–26 s 🗑│
├─────────────────────────────────────────────────────────────────┤
│ [ Ouvrir dans la visionneuse ]              [ ▓ Exporter (CSV) ]│
└─────────────────────────────────────────────────────────────────┘
```

Chaque ligne : photo · nom · poste · localisation · fenêtre Maroc · **retirer**. Un clic sur la ligne
ouvre le profil **dans la Visionneuse**, ancré dessus.

**Vide** : *« Shortlist vide — dévoilez des profils puis ajoutez-les à la shortlist depuis leur fiche. »*

---

# 3. L'export

**Aucune action métier supplémentaire n'est requise** : le travail de pré-export a été fait en amont
(dévoiler + shortlister).

```text
[ Exporter ] → récap (nombre de profils, recherche de rattachement) → téléchargement CSV
```

| Règle | Détail |
| --- | --- |
| **Format** | **CSV**, sans choix de format |
| **Colonnes** | `Nom · Poste · E-mail · Téléphone · Ville · Pays · Présence Maroc` |
| **Séparateur** | `;` + BOM UTF-8 (ouverture directe dans Excel FR) |
| **Nom de fichier** | `shortlist-<nom-de-la-recherche>.csv` |
| **Coût** | **aucun crédit** — la donnée a été payée au dévoilement (à **dire explicitement**) |
| **Non dévoilés** | **exclus** ; la modale les compte et l'annonce |

> **RGPD — obligatoire.** La modale d'export exige un **accusé explicite** : le téléchargement reste
> **désarmé** tant que la case n'est pas cochée (« ces données sortent de la plateforme, je m'engage à
> ne les utiliser que pour ce recrutement… »). De la donnée personnelle quitte la plateforme — cf.
> [../les_2_outils.md §6bis](../les_2_outils.md). ⏳ Reste à **servir et tracer l'export côté API**.

**Reporté** : dédoublonnage, nommer / dater l'export, formats alternatifs.

---

# 4. Décisions validées

- ✅ La shortlist est **portée par la recherche** (pas par l'`Organization`) — cf.
  [console_contextuelle.md §5](./console_contextuelle.md).
- ✅ **`shortlist ⊆ dévoilés`**, garanti à la source.
- ✅ **Export = la shortlist telle quelle**, en **CSV**, **sans coût en crédits**.
- ✅ Deux surfaces : le **panneau de contrôle** de la Visionneuse — où la shortlist est l'**objet
  principal**, visible en permanence — et la **vue plein cadre** (avant export).

---

# 5. TODO

- **Endpoint d'export** côté API (le mockup génère le CSV côté client — inacceptable en prod : la
  donnée dévoilée doit être servie et **tracée** par l'API).
- Tracer l'export dans l'activité de l'équipe (qui a exporté quoi, quand).
