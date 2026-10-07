# TASK 087 — webapp : recherche d'adresse propre (Mapbox) via composant réutilisable + proxy BFF

- **Créée le** : 2026-07-15.
- **Statut** : 📋 à faire.
- **Type** : Front — Webapp Nuxt (composant du kit `ui/` + route BFF de géocodage).
- **Dépend de** : tâches `070` (kit `ui/`) · `037` (socle auth/BFF, pattern « le secret ne quitte
  jamais le serveur »).
- **Premier consommateur** : tâche `082` (tunnel d'inscription entreprise — champs `pays` / `ville`
  aujourd'hui en texte libre, cf. `RegistrationCompanyForm`).
- **S'articule avec** : tâche `085` (champs d'inscription entreprise — `city` / `country`) · tâche
  `017` (présence Maroc, `PresencePeriod.city` — futur consommateur).
- **Réf code** : `webapp/spaces/entreprise/app/components/registration/CompanyForm.vue`
  (les `<input>` `country` / `city` à remplacer) · `webapp/base/server/utils/api.ts` (pattern proxy
  BFF `ofetch`) · `webapp/nuxt.config.ts` (`runtimeConfig`).
- **Réf cadrage** : `dev-book/cadrage/entreprise/api/inscription.md` (§ Informations demandées).

## Contexte

La saisie de localisation est aujourd'hui du **texte libre** : dans `CompanyForm`, `country` est un
champ libre et `city` un `<input autocomplete="address-level2">`. Résultat attendu sans ce travail :
orthographes divergentes, villes non normalisées, aucune donnée exploitable pour un futur filtre
géographique. Le besoin est une **recherche d'adresse propre** — l'utilisateur tape, choisit dans une
liste de suggestions réelles, et le formulaire reçoit une localisation normalisée.

Le fournisseur retenu est **Mapbox** (décision produit, non re-tranchée ici).

## Objectif

Livrer un composant de recherche d'adresse **réutilisable**, accessible et SSR-safe, adossé à un
**proxy BFF** qui porte le token Mapbox côté serveur. Le brancher d'abord sur le formulaire
d'inscription entreprise (pays / ville), en laissant la porte ouverte aux autres consommateurs
(localisation candidat, présence Maroc).

## Décisions d'architecture

- **Proxy BFF, jamais de token dans le navigateur.** Le token Mapbox vit en `runtimeConfig`
  server-only (`MAPBOX_TOKEN`), consommé par une route Nitro (`base/server/api/geocoding/…`) qui
  appelle Mapbox en `ofetch`. Cohérent avec l'invariant « le JWT ne quitte jamais le serveur »
  (tâche `082`) : maîtrise de la **facturation**, du **rate-limit** et des paramètres, et aucun
  token exposé/scrappable dans le bundle.
- **Pas de SDK `mapbox-gl`.** On n'affiche pas de carte : un combobox suffit. Appel **REST**
  (Mapbox Search Box / Geocoding) depuis le BFF → zéro dépendance JS lourde ajoutée au front
  (vigilance poids JS + « pas de dépendance sans justification »).
- **Session tokens Mapbox** pour grouper la facturation d'une session de frappe (suggest → retrieve),
  générés côté BFF et transmis au front le temps de la saisie.
- **Composant dans le kit `ui/`** (layer `base`) : `UiAddressInput` (nom à confirmer), pour être
  partageable entre espaces.

## Protection anti-abus

La route BFF appelle Mapbox **facturé à la requête** : sans garde, c'est un proxy de géocodage
gratuit ouvert sur le budget showcase-recruiter-webapp-saas. Le `debounce` / la longueur minimale sont de l'UX, **pas** une
protection — un bot les ignore. Contrôles retenus, du plus structurant au backstop :

1. **Route derrière la session magic-link** (levier principal). La recherche d'adresse vit sur
   `inscription/entreprise.vue`, atteint **après consommation du magic link** : le déclarant a une
   session. La route BFF de géocodage **exige cette session côté serveur** (`requireUserSession`,
   comme `organizations.post.ts`) — le middleware de page ne protège que la navigation Nuxt, pas les
   routes Nitro. Résultat : appeler Mapbox exige de posséder une boîte mail **et** d'avoir consommé
   un lien. On passe d'« endpoint ouvert » à « session prouvée ».
2. **Rate-limit** par session **et** par IP sur la route (une session valide mais abusive reste
   plafonnée). En mémoire Nitro suffit au MVP.
3. **Backstop facturation Mapbox** : alerte + plafond d'usage sur le token, hors code — si tout le
   reste cède, la facture est bornée.
