# TASK ENT-107 — entreprise : un geste pose toutes les propositions affichées

- **Créée le** : 2026-08-13.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-13 — un bouton annonçant son nombre pose d'un coup toutes les
  propositions restantes, laisse le panneau ouvert, annonce le résultat aux lecteurs d'écran et rend
  la main au champ de recherche ; il est atteignable au clavier, et la règle qui calcule ce qui reste
  à prendre vit dans une fonction pure couverte par huit tests.
- **Type** : Frontend entreprise — nouveau geste dans un composant du socle partagé, retouché *pour*
  l'espace entreprise. Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : **fonction pure + vitest**. La règle « ce qui reste à prendre » sort du composant dans
  `webapp/shared/utils/`, où le banc existant la couvre sans runtime : liste vide, tout déjà retenu,
  doublons dans l'entrée, ordre d'affichage conservé. Le geste lui-même (le clic, le focus, la phrase
  annoncée) n'a pas de banc — le webapp ne sait pas rendre un composant — et se prouve par capture.
- **Réf** : `webapp/base/app/components/ui/TagPicker.vue` ·
  `webapp/spaces/entreprise/app/components/search/Sentence.vue` · `webapp/shared/utils/` ·
  `webapp/base/i18n/locales/{fr,en,ar}.yaml` · amont : `ENT-104`, qui a redessiné ce panneau.

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur **« Nouvelle recherche »** — l'écran
qui sert aussi à retoucher une recherche enregistrée. À droite, la colonne **« Qui maîtrise l'un de
ces mots-clés »** ; le bouton **« + Ajouter un mot-clé »** ouvre un panneau qui vient couvrir la
phrase de recherche.

**Le manque.** Le vocabulaire des compétences n'est pas normalisé : il est recopié tel que les
profils l'écrivent. Taper `vue` remonte donc `vuejs`, `vuej`, `vuex`, `pc vue`,
`modèle-vue-présentation (mvp)` et `frameworks front-end : react, angular, vue.js` — jusqu'à dix
propositions, toutes sous les yeux, chacune demandant son clic. Vouloir en poser sept coûte sept
gestes, dans une liste qu'on vient de lire d'un seul regard.

**Ce que ce geste ne résout pas, et il faut le savoir avant de le construire.** Dans le cas courant,
il ne change aucun résultat. La recherche par mot-clé cherche le texte **n'importe où à l'intérieur**
d'une compétence, alors que les propositions ne remontent que celles où le terme **commence un mot** :
la liste affichée est donc toujours incluse dans ce que le terme brut ramène déjà. Poser les sept
variantes de `vue` rend exactement les mêmes profils que le seul mot `vue`. Choisir une proposition
précise **rétrécit** volontairement — `vuejs` exclut `pc vue` —, et c'est là son intérêt.

**Il reste un cas où le lot apporte quelque chose** : la faute de frappe. Sur `javascrip`, plus aucun
mot ne commence par le terme, le serveur bascule alors sur la ressemblance et propose `javascript` —
une valeur que le texte tapé ne trouvait pas. Le gain de ce geste est donc **la vitesse de saisie**,
pas l'élargissement des résultats.

## Périmètre

- **Un bouton apparaît entre le champ de recherche et la liste**, aligné à la fin de la ligne.
- **Il annonce combien il va poser** : « Ajouter les 7 », avec le compte réel.
- **Il ne compte que ce qui n'est pas déjà retenu**, et n'apparaît qu'à partir de **deux**.
- **Un clic pose toutes ces propositions d'un coup**, sans doublon, dans l'ordre d'affichage.
- **Le panneau reste ouvert** : les lignes passent en coche, on voit ce qu'on vient de poser.
- **Le résultat est annoncé** aux lecteurs d'écran — « 7 mots-clés ajoutés » —, et le focus revient au
  champ de recherche.
- **La règle « ce qui reste à prendre » vit dans une fonction pure** de `webapp/shared/utils/`,
  couverte par des tests.
- **Le libellé entre dans les trois langues**, au pluriel.

## Hors périmètre

- **Aucun geste inverse.** Retirer se fait déjà d'un clic sur la croix de chaque étiquette, et le
  besoin de retirer en masse n'est pas constaté. Un bouton qui bascule de sens sous le doigt se
  clique par erreur.
- **La ligne « ajouter le terme tel quel » n'entre pas dans le lot.** Elle n'est pas une proposition
  du vocabulaire mais la saisie libre du recruteur ; la poser sans qu'il l'ait choisie serait le
  faire chercher un mot qu'il n'a pas validé.
- **Le plafond de dix propositions**, fixé côté serveur, ne bouge pas. C'est lui qui interdit au
  libellé de promettre « tout » — voir *Décisions de cadrage*.
- **La redondance décrite en contexte** n'est pas traitée ici : elle tient à la façon dont la
  recherche compare les textes, donc au serveur.
- **`FiltersPrimary.vue`**, qui monte le même composant sans être affiché nulle part, n'est pas
  touché : le nouveau libellé lui sera facultatif.

## Décisions de cadrage

