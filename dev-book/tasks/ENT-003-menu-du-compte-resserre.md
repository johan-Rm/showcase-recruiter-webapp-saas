# TASK ENT-003 — entreprise : le menu du compte se resserre

- **Créée le** : 2026-07-28.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise — navigation.
- **Réf** : revue des écrans de l'espace entreprise du 2026-07-28.

## Contexte

Le menu de l'avatar aligne six entrées au même niveau — Accueil, Mes recherches, Crédits, L'équipe,
L'entreprise, Mon profil — sans dire lesquelles servent au travail et lesquelles à l'administration
du compte.

## Décisions

**Deux entrées dans la première section** : **Mes recherches** et **Mon compte**.

**« Mon compte » regroupe** ce qui était étalé : L'équipe, Crédits, L'entreprise, Mon profil.

Le reste du menu ne bouge pas — Langue, Apparence, Se déconnecter.

## Ce que cela entraîne

**« Accueil » disparaît du menu.** Le retour à l'accueil repose alors sur le logo. C'est l'usage
courant, mais il doit devenir **explicite** plutôt qu'implicite : le logo est un lien, il doit se
comporter comme tel — état actif, cible accessible au clavier, intitulé lisible par un lecteur
d'écran.

**« Mon compte » est une PAGE** avec sous-navigation, pas un sous-menu déroulant. Chaque rubrique
garde son URL — partageable, atteignable au clavier, et le retour arrière du navigateur fonctionne.
Un menu à deux niveaux économisait un clic au prix de tout cela, et « Mon compte » ne serait jamais
devenu un endroit où l'on est.

Les quatre routes existantes restent valides dans les deux cas — seule leur porte d'entrée change.

## Critères d'acceptation

- [x] La première section du menu ne porte que Mes recherches et Mon compte.
- [x] Les quatre rubriques restent atteignables, chacune à son adresse.
- [x] Le retour à l'accueil est atteignable au clavier et annoncé.

## Mise en œuvre

**La liste des rubriques vit à un seul endroit** (`useAccountSections`). Le menu de l'avatar et la
sous-navigation l'affichent tous les deux ; recopiée, elle aurait divergé à la première rubrique
ajoutée — et le filtre de rôle avec elle, ce qui aurait fini par montrer à un membre une entrée
qu'il ne peut pas ouvrir.

**La sous-navigation est posée sur CHAQUE rubrique**, pas seulement sur `/mon-compte`. C'est ce qui
fait du compte un endroit où l'on est : sans elle, passer des crédits à l'équipe demanderait de
repasser par le menu de l'avatar.

Les quatre rubriques répétaient la même coque à l'identique. Elle vit maintenant en un exemplaire
(`AccountSection`), et chaque page ne déclare plus que sa clé.

`/mon-compte` **ne redirige pas** vers la première rubrique : les rubriques visibles dépendent du
rôle, et une redirection aurait fait atterrir un membre et un propriétaire à deux endroits
différents sous la même adresse.

**Le logo devient un vrai lien** : il annonce sa destination (« showcase-recruiter-webapp-saas — retour à l'accueil » et
non le seul nom de la marque), et porte un anneau de focus. Depuis qu'« Accueil » a quitté le menu,
il en est le seul chemin — un logo qu'on ne peut pas tabuler n'est pas une navigation.

## Limite connue

Le filtre de rôle est une **courtoisie de navigation, pas une autorisation** : `/l-equipe` et
`/l-entreprise` restent atteignables à l'adresse par un membre. C'était déjà le cas quand le menu
seul filtrait. Les deux pages ne servent aujourd'hui aucune donnée ; la garde devra être posée en
même temps que leur contenu, et l'API refusera de toute façon ce qu'elle doit refuser.
