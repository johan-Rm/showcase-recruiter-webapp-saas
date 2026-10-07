# TASK API-063 — un canal sans donnée ne se facture plus

- **Créée le** : 2026-08-14.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff`.
- **Statut** : ✅ fait le 2026-08-17 — un canal sans donnée n'est plus facturé : le lot l'exclut, le
  dévoilement unitaire le refuse en `422`, et la règle de présence des quatre canaux vit désormais à
  un seul endroit.
- **Type** : fix — le dévoilement cesse de débiter un crédit pour un accès qui n'a rien à ouvrir.
- **Tests** : fonctionnels, dans `api/tests/Functional/RevealTest.php` — un lot **mixte** (un profil
  avec la donnée, un déjà payé, un vide) dont seul le premier est facturé ; un lot **entièrement
  vide** qui aboutit sans débit ni ligne ; un profil **rencontré sans compte rendu écrit**, qui n'est
  pas facturable sur ce canal ; et le CV dont l'entrée ne porte pas de fichier.
- **Réf** : `API-064` — les deux booléens de présence, aval de celle-ci ·
  `ENT-132` — l'écran qui éteindra les canaux vides · `api/src/Domain/Credit/ProfileRevealer.php` ·
  [credits_et_devoilement.md](../cadrage/entreprise/api/credits_et_devoilement.md) ·
  [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) §3.1 — la joignabilité qui existe
  précisément pour éviter cette dépense

## Contexte

**On peut payer un crédit pour un e-mail qui n'existe pas.** Le dévoilement vérifie le plafond de
lot, le solde, et ce qui est déjà payé — jamais si le canal a quelque chose à servir.

Mesuré sur une recherche réelle du vivier de développement :

```
profils du jeu               : 100
ont un e-mail                :   1   (1 %)
ont un téléphone             :   1   (1 %)

e-mail déjà payé             :   4
  dont payé POUR RIEN        :   3   ← aucun e-mail à servir
