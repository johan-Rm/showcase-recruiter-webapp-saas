# TASK ENT-088 — entreprise : la colonne du verdict dit d'abord le verdict

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — la colonne s'ouvre sur un anneau et un palier, les deux gestes
  prennent leur hiérarchie, les critères deviennent des lignes à deux étages sans rouge, une note
  explique les manques, et les pastilles d'état descendent de la fiche au pied de la colonne.
- **Type** : refonte d'interface. Aucune donnée nouvelle : tout se calcule sur ce que la fiche
  porte déjà.
- **Tests** : unitaires sur `services/search/` — le score et le choix du palier, avec leurs bascules
  (aucun critère, rien de trouvé, tout trouvé, le tiers, les deux tiers). Le reste est de
  l'affichage, prouvé par capture.
- **Réf** : maquette fournie en conversation le 2026-08-11 · `ENT-076` — qui a sorti le verdict de
  la fiche · `ENT-082` — les deux gestes, **partiellement défaite** · `ENT-083` — les cartouches et
  l'infobulle, **partiellement défaite** · `ENT-047` — le rouge du verdict, **défait** · `ENT-086` —
  le nom retiré de la colonne · aval : une carte **lane API** pour le compte d'occurrences.

## Contexte

Un recruteur qui parcourt des profils décide vite, et la colonne de droite ne lui donne qu'une liste
plate : six lignes, aucune synthèse. Rien ne dit d'un coup d'œil « ce profil colle » ou « ce profil
colle mal » — il faut lire les six lignes et faire le compte de tête, profil après profil, plusieurs
centaines de fois.

La colonne dit **le détail** avant **le verdict**. La maquette inverse l'ordre : le verdict d'abord,
le détail ensuite, la nuance en bas.

## Périmètre

- **Un en-tête de synthèse** : un anneau portant « 3/6 », un palier nommé, et une phrase.
- **Les deux gestes redessinés** : *Retenir* en aplat vert pleine largeur, *Écarter* en icône seule.
- **Les lignes de critères refondues** : deux étages, fond neutre, la couleur réduite au rond et au
  mot du verdict.
- **Le rouge devient gris** sur les critères non trouvés.
- **Une note** expliquant que les manques portent sur le CV public.
- **Les pastilles d'état descendent** du pied de la fiche au pied de la colonne.
- **La colonne passe de 300 à 360 px** de largeur minimale.

## Hors périmètre

- **Le compte d'occurrences** (« Mot-clé · 4 occurrences »). Le contrat serveur ne le transporte
  pas — voir *Décisions* ci-dessous. **Nommé, pas fait** : c'est la lane API.
- **La fiche du profil**, sauf les pastilles qui la quittent. Ni son portrait, ni son parcours, ni
  ses compétences ne bougent.
- **La Table**, qui affiche les mêmes états par un autre chemin.
- **Les deux archives** `search-v1/` et `search-v2/`, qui gardent le dessin d'avant.

## Décisions de cadrage

### Le compte d'occurrences ne se fait pas ici — et il est possible

Vérifié dans `api/src/Domain/Search/CriterionMatcher.php` : la recherche d'un mot-clé parcourt
l'accroche du candidat puis sa liste de compétences, **et s'arrête au premier trouvé**. Compter au
lieu de s'arrêter revient à retirer un `return` — la liste est déjà en mémoire, aucune requête
supplémentaire.

Deux réserves nourriront la carte API :

- **Ce ne sont pas des occurrences dans un CV.** Le serveur ne lit ni le CV, ni les descriptions de
  poste. Le chiffre honnête est « trouvé dans 4 **compétences** ».
- **Le front ne peut pas le calculer.** La fiche ne transporte que les douze premières compétences
  (`CandidateMapper::CARD_SKILLS_LIMIT`) : un profil qui en a trente serait sous-compté en silence.

**Arbitré** : cette carte avance sans le compte. La deuxième ligne dit la nature du critère —
« Mot-clé », « Poste recherché » — et le compte se glissera derrière un point le jour où l'API le
portera, sans rien redessiner.

### Le score compte ce que le serveur a jugé satisfait, poste passé compris

**Arbitré.** Sur la maquette, « 3/6 » compte trois critères alors que deux seulement portent une
coche verte : le troisième est un poste **passé**. Le candidat a exercé ce métier, le serveur a
retenu le profil dessus — le score dit donc la même chose que le tri. Le tiret gris ne dit pas
« non », il dit « plus maintenant ».

Conséquence assumée : une ligne peut compter dans le score sans porter de coche.

### Trois paliers, au tiers et aux deux tiers

