# TASK ENT-059 — entreprise : les trois langues de la carte de recherche se rejoignent

- **Créée le** : 2026-08-06.
- **Session** : `cd8779a6-574b-4406-add4-fbbedd8ef042` — **reprise le 2026-08-10**, sur décision
  explicite de l'utilisateur. Le cadrage vient de la session `393e1873-d1a9-4645-87a7-eb7cb18cf97b`,
  perdue depuis : ses arbitrages ci-dessous ont donc été pris ailleurs, et sont appliqués tels
  quels. Toute décision qui aurait besoin d'être rediscutée se signale avant d'être suivie.
- **Statut** : ✅ fait le 2026-08-10 — les trois fichiers portent le **même jeu de 627 clés**, avec
  les mêmes paramètres nommés et les mêmes formes plurielles. La carte de recherche se lit
  entièrement en anglais et en arabe : plus un mot français. Le test de parité **a été mis à
  l'épreuve**, pas seulement déclaré vert — quatre défauts injectés, quatre défauts nommés. Un écart
  au cadrage assumé : une clé antérieure y est entrée, cf. *Décisions de cadrage*.
- **Type** : Frontend entreprise — contenu de traduction, plus un test qui empêche la dérive de
  recommencer. Aucun écran, aucun contrat, aucun composant touché.
- **Tests** : unitaires (vitest) sur la **parité des trois fichiers de langue** — aucune clé
  française absente ailleurs, aucune clé étrangère sans équivalent français. C'est le seul code
  exécutable de la tâche, et c'est aussi ce qui la prouve : le décompte se lit dans sa sortie.
- **Réf** : **`ENT-050`, `ENT-051`, `ENT-055` — amont** (elles ont posé les vingt-sept clés en
  français seulement) · `webapp/base/i18n/locales/{fr,en,ar}.yaml` ·
  [i18n.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/i18n.md)

## Contexte

**L'anglais et l'arabe ont vingt-sept clés de moins que le français**, et une clé morte en trop.
Mesuré : 603 clés en français, 577 dans chacune des deux autres.

Les vingt-sept manquantes sont **toutes sous `search.`** — dix pour la carte de recherche, six pour
sa clôture, onze pour sa gestion. Ce sont exactement les blocs posés par les trois tâches qui
viennent de passer, chacune ayant écrit son texte en français et laissé les deux autres langues où
elles étaient.

**Le défaut ne se voit pas, et c'est ce qui le rend durable.** Le français est la langue de repli
déclarée : une clé absente n'affiche pas un fragment technique, elle affiche **le texte français**.
L'écran reste donc lisible, rien ne signale l'anomalie — sauf à un recruteur anglophone qui tombe sur
« 12 nouveaux » au milieu de sa page, ou à un lecteur arabophone dont l'interface bascule
silencieusement de langue et de sens de lecture au milieu d'une carte.

**Rien ne surveille cette parité** : ni les treize règles vérifiées à la fusion, ni aucun test. C'est
pourquoi trois tâches d'affilée ont pu creuser l'écart sans que personne le voie.

Dans l'autre sens, `search.card.results` existe en anglais et en arabe mais plus en français :
**aucun écran ne l'appelle** — vérifié sur tout le webapp.

## Périmètre

- **Les vingt-sept clés manquantes sont écrites**, en anglais et en arabe.
- **La clé morte `search.card.results` est retirée** des deux fichiers.
- **Un test de parité** compare les trois fichiers dans les deux sens.

## Hors périmètre

- **Le français** : il est la référence, il ne bouge pas.
- **Le reste de l'interface** : les 576 clés déjà présentes partout ne sont ni relues ni retouchées.
  Cette tâche comble un écart, elle n'audite pas la qualité des traductions existantes.
- **Une règle bloquante à la fusion** : elle rejoindrait les treize règles de conventions, dont le
  périmètre est le socle — donc une autre lane. Le test suffit ici, cf. *Décisions de cadrage*.
- **Le miroir droite-à-gauche** de ces écrans : les composants touchés utilisent déjà des propriétés
  logiques, et aucun texte neuf n'introduit de mise en page. Vérifié à la capture, pas retravaillé.

## Décisions de cadrage

**Tout combler d'un coup, pas par morceaux.** L'écart est déjà identifié et borné ; le combler en
plusieurs fois laisserait un état intermédiaire que personne ne saurait relire — et il faudrait
refaire l'inventaire à chaque reprise. Une passe, et les trois langues repartent du même point.

