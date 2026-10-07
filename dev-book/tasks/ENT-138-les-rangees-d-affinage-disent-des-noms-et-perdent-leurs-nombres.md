# TASK ENT-138 — les rangées d'affinage disent des noms, et perdent leurs nombres

- **Créée le** : 2026-08-18.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-18 — les rangées Pays et Langue disent des noms capitalisés, les
  quatre rangées sans ordre naturel se rangent par poids décroissant, et plus aucune étiquette du
  panneau ne porte de nombre.
- **Type** : feat — l'affichage des rangées d'affinage change ; aucune règle de filtrage n'est touchée.
- **Tests** : vitest sur la fonction pure de nommage (`webapp/services/search/labels.vitest.ts`) — les
  trois langues, un code inconnu qui retombe sur lui-même — et sur l'ordre figé par nombre décroissant
  dans `refine.vitest.ts`. **Aucun test de rendu** : le dépôt n'en a aucun sur ses composants, et
  s'auto-exempter n'est pas en jeu — la logique déplacée est justement celle qui devient testable.
- **Réf** : amont `ENT-110` (le panneau), `ENT-114` (les comptes sur les étiquettes, que celle-ci
  retire), `ENT-123` (Pays et Langue sortis du volet fourre-tout) · aval : la casse des secteurs, non
  cartée · voisin : `webapp/base/app/composables/useCountries.ts`, qui porte déjà la mécanique de
  nommage.

## Contexte

Un recruteur, espace entreprise, écran de parcours des profils (« Visionneuse »), **colonne de
gauche** — le panneau qui resserre ce qu'on regarde sans toucher aux critères de la recherche. Tout en
bas, sous les volets repliés, deux rangées d'étiquettes cliquables : **Pays** et **Langue**.

Elles affichent les abréviations de la base, telles quelles, chacune suivie d'un nombre :

```
Pays      BE 9    FR 58
Langue    ar 31   de 2   en 46   es 4   fr 53   nl 2   ru 1   sv 1   tr 1
```

Quatre défauts, constatés à l'écran :

1. **Personne ne lit ces codes.** « he » veut dire hébreu, « zh » chinois. Rien à l'écran ne le dit.
2. **Deux façons d'écrire se touchent** — capitales pour les pays, minuscules pour les langues.
3. **L'ordre vient du code, que le lecteur ne voit pas.** La valeur de loin la plus fournie se
   retrouve enterrée au milieu d'une rangée, entre deux valeurs à un profil.
4. **Autant de nombres que d'étiquettes** dans une colonne étroite : jusqu'à seize sur la rangée des
   langues, soit trente-deux choses à lire là où on cherche un mot.

Le code justifie aujourd'hui les codes bruts ainsi : *« les traduire supposerait un catalogue qui
suivrait chaque valeur nouvelle, et ce serait le premier à dériver »*. **C'est faux, et deux fois** :
l'exécution connaît déjà ces codes dans les trois langues de l'interface, et le dépôt s'en sert déjà
ailleurs pour la liste des pays de l'inscription. Il n'y a aucun catalogue à tenir.

## Périmètre

- **Une fonction pure de nommage** dans `webapp/services/search/` : elle reçoit un code et une langue,
  elle rend un nom. Deux entrées — pays et langue.
- **Les rangées Pays et Langue affichent ce nom** au lieu du code. La valeur stockée et envoyée au
  filtre reste le code : seul le libellé change.
- **Le nom porte une capitale initiale**, comme toutes les options du panneau.
- **Le nombre disparaît de toutes les étiquettes**, sans exception et sans retour au survol.
- **L'ordre des rangées sans ordre naturel** — villes, pays, langues, secteurs — passe au **nombre
  décroissant**, calculé sur le jeu entier et figé.
- **Une étiquette qui ne mène à aucun profil reste affichée, éteinte et muette** : ni nombre, ni
  mention.

## Hors périmètre

- **Le compteur de la barre haute ne bouge pas.** Il suit déjà l'affinage — c'est lui qui porte
  désormais tout le retour, et il n'y a rien à y faire.
- **Les secteurs restent en capitales.** Ils arrivent bruts de la source, aucun mécanisme d'exécution
  ne les traduit, et une table écrite à la main serait exactement ce qu'on évite ici. Leur ordre, lui,
  passe au nombre décroissant comme les autres rangées sans ordre naturel.
- **Présence, projet de retour et disponibilité gardent leur ordre d'échelle.** « moins de 3 mois »
  avant « 1 an » dit quelque chose ; le nombre n'a pas à le défaire.
- **Aucun changement de contrat d'API, de modèle de données, de rôle ni de permission.** Le filtrage
  rend exactement les mêmes profils qu'avant, sur les mêmes valeurs.
- **Aucune clé de langue ajoutée** — voir les décisions ci-dessous.
- **Le panneau reste non rendu sous 1024 px** — dette antérieure, hors sujet ici.

## Décisions de cadrage

### Des noms plutôt que des capitales harmonisées