**Arbitré.** Faible en dessous d'un tiers des critères, moyenne jusqu'aux deux tiers, forte au-delà.
Trois mots situent sans prétendre à une précision que six critères ne portent pas — un découpage en
quarts rendrait deux paliers inatteignables sur une recherche à trois critères.

### Les deux gestes : la maquette l'emporte sur deux décisions écrites

**Arbitré, et c'est un retour en arrière assumé sur deux points.**

- **`ENT-082` disait « Écarter se nomme, il ne se devine pas ».** Il redevient une icône seule. Ce
  qui rend le retour acceptable : le geste est réversible depuis la colonne d'en face, et l'aplat de
  *Retenir* à côté de lui rend la hiérarchie évidente — un geste majeur, un geste mineur.
- **`ENT-076` réservait l'aplat vert à la sortie vers la Table**, dans la colonne d'en face.
  *Retenir* le prend. La sortie vers la Table conserve sa forme ; c'est la **place** qui les
  distingue désormais, plus la variante.

### Les lignes redeviennent neutres, et le rouge disparaît

**Arbitré.** `ENT-083` teintait la cartouche entière au verdict, à dix pour cent. La maquette pose un
fond neutre et ne garde la couleur que sur le rond et le mot — c'est un **retour à la règle de la
charte**, qui réserve la couleur aux états et l'interdit sur les données.

Le rouge de `--verdict-missed` quitte la colonne : le gris le remplace. Rien n'a échoué, le profil ne
coche simplement pas la case — et la note posée juste en dessous dit précisément que l'absence peut
tenir au CV et non au candidat. Le vert reste seul à signaler, donc il signale mieux.

### L'infobulle de `ENT-083` survit

Le nom du critère garde son souligné pointillé et son infobulle quand le profil a reconnu autre
chose que le mot cherché. C'est la seule façon de lire un libellé de quatre-vingt-dix caractères sans
qu'il mange la colonne, et la deuxième ligne est prise par la nature du critère.

### Les pastilles déménagent, elles ne se dupliquent pas

**Arbitré.** Elles quittent le pied de la fiche (`ProfileCard`, bloc ④) pour le pied de la colonne.
Un seul endroit : le même attribut écrit deux fois sur un même écran est une redite, pas un rappel.

En repli sous 1280 px la colonne passe **sous** la fiche : les pastilles restent donc en bas de
l'écran, à peu près là où elles sont aujourd'hui.

### Seule la liste défile

L'en-tête et les deux gestes restent collés en haut, la note et les pastilles collées en bas, et la
liste du milieu défile. Le geste *Retenir* ne change jamais de place, même sur une recherche à douze
critères — c'est déjà le fonctionnement de la liste, il s'étend au reste.

### La note ne paraît que quand il manque quelque chose

**Arbitré.** Elle apparaît dès qu'un critère au moins n'est pas trouvé, et disparaît sur un profil
qui coche tout : elle répond à une question qu'on ne se pose que dans ce cas. Contrepartie acceptée :
la colonne change de hauteur d'un profil à l'autre.

### La colonne passe à 360 px

**Arbitré.** À 300 px, « méthodes agiles » plus un rond à gauche plus « Non trouvé » à droite passent
tout juste, et « Poste passé » déborde. Soixante pixels de plus libèrent la lecture ; le portrait les
rend sans les regretter. La largeur de la maquette (420 px) serrerait le portrait à son tour sur un
écran de 1280.

### Le score vit dans les services, pas dans le composant

**Arbitré.** Le calcul du score et le choix du palier partent dans `services/search/`, où vit déjà la
confrontation des critères, avec leurs tests. Le composant n'affiche plus qu'un résultat — et une
bascule de seuil ne peut plus casser sans que rien ne le signale.

### ⚠️ Écarts constatés à l'implémentation

Quatre points que le cadrage n'avait pas vus, tranchés en écrivant et repris ici pour que la carte ne
mente pas.

- **Le drapeau `detached` disparaît.** `ENT-086` l'avait réduit à une seule fonction : tirer la barre
  des deux gestes contre le bord haut du bloc. L'en-tête de score passe devant eux — il n'y a plus de
  bord à rejoindre, et le drapeau n'a plus d'objet.
- **La fiche cesse de s'écraser** (`shrink-0`). En repli sous 1280 px, le verdict gagne l'anneau, la
  note et les pastilles : la fiche ne tient plus dans un écran. Enfant souple d'une pile à hauteur
  fixe, elle passait alors sous la hauteur de son contenu, et son corps DÉBORDAIT par-dessus le
  verdict — constaté en capture, à 1150 × 1000. Elle grandit désormais avec son contenu, et une fiche
  se lit en deux temps sur un écran court.
