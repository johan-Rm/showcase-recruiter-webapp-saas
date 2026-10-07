# TASK ENT-038 — entreprise : le panneau dit ce que le profil a et ce qui lui manque

- **Créée le** : 2026-08-02.
- **Session** : `cb761d33-c5ed-45b5-a3db-3340b185010f`.
- **Statut** : ✅ fait le 2026-08-03 — le panneau confronte le profil affiché aux critères de la
  recherche, valeur du profil en face, et les libellés de critère sont devenus lisibles partout où
  ils s'affichent.
- **Type** : Frontend espace entreprise — refonte du panneau de contrôle, et la couche pure de
  libellés qu'elle sort au passage.
- **Tests** : `describeCriterion` et la répartition trouvé / non trouvé descendent dans
  `services/search/criteria.ts`, seule couche que la suite front ramasse (`webapp/vitest.config.ts`
  ne couvre que du code **pur**) — une facette mal libellée ou un verdict inversé se lit en un test
  et se voit mal à l'œil. Précédents : `describeDecision` (`ENT-028`), `hasAccess` (`ENT-037`).
  S'y ajoutent des captures authentifiées dans les deux thèmes, sur un profil qui coche tout et un
  qui rate deux critères.
- **Réf** : `ENT-037` (le dévoilement par canal — cette carte **renverse** une de ses décisions) ·
  `ENT-036` (la colonne vient d'être refactorée, relire avant d'y toucher) · `ENT-028` (l'extraction
  des sept composants) ·
  [vue_la_visionneuse.md](../cadrage/entreprise/ui/vue_la_visionneuse.md) ·
  [services/README.md](../../webapp/services/README.md) ·
  [base/app/components/README.md](../../webapp/base/app/components/README.md) ·
  [i18n.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/i18n.md) — RTL

## Contexte

Le recruteur parcourt une pile de profils pour trancher, et la correspondance entre un profil et sa
recherche lui est dite **à deux endroits, sans jamais la valeur qui compte**.

Sur la carte, une pastille cochée par critère satisfait : `✓ 1`, `✓ LICENCE`, `✓ vue`. Dans la
colonne, la liste de ce qui manque. Trois défauts s'y logent :

- **La valeur du profil n'apparaît jamais.** `✓ LICENCE` sur un profil **Master** coche le critère
  sans dire ce que le profil vaut — la règle serveur est « au moins ce niveau », et l'écran n'en
  laisse rien voir. Même chose sur l'expérience, où le rang du profil est pourtant l'élément qui
  fait trancher.
- **Le critère est affiché brut.** `✓ 1` ne porte ni son unité ni son sens ; `LICENCE` est la valeur
  d'énumération, alors que sa traduction existe depuis toujours (`search.education.LICENCE`). La
  barre haute a le même défaut, sur les mêmes valeurs.
- **Ce qui manque est loin de ce qui est trouvé.** Les deux moitiés d'une même question vivent dans
  deux composants, et le recruteur les rapproche de tête.

**Et un compteur a perdu son objet.** Depuis `API-018`, le dévoilement se paie **par canal** —
e-mail, téléphone, CV, compte rendu, un crédit chacun, et l'identité ne se paie plus. Un profil
n'est plus « dévoilé » ou non ; en compter le nombre mesure quelque chose qui n'existe plus.

## Périmètre

- **Le bloc « ce profil face à vos critères »** : une ligne par critère posé, dans l'ordre de la
  barre haute — ce qui était demandé, le verdict, et la valeur du profil quand la carte la porte.
- **`ProgressHeader`** : les trois gros compteurs se replient en une ligne de texte
  (« 16 vus · 13 à voir · 3 écartés »), le compteur **« dévoilés » disparaît**, et la barre de
  progression passe des profils **défilés** aux profils **vus**.
- **`ControlPanel`** : il place le nouveau bloc, et cesse de recevoir `revealedCount`.
- **`useSearch`** : `revealedCount` disparaît. `isRevealed` **reste** — `services/search/export.ts`
  s'en sert comme seuil d'export, aligné sur celui du serveur.
- **`describeCriterion`** dans `services/search/criteria.ts`, consommé par le nouveau bloc, **par la
  barre haute** (`CriteriaBar`) et **par le nom automatique** (`autoName`).
- **`ProfileCard`** : les pastilles `✓` disparaissent ; le surlignage des compétences trouvées en
  gras accent **reste**, c'est le seul endroit où la correspondance se montre là où elle est.
- **Les clés i18n**, dans les trois langues.
- **Le cadrage client** [vue_la_visionneuse.md](../cadrage/entreprise/ui/vue_la_visionneuse.md),
  §5 et §8.

## Hors périmètre

