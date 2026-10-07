# TASK ENT-139 — l'interrupteur du poste porte sa réserve dans son libellé

- **Créée le** : 2026-08-18.
- **Session** : `a2b26213-41c5-4616-8c5c-7b07d63b0ef1`.
- **Statut** : ✅ fait le 2026-08-18 — l'interrupteur s'appelle « Occupe ce poste aujourd'hui » dans
  les trois langues, sa phrase d'avertissement a disparu, et le jeu qu'il rend est inchangé.
- **Type** : refactor — trois clés de langue et un commentaire ; aucun comportement ne change.
- **Tests** : `webapp/base/i18n/locales/parity.vitest.ts`, qui existe déjà et couvre la réécriture
  comme la suppression — une clé retirée d'une seule langue le ferait échouer. **Aucun test nouveau** :
  la tâche ne produit aucun code exécutable, seulement du texte affiché.
- **Réf** : amont `ENT-113` (l'interrupteur et sa phrase) · voisin `API-044`, qui a posé la règle de
  correspondance côté serveur · mesure du 2026-08-18 sur la base de développement.

## Contexte

Un recruteur, espace entreprise, Visionneuse, **colonne de gauche**, tout en haut du panneau
d'affinage — au-dessus des volets repliés, un interrupteur seul sur sa ligne. Il ne paraît que si la
recherche nomme un métier.

Aujourd'hui il s'appelle **« Poste actuel »**, et traîne sous lui une ligne en petits caractères gris :

> *Écarte aussi les profils dont le poste du moment n'a pas pu être établi.*

Cette phrase dit vrai, et ce qu'elle dit est lourd. Mesuré sur la base de développement :

```
candidats                                       3 899
poste du moment établi par le serveur           2 931   (75,2 %)
poste du moment IMPOSSIBLE à établir              968   (24,8 %)
```

**Un profil sur quatre.** Cocher l'interrupteur ne garde pas « ceux qui font ce métier aujourd'hui »,
mais « ceux dont on a **pu établir** qu'ils le font aujourd'hui ». Les autres sortent sans qu'on sache
s'ils ont changé de métier ou si l'information manquait.

Le problème n'est pas la phrase, c'est **le libellé au-dessus d'elle**. « Poste actuel » ne promet rien
de précis, ce qui oblige à tout rattraper en dessous — à l'endroit du produit que personne ne lit.

## Périmètre

- **Le libellé devient « Occupe ce poste aujourd'hui »**, dans les trois langues.
- **La phrase d'avertissement est supprimée**, dans les trois langues.
- **Le commentaire du composant** qui justifie la présence de cette phrase, réécrit pour dire ce que
  porte désormais le libellé.

## Hors périmètre

- **Le comportement du filtre ne bouge pas d'un pouce** : mêmes profils gardés, mêmes profils écartés.
  Seuls les mots changent.
- **La règle de correspondance côté serveur**, qui décide de ce qu'est un poste du moment.
- **Le taux de 24,8 %** : l'améliorer suppose de mieux résoudre les postes à l'import, ce qui appartient
  à la lane `api` et n'est pas carté.
- **Les autres libellés du panneau.**

## Décisions de cadrage

### Réécrire plutôt que supprimer sèchement

La demande était de supprimer la phrase. **Supprimée seule**, elle aurait laissé un interrupteur qui
fait disparaître un quart du vivier sans qu'aucun mot ne l'annonce, et sans que rien à l'écran permette
de s'en apercevoir.

Une affirmation sur la personne — « occupe » — ne peut se faire que pour ceux dont on connaît le poste.
L'exclusion des inconnus **découle du libellé** au lieu d'être une note de bas de page. La phrase
disparaît donc bien, comme demandé, mais l'information qu'elle portait survit dans le libellé.

### Ni « confirmé », ni « en poste sur ce métier »

**« Poste actuel confirmé »** — écarté : « confirmé » suggère une vérification humaine qui n'a pas eu
lieu. C'est une déduction du serveur à partir des dates du parcours, et showcase-recruiter-webapp-saas réserve la notion de
vérification aux profils passés en entretien.

**« En poste sur ce métier aujourd'hui »** — écarté sur la longueur seule, dans une colonne étroite.
L'idée était équivalente.

### Pourquoi une carte à part

`ENT-138` refond des rangées d'étiquettes dans le même panneau. Celle-ci réécrit un interrupteur et
trois clés de langue. Deux natures de travail, deux commits — mais **le même fichier de panneau**, donc
elles ne peuvent pas avancer en même temps.

## Règles et cas limites

- **L'interrupteur ne paraît que si la recherche nomme un métier** — comportement actuel, conservé :
  sans métier cherché, aucun profil ne porte de correspondance de poste et l'affinage viderait le jeu
  sans qu'on puisse comprendre pourquoi.
- **La clé supprimée doit l'être dans les trois langues.** Une clé orpheline dans une seule langue fait
  échouer le test de parité — c'est voulu, et c'est le filet.
- **Aucun texte utilisateur en dur** : le libellé passe par une clé, comme aujourd'hui.
- **L'arabe se lit de droite à gauche** : le libellé s'allonge un peu dans les trois langues, la ligne
  doit rester sur une ligne.

## Documentation impactée

- **Aucune page de `docs/showcase-recruiter-webapp-saas/`.** Le tableau des déclencheurs ne s'applique pas : ni surface
  d'API, ni entité, ni contrat, ni convention de dossier. Seul du texte affiché change.
- **`dev-book/cadrage/entreprise/criteres_de_filtrage_profils.md`** cite ce filtre, mais le cadrage
  suit son propre rythme et reste hors périmètre d'un commit.

## Vérification

**Le comportement est identique**, et c'était la seule chose à prouver. Interrupteur manœuvré à
l'écran sur « Preuve ENT-113 », rang lu dans la barre haute :

```
au repos      Profil 1 sur 641
coché         Profil 1 sur 173     le filtre agit
décoché       Profil 3 sur 641     et se relâche
```

Le total retrouve exactement sa valeur. Le rang de départ diffère parce que le parcours a avancé
d'un profil entre deux clics — la pile défile, le jeu non.

**L'écran** : l'interrupteur tient sur une ligne, sans rien en dessous. Capture du panneau, thème
sombre, compte `owner@demo.showcase-recruiter-webapp-saas.example`.

**Aucune clé orpheline** : `currentJobHint` ne se trouve plus nulle part dans `webapp/`.

**Test de parité des trois langues : 9 verts**, joué depuis `webapp/`.

## Livré

- `webapp/base/i18n/locales/fr.yaml` — « Occupe ce poste aujourd'hui », réserve supprimée.
- `webapp/base/i18n/locales/en.yaml` — « Currently in this position », réserve supprimée.
- `webapp/base/i18n/locales/ar.yaml` — « يشغل هذا المنصب اليوم », réserve supprimée.
- `webapp/spaces/entreprise/app/components/search/RefinePanel.vue` — la description quitte
  l'interrupteur ; son commentaire dit désormais ce que le libellé porte.

## Reste à faire (hors périmètre)

- **Le taux de 24,8 % de postes du moment non résolus** ne bouge pas : l'améliorer suppose une
  meilleure résolution à l'import, côté `api`. Non carté.

## Définition de terminé

- [x] L'interrupteur s'appelle « Occupe ce poste aujourd'hui » en français, et l'équivalent en anglais
      et en arabe.
- [x] La phrase d'avertissement n'existe plus dans aucune des trois langues.
- [x] Le test de parité des langues est vert.
- [x] Le commentaire du composant dit ce que porte le libellé, sans raconter le changement.
- [x] Cocher l'interrupteur donne exactement le même jeu de profils qu'avant — vérifié à l'écran.
