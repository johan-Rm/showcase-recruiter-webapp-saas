# TASK OPS-035 — la notification raconte ce qui a changé depuis la dernière

- **Créée le** : 2026-08-18.
- **Session** : `1c04995a-7633-4e0b-869c-95fbe9a4a23c`.
- **Statut** : ✅ fait le 2026-08-18 — le mail dit ce que la mise en ligne a apporté, en français
  courant, et joint les accès de recette sous double verrou. Éprouvé sur un banc d'essai complet :
  dépôt jetable avec son historique, serveur de messagerie factice exigeant une authentification, et
  **le vrai appel à l'application**. Le banc a trouvé **deux défauts que la relecture n'avait pas
  vus**, dont un grave — cf. *Vérification*.
- **Type** : feat — la notification de fin de déploiement gagne son contenu et son habillage, et perd
  ce qui ne parlait qu'aux techniciens.
- **Tests** : **aucune suite automatisée** — le dépôt n'en a pas pour les scripts shell, et en écrire
  une supposerait d'introduire un cadre de test pour un script de cent lignes. La preuve est
  l'**exécution réelle** des chemins : envoi nominal contre un serveur de messagerie, absence de
  changement depuis le dernier envoi, bloc d'accès refusé hors préproduction, et une liste de
  changements contenant apostrophes et guillemets — c'est exactement ce qui avait cassé une version
  antérieure de ce mail, et ce qui doit être démontré plutôt qu'affirmé.
- **Réf** : amont **`API-065`**, qui compose le message — cette carte ne peut pas être livrée avant ·
  `OPS-033`, qui a posé la notification · fichiers :
  [scripts/notify-deploy.sh](../../scripts/notify-deploy.sh),
  [.env.example](../../.env.example),
  [docker-and-make.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md).

## Contexte

La notification de fin de déploiement fonctionne — elle est partie pour de vrai le 2026-08-17, par le
compte mail du domaine, à la fin d'un déploiement réussi. Mais son contenu a été écrit pour un
technicien, et il manque l'essentiel.

**Ce qu'elle dit de trop.** Elle nomme la machine, et elle énumère les étapes franchies : la
construction des images, le démarrage des conteneurs, les clés de connexion, les files de messages,
la vérification de santé. Rien de tout cela n'apprend quoi que ce soit à celui qui la reçoit : si le
mail est parti, ces étapes sont passées, c'est la définition même de son envoi.

**Ce qu'elle ne dit pas.** Elle annonce qu'un déploiement a réussi sans dire **ce qui a changé**.
Or c'est la seule question que se pose le lecteur, et la réponse existe : les intitulés des travaux
livrés sont écrits en français courant dans ce dépôt — « une recherche large se signale, sans rien
empêcher », « le menu éteint les canaux sans donnée et dit pourquoi ». Il suffit de retirer leur
emballage technique, un préfixe de type et une référence de tâche, pour obtenir une liste lisible
par n'importe qui.

**Ce qu'elle oblige à chercher ailleurs.** Le mail invite à aller voir, sans donner les accès. La
préproduction en demande trois — l'espace entreprise, l'administration, la boîte aux lettres de
recette — chacun avec son identifiant.

### La difficulté réelle : savoir ce qui est nouveau

Au moment où le mail part, le script sait où en est le code ; il ignore d'où il venait. L'écart
apporté par la récupération du code a défilé à l'écran quelques minutes plus tôt, puis s'est perdu.

Deux façons de le retrouver, qui ne répondent pas à la même question. S'appuyer sur ce que l'outil de
gestion de versions mémorise du dernier téléchargement ne coûte rien à entretenir, mais répond à
« qu'a apporté la dernière récupération » : deux déploiements successifs sans nouveau code
enverraient deux fois la même liste, comme si le travail avait été livré deux fois. Se souvenir du
dernier envoi répond à la bonne question — « qu'est-ce qui est nouveau depuis que je t'ai écrit ».

## Périmètre

- **Retirer** de l'e-mail la machine et l'énumération des étapes techniques.
- **Ajouter** la liste des changements, débarrassée de son emballage : préfixe de type et référence
  de tâche retirés, une puce par intitulé.
- **Mémoriser**, sur le serveur, l'état du code au moment où un mail est **effectivement parti**, et
  ne mettre cette mémoire à jour qu'en cas de succès.
- **Ajouter** le bloc d'accès, alimenté par les réglages du serveur, avec son double verrou.
- **Confier la mise en forme à l'application** : appeler la commande d'`API-065` avec ce que le mail
  raconte, en JSON sur son entrée standard, et expédier le message qu'elle rend.
- **Documenter** les réglages nouveaux et le comportement, dans le fichier d'exemple et la page
  d'infrastructure.

## Hors périmètre

