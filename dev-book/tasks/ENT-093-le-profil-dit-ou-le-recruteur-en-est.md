# TASK ENT-093 — entreprise : le profil dit où le recruteur en est

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — le catalogue passe de cinq à sept états, et le bas de la
  colonne du verdict dit où le recruteur en est sur ce profil.
- **Type** : ajout de deux états au catalogue, plus leurs libellés.
- **Tests** : unitaires sur `services/search/states.ts`, qui en a déjà — chacun des deux états
  paraît quand son drapeau est vrai, jamais quand il est faux ou nul, et l'ordre du catalogue ne
  bouge pas.
- **Réf** : `ENT-092` — qui pose la zone basse que ces états remplissent · `services/search/states.ts`
  et son fichier de tests · `shared/types/candidate-card.ts` — `isSeen` et `isDismissed`, déjà là.

## Contexte

Le catalogue des états d'un profil en compte cinq : rencontré, favori, disponible, nouveau, au Maroc.
Tous décrivent **ce que le profil est**, ou ce que le recruteur a produit sur lui.

Il manque **où le recruteur en est** : ce profil, l'ai-je déjà regardé ? l'ai-je écarté ? Les deux
chiffres existent dans le panneau de gauche — « Vus », « Écartés » — mais **agrégés sur la
recherche entière**. Rien ne le dit profil par profil, là où la question se pose.

La donnée, elle, est déjà servie : `isSeen` et `isDismissed` descendent avec chaque fiche, à côté de
`isFavorite`. Aucun appel serveur n'est nécessaire.

## Périmètre

- **« Vu » et « Écarté » entrent au catalogue** des états, avec leur teinte, leur icône et leur
  libellé dans les trois langues.
- **Leurs tests** rejoignent ceux des cinq autres.

## Hors périmètre

- **Le contrat serveur**, qui porte déjà les deux drapeaux.
- **La Table**, qui ne lit du catalogue que « Rencontré » et ne verra donc rien changer.
- **Le tiroir des profils écartés**, qui liste des noms et non des fiches — voir *Risques*.
- **Les deux archives**, qui appellent le même catalogue et hériteront des badges sans qu'on les
  touche.

## Décisions de cadrage

### « Vu » s'affiche aussi sur le profil qu'on regarde

**Arbitré.** Une règle sans exception : le profil est vu, le badge le dit. Sur celui qu'on a sous les
yeux, il paraît après la seconde d'arrêt qui vaut « vu » — **exactement quand le compteur « Vus » du
panneau de gauche s'incrémente**. Les deux disent la même chose au même instant, ce qui les rend
lisibles ensemble.

L'autre lecture — le taire sur le profil courant, au motif qu'on est en train de le voir — supposait
que le catalogue sache lequel est courant. Il ne le sait pas, et n'a pas à l'apprendre : il décrit un
profil, il ne connaît pas l'écran.

### « Écarté » entre, même sans écran pour le montrer

**Arbitré, et c'est la décision inconfortable de cette carte.** Écarter sort le profil de la pile :
le badge ne se verra **nulle part aujourd'hui**. Le tiroir des écartés liste des noms, la Table ne
lit que « Rencontré », et la pile ne sert plus le profil.

Il entre quand même, pour une raison de cohérence : le catalogue décrit **où en est le recruteur**,
et une moitié de cette réponse manquerait sans lui. Le jour où un écran montre un profil écarté, il
le dira sans qu'on y repense — et cette carte-ci écrit noir sur blanc pourquoi il dort.

### La teinte : AUCUNE hue nouvelle — tranché à l'implémentation

Le cadrage laissait ce point ouvert, en disant qu'il se jugerait sur rendu. Il s'est tranché avant :
les quatre teintes d'état sont prises, et en ouvrir une cinquième mettrait ces deux marques **au rang
d'un attribut du candidat**. Or elles ne disent pas ce que le profil est.

- **Vu** porte `--muted`, le gris du texte discret : un constat, pas un attribut.
- **Écarté** emprunte `--verdict-missed`, la teinte qui compte déjà les écartés dans le panneau de
  gauche. Même notion, même couleur, à deux échelles — et un second usage pour un token qui n'en
  avait plus qu'un.

Les icônes suivent la même logique : l'œil et l'œil barré, ceux des deux décomptes du panneau. Un
badge et son total se reconnaissent.

## Règles et cas limites

- **Hors d'une recherche**, `isSeen` et `isDismissed` valent `null` : aucun badge. Le catalogue
  compare donc à `true`, jamais à la seule véracité — `null` n'est pas « non ».
