# TASK ENT-114 — entreprise : l'écran dit ce qu'il compte, et s'affine sur la langue

- **Créée le** : 2026-08-13.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-13 — la phrase de recherche dit « dans ce métier » dès qu'un poste
  est posé, et le volet « Métier et lieu » porte la langue parlée et le secteur d'expérience, les
  deux premières facettes que le vivier remplit vraiment.
- **Type** : feat — deux affinages de plus, et un libellé qui cesse d'être ambigu.
- **Tests** : les deux nouvelles facettes rejoignent `services/search/refine.ts` et sa suite
  `vitest` — la langue **avec** et **sans** niveau exigé, le niveau absent qui ne doit jamais passer
  pour un débutant, un candidat à plusieurs secteurs qui répond à chacun, et le comptage facetté.
- **Réf** : **amont et jumelle `API-044`** — sans elle, la fiche ne porte ni langues ni secteurs, et
  le libellé annoncerait un calcul qui n'existe pas · **amont `ENT-110`** — le panneau ·
  `ENT-113` — le volet « Métier et lieu » qui les accueille ·
  [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md)

## Contexte

Deux manques, et ils viennent de la même cause : la fiche de profil ne portait pas ce qu'il fallait.

**Le champ « années d'expérience » ment sur ce qu'il compte.** `API-044` lui donne son vrai sens —
les années **dans le métier cherché** dès qu'un poste est posé —, mais l'écran continue d'afficher un
intitulé qui ne dit rien du contexte. Un critère qui change de définition sans le dire est pire que
celui qu'il remplace.

**La langue et le secteur d'expérience restent hors d'atteinte** alors qu'ils couvrent 88 % et 78 %
du vivier — de très loin les deux facettes les mieux remplies après le pays. Le panneau d'affinage
ouvert par `ENT-110` porte six facettes vides ; celles-ci le rendraient enfin utile.

## Périmètre

- **Le libellé du champ d'expérience dit ce qu'il compte**, et il le dit **différemment selon le
  contexte** : avec un poste recherché posé, « années dans ce métier » ; sans poste, « années
  d'expérience ». Le champ ne bouge pas, son intitulé suit.
- **Deux affinages de plus** dans le volet « Métier et lieu » ouvert par `ENT-113` :

| Affinage | Forme | Donnée de la carte |
| --- | --- | --- |
| Langue parlée | pastilles à compteur, les langues du jeu | `languages[]` |
| Secteur d'expérience | pastilles à compteur, les secteurs du jeu | `experienceSectors[]` |

- **Le type de la fiche côté navigateur** (`shared/types/candidate-card.ts`) reçoit les deux champs.
  ⚠️ Il est **écrit à la main** et **aucune gate ne le surveille** — le fichier le dit lui-même : la
  carte n'apparaît nulle part dans l'OpenAPI. Rien ne signalera l'oubli.

## Hors périmètre

- **Le niveau de langue comme facette à part.** La fiche le portera, mais « anglais courant » ajoute
  une seconde dimension à une pastille qui en porte déjà une. La langue seule d'abord ; le niveau se
  jugera à l'usage.
- **La ville de résidence** et **les compétences** — mêmes raisons qu'`ENT-113`.
- **L'accès sous 1024 px.**

## Décisions de cadrage

**Le libellé part avec le changement de moteur, dans la même Pull Request.** Séparer les deux
laisserait un moment où le filtre compte une chose et en annonce une autre. Comme le libellé vit dans
`webapp/` et le moteur dans `api/`, ce sont **deux cartes** — le dépôt interdit qu'un commit traverse
deux lanes — mais elles sont **jumelles** : ni l'une ni l'autre ne se merge seule.

**Un niveau de langue absent n'est pas un niveau faible.** LinkedIn ne renseigne souvent pas la
maîtrise de la langue maternelle. L'affinage porte sur la langue **parlée**, sans seuil, et le code
ne doit jamais traiter `null` comme un plancher.