- **La mise en forme elle-même** : c'est `API-065`. Ce script ne fabrique plus de message, il
  fournit ce qu'il raconte et transporte ce qui lui est rendu.
- **La notification en cas d'échec**, toujours écartée : le mail ne part que sur un déploiement
  entièrement passé.
- **La production** : ni la chaîne de déploiement de production, ni le bloc d'accès qui, là-bas,
  doit rester impossible.
- **La réécriture des intitulés obscurs.** Certains messages de commit resteront opaques pour un
  non-technicien — « la route d'état ne porte plus que l'état ». Le script retire l'emballage, il ne
  réécrit pas la phrase : cela se joue au moment où le message est écrit.

## Décisions de cadrage

### Se souvenir du dernier envoi, plutôt que de la dernière récupération

Retenu pour trois comportements que l'autre voie n'a pas. La mémoire ne se met à jour que si l'envoi
a réussi : un mail perdu n'emporte pas avec lui ce qu'il devait annoncer, le suivant couvre les deux
déploiements. Un redéploiement sans nouveau code se distingue franchement d'une livraison — le mail
dit qu'il n'y a rien de neuf, ce qui est une information en soi. Et le récit reste continu, sans trou
ni répétition.

Le coût est un petit fichier de mémoire sur le serveur, hors du dépôt.

### Le garde-fou vit ici, pas dans l'application

Le bloc d'accès ne part que si les identifiants sont renseignés **et** si l'adresse du site est une
préproduction. Ce second verrou est posé de ce côté-ci parce que c'est ici que l'environnement est
connu — la commande de mise en forme, elle, rend ce qu'on lui donne, et c'est ce qui la rend
testable.

Le verrou est une **liste d'autorisation, pas une liste d'interdiction** : le bloc n'est inclus que
pour une adresse reconnue comme préproduction, tout le reste étant refusé. Un environnement futur
qui porterait un autre nom serait donc traité comme une production — c'est le sens de refus qu'on
veut quand ce sont des mots de passe qui sont en jeu.

### Le refus se voit, il ne se devine pas

Un bloc d'accès écarté par le garde-fou est **annoncé à l'écran** pendant le déploiement. Un
garde-fou silencieux qui se déclenche à tort passe pour une panne ; annoncé, il s'explique.

### L'identifiant de version reste, discrètement

Sept caractères en pied de message, sans étiquette technique. Personne ne les lit au quotidien, et
c'est la seule information qui permette de dire précisément quel code tournait le jour où quelque
chose cloche — la liste des changements ne le dit pas.

### Une liste plafonnée

Au-delà d'une quinzaine de changements, la liste est coupée et le reste compté. Une fournée de
quarante lignes n'est plus lue ; et le nombre annoncé dit qu'il en manque, là où une coupe silencieuse
laisserait croire à l'exhaustivité.

## Règles et cas limites

- **Aucun mot de passe ne passe par la ligne de commande**, ni celui de l'envoi ni ceux des accès :
  tout transite par l'entrée standard ou un fichier de configuration temporaire. C'est la règle déjà
  posée par `OPS-033` et elle s'étend au bloc d'accès.
- **Les mots de passe des accès s'écrivent entre apostrophes** dans le fichier de réglages, comme
  celui de l'envoi : ce fichier est lu par un shell, et un guillemet nu y vide la valeur entière sans
  aucun message d'erreur.
- **Aucun identifiant n'est écrit dans le dépôt.** Le fichier d'exemple porte les variables vides et
  leur explication, jamais une valeur.
- **Premier envoi, sans mémoire préalable** : la liste ne peut pas être calculée. Le dire plutôt que
  d'inventer une étendue, et poser la mémoire pour la fois suivante.
- **Aucun changement depuis le dernier envoi** : le mail part quand même — le déploiement a bien eu
  lieu — et l'annonce en une phrase.
- **L'application indisponible** : la commande de mise en forme échoue. Le déploiement, lui, a
  réussi ; l'échec est signalé et la sortie reste à zéro, comme pour un envoi raté. Le cas est
  théorique — la vérification de santé passe juste avant —, il ne doit pas pour autant faire échouer
  un déploiement.
- **La liste des changements est du texte libre** : apostrophes, guillemets, caractères accentués.
  Elle traverse un bloc JSON fabriqué par un script — c'est le point fragile de cette tâche, et il se
  démontre par un essai, pas par une relecture.

## Documentation impactée

