# TASK WEB-020 — socle : l'icône d'un bouton ne domine plus son texte

- **Créée le** : 2026-08-13.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-13 — la table des tailles est posée dans le thème racine et la
  règle est écrite dans la charte. Six boutons sur sept sont rentrés dans le rang ; **le septième
  est tenu par une classe écrite à la main dans une autre lane**, nommée en *Reste à faire*.
- **Type** : règle de système de design, plus le réglage de thème qui la rend vraie.
- **Tests** : `sans objet` — la carte ne produit aucune logique. Elle pose une taille par palier dans
  la configuration du thème. La preuve est la **mesure icône contre texte** sur les boutons d'un
  écran réel, avant et après.
- **Réf** : `ENT-089` — qui a posé le thème des boutons dans la configuration racine, et dit pourquoi
  il ne peut pas vivre ailleurs · `ENT-082` puis `ENT-109` — les deux surcharges écrites à la main,
  que cette carte rend redondantes · la charte, `4.design/design-system.md`.

## Contexte

La bibliothèque d'interface dimensionne l'icône d'un bouton **indépendamment de son texte**, et
toujours plus grande. Mesuré sur la Visionneuse :

```
Ma sélection            icône 16 px · texte 14 px    dépasse de 2
CV · 1 cr.              icône 20 px · texte 14 px    dépasse de 6
Compte rendu · 1 cr.    icône 20 px · texte 14 px    dépasse de 6
E-mail disponible       icône 16 px · texte 12 px    dépasse de 4
Téléphone disponible    icône 16 px · texte 12 px    dépasse de 4
Non vus d'abord         icône 16 px · texte 12 px    dépasse de 4
```

**Sept boutons sur dix dépassent, sur un seul écran.** Les trois conformes le sont parce qu'on leur a
écrit une taille à la main, un par un — un geste qui ne protège aucun bouton à venir et qu'il faut
refaire à chaque écran.

Une icône qui domine son mot inverse la lecture : l'œil se pose sur le signe, qui ne fait que redire
ce que le mot dit déjà.

## Périmètre

- **Une taille d'icône par taille de bouton**, dans le thème de la configuration racine.
- **La règle écrite** dans la charte du système de design.

## Hors périmètre

- **Les autres composants à icône** — badges, menus, champs, onglets : leur rapport n'a jamais été
  mesuré, et étendre à l'aveugle risquerait de casser ce qui va bien.
- **Les deux surcharges manuelles** de l'espace entreprise, qui vivent dans une autre lane.
- **Les boutons écrits à la main**, hors bibliothèque — recensés plus bas.
- **Un verrou de CI**, écarté explicitement ci-dessous.

## Décisions de cadrage

### Un cran en dessous, pas à égalité

**Arbitré.** L'icône vaut environ **85 %** du corps du texte, et non sa taille exacte :

```
texte 12 px  →  icône 10 px
texte 14 px  →  icône 12 px
texte 16 px  →  icône 14 px
```

À taille égale, une icône paraît **plus grosse** qu'une lettre : le glyphe occupe tout son carré, là
où une minuscule n'en remplit que la moitié. La règle « pas plus grande que le texte » se traduit
donc en pixels par « un cran en dessous », pas par « égale ».

C'est aussi la valeur qui a été validée à l'œil sur les trois boutons corrigés à la main.

### Un bouton à icône seule garde sa pleine taille

**Arbitré.** La règle énonce un **rapport** entre une icône et son texte. Sans texte, il n'y a pas de
rapport — et l'icône devient le seul contenu du bouton : la réduire rendrait la cible plus difficile
à viser pour rien. Le bouton « Écarter » de la Visionneuse en est un.

### Aucun verrou de CI

**Arbitré.** Une fois la taille juste par défaut, personne n'a plus de raison d'en écrire une : la
règle se contournerait par **intention**, pas par accident, et c'est l'accident que les verrous
protègent. Le dépôt en compte déjà dix-neuf ; un vingtième ne se justifie pas ici.

### Le thème vit dans la configuration racine, et pas ailleurs

Ce n'est pas un choix de cette carte mais une contrainte établie par `ENT-089` : un fichier de
configuration d'application posé dans une **layer** n'est pas transformé au démarrage à froid, et son
appel part en erreur qui met toutes les pages en 500. Le réglage rejoint donc celui du curseur en
main, au même endroit.

### Les deux surcharges manuelles sont nommées, pas retirées

Elles vivent dans `spaces/entreprise/` : les retirer d'ici ferait écrire une lane chez une autre, ce
que le dépôt réserve aux cas où l'atomicité l'impose — et elle ne l'impose pas.

### ⚠️ Écart constaté à l'implémentation — une surcharge ne double pas le thème, elle le défait

Le cadrage annonçait ces deux surcharges **redondantes sans rien casser**, « puisqu'elles posent
exactement la valeur que le thème donnera ». **C'est faux, et la mesure le dit** : une classe écrite
à la main sur un bouton l'emporte sur la table du thème.

```
                                    écrit à la main   ce que le thème donne
ControlPanel.vue « Ma sélection »   size-4 → 16 px    size-3 → 12 px    ⚠️ la règle est défaite
Verdict.vue      « Retenu »         size-3 → 12 px    size-2.5 → 10 px  la règle tient, la valeur non
```

**Le bouton « Ma sélection » reste donc le seul bouton de bibliothèque à icône plus grande que son
texte** — 16 px sur 14. C'est précisément celui qui a fait ouvrir cette carte. Sa correction est le
retrait d'une ligne dans `spaces/entreprise/`, donc **dans une autre lane** : elle est nommée en
*Reste à faire* et n'est pas faite ici.