**Tous les secteurs d'un candidat répondent.** `API-044` les descend tous ; l'affinage retient un
profil dès qu'**un** de ses secteurs correspond.

## Règles et cas limites

- **Le libellé contextuel suit le formulaire en direct** : effacer le poste recherché doit ramener
  l'intitulé « années d'expérience » sans rechargement.
- **ET avec les affinages existants**, comptage facetté compris.
- **Les langues et secteurs offerts sont ceux du jeu**, jamais un référentiel figé.
- **Un profil sans langue ni secteur** sort du jeu dès que l'affinage correspondant est posé — c'est
  le cas d'un profil sur huit pour la langue, un sur cinq pour le secteur.
- **Aucun texte en dur** ; les noms de langues et de secteurs viennent de la donnée, pas du code.

## Documentation impactée

- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` — la ligne « Affinages du parcours »,
  et le § *Ce que l'écran pose aujourd'hui* où le champ d'expérience change de description.
- `2.architecture/data-contracts.md` — si `API-044` n'a pas déjà décrit le miroir côté front, le
  dire ici : c'est le seul type du dépôt que rien ne vérifie.

## Ce que l'implémentation a tranché autrement

**Le libellé ne vit pas dans le composant que la carte visait.** `FiltersPrimary.vue` — le formulaire
« classique » à quatre champs — n'est instancié **nulle part** : il est orphelin, exactement comme
`FiltersSecondary` l'était avant `ENT-110`. Deux commentaires du dépôt le citent encore comme s'il
servait la console de la Visionneuse ; c'est faux.

Le formulaire réellement à l'écran est `Sentence.vue`, la recherche « qui se lit comme on la
dicterait ». Le changement y est allé, et il y prend une forme meilleure : ce n'est pas une étiquette
au-dessus d'un champ qui change, c'est **un fragment de la phrase** — « avec 5 ans *dans ce métier*,
» au lieu de « avec 5 ans *d'expérience*, ». L'étiquette destinée aux lecteurs d'écran suit la même
règle. `FiltersPrimary.vue` a été restauré intact : le modifier n'aurait rien changé à l'écran.

**Le niveau de langue n'est pas demandé**, alors que la fiche le porte désormais. Un tiers du vivier
a une langue sans niveau — la maternelle arrive nue du réseau d'origine — et exiger une maîtrise
connue écarterait précisément ceux qui la maîtrisent le mieux. L'affinage dit « parle cette langue »,
et le seuil reste un critère de recherche.

**Les libellés des options ne sont pas traduits** : les codes de langue (`fr`, `ar`) et les secteurs
(`SANTE_PHARMA`) s'affichent tels que la donnée les écrit. Les traduire supposerait un catalogue à
tenir à jour à chaque valeur nouvelle, et ce serait le premier à dériver.

## Vérification

Stack du worktree entreprise, navigateur piloté, sur la recherche « Logisticien » de **41 profils**.

**Le fragment de phrase suit le poste, sans rechargement** — le champ est vidé au clavier :

```
avec un poste posé : « Je cherche un profil de Logisticien avec 15 ans et plus dans ce métier, … »
poste effacé       : « Je cherche un profil de poste… avec 15 ans et plus d'expérience, … »
```

**Les deux affinages, avec des comptes tirés du jeu :**

```
secteurs : AERONAUTIQUE 5 · AGROALIMENTAIRE 8 · ASSURANCE 1 · AUTOMOBILE 6 · AUTRE 9 ·
           BANQUE_FINANCE 2 · BTP 2 · DISTRIBUTION 9 · EDUCATION 1 · ENERGIE 3 · INDEPENDANT 1 ·
           INDUSTRIE 9 · INFORMATIQUE 7 · PUBLIC_ADMINISTRATION 2 · SANTE_PHARMA 5 ·
           TRANSPORT_LOGISTIQUE 13