La demande initiale était d'harmoniser la casse — tout en capitales. **Écarté** : `FR` le pays et `FR`
la langue seraient devenus la même chaîne à trois lignes d'écart, avec deux nombres différents.
Aujourd'hui la casse est la seule chose qui les distingue. Les vrais noms coûtent autant à écrire et
suppriment la collision (« France » n'est pas « Français ») **et** la question de la casse.

Second motif : « tout en capitales » n'aurait pas harmonisé le panneau, qui porte aussi des villes en
toutes lettres et des secteurs déjà en capitales.

### Le nommage est une fonction pure, pas un composable

Le dépôt a déjà `useCountries`, qui va chercher la langue tout seul. **Écarté pour ici** : le README de
`services/` exige une couche sans dépendance au framework, la fonction se range à côté de `refine.ts`
qui alimente ces mêmes étiquettes, et surtout **elle devient testable** — le dépôt n'a aucun test de
composable ni de composant. Le composant lui passe la langue.

`useCountries` garde son rôle : construire une liste de 249 entrées pour un formulaire. Ici on nomme
une poignée de codes déjà connus. Deux besoins, deux fonctions.

### Les nombres partent entièrement, parce que le retour existe déjà ailleurs

C'est le point qui a tranché, et il a été **vérifié dans le code** avant de décider. Le rang de la
barre haute — « 1 / 183 » — bascule sur le jeu affiné dès qu'un filtre est posé :

```js
// LE RANG SUIT L'AFFINAGE, l'avancement non.
stackTotal = isRefined(refineForm) ? visible.length : total
```

Il devient donc « 1 / 7 », et il dit **mieux** que les étiquettes ce qu'on veut savoir : ce qui reste
après **tous** les filtres combinés, quand chaque étiquette n'annonçait que son effet isolé.

Deux pistes envisagées tombent avec ce constat :

- **le nombre au survol** — inutile, puisqu'il ne manque plus ;
- **le nombre conservé dans le nom lu à voix haute** — inutile aussi : le rang de la barre haute est
  du texte visible, donc lu comme le reste. Tout le monde a la même information au même endroit, et
  c'est une clé de langue en moins à tenir.

### L'ordre par nombre, et figé

Une fois les nombres retirés, **l'ordre est le seul repère qui reste** pour savoir où est la matière.
Rangées par nom, `Français` (53 profils) et `Suédois` (1) se ressemblent ; le recruteur découvre le
tri après avoir cliqué.

**IL NE SUIT PAS LES FILTRES POSÉS**, et c'est ce qui rend la décision tenable : le poids qui ordonne
est celui du jeu, jamais celui de l'affinage en cours. Sinon les étiquettes se réordonneraient sous le
curseur à chaque clic.

Il n'est pas figé pour autant, et la formulation initiale de cette carte le disait mal : le jeu se
remplit en arrière-plan, donc l'ordre **se stabilise pendant le chargement**. Constaté à l'écran — de
treize à vingt-quatre étiquettes en trente secondes, l'ordre bougeant avec. Le rendu du serveur et
l'état stabilisé du navigateur diffèrent donc, comme différait déjà la LISTE elle-même avant cette
tâche : ce n'est pas un défaut introduit ici, et le décalage d'hydratation qu'on observe préexiste
— mesuré des deux côtés, avec et sans ce changement.

### La capitale initiale, tranchée par le panneau et non par la langue

Un nom de langue s'écrit en minuscule en français — « français », « anglais » —, quand un nom de pays
est un nom propre. Rendus tels quels, les deux registres se seraient touchés dans la même colonne,
c'est-à-dire le défaut même que ce nommage vient corriger.

La règle vient du panneau : **toutes ses options commencent déjà par une capitale** — « Maintenant »,
« Sous 15 j », « Immédiate », « En réflexion ». Un « français » bas y serait le seul libellé de ce
genre. La capitale est donc posée à l'affichage, dans la langue lue.

**Elle ne s'applique qu'à un NOM.** Faute de nomenclature, l'exécution rend le code lui-même : le
capitaliser produirait un « Zz » qui n'est plus le code et n'est pas davantage un nom. Défaut trouvé
par les tests, pas à l'écran.

### Une étiquette morte reste, plutôt que de disparaître

**Écarté — la faire disparaître** : la rangée changerait de composition à chaque affinage, des
étiquettes s'en allant et revenant sous le curseur, et l'on perdrait l'information « cette valeur
existe dans la recherche, mais pas avec les filtres posés ».

**Écarté — lui laisser son « 0 »** : ce serait le seul chiffre survivant du panneau, à expliquer.

Elle reste donc éteinte et muette. La rangée ne bouge jamais.

## Règles et cas limites

- **Un code que l'exécution ne connaît pas retombe sur lui-même**, à l'identique et sans capitale.
  Une valeur inattendue s'affiche donc comme aujourd'hui : dégradation, jamais d'erreur.
- **Deux valeurs à nombre égal se départagent par le nom**, sinon l'ordre varierait d'un rendu à
  l'autre — et un rendu serveur qui diffère du rendu client se voit.
- **L'ordre se calcule sur le jeu entier**, ce que la propriété `cards` garantit déjà par contrat
  (« le jeu ENTIER, pas la page courante »).
- **Le calcul des nombres ne disparaît pas avec leur affichage** : il décide encore de quelles
  étiquettes s'éteignent. Cette tâche n'allège donc ni le code ni le chargement — c'est une décision
  de lisibilité, et rien d'autre.
- **Une rangée dont le jeu ne porte aucune valeur ne s'affiche pas** — comportement actuel, conservé.
- **Trois langues d'interface, dont l'arabe qui se lit de droite à gauche** : les noms arrivent
  localisés par l'exécution, rien de particulier à prévoir.
- **La valeur envoyée au filtre reste le code.** Une adresse ou un filtre posé avant ce changement
  continue de fonctionner.
- **Les libellés s'allongent** — « Royaume-Uni », « Néerlandais » dans une colonne étroite. Le retrait
  des nombres libère précisément la place qu'ils prennent.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` — la ligne qui situe les filtres
  d'affinage dans la barre haute est fausse depuis `ENT-110`.
- **Pas de changement à `services-standard.md`** : c'est un standard, pas un inventaire, et aucune de
  ses conventions ne bouge.
- **Pas de changement à `dev-book/cadrage/`** : il suit son propre rythme.

## Vérification

Capture du panneau sur « Preuve ENT-113 » (641 profils, 7 pays), compte `owner@demo.showcase-recruiter-webapp-saas.example`,
stack du worktree, thème sombre :

```
Pays     France · Belgique · Maroc · Canada · Espagne · Luxembourg · Qatar
Langue   Français · Anglais · Arabe · Espagnol · Néerlandais · Allemand · Italien ·
         Portugais · Coréen · Russe · Roumain · Chinois · Danois · Hindi · Grec ·
         Japonais · Turc
```

Aucun nombre sur aucune étiquette, le plus porté d'abord dans les deux rangées, capitale initiale
partout comme sur les options voisines du panneau.

**Le serveur nomme comme le navigateur.** Le HTML rendu par le serveur écrit les mêmes noms — relu
directement dans la réponse, pas déduit. Les deux moteurs partagent la nomenclature.

**Le décalage d'hydratation signalé par le navigateur PRÉEXISTE.** Les quatre fichiers modifiés ont
été remisés et la mesure rejouée sur le code d'avant : le même message apparaît à l'identique. Il
appartient au chargement progressif, pas à cette tâche.

**56 tests verts** — 12 de nommage, 44 d'affinage, joués depuis `webapp/`.

## Livré

- `webapp/services/search/labels.ts` **(+)** — le nommage pur : code et langue en entrée, nom en
  sortie, capitale initiale, repli sur le code intact.
- `webapp/services/search/labels.vitest.ts` **(+)** — les trois langues, le repli d'un code inconnu,
  la capitale, et une écriture sans casse.
- `webapp/services/search/refine.ts` — `byWeight` range les valeurs par nombre de profils porteurs ;
  les quatre `refinable*` s'en servent.
- `webapp/services/search/refine.vitest.ts` — trois attentes réalignées sur le nouvel ordre.
- `webapp/spaces/entreprise/app/components/search/RefineChoice.vue` — le nombre disparaît de
  l'étiquette ; `count` ne sert plus qu'au verdict d'extinction.
- `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` — Pays et Langue passent par le
  nommage, avec la langue courante.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` — la ligne qui situait ces
  filtres dans la barre haute depuis `ENT-110`.

## Reste à faire (hors périmètre)

- **Les secteurs restent en capitales** (`BANQUE`, `SANTÉ`) : seule rangée du panneau à ne pas porter
  de nom lisible, faute de référentiel que l'exécution connaisse. Non carté.
- **Le décalage d'hydratation de cet écran** — préexistant, mesuré, appartient au chargement
  progressif. Non carté.

## Définition de terminé

- [x] Les rangées Pays et Langue affichent des noms lisibles, dans la langue de l'interface.
- [x] Un code inconnu s'affiche tel quel, sans erreur et sans capitale ajoutée.
- [x] Les libellés portent une capitale initiale, comme les autres options du panneau.
- [x] Plus aucune étiquette du panneau ne porte de nombre, ni au repos, ni au survol, ni au clavier.
- [x] Une étiquette sans profil reste affichée, éteinte, sans mention.
- [x] Les rangées sans ordre naturel — villes, pays, langues, secteurs — sont rangées par nombre
      décroissant, et cet ordre ne bouge pas quand on affine.
- [x] Présence, projet de retour et disponibilité ont gardé leur ordre d'échelle.
- [x] Le rang de la barre haute reflète bien le jeu affiné — vérifié à l'écran, filtre posé.
- [x] Les tests de nommage et d'ordre sont écrits et verts.
- [x] La ligne fausse d'`arborescence-entreprise.md` est corrigée.
