# TASK OPS-021 — trois brouillons de cadrage disparaissent, et la doc cesse de les citer

- **Créée le** : 2026-07-31.
- **Statut** : ✅ fait le 2026-07-31 — trois fichiers supprimés, trois citations retirées de la doc
  technique.
- **Type** : Refactor — nettoyage documentaire ; aucun code touché.
- **Tests** : sans objet — la tâche supprime du Markdown et corrige deux en-têtes.
- **Réf** : task `OPS-018` (qui avait recensé les quatre documents restants de `draft/`)

## Contexte

`dev-book/cadrage/draft/` portait quatre brouillons. Trois n'avaient plus d'usage :

- `infrastructure_draft.md` — une table de vulgarisation en 9 lignes (VPS, Docker, PostgreSQL,
  Symfony, Nuxt) écrite pour un lecteur non technicien ;
- `parcours_utilisateur_recruteur_draft.md` — 167 lignes de parcours, antérieures à la refonte
  cockpit ;
- `reunion-filtres-vivier-2026-07.md` — 128 lignes de préparation de réunion.

Le piège n'était pas la suppression mais ce qui pointait dessus : **deux pages de la doc technique
les citaient**, et `architecture.md` les listait parmi ses sources.

## Périmètre

- Les trois fichiers supprimés.
- Les trois citations retirées de `architecture.md` et `docker-and-make.md`.

## Hors périmètre

- `notes_en_vrac.md`, le quatrième brouillon, toujours cité comme source par `architecture.md`.

## Décisions de cadrage

### Rien de technique ne se perd

`infrastructure_draft.md` était le seul des trois à porter du contenu encore vrai — mais vulgarisé,
et intégralement couvert par `docker-and-make.md` (compose, Caddy, environnements) et
`architecture.md` (couches, sous-domaines). Le supprimer ne retire aucune information du dépôt, il
retire une **seconde formulation** de la même.

`docker-and-make.md` disait « complète INFRASTRUCTURE.md » : c'était l'inverse: le document
technique était le complet, le brouillon l'abrégé.

### Une source supprimée se retire de la liste des sources

`architecture.md` ouvre sur « Sources : … », six documents dont deux disparaissaient ici. Laisser
les liens aurait produit deux 404 dans la page la plus lue de la doc technique — et une liste de
sources qui ment sur ce dont elle est tirée est pire qu'une liste absente.

Les quatre sources restantes ont été **résolues sur disque**, pas seulement relues.

## Règles et cas limites

- La carte `OPS-018` cite les trois fichiers par leur nom : registre historique, laissée telle
  quelle.
- `notes_en_vrac.md` reste, et reste cité.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` — deux sources retirées de l'en-tête.
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` — la mention « complète
  INFRASTRUCTURE.md » retirée.

## Vérification

- **Plus aucune référence** aux trois fichiers hors `dev-book/tasks/`.
- **Les quatre sources restantes d'`architecture.md` résolvent** depuis le dossier de la page :
  `initial_brief.md`, `notes_en_vrac.md`, `parcours_utilisateur_candidat.md`, `roadmap.md`.

## Livré

- `dev-book/cadrage/draft/infrastructure_draft.md` (D)
- `dev-book/cadrage/draft/parcours_utilisateur_recruteur_draft.md` (D)
- `dev-book/cadrage/draft/reunion-filtres-vivier-2026-07.md` (D)
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/architecture.md` (M)
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md` (M)

## Reste à faire (hors périmètre de cette tâche)

- **`dev-book/cadrage/draft/` n'a plus qu'un fichier**, `notes_en_vrac.md`. Un dossier `draft/` pour
  un document unique se justifie mal.

## Définition de terminé

- [x] Les trois fichiers ont disparu.
- [x] Aucune page de la doc ne les cite plus.
- [x] Les sources restantes d'`architecture.md` résolvent toutes.
