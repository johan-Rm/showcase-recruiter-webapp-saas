# TASK ENT-053 — entreprise : le nom automatique cesse d'être une énumération

- **Créée le** : 2026-08-05.
- **Session** : `393e1873-d1a9-4645-87a7-eb7cb18cf97b`.
- **Statut** : ✅ fait le 2026-08-05 — le titre d'une recherche tient sur deux lignes quel que soit
  le nombre de critères, et les étiquettes retrouvent leur place. 5 tests neufs.
- **Type** : Frontend entreprise — une règle de composition change, aucun contrat ni stockage touché.
- **Tests** : unitaires (vitest) sur `autoName` — l'ordre fixe des quatre groupes, le mot-clé réduit
  au premier, les études qui ne tombent plus sous le plafond, et le repli quand les quatre groupes
  ne donnent rien. Le rendu se prouve en capture, sur une recherche à dix-neuf critères.
- **Réf** : **`ENT-050` — amont** (la carte qui a rendu le défaut visible) ·
  `webapp/services/search/criteria.ts` (`autoName`, `NAME_SEGMENTS`) ·
  `webapp/services/search/card.ts` (`criteriaToShow`, qui masque ce que le titre dit déjà) ·
  `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` ·
  `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue`

## Contexte

Le nom composé d'une recherche plafonne le nombre de **groupes** — trois — mais **jamais le nombre
d'éléments dans un groupe**. Or les mots-clés vivent tous dans le même groupe, joints par des
virgules. Une recherche à dix-neuf mots-clés donne donc ceci, en titre de carte :

> « Développeur logiciel, front end, front office, front desk agent, react, vue, angular,
> typescript, docker, kubernetes, python, java, sql, git, agile, scrum, node, css, html · 2 ans et
> plus »

Le commentaire du code dit pourtant l'intention : *« au-delà, le nom cesse d'être lisible d'un coup
d'œil et devient une énumération »*. Le garde-fou existe, il porte simplement sur le mauvais axe.

**Et le défaut se double.** `ENT-050` masque les étiquettes que le titre dit déjà — règle juste, qui
évite de répéter deux fois la même chose. Mais quand le titre dit **tout**, il ne reste **aucune**
étiquette : titre-paragraphe d'un côté, corps vide de l'autre. Les deux se corrigent d'un seul
geste, en bornant le titre.

Troisième effet, moins visible : les **études disparaissent** dès qu'un quatrième groupe est posé,
puisque le plafond garde les trois premiers groupes rencontrés.

## Périmètre

- **`autoName` compose une suite FIXE et ordonnée** : le métier, le **premier** mot-clé,
  l'expérience, les études — dans cet ordre, quel que soit l'ordre où les critères ont été posés et
  quel que soit le moteur qui les a produits.
- **Repli** : quand ces quatre groupes ne donnent rien, le nom se compose des groupes restants.
- **Le plafond de segments disparaît ou s'adapte** : il devient inutile puisque la liste est fixe.
- Les tests d'`autoName`, et ceux de `criteriaToShow` s'ils bougent.

## Hors périmètre

- **Le nom saisi à la main** : inchangé, et il continue de désactiver le nom automatique.
- **Le nombre d'étiquettes sous le titre** : deux puis le compteur, comme aujourd'hui. Cette tâche
  ne les touche pas — elle leur rend simplement de la place.
- **Toute reprise de données** : cf. *Décisions de cadrage*.
- **Le contrat, le stockage, l'API** : rien n'y est touché.

## Décisions de cadrage

**Quatre groupes nommés, et rien d'autre.** Le nom suit une liste fixe plutôt que l'ordre
d'insertion : deux recherches se comparent alors du regard, parce qu'elles disent les mêmes choses
au même endroit. Garder l'ordre d'insertion aurait produit des titres de grammaire variable — « react
· Maroc · Anglais » à côté de « Développeur logiciel · 2 ans et plus · Bac+2 » —, et c'est
précisément ce qui empêche de lire une colonne de cartes d'un trait.

**Un seul mot-clé dans le nom.** C'est ce qui **borne** le titre : le groupe des mots-clés est le
seul dont la taille n'a pas de limite naturelle, et c'est lui qui a produit le titre-paragraphe.
Plafonner à un élément garantit un titre court quel que soit le nombre de mots-clés posés.

**Repli sur les groupes restants quand les quatre ne donnent rien.** Une recherche dictée en langage
naturel — « des gens au Maroc qui parlent anglais » — n'a ni métier ni mot-clé : la règle stricte la
rendrait muette alors que ses critères disent quelque chose. La grammaire varie dans ce cas précis,
et c'est un prix accepté : **un titre variable vaut mieux qu'un titre absent**.

**Rien n'est stocké, donc rien n'est à reprendre.** L'API enregistre un nom vide et le drapeau
`isAutoNamed` ; le nom est composé **à l'affichage**. Le changement s'applique donc rétroactivement
à toutes les recherches existantes, sans migration ni recalcul.

**Le nom change sur quatre écrans, et c'est assumé.** `displayName` sert la carte, la barre haute de
la Visionneuse, le titre de la Table — et **le nom du fichier exporté**. Une recherche porte un seul
nom : le contraire serait le défaut. Les fichiers déjà exportés gardent évidemment le leur.