langues  : ar 8 · de 3 · en 19 · es 5 · fr 28 · it 2 · nl 1 · ru 1 · zh 1
```

Contrôle en base sur le même jeu : `fr 28`, `en 19`, `ar 8`, `es 5` · `TRANSPORT_LOGISTIQUE 13`,
`AUTRE 9`, `DISTRIBUTION 9`, `INDUSTRIE 9`. Identique.

**Un secteur posé, et tout se recalcule** — « SANTE_PHARMA » (5 profils) :

```
rang    : profil 1 sur 41  →  profil 1 sur 5
pied    : 5 profils sur 41   ·   Tout retirer
pays    : BE 1 · FR 4 · MA 0 [inerte]
langues : en 1 · fr 4 · les sept autres à 0 [inerte]
```

**L'API sert bien les deux champs** après le rebase, sur un profil du jeu :

```
languages         : [{ar, NATIVE_OR_BILINGUAL}, {en, FULL_PROFESSIONAL}, {fr, NATIVE_OR_BILINGUAL}, {nl, ELEMENTARY}]
experienceSectors : ['AUTOMOBILE', 'AUTRE', 'AERONAUTIQUE', 'INDUSTRIE', 'TRANSPORT_LOGISTIQUE']
```

**Tests** — `bun run vitest run services/search/refine.vitest.ts base/i18n/locales/parity.vitest.ts` :

```
 Test Files  2 passed (2)
      Tests  51 passed (51)
```

## Livré

- `webapp/shared/types/candidate-card.ts` **(M)** — `languages` et `experienceSectors`, avec la mise
  en garde sur le niveau `null` que rien d'autre ne porte côté front.
- `webapp/services/search/refine.ts` **(M)** — les deux facettes, et les listes offertes.
- `webapp/services/search/refine.vitest.ts` **(M)** — 9 tests de plus.
- `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` **(M)** — secteurs et langues dans
  le volet « Métier et lieu ».
- `webapp/spaces/entreprise/app/components/search/Sentence.vue` **(M)** — le fragment contextuel.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — « dans ce métier », dans les trois langues.
- `dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md` **(M)** — les deux affinages, la
  raison du niveau absent, et le double sens de la durée d'expérience.

## Reste à faire (hors périmètre)

- **Le volet est devenu long** : trois pays, seize secteurs, neuf langues et un interrupteur dans
  une colonne de 280 px. Il défile et reste lisible, mais si l'usage le rend pénible, deux remèdes
  existent — n'offrir que les valeurs les plus portées, ou scinder le volet. Ni l'un ni l'autre n'a
  été fait : le cadrage disait quatre volets, et tronquer une liste cacherait des options valides.
- **`FiltersPrimary.vue` est orphelin**, et deux commentaires du dépôt le décrivent encore comme
  servant la console de la Visionneuse. Le corriger sort de cette carte.
- **Le niveau de langue comme facette**, écarté au cadrage et confirmé par la donnée.
- **L'accès sous 1024 px.**

## Définition de terminé

- [x] Le champ d'expérience annonce « dans ce métier » quand un poste est posé, et retrouve son
      intitulé général quand il ne l'est plus — sans rechargement.
- [x] Le volet « Métier et lieu » porte la langue parlée et le secteur d'expérience, en pastilles à
      compteur tirées du jeu.
- [x] Un niveau de langue absent n'exclut jamais un profil.
- [x] Un candidat à plusieurs secteurs répond à chacun d'eux.
- [x] `shared/types/candidate-card.ts` porte les deux nouveaux champs.
- [x] La couche pure est couverte, y compris le niveau absent et les secteurs multiples.
- [x] Prouvé à l'écran sur une vraie recherche du vivier — c'est la première fois que le panneau
      d'affinage y montre des comptes non nuls.
- [x] La carte porte, en toutes lettres, son lien avec `API-044` — mergée avant elle, faute de quoi
      rien n'aurait été vérifiable ; l'écart que cela a laissé est écrit dans `API-044`.
