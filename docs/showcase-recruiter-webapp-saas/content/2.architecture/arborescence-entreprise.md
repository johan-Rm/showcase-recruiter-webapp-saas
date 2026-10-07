# showcase-recruiter-webapp-saas — Espace Entreprise · Arborescence & routes

> **Carte de navigation canonique** de l'espace entreprise (`entreprise.showcase-recruiter-webapp-saas.example`,
> organization-centric). **Source unique** des routes applicatives.
>
> Le **shell** qui héberge l'espace de travail → [application_shell.md](../../../../dev-book/cadrage/entreprise/ui/application_shell.md) ; le
> parcours d'inscription en détail → [inscription.md](../../../../dev-book/cadrage/entreprise/api/inscription.md).

---

# Arborescence

```text
entreprise.showcase-recruiter-webapp-saas.example/
│
├─ ENTRER — avant d'avoir un espace
│  │
│  ├─ /login                      Se connecter
│  │   └─ ?inscription            Demander un accès : on laisse son adresse e-mail
│  ├─ /auth/magic-link            Arrivée depuis le lien reçu par e-mail
│  ├─ /inscription/entreprise     Renseigner sa société, son identité, sa provenance
│  └─ /auth/define-password       Choisir son mot de passe — l'e-mail arrive dès le formulaire envoyé
│
├─ /                              Accueil — MES recherches seules, et les deux façons d'en ouvrir une
│                                   · par critères → /recherches/nouvelle
│                                   · en langage naturel, sur place
│                                   sans aucune recherche à soi → /recherches/nouvelle d'emblée
│
├─ /recherches                    Mes recherches — la collection, en cartes
│  │                                créer · reprendre · renommer · clôturer · dupliquer
│  │                                partager · supprimer — tout depuis le menu de la carte
│  │                                trois groupes : les miennes, puis « Partagées avec moi »
│  │                                (section ouverte), puis les closes sous un pli replié
│  ├─ /nouvelle                   DÉCRIRE QUI L'ON CHERCHE — la phrase à critères, seule en scène
│  │                                gouttière : le fil d'ariane « Tableau de bord › Nouvelle
│  │                                recherche » à gauche, l'aide en pastille à droite — aucun
│  │                                bouton de sortie, c'est le fil qui fait remonter
│  │                                barre haute : le nom qui s'écrit tout seul, et le nombre de
│  │                                profils que la recherche ramènerait, qui suit la frappe
│  │                                « Créer ma recherche » vit DANS la carte, sous la phrase et sur
│  │                                son axe — au pied de la carte sur téléphone ; rien n'existe
│  │                                côté serveur avant ce clic
│  ├─ /:uuid/modifier             RETOUCHER — l'écran de création rouvert sur les critères
│  │                                enregistrés ; enregistrer relance la recherche et mène à la
│  │                                Visionneuse. Réservé à l'auteur, et à une recherche ouverte
│  └─ /:uuid                      LA VISIONNEUSE — affiner, et retenir ses favoris
│     │                             un profil à la fois, où l'on en est à gauche, le verdict
│     │                             et ses deux gestes à droite
│     │                             barre haute : le nom de la recherche à gauche, l'accès aux
│     │                             filtres au centre, le rang « 8 / 234 » à droite — seul écran
│     │                             des trois à situer dans un parcours
│     └─ /table                   LA TABLE — les profils retenus, en lignes (vue par défaut)
│                                   dévoiler les coordonnées · exporter
│
├─ /mon-profil                    Mon profil — identité, préférences, mot de passe
├─ /l-equipe                      L'équipe — membres, invitations et rôles
├─ /l-entreprise                  L'entreprise — société, facturation, abonnement
└─ /credits                       Crédits — solde et journal, communs à l'entreprise
                                    l'achat est réservé au propriétaire du compte
```

> **Entrer se fait d'une traite, sans attendre personne.** On laisse son adresse, on reçoit un lien
> qui prouve qu'elle est bien la sienne, on décrit son entreprise — et l'e-mail qui fait choisir le
> mot de passe part **aussitôt le formulaire envoyé**. L'écran de fin ne fait donc pas patienter : il
> annonce que l'espace est prêt, nomme l'adresse destinataire, et ne renvoie plus de lui-même vers la
> page de connexion.
>
> **La vérification de l'entreprise continue, mais elle ne barre plus la route** : elle se fait
> pendant qu'on travaille. Un refus **suspend** le compte — la session en cours devient inerte et la
> connexion suivante est refusée avec un motif propre, que l'écran distingue d'un mot de passe faux.
>
> **Ces écrans sont hors du poste de travail.** Ils ont leur propre mise en page : on n'y a pas
> encore d'espace, donc ni navigation, ni recherches, ni crédits.

