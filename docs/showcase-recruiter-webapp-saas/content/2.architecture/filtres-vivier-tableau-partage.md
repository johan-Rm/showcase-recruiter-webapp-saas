# showcase-recruiter-webapp-saas — Les filtres de recherche du vivier, en clair

> Version simplifiée pour partage (2026-07-09). D'où viennent les infos : nous avons comparé un
> vrai profil et l'ensemble du vivier importé (~1 700 candidats) entre la source (Jarvi/LinkedIn)
> et showcase-recruiter-webapp-saas. Version technique : [inventaire-criteres-3-couches.md](./inventaire-criteres-3-couches.md).

**Comment lire** :
« **La donnée existe ?** » = cette information est-elle réellement remplie dans le vivier actuel
(🟢 quasi tous les profils · 🟠 une partie · 🔴 presque personne — se remplira quand les candidats
complèteront leur espace showcase-recruiter-webapp-saas).
« **Le filtre est prêt ?** » = le moteur de recherche sait-il déjà filtrer dessus
(✅ oui · ⚠️ en partie · 🔧 à construire).

| Famille | Filtre | Exemple d'usage | La donnée existe ? | Le filtre est prêt ? |
| --- | --- | --- | --- | --- |
| Parcours (LinkedIn) | Mots-clés libres | « ingénieur maintenance » | 🟢 | ⚠️ ne cherche que dans le titre du profil |
| Parcours (LinkedIn) | Compétences | GMAO, Python… | 🟢 9 profils sur 10 | 🔧 |
| Parcours (LinkedIn) | Poste occupé | « Responsable Maintenance » | 🟢 | 🔧 |
| Parcours (LinkedIn) | Entreprises fréquentées | TotalEnergies, Sanofi… | 🟢 | 🔧 |
| Parcours (LinkedIn) | Années d'expérience | au moins 5 ans | 🟢 (à calculer) | 🔧 |
| Parcours (LinkedIn) | Diplômes / formations | Arts et Métiers, master… | 🟢 | 🔧 (plus tard) |
| Parcours (LinkedIn) | Langues parlées (+ niveau) | anglais courant, arabe… | 🟢 9 profils sur 10 | 🔧 |
| Parcours (LinkedIn) | CV joint au profil | télécharger le CV | 🔴 3 profils sur 100 | 🔧 |
| Localisation | Pays de résidence | vit en France, en Belgique… | 🟢 | ✅ |
| Localisation | Ville de résidence | vit à Lyon, à Paris… | 🟠 7 profils sur 10 | 🔧 |
| Projet Maroc ⭐ | Horizon de retour | rentre d'ici 3 mois, 6 mois, 1 an | 🔴 | ✅ |
| Projet Maroc ⭐ | Séjours prévus au Maroc | à Casablanca dans les 30 jours | 🔴 | ✅ |
| Projet Maroc ⭐ | Disponibilité (préavis) | disponible immédiatement | 🔴 | ✅ |
| Projet Maroc ⭐ | Mobilité géographique | prêt à bouger dans tout le Maroc | 🔴 | ✅ |
| Projet Maroc ⭐ | Pied-à-terre au Maroc | a déjà un logement sur place | 🔴 | ✅ |
| Projet Maroc ⭐ | Prétentions salariales | budget maximum | 🔴 | 🔧 (règle euros/dirhams à décider) |
| Projet Maroc ⭐ | Secteur / fonction recherchés | vise la finance, la logistique… | 🔴 | ✅ |
| Suivi équipe | Profil vérifié showcase-recruiter-webapp-saas | a passé l'entretien avec notre équipe | 🔴 démarre à peine | ✅ |
| Suivi équipe | Nouveaux profils | arrivés cette semaine | 🟢 | 🔧 |
| Suivi équipe | Mes favoris | les profils mis de côté | selon l'usage | 🔧 |
| Suivi équipe | Déjà dévoilés / pas encore | coordonnées déjà obtenues | selon l'usage | 🔧 |
| Suivi équipe | Assignés à un collègue | suivis par Sophie, par Julien… | selon l'usage | 🔧 (plus tard) |

## À retenir

1. **Le paradoxe actuel** : les filtres déjà construits (projet Maroc ⭐) portent sur des données
   encore vides, et les données bien remplies (compétences, expériences, langues) n'ont pas
   encore de filtre. La priorité proposée : construire d'abord les filtres « Parcours ».
2. **Un bug important a été trouvé et sera corrigé** : aujourd'hui, dévoiler un profil ne donne
   pas l'email / téléphone / LinkedIn du candidat alors que nous avons l'information (tâche 060).
3. **Recommandation d'affichage** : ne montrer un filtre « projet Maroc » que lorsqu'il y a des
   candidats derrière, pour ne jamais afficher 0 résultat à un recruteur.
