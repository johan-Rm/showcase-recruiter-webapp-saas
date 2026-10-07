# TASK API-044 — API : l'expérience se compte dans le métier cherché

- **Créée le** : 2026-08-13.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-13 — la borne d'expérience compte les mois dans le métier cherché
  quand le dictionnaire le connaît, la carrière sinon, et la fiche porte les langues avec leur niveau
  et tous les secteurs exercés.
- **Type** : feat — un critère qui change de définition, et deux champs de plus sur la carte.
- **Tests** : `phpunit` fonctionnel, comme les voisines du moteur. Trois familles : le critère
  d'expérience **avec** un poste posé (il compte les mois dans ce métier), **sans** poste (il compte
  la carrière, inchangé), et la bascule entre les deux sur un même profil — un candidat de dix ans
  de carrière dont deux dans le métier cherché entre à « 5 ans » sans poste, et sort avec.
  Puis la carte : les langues avec leur niveau, et **tous** les secteurs d'expérience, y compris sur
  un profil qui en porte plusieurs.
- **Réf** : **amont `API-010`** — le critère d'expérience, qui compte aujourd'hui la carrière ·
  `API-026` — la correspondance métier · `095` — l'extraction des axes d'expérience à l'import ·
  **jumelle `ENT-114`** — le libellé et les affinages qui en découlent, à merger ensemble ·
  [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md) ·
  [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md)

## Contexte

**Le filtre « années d'expérience » ne compte pas ce que le recruteur croit.** Posé à côté d'un poste
recherché, il additionne la **carrière entière** (`experienceYears`, dérivé du parcours), si bien
qu'« architecte avec 5 ans » retient un architecte débutant qui a fait dix ans d'autre chose. Ce
n'est presque jamais la question posée.

La donnée juste **existe déjà** : `candidate_job_experience` porte, pour chaque candidat et chaque
métier du dictionnaire, le nombre de **mois exercés** — 3 488 candidats sur 3 899 (89 %), 9 416
couples métier/durée. Mieux : le filtre « poste recherché » traverse **déjà cette table**
(`applyJobFilter`), la jointure est en place.

Deux autres données bien remplies restent invisibles au navigateur, ce qui empêche l'espace
entreprise d'en faire quoi que ce soit : les **langues** (88 % du vivier) et le **secteur
d'expérience** (78 %, 18 secteurs distincts). La carte de profil ne les porte pas.

## Périmètre

**1 · Le critère d'expérience devient contextuel.**

| Contexte | Ce que `experienceMin` compte |
| --- | --- |
| Un poste recherché est posé | les **mois exercés dans ce métier**, sur la même correspondance que le filtre de poste |
| Aucun poste posé | la **carrière entière**, comme aujourd'hui |

La borne reste exprimée en **années** dans le contrat : rien ne change à la forme de la requête, seul
son sens suit le contexte.

**2 · La carte de profil porte deux champs de plus** — `CandidateCardOutput` :

- les **langues avec leur niveau**, telles que le moteur les connaît déjà pour filtrer ;
- **tous les secteurs d'expérience**, pas le principal.

## Hors périmètre

- **Le libellé à l'écran** et les affinages qui consomment ces champs : ils vivent dans `webapp/`,
  donc dans `ENT-114`. Une carte ne traverse pas deux lanes.
- **Les mois par métier sur la carte** : cette tâche les rend *filtrables*, pas *affichables*. Les
  descendre demanderait de choisir quel métier montrer quand le profil en porte huit.
- **Toute migration** : les trois tables existent et sont peuplées par l'import.
- **La fenêtre de tolérance** — « 5 ans dans ce métier » se lit strictement, sans arrondi
  bienveillant sur un candidat à 4 ans et 11 mois.

## Décisions de cadrage

**Le changement est rétroactif, et rien n'est migré.** Une recherche enregistrée qui combine un poste
et une durée verra ses résultats **se resserrer** : cinq ans dans un métier est plus exigeant que
cinq ans de carrière. C'est assumé — marquer chaque critère de son intention d'origine imposerait de
maintenir deux comportements pour toujours, et le comportement actuel est celui que personne ne
voulait.

**Un seul critère, deux définitions selon le contexte** — plutôt qu'un second champ. C'est ce que le
recruteur croit déjà poser ; ajouter « années dans ce métier » à côté d'« années de carrière »
alourdirait le formulaire d'un champ que la plupart laisseraient vide, pour lever une ambiguïté que
presque personne ne soupçonne.

