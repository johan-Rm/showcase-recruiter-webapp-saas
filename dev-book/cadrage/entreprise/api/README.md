# API et regles serveur entreprise

Ce dossier contient les documents de cadrage qui contraignent l'API, les statuts metier, les
integrations serveur et les invariants de donnees de l'espace entreprise.

## Documents

- [inscription.md](./inscription.md) : inscription initiale du declarant, validation de l'entreprise et push Jarvi unique.
- [invitation_membre.md](./invitation_membre.md) : invitation membre par e-mail, acceptation, mot de passe, retrait et reactivation.
- [import_profils_jarvi.md](./import_profils_jarvi.md) : ingestion des profils Jarvi vers la replica locale (vivier).
- [recherche_et_masquage.md](./recherche_et_masquage.md) : recherche du vivier (replica locale) et masquage des profils non devoiles.
- [credits_et_devoilement.md](./credits_et_devoilement.md) : systeme de credits, devoilement atomique/idempotent, devoilement groupe.

Les routes exposees officiellement restent documentees dans `docs/showcase-recruiter-webapp-saas/content/2.architecture/`.

## Dette ouverte par la refonte cockpit (2026-07-12)

La refonte UI ([../ui/](../ui/)) impose des changements de **contrat serveur** — aucune tâche n'est
encore ecrite pour eux :

| Sujet | Detail |
| --- | --- |
| **Portee des listes** | `Favorite` doit porter une **cle vers la recherche** ; la **shortlist** devient une collection distincte (`shortlist ⊆ devoiles`). **Refonte de la task 008.** |
| **Recherches persistees** | entite « recherche » (criteres ordonnes, nommage auto, duplication, **auteur + etat de partage**) — **task a creer**. |
| **Partage lecture seule** | un destinataire **lit** une recherche partagee (et peut la dupliquer) ; **l'ecriture est reservee a l'auteur**. Pas de co-edition. |
| **Joignabilite** | exposer `hasEmail` / `hasPhone` / `hasResume` / `hasReport` **avant** devoilement (jamais la valeur). |
| **Nouveaux champs** | « ouvert aux opportunites », **villes cibles au Maroc**, referentiel de postes. |
| **Agregats de facettes** | compteurs par option de filtre (comptage facette). |
| **Export CSV** | endpoint serveur **trace** (le mockup genere le CSV cote client) + accuse RGPD. |
| **Imputation crediteuse** | rattacher la `CreditTransaction` a la **recherche** courante. |
| **Suppressions** | **salaire** retire de la serialisation ; **`CandidateAssignment`** revoque. |
