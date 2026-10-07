# showcase-recruiter-webapp-saas — Recherche assistée par IA (traduction langage naturel → filtres)

> **But** : cadrer la fonctionnalité de recherche candidat assistée par IA côté espace entreprise —
> principe, architecture, confidentialité, coûts et garde-fous. Ce doc ne porte ni la liste
> canonique des critères (→ [criteres_de_filtrage_profils.md](./criteres_de_filtrage_profils.md)),
> ni le placement UI du panneau de filtres (→ [ui/console_contextuelle.md](./ui/console_contextuelle.md)),
> ni les règles serveur de masquage (→ [api/recherche_et_masquage.md](./api/recherche_et_masquage.md)).
>
> **À ne pas confondre avec la « recherche IA » MCP `advancedSearch`** (Jarvi, task 014, classée V2
> dans [vue_d_ensemble.md](./vue_d_ensemble.md) §périmètre) : ici l'IA **ne cherche pas** — elle
> traduit une phrase de recruteur en filtres structurés, et le moteur de recherche existant
> (déterministe, reproductible) fait le reste. Aucune requête vers Jarvi, aucun candidat envoyé au LLM.

Rédigé le 2026-07-04. Statut : **cadrage validé sur le principe, décisions ouvertes en fin de doc**.

> **Emplacement — décision 2026-07-12 : un POC sur la page Smart Home.**
>
> La feature est **prototypée en POC sur la page Smart Home** pendant les développements
> ([ui/smart_home.md §4.7](./ui/smart_home.md)), **hors cockpit** : elle y porte sa propre **barre de
> saisie libre**. Dans le cockpit, le point d'entrée de la recherche est la combobox **« Poste
> recherché »**, dont l'input fait office de recherche libre **sur les intitulés de poste**
> ([ui/console_contextuelle.md §2](./ui/console_contextuelle.md)).
>
> On éprouve le principe avant de lui donner une place définitive — elle pourra ensuite migrer vers la
> console (en écrivant les critères de la recherche courante) ou rester un banc d'essai.

## 1. Principe — l'IA produit des critères, jamais des résultats

Le recruteur dispose aujourd'hui d'un panneau riche en filtres (cf.
[criteres_de_filtrage_profils.md](./criteres_de_filtrage_profils.md)). La fonctionnalité ajoute une
**barre de saisie libre** au-dessus de ce panneau :

> « une directrice financière dispo sous 3 mois, mobile, qui envisage un retour au Maroc dans l'année »

Un appel LLM unique (structured outputs, schéma JSON strict) traduit cette phrase en un objet
critères conforme au contrat de recherche existant :

```json
{ "function": "finance", "availability": "NOTICE_3M", "geoMobility": true, "returnHorizon": "Y1" }
```

Ces critères sont ensuite **appliqués dans le panneau de filtres, visiblement** : les filtres
suggérés apparaissent cochés/renseignés, le recruteur peut en retirer ou en ajuster un à la main,
et la recherche s'exécute par le moteur habituel.

Séparation des responsabilités :

| Étape | Qui | Données envoyées au LLM |
| --- | --- | --- |
| Comprendre la demande | 1 appel LLM (BFF) | la phrase du recruteur uniquement |
| Filtrer et restituer | moteur de recherche existant | aucune |

Conséquences directes :

- **Pas de boîte noire** : le filtrage reste déterministe et reproductible (même exigence que pour
  les recherches enregistrées, cf. [todo.md](../todo.md)) — l'IA ne peut ni oublier un candidat ni
  en halluciner un.
- **Transparence UX** : le recruteur voit ce que l'IA a compris et garde la main.
- **Découplé du moteur** : le pattern fonctionne que le filtrage s'exécute côté API (Postgres) ou
  côté front (store chargé avec le vivier complet, piste liée à la décision en attente sur la
  task 014 / FTS). Seul le lieu d'application des critères change.

## 2. Parcours utilisateur (V1)

1. Le recruteur saisit sa demande dans la barre IA et valide (Entrée / bouton). **Un appel par
   validation, jamais par frappe.**
