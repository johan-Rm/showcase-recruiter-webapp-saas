# showcase-recruiter-webapp-saas — Cadrage entreprise

Cadrage de l'espace entreprise (`entreprise.showcase-recruiter-webapp-saas.example`) : un **cockpit à 2 outils** — la **Table**
(trouver) et la **Visionneuse** (choisir) — pour exploiter le vivier et **exporter** une shortlist.

**Source de vérité UI** : `ui-ux-design/validate/mockup-espace-entreprise-vue-cockpit-latest.html`
(refonte cockpit, 2026-07-12).

## Par où commencer

1. [les_2_outils.md](./les_2_outils.md) — **le modèle produit** : les 2 outils, l'entonnoir
   *trouver → choisir → exporter*, Favoris vs Shortlist, le lexique des verbes.
2. [vue_d_ensemble.md](./vue_d_ensemble.md) — périmètre V1/V2, actions, portées, statut de livraison.
3. [ui/](./ui/) — le cockpit et ses écrans.

## Organisation

- [les_2_outils.md](./les_2_outils.md) : modèle produit et lexique.
- [vue_d_ensemble.md](./vue_d_ensemble.md) : synthèse produit, périmètre V1/V2, liens vers les détails.
- [criteres_de_filtrage_profils.md](./criteres_de_filtrage_profils.md) : critères fonctionnels de
  recherche et de filtrage.
- [dimensions-du-vivier.md](../../../docs/showcase-recruiter-webapp-saas/content/2.architecture/dimensions-du-vivier.md) :
  composition du vivier — ce que contient chaque donnée du profil, sa couverture et sa dispersion.
  Passé en doc technique : c'est une mesure de la population, pas un cadrage produit.
- [recherche_assistee_ia.md](./recherche_assistee_ia.md) : recherche assistée par IA (langage naturel
  → filtres) — **POC sur la page Smart Home**, hors cockpit.
- [ui/](./ui/) : cockpit, console, Table, Visionneuse, inspecteur, shortlist, topbar, routes.
- [api/](./api/) : parcours métier, règles API, contrats, synchronisation et invariants serveur.
- [hors_perimetre.md](./hors_perimetre.md) : **le seul doc qui parle de ce qui n'existe pas** — les
  notions écartées qui **survivent encore dans le code**, et qu'il ne faut pas recâbler.

## Règle de classement

- Ce que l'utilisateur **voit ou manipule** → `ui/`.
- Une **route, un statut serveur, une intégration, une contrainte de données ou une règle
  d'autorisation** → `api/`.
- Une information **transverse** reste dans `vue_d_ensemble.md` si elle sert à orienter vers le bon
  document ; le **modèle produit** vit dans `les_2_outils.md`.

## Décisions structurantes en vigueur

| Décision | Date |
| --- | --- |
| **2 outils** (Table · Visionneuse) | 2026-07-08 |
| **Favoris** et **Shortlist** = deux listes **indépendantes** ; `shortlist ⊆ dévoilés` | 2026-07-08 |
| **Favoris / shortlist / crédits consommés portés par la RECHERCHE** ; dévoilés et solde restent partagés entreprise | **2026-07-12** |
| Une recherche est **privée par défaut**, **explicitement partageable** avec l'équipe (V1), **en lecture seule** — un seul auteur, pas de co-édition ; pour la retravailler, on **duplique** | **2026-07-12** |
| La **Table est un tableau de lignes** | **2026-07-12** |
| **Nouveautés** d'une recherche (profils l'ayant rejointe depuis ma dernière visite) — servies par le **module de notifications** | **2026-07-12** |
| **Espace collaboratif** (équipes, commentaires, notification ciblée, résumés) cadré **d'un bloc en V2** | **2026-07-12** |
| Recherches **auto-enregistrées et auto-nommées** ; **modèles figés produit** ; **alertes = V2** | **2026-07-12** |
| L'input **Poste recherché** fait la recherche libre sur les intitulés | **2026-07-12** |
| **Joignabilité visible avant dévoilement** (`hasEmail` / `hasPhone`) | **2026-07-12** |
| **Crédits : un seul vocabulaire — le solde restant** (la consommation vit dans la modale) | **2026-07-12** |
| **« Tout dévoiler »** : plafond serveur + double confirmation | **2026-07-12** |
| **Inspecteur conservé** ; **historique d'équipe** dans l'onglet « Maroc & dossier » | **2026-07-12** |
| Qualification affichée en **ratio brut** (« 21 / 34 profils vérifiés ») | **2026-07-12** |
| **Recherche IA = POC sur la Smart Home**, hors cockpit | **2026-07-12** |

**Plus aucun arbitrage produit en attente.** Ce qui reste est du travail à faire (accusé RGPD de
l'export, export servi par l'API, dette modèle) : [les_2_outils.md §8](./les_2_outils.md) et
[api/README.md](./api/README.md).
