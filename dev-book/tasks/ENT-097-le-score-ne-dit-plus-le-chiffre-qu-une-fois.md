# TASK ENT-097 — entreprise : le score ne dit plus le chiffre qu'une fois

- **Créée le** : 2026-08-12.
- **Session** : `5382ef9c-ec8e-4c98-ba47-e24d51f91516`.
- **Statut** : ✅ fait le 2026-08-12 — le bloc est passé de 72 à 56 pixels, la phrase du compte ne
  se lit plus mais s'entend toujours, et le palier nomme à quoi le profil est comparé.
- **Type** : habillage et libellés. Aucun calcul, aucun seuil, aucune donnée ne change.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle retire un bloc de texte de
  l'écran et réécrit trois libellés. La preuve est une **capture des trois paliers dans les deux
  thèmes**, plus **l'écoute de ce que le bloc dicte** à un lecteur d'écran, qui doit rester
  identique à aujourd'hui.
- **Réf** : conversation de cadrage du 2026-08-12 · `ENT-088` — qui a posé ce bloc en tête de colonne
  · `ENT-096` — les cartouches de la même colonne, **indépendante**.

## Contexte

Un recruteur, dans l'espace entreprise, sur la **Visionneuse** d'une recherche : la colonne de droite
s'ouvre par un bloc de score, sur trois lignes.

**Les trois disent le même nombre.** L'anneau le dessine, « 3/8 » l'écrit à l'intérieur, la phrase
« 3 critères sur 8 retrouvés dans le profil » le réécrit en toutes lettres, et « Correspondance
moyenne » n'est que ce même 3/8 rangé dans un palier — le seuil médian est à un tiers. **Il n'y a
aucune information nouvelle dans ce bloc en dehors du compte.**

Ce que la phrase apporte réellement, c'est **la légende de l'anneau** : elle explique ce que 3 et 8
comptent. Une légende se lit une fois, pas à chaque profil d'une pile qui en compte des centaines.

Et le mot du palier, une fois seul, doit porter le sens à lui tout seul — or « Correspondance
moyenne » est un nom de classement, pas une réponse, et son premier mot ne fait que rappeler l'objet
de la colonne.

## Périmètre

- **La phrase quitte l'écran** et bascule dans le texte réservé aux lecteurs d'écran, sans changer
  ni de contenu ni de place dans l'ordre de lecture.
- **Le mot du palier reste**, à droite de l'anneau, sur la même ligne.
- **Les trois libellés se réécrivent** : **Loin de vos critères** · **Proche de vos critères** ·
  **Aligné sur vos critères**.

## Hors périmètre

- **Les seuils de palier ne bougent pas** — ils restent à un tiers et deux tiers.
- **L'anneau ne change pas** : même géométrie, même chiffre écrit au centre, même couleur.
- **Les cartouches de critères** juste en dessous : `ENT-096`.
- **L'encadré de fin de liste** et la zone d'états en pied de colonne.

## Décisions de cadrage

### La phrase part de l'écran, pas du produit

**Arbitré.** Elle bascule dans le texte réservé aux lecteurs d'écran — qui sont **déjà les seuls à en
dépendre** : l'anneau et le chiffre leur sont muets, marqués comme décoratifs. Personne ne perd
d'information, et le bloc passe de trois lignes de texte à une.

**Écarté** : la garder en la reformulant pour qu'elle cesse de compter — par exemple « d'après le CV
public du candidat ». L'idée tient, mais elle ferait doublon avec l'encadré déjà présent en pied de
liste, et il faudrait alors choisir lequel des deux vit.

**Écarté aussi** : supprimer le palier et garder le compte écrit. On perdrait le mot qui se lit sans
compter, et c'est lui qui permet de balayer une pile sans s'arrêter sur chaque profil.

### Le palier nomme ce à quoi le profil est comparé

**Arbitré.** « Loin de vos critères », « Proche de vos critères », « Aligné sur vos critères ».

Ce que ça gagne : le mot dit **à quoi** le profil est comparé — au brief du recruteur, pas à une
norme abstraite. « Correspondance », lui, ne faisait que rappeler l'objet de la colonne, pour la
moitié de la longueur.

**Écarté** : la forme verbale (« Correspond en partie »), plus courte et plus fluide, mais qui ne
nomme pas ce à quoi on compare. Et l'état du profil (« Profil partiel »), le plus court des trois,
mais « partiel » se comprend aussi comme « fiche incomplète ».

⚠️ **Ce sont les libellés les plus longs des trois pistes.** Le mot du palier est déjà écrit au
corps du texte courant et non à celui d'un titre, précisément pour ne pas se couper dans les
250 px que l'anneau lui laisse. **Vérifié au rendu** : « Aligné sur vos critères » et « Proche de vos
critères » tiennent sur une ligne sans se couper, et le troisième est plus court qu'eux.

## Règles et cas limites

- **Aucun critère, ou aucun profil à confronter** : le bloc entier est absent — inchangé, « 0 sur 6 »
  se lirait comme un mauvais score là où il n'y a qu'une absence.
