# TASK ENT-098 — entreprise : une recherche enregistrée se retouche

- **Créée le** : 2026-08-12.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-12 — le menu d'une carte ouvre la recherche en modification, l'écran
  de création s'y rouvre prérempli, un avertissement précède l'écriture, et enregistrer relance la
  recherche puis mène à la Visionneuse. Les critères que la phrase ne sait pas exprimer sont
  préservés.
- **Type** : Frontend entreprise — un écran existant rouvert en modification, et un appel élargi.
  Aucun contrat d'API, aucune donnée, aucune règle serveur touchée : tout existe déjà côté API.
- **Tests** : **unitaires (vitest) sur `actionsFor`**, la fonction pure qui décide des gestes offerts
  par une carte de recherche — elle gagne « modifier », et elle ne doit l'offrir qu'à l'auteur d'une
  recherche encore ouverte. Sa suite existe déjà (`services/search/card.vitest.ts`) et s'étend.
  **Le reste est exempté, et motivé** : un écran, un appel réseau élargi d'une clé et une boîte de
  confirmation, que le webapp ne sait pas rendre en test (`webapp/vitest.config.ts` ne couvre que le
  code pur, sans DOM). L'autre logique de la tâche — convertir des critères enregistrés en
  formulaire — **existe déjà et est couverte** (`toPrimaryForm`). Exemption **décidée avec
  l'utilisateur le 2026-08-12**, contre les deux autres options présentées : monter un harnais de
  composant, ou simuler l'appel réseau. **La preuve attendue est un parcours réel capturé** —
  ouvrir, modifier, être averti, enregistrer, arriver sur le nouveau jeu — en thème clair et sombre,
  grand écran et largeur téléphone.
- **Réf** : **`ENT-063` — amont** (elle a retiré la modification des critères de la Visionneuse, et
  dit pourquoi) · **`ENT-081`, `ENT-084`, `ENT-085` — amont** (l'écran de création que celle-ci
  rouvre) · `webapp/spaces/entreprise/app/pages/recherches/nouvelle.vue` ·
  `webapp/spaces/entreprise/app/composables/{useSearches,useSearchDraft}.ts` ·
  `webapp/spaces/entreprise/app/components/search/Card.vue` ·
  `api/src/State/Processor/SavedSearchProcessor.php` (le comportement serveur, à ne pas modifier) ·
  [arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)

## Contexte

**Où ça se passe.** Un recruteur, dans l'espace entreprise, sur « Vos recherches enregistrées ».
Chaque recherche s'y montre en carte, avec ses critères en étiquettes — « Finance », « Disponible
immédiatement », « +1 critère » — et un menu « … ».

**On ne peut pas retoucher une recherche.** Le menu sait la renommer, la dupliquer, la partager, la
clôturer, la supprimer. Il ne sait pas **changer ses critères**. Pour corriger un intitulé de poste
mal choisi ou ajouter un mot-clé, il faut en recréer une — et le tri déjà fait sur l'ancienne, les
profils retenus comme les écartés, reste derrière.

**Le serveur, lui, sait déjà le faire.** `PATCH /v1/searches/{id}` accepte une nouvelle liste de
critères, et son code dit en toutes lettres qu'« ajuster une recherche est un lancement comme un
autre » : il **rejoue le jeu de résultats** dans la foulée. Le relais du webapp transmet déjà cette
clé. Ce qui manque est **uniquement l'écran** : la fonction du front qui appelle ce relais n'accepte
que le nom et le partage, et aucune page n'offre le geste.

**Et la documentation affirme le contraire.** La carte de navigation écrit qu'on ajuste ses critères
depuis la barre haute de la Visionneuse. C'était vrai ; `ENT-063` l'a retiré le 2026-08-06 parce que
le jeu de résultats changeait sous les yeux de quelqu'un en train de trier des gens. La page n'a pas
suivi.

## Périmètre

- **Le menu « … » de la carte gagne une entrée qui ouvre la recherche en modification.**
- **L'écran de création est rouvert à une adresse dédiée**, déjà rempli des critères enregistrés :
  le poste, l'expérience, le niveau et les mots-clés.
- **On modifie avec la même interface qu'à la création** — même phrase, même colonne de mots-clés,
  même décompte de volume qui suit la frappe.
- **Un avertissement précède l'enregistrement** : changer les critères relance la recherche, des
  profils déjà retenus ou écartés peuvent ne plus y figurer, et rien n'est perdu.