- `.env.example` — les réglages nouveaux : le bloc d'accès et sa règle d'écriture, avec
  l'avertissement sur les apostrophes déjà en place pour l'envoi.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md`, § *Déploiement & secrets* — ce que le
  mail contient désormais, la mémoire du dernier envoi et le double verrou. La page décrit
  aujourd'hui une notification qui ne dit que sa réussite.

## Définition de terminé

- [x] Le mail ne nomme plus la machine et n'énumère plus les étapes techniques.
- [x] Il liste les changements en français courant, emballage technique retiré, plafonnés avec le
      reste compté.
- [x] La liste couvre exactement ce qui s'est passé depuis le dernier envoi **réussi**.
- [x] Un déploiement sans nouveau code l'annonce en une phrase.
- [x] Le bloc d'accès n'apparaît qu'en préproduction et qu'une fois renseigné ; son refus est
      annoncé à l'écran.
- [x] L'identifiant de version figure en pied de message.
- [x] La mise en forme provient de l'application, aucune ligne de mise en forme n'est écrite ici.
- [x] Un essai réel prouve qu'une liste contenant apostrophes et guillemets arrive intacte.
- [x] Aucun identifiant n'est écrit dans le dépôt ; le fichier d'exemple et la page d'infrastructure
      disent la même chose que le script.

## Vérification

Un banc d'essai isolé : un dépôt jetable avec son propre historique, un serveur de messagerie
minimal qui exige une authentification et écrit ce qu'il reçoit, et **le vrai appel à la commande de
l'application** — la même, jouée sur le même code.

| Situation | Résultat |
| --- | --- |
| Premier envoi, aucune mémoire | le mail le dit, aucune liste, mémoire posée |
| Trois livraisons, dont deux à libellé hostile | les trois arrivent **intactes**, guillemets et antislashs compris |
| Redéploiement sans nouveau code | « Aucun changement depuis la dernière fois », bloc absent |
| Hors préproduction (`showcase-recruiter-webapp-saas.example`, environnement `prod`) | accès **refusés**, refus annoncé, mot de passe **absent** du message |
| Vingt livraisons | **15 listées + « et 5 autre(s) »** |
| Serveur de messagerie injoignable | avertissement, sortie 0, **mémoire NON mise à jour** |
| Application incapable de composer | avertissement, sortie 0 — le déploiement reste réussi |
| Mot de passe `aB3"x\y$z%25` | reçu **intact** par le serveur |

**Deux défauts trouvés par le banc, aucun par la relecture.**

1. **L'échappement ne s'appliquait pas aux textes d'une seule ligne.** Écrit `N` puis `$!ba`, `sed`
   exécute `N` même sur la dernière ligne : n'ayant rien à joindre, il imprime et **quitte avant les
   substitutions**. Or dans une liste, chaque entrée est une ligne unique — **aucun** intitulé
   n'était donc protégé, et le mail partait cassé dès qu'un message de commit contenait un
   guillemet. Mesuré :

   | | texte d'une ligne | texte de deux lignes |
   | --- | --- | --- |
   | avant | `le champ "nom" accepte C:\Users\x` — **rien d'échappé** | correctement échappé |
   | après | `le champ \"nom\" accepte C:\\Users\\x` | correctement échappé |

   C'est une variante du défaut déjà rencontré sur ce script (`OPS-033`) : la réunion des lignes est
   désormais **gardée** par `$!{N;ba}`, et le commentaire nomme les trois exigences plutôt que deux.

2. **Les phrases de repli étaient coupées en deux paragraphes**, parce qu'elles contenaient un
   retour à la ligne dans le code — or une entrée par ligne vaut un paragraphe. Remises d'un seul
   tenant, avec le commentaire qui dit pourquoi elles doivent le rester.

**Non vérifié, et nommé comme tel** : l'appel réel `docker compose … exec -T api` sur le serveur de
déploiement. Le banc a joué **la même commande sur le même code**, mais depuis l'hôte : ce qui reste
à éprouver là-bas est la ligne d'invocation du conteneur, et rien d'autre.

## Livré

- `scripts/notify-deploy.sh` (M) — le script dit ce qu'il y a à raconter, l'application compose, il
  expédie. Mémoire du dernier envoi réussi, liste plafonnée, double verrou sur les accès,
  échappement JSON gardé.
- `.gitignore` (M) — la mémoire du dernier envoi reste hors du dépôt : elle décrit une machine, pas
  le code.
- `.env.example` (M) — `DEPLOY_NOTIFY_ACCESSES`, son format, et le double verrou en toutes lettres.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` (M) — le partage des trois rôles, la
  mémoire du dernier envoi et le garde-fou d'environnement.

## Reste à faire (hors périmètre)

- **Renseigner `DEPLOY_NOTIFY_ACCESSES`** dans le `.env` du serveur. Vide, le mail part sans le bloc.
- **La notification d'échec**, toujours écartée alors qu'elle porte l'essentiel de la valeur.
- **La production** : `prod-deploy` ne notifie rien. Le script est paramétré par environnement, mais
  le bloc d'accès y sera refusé — c'est voulu.
- **Les intitulés de commit obscurs** : le script retire l'emballage, il ne réécrit pas la phrase.