- **Le palier descend au corps du texte.** « Correspondance moyenne » est le plus long des trois, et
  au corps d'un titre il se coupait en « Correspondance moye… » — le mot qui porte tout le sens.
- **Un test existant est réécrit.** Il verrouillait la règle inverse : « `q` n'est pas libellé ». La
  règle a changé de contexte — la nature se lit sur sa propre ligne — et le test dit maintenant ce
  qui est vrai.

### ⚠️ Un blocage sorti de cette carte — `ENT-089`

Le premier redémarrage du serveur de développement depuis `ENT-082` a mis **toutes les pages des
quatre espaces en erreur 500**. La cause n'est pas cette carte : `webapp/base/app/app.config.ts` n'est
pas transformé dans une layer, et son `defineAppConfig` part en erreur au démarrage. Le rechargement
à chaud l'avait masqué pendant des heures.

Il fallait le corriger pour prouver quoi que ce soit ici. Le correctif a **sa propre carte et son
propre commit** — `ENT-089` —, et il ne touche aucun fichier de celle-ci.

## Règles et cas limites

- **Recherche d'un collègue** (`editable` faux) : pas de gestes, mais l'en-tête, les lignes, la note
  et les pastilles restent — on lit le verdict, on n'y tranche pas.
- **Aucun profil à confronter** (`matched` vaut `null`) : **pas d'anneau ni de note**. Il n'y a rien
  à compter, et « 0/6 » se lirait comme un mauvais score plutôt que comme une absence. Les critères
  restent listés, sans rond.
- **Recherche sans critère** : la colonne ne rend rien, comme aujourd'hui.
- **Aucun état** sur le profil : le pied de colonne reste vide et ne réserve pas de hauteur — un
  badge nomme un attribut acquis, jamais son absence.
- **Tous les critères trouvés** : palier fort, pas de note.
- **Le repli sous 1280 px** : le même composant, en pied de fiche, avec son en-tête et ses pastilles.
- **Un libellé long** se coupe sur le nom du critère, jamais sur le verdict qui ferme la ligne.

## Contraintes transverses

- **Accessibilité** : l'anneau ne doit pas porter son sens par la seule couleur — le chiffre et le
  palier sont écrits. Le gris du « non trouvé » supprime la dernière distinction chromatique entre
  les deux verdicts, ce qui **améliore** la situation pour qui distingue mal les couleurs, le rond et
  le mot restant.
- **i18n** : nouvelles clés — le palier (trois valeurs), la phrase de l'en-tête, la note, et les
  natures de critère. Elles vivent dans `webapp/base/i18n/locales/{fr,en,ar}.yaml`, **partagés avec
  les autres sessions** : staging chirurgical au commit.
- **SSR** : l'anneau se rend côté serveur, aucune mesure de DOM.
- **Mobile-first** : le repli en pied de fiche est le cas nominal sous 1280 px, pas une dégradation.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni convention de dossier. Le README de
`webapp/services/` gouverne le dossier qui reçoit le calcul du score — il est à **relire avant
d'écrire**, pas à modifier.

## Dépendances

- **Amont** : aucune. Tout se calcule sur le contrat actuel.
- **Aval** : une carte **lane API** — faire compter les compétences reconnues par `CriterionMatcher`
  et les exposer dans `CriterionMatch`, son DTO de sortie, `openapi.json` et les types TypeScript.
  Elle n'est **pas écrite ici** : une carte d'une autre lane, écrite depuis celle-ci, atterrirait sur
  `develop` sans le code qu'elle décrit.

## Vérification

### La colonne rendue, sur le profil de la maquette

Recherche « Product Owner · scrum », profil Mohammed Ziati, à 1920 px :

```
anneau                          présent
chiffre                         3/6
palier                          Correspondance moyenne
phrase                          3 critères sur 6 retrouvés dans le profil.
boutons                         Retenir 306 px (aplat) + icône seule 28 px
note                            présente
pastilles dans la colonne       (aucune sur ce profil)
largeur de la colonne           378 px   (plancher porté de 300 à 360)
```

Les six lignes, telles que le DOM les rend — critère, verdict, nature :

```
Product Owner       | Poste passé | Poste recherché
scrum               | Non trouvé  | Mot-clé
jira                | Trouvé      | Mot-clé
confluence          | Trouvé      | Mot-clé
méthodes agiles     | Non trouvé  | Mot-clé
gestion de produit  | Non trouvé  | Mot-clé
```

