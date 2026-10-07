# TASK ENT-119 — entreprise : la lane repasse ses gates avant la PR

- **Créée le** : 2026-08-14.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-14 — la passe qualité complète est verte sur les quatre espaces,
  conventions comprises, et les 474 tests du front passent.
- **Type** : chore — mise en conformité, aucun comportement changé.
- **Tests** : `sans objet — aucun code exécutable n'est produit`. Sept fichiers ne changent que de
  mise en forme ; les trois autres corrigent un type, un marqueur de composant et un commentaire,
  sans toucher à ce que l'écran fait. La preuve est la passe qualité elle-même, lue à la fin.
- **Réf** : `ENT-112` — dont proviennent la plupart des écarts · `ENT-116` · le précédent
  `fix(api): la lane repasse ses gates avant la PR`, qui a ouvert cette forme de tâche

> ⚠️ **Cette carte touche des fichiers écrits par une autre session** — `ENT-112`, `ENT-115` et
> `ENT-116` portent la session `1b9df7cf`. Rien de ce qui suit ne modifie ce que leur code **fait** :
> il s'agit de mise en forme et de trois règles du dépôt appliquées. Le geste a été décidé plutôt que
> pris : sans lui, la branche entière reste rouge et personne ne peut ouvrir de PR.

## Contexte

La branche porte sept tâches. Chacune a lancé ses **tests impactés**, comme le veut le cycle — mais
les gates complètes ne tournent qu'une fois, au point de contrôle. Elles y ont trouvé **quatre
familles d'écarts**, dont aucune n'était visible plus tôt :

- **sept fichiers hors format** — sur toute la branche, tous propriétaires confondus ;
- **une erreur de type** dans `profile.ts` : `document()` élargissait son type de retour à
  `TValue[] | TValue` même quand on lui passait une valeur simple, si bien que le compte rendu
  d'entretien — un texte unique — ne rentrait plus dans le champ `LockedValue<string>` qui l'attend ;
- **deux maquettes** qui appellent `open()` sans argument, depuis que ce composable en réclame un ;
- **deux règles de convention** : un marqueur de composant absent, et un commentaire qui raconte une
  chronologie.

## Périmètre

- Le format, sur les sept fichiers que `prettier` refusait.
- `document()` déduit son type de ce qu'on lui donne : `LockedValue<TValue>` au lieu de
  `LockedValue<TValue[] | TValue>`. Un tableau reste un tableau, une valeur simple reste simple.
- Les deux maquettes gardent leur bouton, sans l'appel.
- `<UDrawer>` du panneau de profil porte son marqueur `profile-panel`.
- Le commentaire de `reveal.ts` dit sa contrainte au présent.

## Hors périmètre

- **Tout changement de comportement.** Aucune ligne ne modifie ce qu'un écran affiche ou fait.
- **Les 23 avertissements ESLint** (`vue/require-default-prop` et voisins) : ils précèdent cette
  branche, la gate les tolère, et les traiter serait un chantier à soi seul.
- **Le modèle des CV.** `resumes` reste un tableau au contrat bien qu'il n'y ait jamais qu'un CV ; le
  corriger touche l'API et le composant qui l'affiche.

## Décisions de cadrage

**Une seule tâche pour les quatre familles, et non quatre.** Elles ont la même cause — des gates qui
ne tournent qu'au point de contrôle — et le même effet : la branche ne part pas. Les séparer aurait
produit quatre commits qui ne veulent rien dire isolément.

**Le générique est corrigé à la source, pas contourné à l'appel.** Un `as` sur la ligne fautive
aurait fait taire le compilateur en laissant la signature mentir au suivant. La forme corrigée dit ce
qu'elle fait : ce qui entre ressort, tableau ou pas.

**Les maquettes gardent leur bouton.** Il montre la place qu'il occupe ; c'est leur seul rôle. Son
appel ne menait nulle part de toute façon — le tiroir réclame une fiche de profil qu'un squelette
statique n'a pas —, et un commentaire le dit désormais sur place.

## Règles et cas limites

- **Le comportement du panneau de profil est inchangé** : le marqueur est une classe statique, sans
  effet visuel.
- **Le compte rendu et le CV se servent toujours pareil** : seul le type déduit change, pas le code
  exécuté.

## Documentation impactée

Aucun déclencheur : ni surface d'API, ni entité, ni contrat, ni convention de dossier modifiée — ces
règles sont **appliquées**, pas changées.

## Vérification

`cd webapp && bun run quality-check`, sur l'hôte — la seule exécution qui reproduise la CI :

```
$ eslint .            ✖ 23 problems (0 errors, 23 warnings)   ← antérieurs à la branche
$ prettier --check .  All matched files use Prettier code style!
$ type-check          espaces public · candidat · entreprise · admin — aucune erreur
$ verify-conventions  ✔ 19 règles, aucune violation
$ vitest run          Test Files 28 passed · Tests 474 passed
```

## Livré

- `webapp/services/search/profile.ts` **(M)** — `document()` déduit son type de son entrée.
- `webapp/services/search/reveal.ts` **(M)** — le commentaire dit sa contrainte au présent.
- `webapp/spaces/entreprise/app/components/ProfilePanel.vue` **(M)** — le marqueur de composant.
- `webapp/spaces/entreprise/app/pages/template-recherches-uuid.vue` **(M)** ·
  `template-recherches-uuid-table.vue` **(M)** — le bouton reste, l'appel part.
- `webapp/base/i18n/locales/fr.yaml` **(M)** · `services/search/profile.vitest.ts` **(M)** ·
  `refine.vitest.ts` **(M)** · `reveal.vitest.ts` **(M)** ·
  `spaces/entreprise/app/components/search/RefinePanel.vue` **(M)** — format seul.

## Reste à faire (hors périmètre)

- **Les 23 avertissements ESLint** de la webapp, antérieurs à cette branche.
- **`resumes` au pluriel** alors qu'il n'y a jamais qu'un CV — décision produit confirmée, correction
  non faite : elle touche le contrat et l'affichage.

## Définition de terminé

- [x] `prettier --check` ne signale plus aucun fichier.
- [x] Le type-check passe sur les quatre espaces.
- [x] Les 19 règles de convention passent sans violation.
- [x] Les 474 tests du front passent.
- [x] Aucun comportement d'écran n'a changé.
- [x] La carte dit qu'elle touche des fichiers d'une autre session, et pourquoi.
