# TASK ADM-024 — la barre haute annonce l'écran, et la page cesse de le répéter

- **Créée le** : 2026-08-11.
- **Session** : `417ad4f5-232c-42c6-8257-f61c6e7c2f12`.
- **Statut** : ✅ fait le 2026-08-11 — au-dessus de 1 024 px la barre haute annonce le titre de
  l'écran, les six titres riches sont conservés au mot près, et aucune page ne l'affiche deux fois.
  Sous le seuil, le fil d'ariane est intact. **Une case n'a pas pu être cochée** : la fiche candidat
  est inatteignable, pour une raison de routage antérieure à cette carte — cf. *Reste à faire*.
- **Type** : refactor — le titre d'écran change de place et cesse d'être dit deux fois ; aucun
  contenu n'est ajouté ni retiré.
- **Tests** : `sans objet côté unitaire` — le composable s'appuie sur l'état partagé et le routeur du
  framework ; un test unitaire les remplacerait par des faux et ne vérifierait alors que lui-même.
  Ce qui doit être prouvé est un **comportement**, et il se mesure dans le navigateur : le titre
  attendu sur chacun des onze écrans, le **nom réel** sur les deux fiches à titre dynamique, le fil
  d'ariane intact sous 1 024 px, le titre toujours présent dans le document mais invisible — et
  surtout le piège, prouvé explicitement : **naviguer d'un écran qui déclare un titre vers un écran
  qui n'en déclare pas, et vérifier que la barre haute retombe sur le libellé de navigation au lieu
  de garder l'ancien.**