- **Le panneau invisible sous `lg`** (`hidden lg:block`) : en mobile, ni compteurs, ni favoris, ni
  sortie vers la Table — et donc, demain, pas de bloc critères. La dette **préexiste** à cette
  carte ; la payer ici mettrait un second sujet de mise en page dans le même commit.
- **La dérive de vocabulaire du cadrage** : la page parle encore de « shortlist » et de
  « Télécharger la shortlist » là où l'écran dit « Mes favoris » et « Ma sélection ». Antérieure à
  cette tâche, elle part en aval.
- **Le vocabulaire du masquage** entre la Table (points floutés) et la Visionneuse (en toutes
  lettres), déjà inscrit par `ENT-037`.
- **Toute évolution du contrat serveur.** La carte se livre sur le contrat actuel — voir la décision
  « Une facette sans valeur côté profil le dit en ne disant rien ».

## Décisions de cadrage

### Le panneau devient celui du profil affiché

Aujourd'hui il résume la **recherche** ; seul le bloc « non trouvé » suit le profil courant. Il
devient, en plus, le lieu où le profil affiché se confronte aux critères : il change à chaque carte.

C'est ce qui justifie que les pastilles quittent `ProfileCard`. Laisser les deux afficherait la même
information à deux endroits d'un même écran, l'un des deux étant moins complet que l'autre.

### Toutes les facettes, une seule liste, dans l'ordre des critères

Le bloc couvre **tous** les critères posés, pas seulement l'expérience, les études et les mots-clés :
une recherche née du langage naturel pose une langue, un pays, une fenêtre de présence — un bloc
muet sur la moitié de ses critères mentirait par omission.

Une seule liste, ordonnée comme la barre haute et comme le nom automatique : on rapproche les deux
sans effort. Deux groupes « trouvés » / « non trouvés » se redimensionneraient à chaque profil, et
feraient sauter les favoris qui sont dessous — la liste unique garde une **hauteur constante**.

### Les libellés descendent dans une couche pure

`describeCriterion` rend des **clés**, pas du texte : `services/` est une couche pure, sans import
framework, et la traduction appartient au composant. C'est aussi ce qui la rend testable hors
runtime.

Elle sert **les trois** consommateurs — le bloc, la barre haute et `autoName`. Ne la brancher que
sur le panneau laisserait le haut de page afficher `LICENCE` pendant que la colonne affiche
`Licence`, soit deux graphies pour le même critère sur un même écran.

Le nom automatique **n'est pas persisté** (l'API garde `isAutoNamed`, le front recompose) : le faire
suivre ne demande aucune migration.

### « Dévoilés » disparaît — et ce que ça renverse

`ENT-037` a décidé, la veille, que « dévoilé reste une propriété du profil » et que le compteur
comptait les **personnes engagées**, pas les accès achetés.

Ce raisonnement tenait tant que le compteur avait une place. Il n'en a plus : la colonne accueille un
bloc qui sert la **décision**, et un chiffre qui vaut zéro sur la quasi-totalité d'une session de
tri occupe la zone la plus visible de l'écran pour ne rien dire. Le renversement porte sur
l'**affichage**, pas sur la notion : `isRevealed` reste, et l'export continue de s'en servir.

### La progression mesure le travail, pas le défilement

La barre suit `position / total` — la position de défilement. Elle affiche 86 % au-dessus d'une ligne
qui annonce « 13 à voir » sur 29. Elle passe sur les profils **vus** : elle raconte alors la même
chose que la ligne juste dessous, et « reprendre » devient lisible — la barre recule quand on saute
des profils, ce qui est exactement ce qui s'est passé.

Le rang `25 / 29` reste : il dit où l'on est dans la pile, ce que la barre ne dit plus.

### Une facette sans valeur côté profil le dit en ne disant rien

Quatre facettes n'ont aucune contrepartie sur la carte candidat : la **langue**, le **secteur
d'expérience**, la **ville au Maroc** et la **fenêtre de présence** — les deux dernières parce que
la carte porte des *périodes*, pas une valeur comparable à un critère. Le serveur n'aide pas :
`matchedOn` n'est pas renseigné dès que le critère échoue (`CriterionMatcher::one`), donc même les
facettes portées par la carte n'ont pas de valeur profil côté match.

Leur ligne dit le critère et le verdict, sans colonne « profil · … ». L'asymétrie est visible et
assumée : on ne montre pas ce qu'on n'a pas. L'alternative — une tâche sur `espace/api` pour que
`matchedOn` porte la valeur du profil même en échec — ferait attendre cette carte derrière un merge
d'API, exactement le blocage vécu par `ENT-037` derrière `API-018`.

## Règles et cas limites

- **Pas de profil courant** — chargement, jeu vide, aucun critère : le bloc **disparaît
  entièrement**, titre compris, comme le fait déjà le bloc « non trouvé ». Le reste de la colonne
  tient seul.
