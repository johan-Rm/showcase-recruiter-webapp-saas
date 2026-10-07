# TASK ENT-083 — entreprise : les critères deviennent des cartouches, et la valeur passe en infobulle

- **Créée le** : 2026-08-11.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-11 — chaque critère est une cartouche teintée au verdict, ouverte
  par un disque plein, fermée par son verdict écrit. Ce que le profil porte en face part dans une
  infobulle. **Le socle reçoit au passage le `<UApp>` qui lui manquait.**
- **Type** : refonte d'un bloc d'interface, plus un correctif de socle sans lequel elle est
  impossible.
- **⚠️ Cadrée après coup.** Demande faite en conversation à partir d'une lecture d'écran, dessinée en
  ASCII puis validée avant écriture. Deux arbitrages posés à l'humain — la forme du disque et le sort
  de l'information au clavier — plus un troisième découvert en cours de route.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle réorganise un gabarit, ajoute
  des classes de teinte et enveloppe conditionnellement une infobulle. La preuve est le **rendu
  serveur compté** (216 cartouches, exactement 5 focalisables) et l'**infobulle déclenchée** sur le
  profil concerné.
- **Réf** : `ENT-076` — qui a écrit cette liste et posé ses règles de couleur · `ENT-047` — la
  passivité de la liste, inchangée · la note de socle sur l'absence de `<UApp>`, qui laisse les
  textes intégrés de Nuxt UI en anglais et que cette carte **rend enfin corrigeable**.

## Contexte

La colonne du verdict alignait des lignes nues : le critère à gauche, puis — selon les cas — la
valeur reconnue du profil, ou le mot « Trouvé », ou « poste passé », puis un ✓ en bout de ligne.

Deux défauts, et le second est celui qui a déclenché la demande :

- **Rien ne se balayait.** « Trouvé » n'apparaissait qu'une ligne sur trois ; les autres se
  terminaient par un texte tronqué. L'œil devait lire chaque ligne pour savoir où elle atterrissait.
- **Le texte tronqué mentait par omission.** Il coupait par le DÉBUT, quel que soit l'endroit où le
  mot cherché avait été reconnu. Sur `scrum`, le profil affichait « Senior Product … » — et le mot
  cherché se trouvait quatre-vingt-dix caractères plus loin, dans « Scrum Certified ». Ça se lisait
  comme si la recherche avait accroché n'importe quoi.

## Périmètre

- **Chaque critère devient une cartouche** : fond à la teinte du verdict, très dilué.
- **Un disque plein ouvre la cartouche**, portant le ✓ ou le ✗ détouré.
- **Le verdict ferme chaque cartouche**, sans exception.
- **La valeur reconnue part dans une infobulle**, au survol et à la tabulation.
- **`<UApp>` à la racine du socle** — sans lui, l'infobulle fait tomber le rendu serveur.

## Hors périmètre

- **Les textes intégrés de Nuxt UI en anglais.** `<UApp>` accepte une locale, et c'est le préalable
  qui manquait pour les traduire. **Nommé, pas fait** : c'est une autre carte, et elle touche les
  quatre espaces.
- **La troncature centrée sur le mot trouvé.** L'infobulle rend le libellé entier ; mettre le mot
  reconnu en évidence à l'intérieur reste une amélioration possible, non demandée.
- **Les deux archives**, qui gardent l'ancienne liste.

## Décisions de cadrage

### La valeur quitte la ligne, et le verdict la remplace partout

C'est la décision qui porte le reste. Tant que la valeur occupait la fin de ligne, le verdict n'avait
nulle part où s'écrire — d'où « Trouvé » une ligne sur trois. En la sortant, **chaque cartouche finit
par son verdict**, à la même abscisse, et la colonne redevient balayable.

Le « quand » garde sa préséance sur le métier : « poste passé » dit déjà que le critère est
satisfait, et l'information utile n'est pas qu'il l'est, c'est quand.

### Le disque porte le signe, il ne se contente pas de la couleur

Arbitré. Une pastille pleine sans signe aurait laissé la couleur seule distinguer les deux rangs —
illisible pour qui distingue mal le vert du rouge. Le disque est plein, à la teinte du verdict, et le
✓ ou le ✗ y est détouré dans la couleur de la surface.

### Le fond à dix pour cent, et pas davantage

Six cartouches empilées dans une colonne de trois cents pixels font six bandes colorées. La charte
prévient : au-delà, elles ne hiérarchisent plus, elles saturent. À cette dilution le fond regroupe la
ligne sans réclamer le regard — c'est le disque qui porte le signal.

### L'infobulle n'enveloppe que ce qui a quelque chose à dire

Une cartouche dont le profil ne dit rien de plus n'a pas à devenir un point d'arrêt du clavier : on
tabulerait sur six éléments pour n'en lire qu'un. Sur la recherche témoin, **5 cartouches sur 216**
sont concernées.

### ⚠️ Une infobulle qu'on ne sait pas où chercher n'existe pas

Défaut de mon propre dessin, constaté à l'essai et corrigé dans la foulée.

N'envelopper que ce qui a quelque chose à dire est juste ; ne rien en montrer ne l'est pas. Sur la
recherche témoin, **5 cartouches sur 216** portent une infobulle — et rien ne les distinguait des
211 autres. Le premier profil de la liste n'en a **aucune**. Survoler devenait un pari perdu dans
quatre-vingt-dix-sept pour cent des cas, et on en concluait que la fonction ne marchait pas.

