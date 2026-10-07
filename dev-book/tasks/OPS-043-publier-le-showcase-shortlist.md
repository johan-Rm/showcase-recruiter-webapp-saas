# TASK OPS-043 — publier le showcase Shortlist

- **Créée le** : 2026-10-07.
- **Session** : non disponible (Codex).
- **Statut** : ✅ fait le 2026-10-07 — copie autonome renommée et identité Shortlist appliquée, prête pour un premier commit public sans historique source.
- **Type** : feat — créer une démonstration publique indépendante du projet source.
- **Tests** : tests existants adaptés aux noms techniques ; test du rendu des emails adapté à Shortlist ; tests de parité des locales et vérifications de compilation.

## Contexte et décisions

Carte rédigée après l’implémentation, à la demande de commit et de publication. Le travail couvre
la conversion du projet copié en showcase et son identité visuelle. Le nom du dépôt reste
`showcase-recruiter-webapp-saas`, le produit utilise Shortlist.

Le dépôt GitHub cible est public et vide. Le premier commit public est un instantané sans parent :
l’historique source et ses anciens accès de recette ne sont pas publiés. Les branches historiques
restent locales. Aucun push forcé ni modification du dépôt source.

## Livré

- Références textuelles, chemins, variables, cookies, Docker et documentation renommés.
- Domaines d’exemple, identifiants d’intégration synthétiques et comptes de démonstration.
- Palette bleu/ardoise, thèmes clair et sombre, typographie Geist et rayons communs.
- Logo géométrique, favicons, emails et traductions français/anglais/arabe harmonisés.
- Charte et design system actualisés ; galerie UX Kit et aperçu HTML de marque.
- Chemin CSS absolu pour la compilation Nuxt depuis son cache.

## Vérification

- 199 tests unitaires API : 1 039 assertions, succès.
- 628 tests frontend : 34 fichiers, succès.
- 5 tests de rendu des emails : 31 assertions, succès.
- TypeScript : les quatre espaces validés.
- Conventions frontend : 19 règles, aucune violation.
- ESLint : aucune erreur, 23 avertissements existants.
- Build Nuxt public : succès ; palette, accent et police Geist présents dans le CSS produit.
- Syntaxe PHP, conteneurs Symfony dev/test, YAML, Twig et compositions Docker vérifiés.
- Recherche de l’ancienne identité, y compris arabe et HTML fragmenté : aucune occurrence dans les fichiers destinés au dépôt.
- Fichiers `.env` locaux ignorés ; clé privée OIDC limitée à la fixture de test synthétique.

## Définition de terminé

- [x] Copie autonome et nom technique cohérent.
- [x] Identité visuelle appliquée et documentée.
- [x] Tests impactés et compilation validés.
- [x] Instantané public délimité, sans fichiers étrangers ni historique source.

## Hors périmètre

Les carrousels AIOD, `dev-book/demand/` et `lequipe-rss.xml` préexistaient et restent non versionnés.
Aucune licence open source ni mise en production n’est ajoutée par cette livraison.
