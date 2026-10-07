# TASK ENT-116 — entreprise : les gestes qui coûtent descendent au contact des lignes

- **Créée le** : 2026-08-13.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff`.
- **Statut** : ✅ fait le 2026-08-13 — la barre de sélection coiffe les lignes à l'intérieur du bloc
  du tableau, chaque canal annonce son coût avant le clic, et la gouttière ne porte plus aucun geste.
- **Type** : feat — la barre de sélection quitte la bande de guidage pour le bloc du tableau, et
  annonce son coût avant le clic.
- **Tests** : le coût affiché par canal devient une **fonction pure** de
  `webapp/services/search/reveal.ts`, déjà couvert par `reveal.vitest.ts` qu'on étend — lot vide,
  lot dont tous les profils sont déjà dévoilés sur ce canal, lot mixte, canal jamais dévoilé,
  cohérence entre le compte payant affiché et le coût total. Aucun test de rendu : la place de la
  barre se prouve à l'écran, son chiffre se prouve ici.
- **Réf** : `ENT-115` — les colonnes du tableau · `ENT-117` — le tableau qui devient l'étape
  « contacter », et qui édite la **même** gouttière · `151` — la fin de la shortlist, l'export sur
  la sélection · `152` et `153` — le dévoilement en quatre accès et son contrat ·
  [application_shell.md](../cadrage/entreprise/ui/application_shell.md) §4 — la gouttière, qui portait
  le menu de sélection · [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) §5 — la sélection ·
  [credits_et_devoilement.md](../cadrage/entreprise/api/credits_et_devoilement.md) — le coût et les
  garde-fous · [components/README.md](../../webapp/base/app/components/README.md)

## Contexte

Le tableau d'une recherche porte, dès qu'une case est cochée, **sept commandes** : retenir la
sélection, dévoiler l'e-mail, le téléphone, le CV, le compte rendu, exporter, tout désélectionner.
Quatre d'entre elles dépensent des crédits.

Elles vivent dans la **gouttière** — la bande la plus fine de la coque, sans fond, posée juste sous
la barre de marque, à trois rangées des lignes qu'elles visent. Le cadrage de la coque dit
explicitement de cette bande qu'elle porte deux choses « qui ne doivent jamais peser
visuellement » : un message de guidage, et **un menu replié**. Le menu a été déplié en barre de
boutons, et la bande a changé de nature sans que la décision soit prise nulle part.

La Visionneuse, elle, applique la règle inverse et la démontre : ses gestes qui engagent — retenir,
écarter, dévoiler — sont dans la colonne de droite, **contre la fiche du profil qu'ils visent**. Sa
gouttière ne porte qu'un réglage d'ordre, gratuit et réversible.

**La règle générale que cette carte inscrit** :

> La gouttière dit **comment le jeu se présente** — guidage, réglages gratuits et réversibles.
> La barre haute dit **sur quoi on travaille** — le nom, l'ampleur, où l'on en est.
> **Tout geste qui engage vit dans la zone de travail**, au contact de sa cible.

Un réglage gratuit peut vivre loin de ce qu'il touche ; un geste qui coûte doit se poser là où l'on
voit ce qu'il vise. C'est vrai d'autant plus ici qu'aucune de ces commandes **n'annonce ce qu'elle va
coûter** : cliquer « E-mail » sur dix profils sélectionnés débite dix crédits, immédiatement, sans
fenêtre de confirmation — le seuil est à onze.

## Périmètre

- **La barre de sélection quitte la gouttière** et se pose **dans le bloc du tableau, au-dessus de
  la rangée d'entêtes**, poussant les lignes vers le bas. Elle n'existe que tant qu'au moins un
  profil est coché.
- **Son contenu final** : le compteur (« 3 sélectionnés », suivi du rappel des sélectionnés hors de
  la vue courante s'il y en a), les quatre canaux de dévoilement, l'export, et « Tout
  désélectionner ».
- **Chaque canal de dévoilement affiche son coût** — « E-mail · 3 cr. » —, **recalculé en excluant
  les profils déjà dévoilés sur ce canal**.
- **« Retenir la sélection » quitte la barre.** Le geste reste sur l'écran — à la ligne, dans la
  colonne « Retenir » que `ENT-115` conserve —, mais il cesse d'exister **en lot**.
- **La gouttière du tableau ne porte plus aucun geste** ; ce qu'elle portera à la place est l'objet
  de `ENT-117`.

## Hors périmètre

- **Le seuil de confirmation** (au-delà de dix profils payants) et **le plafond de lot**
  (cinquante) : inchangés. Les déplacer est une décision sur la sécurité des crédits, qui vaut pour
  toute l'application et pas pour cet écran seul.
- **La fenêtre d'export** et son engagement RGPD : inchangés, seule la place de son bouton bouge.
- **La fenêtre de confirmation du dévoilement** : inchangée, y compris son texte.
- **Le comportement de la sélection au filtrage** — elle survit, et l'écran l'annonce déjà.
- **La colonne « Retenir »**, qui reste où elle est et n'a pas d'équivalent en lot : `ENT-115`.
- **Le fil d'Ariane, les onglets et l'état vide** : `ENT-117`.

## Décisions de cadrage

- **Au-dessus des entêtes, et non à leur place.** Substituer la barre à la rangée des noms de
  colonnes ne décale rien à l'écran — mais elle efface les entêtes au moment précis où le recruteur
  vérifie **qui** il a coché et **dans quelle colonne**. La barre pousse donc les lignes ; le
  décalage est le prix de la lisibilité.
- **Ni barre flottante en pied de tableau.** C'est le procédé courant des outils de gestion, et il
  recouvre en permanence une ligne du tableau. Sur un tableau qui défile, c'est un profil caché tout
  le temps pour une commande utile par intermittence.
- **Boutons à plat plutôt qu'un menu replié.** Le cadrage d'origine prévoyait un menu, parce que la
  gouttière ne devait pas peser. Dans le bloc du tableau, cette contrainte tombe : quatre canaux
  dépliés se cliquent en un geste au lieu de deux, sur l'écran dont c'est précisément le métier.
- **Le coût s'affiche, le seuil ne bouge pas.** Écrire le chiffre sur le bouton est du pur
  affichage : aucun comportement ne change, et le recruteur sait avant de cliquer. Exiger une
  confirmation pour chaque dépense ajouterait un clic au dernier geste du parcours, sur un écran fait
  pour enchaîner — et changerait une règle de dépense qui vaut ailleurs.
- **Le coût exclut ce qui est déjà payé.** Trois profils sélectionnés dont un a déjà son téléphone :
  le bouton dit « 2 cr. ». Afficher le compte de la sélection ferait croire à une double facturation
  et découragerait un geste qui, lui, est juste. La fonction de calcul existe déjà.
- **Retenir n'est pas une opération de masse.** Le geste reste disponible ligne par ligne, dans sa
  colonne. Le proposer sur un lot coché en balayant, c'est constituer une liste de profils qu'on n'a
  pas regardés — exactement ce que la Visionneuse existe pour éviter, une fiche à la fois avec tout
  son détail sous les yeux. La barre garde cinq commandes plutôt que six, et reste **la barre du
  contact** : dévoiler, exporter. Sa clé de traduction devient orpheline et se retire avec elle.

## Règles et cas limites

- **Sélection vide** → aucune barre, et les lignes reprennent leur place sans saut visible.
- **Sélectionnés hors de la vue courante** : le compteur le dit, comme aujourd'hui — c'est le
  garde-fou qui empêche de dévoiler en lot des profils qu'on ne voit plus.
- **Tous les profils sélectionnés déjà dévoilés sur un canal** → le bouton affiche zéro crédit et
  devient inactif : proposer un geste qui ne fera rien est pire que ne rien proposer.
- **Solde insuffisant** : le comportement actuel tient — la fenêtre de confirmation le dit, et le
  tout-ou-rien protège du solde partiel.
- **Recherche partagée en lecture seule** : les gestes d'écriture restent inactifs, l'export et la
  désélection restent disponibles.
- **Lot au-delà du plafond** : le message actuel reste, il n'est pas déplacé.
- **Largeur contrainte** : la barre **passe à la ligne**. Elle ne le fait qu'en dessous d'environ
  850 px, largeur à laquelle le tableau lui-même déborde déjà — le cas est donc rare, et il coûte une
  rangée de hauteur là où un défilement horizontal cacherait un bouton de dépense.
- **Accessibilité** : l'apparition de la barre est annoncée poliment, le compteur est lu, chaque
  bouton porte son coût dans son libellé accessible et non seulement à l'œil.
- **Trois langues** : les libellés à coût sont composés d'une clé existante et d'un nombre ; toute
  clé nouvelle est posée dans les trois fichiers, sous peine d'échec du test de parité.

## Documentation impactée

- [application_shell.md](../cadrage/entreprise/ui/application_shell.md) — §4 : la gouttière ne porte
  plus de menu de sélection ; §6 : la règle générale des trois zones s'y écrit, c'est le document du
  cadre et c'est là qu'elle vaut pour tous les écrans.
- [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) — §5, la sélection : où vit son menu,
  ce qu'il contient, et « Retenir » qui n'en fait plus partie.
- `webapp/base/app/components/README.md` — si la responsabilité du composant de gouttière change
  (elle cesse d'accueillir des gestes).
## Vérification

**Interaction réelle** dans le navigateur, stack du worktree entreprise, recherche « Développeur
logiciel · backend » : trois profils cochés à la case, puis lecture du DOM.

```
gouttière SANS sélection : Tous les profils de la recherche courante | Parcourir les profils
gouttière AVEC sélection : Tous les profils de la recherche courante | Parcourir les profils
barre                    : 3 sélectionné(s) | E-mail · 2 cr. | Téléphone · 3 cr. | CV · 3 cr.
                           | Compte rendu · 3 cr. | Exporter | Tout désélectionner
