# TASK ENT-132 — entreprise : le menu éteint les canaux sans donnée, et dit pourquoi

- **Créée le** : 2026-08-14.
- **Session** : `1b9df7cf-791f-44d5-89cc-73055139d1ff`.
- **Statut** : ✅ fait le 2026-08-17 — le menu cesse de proposer un canal qui n'a rien à ouvrir, et
  quand il ne propose rien il dit laquelle des deux raisons ; les quatre canaux sont couverts, un
  canal ne s'éteignant que sur une réponse explicite du serveur.
- **Type** : fix — le menu de la sélection cesse de proposer un canal qui n'a rien à ouvrir, et
  distingue les deux raisons de ne rien proposer.
- **Tests** : `webapp/services/search/reveal.vitest.ts` — le compte à payer devient sensible à la
  **présence** de la donnée, et c'est du code pur : lot où un profil a la donnée et l'autre non, lot
  où tous l'ont déjà payée, lot où aucun ne l'a du tout, et la distinction entre les deux zéros. Les
  cinq cas existants restent.
- **Réf** : `API-063` — la garde de facturation, qui protège l'argent · `API-064` — les deux
  booléens de présence, **dont cette carte dépend** pour le CV et le compte rendu · `ENT-122` — le
  menu de la sélection et le coût par canal · `ENT-116` — le compte à payer, fonction pure ·
  [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) §5

## Contexte

**Le menu propose de payer un canal qui n'a rien à servir**, et l'écran ne sait pas dire pourquoi un
canal est éteint.

Le compte à payer ne regarde qu'une chose : ce qui est **déjà acheté**. Il ignore la présence de la
donnée, pourtant descendue sur la carte pour l'e-mail et le téléphone — et affichée deux colonnes
plus loin, où un tiret annonce l'absence.

Sur le vivier réel, ce n'est pas un cas de bord : **1 % des profils ont un e-mail**. Un recruteur qui
coche deux profils et clique « E-mail » achète presque toujours du vide.

Le défaut a été trouvé par un recruteur qui trouvait le compteur faux — « E-mail · 1 cr. » sur deux
profils dont il pensait qu'aucun n'était dévoilé. Le compte était juste : l'un des deux avait son
e-mail **payé**. Mais ce profil **n'a pas d'e-mail**, donc la colonne affichait « — » avant comme
après le paiement, et rien à l'écran ne permettait de le savoir.

**Deux zéros différents se lisent aujourd'hui pareil** :

```
CV · 0 cr.     ← les deux profils l'ont déjà obtenu     → bonne nouvelle
CV · 0 cr.     ← aucun des deux n'a de CV                → il faudra chercher ailleurs
```

## Périmètre

- **Le compte à payer exclut les profils sans donnée** sur le canal, en plus de ceux qui ont payé.
- **Le miroir `webapp/shared/types/candidate-card.ts` reçoit `hasResume` et `hasReport`**, à la
  main et **optionnels** : aucune gate ne vérifie ce fichier, la carte n'existe pas dans l'OpenAPI.
- **Le menu remplace « 0 cr. »** par deux libellés distincts — **« déjà obtenu »** quand tout est
  payé, **« aucun »** quand rien n'existe —, dans les trois langues.
- **L'entrée reste inactive** dans les deux cas : ce qui change est ce qu'elle dit, pas ce qu'elle
  permet.

## Hors périmètre

- **Le tiroir d'aperçu du profil**, qui affiche les mêmes canaux et gardera son rendu.
- **Le seuil de confirmation et le plafond de lot**, inchangés.
- **Les dévoilements déjà payés pour rien** : cette carte les rend impossibles à l'avenir, elle ne
  répare pas le passé.
- **La colonne E-mail et la colonne Tél.** du tableau, dont le tiret dit déjà l'absence.

## Décisions de cadrage

### Deux libellés plutôt qu'un zéro muet

Écarté : garder « 0 cr. » et l'entrée éteinte, ce qui ne coûtait aucune clé de traduction. Mais
« déjà obtenu » et « aucun » ne disent pas la même chose — le premier est une bonne nouvelle, le
second dit qu'il faudra chercher la personne autrement. Les confondre sous un même zéro reconduit en
plus petit le défaut qu'on corrige : **un écran qui ne dit pas ce qu'il sait**.

Écarté aussi : ne distinguer que sur la fiche d'un profil et pas sur le lot, au motif qu'un lot
mélange souvent les deux cas. Un lot mixte a bien une réponse : il reste des profils à payer, donc le
canal affiche son coût. Les deux libellés ne s'appliquent qu'aux cas **purs**, où le compte est nul.

### ~~Le CV et le compte rendu attendent le contrat~~ — les quatre canaux, décidé à l'implémentation

L'e-mail et le téléphone pouvaient être gardés tout de suite ; les deux autres attendaient
`API-064`, et cette carte prévoyait donc de couvrir **deux canaux sur quatre**, ou d'attendre.

`API-064` est livrée — mais **son merge est bloqué**, et la lane entreprise part de `develop` : l'API
qu'elle interroge ne renvoie pas encore les deux signaux. Traiter cette absence comme « rien à
servir » aurait éteint le CV et le compte rendu **sur tout le vivier**, rendant un contenu payant
inatteignable partout où le déploiement a du retard. Ce serait une régression bien pire que le défaut
corrigé.

**Décision** : les quatre canaux sont livrés, et **un canal ne s'éteint que sur une réponse
explicite**. Un signal absent vaut « on ne sait pas », donc le coût s'affiche comme avant. Aucune
contrainte d'ordre de merge, aucune régression possible dans un sens comme dans l'autre, et l'argent
reste protégé par la garde serveur d'`API-063`, déjà livrée.

