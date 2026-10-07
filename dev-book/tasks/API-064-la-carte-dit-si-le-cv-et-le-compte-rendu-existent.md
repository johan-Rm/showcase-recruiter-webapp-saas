# TASK API-064 — la carte dit si le CV et le compte rendu existent

- **Créée le** : 2026-08-14.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff`.
- **Statut** : ✅ fait le 2026-08-17 — la carte de recherche et la fiche annoncent quatre présences
  au lieu de deux ; le CV et le compte rendu se lisent par la même source que la garde de
  facturation, et un profil jamais synchronisé les reçoit à faux au lieu de faire tomber la lecture.
- **Type** : feat — deux signaux de présence de plus sur la carte candidat et sur la fiche.
- **Tests** : unitaires sur le mapper de sortie — présence et absence pour chacun des deux canaux,
  et le cas qui les sépare : un profil **rencontré sans compte rendu écrit**, un CV dont l'entrée ne
  porte **pas de fichier**. Même niveau que ce qui couvre déjà `hasEmail` / `hasPhone`.
- **Réf** : `API-063` — la garde de facturation, amont · `ENT-132` — l'écran qui consommera ces deux
  booléens, aval · `api/src/ApiResource/Output/CandidateCardOutput.php` et `CandidateOutput.php` ·
  [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md)

## Contexte

**L'écran ne peut pas éteindre ce qu'il ne sait pas.** La carte candidat porte `hasEmail` et
`hasPhone` — la présence d'une coordonnée, jamais sa valeur — précisément pour qu'un recruteur voie
avant de payer s'il pourra joindre la personne. Les deux autres canaux payants, le CV et le compte
rendu d'entretien, n'ont pas d'équivalent.

Conséquence directe : même une fois `API-063` livrée, l'écran continuera d'annoncer un coût pour un
CV qui n'existe pas. Le serveur refusera de le facturer, mais le bouton l'aura proposé — et un bouton
qui propose puis ne fait rien est une autre façon de mentir.

Le serveur, lui, a tout sous la main : les CV vivent dans le réplica Jarvi, le compte rendu sur le
candidat. C'est **la carte** qui n'en descend que la moitié.

## Périmètre

- **`hasResume` et `hasReport`** sur la carte de recherche **et** sur la fiche, strictement
  symétriques de `hasEmail` / `hasPhone` : un booléen, jamais une valeur.
- **Les mêmes règles de présence que la garde** : le CV compte s'il existe **au moins une entrée
  portant un identifiant de fichier** ; le compte rendu, si **son texte** est non vide.
- **`api/openapi.json` régénéré** et commité — c'est du contenu de commit, pas une gate.

## Hors périmètre

- **Toute valeur servie.** On expose une présence. Le CV et le compte rendu restent derrière leur
  dévoilement, comme aujourd'hui.
- **Le miroir TypeScript** `webapp/shared/types/candidate-card.ts` : il appartient à la lane qui le
  consomme, et c'est `ENT-132` qui l'ajoute. Écrit à la main, aucune gate ne le vérifie.
- **La garde de facturation** — `API-063`, livrée : elle lit les entités par la même source que ces
  deux booléens.
- **L'indicateur « vérifié en entretien »**, qui reste ce qu'il est et ne devient pas un signal de
  présence de compte rendu.

## Décisions de cadrage

### Deux booléens, et pas la réutilisation de « vérifié »

`isStar` est déjà sur la carte, et s'en servir pour le compte rendu ne coûterait rien. Écarté pour la
même raison que dans `API-063` : un profil peut avoir été rencontré sans que le compte rendu ait été
rédigé. Réutiliser l'indicateur ferait proposer l'achat d'un document qui n'existe pas — exactement
le défaut que ces deux cartes corrigent.

### ~~Les règles de présence sont écrites deux fois~~ — une seule source, décidé à l'implémentation

Cette carte prévoyait **deux implémentations** — la garde sur les entités, la carte pour le client —
reliées par une règle écrite dans le cadrage, et nommait leur divergence comme *le risque principal :
elle ne casse aucun test pris isolément, elle se voit seulement en confrontant les deux*.

`API-063` a tranché autrement, et cette carte suit. La règle vit dans
`Domain\Candidate\RevealableContent`, que le dévoilement **et** le mapper de sortie appellent. La
raison s'est vue en lisant le mapper : la présence d'un e-mail n'est pas « la personne en a un »,
c'est une résolution en deux temps — `Person` d'abord, réplica Jarvi ensuite. Réécrite de mémoire,
elle aurait divergé dès le premier jour, pas au premier changement.

**Conséquence sur le périmètre** : le mapper cesse aussi de recopier la résolution des coordonnées et
s'y branche. Le diff dépasse donc l'ajout des deux booléens — c'est le prix de la source unique, et
il se paie une fois.

### Un profil sans réplica ne fait plus tomber la lecture

La carte exigeait que les booléens sortent à faux pour un candidat jamais synchronisé. La lecture du
réplica était **non gardée** : elle aurait planté au lieu de répondre faux. Les quatre accès passent
donc par une lecture sûre — un réplica absent vaut « rien à servir », jamais une erreur.

## Règles et cas limites

- **Profil masqué** : les deux booléens sortent comme `hasEmail` et `hasPhone` le font déjà — la
  présence se dit avant le dévoilement, c'est tout leur intérêt.
- **CV avec plusieurs entrées** dont certaines sans fichier : vrai dès qu'**une** en porte un.
- **Compte rendu réduit à des espaces** : traité comme absent.
- **Réplica Jarvi manquant** — un candidat sans synchro : les deux sortent à faux, jamais nuls.
- **Le contrat ne perd rien** : ce sont deux ajouts, aucun champ existant ne change de forme.

## Documentation impactée

- [credits_et_devoilement.md](../cadrage/entreprise/api/credits_et_devoilement.md) — l'endroit où la
  règle de présence s'énonce une fois pour les deux implémentations. **Mis à jour.**
- [recherche_et_masquage.md](../cadrage/entreprise/api/recherche_et_masquage.md) — la joignabilité
  passe de deux à quatre présences, et le tableau du masquage avec elle. **Mis à jour**, non prévu au
  cadrage : c'est la page qui décrit ce contrat champ par champ.
- [README.md](../cadrage/entreprise/api/README.md) de l'espace — l'invariant de joignabilité y tient
  en une ligne, devenue incomplète. **Mis à jour.**

**Trois pages annoncées au cadrage n'ont finalement pas bougé, et c'est vérifié plutôt que
supposé** :

- [data-contracts.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md) n'énumère aucun
  champ : elle **énonce la règle** — la carte de recherche échappe à l'OpenAPI, son miroir s'écrit à
  la main dans la tâche de l'espace consommateur. Rien n'y devient faux ; `ENT-132` est précisément
  l'application de cette règle.
- [api-routes.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-routes.md) et
  [api-conventions.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/api-conventions.md) ne décrivent
  pas les DTO champ par champ — seul `api/openapi.json` porte la surface, et il est régénéré.

## Vérification

**Le cas qui sépare les deux signaux, observé sur une recherche réelle.** Deux profils tous deux
marqués « rencontré », servis par le même filtre — un seul a un compte rendu écrit :

```
019fc2ea-f2b4…   isStar ✓   hasReport = false   hasResume = false
019fc29d-b191…   isStar ✓   hasReport = true    hasResume = true
```

La base confirme ligne à ligne : le premier a une date d'entretien, aucun texte, aucun CV. Un écran
qui se serait appuyé sur « rencontré » aurait proposé d'acheter un document inexistant.

**Trois fiches contrastées**, choisies en base puis relues par l'API :

```
un CV + un compte rendu      → hasResume=true   hasReport=true
trois CV, aucun compte rendu → hasResume=true   hasReport=false
aucun CV                     → hasResume=false  hasReport=false
```

**Aucune valeur ne fuit** : sur le profil qui a les deux, `resumes` et `starInterviewReport` sont
absents de la réponse, et les accès payés sont à faux.

**Suites jouées** — les impactées seulement :

```
RevealableContentTest .................................. OK  11 tests, 26 assertions
CandidateTest .......................................... OK  31 tests, 279 assertions
RevealTest + DemoProfilesSearchTest + CardSkillsProfile   OK  40 tests, 298 assertions
deptrac ................................................ 0 violation
api/openapi.json ....................................... +6 lignes (les deux champs sur la fiche)
```

**Mesuré au passage** : sur les cinquante premières cartes du vivier, les deux nouveaux signaux sont
faux partout — et la base le confirme, aucun de ces profils n'a de CV ni de compte rendu. Le signal
est juste ; c'est le vivier qui est pauvre sur ces deux canaux.

## Livré

- `api/src/ApiResource/Output/CandidateCardOutput.php` **(M)** et `CandidateOutput.php` **(M)** —
  `hasResume` et `hasReport`, strictement symétriques de `hasEmail` / `hasPhone`.
- `api/src/State/CandidateMapper.php` **(M)** — les deux booléens sur la carte et la fiche ; la
  résolution des coordonnées cesse d'être recopiée et se branche sur la source unique.
- `api/src/Domain/Candidate/RevealableContent.php` **(M)** — lecture sûre du réplica : absent, il
  vaut « rien à servir » et non une erreur.
- `api/tests/Unit/RevealableContentTest.php` **(+)** — onze cas nommés, dont les deux qui séparent
  « la liste n'est pas vide » du CV réel, et « rencontré » du compte rendu.
- `api/tests/Functional/CandidateTest.php` **(M)** — les deux signaux sur la carte et sur la fiche,
  en miroir de ce qui couvrait déjà la joignabilité.
- `api/openapi.json` **(M)** — les deux champs sur le schéma de la fiche.
- `dev-book/cadrage/entreprise/api/recherche_et_masquage.md` **(M)**, `credits_et_devoilement.md`
  **(M)**, `README.md` **(M)** — la joignabilité passe de deux à quatre présences.

## Reste à faire (hors périmètre)

- **`ENT-132`** : le miroir `webapp/shared/types/candidate-card.ts` reçoit les deux booléens à la
  main, et l'écran éteint les canaux vides. Sans elle, le serveur refuse mais le bouton propose
  encore.
- **Conséquence à connaître** : sur le vivier actuel, ces deux canaux seront éteints sur
  l'écrasante majorité des profils. C'est le but, et ça se verra.

## Définition de terminé

- [x] `hasResume` et `hasReport` descendent sur la carte de recherche et sur la fiche.
- [x] Un profil sans CV et sans compte rendu les reçoit à faux, jamais nuls.
- [x] Un profil rencontré sans compte rendu écrit reçoit `hasReport` à faux — observé sur deux
      profils réels du même filtre, et couvert en unitaire.
- [x] Un CV dont l'unique entrée ne porte pas de fichier donne `hasResume` à faux.
- [x] Aucune valeur de CV ni de compte rendu n'est servie avant dévoilement.
- [x] `api/openapi.json` est régénéré et présent dans le commit.
- [x] La règle de présence est écrite au même endroit que celle de la garde — et **implémentée** au
      même endroit, ce que la carte ne prévoyait pas : voir *Décisions de cadrage*.
