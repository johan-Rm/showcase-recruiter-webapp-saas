# base/app/assets/

Assets traités par le pipeline de build.

## Rôle et responsabilités

**Rôle Nuxt :** `assets/` héberge les fichiers que Vite transforme (styles, fontes, images) avec
bundling, optimisation et fingerprinting à la compilation. Ce qui doit être servi tel quel, sans
transformation, relève de `public/`. Réf. : [doc Nuxt — `assets/`](https://nuxt.com/docs/4.x/directory-structure/app/assets).

**Rôle attendu :** identité visuelle et ressources de présentation globales — **point d'entrée CSS
unique** (`main.css`), thème (`themes.css`), transitions (`transitions.css`), déclarations de fontes
(`fonts.css`). Chargement maîtrisé, sans dépendance à un CDN tiers (fontes auto-hébergées, icônes
Lucide bundlées en local).

## Conventions techniques

Aucune règle propre à ce dossier. Les règles transverses de la couche `app/` s'appliquent (voir
[../README.md](../README.md)).