- **Réf** : **`ADM-023` — voisine, non bloquante** (le compte et les notifications descendent en pied
  de colonne ; les deux cartes touchent `Topbar.vue`, l'ordre compte mais aucune n'attend l'autre) ·
  **`ADM-021`**, qui a déjà allégé cette barre haute · fichiers touchés :
  [admin/app/components/Topbar.vue](../../webapp/spaces/admin/app/components/Topbar.vue), les onze
  pages de `admin/app/pages/`, un composable à créer dans `admin/app/composables/`,
  [arborescence-admin.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md).

## Contexte

Un administrateur sur grand écran lit **trois fois** où il se trouve, dans un carré de trente
pixels de haut : la barre latérale surligne l'entrée courante, la barre haute affiche un chemin
— « Espace admin › État de la base » —, et la page redit son titre juste en dessous.

La barre latérale est déjà le repère de position sur cette largeur : c'est sa fonction, et elle ne
disparaît jamais. Le chemin n'ajoute donc rien, et il occupe la seule bande horizontale de l'écran.

### Le doublon est réel, mais il est l'exception

Relevé sur les huit écrans, en comparant ce que dit la barre latérale et ce que dit le titre de la
page :

| Écran | Barre latérale | Titre de la page | |
| --- | --- | --- | --- |
| Tableau de bord | Tableau de bord | **Centre de contrôle** | ≠ |
| Entreprises | Entreprises | **Entreprises — file de revue** | ≠ |
| Utilisateurs | Utilisateurs | **Utilisateurs & droits** | ≠ |
| Jarvi | Jarvi | **Ingestion Jarvi** | ≠ |
| Référentiels | Référentiels | **Référentiels qualifiés** | ≠ |
| Compte | Compte | **Mon compte** | ≠ |
| Le vivier | Le vivier | Le vivier | = |
| État de la base | État de la base | État de la base | = |

**Six sur huit disent deux choses différentes**, et c'est systématiquement le titre de page qui est
le plus riche : il dit ce que l'écran *fait*, là où la barre latérale ne dit que où l'on est. Deux
écrans seulement portent un vrai doublon — et ce sont ceux d'où la demande est partie.

**C'est ce relevé qui gouverne la carte** : reprendre le libellé de navigation dans la barre haute
aurait appauvri six écrans sur huit.

### Et trois écrans ont un titre qui n'existe nulle part dans la navigation

La fiche d'un candidat porte **le nom de la personne**. Et `/vivier` est devenu un dossier depuis
qu'une page de détail par métrique y a été ajoutée : son titre est **le nom de la métrique**. Ces
titres-là ne peuvent pas venir de la barre latérale, qui ne connaît que des sections.

### Ce que le fil d'ariane porte d'autre

Il est aussi **le seul lien texte de retour à l'accueil**. Sous 1 024 px, où la barre latérale
n'existe pas, il est en outre le seul repère de position. Il y reste donc intact.

## Périmètre

- **Un composable de titre de page** : la page déclare le sien, la coque le lit.
  - Il accepte une valeur **qui change** — un nom chargé après coup, une métrique résolue depuis
    l'adresse.
  - **Une page sans titre n'hérite jamais de celui de la précédente** — obtenu en rangeant le titre
    AVEC sa route plutôt qu'en le vidant, cf. *Décisions de cadrage*.
- **La barre haute** affiche ce titre **au-dessus de 1 024 px**, retombe sur le libellé de navigation
  si la page n'en déclare pas, et **garde le fil d'ariane en dessous du seuil**.
- **Les onze pages** déclarent leur titre et **masquent leur `<h1>` à l'œil seulement** : il reste
  dans le document, à sa place.

## Hors périmètre

- **Le titre de l'onglet du navigateur**, aujourd'hui identique sur tous les écrans admin. Autre
  surface, qui touche aussi le référencement et les autres espaces.
- **Les sous-titres et compteurs** posés sous le titre — « 2 561 candidats », « Au 2026-08-11
  13:33 », « L'état de la plateforme, et ce qui attend une décision ». Ils restent dans la page,
  visibles. **Seul le titre monte.**
- **La page d'échafaudage**, qui n'a pas de titre : elle retombera sur le libellé de navigation,
  et c'est le comportement voulu.
- **La coque du cockpit entreprise** : autre lane.

## Décisions de cadrage

### La page déclare son titre, la barre haute ne le devine pas

Trois voies étaient ouvertes.

**Reprendre le libellé de la barre latérale** n'aurait touché aucune page — le mécanisme existe
déjà, c'est lui qui alimente le fil d'ariane. Écartée par le relevé ci-dessus : six écrans sur huit
y auraient perdu leur titre riche, et les trois pages de détail auraient affiché le nom de leur
section à la place de leur sujet — « Le vivier » sur la fiche d'une personne.

**Ne déclarer un titre que sur les pages qui en veulent un plus riche** aurait touché moins de
fichiers. Écartée aussi : deux sources pour une même chose, et c'est exactement ce que `useNav` a
été créé pour supprimer — les libellés y avaient déjà divergé une fois, au point que deux écrans
n'avaient plus de fil d'ariane du tout.

**Ce qui est retenu** : chaque page déclare, la coque affiche, et le libellé de navigation ne sert
plus que de **filet** quand rien n'est déclaré.

### Un composable, et pas une métadonnée de route

Nuxt offre un endroit pour poser des métadonnées de page, sans rien créer. Il ne convient pas ici :
la valeur y est figée, or trois écrans ont un titre qui n'est connu qu'après le chargement des
données. Il aurait fallu un second mécanisme pour eux, donc deux façons de faire la même chose.

Le passage par la structure de contenu que les pages remplissent déjà a été écarté pour une raison
de forme : la barre haute vit **en dehors** de cette structure, et faire remonter un texte d'un
enfant vers un frère est précisément ce qu'un état partagé fait mieux.

### Le titre est rangé AVEC sa route, plutôt que vidé

Le cadrage disait « il se vide au changement de route ». Le résultat est celui-là, le moyen non — et
le moyen compte, parce que le vidage a un défaut d'ordre. Pendant une navigation, la page qui arrive
peut poser son titre **avant** que celle qui part ne libère le sien : le nettoyage effacerait alors
le bon, et la barre haute se retrouverait vide au lieu de rémanente. On échange un défaut contre un
autre.

En rangeant le titre avec le chemin auquel il appartient, un titre venu d'ailleurs est simplement
**ignoré à la lecture**. Il n'y a plus d'ordre à respecter, donc plus de course à perdre.

### ÉCART — un compteur voyageait dans le titre

Sur `/candidats`, le `<h1>` enveloppait **le titre ET le compteur** — « Le vivier · 2 561
candidat(s) ». Le masquer emportait le compteur avec lui, et la page perdait une information
visible. Repéré à la mesure, corrigé : le titre seul se masque, le compteur redevient une ligne à
part. Les neuf autres pages n'avaient que leur titre dans le `<h1>`, vérifié un par un.

### Le titre reste dans le document, invisible

Un document n'a qu'un seul titre principal, et sa place est dans le contenu. Le porter uniquement
dans la barre haute le sortirait du repère principal : la structure du document dirait alors que la
page n'a pas de titre, et un lecteur d'écran l'annoncerait ainsi.

Le titre reste donc dans la page, **masqué à l'œil**. Personne ne voit de doublon, la structure
reste juste.

### Le seuil, et pourquoi il y a deux comportements

Sous 1 024 px, la barre latérale n'existe pas. Le fil d'ariane y est **à la fois** le seul repère de
position et le seul retour à l'accueil en texte : l'y remplacer par un titre ferait perdre les deux.
Le changement ne vaut donc qu'au-dessus du seuil — là où la place manque et où la colonne prend le
relais.

## Règles et cas limites

- **Le piège du titre rémanent est le risque principal.** Une page qui déclare, une page qui oublie,
  et la barre haute garde l'ancien titre : l'utilisateur lit un nom d'écran faux, sans qu'aucune
  erreur ne le signale. La remise à zéro au changement de route est ce qui l'empêche, et elle se
  **prouve** en naviguant vers la page d'échafaudage, qui ne déclare rien.
- **Sous 1 024 px, rien ne doit bouger.** C'est la moitié du travail qu'on ne voit pas.
- **Le titre masqué doit rester lisible par un lecteur d'écran** — masqué à l'œil, pas retiré du
  document. `ADM-022` a montré que la différence entre les deux se mesure dans l'arbre
  d'accessibilité, et pas autrement.
- **Les deux fiches à titre dynamique** affichent « … » ou un équivalent tant que la donnée n'est
  pas là. Le titre ne doit pas sauter du vide au nom d'une façon qui déplace la mise en page.
- **La barre haute ne doit pas grandir** : un titre est plus long qu'un chemin, et un nom de
  métrique peut être très long. Il se tronque plutôt que de pousser ce qui est à sa droite.
- **Aucun texte en dur** : les onze titres passent par des clés de traduction, sauf les deux
  dynamiques qui portent une donnée.
- **`ADM-023` touche le même fichier.** Les deux cartes ne partagent pas un commit ; celle qui passe
  en second relit ce que la première a laissé.

## Documentation impactée

- **`docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md`** — deux lignes, vérifiées dans le
  fichier :
  - « Fiche candidat — **hérite du fil d'ariane de sa section** » : au-dessus du seuil, elle
    affichera le nom de la personne ;
  - « `/compte` … **le fil d'ariane sait quand même le nommer** » : au-dessus du seuil, il n'y en a
    plus.

  La troisième mention — ajouter une entrée la fait apparaître « dans la barre, dans le tiroir
  mobile et dans le fil d'ariane » — reste vraie, grâce au repli sur le libellé de navigation.
- Aucune autre page. La carte déplace un titre et ne crée aucune convention.

## Définition de terminé

- [x] Au-dessus de 1 024 px, la barre haute affiche le titre de l'écran, et lui seul.
- [x] Les six titres riches sont conservés au mot près — vérifiés un par un.
- [x] La fiche à titre dynamique **atteignable** affiche son sujet réel : le détail d'une métrique
      annonce « Ancienneté renseignée », un titre que la navigation ne connaît pas.
- [ ] **NON VÉRIFIABLE** — la fiche candidat devrait annoncer le nom de la personne. Elle est
      inatteignable : à son adresse, c'est la liste qui s'affiche. Défaut de routage **antérieur à
      cette carte** et hors de son périmètre, cf. *Reste à faire*. Le mécanisme est prouvé par
      l'autre page dynamique.
- [x] Aucune page n'affiche son titre deux fois.
- [x] Le titre reste dans le document et un lecteur d'écran le trouve — lu dans l'arbre
      d'accessibilité, pas supposé.
- [x] Naviguer vers un écran qui ne déclare rien fait retomber la barre haute sur le libellé de
      navigation, **jamais** sur le titre précédent.
- [x] Sous 1 024 px, le fil d'ariane est **inchangé**, retour à l'accueil compris.
- [x] Un titre très long se tronque sans pousser ce qui est à sa droite.
- [x] Les deux lignes d'`arborescence-admin.md` ne disent plus quelque chose de trompeur.
- [x] Captures au-dessus et en dessous du seuil, dans les deux thèmes.
- [x] La carte est à jour de ce qui a réellement été fait, y compris les écarts au cadrage.

## Vérification

Mesures relevées dans le navigateur, authentifié, sur la stack du worktree admin.

### Les huit écrans statiques, au-dessus de 1 024 px

| Écran | Barre haute | Titre du document |
| --- | --- | --- |
| `/` | Centre de contrôle | présent, masqué |
| `/entreprises` | Entreprises — file de revue | présent, masqué |
| `/candidats` | Le vivier | présent, masqué |
| `/utilisateurs` | Utilisateurs & droits | présent, masqué |
| `/jarvi` | Ingestion Jarvi | présent, masqué |
| `/referentiels` | Référentiels qualifiés | présent, masqué |
| `/vivier` | État de la base | présent, masqué |
| `/compte` | Mon compte | présent, masqué |

**Les six titres riches sont conservés au mot près.** C'est ce qui justifiait de ne pas reprendre le
libellé de navigation, et la table du *Contexte* en donne la raison chiffrée.

### Le titre rémanent, prouvé par le cas qui devait échouer

De `/jarvi` — qui déclare « Ingestion Jarvi » — vers la page d'échafaudage, qui ne déclare rien :
la barre haute **ne garde pas** l'ancien titre. Cette page n'ayant pas non plus d'entrée de
navigation, le repli aboutit à une barre vide, ce qui est la dégradation attendue pour un
échafaudage.

### Le titre dynamique

Sur le détail d'une métrique, la barre haute affiche **« Ancienneté renseignée »** — un titre que la
navigation ne connaît pas, résolu depuis l'adresse.

### Sous le seuil

Le fil d'ariane est **présent**, et aucun titre n'apparaît dans la barre.

### Ce que la mesure a rattrapé

Le compteur de `/candidats` — « 2 561 candidat(s) » — vivait **dans** le `<h1>` et disparaissait avec
lui. Corrigé, puis revérifié : la ligne est de nouveau visible.

### Gate impactée

`prettier --write` sur les onze pages, la barre haute et le composable ✔.

## Livré

- `webapp/spaces/admin/app/composables/usePageTitle.ts` (+) — la page déclare, la coque lit. Le
  titre est **rangé avec sa route**, ce qui rend le titre rémanent impossible sans dépendre d'un
  ordre de nettoyage.
- `webapp/spaces/admin/app/components/Topbar.vue` (M) — affiche le titre au-dessus de `lg`, retombe
  sur le libellé de navigation à défaut, et garde le fil d'ariane en dessous.
- Les **dix pages** qui déclarent leur titre et masquent leur `<h1>` à l'œil : les huit statiques,
  la fiche candidat (le nom de la personne), le détail d'une métrique (le nom de la métrique).
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-admin.md` (M) — les deux lignes qui parlaient
  du fil d'ariane, plus le signalement du défaut ci-dessous.

## Reste à faire (hors périmètre)

- **La fiche candidat est inatteignable, et ça n'a rien à voir avec cette carte.** À l'adresse
  `/candidats/{id}`, c'est la **liste** qui s'affiche : `candidats.vue` et `candidats/[id].vue`
  coexistent, ce qui fait du premier le parent du second, et le parent ne rend nulle part son
  enfant. **Vérifié antérieur** — le fichier n'a jamais porté ce qu'il faut, y compris sur
  l'intégration. Une autre session a restructuré `/vivier` en dossier, vraisemblablement pour cette
  raison ; `/candidats` est resté dans l'ancienne forme. C'est la seule case de la définition de
  terminé qui reste ouverte.
- **Le titre de l'onglet du navigateur** reste identique sur tous les écrans admin.
