# TASK ENT-120 — le survol d'un critère dit où le mot a été trouvé

- **Créée le** : 2026-08-14.
- **Session** : `b5fe5724-1c7f-4c06-ad0b-9ce84888cfe0`.
- **Statut** : ✅ fait le 2026-08-14 — la bulle de survol dit d'où vient la correspondance d'un
  mot-clé, elle épouse la largeur de la cartouche, et l'extrait du profil ne se déplie plus en pavé.
- **Type** : feat — l'écran consomme une donnée que le serveur envoie déjà, et la bulle de survol
  change de forme pour l'accueillir.
- **Tests** : `webapp/services/search/criteria.vitest.ts` — la provenance remonte dans l'outcome pour
  les trois valeurs possibles, reste vide sur les facettes qui n'en ont pas, et survit à l'absence de
  la clé quand le serveur l'omet. Plus le test de parité des trois langues, qui existe déjà et
  couvrira les nouvelles clés. **Aucun test de rendu** : le dépôt n'a aucun test de composant sur ses
  28 fichiers de test, et la largeur, l'alignement et la coupe se prouvent à l'écran.
- **Réf** : amont `API-040`, qui calcule et sert la provenance et désigne explicitement cette
  tâche-ci comme son aval — « la colonne du verdict, qui affiche déjà l'extrait et attend le nom du
  champ » · `webapp/shared/types/candidate-card.ts` ·
  `webapp/services/search/criteria.ts` ·
  `webapp/spaces/entreprise/app/components/search/MatchList.vue` ·
  `webapp/base/app/components/ui/InfoHint.vue` (le précédent de bornage d'une bulle) ·
  `api/src/Enum/MatchField.php` (les trois valeurs et la raison de leur nombre).

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, parcourt les profils d'une recherche
enregistrée. À droite de la fiche, la colonne du verdict — celle qui s'ouvre sur « 7/9 · Aligné sur
vos critères » — empile une petite cartouche par critère : `sql`, `big data`, `Master`.

**Ce qu'une coche ne dit pas.** Elle dit que le mot a été trouvé, jamais **où**. Or un terme vu dans
une compétence que le candidat a déclarée ne vaut pas un terme vu dans l'accroche de démarchage
qu'il s'écrit sur son réseau, ni un terme vu dans un intitulé de poste qu'il a réellement occupé.
C'est exactement l'écart qui fait dépenser un crédit sur un profil qu'on n'aurait pas ouvert.

**La donnée existe, elle est calculée et elle voyage déjà.** Depuis `API-040`, la correspondance
d'un mot-clé porte le nom du champ qui a répondu — `HEADLINE`, `SKILL` ou `JOB_TITLE` — et le
serveur le sérialise sous `matchedIn`. L'écran le jette : le type que le webapp tient à la main ne
déclare pas le champ, donc rien en aval ne peut l'afficher.

**Le survol, lui, existe déjà.** S'arrêter sur une cartouche ouvre une bulle qui dit trois choses :
la catégorie du critère (« Mot-clé », « Études »), ce qui a été demandé, et ce que le profil porte en
face. Ce qui manque n'est donc pas la bulle, c'est **un repère de plus dedans**.

**Un défaut de forme sort avec le sujet.** Sur un mot-clé trouvé dans l'accroche, la bulle affiche
l'accroche **entière**, sans coupe — 49 caractères en médiane, mais 752 au maximum, et souvent trois
phrases de démarchage. Le défaut ne s'était jamais vu parce que personne n'allait lire cette ligne ;
annoncer « son accroche » y envoie justement le regard.

> ⚠️ **À l'ouverture, le dossier de la lane n'était pas libre** : il portait le chantier non commité
> d'`ENT-117`, qui modifie **les trois mêmes fichiers de traduction**. Attendre sa clôture avant
> d'implémenter — deux sessions qui écrivent dans un même fichier ont déjà coûté du travail ici.

## Périmètre

- **Le type de la carte candidat déclare la provenance.** `matchedIn` sur `CriterionMatch`,
  **optionnel et nullable** comme ses voisins : le sérialiseur omet la clé, il ne l'envoie pas vide.
- **La provenance remonte jusqu'à la ligne.** `CriterionOutcome` la porte, `confrontCriteria` la
  reporte depuis la correspondance.
- **La bulle dit d'où vient le mot**, fusionné à la catégorie sur sa première ligne : « Mot-clé ·
  une compétence ». Les critères qui n'ont pas de provenance gardent la catégorie seule.
- **La bulle épouse la cartouche** : largeur de la ligne survolée, alignée sur son bord gauche.
- **L'extrait se coupe à trois lignes**, et la bulle borne sa hauteur sur la place réellement
  disponible à l'écran.
- **Le texte à l'écoute porte la provenance** comme il porte déjà la catégorie — la bulle sert le
  confort, elle ne détient pas l'information. La règle est déjà écrite dans le composant.
- **Trois clés, trois langues** : `fr`, `en`, `ar`.

## Hors périmètre

- **Toute retouche serveur.** La donnée est calculée, exposée et documentée. Si la vérification à
  l'écran montrait qu'elle n'arrive pas au navigateur, **ça sort de la lane et ça remonte** — ça ne
  se corrige pas ici.
- **Dire *quand* le poste a été occupé.** Le « quand » n'est rempli que pour la facette métier, pas
  pour les mots-clés ; l'obtenir demanderait du serveur. La ligne dit « un poste occupé » et se tait
  sur la date. **Non ouvert.**
- **Remonter toutes les provenances d'un même mot.** Le serveur s'arrête à la première trouvée —
  accroche, puis compétences, puis postes. Tout renvoyer touche le moteur d'explication, qui doit
  rester d'accord champ pour champ avec le filtre SQL. **Non ouvert.**
- **La provenance des autres critères** — qui l'affirme : le candidat, son CV importé, un calcul
  showcase-recruiter-webapp-saas, l'entretien. Écartée le 2026-08-14, cf. *Décisions de cadrage*.
- **Surligner le terme cherché dans l'extrait.** Demande un découpage autour du mot qui n'existe
  nulle part. **Non ouvert.**
- **L'écart de contrat sur `openapi.json`.** La structure des correspondances n'y est pas décrite,
  c'est pourquoi le type est tenu à la main ; défaut préexistant nommé par `API-040`, non traité.
- **La note de pied de colonne** (« les critères non trouvés portent sur le CV public ») ne bouge
  pas : elle parle des absences, pas des correspondances.

## Décisions de cadrage

**La provenance dit l'endroit, pas le crédit — et seulement pour les mots-clés.** L'autre lecture
possible était de dire, sur *chaque* ligne, qui affirme la donnée : « déclaré par le candidat »,
« écrit dans son CV », « calculé sur son parcours ». Écartée : six lignes sur neuf auraient répété
« déclaré par le candidat », ce qui sature la bulle sans lever aucun doute — le doute n'existe que
là où plusieurs endroits sont possibles, c'est-à-dire sur les mots-clés. Et cette lecture-là demande
d'écrire une phrase par famille de critère, donc un vocabulaire produit à arbitrer, pour un gain nul
sur la majorité des lignes.

**La provenance se fusionne à la catégorie plutôt que de s'ajouter en pied.** Un seul repère gris en
tête de bulle au lieu de deux, et la bulle ne gagne aucune ligne. En pied, l'information la plus
utile serait arrivée **après** l'extrait — donc après le pavé, précisément dans le cas où le pavé
existe. La variante « la provenance remplace la catégorie » a été écartée : sur une recherche qui
mélange métier et mots-clés, savoir de quel champ de la recherche vient la ligne reste utile.

**L'extrait se coupe à trois lignes, pas à une.** À une ligne la bulle garde une hauteur constante,
mais sur une accroche on ne voit presque jamais le mot cherché : il arrive en milieu de phrase.
Trois lignes donnent le contexte sans jamais laisser la bulle se déplier en pavé.

**La bulle prend la largeur de la cartouche, et ça vaut pour toutes les lignes.** Le gain n'est pas
de la place — la cartouche fait à peu près la largeur maximale actuelle — c'est un **alignement** :
la bulle épouse la ligne survolée au lieu de flotter centrée à côté, et se lit comme lui appartenant.
Conséquence assumée : les bulles de tous les critères changent d'allure, pas seulement celles des
mots-clés.

**Le retournement haut/bas ne se code pas.** La bibliothèque qui porte ces bulles ouvre par le bas et
bascule au-dessus quand la place manque, avec `avoidCollisions` actif par défaut. Rien à ajouter, et
surtout rien à réimplémenter.

**Le vocabulaire retenu : « son accroche · une compétence · un poste occupé ».** Les articles font
lire la ligne comme une phrase, et l'article indéfini porte l'essentiel de la réserve : c'est **une**
compétence parmi d'autres, **un** poste parmi d'autres, sans prétendre dire lequel ni quand — ce qui
est exactement ce que le serveur sait. Les étiquettes sèches au singulier (« Compétence ») ont été
écartées pour ça : elles se lisent comme « sa compétence ». Le champ n'avait **aucun libellé
utilisateur** dans le produit jusqu'ici ; « accroche » est le mot déjà employé partout dans le code,
et il devient donc le mot du produit.

## Règles et cas limites

- **La provenance est absente sur toutes les facettes sauf les mots-clés.** La bulle affiche alors la
  catégorie seule, exactement comme aujourd'hui. Pas de ligne vide, pas de séparateur orphelin.
- **La clé est omise, pas envoyée à `null`.** Une garde sur le seul `null` laisse passer l'absence —
  le piège est déjà documenté sur `matchedOn` et `currentTitle`, et il a déjà produit une valeur
  blanche à la place d'un verdict. Traiter `undefined` autant que `null`.
- **Un critère non satisfait n'a pas de provenance** : le serveur ne l'envoie que sur une
  correspondance. Rien à afficher, et rien à inventer.
- **Une valeur inconnue ne casse pas l'écran.** Si une quatrième provenance apparaissait un jour côté
  serveur, la ligne retombe sur la catégorie seule plutôt que d'afficher une clé de traduction.
- **Largeur figée = hauteur variable.** C'est le corollaire du choix, et c'est pourquoi la coupe de
  l'extrait n'est pas séparable : le retournement déplace la bulle, il ne la rétrécit pas. Le
  précédent est écrit noir sur blanc dans `InfoHint.vue` — « 16 bulles sur 27 encore hors écran à
  390 px avec la seule première borne ».
- **Aucune valeur en dur dans une classe utilitaire.** La largeur et la hauteur se lisent dans les
  variables que la bibliothèque pose elle-même sur la bulle ; le bornage passe par du CSS `scoped`,
  comme `InfoHint.vue`, parce que les conventions du socle interdisent les valeurs arbitraires.
- **La bulle ne montre jamais ce que le dévoilement fait payer.** Les trois provenances sont des
  champs déjà servis, masqué ou non — c'est ce qui les rend sûres sans règle de masquage. Ni
  employeur, ni contenu de CV, ni compte rendu d'entretien n'entrent ici, aujourd'hui ni plus tard.
- **La provenance n'est pas exhaustive**, et le libellé ne doit jamais le laisser croire : « vu dans
  une compétence », jamais « uniquement dans une compétence ».
- **Aucun arrêt clavier ajouté.** La cartouche reste un bloc non focalisable : huit critères feraient
  huit tabulations avant « Retenir », sur chaque profil d'une pile qui en compte des centaines.
- **Arabe** : la bulle est en écriture droite-à-gauche comme le reste ; vérifier que la ligne
  composée « catégorie · provenance » s'y lit dans le bon ordre.

## Hypothèse assumée

`matchedIn` est bien sérialisé jusqu'au navigateur. C'est lu dans le code du mapper qui construit la
carte, **pas vérifié sur une réponse réelle**. La première étape de l'implémentation est donc une
vérification à l'écran, pas une ligne de code — et si elle échoue, la tâche s'arrête et remonte.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` — la ligne du filtre libre
  décrit déjà que la correspondance dit lequel des trois champs a répondu ; ajouter que **l'écran
  l'affiche**, et où.
- `dev-book/cadrage/entreprise/ui/vue_la_visionneuse.md` §5.3 — décrit ce que le panneau affiche
  (demandé · verdict · valeur du profil) sans rien dire de la provenance. À compléter.
- `webapp/services/README.md` et `webapp/shared/README.md` — à relire, corrigés seulement s'ils
  décrivent la structure des correspondances.
- **Pas de page de contrat d'API** : aucune ne décrit la structure des correspondances, `API-040`
  l'avait déjà vérifié et corrigé son propre cadrage sur ce point.

## Ce que l'implémentation a tranché autrement

**La hauteur de la bulle n'est pas bornée sur la place disponible**, contrairement à ce que le
périmètre annonçait. Mesuré : coupé à trois lignes, l'extrait le plus long du vivier donne une bulle
de 134 pixels, contre 89 pour une valeur courte — elle tient partout, y compris sur une fenêtre de
520 pixels de haut. Une seconde borne n'aurait rien retenu qui dépasse ; elle aurait seulement pu
**rogner** une bulle légitime, sans que rien ne le signale. Le retournement de la bibliothèque et la
coupe suffisent, et se voient.

## Vérification

Stack du worktree entreprise, navigateur piloté, connecté en `owner@demo.showcase-recruiter-webapp-saas.example`. Recherche
« Preuve ENT-120 · provenances » (9 critères, 80 résultats) créée pour l'occasion.

**La donnée arrive bien jusqu'au navigateur** — l'hypothèse assumée est levée. Réponse réelle de
`GET /v1/searches/{id}/results` :

```
{"facet":"q","value":"développeur","matched":true,
 "matchedOn":"Développeur backend PHP","matchedIn":"HEADLINE"}
```

Sur 90 correspondances relevées dans trois recherches : **79 `HEADLINE`, 11 `SKILL`**.
**`JOB_TITLE` n'a été observé sur aucun profil** — attendu, le serveur s'arrête au premier endroit
qui répond et l'accroche passe avant. Le libellé existe et est couvert par les tests, il n'a pas pu
être vu en vrai.

**Ce que la bulle écrit**, relevé au survol :

```
Mot-clé · son accroche      développeur   Développeur Full-Stack à la recherche d'alternance
Mot-clé · une compétence    chef de projet Chef de Projet Structure
```

**L'extrait long est coupé.** Accroche de 189 caractères, bulle rendue sur trois lignes suivies de
points de suspension — hauteur 134 px contre 89 px pour une valeur courte.

**La bulle fait la largeur de la cartouche**, mesuré dans les deux mises en page : `321,625 / 321,625`
en colonne détachée, `512 / 512` quand le panneau retombe en pied de fiche. Bord gauche aligné.

**Elle se retourne quand le bas manque.** Fenêtre de 800 px, cartouche posée à `y=764` : la bulle
s'ouvre de `668` à `755`, donc **au-dessus**, et dans l'écran. Les neuf cartouches testées tiennent
toutes dans la fenêtre.

**Les critères sans provenance sont inchangés**, et le texte à l'écoute porte bien la provenance —
lecture du texte accessible des neuf lignes :

```
Poste recherché · développeur · Poste actuel · Développeur logiciel
Expérience · 3 ans et plus · 8 ans
Études · Master · Ingénieur
Mot-clé · une compétence · php · php(symfony)
Mot-clé · une compétence · kubernetes · Azure Kubernetes Service (AKS)
Mot-clé · terraform                              ← non satisfait : aucune provenance
```

**Tests** :

```
services/search/criteria.vitest.ts   Test Files 1 passed · Tests 69 passed (732 ms)
base/i18n/locales/parity.vitest.ts   Test Files 1 passed · Tests  9 passed (661 ms)
```

## Livré

- `webapp/shared/types/candidate-card.ts` **(M)** — `matchedIn` déclaré sur la correspondance,
  optionnel et nullable, avec la réserve de non-exclusivité et la limite de masquage.
- `webapp/services/search/criteria.ts` **(M)** — `CriterionOutcome` porte `source`, dont le type se
  lit sur le contrat plutôt que de recopier ses trois valeurs.
- `webapp/spaces/entreprise/app/components/search/MatchList.vue` **(M)** — la provenance fusionnée à
  la catégorie, la bulle alignée et à la largeur de la cartouche, l'extrait coupé à trois lignes.
- `webapp/services/search/criteria.vitest.ts` **(M)** — les trois provenances, la facette qui n'en a
  qu'une, la clé omise, le mot-clé non satisfait, le profil absent.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — les trois libellés.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/inventaire-criteres-3-couches.md` **(M)** — la ligne du
  filtre libre dit désormais que l'écran affiche la provenance.
- `dev-book/cadrage/entreprise/ui/vue_la_visionneuse.md` **(M)** — §5.3 gagne le principe de la
  provenance au survol.

## Reste à faire (hors périmètre)

- **`JOB_TITLE` jamais observé à l'écran** : aucun profil du vivier local ne le déclenche, l'accroche
  répondant toujours la première. Le libellé est couvert par les tests, pas par une capture.
- **`webapp/services/README.md` et `webapp/shared/README.md`** : relus, aucune convention touchée —
  rien à corriger. Vérifié.
- **`docs/…/data-contracts.md`** : déjà à jour. Il décrit l'exception `CandidateCardOutput` et
  prescrit exactement ce geste — « reporter le champ dans le miroir dans la tâche de l'espace
  consommateur ». Rien à ajouter.
- **Une recherche de démonstration** « Preuve ENT-120 · provenances » reste dans la base du worktree
  entreprise. Donnée de développement, pas de nettoyage prévu.

## Définition de terminé

- [x] Une réponse réelle de la recherche montre `matchedIn` sur une correspondance de mot-clé.
- [x] Le type de la carte candidat déclare la provenance, optionnelle **et** nullable.
- [x] La provenance remonte dans l'outcome, et reste vide là où elle n'existe pas.
- [x] La bulle d'un mot-clé affiche « Mot-clé · son accroche / une compétence / un poste occupé ».
- [x] La bulle d'un critère sans provenance est inchangée.
- [x] La bulle épouse la largeur de la cartouche survolée et s'aligne sur son bord gauche.
- [x] Sur un profil dont l'accroche fait plusieurs phrases, l'extrait s'arrête à trois lignes et la
      bulle tient dans l'écran, en haut comme en bas de la colonne.
- [x] Le texte à l'écoute annonce la provenance.
- [x] Les trois clés existent en `fr`, `en` et `ar`, et le test de parité passe.
- [x] `criteria.vitest.ts` couvre les trois valeurs, l'absence de clé et les facettes sans
      provenance.
- [x] Les deux pages de documentation sont à jour.
- [x] La carte est clôturée et le commit ne porte que cette tâche.
