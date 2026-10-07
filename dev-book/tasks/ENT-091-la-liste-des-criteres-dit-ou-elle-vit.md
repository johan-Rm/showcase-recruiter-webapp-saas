# TASK ENT-091 — entreprise : la liste des critères dit où elle vit

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — l'en-tête de la liste décrit ses deux emplacements réels et
  ne contredit plus celui du verdict.
- **Type** : correctif de commentaire. Aucune ligne de code ne change, rien ne bouge à l'écran.
- **Tests** : `sans objet` — la carte ne touche qu'un commentaire. La preuve est la lecture du
  gabarit qui le contredit.
- **Réf** : `ENT-076` — qui a sorti le verdict de la fiche et rendu la phrase fausse · la règle
  « Ce qu'un commentaire ne raconte pas » (`base/app/README.md`) · `WEB-018` — le verrou qui la tient.

## Contexte

L'en-tête de `MatchList.vue` affirme :

> « Elle vit **DANS la fiche du profil**, et son verdict ne vaut que de celui-là. »

C'est faux depuis `ENT-076`. Au-dessus de 1280 px, la liste vit dans une colonne **détachée**, à côté
de la pile, et juge le profil qu'on **regarde** — pas celui qui la porte. Sous ce seuil seulement,
elle retombe en pied de fiche.

Le fichier voisin, `Verdict.vue`, dit la chose juste dans son propre en-tête : « posé dans une fiche
il juge celle qui le porte, posé à côté de la pile il juge celle qu'on regarde ». Les deux
commentaires se contredisent, à un dossier d'écart.

C'est exactement le risque que nomme la règle du socle : le fichier est le seul des trois supports —
avec `git log` et `dev-book/` — dont **rien ne vérifie qu'il dit encore vrai**. Quatre jours ont
suffi.

## Périmètre

- **La phrase d'en-tête de `MatchList.vue`** dit les deux emplacements, et ce que ça change pour son
  verdict.

## Hors périmètre

- **Le reste des commentaires de ce fichier**, qui restent vrais.
- **Le volume de commentaire de l'écran**, sujet à part entière.
- **La portée du verrou de CI**, qui ne couvre pas les `.ts` — c'est la lane socle.

## Décisions de cadrage

### La phrase se corrige, elle ne se supprime pas

Ce qu'elle porte reste vrai et utile : cette liste est **le seul endroit de l'écran qui dise une
absence**, là où le reste de la fiche ne peut surligner que ce que le profil porte. C'est le motif de
son existence. Seul l'emplacement qu'elle annonce a changé.

## Règles et cas limites

- **Le fichier ne connaît pas son emplacement**, et c'est voulu : c'est `Verdict.vue` qui le sait, et
  la page qui le décide. Le commentaire décrit donc les deux cas sans que le code n'en teste aucun.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/`, aucun README.

## Définition de terminé

- [x] L'en-tête décrit les deux emplacements réels de la liste.
- [x] Il ne contredit plus celui de `Verdict.vue`.

## Vérification

### La phrase, avant et après

```
❌  Elle vit DANS la fiche du profil, et son verdict ne vaut que de celui-là.

✅  ELLE NE CONNAÎT PAS SA PLACE, et c'est le verdict qui la lui donne : en colonne
    détachée elle juge le profil qu'on REGARDE, en pied de fiche celui qui la PORTE.
```

### Ce que le code fait des deux emplacements

Ils sont exclusifs, et chacun sert un profil différent :

```
la page   <aside class="hidden … xl:block">   <SearchVerdict :candidate="current"   />
la fiche  <SearchVerdict class="… xl:hidden"  :candidate="candidate" />
```

Au-dessus de 1280 px, le verdict reçoit `current` — le profil qu'on **regarde**. En dessous, il
reçoit `candidate` — celui qui le **porte**. C'est exactement ce que la nouvelle phrase annonce, et
l'inverse de ce qu'affirmait l'ancienne.

### La règle qui gouverne ce changement

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/MatchList.vue` | (M) l'en-tête dit les deux emplacements |

## Reste à faire (hors périmètre)

- **Rien pour cette carte.** Le volume de commentaire de cet écran — 40 % de ses lignes — reste un
  sujet ouvert, sans rapport avec la justesse de cette phrase.