4. **Contrôle `Origin` / same-site** (filtre faible, complémentaire).

Pas de CAPTCHA / Turnstile en V1 : la seule surface consommatrice est **post-magic-link**, pas
anonyme. À reconsidérer **uniquement** si la recherche d'adresse arrive un jour sur une surface
publique non authentifiée (vitrine).

## Périmètre

- **Route BFF** (`base/server/api/geocoding/`) : `suggest` (query → suggestions) et `retrieve`
  (sélection → localisation détaillée). Token server-only, **garde de session (`requireUserSession`)**
  + rate-limit (voir Protection anti-abus), `debounce`/garde de longueur minimale, paramètres
  `language` (FR/EN/AR) et `country` (biais/limitation optionnels — voir Règles).
- **Composant `UiAddressInput`** : combobox accessible (pattern ARIA `combobox` + `listbox`),
  navigation clavier (↑/↓/Entrée/Échap), état de chargement, « aucun résultat », effacement.
  `v-model` renvoyant une **localisation normalisée** (voir ci-dessous), props `language`,
  `countries`, `placeholder`, `disabled`.
- **Forme normalisée renvoyée** (V1, volontairement sobre) : libellé affiché + composants
  (`city`, `region?`, `countryCode` ISO-3166-1 alpha-2) + `coordinates?` (`lng`/`lat`). Pas de
  nouveau champ base côté API pour l'entreprise : on **alimente les champs existants**
  (`city`, `country`) de `RegistrationCompanyForm`.
- **Branchement `CompanyForm`** : remplacer les deux `<input>` `country` / `city` par le composant,
  en conservant le contrat d'entrée de l'API (tâche `085` : `city` valeur libre bornée, `country`).
- **i18n FR / EN / AR** : aucun texte en dur (label, placeholder, « aucun résultat », erreurs),
  rendu **RTL** correct en arabe.

## Invariants / Règles

- **`MAPBOX_TOKEN` server-only**, jamais dans `runtimeConfig.public`, jamais committé (`.env`
  d'exemple documenté sans valeur). Pas de secret en clair.
- **SSR-safe** : aucune requête Mapbox au rendu serveur ; la recherche est déclenchée par
  l'interaction (côté client, via le BFF).
- **Accessibilité** : combobox ARIA, focus visible, tout au clavier, labels reliés — contrainte
  transverse UI.
- **Biais pays** : l'entreprise pouvant être internationale, **pas de restriction dure** par défaut ;
  la prop `countries` permet de restreindre (ex. `MA` pour la présence Maroc, tâche `017`).
- **Sobriété d'appels** : `debounce` + longueur minimale + session tokens → on ne tape pas Mapbox à
  chaque frappe (coût facturé à la requête).
- **Dégradé propre** : si Mapbox est indisponible, la saisie manuelle reste possible (le champ ne
  doit pas devenir un cul-de-sac).

## Critères de validation

- [ ] Dans le tunnel d'inscription entreprise, saisir une ville propose des suggestions réelles ;
      la sélection normalise `city` / `country` et l'inscription aboutit (`Organization` en base,
      cf. tâche `082`).
- [ ] Le token Mapbox n'apparaît **nulle part** dans le bundle client ni les réponses réseau du
      navigateur (vérifié) ; tout passe par le BFF.
- [ ] La route de géocodage **sans session valide → 401** ; avec session, un flot d'appels au-delà
      du seuil → réponse de rate-limit (vérifié).
- [ ] Combobox pilotable entièrement au clavier ; rendu RTL correct en arabe ; 3 langues rendues.
- [ ] Mapbox injoignable → message exploitable, saisie manuelle possible, pas de page morte.
- [ ] `quality:check` (webapp) vert.

## Hors périmètre

- **Affichage cartographique** (pin, carte interactive) : pas de `mapbox-gl` — non nécessaire au MVP.
- **Stockage des coordonnées côté API** (lat/lng persistées, filtre géographique serveur) → tâche
  API dédiée à créer si le besoin se confirme ; V1 reste front + champs existants.
- **Branchement candidat** (localisation actuelle, `PresencePeriod.city` — tâche `017`) : le
  composant est conçu pour, mais le câblage suit quand ces écrans existent côté webapp.
- **Choix du fournisseur** : Mapbox est acté, pas de comparatif ici.

## Docs à tenir à jour (même scope)

- `.env` d'exemple + doc de config webapp : ajout de `MAPBOX_TOKEN` (server-only) et de la route BFF
  de géocodage.
- `webapp/base/app/components/ui/README.md` (ou équivalent du kit `ui/`) : entrée `UiAddressInput`.