**Les traductions sont écrites sans relecture humaine, et c'est un risque accepté.** L'anglais est du
vocabulaire produit courant. L'arabe engage davantage : registre, accords, et un système de pluriel
qui compte six formes là où le français en a deux. Le fichier arabe existant n'en emploie que
**deux**, comme le français — cette tâche suit cette convention plutôt que d'en introduire une
nouvelle au détour d'un comblement. Si une formulation choque, elle se corrige ensuite : le repli
français d'aujourd'hui n'est pas meilleur qu'une traduction imparfaite.

**Un test plutôt qu'une règle bloquante.** Une règle de conventions vit dans le socle et bloque la
fusion pour tout le dépôt ; c'est le bon outil, mais il appartient à une autre lane et sortirait
d'ici. Le test, lui, tourne avec les autres, échoue le jour où quelqu'un oublie une langue, et
**donne à la tâche sa seule preuve exécutable** — sans lui, tout reposerait sur une lecture à l'œil
de trois fichiers de six cents lignes.

**Le test regarde dans les deux sens.** Vérifier seulement ce qui manque laisserait s'accumuler des
clés mortes comme `search.card.results` : elles font croire à une couverture qui n'existe pas, et
elles survivent aux écrans qui les appelaient.

**La tâche est rattachée à l'entreprise, pas au socle.** Les fichiers de langue vivent dans
`webapp/base/`, partagé — mais les vingt-sept clés concernées ne servent qu'à l'Espace Entreprise.
La règle de routage attache le transverse à l'espace **consommateur**.

### ÉCART AU CADRAGE — une clé antérieure est entrée, et le test s'est durci

Le cadrage écartait « les clés déjà présentes partout ». L'inventaire a montré que
`search.card.more` existait bien dans les trois langues, mais que l'anglais et l'arabe avaient perdu
**et son pluriel et son mot** : le français dit « +3 critères », les deux autres disaient « +3 » tout
court — le chiffre sans ce qu'il compte. Entrée dans la tâche sur décision explicite de
l'utilisateur, parce qu'elle débloque un test plus fort : sans elle, vérifier les paramètres et les
formes plurielles aurait échoué dès le premier jour sur un défaut antérieur.

Le test vérifie donc **quatre** choses au lieu de deux : clés manquantes, clés mortes, paramètres
nommés, nombre de formes plurielles.

### Deux corrections apportées au test lui-même

Il **échouait à tort** sur une traduction arabe juste — `'ملف واحد مطابق | {count} ملفات مطابقة'`,
dont la branche singulière écrit « un seul fichier » en toutes lettres plutôt que « 1 fichier ».
Comparer le nombre d'occurrences d'un paramètre punissait ce choix : il compare désormais
l'**ensemble** des paramètres, ce qui attrape toujours un `{name}` perdu sans condamner une tournure
idiomatique.

Il mettait **17 secondes** parce qu'il rechargeait et réaplatissait les fichiers à chaque clé. Ils
sont lus une fois : **0,26 seconde** pour le même verdict.

## Règles et cas limites

- **Le français est la source** : toute divergence se résout en alignant les deux autres sur lui.
- **Les formes plurielles se comptent** : une clé française à deux branches (`profil | profils`) doit
  en avoir deux dans les deux autres langues, sinon le pluriel se casse en silence.
- **Les paramètres nommés doivent survivre à la traduction** : `{count}`, `{name}`, `{date}`. Une
  clé traduite qui perd son `{name}` affiche une phrase amputée, et rien ne le signale.
- **L'arabe est droite-à-gauche** : les textes neufs ne doivent contenir aucun caractère de
  ponctuation latin isolé qui casserait le sens de lecture d'une ligne mixte.
- **Le test doit nommer les clés fautives**, pas seulement compter : un test qui dit « 3 clés
  manquent » sans dire lesquelles se corrige à l'aveugle.
- **Le test lit les fichiers, pas la configuration de Nuxt** : il doit tourner sans démarrer
  l'application.
- **Aucune clé n'est inventée** : le test compare des ensembles, il ne juge pas les contenus.

## Documentation impactée

- **[i18n.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/i18n.md)** — elle décrit les trois langues et
  la règle du repli français, mais ne dit nulle part que la parité est **vérifiée**. Le test devient
  un fait d'architecture : à y écrire, avec ce qu'il refuse.

