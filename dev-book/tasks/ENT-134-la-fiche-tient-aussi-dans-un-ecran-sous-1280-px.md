# TASK ENT-134 — la fiche tient aussi dans un écran sous 1280 px

- **Créée le** : 2026-08-17.
- **Session** : `c6e9274a-26a7-4733-86f6-42a6f9f95f36`.
- **Statut** : ✅ fait le 2026-08-18 — sous 1280 px la fiche occupe exactement un écran sur les cinq
  formats mesurés, le verdict se replie en une barre de pied qui ne défile pas, et le détail des
  critères s'ouvre en feuille par le bas.
- **Type** : feat — sous 1280 px, tenir le plein écran demande un dispositif de lecture qui n'existe
  pas : des gestes ancrés en pied et des critères qui s'ouvrent. Ce n'est pas le même travail que de
  borner une hauteur.
- **Tests** : `sans objet` — même motif qu'`ENT-133` : aucune logique produite, une géométrie et un
  panneau qui s'ouvre. **La preuve attendue est la mesure**, relevée sur téléphone (390 × 780) et dans
  la bande étroite (1150 × 800), plus la capture du panneau des critères ouvert.
- **Réf** : `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` ·
  `webapp/spaces/entreprise/app/components/search/Verdict.vue` ·
  `webapp/spaces/entreprise/app/components/search/MatchList.vue` ·
  `ENT-133` (le bornage de hauteur, dont cette carte dépend) · `ENT-088` (l'arbitrage qui a créé le
  problème) · `ENT-112` (le profil complet en tiroir, dispositif d'ouverture déjà en place).

## Contexte

**Où ça se passe.** Le même écran qu'`ENT-133` — un recruteur qui parcourt les candidats d'une
recherche enregistrée, dans l'espace entreprise — mais **sur une fenêtre de moins de 1280 px de
large** : un téléphone, une tablette, ou une fenêtre de navigateur réduite sur un ordinateur portable.

**Ce qui change à cette largeur.** Le verdict n'a plus sa colonne à droite. Il **retombe à
l'intérieur de la fiche**, en pied : la note d'alignement, la liste des critères trouvés et manquants,
le bouton « Retenir » et le bouton d'écart. À lui seul, ce bloc pèse plus de 300 px. Sous 1024 px, les
deux colonnes de la fiche s'empilent en plus — photo, coordonnées, boutons d'achat, nom, quatre faits,
projet de retour, compétences, les uns sous les autres.

**Ce qu'on voit.** La fiche ne tient dans aucun écran de téléphone, et le bornage de hauteur posé par
`ENT-133` ne peut rien y faire : il n'y a pas assez de place pour ce qu'on demande d'afficher. C'est
exactement le constat qui a fait céder `ENT-088` — *« constaté en capture, à 1150 × 1000 »* — et sa
conclusion était de renoncer au plein écran plutôt que de renoncer à du contenu.

**Ce que cette carte renverse.** On garde le plein écran, et c'est **l'affichage qui montre moins**.
Pas moins d'information disponible : moins d'information posée à plat en même temps.

## Périmètre

- **Une barre de gestes ancrée en pied de fiche**, qui ne défile pas : la note d'alignement, le bouton
  « Retenir » et le bouton d'écart. Ils restent atteignables en permanence, quelle que soit la
  position de lecture — c'est la raison d'être de cet écran.
- **La liste des critères s'ouvre en feuille par le bas**, depuis la note. Le dispositif existe déjà
  deux fois sur cet écran — le tiroir du profil complet, le tiroir des écartés —, et la liste y a
  toute la place, ses huit critères et leur détail compris.
- **Le reste de la lecture entre dans la zone souple** posée par `ENT-133`, élargie à cette largeur :
  ce qui suit l'identité défile, sous un bandeau et une identité qui restent en place.
- **Le plein écran vaut du téléphone à 1279 px**, sans trou entre les deux : la bande étroite d'un
  portable et l'écran d'un téléphone reçoivent le même traitement.
- **Les commentaires des fichiers touchés sont réécrits**, y compris celui qui justifie aujourd'hui le
  repli du verdict en pied de fiche.

## Hors périmètre

- **Le grand écran** (≥ 1280 px) : c'est `ENT-133`, et cette carte ne doit rien y changer.
- **La console de gauche** — avancement, filtres d'affinage, « Ma sélection ». Elle est masquée sous
  1024 px et son sort sur téléphone est un sujet à elle seule. **Non ouvert.**
- **Le contenu du panneau de profil complet**, qui reste ce qu'il est.
- **Le seuil de bascule de la colonne du verdict** (1280 px) : il ne descend pas. `ENT-088` a mesuré
  qu'à 1024 px le partage laisserait environ 400 px au portrait, et ce constat tient.
- **Les gestes d'affinage et de tri** sur petit écran. **Non ouvert.**

## Décisions de cadrage

- **Les gestes restent, les critères s'ouvrent.** Écarté : tout faire défiler y compris les
  critères — la zone souple devenait une petite fenêtre où la lecture et le jugement se mélangeaient.
  Écarté aussi : sortir le verdict entier de la fiche derrière un geste d'ouverture — « Retenir »
  aurait disparu derrière un clic sur l'écran dont c'est précisément la raison d'être.
- **La feuille par le bas plutôt qu'un repli sur place.** Un repli sous la note repousse la lecture et
  rouvre le problème de hauteur qu'`ENT-133` vient de fermer ; la feuille réutilise un dispositif déjà
  présent deux fois sur le même écran.
- **Le plein écran vaut aussi sur téléphone**, alors que le renoncement était plus simple. Le motif
  est le geste : cet écran sert à trancher des dizaines de profils à la suite, et un geste qui change
  de place à chaque fiche coûte plus qu'un contenu rangé derrière une ouverture.
- **⚠️ ÉCART — seul le bandeau reste en place, pas l'identité.** Le cadrage annonçait « ce qui suit
  l'identité défile, sous un bandeau et une identité qui restent en place ». Dans les faits, ce sont
  les **deux colonnes entières** qui deviennent le corps défilant sous 1280 px : l'identité défile
  avec le reste. Garder l'identité fixe demanderait de la sortir de la grille, donc de changer la
  mise en page au-dessus de 1280 px — que cette carte met explicitement hors périmètre. En pratique
  l'identité ne bouge que lorsque la fiche est trop chargée pour l'écran : dans la bande étroite,
  mesuré, aucune fiche ne défile.
- **⚠️ ÉCART — sur téléphone, un cran de molette ne passe plus au profil suivant du premier coup.**
  Il finit d'abord la lecture de la fiche courante, puis enchaîne. C'est la conséquence directe du
  choix « la lecture défile » : sur un écran de poche le contenu ne tient pas, et le seul moyen de
  garder la fiche dans un écran est qu'elle défile en interne. Au-dessus de 640 px le cran vaut un
  profil, mesuré.
- **Cette carte dépend d'`ENT-133`** et ne s'implémente pas avant : c'est le bornage de hauteur et la
  zone souple qui lui servent de socle. Ce n'est pas un ordre de priorité, c'est une dépendance.

## Règles et cas limites

- **La barre de gestes ne double jamais celle du grand écran** : à 1280 px et au-delà, elle n'existe
  pas — le verdict a sa colonne et porterait sinon les mêmes gestes deux fois.
- **Recherche d'un collègue (`editable: false`)** : aucun geste de décision, donc la barre de pied se
  réduit à la note. Elle ne doit pas rester en place vide.
- **Un candidat sans critère évalué** : la note et l'ouverture des critères n'ont rien à montrer — la
  feuille ne s'ouvre pas sur du vide.
- **La feuille se referme au clavier et au doigt**, et rend le focus à la note d'où elle a été ouverte.
- **Une fiche retenue garde sa trace visible** sans que l'anneau du bloc ne rogne la barre de pied.
- **Aucun texte nouveau** si les libellés existants suffisent ; sinon, clés i18n, jamais de texte en
  dur.

## Documentation impactée

- Les commentaires des composants touchés — `ProfileCard.vue` et `Verdict.vue` —, qui portent
  aujourd'hui la justification du repli en pied de fiche. Même commit.
- **Aucune page de `docs/showcase-recruiter-webapp-saas/`.**

## Définition de terminé

- [x] À 390 × 780 et à 1150 × 800 : la hauteur de la fiche est inférieure ou égale à celle de la zone
      qui la contient, mesurée à l'écran.
- [x] La note, « Retenir » et le geste d'écart sont visibles à tout moment de la lecture, sans rien
      faire défiler.
- [x] La liste des critères s'ouvre en feuille par le bas, se referme au clavier comme au doigt, et
      rend le focus à son point d'ouverture.
- [~] Un cran de défilement passe d'un profil au profil suivant **au-dessus de 640 px**. Sur
      téléphone, il finit d'abord la fiche courante — conséquence assumée, cf. les écarts.
- [x] À 1280 px et au-delà, rien n'a changé par rapport à `ENT-133` : aucune barre de pied, aucun
      geste en double.
- [~] Sur une recherche de collègue, la barre de pied ne reste pas en place vide — **le garde-fou est
      écrit, il n'est pas prouvé** : aucun compte de recette ne permet d'exercer ce cas ici.
- [x] Les commentaires des composants touchés disent ce qui est vrai.

## Vérification

Banc automatisé sur les **93 fiches** de la recherche `019ff56d-6eee-7002-89ac-46a78d713152`,
navigateur réel piloté, session authentifiée en `entreprise@showcase-recruiter-webapp-saas.example`, thème sombre.

### La fiche tient dans un écran sous 1280 px

```
téléphone            390 × 780    fiche 610 = zone 610     0 px de dépassement
petite tablette      640 × 900    fiche 714 = zone 714     0 px
tablette             820 × 1024   fiche 818 = zone 818     0 px
bande étroite       1150 × 800    fiche 626 = zone 626     0 px
juste sous le seuil 1279 × 720    fiche 545 = zone 545     0 px
```

Aucun contenu ne sort de la fiche, et aucun bloc de la barre n'en chevauche un autre aux trois
largeurs contrôlées.

### Non-régression au-dessus du seuil

Les cinq formats d'`ENT-133` remesurés — 1920 × 1080, 1512 × 900, 1280 × 800, 1280 × 700,
1440 × 620 : **0 px de dépassement partout**, inchangé. La barre de pied est absente à ces largeurs,
donc aucun geste n'est montré deux fois.

### Les deux zones défilantes ne coexistent jamais

```
1280 × 700   la grille défile sur  0 fiches, le corps intérieur sur  2   les deux : 0
1150 × 800   la grille défile sur  0 fiches, le corps intérieur sur  0   les deux : 0
 390 × 780   la grille défile sur 93 fiches, le corps intérieur sur  0   les deux : 0
```

### La feuille des critères

```
geste d'ouverture, nom accessible : « 5/8 · Proche de vos critères · 5 critères sur 8 retrouvés »
ouverture           feuille montée, liste des critères dedans, 8 lignes
fermeture clavier   Échap referme, focus rendu au bouton de la note
fermeture au doigt  un clic hors de la feuille referme
```

### L'accrochage du défilement

```
1150 × 800  molette sur le bandeau  → pile à 626, 1252, 1878   (fiche = 626 px)
1150 × 800  molette sur le corps    → pile à 626, 1252, 1878
 390 × 780  molette sur le bandeau  → pile à   0,  610, 1149   (fiche = 610 px)
 390 × 780  molette sur le corps    → pile à   0,  610,  610
```

Au-dessus de 640 px, un cran vaut exactement un profil. Sur téléphone, le premier cran finit la
lecture de la fiche avant d'enchaîner — cf. les écarts.

### La trace d'un profil retenu

L'anneau d'accent entoure toujours le bloc, et la barre de pied tient entièrement dedans : mesuré.

### Deux défauts introduits en chemin, corrigés

- **Incohérence d'hydratation.** Le point d'entrée clavier bascule d'une zone défilante à l'autre
  selon la largeur — que le serveur ignore. Vue signalait `Hydration attribute mismatch` sur le
  `tabindex` à chaque chargement. Corrigé en ne suivant la fenêtre qu'après le montage. Contrôle :
  les avertissements d'hydratation qui subsistent apparaissent **aussi sur la Table**, écran sans
  fiche ni feuille — ils sont pré-existants et hors de cette tâche.
- **Chevauchement sur téléphone.** En une seule rangée, « Proche de vos critères » passait sous le
  bouton vert — constaté en capture à 390 px. La barre s'empile désormais sous 640 px.

## Livré

- `webapp/spaces/entreprise/app/components/search/Verdict.vue` (M) — mode `compact` : la note devient
  le geste qui ouvre le détail, les deux gestes restent, la liste n'est pas rendue ; la barre
  s'empile sous 640 px et disparaît quand elle n'a ni note ni geste à porter.
- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` (M) — sous 1280 px, les deux
  colonnes deviennent le corps défilant et le verdict se replie en barre de pied ; le point d'entrée
  clavier suit la zone qui défile réellement.
- `webapp/spaces/entreprise/app/components/search/CriteriaSheet.vue` (+) — la feuille par le bas.
- `webapp/spaces/entreprise/app/composables/useCriteriaSheet.ts` (+) — son ouverture, en état partagé.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` (M) — la feuille montée une fois
  pour toute la pile.

## Reste à faire (hors périmètre)

- **La feuille garde une hauteur fixe** quel que soit le nombre de critères, par cohérence avec le
  tiroir du profil complet. Sur une recherche à trois critères, elle laisse beaucoup de blanc.
  **Non ouvert.**
- **La console de gauche sur téléphone** reste masquée sous 1024 px : avancement, affinage et
  « Ma sélection » n'y existent pas. **Non ouvert.**
- **Le vide en bas de fiche sur grand écran**, hérité d'`ENT-133`, n'est pas traité ici.