- **Un profil vu ET écarté** porte les deux badges : ce sont deux faits distincts, et écarter
  suppose d'avoir vu.
- **L'ordre du catalogue** décide de l'ordre à l'écran. Les deux nouveaux se placent **après** les
  cinq existants : ils parlent du parcours, pas du profil.
- **Aucun état** reste possible et la zone basse garde sa hauteur — c'est `ENT-092` qui la pose.

## Contraintes transverses

- **i18n** : deux libellés dans `webapp/base/i18n/locales/{fr,en,ar}.yaml`, sous `search.badge`.
  Fichiers **partagés avec les autres sessions** : staging chirurgical au commit.
- **Accessibilité** : le badge porte son libellé écrit, l'icône n'est qu'un repère.
- **Performance** : le catalogue est un filtre sur cinq entrées, il en aura sept.

## Documentation impactée

⚠️ **Écart au cadrage.** La carte annonçait qu'aucune page n'était touchée, au motif que le catalogue
des états n'est énuméré nulle part. C'est faux : **`4.design/brand-guidelines.md` tient la table des
cinq teintes d'état** et la règle qui les gouverne. Deux états de plus sans un mot laisseraient
croire que la table est complète.

- **`docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md`** — un paragraphe dit pourquoi ces deux-là
  n'ouvrent aucune teinte : ils décrivent le parcours du recruteur, pas le profil, et une sixième hue
  les mettrait au rang d'un attribut du candidat.

`services/README.md` gouverne le fichier de code touché — relu avant d'écrire, inchangé.

## Vérification

### Les deux badges, rendus

Zone basse du verdict, sur le profil courant :

```
Favori   color(srgb 0.861 0.941 0.640)   l'accent
Vu       color(srgb 0.781 0.781 0.781)   le gris
```

### « Écarté » n'est prouvé QUE par les tests

Et c'est la décision de cadrage, pas une lacune : écarter sort le profil de la pile, le tiroir des
écartés liste des noms, la Table ne lit que « Rencontré ». Aucun écran ne le montre aujourd'hui.

```
$ bunx vitest run services/search/states.vitest.ts
  Test Files  1 passed (1)
       Tests  12 passed (12)
```

Quatre cas neufs : chaque drapeau produit son badge · `null` et `false` n'en produisent aucun · vu et
écarté se cumulent · les deux ferment la marche derrière les cinq attributs.

### Les conventions

```
$ bun run quality-conventions-check   (sur l'hôte)   → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `services/search/states.ts` | (M) deux états au catalogue, en queue, sans teinte nouvelle |
| `services/search/states.vitest.ts` | (M) quatre cas, dont le `null` et le cumul |
| `base/i18n/locales/{fr,en,ar}.yaml` | (M) « Vu » et « Écarté » dans les trois langues |
| `docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md` | (M) pourquoi ces deux-là n'ouvrent aucune hue |

## Reste à faire (hors périmètre)

- **Donner un écran à « Écarté »** — le tiroir des profils écartés montre des noms ; s'il montrait
  des fiches, le badge servirait dès le premier jour.
- **Surveiller « Vu »** : il sera présent sur presque tous les profils d'une pile parcourue. Si la
  rangée devient bruyante, c'est lui qu'il faudra revoir en premier.

## Dépendances

- **Amont** : `ENT-092`, qui pose la zone basse où ces badges s'affichent. Sans elle, ils
  apparaîtraient dans un bas de colonne qui saute d'un profil à l'autre.
- **Aval** : rien d'obligatoire. Le tiroir des écartés pourrait montrer les pastilles — c'est ce qui
  donnerait enfin un écran à « Écarté ».

## Risques et reports assumés

- **« Écarté » ne s'affiche nulle part** au terme de cette carte. C'est assumé et écrit ; le risque
  réel est qu'on l'oublie et qu'on le recrée ailleurs. La ligne *Aval* est là pour ça.
- **« Vu » sera présent sur presque tous les profils** d'une pile déjà parcourue. Si la rangée
  devient bruyante à l'usage, c'est le badge le plus fréquent qu'il faudra revoir en premier.

## Définition de terminé

- [x] « Vu » paraît quand `isSeen` vaut vrai, jamais sur `false` ni `null`.
- [x] « Écarté » paraît quand `isDismissed` vaut vrai, aux mêmes conditions.
- [x] Les deux se placent après les cinq états existants.
- [x] Les libellés existent dans les trois langues.
- [x] Les tests du catalogue couvrent les deux, y compris le cas `null`.