```

**Trois dévoilements sur quatre ont acheté du vide.** Et ce n'est pas un cas de bord : avec 1 % de
profils joignables par e-mail, payer pour rien est le geste **par défaut** sur un profil pris au
hasard.

Le plus gênant est que le produit sait déjà tout ce qu'il faut pour l'empêcher. Le cadrage donne
cette raison d'être exacte au signal de joignabilité :

> « C'est un vrai enjeu produit : sans ce signal, le recruteur grille un crédit sur un profil
> injoignable. »

Le signal existe, la carte le descend, la colonne l'affiche — **et personne ne s'en sert pour
refuser la dépense**. Le défaut a été trouvé par un recruteur qui trouvait le compteur faux : il
était juste, mais il facturait un profil dont l'e-mail était déjà payé… et vide.

## Périmètre

- **Le lot à payer exclut les profils dont le canal est vide**, exactement comme il exclut déjà ceux
  qui ont payé — même endroit, même mécanique.
- **Aucune ligne de dévoilement n'est créée** pour eux.
- **Un lot entièrement vide aboutit en succès**, sans débit et sans ligne.
- **Le dévoilement à l'unité répond `422`** avec un message explicite — il doit rendre un objet, et
  il n'y en a pas. Écart au cadrage initial, voir *Décisions de cadrage*.
- **La règle de présence vit dans une classe unique**, `Domain\Candidate\RevealableContent`, que la
  garde utilise et que `API-064` réutilisera. Écart au cadrage initial, voir plus bas.
- **Les quatre canaux**, chacun sur ce qu'il vend réellement :

  | Canal | Ce qui doit exister |
  | --- | --- |
  | E-mail | une adresse de contact |
  | Téléphone | un numéro |
  | CV | **au moins une entrée portant un identifiant de fichier** |
  | Compte rendu | **le texte du compte rendu**, non vide |

## Hors périmètre

- **Les deux booléens de présence sur la carte** — `API-064`. Cette carte protège le débit, elle
  n'expose rien.
- **L'écran**, qui continuera d'afficher un coût pour un canal vide jusqu'à `ENT-132`. On paiera
  moins, l'écran mentira encore : c'est assumé, la garde est ce qui presse.
- **Les dévoilements déjà payés pour rien**, ici comme en production — voir *Risque assumé*.
- **Le plafond de lot et le tout-ou-rien du solde**, inchangés.
- **Le prix d'un accès**, inchangé.

## Décisions de cadrage

### La règle est la jumelle d'une règle déjà écrite

`ProfileRevealer` porte déjà, en toutes lettres : *« ce qui est déjà payé ne se repaie pas et ne
compte pas dans le coût »*. Il manquait sa jumelle — **ce qui n'a rien à servir ne se facture pas
non plus** — et elle se pose au même endroit, sur le même ensemble, sans rien inventer.

### Ignorer, plutôt qu'enregistrer un dévoilement gratuit

Écarté : créer une ligne sans facturer, pour garder trace de la tentative. Elle rendrait le profil
« déjà dévoilé », si bien que le jour où l'e-mail arrive par une synchro, le recruteur l'obtiendrait
**sans payer** — et l'écran continuerait de ne pas distinguer « payé » de « vide ».

En n'enregistrant rien, la donnée qui arrive plus tard se paie à ce moment-là, et le recruteur reçoit
quelque chose en échange.

### Écart assumé — une seule implémentation, pas deux règles jumelles

Cette carte, et `API-064` avec elle, acceptaient **deux implémentations** — la garde sur les
entités, la carte candidat pour l'écran — reliées par une règle écrite dans le cadrage, et nommaient
la divergence comme « le risque principal ». L'implémentation a tranché autrement.

En lisant le mapper de sortie, il est apparu que la présence d'un e-mail n'est pas « la personne en
a un » : c'est une résolution en deux temps, `Person` d'abord, réplica Jarvi ensuite. Cette subtilité
ne se devine pas — la réécrire de mémoire dans la garde aurait produit deux vérités différentes dès
le premier jour, pas au premier changement.

La règle vit donc dans `Domain\Candidate\RevealableContent`, appelée par le dévoilement, et que la
carte candidat consommera. Le risque nommé disparaît au lieu d'être surveillé, et le cadrage garde
son rôle : dire **ce qui** doit exister, pendant que la classe dit **comment** on le lit.

### Écart assumé — le dévoilement à l'unité refuse, il ne réussit pas à vide

Le cadrage décidait qu'un lot vide « aboutit en succès », et n'avait pas vu que l'endpoint unitaire
n'a pas cette liberté : il doit rendre l'accès créé, et il n'y en a aucun. Répondre `201` avec un
corps vide, ou pire avec une ligne inventée, mentirait plus fort que le défaut corrigé.

Il répond donc **`422`** avec un message lisible, tandis que le lot garde le comportement cadré —
une collection plus courte que la sélection. Deux contrats différents, deux réponses différentes ; le
solde ne bouge dans aucun des deux cas.

### Un lot entièrement vide n'est pas une erreur

Écarté : refuser l'appel avec un message explicite. Ce serait s'écarter du comportement du lot
entièrement déjà payé, qui aboutit déjà sans rien faire — et introduire un code d'erreur pour un cas
que l'écran ne produira plus une fois `ENT-132` livrée.

### Le compte rendu se teste sur son texte, pas sur « rencontré »

Un profil peut avoir été rencontré sans que le compte rendu ait été rédigé : `starInterviewedAt`
renseigné, `starInterviewReport` nul. S'appuyer sur l'indicateur « vérifié en entretien » — déjà
exposé sur la carte, donc tentant parce que gratuit — revendrait du vide dans ces cas-là. On teste ce
qu'on vend.

### Le CV se teste sur le fichier, pas sur la longueur de la liste

Le réplica Jarvi porte une liste d'entrées, chacune avec un identifiant de fichier, un nom et un
type. Une entrée sans identifiant n'est pas téléchargeable : la compter reviendrait à reconduire le
défaut qu'on corrige, en plus discret.

## Règles et cas limites

- **Lot mixte** : seuls les profils qui ont la donnée **et** ne l'ont pas déjà payée sont facturés.
  Les deux exclusions se cumulent sans se gêner.
- **Canal à la fois vide et déjà payé** — les trois dévoilements fautifs mesurés sont dans ce cas :
  rien n'est facturé, rien n'est recréé, et la ligne existante n'est pas touchée.
- **Solde insuffisant** : la vérification porte sur le coût **après** exclusion. Un lot qui ne
  passait pas peut désormais passer, ce qui est le comportement attendu — on ne refuse plus un achat
  pour des profils qu'on ne facture pas.
- **Concurrence** : le verrou pessimiste sur le portefeuille ne bouge pas ; l'exclusion se calcule à
  l'intérieur de la transaction, sur les entités relues.
- **Profil masqué ou sans consentement** : hors sujet ici, les gardes existantes s'appliquent avant.

## Risque assumé

**Ce qui a déjà été facturé pour rien n'est pas réparé par cette carte**, et le chiffre de production
est inconnu. Il est mesurable — un dévoilement dont le canal n'a aucune valeur — et il décide de la
suite : si le compte est nul, la garde suffit ; s'il ne l'est pas, c'est de l'argent pris à des
clients, et l'arbitrage devient commercial, pas technique.

**Cette mesure appartient à l'humain** : la session qui cadre n'a pas accès à la base de production.
Elle n'est pas une tâche, elle est une question ouverte que cette carte laisse tracée.

## Documentation impactée

- [credits_et_devoilement.md](../cadrage/entreprise/api/credits_et_devoilement.md) — la règle de
  facturation gagne sa seconde moitié ; c'est là qu'elle se lit.
- **Pas de champ ni de route modifiés** : `api/openapi.json` reste identique, et le vérifier fait
  partie du travail. Le dévoilement unitaire gagne en revanche un **cas de `422`** — un code que la
  route rendait déjà, sur d'autres motifs, et que le contrat ne décrit pas cas par cas.

## Vérification

**Deux dévoilements réels**, sur la base de la lane, solde relevé en base avant et après — le solde
de départ est de 25 crédits :

```
profil SANS e-mail  → HTTP 422  « Ce profil n’a rien à dévoiler sur cet accès. »   solde 25 → 25
profil AVEC e-mail  → HTTP 201                                                     solde 25 → 24
```

Avant ce changement, le premier appel répondait `201` et débitait un crédit contre rien.

**Suites jouées** — les impactées seulement :

```
RevealTest ............................... OK  23 tests, 258 assertions
NotificationEmissionTest + CandidateTest . OK  33 tests, 297 assertions
deptrac .................................. 0 violation
api/openapi.json ......................... régénéré, git status muet
```

**Non observé** : la vérification du solde **après** exclusion est déduite du diff — la garde
s'applique avant la comparaison —, faute d'un appel dédié qui la mette en évidence.

## Livré

- `api/src/Domain/Candidate/RevealableContent.php` **(+)** — la règle de présence des quatre canaux,
  écrite une fois : les coordonnées avec leur repli sur le réplica Jarvi, le CV jugé sur
  l'identifiant de fichier, le compte rendu sur son texte.
- `api/src/Domain/Credit/ProfileRevealer.php` **(M)** — seconde exclusion cumulée à l'idempotence ;
  le lot rend ce qui a été dévoilé et non ce qui a été demandé ; `reveal()` devient nullable.
- `api/src/State/Processor/RevealProcessor.php` **(M)** — `422` explicite quand un profil seul n'a
  rien à servir.
- `api/tests/Functional/RevealTest.php` **(M)** — six cas neufs ; le raccourci d'accès aux candidats
  cesse d'être écrit deux fois.
- `dev-book/cadrage/entreprise/api/credits_et_devoilement.md` **(M)** — l'invariant « jamais à
  vide », le tableau canal par canal, et les deux exclusions du lot.

## Reste à faire (hors périmètre)

- **Mesurer en production** combien de crédits ont été facturés sur un canal vide — voir *Risque
  assumé*. Cette carte ne répare pas le passé.
- **`API-064`** puis **`ENT-132`** : sans elles, l'écran continue d'annoncer un coût pour un canal
  que le serveur refusera de facturer.

## Définition de terminé

- [x] Un lot mixte ne facture que les profils qui ont la donnée sur le canal demandé.
- [x] Aucune ligne de dévoilement n'est créée pour un profil dont le canal est vide.
- [x] Un lot entièrement vide répond en succès, sans débit, sans ligne.
- [x] Le dévoilement à l'unité d'un canal vide répond `422`, sans débit ni ligne.
- [x] Un profil rencontré **sans compte rendu écrit** n'est pas facturable sur ce canal.
- [x] Un CV dont l'unique entrée ne porte pas de fichier n'est pas facturable.
- [x] Le solde est vérifié sur le coût **après** exclusion — déduit du diff, la garde s'applique
      avant la comparaison au solde ; non observé par un appel dédié.
- [x] La règle de présence est implémentée **une seule fois**, et le cadrage la dit une fois.
- [x] `api/openapi.json` est inchangé — contrat régénéré, `git status` muet.
- [x] La suite fonctionnelle du dévoilement reste verte — `RevealTest` 23/23,
      `NotificationEmissionTest` + `CandidateTest` 33/33, deptrac 0 violation.