- **Le bouton dit « Enregistrer et voir les profils »** et mène à la Visionneuse sur le nouveau jeu.
- **La fonction de mise à jour du front accepte désormais les critères**, comme le relais et l'API.
- **La carte de navigation est corrigée** : la ligne qui prête ce geste à la Visionneuse, et la
  nouvelle adresse.

## Hors périmètre

- **Modifier depuis la Visionneuse.** `ENT-063` l'a retiré il y a six jours, et son motif tient : on
  n'ajuste pas le jeu sous les yeux de quelqu'un qui est en train de trier. Le rouvrir demanderait
  de dire ce qui a changé depuis, ce qui n'est pas le sujet ici.
- **Modifier une recherche reçue en partage** : l'API la réserve déjà à son auteur, et le geste
  prévu pour la retoucher est la duplication, qui existe.
- **Modifier une recherche clôturée** : la rouvrir est un geste distinct, qui existe au menu. Les
  confondre ferait faire deux choses en un clic.
- **Purger le tri devenu incohérent** : les profils retenus ou écartés qui sortent du nouveau jeu
  gardent leur étiquette. Cf. *Décisions de cadrage*.
- **Annoncer un chiffre exact avant d'enregistrer** — combien de profils déjà triés sortiraient du
  jeu. Impossible aujourd'hui, cf. *Décisions de cadrage*, et renvoyé à la lane API.
- **Le comportement serveur**, qui n'est pas touché : ni le recalcul, ni le repère de lecture, ni
  l'enregistrement des intitulés non résolus.

## Décisions de cadrage

**L'écran de création est rouvert, plutôt qu'une seconde interface.** C'est le seul écran qui sache
composer une recherche, et il vient d'être travaillé — deux colonnes, phrase centrée, pied de carte.
Écrire un volet de modification en parallèle créerait un second endroit à tenir d'accord avec le
premier, et l'écart s'installerait au premier ajustement fait d'un seul côté.

**Le tri déjà fait survit, et on prévient avant.** Trois options ont été pesées. Purger ce qui sort
du jeu garde la recherche cohérente mais efface un travail de tri — et un crédit dépensé pour
dévoiler un profil retenu deviendrait impossible à justifier. Ne rien dire est le plus simple, mais
on se retrouve avec des retenus qui ne correspondent plus sans jamais l'avoir su. On garde donc
tout, et l'écran le dit avant que le geste soit fait.

**L'avertissement ne porte pas de chiffre, et c'est une contrainte, pas un choix esthétique.** Dire
« 3 profils que vous aviez retenus ne correspondront plus » suppose de savoir **quels** profils un
jeu de critères ramènerait, avant de l'enregistrer. Le décompte actuel n'en rend que le **total** :
il interroge la recherche avec une page d'un seul élément et ne garde que le nombre. Obtenir la
liste demanderait une capacité serveur qui n'existe pas — donc une tâche de la lane API, dont
celle-ci deviendrait dépendante. Arbitré avec l'utilisateur le 2026-08-12 : on avertit sans chiffre
maintenant, le chiffre viendra si le besoin se confirme. Une variante avait été écartée avec lui :
annoncer le chiffre **après** l'enregistrement, que la réponse du serveur permettrait — elle apprend
la conséquence une fois le geste fait.

**Le bouton nomme les deux effets.** « Enregistrer et voir les profils » est plus long que le
« Sélectionner les profils » de la création, et c'est voulu : sur un écran de modification, un
libellé qui ne nomme que la destination laisse découvrir qu'on vient d'écrire quelque chose.

**L'état de l'écran est DÉRIVÉ, pas semé — corrigé après une preuve rouge.** La première version
chargeait le brouillon partagé de la création par un effet. Le serveur rendait donc la page avant que
cet effet ait tourné, avec zéro critère, et le navigateur la remplissait ensuite : les deux rendus
divergeaient et le bouton d'enregistrement restait **éteint sur une recherche pourtant remplie** —
mesuré, quatre critères en mémoire et bouton désactivé. Ce qui sera enregistré se calcule désormais à
partir de la recherche elle-même, identiquement des deux côtés. Le brouillon partagé quitte cet
écran : il appartient à la création, où il sert à traverser la coque ; ici la recherche est déjà la
source.

**Un nom donné à la main est repris tel quel — corrigé après relecture d'une capture.** La barre
haute affichait « Finance, Développeur logiciel », le nom que les critères composeraient, pendant que
le fil d'ariane affichait le vrai, « Finance France vers Casablanca ». Deux noms pour une recherche,
sur le même écran. Seule une recherche qui se nomme toute seule suit ses critères.

