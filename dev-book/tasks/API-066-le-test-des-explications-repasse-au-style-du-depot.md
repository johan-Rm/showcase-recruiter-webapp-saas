# TASK API-066 — le test des explications repasse au style du dépôt

- **Créée le** : 2026-08-18.
- **Session** : `1c04995a-7633-4e0b-869c-95fbe9a4a23c`.
- **Statut** : ✅ fait le 2026-08-18 — la vérification de style ne signale plus **aucun fichier sur
  les 539** de l'API, et le diff fait exactement une ligne, dans un commentaire.
- **Type** : fix — la vérification de style est rouge sur la lane, et elle bloque la Pull Request de
  deux tâches qui n'y sont pour rien.
- **Tests** : `sans objet` — aucun code exécutable n'est produit. La correction est un alignement de
  commentaire opéré par l'outil de mise en forme du dépôt ; elle ne change ni une expression, ni une
  condition, ni un littéral. Ce qu'il faut démontrer est justement l'**absence** de changement de
  comportement, et la suite de tests existante s'en charge en restant verte.
- **Réf** : `API-062` (`a3414f5c`), qui a introduit l'écart · fichier :
  [api/tests/Functional/SearchExplanationsTest.php](../../api/tests/Functional/SearchExplanationsTest.php).

## Contexte

La lane serveur porte deux tâches prêtes à partir en relecture. Sa vérification de style sort
**rouge sur un fichier, un seul, et deux espaces** : un bloc de commentaire de
`SearchExplanationsTest.php` n'est pas aligné comme l'outil de mise en forme du dépôt l'aligne.

```
-     * @param list<Candidate>                                            $members
+     * @param list<Candidate>                                          $members
```

Rien de fonctionnel. Mais cette vérification est **bloquante en intégration continue**, et elle
s'exécute avant les autres : une Pull Request parfaitement saine et une Pull Request qui casse tout
rendent alors le même verdict. C'est le mécanisme exact qui a déjà fait passer un défaut réel
inaperçu sur ce dépôt — un rouge qui traîne est un rouge qu'on cesse de lire.

Le fichier appartient à une tâche livrée par une **autre session**. Sa correction ne s'improvise
donc pas au détour d'un autre commit : elle prend sa propre carte et son propre commit, comme
n'importe quel travail.

## Périmètre

- **Passer l'outil de mise en forme du dépôt** sur `api/tests/Functional/SearchExplanationsTest.php`.

## Hors périmètre

- **Tout le reste du fichier** : aucune assertion, aucun scénario, aucune donnée de test n'est
  touchée. Le travail de `API-062` reste ce qu'il est, à deux espaces près.
- **La cause en amont** — un fichier livré sans que l'outil de mise en forme soit passé. C'est une
  question d'habitude de travail, pas de code ; aucune tâche n'est ouverte ici pour elle.
- **Les autres gates** : analyse statique, frontières de couches et configuration des services
  sortent déjà vertes sur cette lane.

## Décisions de cadrage

### Une carte pour deux espaces

Disproportionné en apparence, et pourtant c'est la seule option qui tienne. Corriger sans carte
aurait produit un commit sans tâche, contre la règle du dépôt ; corriger à l'intérieur d'un autre
commit aurait mêlé deux travaux dans les mêmes fichiers, ce que l'invariant du cycle interdit
précisément. Le dépôt a déjà tranché ainsi pour la même situation en août.

### L'outil corrige, pas la main

Une correction manuelle produirait un fichier que l'outil voudrait encore changer — on aurait
l'illusion du vert sans l'avoir.

## Règles et cas limites

- **Aucun comportement ne change.** Toute différence de résultat des tests après cette tâche est un
  défaut, pas une amélioration.
- **Le diff doit être exactement d'une ligne.** Plus d'une ligne signifie que l'outil a corrigé
  autre chose que l'écart connu : à relire avant de commiter.
- **`git blame` devient légèrement plus bruyant** sur ce fichier. Coût assumé, payé une fois.

## Documentation impactée

Aucune. Aucune convention ne change, aucun contrat, aucune route. La tâche répare l'application
d'une règle existante, pas la règle.

## Définition de terminé

- [x] La vérification de style ne signale plus aucun fichier sur la lane.
- [x] Le diff est d'exactement une ligne, dans un commentaire.
- [x] La suite de tests des explications reste verte — le comportement n'a pas bougé.

## Vérification

| Ce qui a été lancé | Résultat |
| --- | --- |
| Vérification de style, sur toute l'API | **0 fichier sur 539** — elle était à 1 avant |
| Diff du fichier | **1 ligne**, un alignement de commentaire — relu caractère à caractère |
| `SearchExplanationsTest` rejoué | **8 tests, 67 assertions, verts** |

**Le résultat des tests est la preuve qui compte.** Ils passaient avant, ils passent après : c'est
ce qu'il fallait démontrer d'une correction qui ne touche qu'un commentaire. Un test neuf n'aurait
rien ajouté — il aurait couvert l'outil de mise en forme, pas le produit.

## Livré

- `api/tests/Functional/SearchExplanationsTest.php` (M) — une ligne, l'alignement d'un bloc de
  commentaire, posé par l'outil de mise en forme du dépôt. Aucune assertion, aucun scénario, aucune
  donnée de test touchés.

## Reste à faire (hors périmètre)

- **La cause en amont** : un fichier livré sans que l'outil de mise en forme soit passé. Question
  d'habitude de travail, pas de code — aucune tâche ouverte pour elle.
