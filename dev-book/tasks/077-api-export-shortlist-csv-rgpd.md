# TASK 077 — API : export CSV de la shortlist (endpoint tracé + accusé RGPD)

- **Créée le** : 2026-07-12.
- **Statut** : ✅ faite (2026-07-14).
- **Type** : nouvelle capacité / conformité.
- **Dépend de** : tâche `072` (recherche), `073` (shortlist), `005` (dévoilement).
- **Réf ClickUp** : _à créer_.
- **Réf cadrage** :
  - `dev-book/cadrage/entreprise/ui/vue_la_shortlist.md` §3
  - `dev-book/cadrage/entreprise/les_2_outils.md` §6bis

## Contexte

**L'export est la sortie de l'entonnoir** — la finalité du produit : on emporte la shortlist pour
contacter les candidats hors plateforme.

Deux problèmes dans l'état actuel :
1. **Le mockup génère le CSV côté client.** Inacceptable en prod : de la donnée personnelle dévoilée
   sort de la plateforme **sans passer par le serveur, donc sans trace**.
2. **L'accusé RGPD est exigé en V1** par le cadrage — et **absent du mockup**.

## Objectif

Un export **servi et tracé par l'API**, précédé d'un **accusé RGPD**, et **gratuit** (la donnée a été
payée au dévoilement).

## Périmètre

- `POST /v1/searches/{id}/shortlist/export` → CSV.
- **Colonnes** : `Nom · Poste · E-mail · Téléphone · Ville · Pays · Présence Maroc`.
- **Format** : séparateur `;` + **BOM UTF-8** (ouverture directe dans Excel FR).
- **Nom de fichier** : `shortlist-<nom-de-la-recherche>.csv`.
- **Accusé RGPD** : l'export **exige un accusé explicite** du recruteur (flag horodaté dans la requête,
  ou endpoint de consentement préalable — **forme à trancher avec le juridique**). Rappel affiché :
  de la **donnée personnelle sort de la plateforme**.
- **Traçabilité** : journaliser **qui** a exporté **quoi** (recherche, nombre de profils, candidats)
  et **quand** → alimente aussi l'activité d'équipe.
- **Aucun crédit consommé** — à **dire explicitement** dans la réponse / l'UI.
- **Autorisation** : réservée à l'**auteur** de la recherche (une recherche partagée est en lecture
  seule — un destinataire **duplique** s'il veut exporter la sienne).

## Invariants

- **`shortlist ⊆ dévoilés`** est garanti **à la source** (task `073`) : l'export **n'a pas à filtrer**
  `∩ dévoilés`. Si un profil non dévoilé s'y trouvait, c'est un **bug d'invariant**, pas un cas à
  gérer silencieusement — l'API doit **échouer bruyamment**.
- Aucune donnée masquée ne sort : par construction, tous les shortlistés sont dévoilés.
- L'export **ne débite jamais** de crédit.

## Critères d'acceptation

- [ ] L'export renvoie un CSV lisible dans Excel FR (BOM, `;`).
- [ ] L'export **sans accusé RGPD** est **refusé**.
- [ ] L'export est **journalisé** (auteur, recherche, profils, horodatage).
- [ ] Aucun crédit n'est débité.
- [ ] Un non-auteur ne peut pas exporter la shortlist d'une recherche partagée (403).
- [ ] Une shortlist contenant un profil non dévoilé fait **échouer** l'export (violation d'invariant).
- [ ] `make api-quality` vert.

## Décisions de mise en œuvre

- **Accusé RGPD = flag obligatoire dans la requête d'export** (`gdprAcknowledged: true`), et non un
  consentement préalable posé une fois. L'accusé reste ainsi **indissociable de l'acte d'export** :
  on peut prouver qui a reconnu quoi, pour cet export-là. Absent ou faux → **422**.
- **L'horodatage de l'accusé est posé par le SERVEUR**, pas fourni par le client : une date envoyée
  par le front ne prouverait rien.
- **Journal** : entité `ShortlistExport` (recherche, auteur, nombre de profils, **uuids des candidats
  sortis**, date d'accusé) + une ligne de log applicatif. Les uuids sont figés à l'export — la
  shortlist, elle, continue de bouger.
- **Gratuité rendue explicite dans la réponse** via l'en-tête `X-Credits-Charged: 0`, pas seulement
  dans l'UI.
- L'opération est un **State Processor** rendant une `Response` brute (API Platform la laisse
  passer), et non un contrôleur : la frontière deptrac `ApiResource → [State, Enum]` interdit à une
  ressource de référencer un contrôleur.

## Hors périmètre

- Dédoublonnage, nommer / dater l'export, formats alternatifs (reportés).
- Messagerie / contact intégré (hors périmètre produit).
