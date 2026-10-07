# ui-ux-design — l'atelier des maquettes

Maquettes HTML de showcase-recruiter-webapp-saas : explorations d'écrans, essais de palette, arbitrages visuels. C'est
l'**atelier**, pas le design system — celui-ci vit dans `webapp/base/app/assets/css/` et se lit dans
[docs/showcase-recruiter-webapp-saas/content/4.design/](../docs/showcase-recruiter-webapp-saas/content/4.design/).

## La règle qui compte : `draft/` n'est pas versionné

```
ui-ux-design/
├── CLAUDE.md     VERSIONNÉ — ce fichier, les conventions de l'atelier
├── draft/        GITIGNORÉ — l'atelier : itérations, essais de palette, explorations
└── validate/     VERSIONNÉ — les écrans arbitrés, qui font foi
```

**Deux dossiers, pas trois.** Rien ne se pose à la racine : un fichier y est soit un brouillon qui
s'ignore, soit une référence qui fait foi — il n'existe pas de troisième statut. Les fiches de
palette y ont vécu un temps, elles sont redescendues dans `draft/` : ce sont des explorations, et
l'ambiance retenue se lit dans la charte, pas dans un essai.

`draft/` est dans le `.gitignore` de la racine. Conséquence à avoir en tête avant de générer :

- **Une maquette dans `draft/` n'existe que sur cette machine.** Elle ne part pas dans une PR, un
  collègue qui clone le dépôt ne l'aura pas, et elle disparaît avec le dossier de travail.
- **Rien n'est perdu tant qu'on itère** — c'est le but : 32 versions d'un même écran ne doivent pas
  peser sur l'historique du dépôt à perpétuité.
- **Une maquette qui devient une référence doit être promue dans `validate/`.** Tant qu'elle est
  dans `draft/`, aucun document ne doit la citer.

Cette dernière règle vient d'un vrai incident : la « source de vérité UI » de l'espace entreprise
a longtemps été citée par quatre documents de cadrage alors qu'elle vivait dans `draft/`.

## Promouvoir une maquette

Quand un écran est arbitré :

1. `mv draft/<fichier>.html validate/<fichier>.html` puis `git add validate/<fichier>.html`.
   **Pas `git mv`** : la source vit dans un dossier ignoré, Git ne la connaît pas et refuse. Il
   enregistrera un ajout, jamais un renommage — une maquette promue n'a pas de passé dans le dépôt.
2. Relire ses **chemins internes**. Les maquettes citent souvent leurs voisines en commentaire, et
   ces adresses survivent aux déménagements sans jamais être vérifiées. Un fichier versionné ne doit
   pas pointer vers une adresse morte.
3. Repointer les documents qui la citent vers `ui-ux-design/validate/…` — chemin **depuis la racine
   du dépôt**, jamais relatif : les chemins relatifs cassent au premier déplacement.
4. La commiter avec la tâche qui l'a produite.

## Redescendre une maquette

Le chemin inverse a son propre piège : **`.gitignore` ne désuit pas un fichier déjà suivi.** Un
`git mv validate/<fichier>.html draft/` le laisse versionné, simplement à une nouvelle adresse — la
règle du dossier ne s'applique pas.

```bash
git mv validate/<fichier>.html draft/<fichier>.html
git rm --cached draft/<fichier>.html     # ← sans ceci, il reste dans le dépôt
```

Le fichier reste sur le disque ; il quitte le dépôt. C'est bien l'effet voulu, mais il ne s'obtient
pas tout seul.

## Écrire une maquette

- **Un fichier, autoportant.** Tout est inline — CSS, JS, données de démonstration. Elle s'ouvre
  d'un double-clic dans un navigateur, sans build, sans serveur, sans dépendance réseau.
- **Aucun CDN, aucune police distante.** Une maquette doit rester lisible hors ligne et dans deux
  ans. Les fontes de marque sont Sora (titres) et Geist (interface) ; à défaut, `system-ui`.
- **Les deux thèmes.** L'application bascule clair / sombre, et les deux thèmes ne partagent ni
  neutre ni accent. Une maquette qui n'existe que dans un thème cache la moitié des problèmes.
- **Rester dans le vocabulaire de la marque** — cf.
  [brand-guidelines.md](../docs/showcase-recruiter-webapp-saas/content/4.design/brand-guidelines.md). Une maquette peut
  proposer autre chose : c'est même son rôle. Mais l'écart doit être un choix, pas une dérive.
- **Le contenu de démonstration est en français**, avec des données plausibles (des vrais intitulés
  de poste, des vraies villes). Un `Lorem ipsum` ne révèle aucun problème de mise en page.

## Nommer

`<sujet>-<précision>.html`, en minuscules, sans accent ni espace.

```
mockup-espace-entreprise-vue-cockpit-v12.html
color-palette-argile.html
fiche-essai-palette-n3.html
```

Les itérations se numérotent (`-v12`) plutôt que de s'écraser : comparer deux états côte à côte est
la moitié du travail d'arbitrage. Comme `draft/` n'est pas versionné, cette accumulation ne coûte
rien au dépôt — elle se nettoie à la main quand elle ne sert plus.

## Le rattachement Git

Ce dossier a sa lane : **`espace/design`**, préfixe de tâche **`UX`** (`UX-001-…`). Cf.
[git-workflow.md](../docs/showcase-recruiter-webapp-saas/content/1.getting-started/git-workflow.md).

Un commit ne porte donc que ce qui est versionné : `validate/` et ce
fichier. Si un commit ne contient aucun des deux, c'est que le travail est resté dans
l'atelier — ce qui est une situation normale, pas un oubli.
