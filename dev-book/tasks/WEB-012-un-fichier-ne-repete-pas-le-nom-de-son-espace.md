# TASK WEB-012 — un fichier ne répète pas le nom de son espace

- **Créée le** : 2026-08-04.
- **Session** : `a57b4ab0-a355-4f38-a6da-42858884e758`.
- **Statut** : ✅ fait le 2026-08-04 — les deux fiches disent la règle, une treizième gate la fait
  mordre sur tout `spaces/*/app/` hors pages et layouts, et les quatorze fichiers de l'admin sont
  inscrits en dette que `ADM-016` retirera. Deux points ont été tranchés à l'implémentation contre ce
  que cette carte prévoyait, et sont réécrits dans les *Décisions de cadrage* : l'exclusion des
  couches nommées du dehors, et la frontière de mot qui rend l'exception `candidat` inutile.
- **Type** : Socle webapp — convention de nommage et gate CI.
- **Tests** : le checker est du code exécutable et se teste dans
  [webapp/scripts/ci/checkers.vitest.ts](../../webapp/scripts/ci/checkers.vitest.ts), à la suite des
  existants — **neuf cas** en deux blocs, « ce qu'elle refuse » et « ce qu'elle autorise » comme ses
  voisines. Refusés : le composant de coque préfixé, le composable dont on juge ce qui suit `use`, le
  store en camelCase, le nom qui **est** celui de l'espace, et la ligne rapportée (la première — le
  fautif est le nom du fichier). Autorisés : un nom conforme, un mot du métier qui commence comme
  l'espace (`CandidateTable.vue` dans `candidat`), une page nommée par sa route, un layout nommé par
  son identifiant, et le socle qui n'appartient à aucun espace. Les deux phrases de README ne sont
  pas exécutables et se prouvent par la gate elle-même.
- **Réf** : **aval `ADM-016`**, qui renomme les quatorze fichiers et retire la dette que cette carte
  ouvre · **`WEB-006`**, même chorégraphie règle → gate → dette → retrait, et même raison de passer
  par un checker plutôt qu'un motif interdit ·
  [base/app/components/README.md](../../webapp/base/app/components/README.md) — « Nommage dérivé du
  chemin » · [base/app/composables/README.md](../../webapp/base/app/composables/README.md) —
  « Responsabilité unique » · [webapp/README.md](../../webapp/README.md) — « Conventions vérifiées
  en CI » · fichiers touchés : `base/app/components/README.md`, `base/app/composables/README.md`,
  `scripts/ci/checkers.ts`, `scripts/ci/checkers.vitest.ts`, `scripts/ci/rules.yaml`,
  `webapp/README.md`.

## Contexte

**Deux espaces nomment leurs fichiers à l'inverse l'un de l'autre, et aucune fiche ne dit lequel a
raison.**

| | Espace admin | Espace entreprise |
| --- | --- | --- |
| Composants de coque | `AdminTopbar.vue` → `<AdminTopbar>` | `Topbar.vue` → `<Topbar>` |
| Composables | `useAdminNav`, `useAdminVivier`… | `useSearch`, `useCockpit`… |

Les deux respectent la lettre des fiches actuelles, et c'est bien le problème : rien ne départage.
Pire, la fiche des composants penche à contresens de la décision prise. Elle dit qu'« un dossier se
crée dès qu'une feature compte au moins deux composants » — lu au pied de la lettre, ça condamne les
cinq composants de coque restés à la racine de l'admin et les huit de l'entreprise, et pousse vers un
dossier `layout/` que personne ne veut : le contexte `Layout` n'apporte rien sur une barre haute.

La fiche des composables, elle, ne dit **rien** du préfixe d'espace.

**Une phrase de README ne suffira pas, et le dépôt en a déjà la preuve.** C'est exactement le trou
qu'a comblé `WEB-006` : les deux écrans jumeaux de la recherche avaient tranché à l'opposé sur la
balise de région, chacun avec de bonnes raisons, parce que les deux README concernés se lisaient dans
les deux sens. [webapp/README.md](../../webapp/README.md) en a tiré l'exigence : « une gate qui ne
mord jamais est pire qu'inutile — elle rassure sans protéger ».

### Ce que porte le dépôt aujourd'hui