Deux corrections, et elles vont ensemble :

- **Le nom du critère prend un souligné pointillé**, et le curseur devient un point d'interrogation —
  la convention universelle de « il y a plus à lire ici ». Uniquement là où c'est vrai.
- **Le délai d'apparition passe de 754 ms à 250** — mesuré des deux côtés. Au défaut de la
  bibliothèque, un survol de lecture traverse la cartouche sans rien déclencher.

### L'information reste atteignable sans survol

Arbitré. Une infobulle au survol n'existe ni au doigt ni au clavier — or c'est précisément sur
téléphone que la Visionneuse empile tout en une colonne. Les cartouches qui portent une infobulle
sont donc **focalisables**, et la valeur est **doublée en `sr-only`** : l'infobulle sert le confort,
elle ne détient pas l'information.

### ⚠️ Écart découvert — le socle n'avait pas de `<UApp>`

Non prévu au cadrage, et bloquant : une `UTooltip` sans `<UApp>` ne se contente pas de ne pas
s'afficher, elle **fait tomber le rendu serveur entier** :

```
Injection `Symbol(TooltipProviderContext)` not found.
Component must be used within `TooltipProvider`
```

Nuxt UI n'expose pas de fournisseur isolé — seul `<UApp>` l'installe. Trois issues étaient possibles
(l'infobulle native du navigateur, le fournisseur importé à la main dans ce seul composant, ou poser
le `<UApp>` manquant) ; arbitré avec l'humain pour la troisième. Elle débloque du même coup les
fenêtres et les notifications Nuxt UI pour tout le produit.

## Règles et cas limites

- **Aucun profil à confronter** (`matched` vaut `null`) : pas de disque, cartouche grise neutre — il
  n'y a rien à trancher.
- **La valeur identique à la demande** ne produit pas d'infobulle : le service l'efface en amont, et
  « Méthodes agiles » face à « méthodes agiles » n'a rien à ajouter.
- **`truncate` sur le seul critère** : c'est lui qui peut être long, et le verdict qui ferme la
  cartouche ne doit jamais céder sa place.
- **Le repli en pied de fiche** sous 1280 px porte les mêmes cartouches — un seul composant.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni convention de dossier. La charte
(`4.design/design-system.md`) prévient contre la saturation par la couleur — cette carte l'applique
plutôt qu'elle ne la modifie.

## Définition de terminé

- [x] Chaque critère est une cartouche teintée au verdict, disque plein à gauche.
- [x] Chaque cartouche finit par son verdict écrit, ou par le « quand » du métier.
- [x] La valeur reconnue s'affiche au survol et à la tabulation, et reste lisible aux lecteurs
      d'écran.
- [x] Seules les cartouches qui ont quelque chose à dire sont focalisables.
- [x] Le rendu serveur ne tombe plus.

## Vérification

### Le rendu serveur, compté

```
cartouches rendues                    216      (36 fiches × 6 critères)
cartouches focalisables                 5
profils ayant une valeur à montrer      3 sur 17   (mesuré côté API)
```

Cinq focalisables pour trois profils portant respectivement 1, 3 et 1 critères à valeur
différente — les deux comptes tombent d'accord.

### L'enveloppe est bien une infobulle

```html
<div data-state="closed" data-grace-area-trigger
     class="… rounded-md px-2 py-1 … bg-verdict-met/10" tabindex="0">
```

### Le signe ne paraît que là où il y a quelque chose

Sur « Mohammed Ziati », les six cartouches :

```
Poste  Product Owner   souligné: none      curseur: auto
scrum                  souligné: dotted    curseur: help
jira                   souligné: dotted    curseur: help
confluence             souligné: dotted    curseur: help
méthodes agiles        souligné: none      curseur: auto
gestion de produit     souligné: none      curseur: auto
```

### Le délai d'apparition

```
défaut de la bibliothèque : 754 ms   ← un survol de lecture ne la déclenche pas
après réglage             : 250 ms
```

### L'infobulle, déclenchée

Sur « Mohammed Ziati », cartouche « scrum » :

```
Senior Product Owner | Agile Delivery | Digital Transformation | Scrum Certified
```

C'est le libellé que le serveur a reconnu, et que la ligne tronquée réduisait à « Senior Product … ».

### Les styles calculés

```
fond cartouche « trouvé »     oklab(0.622509 -0.109743 0.0536835 / 0.1)
disque                         rgb(64, 156, 100)
```

### Les gates, sur l'hôte

```
$ bun run quality-type-check        → exit=0, quatre espaces
$ bun run quality-conventions-check → 19 règles, aucune violation
```

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/components/search/MatchList.vue` | (M) cartouches, disque plein, verdict systématique, infobulle conditionnelle |
| `base/app/app.vue` | (M) `<UApp>` à la racine — le fournisseur des surcouches Nuxt UI |

## Reste à faire (hors périmètre)

- **Traduire les textes intégrés de Nuxt UI.** `<UApp>` accepte une locale ; le préalable existe
  désormais, la carte reste à écrire, et elle appartient au socle.
- **Mettre le mot reconnu en évidence dans l'infobulle** — sur un libellé de quatre-vingt-dix
  caractères, l'œil cherche encore où « scrum » se trouve.
- **Vérifier les trois autres espaces** : `<UApp>` les enveloppe aussi, et seul l'espace entreprise a
  été rendu ici.
