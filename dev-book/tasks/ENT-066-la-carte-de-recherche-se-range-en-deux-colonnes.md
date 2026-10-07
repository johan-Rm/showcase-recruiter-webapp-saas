# TASK ENT-066 — entreprise : la carte de recherche se range en deux colonnes, et dit ce qu'on a choisi

- **Créée le** : 2026-08-06.
- **Session** : `d6c47bca-7043-42fd-b4d0-53837807b7c2`.
- **Statut** : ✅ fait le 2026-08-06 — la carte de recherche tient en deux colonnes, la phrase sur
  trois lignes, le champ « poste » va jusqu'au bout de sa ligne et ses suggestions s'écrivent en
  entier ; les valeurs choisies portent la couleur de marque, le nom se lit et se corrige au crayon,
  le bouton dit « Créer ma recherche », et les deux sélecteurs restent vides tant que rien n'est
  choisi.
- **Type** : Frontend entreprise — disposition, lisibilité et libellés d'une carte existante. Aucun
  contrat, aucune donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable nouveau`. Rien ne se calcule ici : des colonnes se
  posent, une couleur se pose, un champ s'ouvre au clic. Le webapp n'a aucun harnais de test de
  composant (`webapp/vitest.config.ts` ne couvre que le code pur, sans DOM), et jsdom ne mesure ni
  largeur ni couleur. La preuve attendue est une **capture de l'écran connecté**, en thème clair et
  sombre, sur grand écran et en largeur téléphone, panneau de suggestions ouvert.
- **Réf** : **`ENT-060` — amont** (elle a écrit la phrase que cette carte réagence) · `ENT-044`
  (le volume qui ne s'affiche plus par suggestion) ·
  `webapp/spaces/entreprise/app/components/search/{Sentence,SentenceName}.vue` ·
  `webapp/base/app/components/ui/InlineSelect.vue` · `webapp/base/i18n/locales/{fr,en,ar}.yaml` ·
  **aval : `ENT-067`**, qui sort le nom et le bouton de cette carte pour les monter dans une barre
  haute.

## Contexte

**La carte de recherche par critères occupe mal sa largeur, et se lit mal.** Cinq défauts, tous
constatés à l'écran sur l'accueil de l'espace entreprise, moteur « Créer une recherche » ouvert :

1. **La phrase et les mots-clés s'empilent sur toute la largeur.** La phrase s'étire alors sur une
   seule ligne démesurée pendant que le bloc des mots-clés traîne un vide à sa droite.
2. **Le champ « poste » se règle sur ce qui est tapé**, donc il est minuscule tant qu'on n'a rien
   écrit. Or le panneau de suggestions prend la largeur de son champ : les intitulés y arrivent
   coupés — « Respo… », « Ingéni… », « Dévelo… » —, ce qui est précisément l'inverse du service
   rendu. La coupure a une seconde cause, invisible au premier regard : **la liste hérite de la
   police de la phrase**, grasse et à la taille d'un titre.
3. **Rien ne distingue ce qu'on a choisi de ce qui est écrit d'avance.** « Je cherche un profil de
   Développeur » s'affiche d'un seul ton : la partie fixe de la phrase et la valeur retenue ont la
   même voix.
4. **Le nom de la recherche se présente comme un champ de saisie** — trait souligné, invite grise —
   alors qu'il arrive déjà écrit et qu'on le corrige rarement. L'étiquette qui le surmonte,
   « Recherche en cours · auto », se lit comme un état de chargement.
5. **Le bouton dit « Rechercher »** alors qu'il crée une recherche qui se garde et se retrouve.

**Et deux filtres non posés s'écrivent comme des filtres posés** : « avec **peu importe**
d'expérience, niveau **peu importe** » occupe la phrase de deux mots qui ne disent rien.

## Périmètre

- La disposition de la carte : phrase à gauche, mots-clés à droite, empilés sous `lg`.
- La phrase en **trois** lignes, une par critère, au lieu de deux blocs.
- Le champ « poste » étendu à la fin de sa ligne, et le panneau de suggestions ramené au corps de
  texte courant.
- La couleur de marque sur les valeurs choisies, l'invite et le chevron restant muets.
- Le nom en lecture, corrigé par un crayon ; le champ n'existe que pendant la correction.
- Le libellé du bouton, en clé i18n distincte de celle que garde la console de la Visionneuse.
- Les sélecteurs laissés vides tant que rien n'est choisi, la liste gardant son « peu importe ».

## Hors périmètre

- L'écran dédié à la création, la coque partagée et le glissement : c'est `ENT-067`.
- La recherche assistée, qui ne bouge pas.
- `SearchFiltersPrimary`, qui sert la console de la Visionneuse et garde ses champs étiquetés.
- La taille de la police de la phrase au-delà du palier retenu ici.

## Décisions de cadrage

- **Deux colonnes seulement à partir de `lg`.** En dessous, la phrase n'a déjà plus la largeur d'une
  seule colonne : les empiler est le bon comportement, pas un repli.
- **Le champ « poste » prend la place restante plutôt qu'un plancher plus large.** Rien ne le suit
  sur sa ligne, donc l'élargir ne déplace rien — là où les deux sélecteurs, eux, gardent leur
  largeur réservée au plus long libellé pour que la phrase ne se réorganise pas à chaque choix.
- **La police du panneau de suggestions se corrige dans la phrase, pas dans le kit.** C'est la
  phrase qui impose sa grosse police à un composant conçu neutre ; la correction appartient donc à
  l'appelant.
- **La phrase descend d'un palier typographique** (corps de texte au lieu de sous-titre) : elle reste
  en gras et en police de titre, donc elle domine encore la carte.
- **Un « choix vide » entre dans `UiInlineSelect`**, sous forme d'une option nommée : sa valeur garde
  son libellé dans la liste et laisse le champ vide. Sans elle, vider le champ obligeait à vider
  aussi la liste. Les autres appelants du composant ne la passent pas et ne changent pas de
  comportement.
- **La couleur de marque sur les valeurs contredit un commentaire du kit**
  (`UiInlineInput` : « peindre chaque valeur saisie en accent faisait briller tout le formulaire »).
  L'exception est assumée et locale : ici la grammaire de la phrase fait que les valeurs *sont* le
  contenu, et il n'y en a que trois. Le commentaire du kit sera nuancé pour que la divergence ne
  passe pas pour un oubli.

## Règles et cas limites

- **L'invite reste muette.** « poste… » dit ce qu'on peut écrire, pas ce qu'on a choisi : elle ne
  prend pas la couleur de marque. Le chevron du sélecteur non plus, il appartient au champ.
- **La mention « se nomme tout seul » disparaît** dès que le nom a été corrigé à la main, et ne
  s'affiche pas tant que le nom est vide — l'invite du champ le dit déjà.
- **Entrée dans le champ du nom valide le nom**, elle ne lance pas la recherche : la touche remontait
  au formulaire et partait chercher alors qu'on croyait confirmer.
- **Le crayon annonce son geste** aux lecteurs d'écran, et le champ ouvert reçoit le focus avec son
  texte sélectionné — sans quoi il faudrait deux clics, et le clavier trouverait un champ apparu de
  nulle part.
- **La sortie du champ referme la correction**, et un champ vidé rend la main à l'auto-nommage.
- L'écriture arabe se lit dans l'autre sens : la phrase reste assemblée de segments, et l'écart entre
  les mots d'une ligne en `flex` vient d'un `gap` — une espace de texte se perd en fin d'élément.

## Documentation impactée

- Aucune page de `docs/showcase-recruiter-webapp-saas/` : ni route, ni contrat, ni règle métier ne change.
- Les commentaires de `UiInlineInput` (la règle sur l'accent) et de `UiInlineSelect` (le choix vide)
  portent la doctrine du kit : ils sont mis à jour dans le même commit.

## Vérification

Preuves prises le 2026-08-06 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `owner@demo.showcase-recruiter-webapp-saas.example`, moteur par critères
ouvert depuis l'accueil. Captures dans le dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/d6c47bca-7043-42fd-b4d0-53837807b7c2/scratchpad/`.