**Le code mort qu'on aurait dupliqué est retiré.** Le chargeur de la Visionneuse gardait un
`updateCriteria` complet, exporté et appelé nulle part depuis qu'`ENT-063` a retiré le geste de cet
écran. Écrire un second chemin à côté aurait laissé deux façons de faire la même chose ; les
trente-trois lignes partent, et la mise à jour existante accepte les critères.

**La liste des gestes est une règle, pas un gabarit — découvert à l'implémentation.** Le cadrage
avait annoncé qu'aucune logique pure ne serait écrite. C'est faux : ce qu'une carte propose est
décidé par une fonction pure déjà testée, qui refuse l'écriture sur la recherche d'un collègue et ne
laisse que « rouvrir » et « supprimer » sur une recherche close. « Modifier » s'y ajoute donc comme
une règle, et sa suite de tests s'étend — c'est ce que la ligne `Tests` dit désormais.

**Seules les recherches à soi et encore ouvertes.** L'API réserve déjà la modification à l'auteur ;
l'écran ne doit pas proposer ce que le serveur refusera. La clôture est exclue en plus, pour ne pas
faire d'un ajustement une réouverture implicite.

## Règles et cas limites

- **Le nom automatique suit les critères.** Une recherche qui se nomme toute seule changera de nom
  en changeant de critères — comportement existant et cohérent. Une recherche renommée à la main
  garde son nom.
- **Ouvrir la modification part des critères enregistrés**, jamais d'un brouillon laissé par une
  visite précédente : le brouillon partagé de l'écran de création doit être remis à zéro puis chargé.
- **Quitter sans enregistrer ne change rien** — ni sur le serveur, ni sur la carte de la liste.
- **Une recherche introuvable, close, ou appartenant à quelqu'un d'autre** ne s'ouvre pas en
  modification : l'entrée du menu n'apparaît pas, et l'adresse tapée à la main renvoie où il faut.
- **Enregistrer sans avoir rien changé** est permis et sans effet visible autre que le passage à la
  Visionneuse ; on ne cherche pas à détecter l'absence de changement.
- **Une panne d'enregistrement laisse l'écran en place** avec ses critères modifiés et un message :
  on ne renvoie pas quelqu'un à la liste en ayant perdu sa saisie.
- **Le nouveau jeu peut être vide** : la Visionneuse a déjà son état « aucun profil ne correspond ».
- **L'avertissement est une boîte de dialogue accessible** : focus dedans à l'ouverture, échappement
  pour annuler, focus rendu au bouton à la fermeture.
- **Aucun texte en dur** : les libellés neufs s'écrivent dans les trois langues du même geste, sans
  quoi le test de parité échoue.

## Documentation impactée

- **[arborescence-entreprise.md](../../docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md)**
  — deux corrections. La ligne du tableau qui place « les critères de la recherche » dans la barre
  haute de la Visionneuse est **fausse depuis `ENT-063`** ; elle doit désigner ce nouvel écran. Et
  l'arbre des adresses doit porter la nouvelle route.

Aucun `README.md` ne change de convention : une page s'ajoute là où les pages vivent, un composable
gagne une clé.

## Définition de terminé

- [x] Le menu « … » d'une carte de recherche propose de la modifier.
- [x] L'entrée n'apparaît pas sur une recherche reçue en partage, ni sur une recherche clôturée.
- [x] La règle qui décide des gestes offerts est couverte par un test, dans les trois cas.
- [x] L'écran s'ouvre déjà rempli : poste, expérience, niveau et mots-clés enregistrés.
- [x] Le décompte de volume suit la frappe, comme à la création.
- [x] Enregistrer affiche d'abord un avertissement disant que la recherche est relancée, que des
      profils déjà triés peuvent ne plus y figurer, et que rien n'est perdu.
- [x] L'avertissement se referme au clavier et rend le focus au bouton.
- [x] Le bouton dit « Enregistrer et voir les profils » et mène à la Visionneuse sur le nouveau jeu.
- [x] Les profils retenus et écartés avant modification le sont toujours après.
- [x] Une recherche au nom automatique change de nom avec ses critères ; une recherche renommée à la
      main garde le sien.
- [x] Quitter sans enregistrer ne modifie rien.
- [x] Une panne d'enregistrement laisse la saisie en place et le dit.
- [x] Les trois langues portent les libellés ajoutés ; le test de parité passe.
- [x] La carte de navigation ne prête plus ce geste à la Visionneuse et porte la nouvelle adresse.
- [x] Captures fournies : thème clair et sombre, grand écran et largeur téléphone, avertissement
      ouvert.

