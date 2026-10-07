# TASK 090 — API : enrichissement Jarvi asynchrone à l'inscription (statut + rafraîchissement)

- **Créée le** : 2026-07-18.
- **Statut** : 📋 à faire.
- **Type** : API — onboarding candidat + ingestion Jarvi.
- **Dépend de** : tâches `088` (canal `/applicants`), `089` (câblage onboarding).
- **Lié à** : tâches `020`/`034` (ingestion CLI), `080` (chaîne async Messenger),
  `019` (activité/notifications candidat).
- **Réf cadrage** :
  - `dev-book/cadrage/candidat/api/inscription.md`
  - `dev-book/cadrage/candidat/ui/parcours_utilisateur_candidat.md`

## Contexte

Test réel du 2026-07-18 : après `POST /applicants`, Jarvi renvoie le `headline` immédiatement mais
le **profil complet (positions/educations/skills/summary) n'arrive qu'en ~4 min** en asynchrone.
Juste après l'inscription, `candidate_jarvi` est donc **quasi vide** (`PENDING`), et l'import
quotidien peut ne pas être passé. Sans stratégie explicite, le candidat voit un profil vide et
l'équipe ne sait pas distinguer « en cours d'enrichissement » de « échec ».

## Objectif

Rendre l'état d'enrichissement **observable et convergent** : le profil finit peuplé sans dépendre
uniquement du prochain run quotidien, et le statut est lisible côté candidat et back-office.

## Périmètre

- Statut de sync par candidat (réutiliser `CandidateJarvi.syncStatus` / `SyncStatus`) couvrant au
  moins : `PENDING` (soumis, pas encore enrichi), `SYNCED` (peuplé), `FAILED` (à requalifier).
- **Rafraîchissement ciblé différé** après soumission : planifier un refresh on-demand du profil
  (`app:jarvi:profiles:import --profile-id=<jarviProfileId>` ou l'ingestor appelé via Messenger)
  à un horizon aligné sur le délai observé (~quelques minutes), avec quelques tentatives espacées,
  plutôt que d'attendre l'import du lendemain.
- Détecter la « complétude » d'un profil enrichi (ex. présence de positions/skills) pour passer
  `SYNCED` et arrêter les tentatives.
- Exposer le statut dans la sortie candidat (`MeOutput` ou équivalent) pour piloter l'UX d'attente.

## Hors périmètre

- Écran/animation d'attente côté webapp (front) → tâche webapp dédiée (à créer).
- Notifications push/email de fin d'enrichissement → tâche `019` si retenu.
- Modification du contrat d'extraction Jarvi (non maîtrisé).

## Invariants

- Le délai ~4 min est un **ordre de grandeur observé** (n=1), pas un SLA : la stratégie doit tolérer
  plus lent, plus rapide, ou un échec d'enrichissement.
- Le rafraîchissement ciblé respecte le quota Jarvi (tentatives bornées, espacées).
- Aucune donnée maître showcase-recruiter-webapp-saas écrasée par le refresh.

## Critères d'acceptation

- [ ] Après inscription, un rafraîchissement ciblé est planifié sans attendre l'import quotidien.
- [ ] Le statut passe `PENDING → SYNCED` dès que le profil est enrichi (positions/skills présents).
- [ ] Les tentatives sont bornées et espacées ; un profil jamais enrichi finit `FAILED` (requalifiable).
- [ ] Le statut d'enrichissement est exposé côté candidat.
- [ ] Tests : enrichissement arrivé au 2ᵉ essai, jamais arrivé (FAILED), quota respecté.

## Points de vigilance

- **Faux négatif** : ne pas marquer `FAILED` avant d'avoir laissé le temps observé + marge.
- **Idempotence** du refresh (l'ingestion est déjà idempotente, ne pas dupliquer).
- **Observabilité** : journaliser chaque tentative (candidat, essai, résultat).

## Docs à tenir à jour

- `dev-book/cadrage/candidat/api/inscription.md`
- `docs/showcase-recruiter-webapp-saas/content/2.architecture/jarvi-integration.md`
