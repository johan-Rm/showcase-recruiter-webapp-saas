# TASK API-061 — les fiches par lot, depuis des recherches nommées

- **Créée le** : 2026-08-17.
- **Session** : `d56e8b35-bd10-4a7e-a7b9-86a9fbcd1240`.
- **Statut** : ✅ fait le 2026-08-17 — la route rend les premières fiches de plusieurs recherches,
  dédoublonnées. Un écart au cadrage, consigné plus bas.
- **Type** : Nouvelle route — les fiches se demandent par lot, sans rattachement.
- **Tests** : fonctionnels phpunit — les fiches rendues sont celles des premiers profils de chaque
  recherche nommée, dédoublonnées entre elles ; le nombre demandé est respecté et borné ; une
  recherche hors périmètre rend la même réponse qu'une recherche inconnue ; un tableau vide rend une
  collection vide.
- **Réf** : **amont** `API-060` (l'état, qui dit l'ordre) · **aval** `ENT-130` (l'accueil qui
  l'appelle) · `API-059` (la borne retirée).

## Contexte

L'écran d'accueil affiche quatre à six recherches et voudrait pouvoir en ouvrir n'importe laquelle
sans attendre. **Il lui faut donc les premières fiches de chacune — et rien d'autre.**

Aucune route ne sait faire cela aujourd'hui : la route de résultats sert **une** recherche à la
fois, et la route d'état ne sert plus de fiches depuis `API-060`. Demander quatre recherches
reviendrait à quatre appels, chacun rapportant des profils que les autres rapportent aussi.

## Périmètre

- **Une route qui prend un tableau d'identifiants de recherche et un nombre de profils à prendre**,
  et rend les fiches correspondantes.
- **Dédoublonnage entre les recherches demandées** : un profil qui sert trois d'entre elles n'est
  sérialisé qu'une fois.
- **Les fiches, et rien d'autre** : ni ordre, ni appartenance, ni explication. L'appelant tient déjà
  l'ordre, il vient de la route d'état.

## Hors périmètre

- **L'ordre et l'appartenance** — `API-060` les porte.
- **Les explications** — `API-062` les porte.
- **Le choix des recherches** : c'est l'écran qui nomme celles qu'il affiche, le serveur n'en
  sélectionne aucune.

## Décisions de cadrage

### Des identifiants de recherche, pas de profil

Une liste d'identifiants de profil ferait une adresse de 3 700 caractères pour cent profils, à
comparer aux 249 caractères de six recherches nommées — le plafond usuel des intermédiaires étant de
8 000. Nommer des recherches laisse aussi le dédoublonnage au serveur, seul à voir l'ensemble.

### Une lecture, donc une requête de lecture

La liste tient largement dans une adresse : environ 215 recherches avant d'approcher le plafond. Le
mode lecture donne gratuitement la relance sans risque, la reconnaissance d'un appel déjà en vol par
son adresse, et l'usage des mécanismes de préchargement du navigateur. Aucune lecture de cette API
ne se fait autrement.

### Les fiches seules

C'est le choix produit : la route fait une chose. Une fiche affichée avant que son explication soit
arrivée montrera un instant sa zone de critères vide — conséquence acceptée.

## Règles et cas limites

- **L'ordre des identifiants est trié** avant l'appel, pour que deux demandes du même lot aient la
  même adresse.
- **Le nombre de recherches et le nombre de profils sont bornés**, chacun par une valeur explicite :
  une demande hors borne est réduite, pas refusée.
- **Un identifiant inconnu est ignoré** plutôt que de faire échouer tout le lot : un lot est une
  commodité, pas une transaction. **Une recherche hors périmètre l'est aussi** — cf. l'écart
  ci-dessous.
- **Un tableau vide rend une collection vide**, sans erreur.
- **Les fiches portent le masquage habituel** — ce qui est dévoilé pour cette organisation, et rien
  de plus.

### ÉCART — une recherche hors périmètre est ignorée, pas refusée

Le cadrage disait deux choses incompatibles : refuser une recherche que le lecteur ne peut pas voir,
et ignorer un identifiant inconnu. **Faire les deux, c'est dire laquelle existe** — il suffit de
comparer les deux réponses.

Les deux cas rendent donc exactement la même chose : une collection sans cette recherche. Un test
compare les deux réponses et exige qu'elles soient identiques.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` — la nouvelle route.
- `api/openapi.json` régénéré.

## Vérification

**Appels réels** sur la pile du worktree :

```
4 recherches, take=30   →  200 · 128 770 octets · 0,93 s
                           120 places demandées → 110 fiches uniques

une recherche, take=30              →  30 fiches
la même, take=5                     →   5 fiches
la même, take=9999                  → 100 fiches   ← bornée, pas refusée
la même, sans take                  →  30 fiches   ← défaut
aucun identifiant                   →   0 fiches   ← collection vide, pas d'erreur
identifiant inconnu                 →   0 fiches
inconnu + valide                    →   5 fiches   ← le lot ne tombe pas
recherche d'un autre recruteur      →   0 fiches
```

Sur une fiche servie : coordonnées à `null`, et `isNew`, `isFavorite`, `isSeen`, `isDismissed`,
`matches` tous nuls — ces fiches n'appartiennent à aucune recherche.

```
phpunit --filter 'SearchProfilesTest'    OK (9 tests, 64 assertions)
```

### Corrections venues du point de contrôle

Les gates ne tournent qu'à l'ouverture de la PR, et elles ont relevé quatre défauts de forme dans le
code de ce chantier : deux conversions ou filtres imprécis dans le fournisseur, et deux assertions de
test qu'un type déclaré rendait toujours vraies — l'une d'elles ne prouvait donc **rien** de ce que
le serveur envoie réellement. Elle lit désormais la réponse brute.

Le fichier de test d'`API-060` en fait partie : son commit n'est plus en tête et n'est donc pas
modifiable. La correction voyage ici, sans changement de comportement.

## Livré

- `api/src/ApiResource/Output/SearchProfilesOutput.php` **(+)** — la route et sa forme.
- `api/src/State/Provider/SearchProfilesProvider.php` **(+)** — le périmètre du lecteur, les bornes,
  le dédoublonnage.
- `api/tests/Functional/SearchProfilesTest.php` **(+)** — neuf tests.
- `api/openapi.json` et `webapp/shared/types/api.ts` régénérés.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md`.

## Définition de terminé

- [x] La route rend les premières fiches de chaque recherche nommée, dédoublonnées entre elles.
- [x] Le nombre demandé est respecté, et borné au-delà d'une valeur explicite.
- [x] Une recherche hors périmètre est **ignorée** — critère RÉÉCRIT, cf. l'écart : la refuser
      révélerait son existence.
- [x] Un identifiant inconnu est ignoré ; un tableau vide rend une collection vide.
- [x] Le masquage des coordonnées est celui des autres routes de profils.
- [x] Tests fonctionnels sur ces cinq points.
- [x] Documentation et contrat régénéré à jour.
