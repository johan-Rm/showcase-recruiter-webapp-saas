# base/app/layouts/

Gabarits de mise en page communs aux routes.

## Rôle et responsabilités

**Rôle Nuxt :** un layout est une enveloppe réutilisable autour des pages ; il rend la route dans son `<slot />` et persiste entre les navigations qui partagent le même gabarit.

**Rôle attendu :** le layout est le **gabarit structurel persistant** :

`Route → Gabarit (layouts/) → Modèle de page (pages/) → Éléments (components/)`

Il porte le chrome commun — en-tête, navigation, modals, side panels, overlays et conteneur principal.

## Conventions techniques

### Marqueurs structurels

- `data-layout` est porté par la racine du layout et reprend son nom en kebab-case ;
- `data-section` identifie les grandes zones structurelles définies par le layout.

Les marqueurs sont statiques, descriptifs et ne servent jamais au styling. Aucun wrapper n'est ajouté uniquement pour porter un marqueur.

### Le repère principal appartient au layout

**Le layout pose le `<main>` autour de son `<slot />`, et il y en a exactement un par document.** Les pages n'en écrivent pas ; leurs `<section>` et `<aside>` restent à elles.

Employer `<UMain>` en neutralisant son `min-h` lorsque nécessaire. Deux règles CI tiennent l'invariant : `layouts-single-main` et `pages-no-main`.

### Espacement du gabarit

Le rythme d'un layout s'applique à tous les écrans qu'il porte. Utiliser les paliers du design system plutôt que des espacements Tailwind bruts.

Règle CI : `layouts-no-raw-spacing`.

### La coque d'entrée tient dans la fenêtre

`auth.vue` demande la hauteur **exacte** de la fenêtre, pas « au moins » cette hauteur. Tout écran qu'elle porte — connexion, saisie de l'e-mail, « lien envoyé », définition du mot de passe — doit y tenir sans défiler.

Quand la fenêtre est basse, ce sont les **blancs qui cèdent** : les grands paliers se rabattent sur un palier plus serré de l'échelle, jamais sur une valeur écrite à la main. Le palier visé n'est jamais redéfini dans le même bloc — les variables CSS se résolvent à l'usage, et l'enchaînement ferait sauter deux crans au lieu d'un.

Aucun `overflow: hidden` : si le rabattement ne suffit pas, la page redevient défilable. Un écran d'entrée qu'on ne peut plus valider est pire qu'un écran qui défile.

**Ajouter un bloc à l'un de ces écrans se mesure**, hauteur du document contre hauteur de la fenêtre : c'est l'ajout de deux boutons empilés qui avait fait déborder l'écran sans que rien ne le signale.

> TODO — Revoir ces 2 règles de conventions techniques afin de les simplifier et les vulgariser.

Les règles transverses de la couche `app/` s'appliquent par ailleurs (voir [../README.md](../README.md)).
