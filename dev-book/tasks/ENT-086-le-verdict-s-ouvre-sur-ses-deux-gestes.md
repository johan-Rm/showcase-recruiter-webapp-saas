# TASK ENT-086 — entreprise : le verdict s'ouvre sur ses deux gestes

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — le nom du profil disparaît de la colonne du verdict, et les
  deux gestes forment une barre unique collée au bord haut du bloc.
- **Type** : mise en page. Aucune donnée, aucune règle, aucun geste ne change.
- **⚠️ Cadrée après coup.** Demande faite en conversation, écran sous les yeux. Un arbitrage a été
  posé à l'humain : ce que « collés en haut sans séparation » recouvrait exactement.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle retire un affichage et déplace
  deux boutons. La preuve est une **mesure des positions rendues** aux deux paliers de largeur.
- **Réf** : `ENT-076` — qui a sorti ces deux gestes de la fiche et introduit le nom · `ENT-082` —
  qui a affiné ces mêmes boutons · `ENT-083` — les cartouches qui suivent la barre.

## Contexte

La colonne du verdict s'ouvrait sur le nom du profil, puis ses deux gestes, puis les critères.

Ce nom avait été posé par `ENT-076` avec un motif précis : sortis de la fiche, les deux boutons
n'avaient plus de sujet visible. À l'usage, le motif ne tient pas — **le nom est déjà en tête de la
fiche**, à quelques centimètres à gauche et dans un corps deux fois plus grand. Il ne donnait pas un
sujet aux boutons, il écrivait deux fois le même nom sur une même vue.

## Périmètre

- **Le nom disparaît** de la colonne du verdict.
- **Les deux gestes forment une barre unique**, jointive, collée au bord haut du bloc.
- **Le repli en pied de fiche** garde la barre dans le flux de la carte, sans débordement.

## Hors périmètre

- **Le nom en tête de fiche**, qui reste et devient la seule occurrence.
- **La couleur, la taille et les libellés des boutons**, posés par `ENT-082`.
- **Les deux archives**, qui gardent l'ancienne colonne.

## Décisions de cadrage

### Le nom part, et le drapeau `detached` change de raison d'être

Il ne dit plus « nomme le profil » mais « rejoins le bord du bloc ». Le composant sert toujours deux
emplacements ; ce qui les distingue n'est plus ce qu'il montre, c'est la façon dont il se pose.

### « Collés en haut sans séparation » : les deux à la fois

Arbitré — la phrase se lisait de deux façons, et elles ne donnent pas le même écran. Retenu : la
barre touche le bord haut **et** les deux boutons se touchent.

Deux boutons collés forment un objet ; deux boutons espacés en font trois, avec le vide entre eux.
La couture entre les deux contours suffit à les distinguer.

### Le débordement est porté par la barre, les coins par le bloc

Les marges négatives annulent la marge intérieure de la colonne, et la barre vient au ras. Les coins
arrondis, c'est le `overflow-hidden` du bloc qui les taille — **la barre n'a pas à connaître le rayon
de ce qui la contient**, sans quoi tout changement de rayon devrait être répercuté ici.

En pied de fiche, ce débordement n'aurait pas de sens : il n'y a pas de bord à rejoindre, et la barre
reste dans le flux de la carte.

## Règles et cas limites

- **Recherche d'un collègue** (`editable` faux) : pas de barre du tout, la colonne s'ouvre sur les
  critères.
- **Aucun profil** : le composant ne rend rien, comme avant.
- **Le repli sous 1280 px** : `detached` est faux, aucune marge négative, la barre respecte la marge
  de la fiche.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni convention. La carte de navigation décrit
où vivent les choses, et rien ne se déplace d'une zone à l'autre.

## Définition de terminé

- [x] Le nom n'apparaît plus dans la colonne du verdict.
- [x] La barre touche le bord haut du bloc, sur toute sa largeur.
- [x] Les deux boutons se touchent.
- [x] En pied de fiche, la barre reste dans la marge de la carte.

## Vérification

### Les positions rendues, à 1920 px

```
nom encore affiché      : false
haut du bloc            : 211 px
haut des boutons        : 211 px
écart avec le bord      : 0
écart entre les boutons : 0
largeur de la barre     : 378 px   =   largeur de la colonne : 378 px
```

### En pied de fiche, à 1150 px

```
débordement à gauche : 18 px   ← la marge intérieure de la fiche, respectée
boutons              : 2
```

### Les conventions

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/Verdict.vue` | (M) le nom retiré, la barre jointive collée au bord quand elle est détachée |

## Reste à faire (hors périmètre)

- **Les deux archives** gardent le nom et les boutons espacés — c'est leur raison d'être.