- **Recherche sans critère** : rien à confronter, donc pas de bloc.
- **Recherche partagée** (`editable` faux) : le bloc est identique — il informe, il ne porte aucun
  geste.
- **Le verdict ne passe jamais par la couleur seule** : le `✓` / `✗` est décoratif
  (`aria-hidden`), et l'énoncé porte le verdict en toutes lettres. Sans quoi le bloc ne dit rien à
  qui ne distingue pas le vert du gris.
- **Hauteur constante d'un profil à l'autre** : c'est la raison d'être de la liste unique, et ça se
  vérifie à l'œil en défilant.
- **`languageLevel` n'apparaît jamais dans `matches`** — le matcher l'écarte volontairement, elle
  précise `language` qui porte déjà le niveau atteint. Ce n'est pas une ligne manquante à rattraper.
- **Aucun texte en dur**, `aria-label` compris ; trois langues, et l'arabe en RTL — le bloc aligne
  des colonnes, c'est là que le RTL casse.
- **Les composants restent passifs** : ils reçoivent, ils n'appellent pas `useSearch`.
- **Relire `ENT-036` avant de toucher à la colonne** : elle a refait sa structure de racine et ses
  balises de région il y a deux jours.

## Documentation impactée

- **[vue_la_visionneuse.md](../cadrage/entreprise/ui/vue_la_visionneuse.md) §2, §5 et §8** — le §5.2
  spécifie « trois compteurs : Vus · Favoris · Dévoilés », le §2 les dessine, et le §8 l'inscrit en
  décision validée. Les trois deviennent faux. Le §5 gagne le bloc « ce profil face à mes critères ».
  Page **écrite pour le client** : aucune note, aucune date, aucune mention de ce qui n'existe plus.
- **Vérifiés un par un, non impactés** : `services-standard.md` et `composables-standard.md`
  n'inventorient aucun service et la carte **applique** leur règle plutôt que de la changer ;
  `arborescence-entreprise.md` ne descend pas jusqu'aux composants ;
  `inventaire-criteres-3-couches.md` traite de la donnée disponible, pas de son affichage ;
  `i18n.md` porte la contrainte RTL sans lister les clés.

## Définition de terminé

- [x] Aucun `revealedCount` dans `webapp/` ; `isRevealed` et l'export sont intacts.
- [x] Le bloc affiche **toutes** les facettes d'une recherche née du langage naturel comme les trois
      d'une recherche par critères, vérifié sur les deux.
- [x] La valeur du profil apparaît en face du critère demandé quand la carte la porte —
      « Études · Licence · Master » sur le profil de la capture d'origine.
- [x] La barre haute affiche « 1 an et plus » et « Licence » ; le nom automatique en fait autant.
      Le cadrage écrivait « ≥ 1 an » ; c'est la formulation du **formulaire** qui a été reprise, le
      recruteur ayant choisi sa borne sous ces mots-là.
- [x] Les pastilles `✓` ont quitté `ProfileCard` ; les compétences trouvées y restent en gras accent.
- [x] La barre de progression mesure les profils **vus** et non la position dans la pile — le
      cadrage disait « recule quand on en saute », ce qui décrivait l'écart au rang, pas la barre :
      elle ne décroît que lorsque des profils quittent le jeu.
- [x] `criteria.vitest.ts` couvre chaque famille de facette, l'énumération traduite, la borne
      d'expérience et les facettes sans valeur profil.
- [x] Captures authentifiées, clair et sombre, sur un profil qui coche tout et un qui rate un
      critère.
- [x] Le §5 du cadrage décrit l'écran réel.

## Vérification

**78 tests verts** sur `services/search/` — sortie lue le 2026-08-03 —, dont les **16 nouveaux** de
`criteria.vitest.ts`, qui passe de 17 à 33 cas.

**Le bloc, sur un profil qui rate un critère.** Recherche
« développeur · 8 ans et plus · Doctorat · vue · postgres », captures authentifiées dans les deux
thèmes :

| Ligne | Demandé | Ce profil |
| --- | --- | --- |
| ✓ Poste | développeur | Développeur logiciel *(résolu par le dictionnaire de concepts)* |
| ✓ Expérience | 8 ans et plus | 23 ans |
| ✓ Études | Doctorat | Trouvé *(identique, non répété)* |
| ✓ vue | | Vue.js |
| ✗ postgres | | Non trouvé |

**Le cas qui a ouvert la task**, sur la recherche `1 · LICENCE · vue` : « ✓ Études · **Licence** ·
Master ». Le critère demandait Licence, le profil est Master, et l'écran le dit.

**La barre de progression, mesurée dans le DOM** et non jugée à l'œil : `width: 48%` et
`aria-valuenow="48"` pour 13 profils vus sur 27. L'ancienne barre, adossée à la position, affichait
4 % au même instant.

