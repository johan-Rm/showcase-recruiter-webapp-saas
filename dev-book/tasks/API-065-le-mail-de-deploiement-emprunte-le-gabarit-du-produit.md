# TASK API-065 — le mail de déploiement emprunte le gabarit du produit

- **Créée le** : 2026-08-18.
- **Session** : `1c04995a-7633-4e0b-869c-95fbe9a4a23c`.
- **Statut** : ✅ fait le 2026-08-18 — le gabarit rend les deux blocs, une commande compose le
  message complet sans l'envoyer, et le rendu a été **regardé pour de vrai** dans les deux thèmes.
  Le test d'absence a été **éprouvé par mutation** : garde-fou retiré, deux tests tombent — dont
  celui qui cherche le mot de passe lui-même.
- **Type** : feat — le gabarit unique des e-mails accueille deux blocs qu'il ne sait pas rendre, et
  une commande compose le message de fin de déploiement sans l'envoyer.
- **Tests** : **le premier test de cette mécanique**, fonctionnel, sur le message produit — les deux
  blocs rendus quand ils sont fournis, **absents** quand ils ne le sont pas, dans la version mise en
  forme **comme** dans la version texte. C'est l'absence qui compte le plus : un bloc d'accès qui
  apparaîtrait alors qu'il ne devait pas est le seul défaut vraiment coûteux de cette tâche, et il
  ne se voit pas à l'œil — on ne remarque pas ce qui n'est pas là.
- **Réf** : aval `OPS-035`, qui appelle cette commande et lui fournit ce qu'elle raconte · fichiers :
  [templates/emails/transactional.html.twig](../../api/templates/emails/transactional.html.twig),
  [transactional.txt.twig](../../api/templates/emails/transactional.txt.twig),
  [TransactionalEmail.php](../../api/src/Domain/Email/TransactionalEmail.php),
  [TransactionalMailer.php](../../api/src/Domain/Email/TransactionalMailer.php).

## Contexte

Le déploiement de la préproduction envoie un e-mail quand il a réussi. Il est en texte brut, écrit
dans le vocabulaire du métier, et ne ressemble à aucun des quatre e-mails que showcase-recruiter-webapp-saas envoie
réellement — lien de connexion, mot de passe, réinitialisation, invitation.

Or ces quatre-là partagent **un gabarit unique**, et il est soigné : fond clair ou sombre selon le
lecteur, logo attaché au message plutôt que chargé à distance — beaucoup de messageries bloquent les
images distantes —, mise en page en tableaux imbriqués parce qu'un client de messagerie répandu ne
sait pas mettre en page autrement, et une version texte sans laquelle un message perd des points
auprès des filtres anti-indésirables.

**Le mail de déploiement doit lui ressembler, et la seule façon honnête d'y arriver est de lui
emprunter ce gabarit** plutôt que d'en recopier cent cinquante lignes ailleurs. Une copie
divergerait au premier changement, sans que personne ne s'en aperçoive avant de rouvrir le message.

### Ce que le gabarit ne sait pas encore rendre

Deux blocs manquent, et aucun des quatre émetteurs actuels n'en avait besoin :

- **la liste de ce qui a changé** — les intitulés des travaux livrés par ce déploiement, en français
  courant. Le gabarit ne rend aujourd'hui que des paragraphes, où une liste à puces se lirait mal ;
- **le bloc d'accès** — les adresses de la préproduction avec leurs identifiants, pour aller vérifier
  sans chercher ailleurs. Trois colonnes, pas une phrase.

### Pourquoi une commande qui ne poste rien

Le mot de passe d'envoi vit sur le serveur d'exploitation, **pas** dans l'image de l'application, et
c'est délibéré : en préproduction l'expéditeur de l'application est détourné vers un attrape-mails
pour qu'aucun message n'atteigne de vraies personnes pendant la recette. La commande **compose et
rend** le message complet — en-têtes et logos attachés compris —, l'envoi restant au script
d'exploitation qui, lui, détient de quoi expédier.

Elle ne peut pas non plus calculer elle-même la liste des changements : l'image ne contient que le
code, jamais l'historique du dépôt. Elle reçoit donc ce qu'elle doit raconter.

## Périmètre

- **Le gabarit** — les deux blocs, dans la version mise en forme **et** dans la version texte, tous
  deux facultatifs et absents par défaut.
- **Le contrat de contenu** — l'objet qui nomme les champs des gabarits accueille les deux nouveaux,
  puisqu'il est le seul endroit qui les nomme.
- **La mécanique** — de quoi obtenir le message composé sans l'envoyer, là où il n'existe
  aujourd'hui qu'un envoi.
