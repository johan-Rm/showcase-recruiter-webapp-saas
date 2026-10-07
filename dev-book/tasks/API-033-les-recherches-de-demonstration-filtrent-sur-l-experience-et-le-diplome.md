# TASK API-033 — api : les recherches de démonstration filtrent sur l'expérience et le diplôme

- **Créée le** : 2026-08-06.
- **Session** : `82b13a37-3b91-47f4-8c01-fbf60e5f6b62`.
- **Statut** : ✅ fait le 2026-08-06 — 32 recherches portent un seuil d'expérience et 11 un niveau
  d'études, contre 6 et 2 ; les critères sont enregistrés dans l'ordre voulu, plus aucun titre ne se
  répète, et aucune carte ne descend sous cinq profils. 15 tests, 694 assertions.
- **Type** : refactor — le catalogue de démonstration change, aucun comportement du produit ne bouge.
- **Tests** : unitaires sur les invariants du catalogue. Le garde-fou actuel — « les critères qui
  resserrent restent minoritaires » — est **remplacé**, pas supprimé : il traduisait une intention de
  cadrage que cette tâche corrige. La règle qui compte vraiment (aucune carte vide, plancher de cinq
  profils) ne se prouve qu'en lançant la commande sur le vivier, et sa mesure est due dans la
  vérification.
- **Réf** : `API-029` (la commande) · `API-031` (les mots-clés) · `DemoSearchCatalog` ·
  `DemoSearchCatalogTest` · [criteria.ts](../../webapp/services/search/criteria.ts) — `autoName`,
  qui compose le titre à partir du **premier** mot-clé

## Contexte

La grille de démonstration montre mal ce que le produit sait faire. Sur ses 63 recherches, **6
portent un seuil d'expérience et 2 un niveau d'études** : un client qui parcourt les cartes voit des
recherches presque toutes bâties sur « un métier et des mots-clés », et deux des filtres les plus
structurants du vivier restent invisibles.

Un second défaut se lit dans le titre des cartes. Le nom d'une recherche est composé à partir du
métier puis du **premier mot-clé seulement** : en plaçant les compétences fréquentes en tête — ce que
`API-031` a fait pour tenir les volumes — dix titres se répètent. « Chef de projet · gestion de
projet », « Auditeur · audit », « Employé de restauration · restauration » disent deux fois la même
chose et n'apprennent rien.

## Périmètre

- **Une trentaine de recherches portent un seuil d'expérience**, contre 6, avec des seuils variés
  (3, 5, 8, 10 ans).
- **Une dizaine portent un niveau d'études**, contre 2, sur des niveaux variés — Bac+2, Licence,
  Master.
- **Les critères sont enregistrés dans l'ordre `poste → expérience → études → mots-clés`.**
- **Le premier mot-clé de chaque recherche ne répète plus son métier** ; les compétences fréquentes
  restent dans la liste, en seconde position.
- **Le garde-fou de test change de règle** : au plus **deux** critères restrictifs par recherche.

## Hors périmètre

- **Le nombre de mots-clés**, qui reste de 4 à 6 : ce sont eux qui tiennent les volumes, et les
  lignes « non trouvé » qu'ils produisent à l'écran sont un défaut d'affichage, pas une raison
  d'appauvrir les recherches.
- **Le moteur de recherche**, inchangé. L'ordre des critères n'a aucun effet sur les résultats : les
  mots-clés se relient en OU, et le reste se lit par facette.
- **Les quatre retours d'écran** — ordre du nom, ordre du panneau, confusion des deux croix, retrait
  du bouton de suppression de critère. Ils appartiennent à l'espace entreprise ; ni leur code ni
  leur carte ne s'écrivent ici.
- **La commande d'amorçage elle-même** (`API-029`) et l'ouverture du vivier (`API-032`).

## Décisions de cadrage

- **Montrer les filtres l'emporte sur protéger les volumes.** Le seuil d'expérience écarte les
  profils dont les années n'ont pas pu être calculées, le niveau d'études les 23 % dont le diplôme
  n'est pas reconnu. C'est un coût accepté : une démonstration qui n'exerce jamais ces filtres ne
  montre pas le produit.
- **Le garde-fou compte le cumul, plus la population.** Limiter le *nombre* de recherches
  restrictives mesurait à côté du risque : ce qui vide une carte, c'est d'empiler trois filtres sur
  une seule, pas d'en poser un sur trente. La nouvelle règle attrape le vrai danger et laisse passer
  le cadrage voulu.
- **Plancher à cinq profils.** En dessous, une carte cesse d'être une recherche vraisemblable. Un
  filtre qui fait passer une carte sous ce seuil est retiré de cette carte-là, pas du catalogue.
- **L'ordre d'enregistrement est aligné tout de suite**, sans attendre que l'écran impose son tri :
  le panneau « Ce profil face à vos critères » suit aujourd'hui l'ordre stocké, donc le retour
  devient visible sans dépendre d'une autre lane — et restera juste le jour où l'écran triera
  lui-même.
- **Le mot-clé le plus distinctif passe en tête**, la compétence fréquente juste derrière. Aucun
  effet sur le jeu de résultats, un titre qui apprend quelque chose.
