# TASK ENT-029 — entreprise : la carte profil passe en deux colonnes

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-07-31 — la carte tient sur deux colonnes, les coordonnées s'affichent
  une fois payées et leur existence s'annonce avant, et deux défauts invisibles aux gates ont été
  attrapés à la capture puis corrigés.
- **Type** : Frontend espace entreprise — refonte de mise en page.
- **Tests** : `sans objet` — la suite front ne ramasse que `**/*.vitest.ts` et ne couvre que du code
  **pur** (`webapp/vitest.config.ts`) ; aucune règle de cette carte ne sort du composant, et il
  n'existe pas d'infra de test de composant. La preuve est une capture dans les **deux thèmes** et
  les **deux états de dévoilement**, doublée d'un sondage du DOM rendu. Précédent : `ENT-026`.
- **Réf** : `ENT-006` (la carte comme résumé de décision) · `ENT-013` (le dévoilement comme geste) ·
  `ENT-026` (un signe nomme un attribut acquis) · `ENT-028` (les composants de la Visionneuse) ·
  [vue_la_visionneuse.md §4](../cadrage/entreprise/ui/vue_la_visionneuse.md) ·
  [base/app/components/README.md](../../webapp/base/app/components/README.md)

## Contexte

La carte de parcours empile tout sur une colonne : rangée de badges, en-tête, faits, correspondances,
accès payants, compétences. Sur un plein écran de 1440 px, la moitié droite est vide et la lecture
descend en un long ruban.

Trois défauts s'y ajoutent, tous constatés à l'écran.

**On paie des coordonnées qu'on ne voit jamais.** Une fois `CONTACT` acheté, la carte remplace le
bouton par un badge « acquis » et s'arrête là : `contactEmail` et `phone` ne sont rendus **nulle
part**. La Table, elle, les affiche. Même contrat, même crédit dépensé, deux résultats.

**Elle ne dit pas si le profil est joignable.** `hasEmail` et `hasPhone` descendent précisément pour
éviter de dépenser un crédit sur un injoignable, et la carte les ignore. Sur la base de ce worktree,
**95 candidats sur 2 561 ont un e-mail et 70 un téléphone** : le bouton était proposé sur les 2 561.

**Deux initiales occupent le titre.** `ENT-006` avait posé que le poste prend la première place tant
que le profil est masqué — la règle n'a jamais fonctionné, parce qu'elle attendait un `familyName` à
`null` alors que le serveur renvoie une initiale. On lit donc « B. V. » en grand, et le poste en gris
en dessous.

## Périmètre

`webapp/spaces/entreprise/app/components/search/ProfileCard.vue`, sa structure entière.

- **Bandeau** — le **poste** en titre, les deux gestes (`Retenir`, `Écarter`) à droite.
- **Colonne gauche, la carte de visite** — photo carrée, coordonnées, puis les accès payants.
- **Colonne droite, la substance** — nom, situation, compétences, faits, retour au Maroc.
- **Les états de profil descendent en bas à droite**, en signature.
- Les clés i18n de la joignabilité, dans les trois langues.
- **`UiAvatar` du kit** — la variante carrée, et le repli sur les initiales quand la photo est
  annoncée mais morte.

## Hors périmètre

- **Le contenu du corps** — poste actuel, entreprise, ancienneté, secteur, résumé rédigé. Trois de
  ces quatre encadrés et le résumé n'ont **aucune source** dans le contrat de la carte : ils
  attendent une tâche `espace/api` (voir « Aval »). La grille des quatre faits actuelle reste en
  place d'ici là.
- **Le dévoilement par canal** — dévoiler l'e-mail sans le téléphone suppose deux nouveaux accès
  côté serveur. Tranché comme direction, pas implémentable ici.
- **Le contenu des badges** : les cinq restent identiques, libellé, condition et couleur.
- **La Table**, qui porte les mêmes gestes par ligne.

## Décisions de cadrage

### Le poste prend le bandeau, le nom passe en colonne

C'est la règle d'`ENT-006` enfin appliquée, mais par la **mise en page** plutôt que par une
condition sur les données — ce qui la rend vraie dans les deux états au lieu d'un seul.

Le poste est ce qui permet de trancher ; le nom, masqué, ne dit rien et, dévoilé, ne dit qu'à qui
l'on parle. L'un est un critère, l'autre une étiquette. Le bandeau porte donc le poste **quel que
soit l'état de dévoilement**, et le nom ouvre la colonne droite.

### La colonne gauche est une carte de visite

Photo, coordonnées, documents : tout ce qui sert à **joindre** quelqu'un, au même endroit, dans
l'ordre où on s'en sert. C'est aussi ce qui met l'achat **contre la valeur qu'il débloque** — tant
que le bouton était en bas de carte et la valeur nulle part, on payait un crédit pour voir un badge.

