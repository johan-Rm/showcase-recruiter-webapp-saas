# TASK API-071 — les accès disent lequel est le login

- **Créée le** : 2026-08-18.
- **Session** : `1c04995a-7633-4e0b-869c-95fbe9a4a23c`.
- **Statut** : ✅ fait le 2026-08-18 — chaque accès affiche son identifiant et son mot de passe sur
  deux lignes étiquetées, dans les deux versions du message. Rendu **regardé** en thème clair et en
  thème sombre.
- **Type** : fix — le bloc d'accès affiche deux valeurs côte à côte sans dire ce qu'elles sont.
- **Tests** : le test fonctionnel existant du gabarit (`DeployNotificationEmailTest`) **étendu** :
  les deux étiquettes présentes dans les deux versions du message. La garde d'absence reste ce
  qu'elle est — un bloc non fourni ne doit toujours rien rendre.
- **Réf** : `API-065`, qui a introduit le bloc · fichiers :
  [transactional.html.twig](../../api/templates/emails/transactional.html.twig),
  [transactional.txt.twig](../../api/templates/emails/transactional.txt.twig),
  [DeployNotificationEmailTest.php](../../api/tests/Functional/DeployNotificationEmailTest.php).

## Contexte

Le mail de fin de déploiement joint les accès de la préproduction. Chaque entrée s'affiche ainsi :

```
Boîte aux lettres de test
https://mailpit.preprod.showcase-recruiter-webapp-saas.example
showcase-recruiter-webapp-saas · change-me-demo-password
```

**Rien ne dit lequel des deux est l'identifiant et lequel est le mot de passe.** Le lecteur devine —
et il devine juste, tant que l'identifiant ressemble à une adresse e-mail. Sur `showcase-recruiter-webapp-saas · change-me-demo-password`,
il n'y a plus d'indice : deux suites de caractères, séparées par un point.

Le mail est écrit pour des collaborateurs qui ne connaissent pas l'outil. Leur demander de deviner
est exactement ce que ce bloc est censé leur épargner — et un mot de passe recopié à la place d'un
identifiant produit un refus de connexion qu'ils attribueront au site.

La forme attendue est celle que l'humain a écrite en toutes lettres :

```
https://mailpit.preprod.showcase-recruiter-webapp-saas.example
login : showcase-recruiter-webapp-saas
mot de passe : change-me-demo-password
```

## Périmètre

- **Les deux gabarits** : chaque accès affiche son identifiant et son mot de passe sur **deux lignes
  distinctes**, chacune précédée de son étiquette.
- **Le test existant étendu** aux deux étiquettes, dans les deux versions du message.

## Hors périmètre

- **Le reste du bloc** : le libellé et l'adresse restent tels quels, ils ne posaient pas de problème.
- **Les quatre e-mails du produit** : ils ne fournissent aucun accès, rien ne change pour eux.
- **Le script d'exploitation** : il fournit déjà les quatre champs séparément. Aucune modification
  côté outillage, aucun réglage à retoucher sur le serveur.
- **La question de savoir si des mots de passe doivent voyager par e-mail** : tranchée au cadrage
  d'`OPS-035`, pour une préproduction de recette, avec son double verrou.

## Décisions de cadrage

### Deux lignes plutôt qu'une

Une seule ligne avec deux étiquettes tiendrait à l'écran mais casserait sur un téléphone, là où le
bloc est le plus souvent lu. Deux lignes courtes se lisent partout, et se recopient une par une —
ce que fait réellement quelqu'un qui se connecte.

### Les mots exacts de l'humain

« login » et « mot de passe », pas « identifiant » ni « utilisateur ». C'est ce qui est écrit sur les
écrans de connexion du produit : un mail qui emploie d'autres mots que le formulaire oblige à faire
la traduction soi-même.

## Règles et cas limites

- **La garde d'absence ne bouge pas.** Aucun accès fourni, aucun bloc rendu : c'est elle qui empêche
  un mot de passe d'apparaître dans un e-mail envoyé à un candidat, et elle reste testée.
- **Les deux versions du message changent ensemble.** La version texte n'est pas décorative : c'est
  elle que lisent les clients qui refusent la mise en forme, et les filtres anti-indésirables.
- **Le mot de passe reste sélectionnable d'un bloc** : pas de mise en forme qui insère un espace ou
  coupe la chaîne, sinon le copier-coller ramène une valeur fausse.

## Documentation impactée

Aucune. Aucune convention, aucun contrat, aucune route. La tâche change deux lignes d'affichage.

## Définition de terminé

- [x] Chaque accès affiche `login :` et `mot de passe :` sur deux lignes, dans les deux versions.
- [x] Le test du gabarit vérifie les deux étiquettes, dans les deux versions.
- [x] La garde d'absence reste verte : aucun accès fourni, aucun bloc rendu.
- [x] Le rendu est regardé, en thème clair et en thème sombre.

## Vérification

| Ce qui a été lancé | Résultat |
| --- | --- |
| `DeployNotificationEmailTest` | **5 tests, 31 assertions, vertes** — dont la garde d'absence, intacte |
| Validité des gabarits | **les 2 valides** |
| Style du test étendu | **0 à corriger** |
| Message composé, version texte relue | les trois accès étiquetés, un par bloc de quatre lignes |
| Rendu regardé, thèmes clair et sombre | les étiquettes lisibles, le mot de passe d'un seul tenant |

Ce que le destinataire lit désormais :

```
Vos accès

Boîte aux lettres de test
https://mailpit.preprod.showcase-recruiter-webapp-saas.example
login : showcase-recruiter-webapp-saas
mot de passe : change-me-demo-password
```

**La garde d'absence est le contrôle qui comptait le plus**, et elle n'a pas bougé : le test qui
cherche un mot de passe dans un message qui n'en demandait pas reste vert.

## Livré

- `api/templates/emails/transactional.html.twig` (M) — deux lignes étiquetées à la place d'une
  ligne à deux valeurs. Le commentaire dit pourquoi le point séparateur ne suffisait pas.
- `api/templates/emails/transactional.txt.twig` (M) — le même changement en texte brut.
- `api/tests/Functional/DeployNotificationEmailTest.php` (M) — le test vérifie désormais les
  étiquettes, pas seulement les valeurs.

## Reste à faire (hors périmètre)

- **Un redéploiement de la préproduction** est nécessaire pour que le changement se voie : le
  message est composé par l'application, donc par l'image en ligne.