## Vérification

Preuves prises le 2026-08-12 sur la stack du worktree entreprise
(`entreprise.wt-entreprise.localhost:8280`), session `atlas.owner@demo.showcase-recruiter-webapp-saas.example`, sur la
recherche « Finance France vers Casablanca ». Captures dans le dossier de session
`/tmp/claude-1000/-home-johan-www-lab-showcase-recruiter-webapp-saas-mvp/a976bcf6-f820-47e2-8d17-9e731cbcc4fd/scratchpad/`.

**Le parcours, joué de bout en bout**

| Ce qui est prouvé | Ce qui a été observé |
| --- | --- |
| Le menu propose le geste, en tête | « Modifier les critères · Renommer · Dupliquer · Ne plus partager · Clôturer · Supprimer » |
| L'écran s'ouvre à la bonne adresse | `/recherches/019ff56b…/modifier` |
| Le fil d'ariane porte le nom de la recherche | « Tableau de bord › Finance France vers Casablanca » |
| Le formulaire est prérempli | poste = « Développeur logiciel », en sombre, en clair et à 390 px |
| Le bouton est **actif** sur une recherche remplie | `disabled = false` — c'est ce qui était faux avant la réécriture |
| La barre haute et le fil d'ariane disent le même nom | « Finance France vers Casablanca » des deux côtés |
| L'avertissement s'affiche avant d'écrire | « Enregistrer les modifications ? » et son texte |
| Il se referme au clavier et rend le focus | échappement → refermé, focus sur « Enregistrer et voir les profils » |
| Enregistrer mène à la Visionneuse | l'adresse devient `/recherches/019ff56b…` |

**Ce que la modification préserve**

| Ce qui est prouvé | Mesure |
| --- | --- |
| Les facettes que la phrase ne sait pas dire | avant : `sector`, `availability`, `presenceCity` — après : les trois, **plus** `job = Développeur logiciel` |
| Les profils retenus | **2 avant, 2 après** |

**Tests joués** : `services/search/card.vitest.ts` et `base/i18n/locales/parity.vitest.ts` —
**34 verts**. Conventions de dossier : mes fichiers passent les dix-neuf règles.

Aucune gate qualité complète n'a été jouée : elles appartiennent à l'ouverture de la PR. Le
contrôle de typage n'a pas pu aller au bout — la machine était saturée (moins de 3 Go disponibles,
l'éditeur en occupant la majeure partie) ; il tournera à la PR.

## Livré

- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/modifier.vue` **(neuf)** — l'écran de
  modification : garde-fou sur ce que le serveur refuserait, état dérivé de la recherche,
  avertissement avant écriture, enregistrement puis passage à la Visionneuse.
- `webapp/services/search/card.ts` — la règle des gestes gagne « modifier », en tête, sous les mêmes
  interdits que les autres écritures.
- `webapp/services/search/card.vitest.ts` — trois cas couverts : ma recherche active, celle d'un
  collègue, une recherche close.
- `webapp/spaces/entreprise/app/components/search/Card.vue` — la carte annonce le geste, elle ne
  navigue pas elle-même.
- `webapp/spaces/entreprise/app/pages/recherches/index.vue` — la page ouvre l'écran.
- `webapp/spaces/entreprise/app/composables/useSearches.ts` — la mise à jour accepte les critères et
  range le jeu de résultats renvoyé.
- `webapp/spaces/entreprise/app/composables/useSearch.ts` — retrait de `updateCriteria`, code mort
  depuis `ENT-063`.
- `webapp/spaces/entreprise/app/components/search/Sentence.vue` — la phrase accepte des critères de
  départ.
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — six libellés de gestion, une phrase d'aide, un titre
  de page.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/arborescence-entreprise.md` — le geste ne se fait plus depuis
  la Visionneuse, et la nouvelle adresse est inscrite.

## Reste à faire (hors périmètre)

- **Une recherche composée uniquement de critères que la phrase ne sait pas exprimer** — secteur,
  disponibilité, ville — ouvre un formulaire **vide** alors qu'elle porte bien des critères. C'est
  correct, il n'y a rien à montrer, mais rien ne le signale non plus.
- **Le chiffre exact dans l'avertissement** attend une capacité serveur : dire quels profils un jeu
  de critères ramènerait avant de l'enregistrer.
- **Deux violations de conventions traînent sur la branche**, dans `ProfileCard.vue` et
  `MatchList.vue` : le mot « désormais » y raconte une chronologie. Elles viennent d'une autre
  session et bloqueront la PR.
