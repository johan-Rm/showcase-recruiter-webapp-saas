# TASK ENT-118 — entreprise : le panneau d'affinage survit à une carte incomplète

- **Créée le** : 2026-08-14.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-14 — les deux champs les plus récents de la carte se lisent avec
  leur absence, le panneau continue de rendre ses quatre volets même sur un profil qui ne les porte
  pas, et les fixtures qui construisent une carte en littéral les fournissent.
- **Type** : fix — régression introduite par `ENT-114`, trouvée à l'usage.
- **Tests** : trois cas dans `services/search/refine.vitest.ts`, sur une carte à laquelle les deux
  champs manquent — lister les options, compter, filtrer. Le premier reproduit le défaut à
  l'identique : il jetait avant le correctif.
- **Réf** : **`ENT-114`**, qui a introduit la lecture non gardée · `ENT-113` — le volet concerné ·
  `API-044` — qui ajoute les deux champs côté serveur ·
  [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md) §
  *L'exception : la carte de recherche*

> ⚠️ **Carte écrite après le code, et il faut le savoir en la lisant.** Le correctif a été produit
> pendant un signalement à l'écran, et `ENT-114` ne pouvait plus être amendée — deux commits d'une
> autre session s'étaient empilés par-dessus, et le rebase interactif n'existe pas dans cet
> environnement. Cette tâche n'a donc pas eu de cadrage : elle répare, elle ne conçoit rien.

## Contexte

Signalé à l'écran : **les quatre volets du panneau d'affinage s'ouvrent sur du vide.** Les titres
répondent, le chevron bascule, et rien n'apparaît — pas même les pastilles de « Présence au Maroc »,
qui n'ont pourtant aucun rapport avec ce qui a changé.

La cause est dans `refine.ts` : `refinableLanguages` et `refinableSectors` lisent `card.languages` et
`card.experienceSectors` **sans garde**. Le type les donne pour obligatoires — mais c'est un miroir
écrit à la main que rien ne confronte au serveur, et `data-contracts.md` le dit déjà : « toute
évolution de la carte doit y être reportée, et **rien ne le signalera** ».

Une carte peut donc arriver sans ces champs : servie par une API qui n'a pas encore le déploiement,
ou lue dans un store rempli avant. La liste des options jette alors, le `computed` échoue, et **le
composant entier cesse de rendre** — d'où les trois autres volets vides, qui sont les victimes
collatérales du défaut.

**Le même oubli a une seconde manifestation, muette celle-là** : deux fixtures de test construisent
une carte en littéral, et deux champs devenus obligatoires les ont rendues invalides. Rien ne l'a dit
au moment du commit — les tests impactés passaient — et le type-check ne l'a montré qu'au point de
contrôle. Le fichier de type prévenait pourtant des deux dans la même phrase : « il faut suivre les
consommateurs… ajouter un champ obligatoire casse tout ce qui construit une carte en littéral, les
fixtures de test en premier ». Une seule cause, deux symptômes, une seule réparation.

## Périmètre

- `refinableLanguages` et `refinableSectors` lisent les deux champs **avec leur absence**.
- `matchesRefine` fait de même sur les deux facettes correspondantes.
- Trois tests couvrent une carte à laquelle les deux champs manquent.
- **Les deux fixtures** de `normalize.vitest.ts` et `views.vitest.ts` fournissent les deux champs.

## Hors périmètre

- **Rendre les champs optionnels dans le type.** Ils sont obligatoires au contrat, et c'est juste :
  l'API les sert toujours. La garde protège d'un décalage de déploiement, pas d'un contrat flou.
- **Les autres champs de la carte.** `matches` était déjà lu avec `?.`, les autres sont là depuis
  assez longtemps pour qu'aucun client en vol ne les ignore. Généraliser la garde reviendrait à
  traiter tout le contrat comme incertain.
- **La scission du volet « Métier et lieu »** en deux, décidée dans la foulée du signalement : c'est
  une conception, elle aura sa carte.

## Décisions de cadrage

**La garde est assumée et datée.** Le README du socle demande de retirer les vérifications
défensives « une fois le contrat de type stabilisé ». Il ne l'est pas ici, et pour une raison écrite
noir sur blanc dans `data-contracts.md` : la carte de recherche est **le seul DTO du dépôt** absent
de l'OpenAPI, donc le seul dont l'écart front/serveur n'est vu ni par la CI, ni par un test. Le
commentaire dit quand la garde pourra sauter : le jour où ce contrat sera vérifié des deux côtés.

**Deux fonctions privées plutôt que des `??` éparpillés.** `spokenLanguages(card)` et
`sectorsOf(card)` portent la lecture une fois ; les trois endroits qui en ont besoin passent par
elles. Un `?? []` répété à chaque usage se serait désaccordé au premier ajout.

## Règles et cas limites

- **Une carte sans les deux champs reste retenue par les facettes qui, elles, ont leur donnée** —
  un profil sans langue connue passe toujours le filtre « pays ».
- **Elle est écartée par les deux facettes concernées**, dès qu'on les pose : ne rien savoir n'est
  pas satisfaire.
- **Les listes d'options l'ignorent** sans que le reste du panneau en souffre.

## Documentation impactée

Aucun déclencheur : ni surface d'API, ni entité, ni contrat, ni convention de dossier. Le
comportement corrigé est interne au calcul des options, et la règle qu'il applique est déjà écrite
dans `data-contracts.md`.

## Vérification

Le défaut reproduit avant le correctif, sur une carte privée des deux champs :

```
FAIL  services/search/refine.vitest.ts > refinableLanguages ne doit pas jeter
      expect(() => refinableLanguages([ancienne])).not.toThrow()
```

Après :

```
 Test Files  1 passed (1)
      Tests  45 passed (45)
```

Et le panneau, sur une recherche réelle de 41 profils, rend toujours ses quatre volets — quatre
titres dépliés, 39 pastilles.

## Livré

- `webapp/services/search/refine.ts` **(M)** — `spokenLanguages` et `sectorsOf`, et les trois
  endroits qui les consomment.
- `webapp/services/search/refine.vitest.ts` **(M)** — trois cas sur une carte incomplète.
- `webapp/services/search/normalize.vitest.ts` **(M)** · `views.vitest.ts` **(M)** — les fixtures
  complétées, sans quoi le type-check refuse la branche entière.

## Définition de terminé

- [x] Une carte sans `languages` ni `experienceSectors` ne fait plus jeter le calcul des options.
- [x] Les quatre volets rendent leur contenu, y compris ceux qui n'ont rien à voir avec ces champs.
- [x] Les deux facettes concernées écartent ce profil quand elles sont posées, sans le faire
      disparaître des autres.
- [x] Trois tests couvrent le cas, dont un qui reproduisait le défaut.
- [x] La garde porte, en commentaire, la condition de son retrait.
- [x] Les fixtures qui construisent une carte en littéral fournissent les deux champs, et le
      type-check passe sur les quatre espaces.