> **⚠️ Deux routes d'archive, temporaires et hors carte** — `/recherches-v1/:uuid` et
> `/recherches-v2/:uuid`. Elles gèlent deux états successifs de la Visionneuse pour qu'on les compare
> côte à côte, le temps d'arbitrer sa mise en page. **Ce ne sont pas des écrans du produit** : aucun
> lien n'y mène, on y va par l'adresse, et chacune embarque sa propre copie des composants de
> l'écran — sans quoi elle afficherait celui d'aujourd'hui. Elles se suppriment d'un bloc une fois
> l'arbitrage rendu, avec `components/search-v1/`, `components/search-v2/` et les deux composables
> `useVisibleProfileV1` / `V2`.

## Le parcours

```text
   /recherches/nouvelle     Je décris qui je cherche, par critères
   (ou /  en langage naturel)
        │
        ▼  je lance
   /recherches/:uuid        LA VISIONNEUSE — un profil à la fois
   (la Visionneuse)           j'ajuste mes critères sans quitter mes résultats,
                              et je retiens ceux qui m'intéressent : mes FAVORIS
        │
        ▼  j'ai mes favoris
   /recherches/:uuid/table  LA TABLE — les profils retenus, en lignes
   (la Table)                 je dévoile les coordonnées de ceux que je veux
                              contacter, puis j'exporte
```

**Les trois écrans d'une recherche partagent une même coque.** Décrire, parcourir, conclure : la
gouttière et la barre haute sont les mêmes, portées par le gabarit `recherche` et non par chacune des
pages. C'est ce qui fait que le nom de la recherche ne bouge pas d'un écran à l'autre — il est déjà
là pendant qu'on la décrit, et la zone de contenu glisse dessous au moment où la recherche naît.

**On lance une recherche pour la parcourir, pas pour la contempler** : lancer mène directement à la
Visionneuse. La recherche s'y nomme dans la barre haute, ses critères se lisent **à droite,
confrontés au profil qu'on regarde**, et les filtres s'ouvrent depuis cette même barre — parce qu'on
ajuste sa recherche *en la lisant*, pas avant de la lire.

**Trois zones, et une seule défile.** Où l'on en est à gauche, les profils au centre, le verdict à
droite. Les deux flancs sont immobiles : c'est ce qui permet de trancher cent profils sans que le
geste change de place. Sous 1280 px, le verdict retombe en pied de fiche, où il désigne à nouveau la
fiche qui le porte.

**La Visionneuse trie, la Table conclut.** On passe de l'une à l'autre quand on a fini de retenir :
la Table ne montre plus le vivier, elle montre ce qu'on a gardé. C'est là qu'on dépense — dévoiler
des coordonnées — et là qu'on emporte.

> **La recherche est l'objet central.** Tout ce qu'on exploite du vivier se lit sous
> `/recherches/:uuid` : une recherche porte ses critères, ses favoris et sa shortlist, et c'est elle
> qu'on ouvre, reprend, duplique ou partage. Il n'y a pas de vivier « en général » que l'on
> parcourrait hors d'une recherche.
>
> **Les deux outils sont deux routes, pas deux onglets.** La Visionneuse et la Table regardent le
> **même jeu de résultats** avec deux postures — l'unité et la masse (cf.
> [les_2_outils.md](../../../../dev-book/cadrage/entreprise/les_2_outils.md)). Chacune a son adresse : on envoie un lien vers la Table
> d'une recherche, ou vers la Visionneuse de la même recherche, et le destinataire arrive au bon
> endroit.
>
> **Les vues de la Table sont dans son adresse, mais ce ne sont pas des routes.** `?vue=tous` ouvre
> l'onglet « Tous » ; sans paramètre, ou avec un nom inconnu, ce sont les **retenus** — la vue par
> défaut, qui ne s'écrit jamais dans l'adresse. Un onglet filtre l'affichage d'un même outil : en
> faire `/table/tous` traiterait un filtre comme une navigation. Le paramètre existe pour que le lien
> soit possible, et l'adresse partageable et rechargeable.
>
> Une adresse ancienne portant `?vue=favoris` reste juste sans qu'on ait rien migré : ce nom n'est
> plus reconnu, elle retombe donc sur le défaut — qui est précisément la vue qu'elle demandait.

