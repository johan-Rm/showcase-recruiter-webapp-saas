# TASK API-001 — API : les nomenclatures fermées entrent dans `/v1/config`

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — endpoint appelé en réel, contrat et types régénérés.
- **Type** : API — surface de contrat.
- **Réf** : task 050 (types générés depuis l'OpenAPI) · task ENT-001 (le palier métadonnées qui
  consommera cette réponse) · `api-conventions.md` (les constantes viennent de l'API).

## Contexte

Le front affiche quatre listes de choix fermées — niveau d'études, disponibilité, horizon de retour,
niveau de langue — et les **recopie à la main** : `EDUCATION_LEVELS` dans `<SearchFiltersPrimary>`,
`AVAILABILITY_VALUES` / `RETURN_HORIZON_VALUES` / `PROFICIENCY_LEVELS` dans `shared/types/ai-search.ts`.
Les deux fichiers portent d'ailleurs le commentaire d'usage : « à réconcilier avec les types générés
depuis openapi.json ».

En cherchant à les remplacer, on découvre **pourquoi** personne ne l'avait fait : il n'y avait rien à
consommer. Une enum n'atteint l'OpenAPI que si un **Input** la type ; les Outputs, eux, les servent
en `string`. Seul `DifferentiatorsInput` en exposait deux au passage. `BAC_PLUS_2` et
`NATIVE_OR_BILINGUAL` n'apparaissaient **nulle part** dans `webapp/shared/types/api.ts`.

Le front ne recopiait donc pas par facilité : le contrat ne disait pas ces valeurs.

## Décisions

### Un sous-objet `referentials`, pas cinq champs à plat

`ConfigReferentialsOutput` regroupe les quatre listes. Les conventions veulent des réponses plates
pour les données, mais un référentiel n'est pas une donnée de la ressource : c'est un bloc qui grandit
à chaque nomenclature ajoutée, et l'aplatir noierait `revealCost` et `perPage` au milieu.

### Des valeurs, pas des libellés

Les libellés restent dans l'i18n du front (`search.education.*`). La réponse est donc **indépendante
de la locale** — identique pour tout le monde, et cachable. C'est aussi ce que le front fait déjà :
il traduit des codes, il ne reçoit pas des étiquettes.

### L'ordre est celui des `case`, et il est significatif

`BAC → DOCTORAT`, `ELEMENTARY → NATIVE_OR_BILINGUAL` : ce sont des rangs. Le front affiche la liste
telle quelle et ne retrie pas ; trier côté serveur casserait ce que l'ordre veut dire.

### Quatre nomenclatures, pas toutes celles qui existent

Seulement celles que le front recopie aujourd'hui. `SeniorityLevel`, `Sector` et les autres entreront
quand un écran les demandera : une liste servie que personne ne lit est une liste que personne ne
vérifie.

## Ce que ça ne fait pas

L'expérience professionnelle n'a **pas** de nomenclature côté API — les plages `0_2`, `3_5`… du
formulaire sont une invention du front, en attente du contrat de recherche (cf. task 164). Ce champ
reste donc en dur, et c'est volontaire.

## Vérification

- `GET /v1/config` appelé en réel → **200**, les quatre listes, valeurs **identiques** à celles que
  le front porte en dur.
- `api/openapi.json` régénéré : schéma `ConfigReferentialsOutput` présent, et l'endpoint de
  dévoilement en lot de la task 163 **préservé** (l'export part du même arbre, il ne l'efface pas).
- `webapp/shared/types/api.ts` régénéré dans le **même commit** — la gate `quality-contracts-check`
  compare les deux, ils ne doivent jamais voyager séparément.
- Piège reconfirmé : la **première** passe d'export a raté la propriété neuve, cache de métadonnées
  API Platform en cause. `rm -rf var/cache/<env>` avant tout export.

## Reste à faire (hors périmètre de cette tâche)

- **Le front recopie toujours ses constantes** : elles ne tombent qu'avec le store `referentials` de
  l'espace entreprise, qui lira cette réponse au palier métadonnées (task ENT-001). Tant que ce store
  n'existe pas, la source et le doublon coexistent.
- **`ai-search.ts`** sert aussi le schéma JSON envoyé au modèle depuis le BFF : ses constantes vivent
  côté serveur Nitro, hors de portée d'un store client. Leur remplacement demandera de lire la config
  au niveau du BFF, ce qui n'est pas le même geste.
