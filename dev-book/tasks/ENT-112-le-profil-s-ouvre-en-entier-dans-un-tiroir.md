# TASK ENT-112 — entreprise : le profil s'ouvre en entier dans un tiroir

- **Créée le** : 2026-08-13.
- **Session** : —
- **Statut** : ✅ fait le 2026-08-13 — le panneau du profil complet, resté à l'état de wireframe en
  feuille latérale, devient un tiroir qui monte par le bas et porte le profil : identité, projet au
  Maroc, parcours daté, et ce qui reste à dévoiler. Ouvert depuis la Visionneuse par un bouton neuf,
  et depuis la Table par le clic sur la ligne, qui existait déjà.
- **Type** : feat.
- **Tests** : unitaires sur la fusion des deux sources et sur ce qui reste masqué — service pur,
  sans montage de composant.
- **Réf** : `ENT-006` (la card est un résumé de décision, pas une fiche) · `ENT-092` et `ENT-111`
  (les paliers de profondeur de la Visionneuse) · `dev-book/cadrage/entreprise/ui/inspecteur.md`
  · `dev-book/cadrage/entreprise/ui/vue_la_visionneuse.md`
  · `dev-book/cadrage/entreprise/ui/vue_la_table.md` · `GET /v1/candidates/{id}`.

## Contexte

**Un recruteur peut parcourir des profils, il ne peut en lire aucun en entier.**

La Table les liste, la Visionneuse les empile, l'inspecteur en donne un aperçu — trois surfaces qui
montrent toutes la même chose : la vignette. Elle porte trente-neuf informations. La fiche que le
serveur sait déjà rendre en porte quarante-cinq, et les six qui manquent sont celles qu'on ouvre un
profil pour lire : le résumé, le parcours détaillé, les formations, les langues, le CV, le compte
rendu d'entretien.

Ce n'est pas un oubli, c'est une décision restée sans suite. La card de la Visionneuse le dit en
tête de son propre code :

> Ce n'est PAS une fiche : le profil complet est un écran à part. C'est un RÉSUMÉ DE DÉCISION […]
> Sans onglets — ils existaient pour ranger un profil entier dans un écran, il n'y a plus de profil
> entier à ranger.

L'écran à part a été **amorcé, puis laissé en plan**. `ProfilePanel.vue` existe : une feuille
latérale modale, correcte sur la mécanique — fenêtre native du navigateur, page rendue inerte, focus
piégé, `Échap` — et **vide sur le fond** : son corps est un wireframe de quinze blocs gris. La Table
l'ouvre déjà au clic sur une ligne, retient l'identifiant du profil cliqué, marque la ligne
ouverte — et **ne transmet jamais cet identifiant au panneau**. C'est le seul maillon manquant, et
il n'a jamais été posé.

Le cadrage, lui, est resté en arrière sur deux points :
[inspecteur.md §4](../cadrage/entreprise/ui/inspecteur.md) fait encore pointer « Ouvrir la fiche
complète » vers la Visionneuse, c'est-à-dire vers la vignette qu'on vient de quitter — et
**l'inspecteur qu'il décrit n'existe pas** : aucun composant ne l'implémente, seuls les gabarits de
maquette en dessinent la zone.

**Le serveur, lui, est prêt.** `GET /v1/candidates/{id}` rend les quarante-cinq propriétés, masquage
compris. Rien n'est à demander à la lane API pour cette carte.

## Périmètre

- **`ProfilePanel.vue` passe de la feuille latérale au tiroir qui monte par le bas**, et défile à
  l'intérieur autant que le profil est long. Il monte presque jusqu'en haut et laisse voir une bande
  de la page en dessous : on est **par-dessus**, pas ailleurs. Poignée visible, fermeture par
  glissement vers le bas, par `Échap` et par clic à côté.
- **Son corps cesse d'être un wireframe** : il porte le profil.
- **Deux points d'entrée, un seul composant.** Le clic sur une ligne de la Table, déjà en place, et
  un bouton **« Voir le profil complet »** en pied de card dans la Visionneuse, qui n'a rien
  aujourd'hui — libellé en clé i18n, trois langues.
- **Ouverture immédiate, remplissage progressif** : le tiroir s'ouvre sur ce que la vignette porte
  déjà — identité, titre, ville, expérience, compétences, projet de retour — et se complète quand la
  fiche arrive.
- **Quatre informations viennent de la vignette et non de la fiche** : `returnProject`,
  `hasPiedATerre`, `piedATerreCity`, `isInMorocco`. Le serveur ne les met pas dans sa fiche
  détaillée ; le tiroir s'ouvrant toujours depuis une liste, la vignette est en mémoire.
- **L'ordre suit celui de la décision**, le même que la card : qui est-ce → va-t-il vraiment venir →
  son parcours → ce qui reste à dévoiler.