Cela défait la préséance posée par `ENT-013` (une ligne pour les coordonnées, puis les documents en
corps réduit). Elle ne disparaît pas : elle devient verticale — coordonnées d'abord, documents
ensuite, dans la même colonne.

### La joignabilité s'énonce en toutes lettres

Avant l'achat, la colonne écrit « E-mail disponible » ou « Téléphone indisponible ». Elle ne floute
pas.

**Divergence assumée avec la Table**, qui utilise `MaskedValue` — des points floutés si la donnée
existe, un tiret sinon. Les deux écrans n'ont pas les mêmes contraintes : une colonne de tableau
tient dans sa largeur sur vingt lignes, une carte occupe un plein écran et a la place d'une phrase.
Et le flou est un artifice — il simule une donnée qui n'est jamais descendue au navigateur.

C'est une **dette**, pas une élégance : deux vocabulaires du masquage dans le même produit. Son
unification est renvoyée.

### Les états descendent en signature, et un seul garde l'accent

En bas à droite, les badges cessent d'être une accroche : ils deviennent ce qui **clôt** la lecture.
C'est cohérent avec ce qu'ils sont — des attributs constatés, pas des arguments.

La règle de couleur ne bouge pas : **« Rencontré » seul porte l'accent**, les autres sont neutres.
Cinq badges accentués ne hiérarchisent rien, et l'accent cesse alors de vouloir dire quelque chose.
Celui-là le mérite : c'est la promesse du produit, constatée et pas supposée.

**Point rouvert et écarté ici** : « Au Maroc » est le cœur de la promesse produit et se retrouve en
gris, en bas. Lui donner un second accent est défendable — mais c'est une décision de charte, elle se
prend dans `brand-guidelines.md`, pas dans un composant.

### Un carré à initiales quand la photo manque, et quand elle est morte

La photo passe du rond au **carré légèrement arrondi**, et un profil sans photo — environ un sur
sept — reçoit un carré neutre portant ses initiales. Dans une pile où chaque carte occupe un plein
écran, une géométrie qui change d'une carte à l'autre n'est plus une géométrie.

**Deuxième cas, découvert à la capture** : une URL de photo n'est pas une photo. Le storage Jarvi
peut l'annoncer alors que le fichier a disparu — le navigateur dessine son icône d'image brisée et
déborde le texte alternatif hors du carré. Le repli ne couvrait que l'URL **absente**, pas l'URL
**morte**. Une erreur de chargement bascule désormais sur les initiales.

### Écart au cadrage — un second fichier

`webapp/base/app/components/ui/Avatar.vue` est touché. `UiAvatar` faisait déjà tout ce que cette
carte décide — photo, repli sur les initiales, gabarit fixe ; réécrire un avatar dans la card aurait
produit un second composant à maintenir. Il lui manquait la forme, d'où la variante `square`.

### Le résumé reste absent

Le bandeau ayant pris l'accroche LinkedIn, le bloc « résumé » n'a plus de source : `summary` existe
sur l'entité et sur la fiche détaillée, pas sur la carte de recherche. On le laisse **absent** plutôt
que de composer une phrase à partir des faits — une phrase fabriquée aurait été à défaire dès que le
vrai résumé descend.

## Règles et cas limites

- **Recherche partagée** (`editable` faux) : ni gestes ni boutons d'achat, mais les coordonnées déjà
  payées par l'organisation restent lisibles — l'accès est acquis à l'entreprise, pas au membre.
- **Coordonnée dévoilée mais inexistante** : « Téléphone indisponible » subsiste après l'achat.
  `hasPhone` reste faux, et un crédit ne fabrique pas un numéro.
- **Aucun badge** : le coin bas droit reste vide, il ne réserve pas de hauteur.
- **Solde insuffisant** : les boutons d'achat restent désactivés avec leur infobulle — inchangé.
- **Aucun texte en dur**, y compris dans les `aria-label`.
- **Propriétés logiques obligatoires** (`ps`/`pe`, `start`/`end`) : les deux colonnes s'inversent en
  arabe.
- **Sous `lg`, les colonnes s'empilent** — la carte de visite passe au-dessus de la substance.
- **La carte reste passive** : elle émet, la page décide.
- **Un seul défilement**, celui de la pile ; aucune carte ne défile en interne.
- Le marqueur de racine `search-profile-card` reste en première classe de l'`<article>`.

## Documentation impactée

**Aucune page**, et les déclencheurs ont été vérifiés un par un :

- `4.design/design-system.md` — seul déclencheur applicable (un composant du kit change). Sa section
  « Les composants » **inventorie** le kit sans documenter d'API. Ajouter une variante ne la rend pas
  fausse.
- Aucun `README.md` de dossier ne change de convention.

**Ce qui sort de la lane et remonte** : la galerie `/artefacts/ux-kit`, que la charte présente comme
rendant « chaque composant dans ses variantes ». La variante carrée devrait y figurer — mais elle vit
dans `spaces/public/`. Nommée en aval, pas traitée ici.

