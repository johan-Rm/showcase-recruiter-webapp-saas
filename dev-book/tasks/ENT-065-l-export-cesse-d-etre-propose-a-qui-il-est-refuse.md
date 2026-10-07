# TASK ENT-065 — entreprise : l'export cesse d'être proposé à qui il est refusé

- **Créée le** : 2026-08-06.
- **Session** : `6132d437-ec3b-4d4f-ae53-e96165f4b0a9`.
- **Statut** : ✅ fait le 2026-08-07 — sur la recherche d'un collègue, la sélection de profils
  n'offre plus « Exporter » ; sur les siennes, close comprise, rien ne bouge. Un seul chemin menait
  à l'export, il est désormais conditionné. Sans test, par exemption tranchée avec l'utilisateur.
- **Type** : Frontend entreprise — correction d'un geste proposé à tort. Aucun contrat, aucune règle
  serveur touchée.
- **Tests** : `sans objet — le diff est une condition d'affichage dans un gabarit, posée sur une
  règle qui existait déjà à l'écran deux lignes plus haut`. Rien de calculé n'en sort, et la
  convention de `components/` interdit d'y déplacer de la logique dérivée pour la rendre testable.
  **Tranché avec l'utilisateur à l'implémentation** : l'autre voie — extraire « ce que l'auteur seul
  peut faire » dans un service et le couvrir en unitaire — élargirait la carte d'une correction à un
  refactor de deux écrans. La preuve tient donc à la lecture des boutons **dans le DOM servi**, sur
  trois cas, plus les captures.
- **Réf** : **`ENT-064` — amont, écrite en même temps** (le regroupement des recherches partagées ;
  celle-ci en est le second volet) · **`ENT-051` — mergée** (elle pose que l'écran ne propose jamais
  ce que le serveur refuserait) ·
  `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` ·
  `api/src/State/SearchExportHandler.php` (à lire, pas à modifier)

## Contexte

Sur une recherche qu'un collègue m'a partagée, je sélectionne trois profils dans la Table, je clique
**« Exporter »**, je coche la case de consentement, je valide — et je reçois un refus. Le serveur
réserve l'export à l'auteur de la recherche, exactement comme les favoris et le renommage.

Le bouton n'aurait jamais dû être là. Tous ses voisins de la même barre sont déjà conditionnés à
l'appartenance : « Retenir » est masqué, les quatre boutons d'achat de contact sont inactifs. Seul
l'export a été oublié, et c'est le seul qui aille jusqu'à ouvrir une fenêtre de confirmation avant
d'échouer — l'utilisateur a donc coché une case de responsabilité pour un geste qui ne partira pas.

C'est le principe posé par `ENT-051` qui est en défaut, et il vaut plus que ce bouton : **proposer
puis échouer apprend à l'utilisateur que l'interface ment.**

## Périmètre

- **Le bouton « Exporter » de la barre de sélection de la Table** cesse d'apparaître quand on n'est
  pas l'auteur de la recherche — même règle et même condition que « Retenir », juste au-dessus.
- **Vérifier qu'aucun autre chemin ne mène à l'export** depuis une recherche qu'on ne possède pas :
  raccourci clavier, second bouton dans la Visionneuse, lien direct. Ce qui est trouvé entre dans la
  tâche ; ce qui ne l'est pas se dit en une ligne.
- **Le libellé des trois langues** seulement si un texte nouveau apparaît. Rien n'est prévu : le
  bouton disparaît, il ne s'explique pas.

## Hors périmètre

- **Le regroupement des recherches partagées** et le filtre de l'accueil — `ENT-064`.
- **Le libellé « Ma sélection »** sur la carte d'un collègue, qui parle de « ma » sélection là où ce
  sont les retenus de l'équipe. Écart nommé dans `ENT-064`, toujours pas traité.
- **Rendre l'export possible au destinataire d'un partage.** Ce n'est pas le sujet : la règle
  serveur est délibérée — un seul auteur, pas de co-édition — et cette tâche aligne l'écran sur elle,
  pas l'inverse.
- **Tout contrat, toute règle serveur, toute migration.**

## Décisions de cadrage

**Une carte à part, et pas un ajout à `ENT-064`.** Les deux sujets se ressemblent — « une recherche
partagée est en lecture seule » — mais ils ne se prouvent pas au même endroit : l'un se vérifie sur
l'écran de collection et l'accueil, l'autre dans la Table d'une recherche ouverte. Deux commits, deux
preuves, chacune relisible seule.

**Le bouton disparaît, il ne se grise pas.** Un bouton inactif invite à chercher **comment**
l'activer ; ici il n'y a aucun moyen, et rien à apprendre à l'utilisateur. C'est déjà le traitement
retenu pour « Retenir » sur la même barre — deux traitements différents pour deux interdictions
identiques se liraient comme une différence de sens.