**Tous les secteurs, pas le principal.** Un candidat passé par la santé puis la banque doit rester
trouvable sur « santé » : c'est justement chez les profils qui ont bifurqué que la question se pose.

**Les langues descendent avec leur niveau**, bien que ⚠️ **la langue maternelle arrive souvent sans
niveau depuis LinkedIn** — un piège déjà relevé dans l'inventaire des critères. Le niveau est donc
`null` sur une partie des lignes, et le consommateur doit traiter l'absence comme « non renseigné »,
jamais comme « débutant ».

**Cette carte devait ne pas se merger seule — elle l'a fait quand même, et c'est une décision.**

Le cadrage voulait qu'elle parte avec `ENT-114` dans la même Pull Request : entre les deux, le moteur
compte les années autrement sans que l'écran l'explique. Mais `ENT-114` ne peut pas s'implémenter
avant celle-ci — la fiche de profil n'y porte ni langues ni secteurs tant que ce commit n'est pas sur
`develop`, et rien n'y serait vérifiable. L'ordre inverse était impossible.

**Ce que cela laisse pendant l'intervalle** : sur `develop`, une recherche qui combine un poste et
une borne d'expérience compte les années **dans ce métier**, tandis que le champ à l'écran s'intitule
encore « années d'expérience ». Le résultat est plus juste qu'avant ; c'est son libellé qui retarde.
`ENT-114` referme cet écart, et rien d'autre ne le referme.

## Règles et cas limites

- **Le poste et la durée portent sur la MÊME correspondance**, comme la présence et sa ville : un
  candidat qui a fait deux ans d'architecture et huit ans de conduite de travaux ne satisfait pas
  « architecte, 5 ans ».
- **Le repli du filtre de poste** — quand le terme est inconnu du dictionnaire, `applyJobFilter`
  cherche l'intitulé tel quel, hors de la table des durées. Dans ce cas **il n'y a pas de mois à
  compter** : décider explicitement si la borne d'expérience retombe sur la carrière ou si elle
  n'exclut personne, et l'écrire — c'est le cas limite que la relecture regardera en premier.