- **Les trois pages de cadrage** que cette tâche rend fausses.

## Hors périmètre

- **Tout geste de décision.** Retenir, écarter, dévoiler restent où ils sont — le verdict et
  l'inspecteur. Le tiroir lit, il ne décide pas. Deux endroits qui portent le même geste finissent
  par porter deux états différents.
- **La navigation d'un profil au suivant** depuis le tiroir. Enchaîner les profils est le travail de
  la pile et de la Table, qui le font déjà ; le refaire ici demanderait de tenir la position dans la
  liste, la pagination et la synchronisation avec l'écran de dessous.
- **Le contrat serveur.** Aucune propriété ajoutée, aucun endpoint touché.
- **Le contenu des profils de démo** — trop pauvres pour éprouver l'écran. C'est l'objet de
  `API-042`, qui peut avancer en parallèle.

## Décisions de cadrage

### Les deux surfaces d'un coup, et non l'une puis l'autre

Le tiroir est **un** composant. Le brancher sur la Visionneuse puis, dans une seconde carte, sur la
Table, ferait relire deux fois le même écran et laisserait entre les deux un produit où le geste
existe à un endroit et pas à l'autre. Le surcoût est le branchement, pas le composant.

### Ce qui est payant garde sa place

Sur un profil non dévoilé, **tout est lisible sauf ce qui s'achète**. E-mail, téléphone, CV et
compte rendu d'entretien ne disparaissent pas : ils occupent leur ligne, remplacés par leur bouton
d'achat. C'est déjà la règle du serveur — la propriété est absente de la réponse, jamais remplacée
par une valeur factice — et le tiroir ne fait que la rendre visible.

Le contraire avait été envisagé : ne montrer qu'une partie du parcours pour pousser au dévoilement.
Écarté — ce serait un changement de règle produit, donc un arbitrage commercial, pas une décision
d'écran.

### Les quatre informations viennent de la vignette, la lane API n'est pas sollicitée

`returnProject`, `hasPiedATerre`, `piedATerreCity` et `isInMorocco` sont dans la vignette et pas
dans la fiche. Les faire ajouter côté serveur serait plus propre à terme, et bloquerait cette carte
sur une autre lane pour quatre champs qu'on a déjà sous la main. **On les reprend de la vignette.**

C'est un écart réel entre les deux contrats, et il reste écrit ici pour qui voudra le corriger un
jour : la fiche détaillée est, sur les différenciants Maroc, plus pauvre que la vignette.

### Ouvrir tout de suite, quitte à compléter ensuite

Un tiroir qui attend la réponse du serveur avant de s'ouvrir donne un clic sans effet. Un tiroir qui
s'ouvre sur des blocs gris gaspille ce que la vignette portait déjà. **Il s'ouvre sur ce qu'on sait
et se complète** : le geste répond du premier coup, et les informations déjà lues ne clignotent pas.

### Le tiroir remplace la feuille latérale, il ne s'ajoute pas

Découvert à la lecture du code, après le cadrage : `ProfilePanel.vue` existait déjà, en feuille
latérale et en wireframe. Deux surcouches concurrentes pour le même profil sont exclues — **c'est le
même composant qui change de forme**, et l'état partagé `useProfilePanel`, le libellé « Profil
complet » et le branchement de la Table lui survivent.

Ce qu'on abandonne au passage : la fenêtre modale native du navigateur, choisie pour rendre la page
inerte, piéger le focus et fermer sur `Échap` sans rien réécrire. Le composant de tiroir de la
bibliothèque offre les trois — il repose sur la même mécanique de dialogue — **et** la poignée, le
glissement vers le bas et le geste tactile, qu'il aurait fallu écrire à la main.

### Le clic sur la ligne reste le geste de la Table

Le cadrage prévoyait un bouton dans l'inspecteur. L'inspecteur n'existe pas, et le clic sur la ligne
ouvre déjà le panneau en marquant la ligne ouverte. **On garde ce qui marche** ; le bouton nommé
n'est ajouté que dans la Visionneuse, qui n'a aucun geste.

Deux décisions du cadrage tombent avec l'inspecteur : « le bouton de l'inspecteur change de
destination » et « l'inspecteur reste ouvert derrière » n'ont plus d'objet.

### Les lignes payantes annoncent leur coût, elles ne le prélèvent pas

La définition de terminé réclamait un « bouton d'achat » sur chaque ligne verrouillée, ce que la
décision de lecture seule interdisait dans la même carte. **La lecture seule l'emporte** : la ligne
dit « Masqué — 1 crédit », ce qui suffit à savoir ce qu'on obtiendrait et ce qu'il en coûte, et le
dévoilement reste là où il se faisait déjà. Deux endroits qui débitent le même crédit finiraient par
diverger.