**L'achat de contact n'entre PAS dans cette tâche, et c'est une décision, pas un oubli.** Le cadrage
produit annonçait que le destinataire d'un partage « peut dévoiler (commun) », et le serveur
l'autorise en effet — l'achat s'autorise au niveau de l'entreprise, pas de la recherche. L'écran, lui,
le refuse. **Arbitré : l'écran a raison**, un collègue ne dépense pas les crédits communs depuis la
recherche d'un autre. C'est donc la phrase du cadrage qui est corrigée, dans `ENT-064`, et aucun code
ne bouge de ce côté.

## Règles et cas limites

- **Une recherche close dont je suis l'auteur** garde son export : la clôture range la recherche,
  elle n'en interdit pas la relecture ni l'emport de ce qui a été retenu.
- **Aucune règle nouvelle n'est inventée** : la condition est celle qui existe déjà sur l'écran.
- **La barre de sélection ne doit pas se retrouver vide** au point de n'avoir plus rien à dire pour
  un collègue — à regarder en capture, et à signaler si c'est le cas plutôt qu'à traiter ici.

## Documentation impactée

Aucune page ne décrit l'export comme accessible au destinataire d'un partage : la règle « le partage
est en lecture seule » est déjà écrite dans l'arborescence de l'espace entreprise, et cette tâche la
fait respecter au lieu de la modifier.

Aucun `README.md` ne change de convention.

## Définition de terminé

- [x] Sur une recherche partagée par un collègue, la sélection de profils n'offre plus « Exporter ».
- [x] Sur ma propre recherche, l'export fonctionne exactement comme avant, close comprise.
- [x] Aucun autre chemin n'atteint l'export depuis une recherche dont je ne suis pas l'auteur —
      vérifié, il n'y en avait qu'un.
- [x] Les deux cas sont prouvés en capture authentifiée, côté auteur et côté collègue.
- [x] La ligne `**Tests** :` de cette carte est tranchée : exemption écrite avec sa raison.

## Vérification

**Les boutons de la barre de sélection, lus un par un dans le DOM servi**, un profil coché — c'est
ce qui distingue « absent » de « présent mais grisé », que la capture seule ne tranche pas :

| Recherche ouverte | Ce que la barre propose |
| --- | --- |
| **d'un collègue** (Leila) | E-mail · Téléphone · CV · Compte rendu, **tous inactifs**, puis Tout désélectionner. **Ni « Exporter », ni « Retenir »** |
| **à moi** | Retenir la sélection · les quatre accès · **Exporter** · Tout désélectionner — tous actifs |
| **à moi, une fois close** | la même chose, **« Exporter » toujours actif** |

Le troisième cas est celui de la règle « une recherche close garde son export » : la clôture range
la recherche, elle n'interdit pas d'emporter ce qu'on y a retenu. La recherche a été rouverte après
la mesure.

**Captures authentifiées**, thème sombre, barre haute de la Table :

| Écran | Ce qui a été observé |
| --- | --- |
| « Chef d'équipe · management », de Leila | la barre s'arrête après « Compte rendu » |
| « Product Owner · scrum », à moi | « Exporter » et son icône de téléchargement, entre « Compte rendu » et « Tout désélectionner » |

**Un seul chemin mène à l'export**, vérifié sur tout l'espace entreprise et le serveur
intermédiaire : l'appel `POST /api/searches/{id}/export`, émis par ce bouton et par lui seul. Ni
raccourci clavier, ni second bouton dans la Visionneuse, ni lien direct — la route étant en `POST`,
elle ne s'atteint pas par l'adresse.

## Livré

- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` **(M)** — le bouton « Exporter »
  passe sous la même condition que « Retenir », deux lignes plus haut.

## Reste à faire (hors périmètre)

- **Trois traitements pour une seule interdiction, sur une seule barre.** Pour un collègue,
  « Retenir » et « Exporter » **disparaissent**, tandis que les quatre boutons d'achat de contact
  restent affichés et **grisés** — quatre boutons morts pour une seule action utilisable, « Tout
  désélectionner ». Constaté en vérifiant cette tâche. Non traité : c'est le débat « griser ou
  masquer » sur les achats, que le cadrage a explicitement laissé de côté.
- **Le libellé « Ma sélection »** sur la carte d'un collègue parle de « ma » sélection là où ce sont
  les retenus de l'équipe. Toujours pas traité, nommé pour la troisième fois.
- **Côté serveur, rien n'empêcherait un collègue de dévoiler** depuis la recherche d'un autre :
  l'autorisation se joue au niveau de l'entreprise. Aucun écran ne le propose et la documentation
  dit désormais que c'est interdit, mais l'alignement de l'API appartient à la lane `espace/api`.