2. Latence ~1–2 s ; état de chargement sur la barre.
3. Les filtres traduits se matérialisent dans le panneau, marqués « suggérés par l'IA »
   (étiquette effaçable) ; la recherche se lance immédiatement.
4. Le recruteur affine à la main comme d'habitude — barre IA et panneau écrivent dans le **même
   état de critères**.
5. Si la phrase ne contient aucun critère traduisible, message explicite (« je n'ai pas reconnu de
   critère — précisez le métier, la disponibilité… ») plutôt qu'une recherche vide.

Règle de prompt structurante : **un filtre n'est déduit que s'il est explicite ou fortement
implicite dans la phrase ; sinon `null`**. Sans cette règle le modèle sur-remplit les filtres et le
recruteur obtient zéro résultat sans comprendre pourquoi.

## 3. Architecture technique

```
Barre IA (webapp) ──► POST /api/ai/parse-search   (route Nitro, BFF webapp/server/)
                              │  SDK @anthropic-ai/sdk, clé API côté serveur uniquement
                              ▼
                     { q, sector, function, availability, returnHorizon, geoMobility, … }
                              │
                              ▼
                     état des critères partagé (panneau + barre IA)
                              │
                              ▼
                     moteur de recherche existant (API ou store selon décision 014)
```

- **Emplacement de l'appel** : route serveur Nitro dans le BFF de la webapp (task 022). La clé
  `ANTHROPIC_API_KEY` vit dans l'environnement Docker du service webapp — jamais dans le bundle
  client, jamais committée (invariant « pas de secret en clair »).
- **Structured outputs** : `output_config.format` avec un schéma JSON reflétant
  `CandidateSearchCriteria` (`api/src/Repository/CandidateSearchCriteria.php`) — la réponse est
  garantie conforme au schéma, pas de parsing fragile. Le schéma évoluera avec la liste canonique
  des critères (langues, présence Maroc… cf.
  [criteres_de_filtrage_profils.md](./criteres_de_filtrage_profils.md)).
- **Vocabulaire depuis l'API, pas en dur dans le prompt** : les valeurs des enums sont injectées dans
  le system prompt depuis les constantes exposées par l'API (source = API). Secteur et fonction sont
  des **valeurs libres** : leur vocabulaire se déduit des valeurs réellement présentes dans le vivier
  (facettes), et c'est cette liste qui est injectée — sinon le modèle invente des libellés que la
  donnée ne contient pas.
- **Prompt caching** : le system prompt (règles + vocabulaire, ~1 000 tokens) est identique à
  chaque requête → `cache_control: {type: "ephemeral"}`, facturé ~10 % dès le 2ᵉ appel.
- **`max_tokens` plafonné bas** (~1 024) : la réponse utile fait ~100 tokens.

## 4. Confidentialité & RGPD

Invariant V1 : **aucune donnée candidat ne transite vers le LLM**. L'appel contient la phrase du
recruteur et le vocabulaire des filtres, rien d'autre. Il n'y a donc pas de traitement de données
personnelles candidat par un sous-traitant tiers pour cette fonctionnalité.

Pour l'extension V2 « re-ranking » (§6), la règle serait : projections **minimales et
pseudonymisées** (`{ id, headline, secteur, disponibilité, différenciants }` — jamais nom, e-mail,
téléphone), re-mappées par `id` côté showcase-recruiter-webapp-saas. À instruire au moment venu (registre de traitement,
DPA Anthropic).

À noter : la phrase du recruteur elle-même peut contenir des données personnelles (« quelqu'un
comme Untel ») — l'API Anthropic conserve les requêtes 30 jours (rétention standard). Point à
mentionner dans les CGU entreprise le moment venu.

## 5. Coûts, compte et garde-fous

### Compte

- **Organisation Anthropic dédiée showcase-recruiter-webapp-saas** sur la Console (platform.claude.com) — distinct d'un
  abonnement Claude.ai grand public. Facturation à l'usage par crédits prépayés.
- Un workspace `dev` et un workspace `prod`, chacun avec sa clé API et son **plafond de dépense**.
- Un seul compte pour tout le produit ; les utilisateurs finaux n'ont besoin de rien.

