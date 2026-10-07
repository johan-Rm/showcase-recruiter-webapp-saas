# TASK ENT-006 — entreprise : la carte de décision

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — rendu vérifié sur le dev.
- **Type** : Frontend espace entreprise.
- **Dépend de** : `API-004` (niveau d'études, projet de retour, photo) · `API-005` (la correspondance)
  · `API-008` (présence au Maroc).
- **Réf** : revue des écrans du 2026-07-28 · mockup `…-vue-cockpit-v23.html` (inspiration graphique,
  aucune reprise de code).

## Contexte

Une carte occupe **un plein écran** et se parcourt au défilement, avec accrochage. Elle **n'a pas
d'onglets**, et le profil complet est un sujet à part, traité plus tard. Elle cesse donc d'être une
fiche : c'est un **résumé de décision**, qui doit porter exactement ce qui permet de trancher garder
ou passer, et rien de plus.

## Décisions

### La carte répond à trois questions, dans cet ordre

**① Est-ce le bon professionnel ?** — les badges d'état, la photo, le **poste en titre** (ses propres
mots), une ligne de faits (`11 ans d'expérience · Lyon, France`), et à droite **Retenir** puis les
**trois accès avec leur coût**.

**② Pourquoi ce profil est là ?** — la correspondance de poste **énoncée** (*votre critère
« développeur » correspond à son poste « Frontend developer »*) et les mots-clés trouvés **surlignés**
là où ils ont matché.

**③ Va-t-il vraiment venir ?** — horizon de retour, présence au Maroc, pied-à-terre, mobilité, puis
**le projet de retour dans ses mots**. C'est le différenciant du produit : ce que showcase-recruiter-webapp-saas sait et
qu'aucun réseau professionnel ne dit. Le mockup l'enterrait dans un quatrième onglet ; sur un écran
unique, il monte.

Puis **les compétences**, celles qui correspondent surlignées.

### Les badges d'état

Disponible · Nouveau · Au Maroc · Favori · **Rencontré**.

**« Rencontré » remplace « Vérifié »** : « vérifié » promet une vérification dont l'objet n'est pas
dit, et le lecteur comble ce vide tout seul, en trop. « Rencontré » énonce le fait — quelqu'un chez
showcase-recruiter-webapp-saas a parlé à cette personne — et c'est la promesse déjà écrite dans le code : *constaté, pas
supposé*.

« Non dévoilé » et « Dévoilé » disparaissent : l'état se lit déjà dans la carte, aux accès proposés.

### Pas de flou sur l'identité

Le mockup **floute un faux nom**. Notre API n'envoie rien — `familyName` vaut `null`. Il n'y a rien à
flouter, et simuler une donnée absente est un mensonge d'interface. Tant que le profil est masqué,
**le poste prend le premier rôle** : c'est lui qu'on lit pour décider, pas deux initiales.

### La photo est là dès le départ

Elle sort du cadre du dévoilement (`API-004`) et existe sur 86 % des profils. La mise en page ne doit
pas **réserver** son emplacement : sur les 14 % restants, la carte se réorganise plutôt que d'afficher
un rond vide.

### Les trois accès de dévoilement ne sont PAS dans ce commit

La décision tient — trois boutons visibles avec leur coût — mais **aucun flux de dévoilement
n'existe côté front** : ni route BFF, ni composable, ni gestion du solde. Les livrer ici aurait donné
trois boutons morts, ce qui est pire que pas de boutons.

Ils partent avec la tâche qui les rend cliquables, et qui porte aussi ce dont ils sont
indissociables : le solde, la confirmation au-delà de dix profils, et le retour d'erreur quand les
crédits manquent.

### Ce qui ne figure pas, et pourquoi

Les initiales (n'aident à aucune décision) · l'employeur (ré-identifiant donc masqué : une ligne pour
un champ vide) · l'e-mail et le téléphone en ligne d'information (leur présence appartient au bouton
Coordonnées) · le secteur et la fonction souhaités (redondants avec le poste) · les dates de création
· la dernière interaction (information d'équipe, pas de décision individuelle).

### La règle qui fait tenir une hauteur fixe

**La carte montre ce que la recherche interroge.** Un critère de langue posé fait apparaître les
langues ; sinon elles restent dans le profil complet. Un contenu variable entre ainsi dans une
hauteur fixe sans jamais déborder — et ce qui s'affiche est exactement ce sur quoi on a cherché.

## Ce qu'on reprend du mockup v23

L'anatomie en bandes figées et l'absence de défilement vertical dans la carte, pour que la molette
n'ait qu'un sens : le profil suivant. Les **tuiles clé/valeur** (micro-label en capitales, valeur en
gras) qui font tenir beaucoup de faits sans scroll. Et le **nom qui change de corps selon l'état**,
petit tant qu'il est masqué, titre une fois qu'il devient une identité.

### « Rencontré » remplace « Vérifié » partout

Dans les trois langues, clé comprise (`search.badge.met`). Et les libellés d'horizon de retour ont
été **remontés** de `search.ai.returnHorizon` à `search.horizon` : ils décrivent le candidat, pas
l'écran de recherche assistée qui les avait introduits. La carte les emploie aussi.

### La règle qui fait tenir une hauteur fixe s'applique par `matches`

`candidate.matches` porte exactement les critères posés : la carte en déduit ce que la recherche
interroge, sans qu'on ait à lui passer les critères. Le niveau d'études n'apparaît donc que si on
cherche dessus.

## Vérification

Rendu sur le dev, recherche à un mot-clé :

- le **poste est le titre** — le profil est masqué, il n'a pas de nom à montrer ;
- la **photo est là** malgré le masquage, et l'en-tête se resserre quand elle manque ;
- la **correspondance** du mot-clé s'affiche, avec ce qui l'a satisfait ;
- le bloc **Retour au Maroc** rend l'horizon, le pied-à-terre, et le **projet de retour dans ses
  mots** ;
- aucun placeholder flouté, aucun emplacement vide.

Lint, format, type-check des quatre espaces, conventions, **42 tests** — verts.
