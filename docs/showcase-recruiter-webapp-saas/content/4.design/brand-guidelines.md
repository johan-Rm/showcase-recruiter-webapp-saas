# Charte de marque — Shortlist

- Rôle : définir l’identité visuelle du produit de démonstration Shortlist.
- Related : [design-system.md](./design-system.md), [index.md](./index.md)

Le produit porte le nom **Shortlist** dans l’interface et les messages. Le nom technique du dépôt,
les chemins, les domaines d’exemple et les variables restent `showcase-recruiter-webapp-saas`.

## Direction visuelle

Une application de recrutement sobre : surfaces blanches ou ardoise, actions bleues, informations
hiérarchisées par la typographie et l’espacement. La marque met en avant la sélection, la clarté
et le passage à l’action.

## Logo

Le pictogramme représente une liste structurée et une coche de sélection dans un cadre arrondi.
Il utilise des traits géométriques de deux unités dans une boîte de 32 × 32. Il reste identifiable
seul dans une navigation compacte ou une favicon.

Le mot-symbole **Shortlist** est composé en Geist, graisse 600. Le logo complet aligne le signe
et le nom avec un espace de 12 unités. `LayoutLogo` assemble `LayoutLogomark` et `LayoutWordmark` :
le signe possède une seule source dans les composants. Le nom vient de la clé i18n `app.name`.

Le logo prend la couleur du texte de son contexte. La favicon prend le bleu de l’accent, adapté
au thème système. Aucun dégradé, aucune ombre, aucune déformation du signe ou du mot-symbole.
Les e-mails affichent le nom en texte pour rester lisibles lorsque les images sont bloquées.

## Couleurs

Deux échelles de onze paliers, déclarées dans `palette.css` :

| Palier | Ardoise (`slate`) | Bleu (`blue`) |
| ------ | ----------------- | ------------- |
| 50     | `#f8fafc`         | `#eff6ff`     |
| 100    | `#f1f5f9`         | `#dbeafe`     |
| 200    | `#e2e8f0`         | `#bfdbfe`     |
| 300    | `#cbd5e1`         | `#93c5fd`     |
| 400    | `#94a3b8`         | `#60a5fa`     |
| 500    | `#64748b`         | `#3b82f6`     |
| 600    | `#475569`         | `#2563eb`     |
| 700    | `#334155`         | `#1d4ed8`     |
| 800    | `#1e293b`         | `#1e40af`     |
| 900    | `#0f172a`         | `#1e3a8a`     |
| 950    | `#020617`         | `#172554`     |

L’ardoise porte les surfaces et le texte ; le bleu porte les actions et la sélection. Les composants
consomment les tokens sémantiques de `themes.css`, via leurs utilitaires Tailwind.

### Tokens des deux thèmes

| Token            | Rôle                      | Clair              | Sombre             |
| ---------------- | ------------------------- | ------------------ | ------------------ |
| `--bg`           | Fond de page              | `slate-50`         | `slate-950`        |
| `--surface`      | Cartes et panneaux        | blanc              | `slate-900`        |
| `--surface-soft` | Fonds secondaires         | `slate-100`        | `slate-800`        |
| `--dropdown-bg`  | Menus et champs           | blanc              | `slate-900`        |
| `--text`         | Texte principal           | `slate-900`        | `slate-50`         |
| `--muted`        | Texte secondaire          | `slate-600`        | `slate-400`        |
| `--accent`       | Actions et sélection      | `blue-700`         | `blue-300`         |
| `--on-accent`    | Texte sur bouton primaire | blanc              | `slate-950`        |
| `--line`         | Filet discret             | texte à 14 %       | texte à 16 %       |
| `--accent-soft`  | Sélection douce           | accent à 10 %      | accent à 14 %      |
| `--sl-hover`     | Survol                    | accent à 6 %       | accent à 8 %       |
| `--sl-press`     | Appui                     | accent à 12 %      | accent à 14 %      |
| `--scrim`        | Voile modal               | ardoise 950 à 55 % | ardoise 950 à 65 % |
| `--alert`        | Erreur                    | `#b91c1c`          | `#fca5a5`          |
| `--tint-fill`    | Fond des compteurs        | 18 %               | 22 %               |

Les tokens dérivés et le pont sémantique Nuxt UI sont redéclarés dans `.dark` pour fonctionner aussi lorsque le thème sombre
est un îlot dans une page claire.

### États et verdicts

Les couleurs d’état accompagnent toujours un libellé et une icône. Elles sont déclarées dans
`themes.css` et ne colorent pas les lignes de données.

