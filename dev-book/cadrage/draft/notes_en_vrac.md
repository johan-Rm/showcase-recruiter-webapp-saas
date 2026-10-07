# NOTES ENRICHI

# showcase-recruiter-webapp-saas — Notes de cadrage

## Noms de domaine

### Domaine principal

*   [`showcase-recruiter-webapp-saas.example`](http://showcase-recruiter-webapp-saas.example) — **domaine unique**

### Sous-domaines

*   [`api.showcase-recruiter-webapp-saas.example`](http://api.showcase-recruiter-webapp-saas.example) : API
*   [`candidat.showcase-recruiter-webapp-saas.example`](http://candidat.showcase-recruiter-webapp-saas.example) : espace candidat
*   [`entreprise.showcase-recruiter-webapp-saas.example`](http://entreprise.showcase-recruiter-webapp-saas.example) : espace entreprise
*   [`admin.showcase-recruiter-webapp-saas.example`](http://admin.showcase-recruiter-webapp-saas.example) : administration

* * *

# Brainstorm

## Produit & Architecture

*   Répondre à la question de la PWA.
*   Imaginer le modèle de données et l'interopérabilité avec Jarvi.
*   Déterminer si la création des candidats et des entreprises entraîne automatiquement leur création dans Jarvi.
*   Définir clairement la source de vérité entre showcase-recruiter-webapp-saas et Jarvi.
*   Prévoir un mode clair / mode sombre.
*   Prévoir une traduction en français, anglais et espagnol.

## Inscription & Onboarding

*   Mettre en place une inscription en 2 clics via connexion sociale (Google, LinkedIn, etc.).
*   Après l'authentification, démarrer un onboarding guidé pour compléter progressivement le profil.
*   Tant que l'onboarding n'est pas terminé, empêcher l'utilisation des fonctionnalités principales de l'application.
*   Définir précisément les critères d'un profil considéré comme « complet ».

## Landing Page

*   Reporter la conception de la landing page définitive à une phase ultérieure afin de se concentrer d'abord sur le cœur du produit.
*   Profiter de ce délai pour réfléchir à une véritable stratégie marketing et au positionnement de showcase-recruiter-webapp-saas.
*   Dans un premier temps, générer automatiquement une landing page simple mais efficace permettant de présenter le service et d'accéder aux espaces candidat et recruteur.

## Business Model

*   Lister l'ensemble des fonctionnalités payantes de la plateforme.
*   Identifier les fonctionnalités gratuites.
*   Identifier les fonctionnalités consommant des crédits.
*   Identifier les fonctionnalités réservées aux abonnements premium.
*   Identifier les options ponctuelles et les services additionnels.
*   Définir clairement les leviers de monétisation de showcase-recruiter-webapp-saas.

* * *

# Questions d'architecture

## Progressive Web App (PWA)

À étudier :

*   installation sur mobile et desktop ;
*   fonctionnement hors ligne partiel ;
*   notifications push ;
*   expérience utilisateur proche d'une application native.

## Modèle de données et interopérabilité avec Jarvi

Définir précisément :

*   le modèle de données showcase-recruiter-webapp-saas ;
*   les échanges avec Jarvi ;
*   les données propres à showcase-recruiter-webapp-saas ;
*   les données synchronisées ;
*   les données récupérées à la demande.

## Synchronisation des créations

Pour les candidats :

*   création dans showcase-recruiter-webapp-saas ;
*   synchronisation automatique vers Jarvi ;
*   gestion des erreurs de synchronisation.

Pour les entreprises :

*   création dans showcase-recruiter-webapp-saas ;
*   synchronisation éventuelle vers Jarvi ;
*   définition de la source de vérité.

## Thème graphique

*   Mode clair.
*   Mode sombre.

## Internationalisation

*   Français 🇫🇷
*   Anglais 🇬🇧
*   Espagnol 🇪🇸

* * *

# Idées V2

## Recherches enregistrées et alertes recruteurs

Permettre à un recruteur d'enregistrer une recherche afin de créer une alerte automatique.

Critères envisageables :

*   mots-clés ;
*   recherche IA ;
*   compétences ;
*   langues ;
*   localisation ;
*   prétentions salariales ;
*   disponibilité ;
*   projet de mobilité ;
*   dates de présence prévues au Maroc.

Lorsqu'un nouveau profil correspond à cette recherche :

*   notification dans l'application ;
*   envoi d'un e-mail ;
*   notification push (si PWA).

Cette fonctionnalité implique la création d'une entité métier **Recherche enregistrée**.

* * *

# Notes en vrac

## Exploiter la chaîne LinkedIn → Jarvi → showcase-recruiter-webapp-saas

Si LinkedIn alimente déjà Jarvi et que Jarvi alimente ensuite showcase-recruiter-webapp-saas, il devient possible de proposer une expérience utilisateur beaucoup plus fluide.

Objectifs :

*   réduire le temps d'inscription ;
*   préremplir automatiquement les profils ;
*   éviter les doubles saisies ;
*   réutiliser les données déjà disponibles dans Jarvi.

L'onboarding showcase-recruiter-webapp-saas pourrait alors se concentrer uniquement sur les informations spécifiques à la plateforme :

*   projet de retour au Maroc ;
*   dates de présence prévues au Maroc ;
*   préférences de visibilité ;
*   options premium ;
*   informations métier complémentaires.

Cette approche permettrait de proposer une inscription extrêmement rapide tout en conservant une excellente qualité des données.