**La ligne d'avancement à trois mentions** — « 13 vus · 14 à voir · 2 écartés » — constatée après
avoir écarté deux profils par l'API.

**Le surlignage de la card a survécu au retrait des pastilles.** Sondage du DOM sur un profil dont
un mot-clé est trouvé dans une compétence : `#Revues de presse` et `#Vue.js` portent `text-accent`,
les autres non.

**La barre haute et le nom automatique** disent « 1 an et plus » et « Licence » là où ils affichaient
`1` et `LICENCE`.

## Livré

- `webapp/services/search/criteria.ts` **(M)** — `describeCriterion`, `criterionText`,
  `confrontCriteria` et `renderValue` : la couche pure des libellés et de la confrontation.
- `webapp/services/search/criteria.vitest.ts` **(M)** — 16 cas de plus.
- `webapp/spaces/entreprise/app/components/search/MatchList.vue` **(+)** — le bloc lui-même.
- `webapp/spaces/entreprise/app/components/search/ProgressHeader.vue` **(M)** — les compteurs se
  replient, « dévoilés » disparaît, la barre change de mesure.
- `webapp/spaces/entreprise/app/components/search/ControlPanel.vue` **(M)** — elle place le bloc.
- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` **(M)** — les pastilles partent.
- `webapp/spaces/entreprise/app/components/search/CriteriaBar.vue` **(M)** — puces lisibles.
- `webapp/spaces/entreprise/app/components/search/Card.vue` **(M)** — titre et puces alignés.
- `webapp/spaces/entreprise/app/composables/useSearch.ts` **(M)** — `revealedCount` disparaît.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` **(M)** — elle confronte et
  mesure l'avancement sur les vus.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` **(M)** — `displayName` traduit.
- `webapp/shared/types/candidate-card.ts` **(M)** — `matchedOn` devient optionnel.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — libellés de facette, valeurs de critère et
  vocabulaire du panneau.
- `dev-book/cadrage/entreprise/ui/vue_la_visionneuse.md` **(M)** — §2, §5 et §8.

## Écart au cadrage

Quatre décisions ont été prises à l'implémentation, sur des points que le cadrage n'avait pas vus.

### Une valeur de profil identique à celle demandée ne se répète pas

Constaté à l'écran sur la recherche « Finance France vers Casablanca » :
« Disponibilité · Disponible immédiatement · Disponible immédiatement » remplissait la largeur d'une
colonne de 280 px pour n'apprendre rien, et « Secteur visé · Finance · Finance » faisait de même.

Quand la valeur du profil est identique à celle demandée, elle n'est pas rendue : le verdict prend
sa place. Les cas utiles — « Licence » demandé, « Master » trouvé — ne sont pas touchés, les deux
valeurs y étant différentes.

### `matchedOn` est OMIS par le serveur, pas mis à `null`

Le cadrage avait lu la bonne règle dans `CriterionMatcher::one` — pas de valeur de profil sur un
critère échoué — mais pas sa **forme sur le fil** : le sérialiseur retire la clé au lieu de
l'envoyer vide. Le miroir manuel `shared/types/candidate-card.ts` déclarait `matchedOn: string |
null`, et la garde `null !== matchedOn` laissait donc passer l'absence.

À l'écran, la ligne d'un mot-clé non trouvé affichait une valeur **blanche** au lieu de son verdict.
Le type devient optionnel, ses deux lecteurs sont alignés, et un test tient la règle — le genre de
défaut qu'aucune gate ne voit, puisque le type mentait dans le sens qui compile.

### La liste des recherches suit aussi les libellés

`search/Card.vue` affichait ses critères en valeurs brutes. Elle devait de toute façon changer, son
titre passant par `displayName` ; la laisser en l'état aurait mis « Licence » dans son titre et
`LICENCE` dans ses puces, **à l'intérieur de la même carte**. C'est le défaut que la décision
« les libellés descendent dans une couche pure » vient corriger, pas un élargissement de périmètre.

### Le schéma d'anatomie du cadrage a bougé avec le §5

Le cadrage limitait la mise à jour au §5 et au §8. Le **§2** dessine pourtant le panneau, compteurs
compris : le laisser intact aurait fait dire à la page deux choses opposées à trois paragraphes
d'écart. Seule la colonne de droite du schéma est reprise ; le vocabulaire « shortlist » et
« Télécharger la SL », lui, reste tel quel — c'est la dérive explicitement écartée.

## Aval

- **La Visionneuse en mobile** : le panneau n'existe pas sous `lg`, et avec lui disparaissent les
  compteurs, les favoris et le seul chemin vers la Table depuis cet écran.
- **La dérive « shortlist » du cadrage entreprise** — la page nomme encore un objet et un bouton que
  l'écran n'a plus.
