# TASK 162 — entreprise : la sélection de la Table

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — rendu vérifié sur le dev.
- **Type** : Frontend espace entreprise.
- **Réf** : `vue_la_table.md` §5 (la sélection), task 154 (l'export l'attend), task 161 (le favori).

## Contexte

La Table affichait des lignes sans permettre d'en désigner. Or **tout ce qui suit en dépend** :
l'export porte sur une sélection depuis la task 154, et les dévoilements en lot n'ont pas d'autre
entrée. C'était le verrou du reste du parcours.

## Décisions

### La sélection survit au filtrage, et on le DIT

Changer de vue d'état ou ajuster un critère ne vide pas ce qui est coché : un profil sélectionné
devenu invisible **reste sélectionné**. C'est la règle du cadrage, et elle a un revers — on risquerait
d'agir en lot sur des profils qu'on ne voit plus. L'écran annonce donc leur nombre à côté du
compteur, au lieu de les faire disparaître en silence.

Elle ne tombe qu'en **changeant de recherche** : cocher ici et dévoiler là serait la pire des
surprises.

### Elle vit dans le store, scopée à la recherche

Comme le reste du jeu. Deux raisons : elle doit traverser le rechargement des résultats (un ajustement
de critère la conserve), et « voir ma sélection » l'ouvrira bientôt dans la Visionneuse — un état
local à la Table ne le permettrait pas.

Des identifiants, pas des cartes : la sélection doit survivre à des cartes qui sortent de
l'affichage. Un tableau et pas un `Set` : le state d'un store reste sérialisable pour le SSR.

### Le menu de sélection prend la place de la bascule

Dès qu'une case est cochée, ce qu'on est en train de faire compte plus que l'autre outil. La bascule
revient quand la sélection se vide.

### L'étoile en 4ᵉ colonne, pas en fin de ligne

Favoriser est une **action** qu'on pose en balayant la table, pas une donnée qu'on lit : elle vit à
portée du poste et de l'expérience, là où la décision se prend.

### Le lot CONSOMME la sélection

Une fois les favoris posés, garder les cases cochées laisserait croire qu'il reste quelque chose à
faire. Et l'appel est séquentiel : partir en rafale n'apporte rien à l'API, alors qu'une erreur au
milieu laisse un état lisible — ce qui est passé est passé.

## Livré

- `stores/candidates.ts` : `selected`, `toggleSelected`, `clearSelection`, `getSelectedFor` — et
  `setResults` qui ne vide la sélection qu'au changement de recherche.
- `useSearch()` : `selection`, `favoriteSelection()`.
- La Table : colonne de cases en tête, étoile en 4ᵉ, menu de sélection dans la gouttière avec le
  compteur, l'annonce des cochés hors vue, « Retenir la sélection » et « Tout désélectionner ».

## Reste à faire (hors périmètre de cette tâche)

- **Dévoiler en lot** — les trois accès, avec la modale de coût et le plafond serveur à fixer.
- **Exporter la sélection** — l'API l'attend depuis la task 154.
- **« Voir ma sélection »** : ouvrir la Visionneuse scopée sur les profils cochés.
