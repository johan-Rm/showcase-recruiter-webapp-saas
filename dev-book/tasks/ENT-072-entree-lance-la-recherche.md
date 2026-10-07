# TASK ENT-072 — Entrée lance la recherche

- **Créée le** : 2026-08-08.
- **Session** : `cd8779a6-574b-4406-add4-fbbedd8ef042`.
- **Statut** : ✅ fait le 2026-08-08 — Entrée lance la recherche, pour la première fois depuis que
  l'écran existe : constaté en navigateur (création + navigation vers la recherche, sans
  rechargement), le chemin vide affiche le message sans rien créer, le bouton de la barre est
  intact, conventions à zéro violation. Trois écarts au cadrage, tous tracés : le motif requalifié
  (le pipeline s'était débloqué par l'amont), le **bouton de soumission caché** ajouté — la
  découverte-clé : sans lui, le formulaire seul laissait Entrée morte, et l'avait toujours
  laissée —, et le test de composant devenu exemption motivée, tranchée par l'utilisateur.
- **Type** : fix — un geste promis et jamais tenu : la touche Entrée est censée lancer la recherche
  depuis un champ, l'écran d'origine l'écrivait en commentaire, et elle n'a **jamais** rien fait —
  ni avant la recomposition de l'écran, ni après. Elle lance désormais.