### Écart : le « +N » quitte le titre

L'ancien nom terminait par un `+N` comptant les **groupes** laissés de côté — « Développeur logiciel
· 2 ans et plus · Bac+2 **+1** ». Il disparaît du chemin nommé : ce que le nom laisse dehors est
désormais compté par les **étiquettes** de la carte, qui le disent en toutes lettres — « +15
critères » plutôt qu'un « +1 » sans sujet, dont on ne savait pas s'il comptait des critères, des
groupes ou des profils.

Il subsiste sur le chemin de **repli**, où il garde son sens d'origine faute d'étiquettes en face.

## Règles et cas limites

- **Un groupe absent se saute** sans laisser de séparateur orphelin : une recherche sans études
  donne « Développeur logiciel, react · 2 ans et plus », pas un « · » qui pend.
- **Aucun critère du tout** — le nom reste vide et l'écran affiche « Recherche sans nom », comme
  aujourd'hui.
- **Un mot-clé unique** ne déclenche aucun compteur : le nom le porte, il n'y a rien à compter.
- **Le nom saisi à la main l'emporte toujours**, et vider le champ réactive la composition.
- **`criteriaToShow` n'a pas à changer** : sa règle — masquer ce que le titre dit déjà — redevient
  utile mécaniquement, puisque le titre dit désormais moins.

## Documentation impactée

Aucune. **Aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit la règle de nommage** — vérifié. Aucun
`README.md` ne change de convention : la règle vit dans `services/search/`, dont le contrat est
« couche métier pure », inchangé.

## Définition de terminé

- [x] Une recherche à dix-neuf mots-clés porte un titre qui tient sur deux lignes au plus.
- [x] Le titre suit toujours le même ordre : métier, premier mot-clé, expérience, études.
- [x] Les études n'y disparaissent plus quand un quatrième groupe est posé.
- [x] Une recherche sans métier ni mot-clé porte quand même un nom, composé de ce qui reste.
- [x] Les étiquettes réapparaissent sous le titre, avec leur compteur.
- [x] Le titre est identique sur la carte, dans la Visionneuse, dans la Table et dans le nom du
      fichier exporté.
- [x] `autoName` est couvert par des tests unitaires, repli compris.

## Vérification

**Reproduit sur une recherche à vingt-et-un critères**, celle-là même qui avait révélé le défaut.

| | Titre | Étiquettes |
| --- | --- | --- |
| **avant** | « Développeur logiciel, front end, front office, front desk agent, react, vue, angular, typescript, docker, kubernetes, python, java, sql, git, agile, scrum, node, css, html · 2 ans et plus » | **aucune** |
| **après** | « Développeur logiciel · front end · 2 ans et plus · Bac+2 » | `front office` `front desk agent` `+15 critères` |

Trois choses s'y lisent : le titre tient sur **deux lignes**, les **études sont revenues** — elles
tombaient sous l'ancien plafond de trois segments —, et les **étiquettes réapparaissent** avec leur
compteur.

**Le même titre sur les autres écrans**, lu en HTTP réel :

```
carte        Développeur logiciel · front end · 2 ans et plus · Bac+2
visionneuse  Développeur logiciel · front end · 2 ans et plus · Bac+2
table        Développeur logiciel · front end · 2 ans et plus · Bac+2
```

Le nom du fichier exporté lit la même fonction.

**Les hauteurs restent égales** dans chaque rangée — 153 · 153 puis 129 · 129.

**`criteria.vitest.ts` : 5 cas neufs**, et **150 tests** verts sur `services/search/`. Format et
type-check propres sur les quatre espaces.

**Deux tests ont échoué d'abord**, et c'est ce qui rend la preuve solide : un test existant
affirmait « un segment par groupe, **dans l'ordre de sélection** » — la règle même que cette tâche
remplace ; et une fixture d'`ENT-050` utilisait un groupe `'mot'` **qui n'existe pas dans le
produit**. Le défaut était invisible tant que le nom des groupes n'avait aucune conséquence.

## Livré

- `webapp/services/search/criteria.ts` **(M)** — `NAMED_GROUPS` et `KEYWORDS_GROUP` ; `autoName`
  compose la suite fixe, plafonne les mots-clés au premier, et retombe sur les groupes restants
  quand les quatre ne donnent rien. `NAME_SEGMENTS` ne gouverne plus que le repli, et son
  commentaire le dit.
- `webapp/services/search/criteria.vitest.ts` **(M)** — la règle remplacée, plus l'ordre fixe, le
  mot-clé unique, les études qui restent, les groupes écartés et le repli.
- `webapp/services/search/card.vitest.ts` **(M)** — fixture corrigée : `'mot'` → `'motsCles'`.

## Reste à faire (hors périmètre)

- **Le groupe `metier` peut porter jusqu'à quatre éléments** — secteur, secteur d'expérience,
  fonction et intitulé, via le formulaire avancé. Borné et court, contrairement aux mots-clés :
  laissé tel quel, nommé pour qu'on ne le redécouvre pas.
- **`ENT-054`** (l'accueil) et **`ENT-055`** (la gestion) restent à mener.