### Le focus n'entre pas tout seul dans le tiroir

Mesuré à la capture : le composant rend bien la page inerte, mais **laisse le focus sur le bouton qui
vient d'ouvrir** — il désactive la mise au point automatique pour ne pas gêner le glissement au
doigt. Au clavier, le tiroir était donc inatteignable et la tabulation continuait de parcourir la
page derrière. Le composant vise explicitement son corps à l'ouverture.

De la même façon, l'en-tête ne peut pas être remplacé en entier : c'est lui qui rend le titre du
dialogue et le rattache au tiroir. En l'écrasant, le tiroir s'annonçait **sans nom** — également
mesuré. Les emplacements du titre et des actions sont utilisés séparément.

### « Profil complet », pas « fiche complète »

Le cadrage de l'inspecteur écrit « Ouvrir la fiche complète ». Mais « fiche » désigne déjà la card
dans le code de la Visionneuse — deux objets, un mot. Le bouton dit **« Voir le profil complet »**,
le titre du tiroir garde le sien, et le cadrage est corrigé dans le même commit.

## Règles et cas limites

- **La fiche ne se charge pas** — réseau coupé, profil devenu invisible, session expirée : le tiroir
  **reste ouvert** sur les informations de la vignette, une ligne explique que le détail n'a pas pu
  être chargé, un bouton propose de réessayer. Rien de ce qui était affiché ne disparaît.
- **Recherche d'un collègue** (`editable` faux) : sans effet ici, le tiroir ne porte aucun geste.
- **L'état d'ouverture survit à la navigation** entre les deux écrans d'une recherche — il vit dans
  `useProfilePanel`, partagé. Le profil affiché, lui, ne doit pas survivre à la fermeture : rouvrir
  le tiroir sur le profil précédent serait un mensonge.
- **Profil pauvre** : le tiroir doit tenir sans résumé, sans formation, avec une seule expérience et
  sans langue. Une section vide ne s'affiche pas — elle ne s'affiche pas *en creux* non plus, avec
  un titre suivi de rien.
- **Mobile d'abord** : le tiroir par le bas est le geste naturel du petit écran ; c'est là qu'il se
  conçoit, et le grand écran en hérite.
- **Accessibilité** : le focus entre dans le tiroir à l'ouverture et n'en sort pas tant qu'il est
  ouvert, `Échap` ferme, le focus revient sur le bouton d'origine à la fermeture, le tiroir porte un
  nom accessible. Le défilement de la page en dessous est bloqué.
- **SSR** : le tiroir est un geste, il ne s'ouvre jamais au rendu serveur.
- **i18n** : aucun texte en dur, trois langues, et le sens de lecture arabe respecté.
- **VueUse plutôt que les API du navigateur** pour toute écoute d'évènement ou de touche.

## Documentation impactée

- [`dev-book/cadrage/entreprise/ui/vue_la_table.md`](../cadrage/entreprise/ui/vue_la_table.md) — la
  phrase « Ne montre **jamais** un profil complet » devient fausse.
- [`dev-book/cadrage/entreprise/ui/inspecteur.md`](../cadrage/entreprise/ui/inspecteur.md) — son
  bouton n'ouvre plus la Visionneuse mais le tiroir, et change de libellé.
- [`dev-book/cadrage/entreprise/ui/vue_la_visionneuse.md`](../cadrage/entreprise/ui/vue_la_visionneuse.md)
  — un geste apparaît en pied de card.
- Le `README.md` du dossier de composants si le tiroir y introduit une convention.

Ces pages sont lues par le client : elles se corrigent dans le commit de la tâche, pas plus tard.

## Vérification

Capture authentifiée sur la stack du worktree, compte `owner@demo.showcase-recruiter-webapp-saas.example`, recherche
« Profils de démo (fixtures) », thème sombre.

**Le relais serveur, seul :**

```
POST /api/auth/login                                        → 200
GET  /api/candidates/01900000-0000-7000-8000-0000000000c1   → 200
     { "summary": "…", "experienceYears": 8, "currentPositionCertainty": … }
```

**L'écran :** le bouton « Voir le profil complet » occupe le pied de card, pleine largeur ; le tiroir
monte par le bas en laissant une bande de page visible, poignée comprise ; le corps défile pendant
que le titre reste accroché ; `Échap` referme et la Table retrouve sa sélection. Le clic sur une
ligne de la Table ouvre le même tiroir, sur le bon profil.

**Trois défauts trouvés à la capture, corrigés avant clôture :**

- les dates de présence sortaient en anglais — le format suivait la langue du navigateur, il suit
  désormais celle de l'application ;
- `search.ai.proficiency.PROFESSIONAL` s'affichait tel quel — les niveaux de langue de la réplique
  Jarvi ne sont pas tous normalisés, le libellé retombe maintenant sur la valeur brute ;