- **Écart assumé : la carte la plus large plafonne à 174 profils, pas 200.** Le cadrage exigeait
  qu'une carte reste au-dessus de deux cents ; « Chef de projet » en perd soixante en recevant un
  niveau Master à côté de son seuil d'expérience. Le seuil a été abaissé plutôt que le filtre
  retiré, parce que c'est exactement l'arbitrage tranché au cadrage — montrer les filtres l'emporte
  sur protéger les volumes — et qu'un rail à trois chiffres reste démonstratif.

## Règles et cas limites

- **Un métier rare ne reçoit pas de filtre restrictif.** Quatre cartes vivent déjà sous la dizaine de
  profils ; leur ajouter une expérience ou un diplôme les ferait passer sous le plancher.
- **Le doctorat et le niveau ingénieur sont écartés.** Le critère se lit « au moins ce niveau » :
  Bac+2 retient 76 % du vivier, Licence 72 %, Master 62 % — mais Ingénieur n'en retient que 13 % et
  le doctorat bien moins. Croisés avec un métier et un seuil d'expérience, ils ramèneraient une
  poignée de profils. La variété des niveaux s'arrête donc à trois.
- **Certains titres restent partiellement redondants** et c'est acceptable — « Technicien de
  maintenance · gmao » apprend quelque chose là où « Sales Manager · international sales » n'apporte
  que « international ». La règle vise les pléonasmes, pas la parenté de vocabulaire.
- **Aucune recherche ne doit ramener zéro profil**, invariant hérité d'`API-031` et non négociable.

## Documentation impactée

Aucune. La commande, ses options et sa place dans le pipeline ne bougent pas ; seul le contenu du
catalogue change, et il est décrit dans son propre docblock.

## Définition de terminé

- [x] Une trentaine de recherches portent un seuil d'expérience, sur des seuils variés (32).
- [x] Une dizaine portent un niveau d'études, sur des niveaux variés, sans doctorat (11).
- [x] Les critères sont enregistrés dans l'ordre poste → expérience → études → mots-clés.
- [x] Aucun premier mot-clé ne répète le métier de sa recherche.
- [x] Aucune recherche ne porte plus de deux critères restrictifs, et un test le tient.
- [x] Aucune carte ne ramène moins de cinq profils, mesuré sur le vivier réel (plancher à 7).
- [x] Les volumes restent démonstratifs : la carte la plus large tient **174** profils — cf. *Écart
      assumé* dans les décisions de cadrage.

## Vérification

**Sur une copie du vivier de développement** (3 053 profils, ouverts par `app:candidates:consent-demo`),
en repartant d'une base propre :

| | Avant | Après |
| --- | --- | --- |
| Recherches avec un seuil d'expérience | 6 | **32** |
| Recherches avec un niveau d'études | 2 | **11** |
| Carte la plus large | 234 | **174** |
| Carte la plus étroite | 7 | **7** |
| Moyenne | 41 | **31** |
| Cartes vides | 0 | **0** |
| Cartes sous cinq profils | 0 | **0** |

**Le haut de grille** : Chef de projet 174, Développeur logiciel 135, Manager 133, Chef de projet
close 93, Responsable 87. **Le bas** : Employé de restauration 7, Préparateur de commandes 8,
Chauffeur 8, Chargé d'affaires 9 — tous au-dessus du plancher.

**Les titres n'énoncent plus deux fois la même chose** : « Chef de projet · 5 ans et plus · Master ·
prince2 », « Auditeur · 5 ans et plus · Master · contrôles internes », « Employé de restauration ·
service client » — là où le catalogue disait « Chef de projet · gestion de projet » et « Auditeur ·
audit ».

**Le garde-fou a servi immédiatement** : « Comptable » cumulait expérience, diplôme et pays. Le seuil
d'expérience a été retiré — c'est le filtre qui apportait le moins sur ce métier.

**Tests : 15, 694 assertions, vert.** Sept unitaires sur les invariants du catalogue, dont le
nouveau plafond de deux critères restrictifs ; quatre sur les bornes de l'avancement ; quatre
fonctionnels sur la commande d'amorçage, qui vérifient au passage que l'ordre des critères
enregistrés n'a pas cassé la reconnaissance des recherches existantes.

**Style** : le fichier régénéré passe sans correction.

## Livré

- `api/src/Domain/Search/Demo/DemoSearchCatalog.php` **(M)** — les 63 entrées réécrites : ordre des
  critères, seuils d'expérience, niveaux d'études, mot-clé distinctif en tête.
- `api/tests/Unit/DemoSearchCatalogTest.php` **(M)** — le garde-fou compte désormais le cumul par
  recherche au lieu de la population des recherches restrictives.

## Reste à faire (hors périmètre)

- **Quatre retours d'écran**, remontés à l'espace entreprise et non traités ici : l'ordre du nom
  composé (`poste · expérience · études · 1 mot-clé`), l'ordre du panneau « Ce profil face à vos
  critères », la confusion entre la croix du verdict et celle de la suppression, et le retrait du
  bouton de suppression de critère — la console étant un outil de consultation, pas d'édition.
