# TASK ENT-070 — entreprise : le verdict ferme la ligne au lieu de l'ouvrir

- **Créée le** : 2026-08-06.
- **Session** : `82b13a37-3b91-47f4-8c01-fbf60e5f6b62`.
- **Statut** : ✅ fait le 2026-08-06 — le signe du verdict ferme la ligne, aligné d'une ligne à
  l'autre. Un seul fichier, un déplacement de bloc dans le gabarit.
- **Type** : refactor — la même information change de place, rien ne change de sens.
- **Tests** : sans objet — le changement déplace un élément dans un gabarit, sans logique ni
  fonction pure à couvrir. Le dépôt ne pratique aucun test de composant côté front, et en monter un
  harnais pour un déplacement serait un chantier plus lourd que la tâche. La preuve est une
  **capture d'écran** du panneau, avant et après.
- **Réf** : `ENT-063` — **à committer AVANT**, elle modifie le même fichier · `ENT-047` (la liste a
  absorbé la barre de puces) · `ENT-046` (« poste actuel ») · `MatchList.vue`

## Contexte

Dans « Ce profil face à vos critères », chaque ligne annonce son verdict **deux fois, aux deux
extrémités** : un signe ✓ ou ✗ tout à gauche, et le mot « Trouvé » ou « Non trouvé » tout à droite,
séparés par toute la largeur de la colonne.

Le signe arrive donc **avant qu'on sache de quel critère il parle**. On lit un verdict, puis le
critère qu'il juge, puis à nouveau le verdict. C'est aussi ce qui rendait ce signe confondable avec
le bouton de suppression retiré par `ENT-063` : deux croix de sens opposés sur la même ligne.

## Périmètre

- **Le signe du verdict passe en fin de ligne**, dans une colonne alignée à droite.
- **Il y est posé sur toute ligne confrontée**, y compris celles qui se terminent par une valeur du
  profil ou par « poste actuel ».
- **Le mot du verdict reste** : le signe le suit au lieu de le doubler à distance.

## Hors périmètre

- **Le libellé du verdict et sa teinte**, inchangés : « Trouvé » / « Non trouvé », teintés sur le
  signe et le mot seulement — jamais sur la ligne entière, la charte réserve la couleur aux états.
- **Le texte destiné aux lecteurs d'écran**, déjà présent, qui continue d'annoncer le verdict quand
  la ligne montre une valeur du profil.
- **Le contenu des lignes** : ce qui est demandé, ce que le profil porte, la temporalité du métier.
  Seule la place du signe bouge.
- **Les autres écrans** qui affichent des critères — la carte de recherche, la barre haute.

## Décisions de cadrage

- **Une colonne alignée à droite, pas un signe collé au mot.** La proposition initiale était de le
  poser juste après « Trouvé » ; le code montre que ce mot n'apparaît que sur **une ligne sur
  trois** — ailleurs la ligne finit par une valeur tronquée ou par « poste actuel ». Collé, le signe
  changerait de position à chaque ligne et atterrirait parfois après une ellipse.
- **Le comptage d'un coup d'œil est ce qu'il ne faut pas perdre.** Aujourd'hui la colonne de signes
  alignée à gauche permet de voir combien de critères passent sans lire une ligne. Une colonne
  alignée à droite le préserve ; un signe flottant le détruirait.
- **Le mot reste, et c'est une contrainte d'accessibilité, pas une préférence.** Le composant pose
  déjà que « le signe porte la teinte, jamais elle seule le sens ». Supprimer le mot ferait reposer
  le verdict sur une forme et une couleur.
- **Le signe est posé sur toutes les lignes confrontées.** L'alternative — ne le montrer que quand
  le mot est là — épargnait une répétition mais rendait le comptage impossible.

## Règles et cas limites

- **Rien à confronter, pas de signe.** Quand aucun profil n'est affiché, les critères restent
  lisibles sans verdict — comportement existant, à ne pas casser.
- **La valeur du profil reste tronquée** et neutre : c'est une donnée, la charte ne la colore pas.
  Le signe qui la suit ne doit pas la faire rétrécir davantage.
- **« Poste actuel » n'est pas un verdict** : il dit quand le profil exerce le métier, et reste
  neutre. Le signe s'ajoute après lui sans le teinter.
- **La ligne doit tenir dans une colonne étroite** — deux cent quatre-vingts pixels : le signe est
  `flex-none`, c'est la valeur du profil qui raccourcit.

## Documentation impactée

Aucune. `4.design/design-system.md` pose l'usage de la couleur sur les états, que cette tâche
respecte sans le modifier ; aucune page ne décrit la disposition de cette liste.

## Définition de terminé

- [x] Le signe du verdict apparaît en fin de ligne, aligné d'une ligne à l'autre.
- [x] Aucun signe ne subsiste en début de ligne.
- [x] Le mot « Trouvé » / « Non trouvé » est toujours affiché quand il l'était.
- [x] Les lignes qui montrent une valeur du profil ou « poste actuel » portent aussi leur signe.
- [x] Une liste sans profil à confronter n'affiche aucun signe — condition d'affichage inchangée.
- [x] Une capture d'écran est jointe à la vérification de la carte.

## Vérification

**À l'écran**, sur la même recherche à dix critères qu'`ENT-063` :

```
CE PROFIL FACE À VOS CRITÈRES
Poste  Développeur logiciel      poste actuel  ✓
Expérience  5 ans et plus               12 ans  ✓
java                Senior software an…         ✓
sql                              Trouvé         ✓
javascript            JavaScript/Jquery         ✓
nestjs                       Non trouvé         ✗
spring batch                     Trouvé         ✓
```

| | Avant | Après |
| --- | --- | --- |
| Position du signe | début de ligne | **fin de ligne, aligné** |
| Lignes de critères | 10 | **10** |
| Lignes portant leur signe | 10 | **10** |

**Les trois cas de fin de ligne portent bien leur signe** : celles qui montrent une valeur du profil
(« Senior software an… »), celles qui montrent le mot du verdict (« Non trouvé »), et celle qui
montre la temporalité (« poste actuel »).

**Le comptage d'un coup d'œil est préservé** — c'était le risque de la forme collée au mot : la
colonne alignée laisse lire huit critères satisfaits et deux manquants sans parcourir les libellés.

**Là où le mot est présent, le signe le suit immédiatement** : « Non trouvé ✗ ». C'est l'intention
d'origine, obtenue sans faire flotter le signe sur les deux autres tiers des lignes.

**Tests** : aucun, conformément à l'exemption de la ligne `**Tests**`.

## Livré

- `webapp/spaces/entreprise/app/components/search/MatchList.vue` **(M)** — le bloc du signe déplacé
  en fin de ligne, et son commentaire qui dit pourquoi la colonne l'emporte sur l'accolement au mot.
