# TASK ENT-084 — entreprise : le lancement devient le pied de la carte

- **Créée le** : 2026-08-11.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-11 — « Sélectionner les profils » occupe le bord bas de la carte
  des mots-clés d'un côté à l'autre, à angles droits, comme « Ma sélection » au pied de la colonne
  d'avancement de la Visionneuse.
- **Type** : Frontend entreprise — forme et ancrage de l'action principale d'un écran existant.
  Aucun contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Ce qui change est une forme et un
  ancrage : une carte qui cesse de porter son propre rembourrage, un bouton qui devient une bande.
  Le webapp n'a aucun harnais de test de composant (`webapp/vitest.config.ts` ne couvre que le code
  pur, sans DOM). **La preuve attendue est une capture de l'écran connecté**, thème clair et sombre,
  grand écran et largeur téléphone. Aucune clé de langue ne bouge.
- **Réf** : **`ENT-081` — amont** (elle a posé les deux cartes et descendu le bouton en pied) ·
  `webapp/spaces/entreprise/app/components/search/Sentence.vue` ·
  `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` ·
  `webapp/spaces/entreprise/app/components/search/{Console,ControlPanel}.vue` (le pied dont
  celui-ci s'inspire)

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur la page « Décrivez le profil que
vous cherchez ». À droite, la carte des mots-clés ; tout en bas de cette carte, le bouton qui lance
la recherche.

`ENT-081` l'a bien amené là, mais sous une forme intermédiaire : **un bouton posé dans une bande**,
à droite, avec un filet au-dessus et du vide autour. Il se lisait comme le dernier élément d'une
pile, pas comme le pied du bloc.

Or **le produit a déjà ce pied-là**, et depuis longtemps : dans la Visionneuse, la colonne
d'avancement se termine par « Ma sélection », un bouton **pleine largeur, à angles droits, à fleur
des trois bords de sa carte**. Son code porte même l'argument en clair — « le pied doit toucher le
bord, une marge sous lui le transformerait en bouton posé dans un cadre ». C'est exactement le
défaut que la forme intermédiaire avait.

## Périmètre

- **Le bouton devient le pied** : pleine largeur, à angles droits, collé aux trois bords de la carte
  des mots-clés. Le filet de séparation et la bande qui l'entouraient disparaissent avec.
- **La carte des mots-clés cesse d'employer la carte du kit** : c'est son contenu qui porte le
  rembourrage, pas elle — sans quoi le pied ne peut pas toucher les bords.
- **Le libellé et la flèche ne changent pas** : « Sélectionner les profils », flèche à droite qui se
  retourne en arabe.
- **Sur téléphone**, le pied reste ce qu'il est : la carte des mots-clés est la seconde de la pile,
  son pied est donc tout en bas de l'écran.

## Hors périmètre

- **La carte de la phrase**, qui garde la carte du kit et son rembourrage : elle n'a pas de pied.
- **Le bouton « + Ajouter un mot-clé »**, qui reste dans le contenu et garde sa forme.
- **La colonne d'avancement de la Visionneuse**, dont ce pied s'inspire et qui ne bouge pas.
- **Le bouton du kit `UiButton`** : son rayon n'est pas débrayable, mais lui en ajouter un réglage
  dépasserait cette tâche — cf. *Décisions de cadrage*.

## Décisions de cadrage

**Le pied emploie `UButton` et non le bouton du kit, et c'est un choix contraint.** `UiButton` écrit
son rayon en dur ; le neutraliser de l'extérieur demanderait de forcer la priorité d'une règle, ce
que le dépôt évite. `UButton` accepte `rounded-none`, et c'est **déjà le composant qui rend ce
pied-là** dans la Visionneuse : reprendre le même donne le même objet, pas un sosie. La bonne
correction de fond — un réglage de forme sur `UiButton` — appartient au socle et est nommée en
*Reste à faire*.

**La carte des mots-clés perd `UiCard`.** Le kit pose le rembourrage sur la carte elle-même, ce qui
interdit à tout enfant de toucher le bord. La colonne d'avancement de la Visionneuse ne l'emploie pas
non plus, pour cette raison exacte : elle pose sa surface à la main et met le rembourrage sur son
contenu. On copie cette construction plutôt que d'annuler le rembourrage par des marges négatives —
ce que faisait la forme intermédiaire, au prix de valeurs à tenir synchronisées avec le kit.

**`overflow-hidden` sur la carte, pas de rayon sur le pied.** Ce sont les coins de la carte qui
rognent ceux du bouton. L'inverse — arrondir le bas du bouton pour épouser la carte — obligerait à
répéter le rayon à deux endroits et à les tenir d'accord.

**Le libellé se range à gauche et la flèche à droite**, plutôt que centrés ensemble comme « Ma
sélection ». C'est le comportement de `UButton` en pleine largeur avec une icône de fin, et il sert
le propos : la flèche annonce l'écran d'après, elle est plus lisible collée au bord vers lequel on
va qu'accolée au dernier mot.

## Règles et cas limites

- **Le pied n'existe que si l'appelant fournit une action** : sans elle, la carte se referme sur son
  contenu et ne réserve pas une bande vide.
- **Le bouton reste désactivé tant qu'aucun critère n'est posé** — devenir une bande ne change pas sa
  condition.
- **La flèche se retourne en arabe**, où l'on avance vers la gauche.
- **La liste des mots-clés continue de défiler au-dessus du pied**, qui ne bouge pas : c'est le
  contenu qui se rétracte, jamais la bande.
- **Rien n'est écrit en dur** : la surface, le rayon et le rembourrage viennent des jetons du kit.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : la carte de navigation dit déjà que le bouton vit dans la carte des
mots-clés, et sa forme n'y est pas décrite. Aucun `README.md` ne change de convention.

## Vérification

Preuve prise le 2026-08-11 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`.

| Ce qui est prouvé | Ce qui a été observé |
| --- | --- |
| Le pied touche les trois bords | à 1440 px, la bande part du bord gauche de la carte et va jusqu'au droit, son bas est celui de la carte |
| Angles droits, coins rognés par la carte | les coins bas de la bande épousent ceux de la carte |
| Libellé à gauche, flèche à droite | « Sélectionner les profils » puis la flèche au bord |
| Désactivé sans critère | la bande est éteinte tant que la phrase est vide |
| Téléphone | la carte des mots-clés est la seconde de la pile, son pied ferme l'écran |
| Thème clair | même forme, mêmes bords |

**Captures** : `preuve-pied-1440.png`, `preuve-pied-light.png`, `preuve-pied-390.png`, dans le
dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

Aucune gate qualité n'a été jouée : elles appartiennent à l'ouverture de la PR.

## Livré

- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — la carte des mots-clés pose sa
  surface elle-même, son contenu porte le rembourrage, et l'emplacement d'action devient le pied nu.
- `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` — le bouton passe en `UButton` pleine
  largeur à angles droits, flèche en fin, retournée en arabe.

## Reste à faire (hors périmètre)

- **`UiButton` n'a pas de réglage de forme.** Tant qu'il n'en a pas, un pied de carte doit passer par
  `UButton`. Le sujet appartient au socle, pas à cet écran.
- **La construction « surface posée à la main + contenu rembourré »** est maintenant employée à deux
  endroits sans être nommée nulle part. Si un troisième la reprend, elle mérite une variante du kit.

## Définition de terminé

- [x] Le bouton occupe le bord bas de la carte des mots-clés d'un côté à l'autre.
- [x] Il est à angles droits, et ce sont les coins de la carte qui le rognent.
- [x] Plus aucun filet ni marge ne l'entoure.
- [x] Il porte toujours « Sélectionner les profils » et sa flèche, retournée en arabe.
- [x] Il reste désactivé tant qu'aucun critère n'est posé.
- [x] La liste des mots-clés défile au-dessus de lui sans le déplacer.
- [x] Captures fournies : thème clair et sombre, grand écran et largeur téléphone.
