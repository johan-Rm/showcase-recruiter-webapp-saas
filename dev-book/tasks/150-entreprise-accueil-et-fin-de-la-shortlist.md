# TASK 150 — entreprise : l'accueil sert les vraies recherches, et la shortlist disparaît

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — vérifié en session réelle sur le dev.
- **Type** : Frontend espace entreprise (+ décision produit).

## Contexte

Deux choses sans rapport, réunies parce qu'elles touchent les mêmes écrans dans la même passe.

L'accueil affichait une liste **en dur** (`const searches: never[] = []`) : un recruteur qui se
connectait lisait « Aucune recherche pour l'instant » alors que `/recherches` lui en montrait une.

Et la **shortlist est retirée du produit**. Le parcours n'a plus deux listes : on retient ses top
profils en **favoris** dans la Visionneuse, et c'est dans la Table qu'on dévoile puis qu'on exporte.

## Décisions

### L'accueil montre les DERNIÈRES, pas toutes

Six recherches, triées par date de mise à jour. L'accueil sert à **reprendre** un travail en cours ;
parcourir, c'est le métier de `/recherches`, qui garde son lien dès qu'il y a plus de six cartes. Le
tri vit dans la page : c'est une règle d'affichage de cet écran, pas du domaine.

La carte est **la même** que celle de `/recherches` — deux rendus de la même chose auraient divergé
au premier ajustement.

### Un seul geste de rétention : le favori

La Visionneuse ne shortliste plus, elle met en favori. Le panneau de contrôle affiche donc **les
favoris** et non plus la shortlist, et le badge « Shortlisté » disparaît de la card.

Conséquence à assumer : **un favori peut être masqué** — c'est le filet large, on retient avant de
payer. Le nom passe donc par le composant de masquage, y compris dans le panneau, là où la shortlist
(réservée aux dévoilés) pouvait l'afficher en clair.

## Livré

- `/` : `useSearches()` à la place de la liste en dur, avec les états chargement / erreur / vide que
  la page n'avait pas, les six dernières recherches et le lien vers la collection complète.
- Visionneuse : panneau « Mes favoris », badge « Shortlisté » retiré, getter `getFavoritesFor`.
- i18n : clés de shortlist renommées en favoris dans les trois langues.

## Reste à faire (hors périmètre de cette tâche)

La shortlist survit **partout ailleurs**, et il faudra la retirer explicitement :

- **Cadrage** : `vue_la_shortlist.md` (document entier) et ~20 documents qui s'y réfèrent, dont
  `arborescence.md` (« l'export = la shortlist ») et `vue_la_visionneuse.md` §5.3.
- **API** : entités `Shortlist` et `ShortlistExport`, quatre endpoints, `ShortlistCsvWriter`,
  l'invariant `shortlist ⊆ dévoilés` et son exception — l'export doit être recyclé sur la
  **sélection** de la Table (à l'unité ou en lot).
- **Contrat** : `isShortlisted` sur la carte candidat, ajouté par la task 146.