- **Une commande en ligne de commande** qui lit sur son entrée standard, en JSON, tout ce que le mail
  raconte — destinataires, expéditeur, titre, texte, changements, accès —, et rend le message complet
  sur sa sortie.
- **Un test fonctionnel** sur le message produit.

## Hors périmètre

- **L'envoi**, et le calcul de la liste des changements : c'est `OPS-035`, sur sa lane, avec son
  commit. Cette carte ne sait rien du serveur de messagerie ni de l'historique du dépôt.
- **Le garde-fou qui interdit les accès hors préproduction** : il vit là où l'environnement est
  connu, c'est-à-dire côté exploitation. La commande rend ce qu'on lui donne — c'est ce qui la rend
  testable, et ce qui interdit de lui faire confiance seule.
- **Les quatre e-mails existants** : ils ne fournissent aucun des deux blocs, donc rien ne change
  pour eux. Toute différence dans leur rendu après cette tâche est un défaut.
- **La traduction** : les cinq émetteurs écrivent leur texte en français, en dur. La notification
  suit la règle en vigueur ; la changer serait un sujet à soi seul.
- **Le contrat d'API** : une commande en ligne de commande n'ouvre aucune route. Rien à régénérer.

## Décisions de cadrage

### Emprunter le gabarit plutôt que le recopier

Trois voies existaient : recopier la mise en forme dans le script d'exploitation, écrire un habillage
plus simple propre à ce mail, ou emprunter le gabarit. La copie a été écartée pour ce qu'elle coûte
dans le temps — cent cinquante lignes qui divergent en silence, et l'attachement des deux logos à
réécrire à la main là où l'application le fait en deux lignes. L'habillage simplifié l'a été parce
que la demande est explicitement « comme les autres mails ».

Le prix de l'emprunt est nommé : deux tâches sur deux lanes au lieu d'une, et une notification qui
dépend de l'application. Cette dépendance ne coûte rien ici — la commande n'est appelée qu'après la
vérification de santé, donc à un instant où l'application répond, par construction.

### Composer sans envoyer

La commande rend le message, elle ne le poste pas. Deux raisons, et la seconde est la plus solide :
le mot de passe d'envoi n'a rien à faire dans l'image de l'application ; et une commande sans effet
de bord **se teste**, là où une commande qui envoie demande un serveur de messagerie pour être
éprouvée.

### Recevoir en JSON, sur l'entrée standard

Les mots de passe de recette font partie de ce qui est transmis : ils ne peuvent pas passer par la
ligne de commande, où ils seraient visibles de tout compte de la machine le temps de l'exécution.
L'entrée standard est donc imposée, la forme ne l'est pas — un format en lignes aurait été plus
simple à fabriquer côté script.

Le JSON a été retenu pour ce qu'il apporte **ici** : une donnée mal formée est détectée à la lecture
et arrête la commande, là où une ligne mal formée produirait silencieusement un mail incomplet. Le
coût est reporté sur `OPS-035`, à qui il revient de fabriquer ce bloc — et un défaut d'échappement
avait déjà cassé une version antérieure de ce mail, ce qui est précisément la raison d'exiger une
preuve par un essai réel de ce côté-là.

### Les deux blocs sont facultatifs, et absents par défaut

Le gabarit sert cinq émetteurs qui n'en veulent pas. Ils ne doivent rien voir changer, et un bloc
qui apparaîtrait sans avoir été demandé serait, pour le bloc d'accès, une fuite de mots de passe.
D'où un test qui vérifie l'absence autant que la présence.

## Règles et cas limites

- **Les quatre e-mails existants ne changent pas d'un pixel.** C'est vérifiable : ils ne fournissent
  aucun des deux blocs nouveaux.
- **Le bloc d'accès n'est jamais rendu de sa propre initiative.** Absent des données reçues, absent
  du message — dans les deux versions.
- **La version texte n'est pas décorative.** Un message sans elle perd des points auprès des filtres
  anti-indésirables, et c'est elle que lisent les clients qui refusent la mise en forme. Les deux
  blocs doivent y figurer, dans une forme lisible sans mise en page.
- **Une liste de changements vide est un cas normal**, pas une erreur : un déploiement sans nouveau
  code existe. Le bloc s'efface alors, et c'est à l'appelant de dire qu'il n'y a rien de neuf.
- **Le rendu du fond sombre est porté par la même règle que le reste du gabarit** : les deux blocs
  doivent en hériter, sans quoi un lecteur en thème sombre verrait deux zones incohérentes.
- **Aucune donnée reçue n'est de confiance** : elle vient d'un script, elle finit dans du HTML. Le
  gabarit échappe déjà ce qu'il rend ; ne rien introduire qui contourne cette protection.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/`. Aucune convention ne change, aucune route n'est ouverte, aucun
