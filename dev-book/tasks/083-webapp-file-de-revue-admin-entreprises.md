# TASK 083 — webapp : file de revue admin des entreprises (front)

- **Créée le** : 2026-07-13.
- **Statut** : ✅ livré le 2026-07-13 — liste filtrable, fiche de revue (provenance incluse),
  valider / refuser. Vérifié de bout en bout en dev (inscription → file → validation).
- **Type** : Front — Webapp Nuxt (espace admin).
- **Dépend de** : tâche `026a` (route API de **liste** filtrable par statut — seul maillon API
  manquant) · tâches `037` (auth/BFF) · `070` (kit `ui/`).
- **Contrepartie de** : tâche `082` (tunnel d'inscription — l'autre bout du parcours).
- **Réf cadrage** : `dev-book/cadrage/entreprise/api/inscription.md` (§ États possibles).
- **Réf code** : `webapp/spaces/admin/app/pages/entreprises.vue` (coquille de démo à remplacer).

## Contexte

Une entreprise qui s'inscrit atterrit en `EN_REVUE`. **Personne ne peut aujourd'hui l'en sortir
depuis une interface** : `POST /v1/organizations/{id}/validate` et `/reject` existent côté API,
mais aucun écran ne les appelle, et la page admin `entreprises.vue` est une coquille de démo.
Sans cet écran, le tunnel de la tâche `082` débouche sur un cul-de-sac.

**Préalable API** : il n'existe **pas** de `GET /v1/organizations` filtrable par statut — c'est
précisément l'objet de la tâche `026a`, qui doit passer avant.

## À livrer

`spaces/admin/app/pages/entreprises.vue` (remplace la démo) :

- **Liste** des entreprises, filtrable par statut (`EN_REVUE` par défaut — c'est la file de travail
  réelle de l'admin — puis `INCOMPLET`, `VALIDEE`, `REFUSE`).
- **Fiche de revue** : les informations saisies à l'inscription, **provenance comprise** — le
  cadrage en fait un signal de légitimité, elle doit être visible sans clic supplémentaire.
- **Deux actions** : valider, refuser. Le refus est irréversible côté parcours : confirmation
  explicite avant d'appeler la route.
- **`INCOMPLET`** : action « renvoyer un magic link » pour permettre à l'entreprise de reprendre son
  inscription (prévue au cadrage).

## Règles

- Écran réservé à l'espace admin (build `admin`, 404 natif ailleurs) et aux rôles admin.
- Après validation, l'entreprise reçoit son lien de définition de mot de passe : le front n'en est
  pas responsable, il ne doit **pas** le simuler ni l'afficher.
- Aucun texte en dur (i18n), mobile-first, accessibilité clavier.

## Critères de validation

- Une entreprise inscrite via la tâche `082` apparaît dans la file, est validée depuis l'écran,
  passe `VALIDEE` en base, et le déclarant reçoit son lien de mot de passe (Mailpit en dev).
- Le refus place l'entreprise en `REFUSE` et la sort de la file par défaut.
- `quality:check` vert, 3 langues.

## Vérifié

Session admin réelle (`admin@demo.showcase-recruiter-webapp-saas.example`) sur la build `admin` : une entreprise inscrite via
l'API apparaît dans la file `EN_REVUE` avec sa provenance (« Autre — Podcast recrutement »), sa
validation depuis l'écran la passe `VALIDEE` et la fait sortir de la file. `quality-check` vert
(lint, format, type-check, conventions, Vitest).

**Piège rencontré** : la base **de dev** ne se met pas à jour toute seule (seule celle de test le
fait). `make dev-schema` **détruit la base** — préférer `doctrine:schema:update --force`, additif,
après avoir lu le `--dump-sql`.

## Reste

- **Renvoyer un magic link** à une entreprise `INCOMPLET` (prévu au cadrage) : l'écran filtre bien
  ce statut mais n'offre pas l'action — la route API publique de magic link prend un e-mail, or la
  fiche entreprise n'expose pas celui du déclarant. À trancher avec la tâche `082`.
- Pas de pagination dans l'écran : la file est courte au démarrage. La route la porte déjà
  (`page`, `perPage`) — à brancher quand elle grossira.

## Hors périmètre

- Le reste du dashboard admin (P3, à cadrer).
- Le push Jarvi déclenché par la validation (côté API).