entêtes pendant l'action : POSTE · EXP. · RETENIR · NOM & PRÉNOM · E-MAIL · TÉL. · FENÊTRE MAROC
                           · VILLE ACTUELLE · MOBILITÉ
```

Deux faits s'y lisent directement : **la gouttière est identique avec et sans sélection** — elle ne
porte plus que le guidage et la bascule d'outil —, et **les entêtes restent lus** pendant qu'on agit.

**L'exclusion des profils déjà payés, vérifiée contre la donnée** : `E-mail · 2 cr.` là où les trois
autres canaux annoncent `3 cr.` L'API le confirme sur les trois profils sélectionnés —

| Profil | `revealedEmail` | `revealedPhone` |
| --- | --- | --- |
| Ayoub Rachid | `false` | `false` |
| Martin Dohou | `false` | `false` |
| Semi Gökol | **`true`** | `false` |

**Les classes de marge de la barre ne sont pas du CSS mort** : `--spacing-cell-x` et `-y` vivent dans
le bloc `@theme` de `sizing.css`, dont le commentaire documente `px-cell-x` / `py-cell-y` comme les
utilitaires attendus.

**Tests** : `vitest run services/search/reveal.vitest.ts services/search/candidate.vitest.ts
base/i18n/locales/parity.vitest.ts` → **3 fichiers, 44 tests verts en 337 ms**.

## Livré

- `webapp/services/search/reveal.ts` — `payableCount`, le compte de ce qui reste à payer par canal,
  descendu du composable vers la couche pure.
- `webapp/services/search/reveal.vitest.ts` — 5 cas : lot entier à payer, exclusion sur le bon canal
  seulement, zéro quand tout est payé, lot vide, indépendance des quatre canaux.
- `webapp/spaces/entreprise/app/composables/useReveal.ts` — `costOf` n'est plus que la multiplication
  par le prix courant.
- `webapp/spaces/entreprise/app/composables/useSearch.ts` — `favoriteSelection` retirée : ce commit
  lui a enlevé son seul consommateur.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` — la barre descend dans le bloc du
  tableau, les quatre canaux portent leur coût, la gouttière est vidée de ses gestes.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — `search.selection.retain` retirée, devenue orpheline.
