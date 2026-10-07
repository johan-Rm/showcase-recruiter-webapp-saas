# showcase-recruiter-webapp-saas — Espace Entreprise · Inspecteur (V1)

> **Tiroir droit** du cockpit ([application_shell.md](./application_shell.md) §2, zone ④) : l'**aperçu
> rapide** d'un profil, sans quitter la Table. C'est la responsabilité n°4 de la Table
> ([../les_2_outils.md §3](../les_2_outils.md)) — pas une fiche, pas une lecture approfondie.
>
> Réf. mockup : `mockup-espace-entreprise-vue-cockpit-latest.html`.

---

# 1. Rôle — la frontière avec la Visionneuse

| | Inspecteur | [Visionneuse](./vue_la_visionneuse.md) |
| --- | --- | --- |
| **Question** | « c'est quoi, ce profil ? » | « celui-là, je le veux ou pas ? » |
| **Geste** | clic sur une **ligne** de la Table | ouverture explicite d'un **jeu de profils** |
| **Cadre** | tiroir latéral, la Table reste visible | plein cadre, sans distraction |
| **Contenu** | ce qu'il faut pour **trancher : j'ouvre ou je passe** | tout le dossier (4 onglets, CV, compte rendu) |

L'inspecteur est un **triage**. Il ne remplace jamais la Visionneuse : il y **mène**.

---

# 2. Anatomie

```text
┌─ INSPECTEUR ──────────────────── ✕ ┐
│ ✓Vérifié ★Favori ⏱Disponible       │  ← badges (§2bis)
│                                    │
│ Consultant SAP                     │  ← poste (titre de la fiche)
│ ▓ ░O. Sefrioui░ · 11 ans · Lyon, FR│  ← QUI · COMBIEN · OÙ (nom+photo floutés si masqué)
├────────────────────────────────────┤
│ SITUATION ACTUELLE       (défilant)│
│ Présence au Maroc 08–24 juil       │
│ Disponibilité     Immédiate        │
│ ─ après dévoilement ─              │
│ Entreprise        Atlas Group      │
│ Depuis            2021 · 4 ans     │
├────────────────────────────────────┤
│ Identité protégée · voir les détails│ ← zone CTA, épinglée en bas
│                                    │    (le lien ouvre le popover §3)
│ [ 👁 Dévoiler ce profil · 1 crédit ]│
│ [ ★ Ajouter aux favoris           ]│
│ [ ↗ Voir la fiche profil complète ]│
└────────────────────────────────────┘
```

Le tiroir porte un **liseré accent** quand le profil est **vérifié**. L'en-tête et la zone CTA sont
**épinglés** (jamais scrollés) : l'identité reste lisible, les trois actions toujours atteignables.
Seule la **situation actuelle** défile, entre les deux.

**Trois arbitrages de place** (2026-07-13) — le tiroir est étroit, chaque ligne se mérite :

| Ce qui a bougé | Pourquoi |
| --- | --- |
| Les **compétences** quittent l'inspecteur | On les lit vraiment dans la fiche complète ; ici elles mangeaient la hauteur des faits qui font trancher. |
| La **ville** rejoint la ligne d'identité | *Qui · combien d'expérience · où* se lit d'un trait — et la ligne « Localisation » du bloc faisait doublon. |
| Le **coût du dévoilement** descend dans le popover | Un titre + trois lignes de texte pour dire ce que le bouton dit déjà : le lien suffit, le détail est à un clic. |

L'**employeur** (entreprise, ancienneté) n'apparaît qu'**après dévoilement** : c'est une donnée
**ré-identifiante**, donc payante (cf. [../api/recherche_et_masquage.md](../api/recherche_et_masquage.md)
§ Ré-identification).

---

# 2bis. Les badges — **source unique**

Les **5 badges** du profil sont produits par **une seule fonction**, partagée avec la card de la
Visionneuse ([vue_la_visionneuse.md §4.1](./vue_la_visionneuse.md)) et avec la Table : trois surfaces
qui montrent le même profil doivent le décrire avec **les mêmes mots**, sinon le recruteur relit
l'écran à chaque bascule.

| Badge | Famille | Couleur |
| --- | --- | --- |
| **Rencontré** | qualification | olive |
| **Favori** | état de travail | signature de la marque |
| **Disponible** | qualification | vert |
| **Nouveau** | qualification | rose |
| **Au Maroc** ⭐ | qualification | rouge, distinct de celui des messages d'erreur |