| Token               | Valeur         | Usage                                                      |
| ------------------- | -------------- | ---------------------------------------------------------- |
| `--state-met`       | `#8b5cf6`      | Profil rencontré                                           |
| `--state-available` | `#10b981`      | Disponible                                                 |
| `--state-new`       | `#0ea5e9`      | Nouveau                                                    |
| `--state-morocco`   | `#f59e0b`      | Présence au Maroc dans le scénario métier de démonstration |
| `--accent`          | Selon le thème | Retenu par le recruteur                                    |
| `--muted`           | Selon le thème | Vu                                                         |

Un verdict concerne une recherche. `--verdict-met` vaut `#047857` en clair et `#6ee7b7` en sombre ;
`--verdict-missed` vaut `#b45309` et `#fcd34d`. La visionneuse conserve l’accent pour les critères
satisfaits et les neutres pour les critères manquants.

Les badges d’état gardent leur recette : fond teinté à 20 %, texte mêlé à 62 % avec `--text`,
icône dans la teinte d’état. La couleur ne porte jamais seule le sens.

## Typographie

**Geist** est la famille commune aux titres, au texte courant et à la navigation. Les graisses
400, 500 et 600 sont auto-hébergées en WOFF2, avec des sous-ensembles latin et latin étendu.
Arial et `sans-serif` servent de repli, notamment pour les écritures non couvertes.

Les titres utilisent la graisse 600 et un interlettrage de −0,025 em. Les deux tokens `--font-ui`
et `--font-title` restent distincts pour permettre une évolution future sans toucher les écrans.
Les e-mails demandent Geist puis Arial et Helvetica, selon les polices disponibles sur le client.

L’échelle fluide de `sizing.css` conserve les niveaux `label`, `caption`, `small`, `body`, `h3`,
`h2`, `h1` et `display`. Le corps de formulaire reste à au moins 16 px. Les libellés arabes
conservent leur correctif de casse et d’interlettrage.

## Espacement et gabarits

Le rythme commun conserve `element`, `block`, `pane`, `section` et `gutter`, ainsi que les cellules
de tableau et les hauteurs de bande. Les pages de lecture utilisent une largeur contrainte ; les
outils de recherche utilisent le gabarit `shell`. Les tableaux gardent une densité permettant
la comparaison de plusieurs profils.

## Formes et composants

Les rayons dépendent de `--ui-radius: 0.375rem` dans `nuxt-ui.css` :

- Champs, boutons et badges `rounded-lg` : **12 px**.
- Cartes `UiCard` en `rounded-xl` : **18 px**, avec un filet `border-line`.
- Pictogramme de marque : rayon de 7 unités dans son dessin de 32 unités.
- Cartes et boutons des e-mails : **12 px**, adapté au support de messagerie.

Les cartes restent planes. L’ombre `--overlay-shadow` est réservée aux menus et aux surcouches.
Le blanc des cartes sur le fond ardoise clair et le filet discret rendent les groupes lisibles.
En sombre, les surfaces deviennent progressivement plus claires à mesure qu’elles sont élevées.

Le bouton primaire utilise `--accent` et `--on-accent`. Le secondaire utilise `--surface-soft`
et `--accent`. Le focus clavier affiche un contour accent de deux pixels, décollé du contrôle.
Les champs en ligne conservent leur soulignement discret ; les champs de formulaire restent encadrés.

## Mouvement

Les durées et courbes restent dans `transitions.css`. Le survol modifie la teinte ; l’appui d’un
bouton déplace celui-ci d’un pixel. Aucun déplacement de carte au survol. La préférence
`prefers-reduced-motion` est respectée.

## Contraste et accessibilité

Rapports calculés à partir de la luminance relative sRGB des valeurs du kit :

| Couple                                   | Rapport |
| ---------------------------------------- | ------- |
| Accent clair / fond de page              | 6,41:1  |
| Texte blanc / bouton primaire clair      | 6,70:1  |
| Texte secondaire clair / carte blanche   | 7,58:1  |
| Accent sombre / surface sombre           | 9,90:1  |
| Texte sombre / bouton primaire sombre    | 11,19:1 |
| Texte secondaire sombre / surface sombre | 6,96:1  |
| Texte secondaire sombre / surface douce  | 5,71:1  |

Ces couples dépassent 4,5:1 pour le texte courant. Les mélanges, les photos et les états
interactifs demandent une vérification dans leur contexte ; ces mesures ne certifient pas tous les écrans.
Le sens de lecture RTL, le focus clavier, les libellés et les icônes font partie du système.