**Le libellé porte un nombre plutôt que le mot « tout », arbitré avec l'utilisateur le 2026-08-13.**
Le serveur ne sert que dix propositions au maximum : « tout ajouter » désignerait les dix affichées
et non toutes les variantes du vivier, une promesse que le geste ne tient pas. Un compte réel dit
exactement ce qui va se passer, et il se vérifie d'un coup d'œil sur la liste juste en dessous.

**Le bouton compte ce qui reste à prendre, pas ce qui est affiché.** Sinon il annoncerait sept quand
cinq sont déjà posés, et le recruteur verrait deux étiquettes apparaître pour un bouton qui en
promettait sept.

**Il disparaît en dessous de deux.** À une seule proposition restante, il ne fait gagner aucun geste
et double la ligne qu'il surplombe. Quand tout est pris, il n'a plus d'objet.

**Le panneau reste ouvert après le lot**, comme après un ajout ligne à ligne. C'est ce qui permet de
retirer dans la foulée une proposition indésirable — et sur un geste qui en pose sept d'un coup,
cette correction immédiate vaut plus que sur un geste qui en pose une.

**Le résultat est annoncé à voix haute, arbitré le même jour.** C'est le geste le plus lourd du
panneau et le seul dont l'effet ne se voit pas là où on l'a déclenché : les étiquettes apparaissent
dans une autre colonne, et le bouton disparaît au moment même du clic. Sans phrase annoncée, une
personne au lecteur d'écran entend le bouton, l'active, et n'apprend rien — il faudrait reparcourir
toute la liste pour découvrir ce qui a changé.

**Le bouton vit HORS de la liste.** La liste est déclarée comme une liste d'options ; un bouton
dedans casserait ce contrat et serait annoncé comme une option de plus. Il se pose donc entre le
champ de recherche et la liste, où il reste visuellement rattaché aux propositions qu'il désigne.

**La règle sort en fonction pure, arbitré le même jour.** Le webapp n'a aucun banc capable de rendre
un composant, et deux tâches d'affilée sur ce panneau se sont déjà exemptées de test. Le calcul
« ce qui reste à prendre » — filtrage, dédoublonnage, ordre — est la seule partie qui puisse se
tromper silencieusement ; elle part dans `shared/utils/`, dont c'est le rôle : des helpers purs,
sans framework, auto-importés des deux côtés. Le composant reste sans domaine et peut l'appeler
sans rien enfreindre.

**Le gain assumé est la vitesse de saisie, pas l'élargissement.** L'utilisateur a été prévenu que le
lot ne change pas les résultats dans le cas courant, et a demandé le geste quand même. C'est écrit
ici pour que personne ne redécouvre la redondance dans six mois en croyant à un défaut.

## Règles et cas limites

- **La tabulation doit atteindre le bouton avant de refermer le panneau.** Aujourd'hui, tabuler
  depuis le champ de recherche ferme le panneau — un bouton posé là n'existerait tout simplement pas
  au clavier. Le geste doit être atteignable au clavier comme à la souris.
- **Après le clic, le focus revient au champ de recherche.** Le bouton disparaît au moment où il
  agit, puisqu'il ne reste plus rien à prendre : sans reprise explicite, le focus retombe sur le
  corps de page et le parcours clavier repart du début de l'écran.
- **Aucun doublon, et l'ordre d'affichage est conservé** : les propositions se posent dans l'ordre où
  elles sont lues, et une valeur déjà retenue n'est pas repoussée en fin de liste.
- **Le bouton ne s'affiche pas pendant le chargement, ni en panne, ni sur une liste vide** — il n'y a
  alors rien à prendre.
- **La saisie n'est pas vidée par le lot**, pas plus que par un ajout unitaire : c'est elle qui tient
  la liste à l'écran, et l'effacer ferait disparaître les lignes qui viennent de passer en coche.
- **Les libellés ne portent PAS de forme singulière**, et c'est un écart assumé au cadrage. Celui-ci
  en demandait une par langue ; le bouton n'apparaissant jamais en dessous de deux, cette forme ne
  pourrait jamais s'afficher. L'écrire aurait produit trois textes traduits que rien ne rend —
  exactement le contenu sans lecteur que `ENT-108` vient de retirer du dépôt.
- **Le sens de lecture arabe est respecté** : le bouton suit le bord logique de fin de ligne.
- **Le mode plein écran mobile reçoit le même bouton**, au même endroit.

## Documentation impactée

**Aucune page de `docs/showcase-recruiter-webapp-saas/`** : ni route, ni contrat, ni jeton de design, ni règle métier ne
change. Le `README.md` de `webapp/shared/` décrit le rôle du dossier et ses conventions, pas la liste
de ses fichiers : y ajouter un helper pur conforme ne le modifie pas. Même chose pour
`webapp/base/app/components/README.md`.

## Définition de terminé

- [x] Un bouton apparaît entre le champ de recherche et la liste dès que **deux** propositions au
      moins restent à prendre.
- [x] Son libellé porte le compte réel de ce qu'il va poser, et ce compte ignore les propositions
      déjà retenues.