Ils n'apparaissent que **s'ils sont vrais** : un badge nomme un attribut **acquis**, jamais son
absence — le vide est déjà le signal. L'état de dévoilement, lui, est porté par la zone CTA et le
contrat de masquage (§3).

La Table n'en montre qu'un, *Rencontré*, dans la cellule du poste : ses dix colonnes se disputent
déjà la largeur. Mais il vient de la même source, donc du même libellé et de la même teinte.

---

# 3. Le contrat de masquage — affiché, champ par champ

Sous le CTA, un lien **« Voir les détails »** ouvre un **popover** (au-dessus, pour ne pas pousser la
zone CTA) qui dit **exactement** ce que le crédit achète :

| Champ | Non dévoilé | Dévoilé |
| --- | --- | --- |
| Prénom · Nom | initiale seule | ✓ complet |
| E-mail · Téléphone · WhatsApp · LinkedIn | masqué | ✓ accessible |
| **Employeurs (actuel & passés)** | **masqué** | ✓ accessible |
| CV | masqué | ✓ accessible |
| Compte rendu d'entretien ★ | masqué *(ou « — non vérifié »)* | ✓ accessible |
| **Photo de profil** | **masquée** *(vignette floutée)* | ✓ accessible |

Le popover porte aussi le **coût** et le **solde** (« 1 crédit — solde : 844 ») : c'est là, et nulle
part ailleurs, que le recruteur lit ce qu'il achète et ce qu'il lui reste.

> **Le contrat de masquage est rendu visible dans l'UI.** Le recruteur sait ce qu'il achète **avant** de
> payer — ligne à ligne, photo comprise. Le tableau
> canonique vit dans [../api/recherche_et_masquage.md](../api/recherche_et_masquage.md) ; l'UI en est
> le reflet fidèle, pas une reformulation.

---

# 4. Les 3 actions

Toujours dans cet ordre, quel que soit l'état :

| # | Action | Masqué | Dévoilé |
| --- | --- | --- | --- |
| 1 | **Dévoiler** | `Dévoiler ce profil · 1 crédit` (primaire) | `Profil déjà dévoilé` (désactivé) |
| 2 | **Favoris** | `Ajouter / Retirer des favoris` — **fonctionne sur un profil masqué** | idem |
| 3 | **Fiche complète** | `Voir la fiche profil complète` (secondaire — fiche anonymisée) | `Ouvrir la fiche complète` (primaire) |

**Shortlister n'est pas là.** C'est un verbe de la Visionneuse, et il exige un profil dévoilé.

« Fiche complète » **ouvre la Visionneuse** sur les résultats courants, **ancrée sur ce profil** — pas
un écran de plus : l'outil de lecture.

---

# 5. Comportement

| Situation | Effet |
| --- | --- |
| Clic sur une **autre ligne** | l'inspecteur **suit** le profil (reste ouvert) |
| Clic sur un **tab d'état** ou une **action** | l'inspecteur **se ferme** (on a changé d'intention) |
| Ouverture de la **Visionneuse** | l'inspecteur est **masqué** |
| ✕ ou `Échap` | ferme |

---

# 6. Décisions validées

- ✅ L'inspecteur est un **tiroir d'aperçu**, ouvert au clic d'une ligne — **pas** une fiche.
- ✅ Son en-tête dit **qui · combien d'expérience · où** ; la **situation actuelle** dit **présence au
  Maroc** et **disponibilité** — les faits qui font trancher, et eux seuls. Pas de compétences.
- ✅ L'**employeur** (entreprise + ancienneté) n'apparaît qu'**après dévoilement** : il est
  ré-identifiant, donc payant.
- ✅ Il expose le **contrat de masquage champ par champ** — employeurs compris — avant l'achat, dans
  un **popover** qui porte aussi le coût et le solde.
- ✅ **3 actions** dans un ordre fixe : Dévoiler · Favoris · Fiche complète. **Pas de Shortlister.**
- ✅ Il **suit** le profil au clic d'une autre ligne, et **se ferme** dès qu'on change d'intention.

---

# 7. Questions ouvertes

- **Doublon de surface** : inspecteur et Visionneuse montrent le même profil à deux profondeurs.
  Tient-il à l'usage, ou fusionne-t-il avec la Visionneuse ?
- **Comparaison** (2–4 profils côte à côte) et **score de match** : reportés 🔭 V2 — l'inspecteur en
  serait le point d'accroche naturel.