- **Un métier sans durée exploitable** (poste sans date de début : le cas est documenté dans les
  motifs d'écart du vivier) ne doit pas compter comme zéro mois sans le dire.
- **`experienceMin` à zéro ou absent** ne pose rien, dans les deux contextes.
- **Le contrat ne change pas de forme** : même paramètre, même type. Seuls les résultats bougent.

## Ce que l'implémentation a tranché autrement

**Le cas du repli est réglé : la borne retombe sur la carrière.** La carte le laissait ouvert ;
l'alternative — aucune durée, donc aucun résultat — rendait un terme hors dictionnaire inutilisable
dès qu'on exigeait de l'expérience. Décidé au vu de la mesure ci-dessous.

**`api/openapi.json` n'a pas bougé, et c'est normal.** La DoD le demandait ; la régénération a été
lancée et le fichier est identique. `CandidateCardOutput` **n'apparaît nulle part dans l'OpenAPI** —
la carte n'y existe que nichée dans des collections aux `items` opaques, ce que
`data-contracts.md § L'exception : la carte de recherche` documente déjà. Ajouter un champ à ce DTO
ne change donc pas le contrat, et aucune garde ne le verra : c'est `ENT-114` qui doit reporter les
deux champs dans le miroir écrit à la main.

**Aucun helper n'a été écrit.** `spokenLanguages()` et `sectorExperiences()` existaient déjà dans le
mapper pour le profil détaillé ; la carte s'y branche. Les secteurs y arrivent triés par durée
décroissante, ce qui met le secteur dominant en tête sans rien recalculer.

**La mesure qui a précédé l'implémentation.** Le code portait un avertissement à l'endroit exact du
changement — « filtrer sur une durée trierait selon ce que le dictionnaire a rattaché ». Vérifié :
**11 307 postes sur 15 421 sont rattachés, soit 73 %**. Sur le seuil de cinq ans, le changement fait
passer les candidats éligibles de 1 376 à 825. Une part de cette baisse est juste — les gens changent
de métier —, l'autre est un artefact du rattachement, et rien ne permet de les séparer sans examen
manuel. Les intitulés bruts ne portant pas de dates, aucune correction n'est possible côté code.

**Conséquence assumée, écrite dans le code et dans la doc** : le filtre est **strictement plus
sévère** que la réalité. Il n'ajoute jamais un profil qui ne convient pas ; il en écarte qui
conviendraient.

## Vérification

Requêtes réelles sur l'API de la lane, connecté en `owner@demo.showcase-recruiter-webapp-saas.example`.

**L'expérience se compte dans le métier cherché :**

```
job=consultant                     → 468 profils
job=consultant&experienceMin=5     →  79 profils
```

79 est exactement le nombre de candidats que la base compte avec cinq ans **dans** ce métier ; les
243 qui ont cinq ans de carrière sans les avoir en conseil ne remontent plus.

**Sans métier, la carrière, inchangée :**

```
experienceMin=5   → 1415 profils     (base : 1416 avec ≥ 5 ans de carrière)
experienceMin=10  →  795 profils     (base :  795)
```

**Le repli, sur « sales advisor » — un intitulé que le dictionnaire ignore :**

```
job=sales advisor                   → 9 profils
job=sales advisor&experienceMin=5   → 5 profils
job=sales advisor&experienceMin=30  → 0 profils
```

**La fiche porte les deux champs :**

```
languages         : [{ar, NATIVE_OR_BILINGUAL}, {de, null}, {en, PROFESSIONAL_WORKING}, …]
experienceSectors : ['INFORMATIQUE', 'BANQUE_FINANCE']
```

⚠️ La table `candidate_sector_experience` est **vide dans la base de cette lane** — la projection n'y
a jamais tourné. Le chemin de sérialisation a donc été prouvé en insérant deux secteurs sur un profil
servi, puis en les supprimant ; la table est revenue à zéro, vérifié. Les tests, qui montent leurs
propres fixtures, couvrent ce point sans dépendre de l'état d'une base de développement.

**Tests :**

```
CandidateJobExperienceMinTest      OK (4 tests, 6 assertions)
CandidateCardSkillsProfileTest     OK (3 tests, 4 assertions)
les cinq suites du filtre de métier OK (19 tests, 69 assertions)
```

## Livré

- `api/src/Repository/CandidateRepository.php` **(M)** — la borne part avec le filtre de métier, qui
  seul sait sur quelle correspondance elle porte ; deux façons de compter dans **une** requête.
- `api/src/ApiResource/Output/CandidateCardOutput.php` **(M)** — `languages` et `experienceSectors`.
- `api/src/State/CandidateMapper.php` **(M)** — branché sur les deux helpers existants.
- `api/tests/Functional/CandidateJobExperienceMinTest.php` **(+)** — les quatre comportements.
- `api/tests/Functional/CandidateCardSkillsProfileTest.php` **(+)** — la fiche, et ses deux pièges.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md` **(M)** — les deux sens du paramètre, et ce
  que la durée par métier ne voit pas.

## Reste à faire (hors périmètre)

- **`ENT-114`** doit reporter `languages` et `experienceSectors` dans
  `webapp/shared/types/candidate-card.ts` — écrit à la main, surveillé par rien.
- **La projection des secteurs n'a jamais tourné sur la base de la lane API.** Sans objet pour cette
  tâche, mais toute vérification à l'écran depuis ce worktree la trouvera vide.

## Documentation impactée

- `2.architecture/api-routes.md` **(M)** — les deux sens d'`experienceMin`, en tableau, et la réserve
  sur ce que la durée par métier ne compte pas.
- `2.architecture/data-contracts.md` — **inchangé** : son § *L'exception : la carte de recherche*
  énonce déjà la règle qui s'applique ici, et la nommer une seconde fois la dupliquerait.
- `api/openapi.json` — **inchangé** après régénération, voir *Ce que l'implémentation a tranché*.

## Définition de terminé

- [x] Avec un poste posé, la borne d'expérience compte les mois exercés dans ce métier.
- [x] Sans poste posé, elle compte la carrière entière, exactement comme avant.
- [x] Le cas du repli — poste inconnu du dictionnaire — a une règle écrite et testée.
- [x] La carte de profil porte les langues avec leur niveau, `null` compris.
- [x] La carte de profil porte tous les secteurs d'expérience d'un candidat.
- [x] Les trois familles de tests passent, et les suites existantes du moteur restent vertes.
- [x] `api/openapi.json` régénéré — **inchangé**, la carte n'y figurant pas ; rien à commiter.
- [x] La carte porte, en toutes lettres, ce que son merge anticipé laisse ouvert jusqu'à `ENT-114`.