### Ordre de grandeur des coûts (tarifs 2026-07, system prompt caché)

| Modèle | Coût / recherche | 1 000 recherches |
| --- | --- | --- |
| Claude Opus 4.8 (5 $ / 25 $ par M tokens) | ~0,3 c$ | ~3 $ |
| Claude Sonnet 5 (2 $ / 10 $ intro jusqu'au 2026-08-31) | ~0,15 c$ | ~1,5 $ |
| Claude Haiku 4.5 (1 $ / 5 $) | ~0,06 c$ | ~0,6 $ |

Scénario réaliste : 50 recruteurs actifs × 20 recherches/jour ouvré ≈ 22 000 appels/mois →
**entre ~13 $ (Haiku) et ~66 $ (Opus) par mois**. Le re-ranking V2 coûterait ~10× plus par appel
(fiches condensées envoyées), soit quelques centimes par recherche.

### Pourquoi le risque « beaucoup de visiteurs » est structurellement borné

1. La recherche vivier est **derrière l'authentification** (espace entreprise) : le trafic du site
   public ne déclenche jamais d'appel IA. Le volume est proportionnel à la base de recruteurs
   onboardés, pas au trafic web.
2. **Un appel par recherche soumise**, pas par frappe ; le geste répété (ajuster les filtres) reste
   dans le moteur existant, gratuit.
3. Garde-fous techniques empilés :
   - rate limiting par utilisateur dans le BFF (ex. 20 requêtes IA/min) ;
   - `max_tokens` plafonné ;
   - plafond de dépense + alertes sur le workspace Console (coupe-circuit ultime).

Les rate limits Anthropic (quotas API par palier) sont très largement suffisants au volume estimé —
non-sujet à ce stade.

## 6. Extensions V2 (hors périmètre V1)

| Extension | Principe | Condition d'ouverture |
| --- | --- | --- |
| Re-ranking + justification | après filtrage, classer les N résultats et générer une accroche par candidat (« 12 ans en direction financière, préavis 3 mois ») | V1 en production, cadrage RGPD §4 |
| Mots-clés enrichis | l'IA renvoie en plus `keywords[]` matchés contre le texte libre des profils (headline, différenciants) — substitut FTS léger si filtrage côté store | décision task 014 |
| Recherche agentique (tool use) | Claude appelle lui-même la recherche en boucle, élargit/resserre, rend une shortlist commentée | seulement si la V1 montre ses limites |
| Recherche sémantique (embeddings) | matching sur le contenu libre Jarvi via vectorisation | chantier infra dédié, non envisagé |

Le MCP `advancedSearch` Jarvi (task 014, V2 historique) est **probablement rendu caduc** par cette
approche — à confirmer lors de la décision 014.

## 7. Décisions ouvertes

- [ ] **Choix du modèle** : curseur qualité/coût (Opus 4.8 ↔ Haiku 4.5). Tâche simple et très
  cadrée par le schéma → un modèle léger est défendable ; changement = une ligne. À trancher après
  essais sur des phrases réelles de recruteurs.
- [ ] **Périmètre du schéma V1** : quels critères de la liste canonique sont traduisibles dès la
  V1 (le `CandidateSearchCriteria` actuel n'en couvre qu'un sous-ensemble : q, secteur, fonction,
  disponibilité, horizon de retour, mobilité) ; langues et présence Maroc suivent l'évolution du
  moteur.
- [ ] **Articulation avec la décision task 014** (FTS vs filtrage côté front) : sans impact sur le
  pattern, mais détermine où s'appliquent les critères et si l'extension « mots-clés enrichis »
  a du sens.
- [ ] **Titulaire du compte Anthropic** et rattachement de la facturation (qui charge les crédits,
  qui reçoit les alertes de plafond).
- [ ] **Valeurs précises des garde-fous** : rate limit par utilisateur, plafonds de dépense
  dev/prod.
- [ ] **Wording UI** de la barre et de l'étiquette « suggéré par l'IA » (+ i18n fr/en, cohérence
  brand guidelines).
- [ ] **Mention CGU / registre de traitement** pour la rétention 30 jours des requêtes (§4).
