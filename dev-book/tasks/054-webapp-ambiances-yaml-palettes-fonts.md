# TASK 054 — webapp : ambiances (palette + duo de fonts) pilotées par YAML

- **Créée le** : 2026-07-09.
- **Statut** : ⬜ à faire.
- **Type** : Front — Webapp Nuxt (design system, theming).
- **Dépend de** : tâche `044` (retrait `@nuxt/fonts`, `@font-face` manuels).
- **S'articule avec** : tâche `051` (`--ui-primary` dérive de `--accent`) · tâche `046`
  (tests du registre) · pattern génération + gate de la tâche `050`.
- **Réf** : `dev-book/cadrage/wireframes/draft/mockup-espace-entreprise-vue-cockpit-latest.html`
  (mécanisme `data-palette` / `data-fonts`) · `webapp/app/assets/css/themes.css` ·
  `dev-book/cadrage/requirements/brand_guidelines.md`.

## Contexte

Le mockup cockpit a validé un modèle d'« ambiances » à **3 axes orthogonaux** posés sur
`<html>` : `data-theme` (clair/sombre — bases neutres), `data-palette` (couleur signature —
ne pilote que `--accent`/`--on-accent`), `data-fonts` (duo typographique — ne pilote que
`--font-title`/`--font-ui`). Les axes se composent sans explosion combinatoire.

Décisions du 2026-07-09 :

- **Une ambiance = une couleur signature + un duo de fonts.**
- **Source unique YAML** (option B) : ajouter une ambiance = éditer un fichier de config,
  tout le reste est généré.
- **Aucun module de fonts** (ni `@nuxt/fonts`, ni fonts Nuxt UI — `ui.fonts: false` conservé) :
  chargement manuel en `@font-face`, fichiers `.woff2` locaux, `font-display: swap`.

## Objectif

Changer d'ambiance en éditant un seul fichier YAML ; le registre TS, le CSS et les
`@font-face` en dérivent. Bascule SSR-safe, sans flash.

## Architecture

Source : `webapp/ambiances.yaml`

```yaml
palettes:
  sable:
    name: Sable
    accent: '#C8A882'
    onAccent: '#111111'
  cedre:
    name: Cèdre
    accent: '#054a29'
    onAccent: '#ffffff'
    dark:
      accent: '#1a8f56'      # signature foncée → éclaircie en mode sombre

fonts:
  exo:
    name: Exo 2 + Manrope
    title: { family: 'Exo 2', files: [exo2-regular.woff2, exo2-semibold.woff2] }
    ui: { family: Manrope, files: [manrope-regular.woff2, manrope-semibold.woff2] }
```

Trois dérivés :

1. **Registre** `shared/utils/ambiance.ts` : charge le YAML, **valide avec zod** (remplace la
   sécurité du `as const satisfies` perdue en sortant du TS), expose `PALETTES` / `FONT_DUOS`
   typés + `DEFAULT_PALETTE` / `DEFAULT_FONTS`. Le futur sélecteur d'ambiance en dérive.
2. **Génération CSS** : script `quality:generate-ambiances` → `app/assets/css/ambiances.css`
   (marqué *généré — ne pas éditer*), importé par `main.css` :
   - blocs `[data-palette='x']` (+ variante `.dark[data-palette='x']` si `dark:` présent) ;
   - blocs `[data-fonts='y']` ;
   - règles `@font-face` (woff2, `font-display: swap`) pour chaque famille référencée.
   Gate CI anti-dérive (régénère + `git diff` vide), même pattern que la tâche `050`.
3. **Fichiers de polices** : `app/assets/fonts/*.woff2`, versionnés. Ajouter un duo = ajouter
   les `.woff2` + l'entrée YAML. `nuxt.config.ts` n'est **plus concerné** par les fonts.

Application runtime : composable `useAmbiance()` — `useCookie` (palette + fonts, scope domaine
parent comme la session) + `useHead({ htmlAttrs: { 'data-palette', 'data-fonts' } })` : le
serveur rend directement la bonne ambiance, **zéro flash**. Le clair/sombre reste arbitré par
`@nuxtjs/color-mode` (via Nuxt UI), qui n'est pas touché.

`themes.css` conserve les bases neutres (`:root` clair / `.dark` sombre) et le défaut Sable ;
il reste la source des tokens **non liés à l'ambiance**.

## Comment ajouter une ambiance (chemin d'extension)

- **Palette** : une entrée `palettes:` dans le YAML (+ variante `dark:` si la signature est
  trop foncée pour un fond sombre) → régénération. 1 fichier touché.
- **Duo de fonts** : une entrée `fonts:` + les `.woff2` dans `app/assets/fonts/` →
  régénération. 2 emplacements touchés.
- **Mode complet** (ex. contraste élevé) : hors ambiances — bloc `[data-theme='x']` dans
  `themes.css`, porté par color-mode (extension prévue dès la tâche `035`).

## Invariants

- **Une seule source** : ni palette ni duo déclarés à la main dans un CSS ou un TS ;
  `ambiances.css` n'est jamais édité directement.
- Chaque palette définit `onAccent` et garantit un contraste **AA** accent/onAccent et
  accent/fond, dans les deux modes (le générateur peut le vérifier et échouer sinon).
- Aucune requête réseau pour les polices, au build comme au runtime ; `font-display: swap`.
- Les composants ne consomment que les tokens (`--accent`, `--on-accent`, `--font-*`,
  `--ui-*`) — aucun composant ne lit la palette courante pour autre chose que du style.
- SSR-safe : l'ambiance rendue par le serveur = celle du cookie, pas de bascule client visible.
- RTL : les duos doivent couvrir les glyphes nécessaires ou déclarer un fallback pour `/ar`.

## Points ouverts (à trancher pendant la tâche)

- **Duos embarqués en prod** : exploration à figer (on garde plusieurs duos le temps de
  choisir, puis on réduit) ou feature produit durable ? Chaque duo pèse ses `.woff2` pour
  tous les visiteurs.
- Palette Olive du mockup : `#b0b776` quasi identique au token statut « Disponible »
  `#b2b760` — à arbitrer si retenue.
- Le sélecteur d'ambiance UI (menu topbar du mockup) : ici ou dans la tâche `051`.

## Critères d'acceptation

- [ ] Ajouter une palette de test dans le YAML + régénérer suffit à la voir appliquée via
      `data-palette` (aucun autre fichier touché).
- [ ] `useAmbiance()` : le choix persiste (cookie), le HTML serveur porte les bons attributs
      (vérifié sur la source, JS désactivé), aucun flash au chargement.
- [ ] Gate anti-dérive : YAML modifié sans régénération → CI rouge.
- [ ] Zod rejette un YAML invalide (hex malformé, duo sans fichiers) avec une erreur claire.
- [ ] Aucune requête `fonts.googleapis.com` / `gstatic` / CDN au build et au runtime.
- [ ] Contraste AA vérifié pour chaque palette livrée, clair et sombre.
- [ ] Tests Vitest du registre (`shared/utils/ambiance.vitest.ts`) verts.
- [ ] `bun run quality:check` vert.

## Hors périmètre

- Le mapping tokens → `--ui-*` des composants Nuxt UI → tâche `051`.
- Ambiances chargées au runtime sans rebuild (impossible proprement : les polices doivent
  être embarquées au build).
- Préférences d'ambiance persistées côté serveur (profil utilisateur) — V2 éventuelle.