Une seule couche de l'admin dérive. Les autres appliquent **déjà** la règle qu'on écrit :

```
spaces/admin/app/stores/      users.ts · vivier.ts · jarvi.ts · candidates.ts · dashboard.ts   ✅
spaces/admin/app/utils/       navRoute.ts · vivierMetrics.ts                                   ✅
spaces/admin/app/bootstrap/   loadUsers · loadVivier · loadCandidates · loadJarvi              ✅
spaces/admin/app/components/  5 fichiers préfixés Admin*                                       ❌
spaces/admin/app/composables/ 9 fichiers préfixés useAdmin*                                    ❌
```

La règle ne crée donc rien : elle **finit** une convention que l'espace applique partout ailleurs.

## Périmètre

**Deux phrases, une règle, une dette.**

- **`base/app/components/README.md`** — la coque d'un espace (barre haute, barre latérale, tiroir
  mobile) n'est pas une feature : elle reste à la racine de `components/`, sans préfixe d'espace. La
  fiche pose déjà trois axes qui ne se recouvrent pas — la **layer** porte le domaine, le **dossier**
  porte la feature, `ui/` porte les primitives sans domaine. Un préfixe `Admin` fait dire au nom de
  fichier ce que la layer dit déjà.
- **`base/app/composables/README.md`** — le nom dit la responsabilité, jamais l'espace.
- **`scripts/ci/rules.yaml`** — une treizième règle, `app-no-space-prefix`, sur
  `spaces/*/app/**/*.{vue,ts}`, portée par un `check:` et non par un `forbid:`.
- **La dette** — les quatorze fichiers de l'admin en `except:`, étiquetés `# admin`, avec la date
  d'ouverture du bloc.
- **`webapp/README.md`** — la liste des règles actives passe à treize. Elle annonce « neuf » alors
  que `rules.yaml` en porte déjà **douze** : `WEB-010` a ajouté `layouts-single-main` et
  `pages-no-main`, `WEB-011` a ajouté `layouts-no-raw-spacing`, aucune des deux n'a touché au compte.
  La correction voyage ici — et l'énumération, qui liste les règles une à une, est remise à jour en
  entier plutôt que complétée, sans quoi elle rederivera au prochain ajout.

## Hors périmètre

- **Le renommage lui-même** — c'est `ADM-016`, dans sa lane, avec son commit.
- **Toute règle sur le découpage en dossiers.** On encadre le préfixe d'espace, pas la question de
  savoir quand une feature mérite son dossier.
- **`Gutter.vue` et `WireframeZoneName.vue`** de l'espace entreprise — le premier est une primitive
  d'espacement qui aurait sa place dans `ui/`, le second est de l'échafaudage dont la question est la
  survie. Deux sujets distincts, deux lanes, aucun rapport avec le préfixe.

**Écarté explicitement** : aucune règle métier, aucun rôle, aucun état de donnée, aucun rendu touché
— donc ni mobile-first, ni SSR, ni accessibilité, ni SEO, ni i18n dans cette carte.

## Décisions de cadrage

**Un checker, pas un motif interdit.** La chaîne à refuser dépend du **chemin du fichier lui-même** :
dans `spaces/admin/`, c'est `Admin` ; dans `spaces/public/`, c'est `Public`. Une regex statique ne
sait pas exprimer ça. C'est la raison qui a déjà fait de `components-root-marker` un checker.

**La détection est mécanique et per-espace.** Le nom de base d'un fichier de `spaces/<espace>/app/`
ne commence ni par `<Espace>` en PascalCase, ni par `use<Espace>`. Conséquence voulue :
`AdminCandidateTable.vue` est refusé dans l'espace admin, tandis que `CandidateTable.vue` y passe —
« Candidate » n'est pas le nom de cet espace-là.

**La gate couvre tout `app/`, pas seulement les deux couches fautives.** Le coût est nul : stores,
utils, plugins, middleware et bootstrap sont déjà conformes dans les quatre espaces, donc zéro ligne
de dette supplémentaire. Étendre maintenant protège les couches où la dérive n'a pas encore eu lieu ;
le faire plus tard demanderait une seconde carte socle pour un gain identique.