- **Tests** : `sans objet côté unitaire` — **exemption tranchée en cours de tâche, cf. Décisions de
  cadrage**. Le dépôt ne teste que le code pur, sans runtime Nuxt (doctrine écrite dans
  `vitest.config.ts` : « tant qu'il tient, @nuxt/test-utils reste inutile ») ; aucune bibliothèque
  de montage n'est installée, et ce diff est du câblage — une balise, un événement, un bouton
  caché — sans une fonction à extraire. La couverture est la **preuve navigateur** consignée dans
  *Vérification* : Entrée vide → message sans création, Entrée avec critère → création et
  navigation, sans rechargement de page, bouton de barre intact. Précédent : `WEB-015`, exemptée
  pour la même raison. **Limite assumée** : rien ne garde ce comportement en CI — s'il recasse, ce
  sera à nouveau en silence.
- **Réf** : `ENT-067` (l'écran d'origine, qui portait Entrée par un `<form>` de page) · les commits
  amont `cf3cac0` et `25681e3` (la recomposition qui l'a perdue) · la règle CI
  `pages-no-interactive-template` dans [rules.yaml](../../webapp/scripts/ci/rules.yaml), qui
  explique pourquoi le formulaire ne peut pas revenir dans la page · fichiers touchés :
  [components/search/Sentence.vue](../../webapp/spaces/entreprise/app/components/search/Sentence.vue),
  [recherches/nouvelle.vue](../../webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue).

## Contexte

Sur l'écran « Nouvelle recherche », un recruteur décrit qui il cherche en complétant une phrase —
un poste, une expérience, un niveau d'étude, des mots-clés. À l'origine (`ENT-067`), **taper Entrée
dans un champ lançait la recherche** : la page enveloppait tout l'écran d'un `<form>`, et son
commentaire disait le geste en toutes lettres — « Entrée y garde son effet naturel ».

**Sauf que ce geste n'a jamais fonctionné — le commentaire promettait, la page ne tenait pas.** La
règle HTML de la *soumission implicite* dit qu'un formulaire **sans bouton de soumission à
l'intérieur** ne se soumet à Entrée que s'il ne compte qu'**un seul** champ texte. Or la phrase en a
plusieurs, et le bouton visible a toujours vécu **hors** du formulaire — téléporté dans la barre de
la coque, en `type="button"`. Deux champs texte, zéro bouton interne : Entrée ne faisait rien, dès
`ENT-067`. Prouvé a contrario pendant l'implémentation : le formulaire reposé à l'identique, Entrée
restait morte ; le bouton caché ajouté, elle lance.

Depuis, ce `<form>` de page violait une règle d'architecture bloquante
(`pages-no-interactive-template` : une page n'écrit pas de code interactif), et la recomposition
arrivée par l'amont (`cf3cac0`, puis `25681e3` « keep new search page convention compliant ») a levé
la violation en supprimant le formulaire tout court. Constaté sur l'arbre : plus aucun `<form>`,
aucun gestionnaire de touche, nulle part dans l'écran.

**Résultat, avant cette carte : Entrée ne fait rien**, et n'a jamais rien fait. Le seul moyen de
lancer est le bouton de la barre haute — à la souris, ou au clavier après avoir tabulé à travers
tous les champs. Pour un écran dont le cœur est un champ de saisie, le geste le plus naturel manque,
et il manque **en silence** : aucune gate ne teste un comportement, elles testent des structures.

### Pourquoi cette carte a changé de motif en cours de route

Elle a été cadrée comme « sortir le formulaire de la page pour lever la violation qui bloque le
pipeline ». Entre le cadrage et l'implémentation, l'amont a levé la violation lui-même — le
pipeline est débloqué. Mais le **mécanisme** cadré (le formulaire descend dans le composant qui
porte les champs, et remonte un événement de lancement) est exactement ce qui répare Entrée. Le
code cadré survit intégralement ; seul le problème qu'il résout a changé de nom.

## Périmètre

- **La racine de `SearchSentence` devient le formulaire** — `<UiCard as="form">`, la propriété
  existant pour ça (« la carte n'impose pas sa sémantique : la page choisit »). Rien ne bouge à
  l'écran : mêmes classes, même allure, seule la balise change. Entrée retrouve son effet natif,
  puisque le formulaire entoure à nouveau les champs.
- **La soumission est prévenue dans le composant** (`@submit.prevent`) et remonte un événement
  **`launch`** : le composant demande, la page décide.
- **La page écoute** — `@launch="submit"` — et garde toute son orchestration : brouillon, comptage,
  création, navigation. Une ligne.
- **Un bouton de soumission caché à l'intérieur du formulaire** — c'est lui qui donne son effet à
  Entrée (cf. la règle de soumission implicite, dans le Contexte). Masqué par l'attribut natif : il
  ne s'affiche pas, ne se tabule pas, n'est pas annoncé aux lecteurs d'écran, mais compte pour la
  soumission. *Ajouté en cours d'implémentation — cf. Décisions de cadrage.*

## Hors périmètre

- **Le reste de la recomposition amont** : la disparition des téléportations, le changement de
  coque, la barre haute — tout cela est le travail d'`ENT-071` et des commits amont, pas le sujet.
- **L'écran Table** et les autres lignes de la dette `pages-no-interactive-template`.
- **Tout autre raccourci clavier** : cette carte rend Entrée, elle n'invente pas de nouveaux gestes.

## Décisions de cadrage

### Le formulaire va au composant qui a les champs — pas dans la page, pas dans une enveloppe

Le remettre dans la page recréerait la violation levée par l'amont. Une enveloppe minimale à slot
déplacerait les mots interdits sans déplacer la responsabilité. `SearchSentence` est le siège
naturel : les champs y sont, Entrée n'a d'effet natif que si la balise les entoure, et le composant
n'a qu'un consommateur.

### L'événement s'appelle `launch`, et ce n'est pas un goût

Le vérificateur des conventions applique des expressions régulières ligne à ligne sur le template
des pages — il ne distingue pas un `@submit` de balise native d'un `@submit` d'événement de
composant. Écouter un événement nommé `submit` dans la page rejouerait la violation. `launch` est
le mot du produit : le bouton s'appelle « Lancer », et le commentaire historique de l'écran disait
« LANCER, C'EST CRÉER ».

### ÉCART AU CADRAGE — ce n'était pas une régression, et le formulaire seul ne suffisait pas

La carte a été requalifiée deux fois, et les deux fois par un fait.

D'abord son motif : cadrée pour « débloquer le pipeline », elle a trouvé le pipeline débloqué par
l'amont avant d'avoir commencé — le correctif de la violation était arrivé par la PR #3 de
l'organisation. Le mécanisme cadré restait pourtant le bon, pour un autre problème : Entrée.

Ensuite son récit : la carte affirmait qu'Entrée était une **régression** de la recomposition.
Faux. Le formulaire reposé exactement comme le cadrage le prévoyait (`as="form"`,
`@submit.prevent`), la preuve navigateur est restée muette : Entrée ne déclenchait toujours rien.
La cause est la règle de soumission implicite — plusieurs champs texte, aucun bouton de soumission
interne, donc pas de soumission à Entrée — et cette structure était **déjà celle de l'écran
d'origine**, dont le bouton a toujours été téléporté hors du formulaire. Le commentaire historique
« Entrée y garde son effet naturel » décrivait une intention, pas un comportement.

D'où la pièce ajoutée au périmètre : le bouton de soumission caché. C'est lui, et non le
formulaire, qui rend le geste réel — le formulaire n'était que la condition préalable.

### Le test de composant cadré est devenu une exemption, et c'est une décision, pas un oubli

Le cadrage promettait un test de composant sur la soumission. À l'implémentation, la doctrine de
test du dépôt s'y est opposée frontalement : `vitest.config.ts` borne les tests unitaires au code
pur sans runtime Nuxt, aucune bibliothèque de montage n'est installée, et introduire ce mécanisme —
deux dépendances et une frontière documentée franchie, dans une configuration partagée par les
quatre espaces — n'a pas sa place en passager d'un fix. L'exemption a été **posée à l'utilisateur
et tranchée par lui**, preuve navigateur à l'appui, sur le précédent de `WEB-015`. Si le besoin
d'une infrastructure de test de composants se confirme, il se cadrera pour lui-même, sur la lane
socle.

### La prévention de la soumission vit dans le composant, pas chez l'appelant

Un rechargement de page perd le brouillon. Si la prévention était à la charge de l'écouteur, chaque
futur consommateur devrait y penser — donc l'un d'eux l'oublierait.

## Règles et cas limites

- **Le bouton de la barre haute ne bouge pas** : il est hors du formulaire, il lance au clic
  (`type="button"`). Ne pas le « réparer » au passage.
- **Le contrat `change` ne bouge pas** : la phrase collecte et remonte à chaque frappe ; `launch`
  s'ajoute, il ne remplace rien.
- **Entrée sans critère suit la règle existante de la page** : `submit` refuse déjà de créer une
  recherche vide et affiche le message d'erreur — le chemin clavier emprunte le même code que le
  chemin bouton, aucun cas nouveau.
- **Le marqueur `search-sentence` reste en première classe** de la racine du composant (règle CI
  `components-root-marker`).
- **Racine unique, commentaires compris** — le commentaire qui documente le choix vit **dans** la
  racine, jamais avant elle.
- **`bun run quality-conventions-check` reste à zéro violation** : le correctif ne doit réveiller
  ni `pages-no-interactive-template`, ni aucune des règles ajoutées par l'amont.
- **Aucune clé i18n** n'apparaît ni ne bouge.

## Documentation impactée

Aucune. Le modèle de page appliqué est déjà écrit dans `base/app/pages/README.md`, l'usage `as` de
la carte est documenté dans le composant, et aucune convention de dossier ne change.

## Définition de terminé

- [x] Dans le navigateur : Entrée dans le champ de poste **lance la recherche** — création puis
      navigation vers `/recherches/{uuid}` — constaté, pas supposé.
- [x] Entrée sans aucun critère affiche le message « aucun critère » et ne crée rien.
- [x] Le bouton « Créer ma recherche » de la barre haute fonctionne toujours.
- [x] Aucun rechargement de page au passage (la soumission native est prévenue).
- [x] Le choix de couverture est tracé : exemption motivée dans la ligne **Tests**, décision et
      limite assumée écrites dans la carte.
- [x] `bun run quality-conventions-check` : zéro violation.
- [ ] Les gates de la lane passent à l'ouverture de sa PR — pas ici : la lane empile ses tâches
      pour une seule passe.

## Vérification

Constats relevés dans un navigateur réel (Chromium), connecté en recruteur
(`owner@demo.showcase-recruiter-webapp-saas.example`) sur la stack du worktree entreprise, l'écran servi portant le diff à
chaud. Chaque ligne est une observation, pas une intention.

| Geste | Observé |
| --- | --- |
| Racine de la phrase | `<form>` — la carte du kit rend la balise demandée par `as` |
| Entrée, aucun critère | reste sur l'écran ; message : « Posez au moins un critère : le vivier entier n'est pas un résultat de recherche. » ; rien créé |
| Entrée, critère « commercial » | recherche créée, navigation vers `/recherches/019fe59a-5fc3-…` |
| Rechargement de page | **aucun** — un marqueur posé dans la fenêtre avant le geste a survécu au lancement |
| Bouton « Créer ma recherche » (barre haute) | clic → création, navigation vers `/recherches/019fe59c-8b5d-…` |
| `bun run quality-conventions-check` | **18 règles, aucune violation** |

**La contre-épreuve qui a requalifié la carte** : au premier essai, le formulaire seul était en
place — racine `<form>` constatée — et Entrée ne déclenchait **rien**, ni message ni création. Le
bouton caché ajouté, tout le tableau ci-dessus passe. C'est la démonstration en deux temps de la
règle de soumission implicite, et la preuve que l'écran d'origine — même structure, bouton dehors,
plusieurs champs texte — n'a jamais honoré Entrée.

## Livré

- `webapp/spaces/entreprise/app/components/search/Sentence.vue` (M) — la racine devient le
  formulaire (`as="form"`), la soumission est prévenue sur place et remonte l'événement `launch` ;
  un bouton de soumission caché donne son effet à Entrée, et son commentaire explique pourquoi il
  n'est pas un résidu. Le contrat `change` est intact.
- `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` (M) — la page écoute
  `@launch="submit"` ; une ligne, plus le commentaire du partage. Son orchestration ne bouge pas.

## Reste à faire (hors périmètre)

- **Rien ne garde Entrée en CI** — limite assumée de l'exemption : si le comportement recasse, ce
  sera à nouveau en silence. Le besoin d'une infrastructure de test de composants, s'il se
  confirme, se cadre pour lui-même sur la lane socle, cette carte comme premier cas d'usage.
- **La lane porte deux tâches non poussées** (`ENT-071`, celle-ci) : la PR de lane les livrera
  ensemble. Le conflit connu entre `ENT-071` et `WEB-016` sur `ActionBar.vue` se résoudra à ce
  moment-là — garder **le palier** (`--band-action`) **et la grille**, les deux étant orthogonaux.