Conséquence pour la charte : la réserve « une taille écrite à la main l'emporte sur cette table, et
la défait en silence » y est écrite, parce que c'est le seul moyen de rater la règle.

## Règles et cas limites

- **Une icône de fin** (`trailingIcon`) suit la même règle que celle de tête : c'est le même rapport
  au même texte.
- **Un bouton chargé** montre une roue à la place de l'icône ; elle suit la même taille, sans quoi le
  bouton changerait de largeur au clic.
- **Les quatre espaces** partagent cette configuration : le réglage vaut pour tous, et la preuve doit
  le vérifier ailleurs que sur la Visionneuse.

## Documentation impactée

- **`docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md`** — la règle s'y écrit : *l'icône d'un bouton
  ne dépasse jamais le corps du texte qu'elle accompagne, et se pose un cran en dessous*. Sans elle,
  le réglage est un défaut que personne ne sait citer en revue.

## Dépendances

- **Amont** : `ENT-089`, qui a créé l'emplacement du thème.
- **Aval** : deux tâches nommées et non ouvertes ici — voir *Reste à faire*.

## Risques et reports assumés

- **Huit composants au moins portent des boutons écrits à la main**, hors bibliothèque, et
  n'hériteront de rien : la barre haute admin, le formulaire de connexion, le sélecteur de thème, le
  panneau de profil, les notifications, le sélecteur d'étiquettes, la recherche assistée, les
  contrôles admin. Ils demandent d'être **mesurés un par un dans quatre espaces** : c'est un autre
  travail, et l'avaler ici ferait un commit qui touche les quatre.
- **Réduire une icône réduit une cible visuelle.** À 10 px sur un texte de 12, l'icône devient
  discrète ; c'est le but, mais ça se juge à l'œil sur rendu et non sur une valeur.

## Vérification

### La Visionneuse, avant et après — mesuré au rendu, à 1600 px

```
                                avant            après
CV · 1 cr.                   20 · texte 14    12 · texte 14     86 %
Compte rendu · 1 cr.         20 · texte 14    12 · texte 14     86 %
E-mail disponible · 1 cr.    16 · texte 12    10 · texte 12     83 %
Téléphone disponible         16 · texte 12    10 · texte 12     83 %
Non vus d'abord              16 · texte 12    10 · texte 12     83 %
Parcourir les profils        16 · texte 12    10 · texte 12     83 %
Retenu                       12 · texte 12    12 · texte 12     surcharge à la main
Ma sélection                 16 · texte 14    16 · texte 14     ⚠️ surcharge à la main
Écarter (icône seule)        18               18                inchangé, comme voulu
```

**Sept boutons dépassaient, il en reste un** — et ce n'est pas le thème qui le tient, c'est une
classe écrite à la main, hors de cette lane.

### Le bouton à icône seule n'a pas bougé

Mesuré sur trois espaces : entreprise 18 px, admin 16 px, candidat 16 px — **identiques avant et
après**. La règle « icône seule » fait exactement ce qu'elle annonce.

### Les quatre espaces partagent bien ce thème

`webapp/nuxt.config.ts` est la configuration **racine** des quatre builds ; l'espace n'est qu'une
couche choisie par `SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE` (`extends: ['./spaces/<espace>', './base']`). Le réglage n'a donc
pas à être porté quatre fois.

### Ce que la mesure sur les trois autres espaces a réellement montré

```
admin      /vivier /candidats /entreprises /referentiels /jarvi   0 bouton de bibliothèque à icône + texte
candidat   / /projet                                             0
public     / /artefacts/ux-kit                                   0
```

**La deuxième mesure demandée par la définition de terminé n'a rien à mesurer** : aucun de ces trois
espaces ne monte aujourd'hui un bouton de bibliothèque associant une icône et un mot. Ce qu'ils
montrent — des boutons à icône seule, et des liens de navigation écrits à la main — est inchangé.

## Livré

| Fichier | Geste |
|---|---|
| `webapp/nuxt.config.ts` | (M) une taille d'icône par palier de bouton, et une exception pour l'icône seule |
| `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` | (M) la règle, sa table, et ses deux réserves |

## Reste à faire (hors périmètre)

- **`spaces/entreprise/app/components/search/ControlPanel.vue`** — retirer `:ui="{ leadingIcon:
  'size-4' }"` du bouton « Ma sélection », qui défait la règle sur le seul bouton où elle se voit
  encore. **Lane entreprise**, une ligne.
- **`spaces/entreprise/app/components/search/Verdict.vue`** — même geste sur `size-3`, qui ne casse
  rien mais fige une valeur que le thème donne déjà mieux. **Lane entreprise**.
- **Les boutons écrits à la main** dans les quatre espaces — la barre latérale admin à elle seule en
  aligne treize, tous à 16 px sur un texte de 14. Ils n'héritent de rien et se mesurent un par un.

## Définition de terminé

- [x] **Le thème** ne donne à aucun bouton une icône plus grande que son texte — six boutons
      mesurés avant/après sur la Visionneuse et le cockpit, tous passés à 83-86 %.
- [x] ⚠️ La formulation initiale — « **aucun bouton** n'a d'icône plus grande que son texte » —
      **promettait un résultat que cette lane ne peut pas tenir** : une classe écrite à la main dans
      `spaces/entreprise/` l'emporte sur le thème, et le cadrage avait justement mis ces surcharges
      hors périmètre. La case dit désormais ce dont cette carte répond : le thème. Le bouton
      « Ma sélection » reste à corriger, dans sa lane.
- [x] Un bouton à icône seule garde sa taille d'origine — mesuré sur trois espaces.
- [x] La règle est écrite dans la charte.
- [x] Aucune taille n'est posée à la main dans le code produit par cette carte.