- [x] Un clic pose toutes ces propositions, sans doublon et dans l'ordre d'affichage.
- [x] Le panneau reste ouvert, les lignes concernées passent en coche.
- [x] Le bouton disparaît une fois qu'il n'y a plus rien à prendre.
- [x] Le focus revient au champ de recherche après le clic.
- [x] Le bouton est atteignable au clavier depuis le champ de recherche.
- [x] Une phrase est annoncée aux lecteurs d'écran, disant combien de mots-clés ont été ajoutés.
- [x] La fonction pure vit dans `webapp/shared/utils/` et ses tests passent : liste vide, tout déjà
      retenu, doublons, ordre conservé.
- [x] Les libellés existent dans les trois langues ; le test de parité passe. **Sans forme
      singulière** — écart assumé, motivé dans les *Règles et cas limites*.
- [x] Rien ne s'affiche pendant le chargement, en panne, ou sur une liste vide.
- [x] Le mode plein écran mobile reçoit le même bouton.
- [x] Captures fournies, avant et après le clic, avec le nombre de mots-clés posés mesuré.

## Vérification

Preuves prises le 2026-08-13 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`, sur
« Nouvelle recherche », terme `vue` — un cas réel où le vocabulaire non normalisé rend neuf
propositions.

**Le geste, à la souris (1440 px)**

| Ce qui est prouvé | Avant le clic | Après |
| --- | --- | --- |
| Le bouton | « **Ajouter les 8** » | **disparu** |
| Est-il dans la liste d'options ? | **non**, et posé au-dessus d'elle | — |
| Propositions affichées | 9 | 9 |
| Dont marquées retenues | 0 | **8** |
| Étiquettes posées dans la colonne | 0 | **8**, dans l'ordre d'affichage |
| Zone d'annonce | présente et **vide** | « **8 mots-clés ajoutés** », `aria-live="polite"` |
| Où revient le focus | — | **le champ de recherche** |

**Huit sur neuf, et c'est la règle** : la neuvième ligne est « Ajouter « vue » », la saisie libre du
recruteur. Elle est restée intacte, hors du lot — conforme au *Hors périmètre*.

**Le geste, au clavier**

| Geste | Ce qui a été observé |
| --- | --- |
| Tabulation depuis le champ | le focus **atteint** le bouton « Ajouter les 8 », le panneau **reste ouvert** |
| Entrée sur le bouton | mêmes huit étiquettes, même annonce |
| Tabulation quand il ne reste rien à prendre | le panneau **se referme**, comportement d'origine |

**Le seuil**, observé sur une liste incomplète : une seule proposition à l'écran, **aucun bouton**.

**Le plein écran mobile (390 × 780)** : le panneau occupe exactement la fenêtre, à l'origine ; le
bouton « Ajouter les 8 » y est visible ; après le clic, huit étiquettes, l'annonce « 8 mots-clés
ajoutés », et le bouton disparu.

**Tests** : `pendingTags` couverte par **8 tests verts** — liste vide, rien de retenu, tout retenu,
doublons dans l'entrée, ordre conservé, retenu-mais-non-proposé, casse distincte. Avec la parité des
langues, **17 verts**. **Conventions** : 19 règles, aucune violation.

Aucune gate complète n'a tourné : elles appartiennent à l'ouverture de la PR.

**Captures** : `ent107-avant-dark.png`, `ent107-avant-light.png`, `ent107-apres.png`,
`ent107-clavier.png`, `ent107-mobile-avant.png`, `ent107-mobile-apres.png`, dans le dossier de
session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

## Livré

- `webapp/shared/utils/tags.ts` — `pendingTags`, qui rend ce qui reste à prendre en dédoublonnant
  contre les termes retenus **et** contre la liste elle-même, dans l'ordre d'affichage.
- `webapp/shared/utils/tags.vitest.ts` — ses huit cas.
- `webapp/base/app/components/ui/TagPicker.vue` — le bouton, posé hors de la liste d'options ; la
  zone d'annonce, rendue vide dès l'ouverture ; la reprise du focus ; et l'exception de tabulation
  qui rend le bouton atteignable au clavier.
- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — passe les deux libellés.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — `addAll` et `addedCount`.

## Reste à faire (hors périmètre)

- **Le lot ne change pas les résultats dans le cas courant**, et c'est écrit en *Contexte* : la
  recherche cherche le terme n'importe où à l'intérieur d'une compétence, alors que les propositions
  ne remontent que celles où il commence un mot. Le gain est la vitesse de saisie. Corriger cet
  écart relève du serveur.
- **Une même compétence peut sortir deux fois** des suggestions — écritures distinctes du même mot.
  `pendingTags` protège des doublons **exacts** ; deux graphies proches restent deux critères. Lane
  `espace/api`.
- **`FiltersPrimary.vue` monte toujours ce composant sans être affiché nulle part.** Il ne reçoit pas
  les nouveaux libellés, donc pas le bouton — ce qui est sans effet, et reste à trancher pour
  lui-même.