- `dev-book/cadrage/entreprise/ui/application_shell.md` — §4.1, la règle des trois zones ; la
  gouttière ne porte plus le menu de la sélection ; le stepper dit « Retenir » et non « Favoriser ».
- `dev-book/cadrage/entreprise/ui/vue_la_table.md` — §5 réécrit, et la figure d'anatomie du §2.

## Reste à faire (hors périmètre)

- **Le seuil de confirmation reste à onze profils payants** : en deçà, le clic débite sans rien
  demander. Le coût affiché atténue le risque, il ne le supprime pas — déplacer le seuil est une
  décision sur la sécurité des crédits, valable pour toute l'application.
- **La bascule d'outil est encore dans la gouttière** (« Parcourir les profils ») : c'est le dernier
  élément non-guidage de la bande, et `ENT-117` l'en retire.

## Écart au cadrage

La carte annonçait un diff limité à la barre. Deux nettoyages s'y sont ajoutés, et ce ne sont pas des
refactors d'opportunité : **c'est ce commit qui a rendu `favoriteSelection` et
`search.selection.retain` orphelines**, en retirant leur unique consommateur. Les laisser aurait
signifié qu'un lecteur ultérieur ne puisse plus dire si elles servaient encore.

**La barre passe à la ligne au lieu de défiler**, contre ce que disait la carte. Le motif écrit
— garder au bloc du tableau une hauteur stable quelle que soit la largeur — était juste, mais il
pesait moins que son revers : un défilement horizontal met « Compte rendu · 3 cr. » hors de vue sur
l'écran dont le métier est justement de dépenser, et rien n'annonce qu'il reste des boutons à droite.
Le repli ne s'active qu'en dessous d'environ 850 px, où le tableau déborde déjà.

La **figure d'anatomie du §2 de `vue_la_table.md`** a été corrigée en entier, y compris sa liste de
colonnes qui relevait d'`ENT-115` : elle décrivait à la fois l'ancienne gouttière et les anciennes
colonnes, et une figure à moitié fausse coûte plus qu'un dépassement de deux lignes.

## Définition de terminé

- [x] Aucun bouton d'action ne subsiste dans la gouttière du tableau.
- [x] Cocher une case fait apparaître la barre au-dessus des entêtes ; décocher la dernière la fait
      disparaître.
- [x] Les noms de colonnes restent lisibles pendant qu'une sélection est active.
- [x] « E-mail · 2 cr. » sur trois profils dont un a déjà son e-mail dévoilé.
- [x] Un canal entièrement déjà dévoilé sur la sélection est inactif et affiche zéro — *couvert par le test ; la liaison `:disabled` porte la même condition que le compte*.
- [x] « Retenir la sélection » n'existe plus dans la barre, et sa clé de traduction non plus ;
      l'étoile de la colonne « Retenir », elle, fonctionne toujours.
- [x] Le rappel des sélectionnés hors de la vue courante est toujours là.
- [x] Le calcul du coût par canal est couvert par les cas listés en tête de carte.
- [x] `application_shell.md` porte la règle des trois zones, et `vue_la_table.md` décrit la barre
      telle qu'elle est.