**Sauf `pages/` et `layouts/` — tranché à l'implémentation, contre ce que cette carte prévoyait.**
La première exécution a refusé `spaces/entreprise/app/pages/inscription/entreprise.vue`, et elle
avait raison de le montrer : le nom d'une page **est** son adresse (`/inscription/entreprise`), le
nom d'un layout est l'identifiant que `definePageMeta({ layout: … })` va chercher. Ni l'un ni l'autre
n'est un nom d'auto-import, et aucun des deux ne peut être choisi librement. `eslint.config.mjs`
faisait déjà cette distinction en y désactivant la règle de nommage multi-mots. L'exclusion vit dans
le checker, pas en `except:` : c'est une propriété de la règle, pas une dette.

**Le préfixe doit être un mot entier — trouvé en écrivant le checker.** `CandidateCard.vue` dans
l'espace `candidat` passe, parce qu'après « Candidat » vient un `e` minuscule. Cette carte prévoyait
de traiter ce cas par une ligne d'exception le jour venu ; c'était plus fragile, et le risque
qu'elle décrivait au chapitre suivant n'existe plus.

**La dette s'ouvre ici et se retire dans la carte suivante.** `ADM-016` est écrite juste après
celle-ci et retire les quatorze lignes en même temps que les noms. La dette n'est donc pas un permis
d'ignorer : elle est comptée, datée, nommée, et sa carte de retrait existe avant qu'elle ne soit
inscrite.

**Ce que la carte ne fait pas dire à la règle.** Elle n'interdit pas qu'un composant s'appelle
`Topbar` tout court. Elle interdit qu'il répète le nom de son espace. Le débat « nom court contre nom
qualifié par un dossier » a été tranché en session : la coque reste à la racine.

## Règles et cas limites

- **Le mot de l'espace est aussi un mot du métier.** Dans l'espace `candidat`, « Candidat » est le
  nom de l'objet manipulé. La frontière de mot règle le cas sans exception : `CandidateCard.vue`
  passe, `CandidatProfile.vue` est refusé. Un test l'exige, pour que la règle ne se remette pas à
  condamner le vocabulaire de cet espace le jour où il aura des composants.
- **Le nom d'une page ou d'un layout ne se choisit pas.** Il est imposé du dehors — une adresse, un
  identifiant de gabarit. Ces deux couches sortent du périmètre du checker.
- **`base/` est hors périmètre.** Le socle n'appartient à aucun espace : la question du préfixe
  d'espace ne s'y pose pas. Un test le verrouille.
- **Les fichiers générés et `node_modules`** ne sont pas dans le `scope:` — même traitement que les
  onze règles existantes.
- **Casse du nom d'espace** : les dossiers sont en minuscules (`admin`, `candidat`, `entreprise`,
  `public`), le nom de fichier en PascalCase. La comparaison capitalise la première lettre du
  segment de chemin, elle ne devine rien.

## Documentation impactée

- [webapp/base/app/components/README.md](../../webapp/base/app/components/README.md) — section
  « Nommage dérivé du chemin », la phrase sur la coque.
- [webapp/base/app/composables/README.md](../../webapp/base/app/composables/README.md) — section
  « Responsabilité unique », la phrase sur l'espace.
- [webapp/README.md](../../webapp/README.md) — section « Conventions vérifiées en CI », le compte des
  règles actives et l'énumération.
- [composables-standard.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/composables-standard.md) —
  sa section « Conventions d'écriture » **énumère** celles du README co-localisé : une convention
  ajoutée là et pas ici rendrait la liste incomplète pour qui consulte l'architecture. Une puce, qui
  pointe la règle sans la recopier. Les composants n'ont pas d'équivalent — il n'existe pas de
  `components-standard.md`.

## Définition de terminé

- [x] La fiche des composants dit que la coque d'un espace reste à la racine, sans préfixe d'espace,
      et **pourquoi** — la layer porte déjà le domaine.
- [x] La fiche des composables dit que le nom porte la responsabilité, jamais l'espace.
- [x] `app-no-space-prefix` existe dans `rules.yaml`, cite la section de README qui la fonde, et
      porte les quatorze fichiers de l'admin en `except:` étiquetés `# admin` avec la date
      d'ouverture.
- [x] Le checker vit dans `scripts/ci/checkers.ts` et est enregistré dans `CHECKERS`.
- [x] Les neuf cas de test sont écrits dans `checkers.vitest.ts` et **échouent** si le checker cesse
      de mordre — vérifié en cassant volontairement le checker une fois.
