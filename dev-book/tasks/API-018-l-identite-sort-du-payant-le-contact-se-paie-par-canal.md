# TASK API-018 — l'identité sort du payant, le contact se paie par canal

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-08-01 — l'identité, l'employeur et le profil LinkedIn se lisent sans
  crédit ; l'e-mail et le téléphone s'achètent séparément, un crédit chacun ; une commande convertit
  les dévoilements hérités sans refacturer personne.
- **Type** : Backend API — modèle de dévoilement, contrat et conversion des données.
- **Tests** : unitaires sur `RevealedAccess` (les quatre combinaisons, et une valeur héritée qui
  n'ouvre rien) ; fonctionnels sur l'endpoint de dévoilement (débit réel, refus sur solde
  insuffisant, absence de refacturation) et sur la commande de conversion (un `CONTACT` hérité
  devient `EMAIL` + `PHONE`, idempotence, `--dry-run`). **`CandidateMapper` est couvert
  fonctionnellement, pas en unitaire** : il n'est atteignable qu'à travers l'endpoint, et c'est là
  que se vérifie ce qui sort vraiment.
- **Réf** : `ENT-029` (la carte profil, qui consomme ce contrat) · `ENT-013` (le dévoilement comme
  geste) · [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md) ·
  [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) ·
  [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md)

## Contexte

**Le dévoilement est un bloc.** Un crédit sur `CONTACT` ouvre d'un coup sept choses : le nom, l'adresse
e-mail, le téléphone, l'identifiant WhatsApp, l'URL LinkedIn, l'entreprise actuelle et les employeurs
du parcours. Le recruteur ne peut donc ni lire un profil sans payer, ni payer seulement ce dont il a
besoin.

Deux conséquences, l'une pour l'utilisateur et l'autre pour le produit.

**On ne sait pas qui on regarde.** Le serveur réduit le nom à ses initiales — « Y. B. » pour Youssef
Boutaib. Deux lettres n'aident à aucune décision, et le profil reste illisible tant qu'un crédit
n'est pas engagé. C'est le pari inverse de celui qu'on veut tenir : donner à lire, faire payer le
contact.

**On paie un canal dont on n'a pas besoin.** Un recruteur qui ne travaille qu'au téléphone paie
l'e-mail, et réciproquement. Le coût est le même quel que soit l'usage, alors que sur ce vivier
**95 candidats sur 2 561 ont un e-mail et 70 un téléphone** — les deux ne sont presque jamais
disponibles ensemble.

## Périmètre

- **L'enum** passe à quatre valeurs : `EMAIL`, `PHONE`, `RESUME`, `INTERVIEW_REPORT`. `CONTACT`
  disparaît.
- **`RevealedAccess`** porte `email` et `phone` à la place de `contact`.
- **`CandidateMapper`** cesse de masquer l'identité, et masque **par canal**.
- **WhatsApp suit `PHONE`**, jamais l'identité.
- **Une commande** `app:reveals:split-contact` convertit chaque `CONTACT` acquis en deux lignes,
  `EMAIL` et `PHONE` — le dépôt n'a pas de migrations (voir *Décisions de cadrage*).
- **L'endpoint de dévoilement** et la politique de crédits : un crédit par canal.
- **L'export CSV** : au moins un canal exigé, et masquage colonne par colonne.
- **`api/openapi.json` régénéré**, et **`webapp/shared/types/api.ts` avec lui** — contenu de commit,
  pas gate.

## Hors périmètre

- **Le front écrit à la main.** La carte profil, la Table, les composables et les deux miroirs de
  types rédigés à la main cassent avec ce commit : `revealedContact` n'existe plus. Rupture assumée,
  réparée par une tâche `espace/entreprise` qui suit — c'est elle qui rendra les coordonnées
  cliquables à l'unité.

  **`shared/types/api.ts` n'en fait PAS partie** : il est *généré* depuis `openapi.json` par
  `openapi-typescript`, et la gate de dérive exige qu'il suive le contrat dans le même commit. Le
  premier cadrage l'avait rangé dans « le front » et la CI l'a corrigé — voir *Décisions de
  cadrage*.
- **Le barème de crédits offerts.** Le prix double pour qui veut les deux canaux ; ajuster la
  dotation de départ est une décision commerciale, pas une conséquence mécanique.
- **Le compte rendu et le CV**, dont les accès ne changent pas.

## Décisions de cadrage

### L'identité entière devient gratuite, LinkedIn compris

Nom, photo, entreprise actuelle, employeurs du parcours et **URL LinkedIn** sortent du périmètre
payant. Seuls l'e-mail et le téléphone restent derrière un crédit.

**Le risque est nommé et assumé** : avec un nom, un poste et une URL LinkedIn, un recruteur joint le
candidat hors plateforme sans dépenser un crédit. Le paywall devient contournable, et c'est un choix
produit — donner à lire pour donner envie, plutôt que faire payer pour savoir qui l'on regarde.
Il est écrit ici pour qu'il ne se redécouvre pas comme une surprise.

### Un crédit par canal, donc deux pour les deux

L'e-mail coûte un crédit, le téléphone un crédit. Le contact groupé en coûtait un pour les deux :
c'est un doublement de prix pour qui veut tout, et il est délibéré — on paie ce qu'on prend.

L'alternative — premier canal payant, second offert — aurait gardé le prix constant, mais la
granularité n'aurait plus rien facturé : elle serait devenue un confort d'affichage. Écartée pour
cette raison, pas par gourmandise.

### WhatsApp suit le téléphone, parce qu'il *est* le téléphone

L'identifiant WhatsApp a la forme `33600000000@s.whatsapp.net` : il **contient le numéro en clair**.
Le libérer avec l'identité aurait donné gratuitement ce qu'on fait payer sous `PHONE` — il suffisait
de lire l'identifiant.

Il est donc servi avec `PHONE`, et jamais autrement. C'est le seul endroit de cette carte où la règle
ne suit pas la nature apparente de la donnée mais ce qu'elle **révèle réellement**.

### `CONTACT` disparaît au lieu de survivre

Garder `CONTACT` comme cinquième valeur aurait évité la migration, au prix d'une règle à deux
branches — « e-mail visible si `EMAIL` **ou** `CONTACT` » — à porter dans tout le mapper, et pour
toujours. Le choix d'un lot achetable a par ailleurs été écarté côté produit : le dévoilement en lot
depuis la Table portera **un canal à la fois**, donc `CONTACT` n'aurait plus eu d'appelant.

### Ce qui a déjà été payé reste acquis — par une COMMANDE, pas une migration

La conversion écrit `EMAIL` **et** `PHONE` sur le même candidat, la même organisation, le même auteur
et la **même date** que le `CONTACT` d'origine : l'historique justifie une facturation, il ne doit pas
mentir. C'est la seule option qui ne refacture personne, et elle vaut même si la base de production
est vide aujourd'hui — la règle doit exister avant le premier client, pas après.

**Écart au cadrage.** La carte annonçait une migration. Le dépôt n'en a aucune, délibérément :
`api/migrations/` ne contient qu'un `.gitignore`, et le commit `2db1b12` (20 juin) pose que « les
migrations Doctrine ne seront introduites qu'à la mise en prod », le schéma dev étant recréé depuis
le mapping. Une migration ici n'aurait eu ni véhicule ni destinataire.

D'où `app:reveals:split-contact` : versionnée, testée, **idempotente**, avec un `--dry-run`. Elle lit
les lignes héritées en SQL brut — la valeur n'existe plus dans l'enum, Doctrine refuserait de les
hydrater — et se lancera le jour où une base réelle en portera. Elle ne débite rien.

### L'export exige UN canal, et masque l'autre — écart au cadrage

Le cadrage n'avait pas vu l'export CSV, qui exigeait `CONTACT` et écrivait e-mail et téléphone sans
masquer : « par construction, tous les sélectionnés sont dévoilés ». Cette phrase n'a plus de sens
quand les canaux s'achètent séparément.

Il exige donc **au moins un canal** payé, et le writer laisse **vide la colonne du canal non
acheté**. Exiger les deux aurait fait payer deux crédits ce qu'un seul ouvrait hier ; exiger
l'identité n'a plus d'objet, elle est gratuite. Un export ne doit jamais livrer ce qui n'a pas été
acheté, et un fichier amputé en silence ne se remarque pas — d'où le refus qui NOMME les profils
fautifs, conservé tel quel.

Au passage, la lecture des accès descend à **une requête pour tout le lot** au lieu d'une par
candidat : elle sert désormais deux fois, au filtre et au masquage.

### Le miroir généré voyage avec le contrat — écart au cadrage

`shared/types/api.ts` vit sous `webapp/`, ce qui l'a fait classer « front, hors périmètre ». C'était
faux : ce fichier n'est écrit par personne. `openapi-typescript` le produit à partir d'`openapi.json`,
et `quality-contracts-check` régénère puis exige un `git diff` vide.

Le laisser au front revenait donc à livrer une lane rouge : la gate a sorti la PR d'`API-018` en
échec sur ce seul fichier. Il est régénéré et commité ici, avec le contrat qui le détermine.

Vérifié : le reste du front tient sans lui être aligné — `bun run quality-check` sort à 0, type-check
vert sur les quatre espaces, 83 tests vitest verts. Les miroirs écrits à la main cohabitent, parce
que le front consomme ses propres types et non `api.ts`. La rupture qu'`ENT-037` doit réparer est
donc bien devant, pas ici.

### Rupture nette du contrat, plutôt qu'un champ de compatibilité

`revealedContact` cède la place à `revealedEmail` et `revealedPhone`. Le garder en champ dérivé —
`revealedEmail && revealedPhone` — aurait évité de casser le front, mais laissait dans le contrat une
valeur que quelqu'un finirait par consommer, et qui mentirait dès qu'un seul canal serait acheté.

Conséquence assumée : entre le merge de cette carte et celui de la tâche entreprise, l'écran affiche
mal l'état de dévoilement. Les deux PR doivent partir rapprochées.

## Règles et cas limites

- **Un accès déjà acquis ne se refacture pas** : redemander `EMAIL` sur un profil dont l'`EMAIL` est
  payé ne débite rien et ne crée pas de seconde ligne.
- **Solde insuffisant** : le lot entier est refusé, aucun débit partiel — règle actuelle, inchangée.
- **Un canal inexistant reste achetable** : `hasEmail` à faux n'empêche pas de payer, mais le front
  l'annonce avant. À ne pas transformer en refus serveur — le vivier bouge, et un refus figerait une
  absence temporaire.
- **L'accès est acquis à l'ORGANISATION**, pas au recruteur : deux membres ne paient pas deux fois.
- **La photo ne change pas de régime** — elle était déjà gratuite, elle le reste.
- **Concurrence** : deux achats simultanés du même canal sur le même profil ne doivent débiter
  qu'une fois. Le débit est déjà atomique, la contrainte d'unicité doit couvrir le triplet
  organisation / candidat / type.

## Documentation impactée

- [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md) — ligne 38
  (« identité/coordonnées masquées tant que le profil n'est pas dévoilé », qui devient faux sur
  l'identité) et ligne 79 (« débit 1 crédit », désormais par canal).
- [data-model.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-model.md) — lignes 155-157 :
  `linkedinUrl` cesse d'être « dévoilé sur crédit » ; `contactEmail` et `phone` le restent, chacun
  sous son propre accès.
- [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md) — le champ du
  contrat de carte qui change de nom et de nombre.
- `api/openapi.json` régénéré par `make dev-openapi`.
- **`dev-book/cadrage/` reste hors périmètre** — écrit pour les clients, il suit son propre rythme.

## Définition de terminé

- [x] `RevealType` porte quatre valeurs et `CONTACT` n'apparaît plus nulle part dans le code.
- [x] Le nom complet, l'entreprise actuelle, les employeurs et l'URL LinkedIn sont servis **sans**
      dévoilement.
- [x] L'e-mail et le téléphone se dévoilent séparément, un crédit chacun.
- [x] L'identifiant WhatsApp n'apparaît qu'avec l'accès `PHONE`.
- [x] La commande convertit les `CONTACT` hérités en `EMAIL` + `PHONE`, vérifié sur une base portant
      une ligne héritée, et se rejoue sans dupliquer.
- [x] Un accès déjà acquis ne débite rien.
- [x] `api/openapi.json` **et** son miroir généré `shared/types/api.ts` sont régénérés et commités.
- [x] Les trois pages de documentation listées disent le nouveau modèle.
- [x] `make api-quality` — vert à l'ouverture de la PR : `lint:container` OK, phpstan **No errors**,
      php-cs-fixer **0 of 463**, deptrac **0 erreur**, phpunit **391 tests / 1 976 assertions**.
      Deux défauts de gate ont été corrigés à cette occasion, tous deux dans le code neuf de cette
      carte : des `assert` redondants dans le test de la commande, et un import inutilisé dans la
      commande elle-même.

## Vérification

**81 tests, 603 assertions, verts** sur les neuf classes impactées — dernière sortie lue le
2026-08-01. Trois classes n'existaient pas avant cette tâche.

Ce que les tests établissent, et pas seulement qu'ils passent :

| Test | Ce qu'il prouve |
| --- | --- |
| `RevealedAccessTest` (6 cas) | Payer un canal n'ouvre pas l'autre ; un document ne rend joignable personne ; **une valeur `CONTACT` héritée n'ouvre rien** |
| `testSearchFullViewUnmasksRevealedProfiles` | E-mail acheté → nom, e-mail et employeur lisibles, **téléphone et WhatsApp toujours nuls** |
| `testPhoneIsRevealedOnItsOwnAccess` | Téléphone acheté seul → numéro et WhatsApp ouverts, e-mail toujours fermé |
| `testUnrevealedProfileStillExposesEmployerNames` | Sans crédit : employeurs lisibles, canaux fermés |
| `RevealSplitContactCommandTest` (3 cas) | Un `CONTACT` hérité devient `EMAIL` + `PHONE` ; idempotence ; `--dry-run` n'écrit rien |
| `SearchExportTest` | Passe **sans modification** : la porte à un canal et le masquage par colonne tiennent |

**Deux tests affirmaient l'ancienne règle et ont dû être réécrits** — c'est la meilleure preuve que
la bascule est réelle : l'un exigeait que l'employeur soit masqué, l'autre attendait le téléphone
après un achat qui ne le couvre plus.

**Le contrat régénéré dit exactement la bascule** : `revealedContact` devient `revealedEmail` +
`revealedPhone`, et l'enum passe de trois à quatre valeurs aux deux endroits où elle apparaît.

**Environnement** : la stack du worktree `api` n'étant pas démarrée, les tests ont tourné sur deux
bases isolées — `showcase-recruiter-webapp-saas_wtapi` et `showcase-recruiter-webapp-saas_wtapi_test` — créées sur le Postgres de la stack
entreprise déjà en route, via `.env.test.local` (non versionné). Aucune donnée d'une autre lane
n'est touchée.

## Livré

- `api/src/Enum/RevealType.php` **(M)** — `CONTACT` disparaît, `EMAIL` et `PHONE` le remplacent.
- `api/src/Domain/Candidate/RevealedAccess.php` **(M)** — quatre accès, plus `anyContact()`.
- `api/src/State/CandidateMapper.php` **(M)** — l'identité, l'employeur, le parcours et LinkedIn
  sortent du masquage ; les canaux y entrent séparément ; `initial()` et le masquage d'employeur
  disparaissent, devenus sans objet.
- `api/src/ApiResource/Output/{CandidateCardOutput,CandidateOutput}.php` **(M)** — le champ se
  dédouble.
- `api/src/Entity/RevealedProfile.php` **(M)** — plus de type par défaut.
- `api/src/State/SearchExportHandler.php` **(M)** — porte à un canal, accès lus en une requête.
- `api/src/Domain/Search/SearchCsvWriter.php` **(M)** — masquage colonne par colonne.
- `api/src/DataFixtures/AppFixtures.php` **(M)** — la démo paie l'e-mail seul.
- `api/src/Command/RevealSplitContactCommand.php` **(+)** — la conversion, idempotente.
- `api/tests/Unit/RevealedAccessTest.php` **(+)** · `api/tests/Functional/RevealSplitContactCommandTest.php` **(+)**.
- `api/tests/Functional/{CandidateTest,RevealTest,JarviIngestionTest,CandidateActivityTest,CandidateQualifiedExperienceTest,NotificationEmissionTest}.php` **(M)**.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/{api-routes,data-model,data-contracts}.md` **(M)**.
- `api/openapi.json` **(M)** et `webapp/shared/types/api.ts` **(M)** — le contrat et son miroir
  généré, régénérés ensemble.
- `dev-book/tasks/API-018-…md` **(+)** — cette carte.

## Reste à faire (hors périmètre)

**Le front est cassé par ce commit, et c'est assumé.** `revealedContact` n'existe plus ; l'espace
entreprise le lit dans `ProfileCard.vue`, `useReveal.ts`, `useSearch.ts` et dans les types écrits à
la main de `webapp/shared/types/`. Ces types sont un **miroir manuel** que la gate de contrat ne
compare à rien : rien ne criera, l'écran affichera simplement mal l'état de dévoilement.

Les deux PR doivent donc partir rapprochées.

## Aval

- **La tâche `espace/entreprise`** qui recolle la carte profil et la Table : coordonnées cliquables à
  l'unité, disparition du bouton « Coordonnées », modale de lot par canal.
- **Le barème de crédits offerts**, à revoir si le doublement de prix change l'expérience d'un compte
  gratuit.
- **La joignabilité du vivier** : 95 e-mails et 70 téléphones sur 2 561 candidats. Cette carte rend
  le problème visible et facturable au bon niveau ; elle ne le corrige pas — c'est l'ingestion qu'il
  faudra regarder.