Aucun `README.md` ne change de convention : `base/i18n/locales/` n'en a pas, et celui de `base/app/`
ne gouverne pas les fichiers de langue.

## Définition de terminé

- [x] Les trois fichiers de langue portent exactement le même jeu de clés — **627 partout**.
- [x] Les vingt-sept clés manquantes sont traduites en anglais et en arabe.
- [x] `search.card.results` a disparu des deux fichiers où elle traînait.
- [x] Chaque clé traduite conserve les paramètres nommés et le nombre de formes plurielles de son
      équivalent français — **zéro anomalie sur les 627**.
- [x] Un test échoue si une clé existe dans une langue et manque dans une autre, **dans les deux
      sens**, et il nomme les clés fautives — démontré en injectant quatre défauts.
- [x] La carte de recherche a été regardée en anglais et en arabe, et plus aucun mot français n'y
      apparaît.
- [x] La page d'architecture i18n dit que la parité est vérifiée.

## Vérification

### La parité, avant et après

| | fr | en | ar |
| --- | --- | --- | --- |
| Avant | 627 | 601 | 601 |
| Après | **627** | **627** | **627** |

Vingt-sept clés manquaient dans chacune des deux langues — **exactement les mêmes** —, et une clé
morte y traînait. Après : zéro écart dans les deux sens, zéro divergence de paramètre nommé, zéro
divergence de forme plurielle, sur les 627 clés.

### Le test sait échouer, et c'est ce qui compte

Un test vert ne prouve rien tant qu'on ne l'a pas vu tomber. Quatre défauts injectés dans le fichier
anglais, quatre défauts **nommés** :

```
+ "search.card.noKeywords"                                          ← clé retirée
+ "search.card.ghost"                                               ← clé morte ajoutée
+ "search.card.closedOn — attendu ["date","name"], trouvé ["date"]" ← paramètre perdu
+ "search.card.profiles — attendu 2 forme(s), trouvé 1"             ← pluriel effondré
```

Fichier restauré, 9 assertions vertes en 0,26 s.

### L'écran, regardé dans les deux langues

Sur la liste des recherches, connecté en recruteur : **aucun** des vingt-cinq mots français des clés
traduites ne subsiste. Les seuls mots latins restants sont « comptable », « commercial » et
« Développeur » — ce sont les **noms des recherches**, donc de la donnée saisie.

| | Ce qui s'affiche |
| --- | --- |
| Anglais | 125 profiles · nothing new · 0 seen · 0 shortlisted · no keywords · Browse profiles |
| Arabe (rtl) | ملفات · لا جديد · مُشاهَدة · مُختارة · لا كلمات مفتاحية · تصفّح الملفات |

### Les gates jouées ici

| | |
| --- | --- |
| Suite complète | **340 tests**, 24 fichiers |
| Conventions d'architecture | 19 règles, aucune violation |

## Livré

- `webapp/base/i18n/locales/en.yaml` (M) — vingt-sept clés traduites, la clé morte retirée, et le
  pluriel de `search.card.more` rétabli avec son mot.
- `webapp/base/i18n/locales/ar.yaml` (M) — idem, en suivant la convention à **deux formes** du
  fichier existant plutôt que d'introduire les six de l'arabe au détour d'un comblement.
- `webapp/base/i18n/locales/parity.vitest.ts` (+) — le test, colocalisé avec les fichiers qu'il
  surveille. Il tire la liste des langues du registre partagé : une langue ajoutée là est couverte
  ici sans rien toucher.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/i18n.md` (M) — la page disait le repli français, jamais que
  la parité était vérifiée. Elle énonce maintenant les quatre dérives refusées et pourquoi le test
  compare des ensembles.

## Reste à faire (hors périmètre)

- **Les traductions n'ont pas été relues par un humain**, et c'est le risque accepté au cadrage.
  L'arabe engage le plus : registre, accords, et un système de pluriel réduit ici à deux formes par
  cohérence avec l'existant. Une formulation qui choque se corrige ensuite — le repli français
  d'avant n'était pas meilleur.
- **Une règle bloquante à la fusion** reste plus forte qu'un test : elle vit dans le socle, donc
  dans une autre lane. Le test suffit tant que la suite tourne.
- **Les 576 clés déjà présentes partout** n'ont pas été relues : cette tâche comble un écart, elle
  n'audite pas la qualité de l'existant. Le test, lui, surveille désormais leur forme.