- [x] Une violation volontaire (un fichier renommé `AdminFoo.vue` le temps d'un essai) fait rougir
      `bun run quality-conventions-check`, et le fichier est remis en état.
- [x] `bun run quality-conventions-check` et `bun run quality-test` sont verts sur la lane.
- [x] `webapp/README.md` annonce treize règles et cite la nouvelle.
- [x] `ADM-016` est nommée en aval dans la carte, et la dette porte son numéro comme carte de
      retrait.

## Vérification

**La gate sur le code réel** — `bun run quality-conventions-check`, worktree socle :

```
✔ conventions — 13 règles, aucune violation
```

**La violation volontaire**, exigée par [webapp/README.md](../../webapp/README.md). Quatre appâts
écrits puis retirés, deux fautifs et deux légitimes :

```
✖ app-no-space-prefix — 2 violation(s)
    spaces/entreprise/app/components/EntrepriseFoo.vue:1 → EntrepriseFoo — « Entreprise » est déjà porté par la layer
    spaces/admin/app/composables/useAdminFoo.ts:1        → useAdminFoo — « Admin » est déjà porté par la layer
```

`spaces/candidat/app/components/CandidateCard.vue` et `spaces/entreprise/app/pages/entreprise.vue`
ont été épargnés — c'est le comportement recherché, pas un silence de la règle.

**Les tests mordent.** Suite complète verte, puis le checker neutralisé pour vérifier que les tests
le voient :

```
checker intact  → Test Files 1 passed  ·  Tests 51 passed (51)
checker cassé   → Test Files 1 failed  ·  Tests 5 failed | 46 passed (51)
suite complète  → Test Files 15 passed ·  Tests 180 passed (180)   [886 ms]
```

Les cinq tombés sont les cinq du bloc « ce qu'elle refuse » : une règle qui cesse de mordre ne passe
plus inaperçue.

**Lint et format** sur les fichiers touchés : `eslint` sans sortie, `prettier --check` sans warning.
La carte elle-même n'est pas concernée — `prettier --check .` s'exécute depuis `webapp/`, donc
`dev-book/` est hors de sa portée, comme pour toutes les cartes voisines.

## Livré

| Fichier | Ce qu'il change |
| --- | --- |
| `webapp/base/app/components/README.md` **(M)** | La section « Un fichier ne répète pas le nom de son espace » : la coque n'est pas une feature, elle reste à la racine, et le nom ne reprend pas celui de l'espace. |
| `webapp/base/app/composables/README.md` **(M)** | Le nom dit la responsabilité, jamais l'espace — et c'est l'**export** que Nuxt lit, pas le fichier. |
| `webapp/scripts/ci/checkers.ts` **(M)** | `appNoSpacePrefix` et son enregistrement : préfixe per-espace, frontière de mot, `pages/` et `layouts/` écartés. |
| `webapp/scripts/ci/checkers.vitest.ts` **(M)** | Neuf cas, deux blocs — cinq refus, quatre autorisations. |
| `webapp/scripts/ci/rules.yaml` **(M)** | La treizième règle et ses quatorze lignes de dette datées, étiquetées `# admin`. |
| `webapp/README.md` **(M)** | Treize règles annoncées au lieu de neuf, énumération reprise dans l'ordre du fichier. |
| `docs/…/composables-standard.md` **(M)** | Une puce dans « Conventions d'écriture », dont la liste reprend celles du README co-localisé. |
| `dev-book/tasks/WEB-012-*.md` **(+)** | Cette carte. |

## Reste à faire (hors périmètre)

- **`ADM-016`** retire les quatorze lignes de dette en même temps que les noms. Elle est **bloquée**
  tant que cette carte n'est pas mergée : les lignes n'existent pas sur `espace/admin`, et
  `rules.yaml` est partagé — le toucher depuis deux branches produirait un conflit.
- **`Gutter.vue`** et **`WireframeZoneName.vue`** de l'espace entreprise, nommés au passage pendant
  le cadrage : le premier est une primitive d'espacement qui aurait sa place dans `ui/`, le second
  n'est employé que par les pages d'échafaudage. Ni l'un ni l'autre n'est un problème de préfixe.