Le score compte trois satisfaits pour deux coches vertes : le poste passé compte, et il porte un
tiret. C'est la décision de cadrage, vérifiée à l'écran.

### La note ne paraît que là où elle a un objet

```
profil à 3 sur 6   note présente
profil à 6 sur 6   note absente
```

### Les pastilles ont bien DÉMÉNAGÉ

```
pastilles dans la colonne du verdict   Favori
pastilles restées dans la fiche        0
```

Compté hors verdict, la fiche portant encore le bloc replié sous `xl`.

### Le repli en pied de fiche, à 1150 × 1000

```
anneau              présent
boutons             2
pastilles           Favori
débordement gauche  18 px   ← la marge intérieure de la fiche, respectée
```

⚠️ **Défaut trouvé ici et corrigé** : avant `shrink-0`, la fiche était écrasée à la hauteur d'un
écran et son corps débordait PAR-DESSUS le verdict — le portrait, les coordonnées et les compétences
se superposaient à l'anneau et au bouton *Retenir*. Mesuré : corps jusqu'à 548 px, verdict à partir
de 566 px après correction, contre un chevauchement franc avant.

### Les deux thèmes

```
sombre   aplat Retenir  le lime #c6e76b        rond satisfait  vert verdict
clair    aplat Retenir  le vert Cèdre #0f4b37  rond satisfait  vert verdict
```

### Les tests

```
$ bunx vitest run services/search/criteria.vitest.ts
  Test Files  1 passed (1)
       Tests  62 passed (62)
```

Cinq cas neufs sur `scoreOf` — aucun critère, aucun profil à confronter, le poste passé qui compte,
les bascules au tiers et aux deux tiers, les deux nombres rendus tels quels. Un test existant a été
réécrit : il verrouillait la règle que cette carte change.

### Les conventions

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/Score.vue` | (+) l'anneau, le palier et la phrase |
| `spaces/entreprise/app/components/search/MatchList.vue` | (M) lignes à deux étages, fond neutre, note conditionnelle |
| `spaces/entreprise/app/components/search/Verdict.vue` | (M) l'ordre du bloc, les deux gestes, les pastilles en pied |
| `spaces/entreprise/app/components/search/ProfileCard.vue` | (M) les pastilles partent, la fiche cesse de s'écraser |
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) colonne à 360 px, drapeau `detached` retiré |
| `services/search/criteria.ts` | (M) `scoreOf` et ses paliers ; `q` prend un libellé de nature |
| `services/search/criteria.vitest.ts` | (M) cinq cas sur `scoreOf`, un test réaligné |
| `base/i18n/locales/{fr,en,ar}.yaml` | (M) paliers, phrase du score, note, « Mot-clé » |

## Reste à faire (hors périmètre)

- **Le compte d'occurrences** — carte de la lane API, non écrite ici. Voir *Dépendances*.
- **La Table** montre les mêmes états par un autre chemin et n'a pas été touchée.

## Risques et reports assumés

- **Trois décisions écrites sont défaites** (`ENT-082`, `ENT-076`, `ENT-047`/`ENT-083`). Elles sont
  nommées ci-dessus avec leur motif d'origine : si l'usage donne tort à ce retour, on saura ce qu'on
  avait cru.
- **Le pied de la fiche se vide** de ses pastilles. Sur un profil sans compétences ni citation, la
  colonne de gauche peut sembler courte.
- **La colonne change de hauteur** d'un profil à l'autre, la note étant conditionnelle.
- **Le composant grossit** : en-tête, gestes, liste, note, pastilles. Le README de `components/`
  plafonne à ~200 lignes — un découpage sera probablement nécessaire, et il fait partie de la tâche.

## Définition de terminé

- [x] L'en-tête montre l'anneau, le palier nommé et la phrase du décompte.
- [x] Le score compte les critères satisfaits, poste passé inclus.
- [x] Les paliers basculent au tiers et aux deux tiers, et leurs tests le verrouillent.
- [x] *Retenir* est un aplat vert pleine largeur, *Écarter* une icône seule.
- [x] Chaque ligne porte son rond, son nom, sa nature en dessous, son verdict à droite.
- [x] Le rouge a disparu de la colonne.
- [x] La note ne paraît que lorsqu'un critère au moins manque.
- [x] Les pastilles d'état sont au pied de la colonne et **plus** au pied de la fiche.
- [x] Seule la liste des critères défile.
- [x] La colonne ne descend plus sous 360 px.
- [x] L'infobulle du libellé reconnu fonctionne toujours.
