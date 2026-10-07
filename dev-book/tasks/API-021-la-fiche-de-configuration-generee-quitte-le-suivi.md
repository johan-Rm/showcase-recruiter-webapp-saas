# TASK API-021 — api : la fiche de configuration générée quitte le suivi

- **Créée le** : 2026-08-02.
- **Session** : `1459cc78-6e98-480f-98ee-3583c02ec934`.
- **Statut** : ✅ fait le 2026-08-02 — le fichier est ignoré et retiré du suivi, il reste sur le
  disque. Régénération automatique et neutralité de la dérive prouvées au conteneur ; gates au
  passage PR.
- **Type** : chore — hygiène du dépôt.
- **Tests** : sans objet — le diff est une ligne de `.gitignore` et un retrait du suivi. Aucun code
  exécutable n'est produit ni modifié, et le fichier concerné n'est lu par aucun test. Preuve par
  suppression / régénération observée et par l'état de l'arbre après un démarrage de stack.
- **Réf** : `API-015` (dernière régénération en date, sous l'environnement de test) ·
  [bundles.php](../../api/config/bundles.php) (l'enregistrement du bundle qui explique la dérive) ·
  `ADM-011`, `ADM-012`, `ADM-013`, dont les cartes signalent toutes ce fichier en reste-à-faire.

## Contexte

`api/config/reference.php` est **généré par Symfony au réchauffement du cache** — son propre en-tête
le dit : « This file is auto-generated and is for apps only ». Son contenu dépend des bundles
enregistrés dans **l'environnement** où ce réchauffement a lieu, or `DAMADoctrineTestBundle` n'est
enregistré qu'en `test`. Le fichier oscille donc entre deux versions :

- la version **commitée** vient de `test` (produite par `API-015`) et contient le bloc
  `dama_doctrine_test` ;
- tout démarrage de stack réchauffe en `dev` et le réécrit **sans** ce bloc.

Chaque `make dev-up-space` salit ainsi l'arbre de sept lignes, chaque passage de tests le rétablit.
Le fichier a été recommité **six fois** depuis le premier commit du dépôt, dont trois pour la seule
raison qu'il avait été régénéré. Il n'est jamais entré volontairement : il est arrivé avec
l'échafaudage Symfony du 2026-06-17.

Le coût n'est pas cosmétique. Un arbre sale **bloque `/apres-pr-mergee`**, oblige à un ménage avant
chaque livraison — trois restaurations pour la seule journée du 2026-08-02 — et fait rapporter par
`/etat-des-lanes` un « travail en cours » qui n'appartient à personne.

## Périmètre

- **`api/.gitignore`** : le fichier y entre.
- **Le retrait du suivi** (`git rm --cached`), qui le laisse sur le disque.

## Hors périmètre

- **L'enregistrement des bundles** : `DAMADoctrineTestBundle` reste réservé à `test`. L'activer en
  `dev` alignerait les deux générations, mais au prix d'un bundle d'isolation de tests chargé en
  développement — le remède serait pire que le mal.
- **Une gate CI sur ce fichier**, du type de celle qui protège `api/openapi.json` : elle
  transformerait une dérive silencieuse en échec régulier, puisque tout démarrage de stack le
  modifie.
- **Les autres fichiers générés déjà ignorés** (`phpstan.neon`, `phpunit.xml`, `.php-cs-fixer.php`,
  `var/`, caches) : rien à changer, ils sont déjà hors suivi.

## Décisions de cadrage

- **On supprime la cause, pas le symptôme.** Les trois alternatives — figer sur `test`, activer le
  bundle en `dev`, ou continuer à restaurer à la main — laissent toutes le fichier osciller. Seul
  le retrait du suivi rend l'oscillation inoffensive.
- **Cohérent avec ce que le dépôt fait déjà** : `api/.gitignore` écarte `phpstan.neon`,
  `phpunit.xml` et `.php-cs-fixer.php`, tous produits par les recettes Flex. `reference.php` est le
  seul de cette famille resté suivi, et rien n'indique un choix — plutôt un oubli d'origine.
- **Le fichier reste sur le disque et garde son utilité** : il fournit les formes de tableau à
  l'analyse statique et à l'autocomplétion. On cesse de le versionner, on ne s'en prive pas.
- **Aucune gate ne s'appuie dessus** — vérifié dans `.github/workflows/`, `composer.json`,
  `phpstan.neon.dist` et les Makefile : le fichier n'y est cité nulle part.

## Règles et cas limites

- **Le fichier disparaîtra des autres arbres au prochain `git pull`.** Ce n'est pas une perte : il
  se régénère au premier réchauffement de cache, donc au premier démarrage de stack ou au premier
  `bin/console` lancé.
- **Un développeur qui n'a pas de stack** ne l'aura pas tant qu'il n'aura pas lancé une commande
  Symfony. Son absence ne casse ni la build, ni les tests, ni l'analyse statique — elle prive
  seulement l'IDE d'une aide à la saisie, jusqu'au premier warmup.
- **Ne pas le supprimer du disque** dans les arbres existants : `git rm --cached` seulement.

## Documentation impactée

Aucune. Aucune page de `docs/showcase-recruiter-webapp-saas/` ne décrit ce fichier ; les cartes qui le signalaient le
faisaient en *Reste à faire*, ce que cette tâche referme.

## Livré

- **`api/.gitignore`** : `/config/reference.php` entre dans le bloc du framework, avec le commentaire
  qui dit **pourquoi** — un fichier régénéré dont le contenu dépend de l'environnement.
- **Le suivi est retiré** par `git rm --cached` : le fichier disparaît du dépôt, pas du disque.

## Vérification

Trois faits observés, aucun supposé.

- **La régénération est automatique.** Fichier supprimé dans le conteneur API, puis
  `php bin/console cache:warmup` → *« Cache for the "dev" environment was successfully warmed »* et
  le fichier revient, **121 586 octets, à l'identique**. L'ignorer ne prive donc personne de rien.
- **La dérive est devenue invisible à git.** Le fichier a été modifié à la main après le retrait du
  suivi : `git status` ne le mentionne plus. Seules restent les trois lignes de cette tâche.
- **Aucune configuration ne le référence** — `grep` sur `.github/workflows/`, `composer.json`,
  `phpstan.neon.dist`, `deptrac.yaml` et les Makefile : zéro occurrence.
- **La cause est confirmée par le code** : `DAMADoctrineTestBundle` n'est déclaré que pour `test`
  dans `bundles.php`, et c'est exactement le bloc de sept lignes qui oscillait.

## Reste à faire (hors périmètre)

- **Les arbres de travail existants** verront le fichier passer en non suivi à leur prochain rebase
  sur `develop`. Rien à faire : il est déjà sur leur disque.

## Définition de terminé

- [x] `api/config/reference.php` n'est plus suivi par git, et le fichier est toujours sur le disque.
- [x] Un démarrage de stack ne salit plus l'arbre.
- [x] La régénération automatique est prouvée : fichier supprimé, réchauffement du cache, fichier
      revenu à l'identique.
- [x] Aucune gate ni configuration ne référence le fichier — vérifié, pas supposé.
- [ ] Conventions, lint, format, type-check et tests API verts — **au passage PR**.