- **Score à zéro sur des critères existants** : palier bas, « Loin de vos critères ».
- **Recherche d'un collègue** (`editable` faux) : le bloc s'affiche comme d'habitude.
- **Repli sous 1280 px** : même composant en pied de fiche, mêmes règles.

## Contraintes transverses

- **Accessibilité** : ce qui est dicté ne change pas d'un mot. La phrase reste dans l'ordre de
  lecture actuel, simplement invisible à l'œil. L'anneau et le chiffre restent décoratifs — sans
  quoi le compte serait énoncé trois fois de suite pour une seule information.
- **i18n** : trois valeurs réécrites sous `search.viewer.panel.score.tier`. **Les clés ne changent
  pas de nom** — `low`, `medium`, `high` restent justes. Les autres langues suivent, et le test de
  parité des locales les couvre en présence.
- **SSR** : rien à mesurer côté client.
- **Mobile-first** : le bloc rétrécit avec la colonne ; le mot du palier se coupe avant l'anneau.

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/` ne devient fausse.**
- **`webapp/spaces/entreprise/app/components/search/Score.vue`** — le commentaire d'en-tête défend
  « trois fois la même chose, à trois distances de lecture » et explique pourquoi la phrase porte
  tout le sens pour les lecteurs d'écran. Le premier devient faux, le second reste vrai et gagne en
  importance.

## Dépendances

- **Amont** : aucune.
- **Aval** : aucune. Indépendante de `ENT-096`, qui touche un autre fichier de la même colonne.

## Risques et reports assumés

- **« Proche de vos critères » se déclenche dès 3 critères sur 8**, le palier médian commençant à un
  tiers. **Le mot est plus généreux que le compte.** Assumé : l'anneau est juste à côté et dit le
  chiffre exact. Deux façons de le lever un jour — adoucir le mot du milieu, ou remonter le seuil —,
  aucune n'est ouverte ici.
- **Les nouveaux libellés sont plus longs que les anciens.** Si « Aligné sur vos critères » se coupe
  au rendu, c'est le libellé qui cède, pas la taille du texte : elle a déjà été baissée d'un cran
  pour cette raison.

## Vérification

Deux navigateurs réels : l'« avant » dans l'arbre principal qui tourne sur `develop`, l'« après »
dans le worktree de la lane.

### La hauteur, et ce qui reste à l'écran

```
                 AVANT (develop)                      APRÈS
hauteur du bloc  72 px                                56 px
visible          « Correspondance forte »             « Aligné sur vos critères »
                 « 8 critères sur 9 retrouvés
                    dans le profil. »
à l'écoute       —                                    « 8 critères sur 10 retrouvés
                                                         dans le profil. »
```

Le bloc est désormais **gouverné par l'anneau** (56 px), qui ne bouge pas : la hauteur ne dépend plus
de la longueur du texte.

### Les paliers, observés à l'écran

```
4 critères sur 5  →  « Aligné sur vos critères »
3 critères sur 5  →  « Proche de vos critères »
```

⚠️ **Le palier bas n'a pas été observé, et il ne peut pas l'être sur données réelles** : le moteur ne
ramène que des profils qui satisfont au moins un critère — le plus souvent le métier —, si bien qu'un
score sous le tiers n'apparaît quasiment jamais dans une pile. La chaîne est prouvée par ses deux
bouts :

```
$ bunx vitest run base/i18n/locales/parity.vitest.ts services/search/criteria.vitest.ts
  → 71 tests passés

  scoreOf : « bascule de faible à moyenne au tiers » — 0/6 et 1/6 rendent bien `low`
  parité  : la clé `…score.tier.low` existe dans les trois langues
```

Le gabarit compose la clé de la même façon pour les trois paliers, et deux des trois ont été vus
rendus. **« Loin de vos critères » est aussi le plus court des trois** : il ne peut pas se couper là
où les deux autres tiennent.

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/Score.vue` | (M) la phrase du compte passe à l'écoute seule ; les commentaires disent la nouvelle règle |
| `base/i18n/locales/{fr,en,ar}.yaml` | (M) les trois paliers nomment à quoi le profil est comparé |

## Définition de terminé

- [x] Le bloc de score n'affiche plus que l'anneau et le mot du palier, sur une ligne.
- [x] La phrase « … critères sur … retrouvés dans le profil » n'est plus visible mais reste dictée,
      au même endroit de l'ordre de lecture.
- [x] Les trois paliers disent « Loin de vos critères », « Proche de vos critères », « Aligné sur vos
      critères », dans toutes les langues du dépôt.
- [x] Aucun des trois libellés ne se coupe dans la largeur de colonne.
- [x] Le commentaire d'en-tête de `Score.vue` dit la nouvelle règle.
- [x] Capture des paliers dans les deux thèmes — **deux des trois observés**, le bas étant hors
      d'atteinte sur données réelles (cf. *Vérification*).