**Prix payé, et il est visible** : les deux champs sont **optionnels** dans le miroir écrit à la
main. Ils redeviendront obligatoires quand la version qui les envoie sera partout — c'est écrit dans
le miroir lui-même, pas seulement ici.

### Le compte à payer devient sensible à la présence, et c'est le service qui le porte

Écarté : filtrer dans la page, au moment de construire le menu. Le compte à payer sert **deux**
endroits — le libellé du menu et le récapitulatif de la confirmation — et il est déjà dans la couche
pure pour cette raison. L'y laisser garantit que le prix annoncé avant le clic et celui confirmé
après ne peuvent pas diverger.

## Règles et cas limites

- **Lot mixte** — certains ont la donnée, d'autres non : le canal affiche le coût des seuls
  facturables. C'est le cas courant, et il n'a pas de libellé spécial.
- **Un profil sans e-mail reste sélectionnable** : la sélection sert aussi les autres canaux. La
  garde est **par canal**, jamais par profil.
- **Un canal à la fois vide et déjà payé** — le cas des trois dévoilements fautifs mesurés : il
  affiche « aucun », parce que c'est l'information utile pour la suite.
- **Recherche partagée en lecture seule** : le premier groupe du menu n'apparaît pas du tout, rien
  ne change ici.
- **Signal de présence absent** : le canal **n'est pas éteint** — voir *Décisions de cadrage*.
- **Trois langues** : les deux libellés sont posés dans `fr`, `en` et `ar`, sous peine d'échec du
  test de parité.
- **Le coût reste calculé sur la sélection courante**, pas sur la vue : un profil coché puis sorti de
  la vue compte toujours.

## Documentation impactée

- [vue_la_table.md](../cadrage/entreprise/ui/vue_la_table.md) — §5, le tableau du menu : ce qu'un
  canal affiche selon qu'il reste à payer, qu'il est acquis, ou qu'il n'y a rien.

## Vérification

**Les trois états du menu, capturés sur l'écran réel**, en cochant deux profils puis en ouvrant le
menu de la sélection.

Deux profils qu'aucune coordonnée ne permet de joindre — les colonnes E-mail et Tél. affichent « — »
sur les deux lignes :

```
E-mail · aucun          ← éteint
Téléphone · aucun       ← éteint
CV · 2 cr.
Compte rendu · 2 cr.
```

Deux profils joignables, rien de payé — le cas courant, inchangé :

```
E-mail · 2 cr.   Téléphone · 2 cr.   CV · 2 cr.   Compte rendu · 2 cr.
```

Les mêmes après paiement de l'e-mail des deux :

```
E-mail · déjà obtenu    ← éteint, pour la bonne raison
Téléphone · 2 cr.
```

**Le signal absent n'éteint rien, vérifié à la source** : l'API de cette lane vient de `develop`,
sans `API-064`. La réponse de recherche renvoie `hasResume: null` — et le CV affiche son coût au lieu
de s'éteindre à tort.

**Tests** : `bun run vitest run services/search/reveal.vitest.ts` → **21 tests verts**.

## Livré

- `webapp/shared/types/candidate-card.ts` **(M)** — `hasResume` et `hasReport`, optionnels, avec la
  raison de leur optionalité et la condition pour la lever.
- `webapp/services/search/reveal.ts` **(M)** — `hasDataOn` (la présence, avec le silence traité
  comme « on ne sait pas »), `payableCount` qui cumule les deux exclusions, et `channelState` qui
  sépare les deux zéros.
- `webapp/spaces/entreprise/app/pages/recherches/[uuid]/table.vue` **(M)** — le libellé d'un canal
  devient le coût, « déjà obtenu » ou « aucun ».
- `webapp/base/i18n/locales/{fr,en,ar}.yaml` **(M)** — les deux libellés dans les trois langues.
- `webapp/services/search/reveal.vitest.ts` **(M)** — neuf cas neufs ; le profil de test déclare
  désormais avoir de quoi servir sur les quatre canaux, sans quoi le nouveau compte les exclurait.
- `dev-book/cadrage/entreprise/ui/vue_la_table.md` **(M)** — les trois réponses d'un canal, et la
  règle de prudence sur le signal absent.

## Reste à faire (hors périmètre)

- **Rendre les deux champs obligatoires** dans le miroir quand la version d'API qui les sert est
  déployée partout — la note est dans le miroir lui-même.
- **Le tiroir d'aperçu** affiche les mêmes canaux et garde son rendu : hors périmètre assumé.
- **Deux crédits consommés** dans la base de développement de la lane pour produire l'état « déjà
  obtenu » : le solde de l'entreprise de démo y est passé de 2 à 0.

## Définition de terminé

- [x] Un canal dont aucun profil sélectionné n'a la donnée affiche « aucun » et reste inactif.
- [x] Un canal dont tous les profils l'ont déjà payée affiche « déjà obtenu » et reste inactif.
- [x] Un lot mixte affiche le coût des seuls profils facturables.
- [x] Les quatre canaux sont couverts — l'e-mail, le téléphone, le CV et le compte rendu.
- [x] Le miroir du contrat porte les deux booléens nouveaux, **optionnels** : voir *Décisions de
      cadrage*.
- [x] Un signal de présence absent n'éteint aucun canal.
- [x] Les trois langues sont renseignées.
- [x] Le compte à payer est couvert par les cas listés en tête de carte.
