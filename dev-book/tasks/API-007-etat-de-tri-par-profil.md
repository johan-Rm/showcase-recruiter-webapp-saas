# TASK API-007 — API : l'état de tri d'un profil, vu et écarté

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-28 — 4 tests, 48 assertions.
- **Type** : API — modèle et surface de contrat.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

Parcourir une recherche devient une **file de travail** : le recruteur avance profil par profil, en
retient, en écarte, et reprend le lendemain là où il s'était arrêté. Rien de cela n'est stockable
aujourd'hui — le compteur « vus » du mockup est un `Set` en mémoire, perdu au rechargement.

Sur 256 profils, un tri ne tient pas en une séance. Sans persistance, l'état ne sert à rien.

## Décisions

### Deux états, personnels tous les deux

| État | Portée | Pourquoi |
| --- | --- | --- |
| **Vu** | recruteur × recherche × profil | mon avancement, pas celui de mon collègue |
| **Écarté** | recruteur × recherche × profil | mon jugement, relatif à mon besoin |

**Écarter est soustractif là où favoriser est additif.** Un favori partagé ajoute de l'attention ;
un écarté partagé **retire** de la pile d'un collègue un profil qu'il n'a jamais choisi d'ignorer —
et il ne saura pas ce qu'il n'a pas vu. Rejeter est en outre un jugement relatif à un poste : « trop
junior pour moi » est peut-être exactement ce que cherche l'autre.

Un fait tranche presque seul : sur une recherche partagée, le destinataire **ne peut même pas
favoriser** (`FavoriteProcessor` refuse en « lecture seule »). Si « écarté » suivait ce régime,
l'écran de tri lui serait inutilisable. Un état personnel n'écrit pas sur la recherche de l'autre :
il écrit sur **mon parcours** de cette recherche, comme le fait déjà le repère de nouveautés.

### Partager l'information, jamais l'effet

Le bénéfice du partage est préservé autrement : la carte peut annoncer *« Karim a écarté ce
profil »* sans que le profil quitte ma pile. Je décide en connaissance de cause au lieu de refaire le
travail à l'aveugle — et personne ne décide à ma place. Le produit fait déjà cela pour les
dévoilements, qui consomment le budget commun.

### Une table à part, et pas celle des favoris — la carte se trompait

La carte annonçait que « écarté » et « favori » gagneraient à partager une table, « même portée,
même réversibilité ». C'est faux, et l'écriture l'a montré : le favori porte sur **(recherche ×
candidat)**, l'écart sur **(recherche × candidat × recruteur)**. Les fondre aurait rendu l'écart
partagé — exactement ce que la décision ci-dessus refuse.

D'où `SearchProfileReview`, avec son unicité sur le triplet. La contrainte « pas retenu **et**
écarté » ne tient donc pas par la structure ; elle n'a d'ailleurs pas lieu d'être : sur une recherche
partagée, un collègue peut légitimement avoir retenu ce que j'écarte.

### Une autorisation en LECTURE, et c'est le point

Le processeur exige `SavedSearchVoter::READ`, pas `WRITE`. Le destinataire d'une recherche partagée
ne peut ni la modifier ni la favoriser — mais il doit pouvoir la **trier**, sinon le partage ne sert
à rien : il repasserait le jeu entier sans jamais noter son avancement.

C'est possible précisément parce que l'état est personnel : il n'écrit pas sur la recherche de
l'autre, il écrit sur **son parcours** de cette recherche.

### Écarter fait quitter la pile, sans faire disparaître

`/results` sert par défaut la **pile de travail** — les écartés en sont sortis, et le total suit.
`?scope=dismissed` rouvre la corbeille, d'où l'on remet. C'est ce qui rend un tri de plusieurs
centaines de profils fini plutôt qu'infini, sans rendre le geste redoutable.

Le périmètre est porté par un objet `ProfileScope` passé **à la fois** à la page et à la liste
complète d'identifiants : deux paramètres nus auraient fini par diverger, et le total aurait
contredit l'ordre.

### Écarter implique d'avoir vu

On ne rejette pas ce qu'on n'a pas regardé — et laisser le profil « non vu » le ferait ressortir au
prochain « reprendre au premier non vu ». Remettre efface l'écart, jamais le fait d'avoir vu.

## Vérification

`SearchProfileReviewTest` : **4 tests, 48 assertions**.

- Écarter retire de la pile et décrémente le total ; la corbeille rend le profil ; remettre le
  ramène sans effacer « vu ».
- **L'état est personnel** : le collègue voit toujours le profil qu'on a écarté.
- **Le destinataire d'une recherche partagée peut la trier**, alors qu'il ne peut pas la favoriser.
- Un profil hors du jeu est refusé, et le refus le **nomme**.

### L'écriture se fait en LOT

Marquer un profil vu à chaque carte franchie, c'est 256 écritures pour un passage complet. Le client
accumule et pousse par paquets ; l'API accepte une liste d'identifiants.

## Critères d'acceptation

- [ ] Marquer vus / écartés **en lot**, et les relire sur le jeu.
- [ ] Un état posé par un recruteur n'apparaît jamais comme le sien pour un autre.
- [ ] Le destinataire d'une recherche **partagée** peut marquer vu et écarter.
- [ ] Un profil ne peut pas être simultanément retenu et écarté.
- [ ] Écarter est **réversible**.

## Hors périmètre

Ce qui déclenche « vu » à l'écran (une à deux secondes d'arrêt sur la carte) → espace entreprise.
