# TASK ENT-022 — entreprise : le socle typographique et le solde en topbar

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30.
- **Type** : Frontend — socle webapp et espace entreprise, design.
- **Tests** : sans objet — la suite front est volontairement limitée au code PUR
  (`webapp/vitest.config.ts` : `shared/`, `services/`) ; cette carte ne touche que des tokens CSS et
  deux composants, et n'introduit aucune fonction pure.
- **Réf** : `ui-ux-design/draft/mockup-espace-entreprise-vue-cockpit-v23.html` · `ENT-023` et `ENT-024`
  (elles consomment les deux paliers posés ici) · `ENT-016` (la passe de cohérence, qui n'était pas
  celle-là) · [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md)

## Contexte

La direction graphique des écrans d'une recherche devait s'inspirer du **mockup v23**. Sa signature
la plus répétée n'est ni une couleur ni une fonte : c'est le traitement de ses **libellés de
section**. Petit corps, graisse forte, capitales, interlettrage — un libellé s'y lit comme une
étiquette, pas comme du contenu. Nous écrivons `text-caption uppercase text-muted` : la taille change
mais rien d'autre, et le libellé reste du texte parmi le texte.

Ce traitement n'est pas écrivable dans un composant. Le design system ferme l'échelle
typographique : « aucun `clamp()` ni `min-[…px]:` à la main — un besoin non couvert se règle en
ajoutant un palier dans `sizing.css`, pas en improvisant dans un écran ». La signature du mockup est
donc un **palier**, et un palier profite aux quatre espaces.

Deuxième sujet, sans rapport graphique mais de même nature — une valeur qui n'a pas sa place :
**le solde de crédits est affiché deux fois, et aux deux mauvais endroits**. Dans la barre haute de
la visionneuse, et dans le menu de sélection de la table. Les deux sont liés à une recherche, alors
que le solde vaut pour l'espace entier : il conditionne le geste le plus coûteux du produit, et le
chercher écran par écran n'a pas de sens.

## Périmètre

- `sizing.css` : deux paliers — un palier de **libellé** et un palier de **densité de table**.
- La neutralisation de l'interlettrage en écriture arabe, portée par le palier lui-même.
- La substitution des neuf libellés de section de l'espace entreprise.
- Le solde de crédits : une pastille dans la topbar, et son retrait des deux écrans de recherche.
- La charte et le design system, mis à jour dans le même changement.

## Hors périmètre

- **La mise en forme des deux écrans** — `ENT-023` pour la visionneuse, `ENT-024` pour la table.
  Cette carte pose les paliers, elle ne redessine rien.
- **Les trois autres espaces** : ils héritent des paliers, on ne convertit pas leurs libellés ici.
- **La console de facettes de 320 px** du mockup : non rouverte. Le mockup lui-même la masque dans
  la visionneuse — elle appartient à la table, et les facettes y restent dans leur tiroir.

## Décisions de cadrage

### Un palier, et non des valeurs dans les composants

Le traitement complet appartient au palier : corps, graisse, capitales et interlettrage sont
**appariés une fois**, comme les niveaux typographiques existants apparient déjà taille et
interlignage. Un composant écrit le palier et rien d'autre. C'est la seule forme conforme au design
system, et c'est aussi la seule qui rende le correctif RTL ci-dessous automatique.

### L'interlettrage tombe en écriture arabe

L'arabe est **cursif** : espacer les lettres brise les liaisons et le mot cesse d'être lisible.
Comme ces libellés sont traduits — `ar.yaml` existe et les layouts gèrent déjà `[dir='rtl']` —, le
palier neutralise son propre interlettrage sous `[dir='rtl']`. La graisse et les capitales restent :
elles ne nuisent pas, et les capitales sont sans effet sur une écriture qui n'en a pas.

Le correctif vit **dans le palier**, pas dans les écrans : posé ailleurs, il faudrait y penser à
chaque usage, donc le rater une fois.

### Les neuf libellés basculent, pas quatre

Seuls quatre des neuf libellés de section de l'espace sont sur les deux écrans redessinés. Les cinq
autres — « Mon profil », « L'équipe », le journal des crédits — basculent quand même : une signature
appliquée à la moitié d'un espace se lit comme une incohérence, pas comme une intention. La
substitution est mécanique et appartient au commit qui introduit le palier.

### Le solde n'a qu'une place

Il quitte la barre haute de la visionneuse et le menu de sélection de la table pour la **topbar**,
à gauche des notifications — la place que lui donne le mockup, et la seule qui corresponde à ce
qu'il est. Il vaut alors sur tout l'espace : les crédits, la fiche entreprise, l'équipe, autant que
les deux écrans de recherche.

Ce n'est **pas** un déplacement de présentation : c'est un retrait de deux écrans et un ajout dans
un composant partagé. D'où sa présence ici plutôt que dans `ENT-023` ou `ENT-024`, qui ne touchent
que de la mise en forme.

## Règles et cas limites

- Le solde est **lu** à l'ouverture et vit dans le store de dévoilement ; tant qu'il n'est pas
  chargé, la pastille ne s'affiche pas — un zéro affiché par défaut ferait croire à un compte vide et
  découragerait le geste.
- Le solde **suit une dépense sans rechargement** : c'est déjà le cas, la pastille lit la même
  source.
- Le palier de densité de table ne s'applique qu'aux cellules ; l'en-tête garde son propre rythme,
  il est traité par `ENT-024`.
- Un palier qui touche du texte sur aplat demande une **mesure de contraste** inscrite dans la
  charte : elle est due pour l'encre du libellé sur les deux thèmes.

## Documentation impactée

- [brand-guidelines.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md) — les deux paliers
  et leurs mesures de contraste.
- [design-system.md](../../docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md) — la table « Consommer
  un token », famille Typographie.

## Définition de terminé

- [x] Les deux paliers existent dans `sizing.css` et aucun écran n'écrit de valeur.
- [x] Les neuf libellés de section de l'espace consomment le palier de libellé.
- [x] Le palier annule son interlettrage en écriture arabe.
- [x] Le solde vit dans la topbar, sur tout l'espace, et ne figure plus sur les deux écrans de
      recherche.
- [x] Les contrastes sont mesurés dans les deux thèmes et inscrits dans la charte.
- [x] La charte et le design system sont à jour dans le même commit.
- [ ] Une dépense décrémente la pastille sans rechargement — **non vérifié**, cf. ci-dessous.

## Vérifications

Constaté sur le rendu réel de l'application, connecté avec un compte de recette :

- la feuille servie porte les quatre variables du palier, la règle de capitales, et
  `[dir='rtl'] .text-label { letter-spacing: normal }` ;
- la pastille de solde est rendue dans la topbar, avec son doublage pour lecteur d'écran ;
- les deux écrans de recherche ne portent plus le solde ;
- l'écran de parcours rendu en arabe sort en `dir="rtl"`, libellés traduits.

Contrastes **calculés** sur les tokens : `porcelain-600` sur `porcelain-200` = 5,71:1 en thème
clair, `shark-400` sur `shark-900` = 7,07:1 en sombre. Seuil applicable 4,5:1.

Restent à constater **dans un navigateur**, faute d'accès à un rendu visuel ici : l'aspect du palier
dans les deux thèmes, et la décrémentation du solde après une dépense — elle demande un clic et des
crédits.

## Aval

`registration/Field.vue` dessine son propre libellé en micro-capitales (`font: 600 10px…`,
`letter-spacing: 0.07em`, `text-transform: uppercase`) : c'est ce palier, écrit à la main avant
qu'il existe. Le tunnel d'inscription n'est pas dans le périmètre de cette carte, mais la
substitution est due.