| Ce qui est prouvé | Capture | Mesure lue |
| --- | --- | --- |
| Deux colonnes, thème sombre, 1440 px | `preuve-dark-1440.png` | carte 1056 × 227 |
| Deux colonnes, thème clair, 1440 px | `preuve-light-1440.png` | idem, accent sapin sur fond clair |
| Empilement, 390 px | `preuve-dark-390.png` | carte 374 × 334 |
| Suggestions ouvertes | `preuve-suggestions.png` | panneau **387 px** — « Ingénieur d'études », « Développeur logiciel », « Business Developer » entiers |
| Nom en lecture | `preuve-nom-lecture.png` | « Développeur ✎ se nomme tout seul » |
| Nom en correction | `preuve-nom-edition.png` | champ ouvert, focus dedans, texte sélectionné |

Trois comportements mesurés dans le DOM, et non jugés à l'œil :

- **avant** le clic sur le crayon, zéro champ de saisie dans le pied de carte ; **après**, un seul,
  et `document.activeElement` est bien lui (`ui-inline-input font-display font-bold`) ;
- **Entrée dans le champ du nom n'a pas lancé la recherche** : l'adresse est restée sur l'accueil et
  aucune recherche n'a été créée ;
- une fois le nom corrigé à la main, le bloc ne contient plus la mention « se nomme tout seul ».