## Ce qui n'est pas une route

Certaines choses se superposent à l'écran courant au lieu de le remplacer. Elles n'ont pas d'adresse,
et le bouton « précédent » ne les traverse pas.

| Élément | Où il apparaît | Pourquoi il n'est pas une page |
| --- | --- | --- |
| **Le profil complet** | Visionneuse et Table | Il se lit *par-dessus* la liste qu'on est en train de parcourir ; le quitter, c'est revenir exactement où l'on en était. C'est le même panneau depuis les deux outils. |
| **Les critères de la recherche** | Un écran à part, `/recherches/:uuid/modifier` | Les ajuster **relance la recherche** : ce n'est pas un réglage d'affichage, c'est un nouveau lancement. Il a donc son écran — celui de la création, rouvert sur les critères existants — et non un geste posé au milieu du tri. La Visionneuse l'a porté jusqu'au 2026-08-06 : le jeu changeait sous les yeux de quelqu'un en train de juger des profils. |
| **Les filtres d'affinage** | Colonne de gauche de la Visionneuse | Ils resserrent ce qu'on regarde sans toucher aux critères de la recherche, et ne survivent pas à l'écran : leur place est donc auprès de l'avancement du tri, et non dans la barre haute, qui mène ailleurs. Repliés en volets, sauf les rangées courtes. |
| **Le verdict sur un profil**, et les gestes « retenir » et « écarter » | Colonne de droite de la Visionneuse | Ce qui juge et ce qui tranche vont ensemble, à une place qui ne bouge pas quand la pile défile. La colonne NOMME le profil sur lequel elle porte : sortis de la fiche, les deux boutons n'ont plus d'autre sujet visible. |
| **L'export** | La Table | C'est un fichier qui part, pas un écran où l'on va. |
| **L'ordre du parcours** | Gouttière de la Visionneuse, à droite | Il réordonne la pile sous les yeux, il ne mène nulle part. Il vit **au niveau de l'écran** et non dans la console : celle-ci dit où l'on en est dans le tri, quand l'ordre gouverne la pile entière. Il ne se retient pas d'une visite à l'autre. |

---

# Portées & accès

| Zone | Portée | Accès |
| --- | --- | --- |
| **Entrer** (`/login`, `/auth/*`, `/inscription/entreprise`) | — | ouvert, hors connexion |
| `/` (accueil) | partagé `Organization` | tous les recruteurs |
| **dévoilés · solde de crédits** | partagé `Organization` | tous les recruteurs |
| `/credits` — consulter le solde et le journal | partagé `Organization` | tous les recruteurs |
| `/credits` — **acheter des crédits** | entreprise | **ROLE_COMPANY_OWNER** |
| `/recherches` et tout ce qui en dépend | **la recherche** | **son auteur**, et l'équipe **si elle est partagée** |
| `/l-equipe`, `/l-entreprise` | entreprise | **ROLE_COMPANY_OWNER** |
| `/mon-profil` | individuel | chaque recruteur |

> **Partage d'une recherche.** Une recherche est **privée par défaut** et **explicitement
> partageable** avec l'équipe : la partager rend visibles ses critères, ses favoris et sa shortlist.
> La règle est simple à énoncer — *ce que je partage devient visible* — et elle gouverne aussi le flux
> d'activité de l'[accueil](../../../../dev-book/cadrage/entreprise/ui/smart_home.md).
>
> **Le partage est en LECTURE SEULE** : le collaborateur consulte et **duplique**, mais ne modifie
> **ni les critères, ni les favoris, ni la shortlist**, et **ne dévoile pas** — les crédits sont
> communs à l'entreprise, et on ne les dépense pas depuis la recherche d'un autre. Un seul auteur
> par recherche, pas de co-édition. L'API distingue donc **auteur** (écriture) et **destinataire**
> (lecture). Il garde en revanche son **avancement personnel** : marquer vu et écarter s'écrivent
> sur son parcours, pas sur la recherche.

---

# Notes

- **Un profil reste masqué tant qu'il n'est pas dévoilé.**
- **Les dévoilements sont communs à l'entreprise** : ce qu'un recruteur dévoile, toute l'équipe le
  voit dévoilé. Les recherches, elles, restent celles de leur auteur.
- **Trois zones, trois portées, trois adresses.** « Mon profil » ne concerne que le recruteur
  connecté, « L'équipe » et « L'entreprise » engagent toute la société : elles sont séparées parce
  qu'on n'y accède pas avec les mêmes droits.