- le portrait manquait, et une balise nue aurait raté le repli sur initiales : le composant du kit
  s'en charge, ce qui se voit ici puisque la photo de démonstration est morte.

**Accessibilité, mesurée dans le navigateur** — deux défauts trouvés, corrigés, puis reconstatés :

```
focus dans le tiroir       : true      (false avant correction)
après Tab, toujours dedans : true      (false avant correction)
titre associé au dialogue  : « Comptable confirmée »   (aucun avant correction)
page derrière inerte       : true
fermé au clic à côté       : true
focus rendu au bouton      : true
```

**Tests** : `bun run vitest run services/search/` → 264 tests, 16 fichiers, verts. Deux des treize
tests de `profile.vitest.ts` ont d'abord échoué et fait corriger le code — les postes en cours se
rangeaient en dernier, et une coordonnée verrouillée passait pour inexistante.

**Non lancé** : les gates qualité, qui tournent à l'ouverture de la PR.

## Livré

- `webapp/shared/types/candidate-profile.ts` — miroir manuel de la fiche détaillée, avec ce que le
  contrat ne garantit pas.
- `webapp/base/server/api/candidates/[id].get.ts` — relais vers `GET /v1/candidates/{id}`.
- `webapp/services/search/profile.ts` et son test — la fusion des deux sources, et les trois états
  d'une donnée qui se paie : dévoilée, verrouillée, inexistante.
- `webapp/spaces/entreprise/app/composables/useProfilePanel.ts` — porte désormais le profil ouvert
  et son chargement, pas seulement l'ouverture.
- `webapp/spaces/entreprise/app/components/ProfilePanel.vue` — le tiroir, à la place du wireframe.
- `webapp/spaces/entreprise/app/components/search/ProfileCard.vue` — le bouton en pied de card.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/index.vue` — le tiroir posé sur la
  Visionneuse.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` — la ligne transmet sa vignette.
- Les trois fichiers de langue.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/data-contracts.md` — la section des exceptions, qui
  n'annonçait qu'un seul DTO hors gardes.

## Reste à faire (hors périmètre)

- **Les trois pages de `dev-book/cadrage/`** — celle de la Table dont la phrase « Ne montre
  **jamais** un profil complet » devient fausse, celle de l'inspecteur et celle de la Visionneuse.
  Le cadrage de cette carte prévoyait de les corriger ici ; `/commit-tache` place `dev-book/cadrage/`
  hors du périmètre d'un commit de code, ce dossier étant écrit pour les clients et suivant son
  propre rythme. **Elles restent donc fausses**, et c'est nommé ici pour que ça ne se perde pas.
- **L'inspecteur décrit par le cadrage n'existe pas** en code : la Table ouvre le tiroir au clic sur
  la ligne, sans volet latéral.
- **Le cadrage dit l'employeur payant**, le code le sert gratuitement avec l'identité. L'écart est
  antérieur à cette carte.
- **Un avertissement d'hydratation** au chargement de la Table, observé et non attribué : le tiroir
  n'apparaît pas dans le rendu serveur de cette page, vérifié.

## Définition de terminé

- [x] Un bouton « Voir le profil complet » en pied de card dans la Visionneuse ouvre le tiroir.
- [x] Le clic sur une ligne de la Table ouvre le même tiroir, sur le bon profil.
- [x] Le tiroir monte par le bas, laisse voir une bande de page, défile à l'intérieur, se ferme par
      `Échap` et par clic à côté. *Le glissement au doigt vient du composant et n'a pas été rejoué
      ici : la capture est menée à la souris.*
- [x] Le corps du tiroir ne contient plus aucun bloc de wireframe.
- [x] Il s'ouvre immédiatement avec les informations de la vignette, puis se complète.
- [x] Il présente, dans cet ordre : identité · projet au Maroc · parcours · ce qui reste à dévoiler.
- [x] Sur un profil non dévoilé, e-mail, téléphone, CV et compte rendu gardent leur place et
      annoncent leur coût.
- [x] Le projet de retour, le pied-à-terre, sa ville et la présence au Maroc sont affichés.
- [x] Une fiche qui ne se charge pas laisse le tiroir ouvert, l'explique et propose de réessayer.
      *Chemin écrit et lisible dans le composant ; la panne réseau n'a pas été simulée.*
- [x] Tests unitaires verts sur la fusion des deux sources et sur le masquage.
- [x] Aucun texte en dur ; les trois langues sont renseignées.
- [x] Focus piégé, `Échap`, retour du focus, nom accessible.
- [ ] ~~Les trois pages de cadrage sont corrigées dans le même commit.~~ — **non fait, et assumé** :
      `dev-book/cadrage/` est hors du périmètre d'un commit de code. Reporté, cf. *Reste à faire*.