Aucune gate qualité n'a été jouée : elles appartiennent à l'ouverture de la PR.

## Livré

- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — grille deux colonnes, phrase en
  trois blocs, champ « poste » en `flex: 1`, `blank-value` sur les deux sélecteurs, palier
  typographique descendu au corps de texte, et deux règles `:deep()` : accent sur les champs du kit,
  retour au corps courant pour les panneaux de suggestions.
- `webapp/spaces/entreprise/app/components/search/SentenceName.vue` — deux états (lecture /
  correction), bouton portant le nom **et** le crayon, mention « se nomme tout seul », Entrée qui
  valide sans soumettre, sortie du champ portée par le champ lui-même. Le bloc `<style scoped>`
  ajouté en cours de route a été retiré au profit d'utilitaires Tailwind.
- `webapp/base/app/components/ui/InlineSelect.vue` — prop `blankValue`, sans effet pour ses autres
  appelants.
- `webapp/base/app/components/ui/InlineInput.vue` — le commentaire de doctrine dit désormais que la
  phrase de recherche est l'exception à la règle « pas d'accent sur une valeur saisie », et pourquoi.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — `search.sentence.submit`, `name.autoHint`,
  `name.edit` ajoutées ; `name.eyebrow`, `name.auto`, `name.custom` retirées.

## Reste à faire (hors périmètre)

- Le pied de carte disparaîtra avec `ENT-067` : le nom, le décompte et le bouton montent dans la
  barre haute d'un écran dédié. Ce qui est livré ici reste juste jusque-là.
- Ouvrir la correction sélectionne tout le nom, donc taper le remplace au lieu de le compléter.
  C'est le geste habituel d'un « renommer » ; si le curseur en fin de texte est préféré, c'est une
  ligne à changer.

## Définition de terminé

- [x] Au-dessus de 1024 px, la phrase et les mots-clés se tiennent côte à côte ; en dessous, l'un
      sous l'autre.
- [x] La phrase tient sur trois lignes, une par critère.
- [x] Le champ « poste » va jusqu'au bout de sa ligne, et les intitulés suggérés s'y écrivent en
      entier, au corps de texte courant.
- [x] Les valeurs choisies portent la couleur de marque en thème clair comme en thème sombre ;
      l'invite et le chevron restent muets.
- [x] Aucun sélecteur n'affiche « peu importe » tant que rien n'est choisi, et la liste le propose
      toujours pour revenir en arrière.
- [x] Le nom se lit en clair, suivi d'un crayon ; le champ n'apparaît qu'après le clic, focus et
      texte sélectionné.
- [x] L'étiquette « Recherche en cours · auto » a disparu au profit de la mention « se nomme tout
      seul », qui s'efface une fois le nom corrigé.
- [x] Le bouton dit « Créer ma recherche » ; la console de la Visionneuse dit toujours
      « Rechercher ».
- [x] Les trois langues portent les clés ajoutées et ne portent plus les clés retirées.
- [x] Captures fournies : thème clair et sombre, grand écran et largeur téléphone, panneau de
      suggestions ouvert.
