# TASK ENT-062 — entreprise : l'ordre des critères devient le même partout

- **Créée le** : 2026-08-06.
- **Session** : `82b13a37-3b91-47f4-8c01-fbf60e5f6b62`.
- **Statut** : ✅ fait le 2026-08-06 — le nom composé et la liste des critères lisent le même ordre,
  défini à un seul endroit : poste, expérience, études, mots-clés. 57 tests, dont 5 écrits ici.
- **Type** : fix — deux endroits énoncent les mêmes critères dans deux ordres différents.
- **Tests** : unitaires sur `services/search/criteria.ts` — l'ordre du nom composé, et le tri de la
  liste des critères. Les deux sont des fonctions pures, testables sans monter de composant ; c'est
  la seule couverture que le dépôt pratique côté front, et elle suffit ici.
- **Réf** : `ENT-053` (l'ordre fixe des groupes dans le nom, que cette tâche corrige) · `ENT-047`
  (la liste des critères a absorbé la barre de puces) · `criteria.ts` — `autoName`, `CANONICAL_GROUPS`, `orderCriteria` ·
  `MatchList.vue` · `ControlPanel.vue`

## Contexte

Un recruteur lit les critères de sa recherche à deux endroits, et ils n'y sont pas dans le même
ordre.

**Dans le titre**, le nom composé annonce le métier, puis le premier mot-clé, puis l'expérience et
les études. Sur une recherche « Chef de projet » filtrée à cinq ans d'expérience, ça donne « Chef de
projet · gestion de projet · 5 ans et plus » : le mot-clé s'intercale entre deux informations qui se
suivent naturellement.

**Dans le panneau « Ce profil face à vos critères »**, la liste suit l'ordre où les critères ont été
*enregistrés* — donc n'importe lequel. Sur la même recherche, l'expérience arrive **après cinq
mots-clés**, tout en bas, alors qu'elle est un filtre structurant.

Aucun des deux ne suit l'ordre dans lequel un recruteur pense sa recherche : le poste d'abord, puis
ce qu'il exige de la personne — l'expérience, le diplôme —, puis ce qu'il cherche dans le détail.

## Périmètre

- **Un ordre canonique**, écrit une seule fois : **poste → expérience → études → mots-clés**.
- **Le nom composé le suit** : `poste · expérience · études · 1 mot-clé`, le mot-clé passant en
  dernier et restant limité à un.
- **La liste des critères le suit aussi**, quels que soient l'ordre d'enregistrement et le nombre de
  mots-clés — eux s'affichent tous, contrairement au nom.
- **Le tri est une fonction pure de `services/search/`**, testée, appelée par la vue.

## Hors périmètre

- **Le retrait du bouton de suppression** dans la liste des critères : c'est `ENT-063`.
- **Le moteur de recherche et l'ordre d'enregistrement** : les critères continuent d'être stockés
  dans l'ordre où ils sont posés ; c'est l'affichage qui impose le sien. Un jeu de résultats ne
  dépend pas de cet ordre.
- **Les groupes hors des quatre nommés** — séjour au Maroc, langue, disponibilité, mobilité. Ils
  gardent le comportement actuel : un chemin de repli les affiche quand aucun des quatre n'est posé.
- **`SentenceName.vue` et `Sentence.vue`**, modifiés en ce moment par une autre session : cette
  tâche ne touche pas l'affichage du nom, seulement la règle qui le compose.

## Décisions de cadrage

- **Une seule liste ordonnée, deux usages.** Le nom et le panneau partagent la même constante : deux
  listes finiraient par diverger, et c'est exactement ce qui s'est produit — `ENT-053` a fixé
  l'ordre du nom sans que la liste des critères en sache rien.
- **Le mot-clé passe en dernier dans le nom.** Il est le plus variable et le moins structurant ;
  placé au milieu, il coupait la lecture entre le poste et ses exigences.
- **Le nom garde un seul mot-clé**, la liste les montre tous. Ce n'est pas une incohérence : un
  titre se lit d'un coup d'œil sur une carte, une liste se lit ligne à ligne dans un panneau.
- **La règle vit dans `services/`**, pas dans le composant : `services/README.md` réserve aux
  services la logique métier pure et sans dépendance framework, et le nom composé y est déjà.

## Règles et cas limites

- **Un groupe absent ne laisse pas de trou** : une recherche sans niveau d'études affiche
  « poste · expérience · mot-clé », sans séparateur vide.
- **Une recherche sans aucun des quatre groupes** — une recherche dictée du type « des gens au Maroc
  qui parlent anglais » — garde le chemin de repli existant, qui affiche les autres groupes, trois
  au plus, puis un décompte.
- **L'ordre d'enregistrement reste libre.** Le catalogue de démonstration pose déjà ses critères
  dans l'ordre canonique, mais une recherche créée à l'écran peut les poser autrement : le tri ne
  doit rien supposer de la source.
- **Aucun critère ne disparaît au tri** : autant de lignes après qu'avant, y compris pour un groupe
  inconnu du classement.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit l'ordre d'affichage des critères. `services/README.md`
énonce les conventions du dossier et reste vrai — la tâche s'y conforme, elle ne les change pas.

## Définition de terminé

- [x] Le nom composé affiche `poste · expérience · études · 1 mot-clé`.
- [x] La liste « Ce profil face à vos critères » affiche poste, expérience, études, puis les
      mots-clés, quel que soit l'ordre d'enregistrement.
- [x] Les deux lisent la même liste ordonnée, définie à un seul endroit.
- [x] Un groupe absent ne laisse ni trou ni séparateur orphelin.
- [x] Aucun critère n'est perdu par le tri.
- [x] Les tests unitaires couvrent l'ordre du nom et le tri de la liste.

## Vérification

**Le nom composé, sur la recherche de la capture qui a fait remonter le sujet :**

| | |
| --- | --- |
| Avant | `Chef de projet · gestion de projet · 5 ans et plus` |
| Après | `Chef de projet · 5 ans et plus · Master · prince2` |

**La liste des critères ne dépend plus de l'ordre d'enregistrement.** Les mêmes critères, posés
rangés puis mélangés, produisent la même sortie :

```
metier → experience → etudes → motsCles → motsCles → motsCles
```

**Les cas limites, éprouvés sur le service réel :**

- une recherche sans aucun des quatre groupes garde son chemin de repli — `Maroc Casablanca · fr` ;
- **aucun critère n'est perdu** : huit en entrée, huit en sortie ;
- les groupes inconnus — séjour au Maroc, langue — ferment la marche au lieu d'être écartés ;
- la liste reçue n'est pas modifiée : la couche reste pure.

**Tests : 57, vert** (52 existants, 5 écrits ici). Les quatre premiers couvrent le tri — ordre
canonique, stabilité entre critères d'un même groupe, aucun critère perdu, liste d'origine
intacte —, le cinquième vérifie que la liste affichée en hérite.

**Un test existant a été corrigé, pas supprimé.** « Ne laisse plus tomber les études quand quatre
groupes sont posés » (`ENT-053`) figeait l'ancien ordre, avec le mot-clé en deuxième position. Son
intention — les études survivent à un quatrième groupe — reste vraie ; seul son attendu a bougé, et
le commentaire dit pourquoi.

## Livré

- `webapp/services/search/criteria.ts` **(M)** — l'ordre canonique remplace la liste réservée au
  nom, une fonction de tri exportée l'applique, et la confrontation des critères la traverse.
- `webapp/services/search/criteria.vitest.ts` **(M)** — 5 tests, et l'attendu d'un test d'`ENT-053`
  remis à jour.

## Reste à faire (hors périmètre)

- **`ENT-063`** — la console cesse de modifier la recherche : le bouton de suppression quitte la
  liste des critères, et la confusion entre la croix du verdict et celle de l'action disparaît avec
  lui. Cadrée, pas commencée.