## Définition de terminé

- [x] Le bandeau porte le **poste**, dans les deux états de dévoilement, et les deux gestes à droite.
- [x] Colonne gauche : photo carrée, coordonnées, accès payants — dans cet ordre.
- [x] L'e-mail et le téléphone **s'affichent** une fois payés ; leur disponibilité s'énonce avant.
- [x] Colonne droite, dans cet ordre : nom, situation, faits, correspondances satisfaites, retour au
      Maroc, compétences.
- [x] Les états de profil ferment la carte en bas à droite, « Rencontré » seul en accent.
- [x] Un profil sans photo — ou dont la photo est morte — porte un carré à initiales.
- [x] Sous `lg`, les deux colonnes s'empilent sans rien perdre.
- [x] Captures dans les deux thèmes, sur un profil **masqué** et un profil **dévoilé**.
- [ ] `bun run quality-check` — **non lancé** : les gates tournent à l'ouverture de la PR, en une
      passe pour toute la lane. Format appliqué sur les deux composants touchés.

## Vérification

**Quatre captures réelles**, connecté sur la stack du worktree, sur une recherche de 1 076 profils —
masqué et dévoilé, en clair et en sombre. Le shell rendu correspond au dessin validé : bandeau
`poste + gestes`, colonne de visite à gauche, substance à droite, états en signature en bas à droite.

| | Masqué | Dévoilé |
| --- | --- | --- |
| Bandeau | accroche bornée à deux lignes | `Développeur backend PHP` |
| Colonne gauche | *E-mail disponible* · *Téléphone disponible*, puis Coordonnées · CV · Compte rendu | `yassine@example.com` · `+33600000000`, puis CV · Compte rendu |
| Bas à droite | vide — aucun état | **Rencontré** en accent, Disponible et Favori en neutre |

Le bouton « Coordonnées » **disparaît** une fois l'accès acquis : la valeur au-dessus le dit mieux
qu'un badge. C'est le défaut de fond de la carte, réparé et constaté.

**Deux défauts attrapés par le sondage du DOM, invisibles à toute gate.**

Le premier : la mention de joignabilité sortait en encre pleine au lieu du gris italique.
`null !== candidate.contactEmail` prenait une clé **absente** de la charge utile pour une valeur
présente ; passé en test de vérité. Vérifié après correction — `italic text-muted`,
`rgb(71, 94, 78)`.

Le second : l'avatar d'un profil dont la photo est annoncée mais morte affichait l'icône d'image
brisée du navigateur, texte alternatif débordant hors du carré. Vérifié après correction — le carré
porte « YE ».

**Un chiffre qui dépasse la carte** : sur les 2 561 candidats de cette base, **95 ont un e-mail et
70 un téléphone**. Le bouton était proposé sur les 2 561 sans jamais dire lesquels étaient
joignables.

**Donnée de recette** : aucun profil du jeu ne portait de badge, donc leur placement n'était pas
démontrable. Un candidat de test a été marqué rencontré, disponible immédiatement et retenu en
favori dans la base de ce worktree — la capture des badges montre un état provoqué, pas spontané.

## Livré

- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` **(M)** — deux colonnes, le poste
  en bandeau, la carte de visite à gauche, les états en signature ; le bloc payant du bas disparaît.
- `webapp/base/app/components/ui/Avatar.vue` **(M)** — variante `square`, et repli sur les initiales
  quand le chargement de la photo échoue.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — la joignabilité en toutes lettres.
- `dev-book/tasks/ENT-029-…md` **(+)** — cette carte.

## Aval

- **Le contenu du corps** (lane `espace/api` puis `espace/entreprise`) : `summary`, `currentTitle`,
  `currentCompanyName` — ce dernier soumis à l'accès contact — et `currentRoleMonths` existent sur
  l'entité et sur la fiche détaillée, mais pas sur `CandidateCardOutput`. Sans eux, le résumé et
  trois des quatre encadrés visés n'ont pas de source.
- **Le dévoilement par canal** : deux accès `EMAIL` et `PHONE` côté serveur, pour cliquer directement
  sur la coordonnée qu'on veut. À trancher avant cadrage — le prix, le sort du nom (aujourd'hui lié
  au même accès), et le sort des dévoilements déjà payés.
- **Servir le CV et le compte rendu** une fois payés : aujourd'hui l'achat n'ouvre rien.
- **Unifier le vocabulaire du masquage** entre la Table (flou) et la Visionneuse (en toutes lettres).
- **Un second accent pour « Au Maroc »** — décision de charte.
- **La galerie `/artefacts/ux-kit`** doit montrer la variante carrée — lane `espace/public`.
- **La joignabilité réelle du vivier** : 95 e-mails et 70 téléphones sur 2 561 candidats. La carte
  cesse de le cacher, elle ne le corrige pas.
- **Le réalignement de `vue_la_visionneuse.md`** sur la Visionneuse livrée — lane `espace/outillage`.