contrat public n'est touché. Le commentaire d'en-tête des gabarits et celui de l'objet de contenu
portent déjà l'explication de leur mécanique : ce sont eux qui accueillent la description des deux
blocs, à l'endroit où on les lit.

## Définition de terminé

- [x] Le gabarit rend une liste de changements et un bloc d'accès, dans les deux versions du message.
- [x] Les deux blocs sont **absents** quand ils ne sont pas fournis, prouvé par test — et le test
      lui-même prouvé par mutation.
- [x] L'objet qui nomme le contrat des gabarits porte les deux champs nouveaux.
- [x] Une commande lit un bloc JSON sur son entrée standard et rend le message complet sur sa sortie,
      logos attachés compris.
- [x] Rien n'est envoyé par cette commande, et aucun mot de passe ne passe par la ligne de commande.
- [x] Le rendu des quatre e-mails existants est inchangé — les cinq suites qui les envoient sont
      vertes.
- [x] Le fond sombre couvre les deux blocs nouveaux.

## Vérification

Sorties lues, jamais supposées.

| Ce qui a été lancé | Résultat |
| --- | --- |
| `DeployNotificationEmailTest` | **5 tests, 31 assertions, vertes** |
| Les 5 suites qui envoient réellement un e-mail, plus celle-ci | **35 tests, 184 assertions, vertes** |
| **Mutation** : garde-fou du bloc d'accès retiré | **2 échecs** — l'absence est bien gardée |
| Message composé, relu par un analyseur de courrier | `multipart/related` → `multipart/alternative` → texte + HTML, **2 images attachées** |
| Références d'images du HTML | égales aux identifiants des pièces jointes — le logo s'affiche sans téléchargement |
| Sujet accentué | décodé correctement : « La préproduction est à jour » |
| Entrée volontairement incomplète | `Entrée incomplète : « to » manque.` — refus net, pas de message bancal |
| `lint:twig`, style, analyse statique | gabarits valides · 0 à corriger · **0 erreur** |

**Le rendu, regardé.** Message composé, logos décodés, ouvert dans un navigateur en thème clair et
en thème sombre. Le logo bascule du vert profond au lime, le bouton suit, les puces prennent la
couleur d'accent, et la ligne de séparation isole le bloc d'accès. C'est la seule preuve qui vaille
pour une tâche d'apparence : une suite verte n'a jamais dit qu'un e-mail était beau.

**La mutation est la preuve qui compte.** Un test d'absence qui n'a jamais échoué ne garde rien. La
condition du bloc d'accès a été retirée, comme l'aurait fait un remaniement distrait : deux tests
sont tombés, dont celui qui cherche le mot de passe et non le titre du bloc — c'est lui qui
survivrait à un simple renommage.

## Livré

- `api/src/Domain/Email/EmailAccess.php` (+) — un accès : où aller, avec quoi entrer. Son
  commentaire dit à quoi il est réservé et où vit le garde-fou, qui n'est pas ici.
- `api/src/Domain/Email/TransactionalEmail.php` (M) — le contrat des gabarits porte `changes` et
  `accesses`, vides par défaut. Placés après les paragraphes qu'ils prolongent : les cinq émetteurs
  construisent en arguments nommés, l'ordre ne les concerne pas.
- `api/src/Domain/Email/TransactionalMailer.php` (M) — `compose()` extrait de `send()`, qui l'appelle.
  Le commentaire dit pourquoi l'expéditeur n'y est pas posé : un appelant qui sérialise sans envoyer
  doit le poser lui-même, sinon le message refuse de se rendre.
- `api/templates/emails/transactional.html.twig` (M) — les deux blocs, en tables plutôt qu'en listes
  (les clients de messagerie appliquent aux listes leurs propres marges), héritant de la bascule
  claire/sombre déjà en place.
- `api/templates/emails/transactional.txt.twig` (M) — leurs pendants en texte brut.
- `api/src/Command/ComposeDeployNotificationCommand.php` (+) — lit un objet JSON sur son entrée,
  rend le message sur sa sortie, les diagnostics sur la sortie d'erreur. Refuse une entrée
  incomplète au lieu de composer un message à trous.
- `api/tests/Functional/DeployNotificationEmailTest.php` (+) — le premier test de cette mécanique.

## Reste à faire (hors périmètre)

- **`OPS-035`** — le script qui calcule les changements, mémorise le dernier envoi, applique le
  garde-fou d'environnement et expédie. Sans elle, cette commande n'est appelée par personne.
- **Le garde-fou d'environnement**, qui vit côté exploitation : cette commande rend ce qu'on lui
  donne, et c'est ce qui la rend testable.
